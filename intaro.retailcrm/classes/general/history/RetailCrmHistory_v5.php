<?php

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Context;
use Bitrix\Main\NotSupportedException;
use Bitrix\Main\SystemException;
use Bitrix\Sale\Basket;
use Bitrix\Sale\Delivery\Services\EmptyDeliveryService;
use Bitrix\Sale\Delivery\Services\Manager;
use Bitrix\Sale\Fuser;
use Bitrix\Sale\Internals\PaymentTable;
use Bitrix\Sale\Location\Search\Finder;
use Bitrix\Sale\Order;
use Bitrix\Sale\OrderUserProperties;
use Bitrix\Sale\Payment;
use Intaro\RetailCrm\Component\ServiceLocator;
use Intaro\RetailCrm\Component\Builder\Bitrix\LoyaltyDataBuilder;
use Intaro\RetailCrm\Service\ManagerService;
use Intaro\RetailCrm\Service\OrderLoyaltyDataService;
use Intaro\RetailCrm\Component\ConfigProvider;
use Intaro\RetailCrm\Component\Constants;
use Intaro\RetailCrm\Component\Handlers\EventsHandlers;

IncludeModuleLangFile(__FILE__);
class RetailCrmHistory
{
    public static $MODULE_ID = 'intaro.retailcrm';
    public static $CRM_API_HOST_OPTION = 'api_host';
    public static $CRM_API_KEY_OPTION = 'api_key';
    public static $CRM_ORDER_TYPES_ARR = 'order_types_arr';
    public static $CRM_DELIVERY_TYPES_ARR = 'deliv_types_arr';
    public static $CRM_PAYMENT_TYPES = 'pay_types_arr';
    public static $CRM_PAYMENT_STATUSES = 'pay_statuses_arr';
    public static $CRM_PAYMENT = 'payment_arr'; //order payment Y/N
    public static $CRM_ORDER_LAST_ID = 'order_last_id';
    public static $CRM_SITES_LIST = 'sites_list';
    public static $CRM_ORDER_PROPS = 'order_props';
    public static $CRM_LEGAL_DETAILS = 'legal_details';
    public static $CRM_CUSTOM_FIELDS = 'custom_fields';
    public static $CRM_CONTRAGENT_TYPE = 'contragent_type';
    public static $CRM_ORDER_FAILED_IDS = 'order_failed_ids';
    public static $CRM_ORDER_HISTORY = 'order_history';
    public static $CRM_CUSTOMER_CORPORATE_HISTORY = 'customer_corp_history';
    public static $CRM_CATALOG_BASE_PRICE = 'catalog_base_price';
    public static $CRM_ORDER_NUMBERS = 'order_numbers';
    public static $CRM_CANSEL_ORDER = 'cansel_order';
    public static $CRM_CURRENCY = 'currency';
    public static $CRM_DISCOUNT_ROUND = 'discount_round';

    const CANCEL_PROPERTY_CODE = 'INTAROCRM_IS_CANCELED';

    public static function customerHistory()
    {
        if (!RetailcrmDependencyLoader::loadDependencies()) {
            return false;
        }

        $historyFilter = array();
        $historyStart = RetailcrmConfigProvider::getCustomersHistorySinceId();
        $api = new RetailCrm\ApiClient(RetailcrmConfigProvider::getApiUrl(), RetailcrmConfigProvider::getApiKey());

        if ($historyStart && $historyStart > 0) {
            $historyFilter['sinceId'] = $historyStart;
        }

        while (true) {
            $customerHistory = RCrmActions::apiMethod($api, 'customersHistory', __METHOD__, $historyFilter);

            $customerH = isset($customerHistory['history']) ? $customerHistory['history'] : array();

            Logger::getInstance()->write($customerH, 'customerHistory');

            if (is_array($customerH) && count($customerH) === 0) {
                if ($customerHistory['history']['totalPageCount'] > $customerHistory['history']['currentPage']) {
                    $historyFilter['page'] = $customerHistory['history']['currentPage'] + 1;

                    continue;
                }

                return true;
            }

            $customers = self::assemblyCustomer($customerH);

            $GLOBALS['RETAIL_CRM_HISTORY'] = true;

            $newUser = new CUser();
            $customerBuilder = new CustomerBuilder();

            foreach ($customers as $customer) {
                if (function_exists('retailCrmBeforeCustomerSave')) {
                    $newResCustomer = retailCrmBeforeCustomerSave($customer);
                    if (is_array($newResCustomer) && !empty($newResCustomer)) {
                        $customer = $newResCustomer;
                    } elseif ($newResCustomer === false) {
                        RCrmActions::eventLog('RetailCrmHistory::customerHistory', 'retailCrmBeforeCustomerSave()', 'UserCrmId = ' . $customer['id'] . '. Sending canceled after retailCrmBeforeCustomerSave');

                        continue;
                    }
                }

                if (isset($customer['deleted'])) {
                    continue;
                }

                if (RetailcrmConfigProvider::isPhoneRequired()) {
                    if (empty($customer['phones'])) {
                        Logger::getInstance()->write('$customer["phones"] is empty. Customer ' . $customer['id'] . ' cannot be created', 'createCustomerError');
                        continue;
                    }
                }

                if (isset($customer['externalId']) && !is_numeric($customer['externalId'])) {
                    unset($customer['externalId']);
                }

                $customerBuilder->setDataCrm($customer)->build();

                if (!isset($customer['externalId'])) {
                    if (!isset($customer['id'])) {
                        continue;
                    }

                    $registerNewUser = true;

                    if (!empty($customer['email'])) {
                        $dbUser = CUser::GetList(($by = 'ID'), ($sort = 'ASC'), array('=EMAIL' => $customer['email']));
                        switch ($dbUser->SelectedRowsCount()) {
                            case 0:
                                $login = $customer['email'];
                                $customerBuilder->setLogin($login);
                                break;
                            case 1:
                                $arUser = $dbUser->Fetch();
                                $registeredUserID = $arUser['ID'];
                                $registerNewUser = false;
                                break;
                            default:
                                $login = uniqid('user_' . time()) . '@example.com';
                                $customerBuilder->setLogin($login);
                                break;
                        }
                    }

                    if ($registerNewUser === true) {
                        $customerBuilder->buildPassword();
                        $registeredUserID = $newUser->Add(
                            $customerBuilder->getCustomer()->getObjectToArray()
                        );

                        if ($registeredUserID === false) {
                            RCrmActions::eventLog(
                                'RetailCrmHistory::orderHistory',
                                'CUser::Register',
                                'Error register user: ' . $newUser->LAST_ERROR
                            );

                            continue;
                        }
                    }

                    if(RCrmActions::apiMethod(
                            $api,
                            'customersFixExternalIds',
                            __METHOD__,
                            array(array('id' => $customer['id'], 'externalId' => $registeredUserID))) == false
                    ) {
                        continue;
                    }

                    $customer['externalId'] = $registeredUserID;
                }

                if (isset($customer['externalId'])) {
                    $customerBuilder->setDataCrm($customer);
                    if (isset($customer['phones'])) {
                        $customerBuilder->setUser(
                            CUser::GetList(
                                ($by = "ID"),
                                ($order = "desc"),
                                array('ID' => $customer['externalId']),
                                array('FIELDS' => array('PERSONAL_PHONE', 'PERSONAL_MOBILE'))
                            )->fetch()
                        );
                    }

                    $u = $newUser->Update($customer['externalId'], $customerBuilder->getCustomer()->getObjectToArray());
                    if (!$u) {
                        RCrmActions::eventLog(
                            'RetailCrmHistory::customerHistory',
                            'Error update user',
                            $newUser->LAST_ERROR
                        );
                    }

                    if (function_exists('retailCrmAfterCustomerSave')) {
                        retailCrmAfterCustomerSave($customer);
                    }
                }

                $customerBuilder->reset();
            }

            $GLOBALS['RETAIL_CRM_HISTORY'] = false;

            //last id
            $end = array_pop($customerH);
            RetailcrmConfigProvider::setCustomersHistorySinceId($end['id']);

            if ($customerHistory['pagination']['totalPageCount'] == 1) {
                return true;
            }
            //new filter
            $historyFilter['sinceId'] = $end['id'];
        }
    }

    /**
     * @return bool
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     * @throws \Bitrix\Main\ArgumentTypeException
     * @throws \Bitrix\Main\NotImplementedException
     * @throws \Bitrix\Main\NotSupportedException
     * @throws \Bitrix\Main\ObjectException
     * @throws \Bitrix\Main\ObjectNotFoundException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function orderHistory(): bool
    {
        global $USER;

        if (is_object($USER) === false) {
            $USER = new RetailUser();
        }

        if (!RetailcrmDependencyLoader::loadDependencies()) {
            return false;
        }

        $optionsOrderTypes = RetailcrmConfigProvider::getOrderTypes();
        $optionsDelivTypes = array_flip(RetailcrmConfigProvider::getDeliveryTypes());
        $optionsPayStatuses = array_flip(RetailcrmConfigProvider::getPaymentStatuses()); // --statuses
        $optionsOrderProps = RetailcrmConfigProvider::getOrderProps();
        $optionsLegalDetails = RetailcrmConfigProvider::getLegalDetails();
        $optionsOrderNumbers = RetailcrmConfigProvider::getOrderNumbers();
        $optionsCanselOrder = RetailcrmConfigProvider::getCancellableOrderPaymentStatuses();
        $currency = RetailcrmConfigProvider::getCurrencyOrDefault();
        $contragentTypes = array_flip(RetailcrmConfigProvider::getContragentTypes());
        $shipmentDeducted = RetailcrmConfigProvider::getShipmentDeducted();

        $api = new RetailCrm\ApiClient(RetailcrmConfigProvider::getApiUrl(), RetailcrmConfigProvider::getApiKey());

        /* @var OrderLoyaltyDataService $orderLoyaltyDataService */
        $orderLoyaltyDataService = ServiceLocator::get(OrderLoyaltyDataService::class);

        $historyFilter = array();
        $historyStart = COption::GetOptionString(self::$MODULE_ID, self::$CRM_ORDER_HISTORY);

        if ($historyStart && $historyStart > 0) {
            $historyFilter['sinceId'] = $historyStart;
        }

        while (true) {
            $orderHistory = RCrmActions::apiMethod($api, 'ordersHistory', __METHOD__, $historyFilter);
            $orderH = $orderHistory['history'] ?? [];

            Logger::getInstance()->write($orderH, 'orderHistory');

            if (is_array($orderH) && count($orderH) === 0) {
                if ($orderHistory['history']['totalPageCount'] > $orderHistory['history']['currentPage']) {
                    $historyFilter['page'] = $orderHistory['history']['currentPage'] + 1;

                    continue;
                }

                return true;
            }

            $orders = self::assemblyOrder($orderH);
            $GLOBALS['RETAIL_CRM_HISTORY'] = true;

            //orders with changes
            foreach ($orders as $order) {
                if (function_exists('retailCrmBeforeOrderSave')) {
                    $newResOrder = retailCrmBeforeOrderSave($order);

                    if (is_array($newResOrder) && !empty($newResOrder)) {
                        $order = $newResOrder;
                    } elseif ($newResOrder === false) {
                        RCrmActions::eventLog('RetailCrmHistory::orderHistory',
                            'retailCrmBeforeOrderSave()',
                            'OrderCrmId = ' . $order['id'] . '. Sending canceled after retailCrmBeforeOrderSave'
                        );

                        continue;
                    }
                }

                Logger::getInstance()->write($order, 'assemblyOrderHistory');

                if (isset($order['deleted'])) {
                    if (isset($order['externalId'])) {
                        self::cancelOrder($order['externalId']);
                    }

                    continue;
                }

                $site = self::getSite($order['site']);

                if (empty($site)) {
                    RCrmActions::eventLog(
                        __CLASS__ . '::' . __METHOD__,
                        'Bitrix\Sale\Order::create',
                        'Site = ' . $order['site'] . ' not found in setting. Order crm id=' . $order['id']
                    );

                    continue;
                }

                if (isset($order['customer']['externalId']) && !is_numeric($order['customer']['externalId'])) {
                    unset($order['customer']['externalId']);
                }

                $corporateCustomerBuilder = new CorporateCustomerBuilder();

                $corporateContact = array();
                $orderCustomerExtId = $order['customer']['externalId'] ?? null;
                $corporateCustomerBuilder->setOrderCustomerExtId($orderCustomerExtId)
                    ->setContragentTypes($contragentTypes)
                    ->setDataCrm($order)
                    ->build();
                $corporateCustomerBuilder->getCustomerBuilder()->buildPassword();

                if (RetailCrmOrder::isOrderCorporate($order)) {
                    // Fetch contact only if we think it's data is not fully present in order
                    if (!empty($order['contact'])) {
                        if (isset($order['contact']['email'])) {
                            $corporateContact = $order['contact'];

                            $orderCustomerExtId = $corporateContact['externalId'] ?? null;
                            $corporateCustomerBuilder->setCorporateContact($corporateContact)
                                ->setOrderCustomerExtId($orderCustomerExtId);

                        } else {
                            $response = false;

                            if (isset($order['contact']['externalId'])) {
                                $response = RCrmActions::apiMethod(
                                    $api,
                                    'customersGet',
                                    __METHOD__,
                                    $order['contact']['externalId'],
                                    $order['site']
                                );
                            } elseif (isset($order['contact']['id'])) {
                                $response = RCrmActions::apiMethod(
                                    $api,
                                    'customersGetById',
                                    __METHOD__,
                                    $order['contact']['id'],
                                    $order['site']
                                );
                            }

                            if ($response && isset($response['customer'])) {
                                $corporateContact = $response['customer'];

                                $orderCustomerExtId = $corporateContact['externalId'] ?? null;
                                $corporateCustomerBuilder->setCorporateContact($corporateContact)
                                    ->setOrderCustomerExtId($orderCustomerExtId);
                            }
                        }
                    }
                }

                if (!isset($order['externalId'])) {
                    if (empty($orderCustomerExtId)) {
                        if (!isset($order['customer']['id'])
                            || (RetailCrmOrder::isOrderCorporate($order)
                                && (!isset($order['contact']['id']) || !isset($order['customer']['id'])))
                        ) {
                            continue;
                        }

                        if (RetailcrmConfigProvider::isPhoneRequired()) {
                            if (empty($order['customer']['phones'])) {
                                Logger::getInstance()->write('$customer["phones"] is empty. Order ' . $order['id'] . ' cannot be created', 'createCustomerError');
                                continue;
                            }
                        }

                        $login = null;
                        $registerNewUser = true;

                        if (!isset($order['customer']['email']) || empty($order['customer']['email'])) {
                            if (RetailCrmOrder::isOrderCorporate($order) && !empty($corporateContact['email'])) {
                                $login = $corporateContact['email'];
                                $order['customer']['email'] = $corporateContact['email'];
                                $corporateCustomerBuilder->setLogin($login)
                                    ->setEmail($corporateContact['email']);
                            } else {
                                $login = uniqid('user_' . time()) . '@example.com';
                                $order['customer']['email'] = $login;
                                $corporateCustomerBuilder->setLogin($login)
                                    ->setEmail($login);
                            }
                        }

                        $dbUser = CUser::GetList(
                            ($by = 'ID'),
                            ($sort = 'ASC'),
                            array('=EMAIL' => $order['customer']['email'])
                        );

                        switch ($dbUser->SelectedRowsCount()) {
                            case 0:
                                $login = $order['customer']['email'];
                                $corporateCustomerBuilder->setLogin($login);
                                break;
                            case 1:
                                $arUser = $dbUser->Fetch();
                                $registeredUserID = $arUser['ID'];
                                $registerNewUser = false;
                                break;
                            default:
                                $login = uniqid('user_' . time()) . '@example.com';
                                $corporateCustomerBuilder->setLogin($login);
                                break;
                        }

                        if ($registerNewUser === true) {
                            $userData = RetailCrmOrder::isOrderCorporate($order)
                                ? $corporateContact
                                : $order['customer'];

                            $corporateCustomerBuilder->setCorporateContact($userData);

                            $newUser = new CUser();
                            $registeredUserID = $newUser->Add(
                                $corporateCustomerBuilder->getCustomer()->getObjectToArray()
                            );

                            if ($registeredUserID === false) {
                                RCrmActions::eventLog(
                                    'RetailCrmHistory::orderHistory',
                                    'CUser::Register',
                                    'Error register user' . $newUser->LAST_ERROR
                                );

                                continue;
                            }

                            if(RCrmActions::apiMethod(
                                    $api,
                                    'customersFixExternalIds',
                                    __METHOD__,
                                    array(array(
                                        'id' => $order['customer']['id'],
                                        'externalId' => $registeredUserID
                                    ))) == false
                            ) {
                                continue;
                            }
                        }

                        $orderCustomerExtId = isset($registeredUserID) ? $registeredUserID : null;
                        $corporateCustomerBuilder->setOrderCustomerExtId($orderCustomerExtId);
                    }

                    $buyerProfileToAppend = array();

                    if (RetailCrmOrder::isOrderCorporate($order) && !empty($order['company'])) {
                        $buyerProfile = $corporateCustomerBuilder->getBuyerProfile()->getObjectToArray();
                        $buyerProfileToAppend = OrderUserProperties::getList(array(
                            "filter" => $buyerProfile
                        ))->fetch();

                        if (empty($buyerProfileToAppend)) {
                            $buyerProfileInstance = new CSaleOrderUserProps();

                            if ($buyerProfileInstance->Add($buyerProfile)) {
                                $buyerProfileToAppend = OrderUserProperties::getList(array(
                                    "filter" => $buyerProfile
                                ))->fetch();
                            }
                        }
                    }

                    $newOrder = Order::create($site, $orderCustomerExtId, $currency);

                    self::setManager($newOrder, $order);

                    if (isset($buyerProfileToAppend['ID']) && isset($optionsLegalDetails['legalName'])) {
                        $newOrder->setFields([
                            $optionsLegalDetails['legalName'] => $buyerProfileToAppend['NAME'],
                            'PERSON_TYPE_ID'                  => $buyerProfileToAppend['PERSON_TYPE_ID'],
                        ]);
                    }

                    if (!is_object($newOrder) || !$newOrder instanceof Order) {
                        RCrmActions::eventLog(
                            'RetailCrmHistory::orderHistory',
                            'Bitrix\Sale\Order::create',
                            'Error order create'
                        );

                        continue;
                    }

                    $externalId = $newOrder->getId();
                    $order['externalId'] = $externalId;
                }

                if (isset($order['externalId'])) {
                    $itemUpdate = false;

                    if ($order['externalId'] && is_numeric($order['externalId'])) {
                        try {
                            $newOrder = Order::load($order['externalId']);
                        } catch (ArgumentNullException $e) {
                            RCrmActions::eventLog(
                                'RetailCrmHistory::orderHistory',
                                'Bitrix\Sale\Order::load',
                                $e->getMessage() . ': ' . $order['externalId']
                            );

                            continue;
                        }
                    }

                    if (!isset($newOrder)) {
                        RCrmActions::eventLog(
                            'RetailCrmHistory::orderHistory',
                            'Bitrix\Sale\Order::load',
                            'Error order load number=' . $order['number']
                        );

                        continue;
                    }

                    $site = self::getSite($order['site']);

                    if (null === $site) {
                        RCrmActions::eventLog(
                            'RetailCrmHistory::orderHistory',
                            'Bitrix\Sale\Order::edit',
                            sprintf(
                                'Site = %s not found in settings. Order number = %s',
                                $order['site'],
                                $order['number']
                            )
                        );

                        continue;
                    }

                    self::setManager($newOrder, $order);

                    $propsRemove = false;
                    $personType = $newOrder->getField('PERSON_TYPE_ID');

                    if (RetailCrmOrder::isOrderCorporate($order)
                        || (!empty($order['contragentType']) && $order['contragentType'] == 'legal-entity')
                    ) {
                        $personType = $contragentTypes['legal-entity'];
                        $newOrder->setField('PERSON_TYPE_ID', $personType);

                        $propsRemove = true;
                    } else {
                        if (isset($order['orderType']) && $order['orderType']) {
                            $nType = array();
                            $tList = RCrmActions::OrderTypesList(array(array('LID' => $site)));

                            foreach ($tList as $type) {
                                if (isset($optionsOrderTypes[$type['ID']])) {
                                    $nType[$optionsOrderTypes[$type['ID']]] = $type['ID'];
                                }
                            }

                            $newOptionsOrderTypes = $nType;

                            if ($newOptionsOrderTypes[$order['orderType']]) {
                                if ($personType != $newOptionsOrderTypes[$order['orderType']] && $personType != 0) {
                                    $propsRemove = true;
                                }

                                $personType = $newOptionsOrderTypes[$order['orderType']];
                                $newOrder->setField('PERSON_TYPE_ID', $personType);
                            } elseif ($personType == 0) {
                                RCrmActions::eventLog(
                                    'RetailCrmHistory::orderHistory',
                                    'orderType not found',
                                    'PERSON_TYPE_ID = 0'
                                );
                            }
                        }
                    }

                    //status
                    if ($optionsPayStatuses[$order['status']]) {
                        $newOrder->setField('STATUS_ID', $optionsPayStatuses[$order['status']]);

                        if (
                            is_array($optionsCanselOrder)
                            && in_array($optionsPayStatuses[$order['status']], $optionsCanselOrder)
                        ) {
                            self::unreserveShipment($newOrder);
                            $newOrder->setFieldNoDemand('CANCELED', 'Y');
                        } else {
                            $newOrder->setFieldNoDemand('CANCELED', 'N');
                        }
                    }

                    if (array_key_exists('statusComment', $order)) {
                        self::setProp(
                            $newOrder,
                            RCrmActions::fromJSON($order['statusComment']),
                            'REASON_CANCELED'
                        );
                    }

                    //props
                    $propertyCollection = $newOrder->getPropertyCollection();
                    $propertyCollectionArr = $propertyCollection->getArray();
                    $nProps = array();

                    foreach ($propertyCollectionArr['properties'] as $orderProp) {
                        if ($orderProp['ID'][0] == 'n') {
                            $orderProp['ID'] = substr($orderProp['ID'], 1);
                            $property = $propertyCollection->getItemById($orderProp['ID']);

                            if ($property) {
                                $orderProp['ID'] = $property->getField('ORDER_PROPS_ID');
                            } else {
                                continue;
                            }
                        }

                        $nProps[] = $orderProp;
                    }

                    $orderDump = array();
                    $propertyCollectionArr['properties'] = $nProps;

                    if ($propsRemove) {//delete props
                        foreach ($propertyCollectionArr['properties'] as $orderProp) {
                            if ($orderProp['PROPS_GROUP_ID'] == 0) {
                                $somePropValue = $propertyCollection->getItemByOrderPropertyId($orderProp['ID']);
                                self::setProp($somePropValue);
                            }
                        }

                        $orderCrm = RCrmActions::apiMethod($api, 'orderGet', __METHOD__, $order['id']);

                        $orderDump = $order;
                        $order = $orderCrm['order'];
                    }

                    $propsKey = array();

                    foreach ($propertyCollectionArr['properties'] as $prop) {
                        if ($prop['PROPS_GROUP_ID'] != 0) {
                            $propsKey[$prop['CODE']]['ID'] = $prop['ID'];
                            $propsKey[$prop['CODE']]['TYPE'] = $prop['TYPE'];
                        }
                    }

                    // fio
                    if ($order['firstName'] || $order['lastName'] || $order['patronymic']) {
                        $fio = '';

                        foreach ($propertyCollectionArr['properties'] as $prop) {
                            if (in_array($optionsOrderProps[$personType]['fio'], $prop)) {
                                $getFio = $newOrder->getPropertyCollection()->getItemByOrderPropertyId($prop['ID']);

                                if (method_exists($getFio, 'getValue')) {
                                    $fio = $getFio->getValue();
                                }
                            }
                        }

                        $fio = RCrmActions::explodeFio($fio);
                        $newFio = array();

                        if ($fio) {
                            $newFio[] = isset($order['lastName'])
                                ? RCrmActions::fromJSON($order['lastName'])
                                : ($fio['lastName'] ?? '');
                            $newFio[] = isset($order['firstName'])
                                ? RCrmActions::fromJSON($order['firstName'])
                                : ($fio['firstName'] ?? '');
                            $newFio[] = isset($order['patronymic'])
                                ? RCrmActions::fromJSON($order['patronymic'])
                                : ($fio['patronymic'] ?? '');

                        } else {
                            $newFio[] = isset($order['lastName']) ? RCrmActions::fromJSON($order['lastName']) : '';
                            $newFio[] = isset($order['firstName']) ? RCrmActions::fromJSON($order['firstName']) : '';
                            $newFio[] = isset($order['patronymic']) ? RCrmActions::fromJSON($order['patronymic']) : '';
                        }
                        $order['fio'] = trim(implode(' ', $newFio));
                    }

                    if (array_key_exists('fio', $order)) {
                        $order['fio'] = str_replace("clear", "", $order['fio']);
                    }

                    //optionsOrderProps
                    if ($optionsOrderProps[$personType]) {
                        foreach ($optionsOrderProps[$personType] as $key => $orderProp) {
                            if (array_key_exists($key, $order)) {
                                $somePropValue = $propertyCollection
                                    ->getItemByOrderPropertyId($propsKey[$orderProp]['ID']);

                                if ($key == 'fio' && '' !== trim($order['fio'])) {
                                    self::setProp($somePropValue, $order[$key]);
                                } else {
                                    self::setProp($somePropValue, RCrmActions::fromJSON($order[$key]));
                                }
                            } elseif (is_array($order['delivery']['address']) && array_key_exists($key, $order['delivery']['address'])) {
                                if ($propsKey[$orderProp]['TYPE'] == 'LOCATION') {

                                    if (!empty($order['delivery']['address'][$key])) {
                                        $parameters['filter']['NAME.LANGUAGE_ID'] = 'ru';
                                        $parameters['limit'] = 1;
                                        $parameters['select'] = array('*');

                                        // if address have a dot
                                        $loc = explode('.', $order['delivery']['address'][$key]);
                                        if (count($loc) == 1) {
                                            $parameters['filter']['=NAME.NAME'] = RCrmActions::fromJSON(trim($loc[0]));
                                        } else {
                                            $parameters['filter']['=NAME.NAME'] = RCrmActions::fromJSON(trim($loc[1]));
                                        }

                                        $location = Finder::find(
                                            $parameters
                                        )->fetch();
                                    }

                                    if (!empty($location)) {
                                        $somePropValue = $propertyCollection
                                            ->getItemByOrderPropertyId($propsKey[$orderProp]['ID'])
                                        ;
                                        try {
                                            self::setProp($somePropValue, $location['CODE']);
                                        } catch (ArgumentException $argumentException) {
                                            RCrmActions::eventLog(
                                                'RetailCrmHistory::orderHistory',
                                                'RetailCrmHistory::setProp',
                                                'Location parameter is incorrect in order number=' . $order['number']
                                            );
                                        }
                                    } else {
                                        RCrmActions::eventLog(
                                            'RetailCrmHistory::orderHistory',
                                            'RetailCrmHistory::setProp',
                                            sprintf(
                                                'Error location. %s is empty in order number=%s',
                                                $order['delivery']['address'][$key],
                                                $order['number']
                                            )
                                        );
                                    }
                                } else {
                                    $somePropValue = $propertyCollection
                                        ->getItemByOrderPropertyId($propsKey[$orderProp]['ID']);
                                    self::setProp(
                                        $somePropValue,
                                        RCrmActions::fromJSON($order['delivery']['address'][$key])
                                    );
                                }
                            }
                        }
                    }

                    if (isset($order['company']['id'])) {
                        if (!empty($order['company']['name'])) {
                            $order["legalName"] = $order['company']['name'];
                        }
                    }

                    // Corporate clients section
                    if ($optionsLegalDetails[$personType]) {
                        foreach ($optionsLegalDetails[$personType] as $key => $orderProp) {
                            if (array_key_exists($key, $order)) {
                                $somePropValue = $propertyCollection
                                    ->getItemByOrderPropertyId($propsKey[$orderProp]['ID']);

                                self::setProp($somePropValue, RCrmActions::fromJSON($order[$key]));
                            } elseif(is_array($order['contragent']) && array_key_exists($key, $order['contragent'])) {
                                $somePropValue = $propertyCollection
                                    ->getItemByOrderPropertyId($propsKey[$orderProp]['ID']);
                                self::setProp($somePropValue, RCrmActions::fromJSON($order['contragent'][$key]));
                            } elseif (isset($order['company']) && (array_key_exists($key, $order['company'])
                                    || array_key_exists(
                                        lcfirst(str_replace('legal', '', $key)),
                                        $order['company'])
                                )
                            ) {
                                $somePropValue = $propertyCollection
                                    ->getItemByOrderPropertyId($propsKey[$orderProp]['ID']);

                                // fallback for order[company][name]
                                if ($key == 'legalName') {
                                    $key = 'name';
                                }

                                self::setProp(
                                    $somePropValue,
                                    RCrmActions::fromJSON(
                                        $key == 'legalAddress'
                                            ? ($order['company']['address']['text'] ?? '')
                                            : $order['company'][$key]
                                    )
                                );
                            } elseif (isset($order['company']['contragent'])
                                && array_key_exists($key, $order['company']['contragent'])
                            ) {
                                $somePropValue = $propertyCollection
                                    ->getItemByOrderPropertyId($propsKey[$orderProp]['ID']);
                                self::setProp($somePropValue,  RCrmActions::fromJSON($order['company']['contragent'][$key]));
                            }
                        }
                    }

                    if ($propsRemove) {
                        $order = $orderDump;
                    }

                    //comments
                    if (array_key_exists('customerComment', $order)) {
                        self::setProp($newOrder, RCrmActions::fromJSON($order['customerComment']), 'USER_DESCRIPTION');
                    }
                    if (array_key_exists('managerComment', $order)) {
                        self::setProp($newOrder, RCrmActions::fromJSON($order['managerComment']), 'COMMENTS');
                    }

                    //items
                    $basket = $newOrder->getBasket();

                    if (!$basket) {
                        $basket = Basket::create($site);
                        $newOrder->setBasket($basket);
                    }

                    $fUserId = $basket->getFUserId(true);

                    if (!$fUserId) {
                        $fUserId = Fuser::getIdByUserId($order['customer']['externalId']);
                        $basket->setFUserId($fUserId);
                    }

                    if (isset($order['contact']['id'])) {
                        $ExtId = null;

                        if (isset($order['contact']['externalId'])) {
                            $ExtId = $order['contact']['externalId'];
                        }

                        if (isset($ExtId)) {
                            $newOrder->setFieldNoDemand('USER_ID', $ExtId);
                        } else {
                            $response = RCrmActions::apiMethod(
                                $api,
                                'customersGetById',
                                __METHOD__,
                                $order['contact']['id'],
                                $order['site']
                            );

                            $newUser = new CUser();
                            $customerBuilder = new CustomerBuilder();
                            $customerBuilder->setDataCrm($response['customer'])->build();

                            if (!empty($response['customer']['email'])) {
                                $registerNewUser = true;
                                $dbUser = CUser::GetList(
                                    ($by = 'ID'),
                                    ($sort = 'ASC'),
                                    array('=EMAIL' => $response['customer']['email'])
                                );

                                switch ($dbUser->SelectedRowsCount()) {
                                    case 0:
                                        $login = $response['customer']['email'];
                                        $customerBuilder->setLogin($login);
                                        $customerBuilder->buildPassword();
                                        break;
                                    case 1:
                                        $arUser = $dbUser->Fetch();
                                        $registeredUserID = $arUser['ID'];
                                        $registerNewUser = false;
                                        break;
                                    default:
                                        $login = uniqid('user_' . time()) . '@example.com';
                                        $customerBuilder->setLogin($login);
                                        $customerBuilder->buildPassword();
                                        break;
                                }

                                if ($registerNewUser === true) {
                                    $registeredUserID = $newUser->Add(
                                        $customerBuilder->getCustomer()->getObjectToArray()
                                    );
                                    if ($registeredUserID === false) {
                                        RCrmActions::eventLog(
                                            'RetailCrmHistory::orderHistory',
                                            'CUser::Register',
                                            'Error register user: ' . $newUser->LAST_ERROR
                                        );

                                        continue;
                                    }

                                    if(RCrmActions::apiMethod(
                                            $api,
                                            'customersFixExternalIds',
                                            __METHOD__,
                                            array(array(
                                                    'id' => $response['customer']['id'],
                                                    'externalId' => $registeredUserID
                                            ))
                                        ) == false
                                    ) {
                                        continue;
                                    }
                                }
                                $newOrder->setFieldNoDemand('USER_ID', $registeredUserID);
                            }
                        }
                    }

                    $editBasketInfo = [];
                    $deleteBasketInfo = [];
                    $bonusesChargeTotal = null;
                    $loyaltyDiscount = null;

                    if (isset($order['items'])) {
                        $itemUpdate = true;
                        $response = RCrmActions::apiMethod($api, 'orderGet', __METHOD__, $order['id']);

                        if (isset($response['order'])) {
                            $orderTemp = $response['order'];
                            $duplicateItems = [];

                            foreach ($orderTemp['items'] as $item) {
                                $duplicateItems[$item['id']]['externalId'] += $item['offer']['externalId'];
                                $duplicateItems[$item['id']]['quantity'] += $item['quantity'];
                                $duplicateItems[$item['id']]['discountTotal'] +=
                                    $item['quantity'] * $item['discountTotal'];
                                $duplicateItems[$item['id']]['initialPrice'] = (float) $item['initialPrice'];
                                $duplicateItems[$item['id']]['price_sum'] = ($item['quantity'] * $item['initialPrice'])
                                    - ($item['quantity'] * $item['discountTotal']);
                            }

                            unset($orderTemp);
                        } else {
                            continue;
                        }

                        $collectItems = [];

                        foreach ($duplicateItems as $it) {
                            $collectItems[$it['externalId']]['quantity'] += $it['quantity'];
                            $collectItems[$it['externalId']]['price_sum'] += $it['price_sum'];
                            $collectItems[$it['externalId']]['discountTotal_sum'] += $it['discountTotal'];

                            if (isset($collectItems[$it['externalId']]['initialPrice_max'])) {
                                if ($collectItems[$it['externalId']]['initialPrice_max'] < $it['initialPrice']) {
                                    $collectItems[$it['externalId']]['initialPrice_max'] = $it['initialPrice'];
                                }
                            } else {
                                $collectItems[$it['externalId']]['initialPrice_max'] = $it['initialPrice'];
                            }

                            $collectItems[$it['externalId']]['initialPricesList'][] = $it['initialPrice'];
                        }

                        foreach ($collectItems as $key => $itemData) {
                            if (count($itemData['initialPricesList']) > 1) {
                                $discountDelta = 0;

                                foreach ($itemData['initialPrices'] as $initialPriceItem) {
                                    $delta = $itemData['initialPrice_max'] - (float) $initialPriceItem;

                                    if ($delta !== 0) {
                                        $discountDelta += $delta;
                                    }
                                }

                                $collectItems[$key]['discountTotal_sum'] += $discountDelta;
                            }
                        }

                        Logger::getInstance()->write($duplicateItems, 'duplicateItemsOrderHistory');
                        Logger::getInstance()->write($collectItems, 'collectItemsOrderHistory');

                        $optionDiscRound = COption::GetOptionString(self::$MODULE_ID, self::$CRM_DISCOUNT_ROUND, 0);

                        foreach ($order['items'] as $product) {
                            if ($collectItems[$product['offer']['externalId']]['quantity']) {
                                $product['quantity'] = $collectItems[$product['offer']['externalId']]['quantity'];
                            }

                            $item = self::getExistsItem($basket, 'catalog', $product['offer']['externalId']);

                            if (!$item) {
                                if ($product['delete']) {
                                    continue;
                                }

                                $item = $basket->createItem('catalog', $product['offer']['externalId']);

                                if ($item instanceof \Bitrix\Sale\BasketItem) {
                                    $elem = self::getInfoElement($product['offer']['externalId']);

                                    $item->setFields(array(
                                        'CURRENCY' => $newOrder->getCurrency(),
                                        'LID' => $site,
                                        'BASE_PRICE' => $collectItems[$product['offer']['externalId']]['initialPrice_max'],
                                        'NAME' => $product['offer']['name'] ? RCrmActions::fromJSON($product['offer']['name']) : $elem['NAME'],
                                        'DETAIL_PAGE_URL' => $elem['URL'],
                                        'PRODUCT_PROVIDER_CLASS' => 'CCatalogProductProvider',
                                        'DIMENSIONS' => $elem['DIMENSIONS'],
                                        'WEIGHT' => $elem['WEIGHT'],
                                        'NOTES' => GetMessage('PRICE_TYPE'),
                                        'PRODUCT_XML_ID' => $elem["XML_ID"],
                                        'CATALOG_XML_ID' => $elem["IBLOCK_XML_ID"]
                                    ));
                                } else {
                                    RCrmActions::eventLog(
                                        'RetailCrmHistory::orderHistory',
                                        'createItem',
                                        'Error item add'
                                    );

                                    continue;
                                }
                            }

                            if ($product['delete'] && $collectItems[$product['offer']['externalId']]['quantity'] <= 0) {
                                if (is_int($item->getBasketCode())) {
                                    $deleteBasketInfo[] = $item->getBasketCode();
                                }

                                $item->delete();

                                continue;
                            }

                            if ($product['quantity']) {
                                $item->setFieldNoDemand('QUANTITY', $product['quantity']);
                            }

                            if (array_key_exists('initialPrice_max', $collectItems[$product['offer']['externalId']])) {
                                $item->setField(
                                    'BASE_PRICE',
                                    $collectItems[$product['offer']['externalId']]['initialPrice_max']
                                );
                            }

                            if (array_key_exists('discountTotal_sum', $collectItems[$product['offer']['externalId']])) {
                                $item->setField('CUSTOM_PRICE', 'Y');
                                $item->setField('DISCOUNT_NAME', '');
                                $item->setField('DISCOUNT_VALUE', '');

                                // Полную цену позиции с учётом скидок делим на количество - получаем цену каждой единицы
                                // товара с учётом скидок.
                                $price = $collectItems[
                                    $product['offer']['externalId']
                                    ]['price_sum'] / $collectItems[$product['offer']['externalId']]['quantity'];

                                if ('Y' == $optionDiscRound) {
                                    $price = self::truncate($price, 2);
                                }

                                $item->setField('PRICE', $price);
                            }

                            $manualProductDiscount = 0;

                            $bonusesChargeProduct = $product['bonusesCharge'] ?? null;

                            foreach ($product['discounts'] as $productDiscount) {
                                if ('manual_product' === $productDiscount['type']) {
                                    $manualProductDiscount = $productDiscount['amount'];
                                }

                                if ('loyalty_level' === $productDiscount['type']) {
                                    $loyaltyDiscount += $productDiscount['amount'];
                                }
                            }

                            $editBasketInfo[] = [
                                'productId' => $item->getProductId(),
                                'productDiscount' => $manualProductDiscount / $item->getQuantity(),
                                'bonusesCharge' => $bonusesChargeProduct,
                            ];

                            if (null !== $bonusesChargeProduct) {
                                $bonusesChargeTotal += $bonusesChargeProduct;
                            }
                        }
                    }

                    $orderSumm = 0;

                    foreach ($basket as $item) {
                        $orderSumm += $item->getFinalPrice();
                    }

                    if (is_array($order['delivery']) && array_key_exists('cost', $order['delivery'])) {
                        $deliverySumm = $order['delivery']['cost'];
                    } else {
                        $deliverySumm = $newOrder->getDeliveryPrice();
                    }

                    $orderSumm += $deliverySumm;

                    $order['summ'] = $orderSumm;

                    //payment
                    $newHistoryPayments = array();

                    if (array_key_exists('payments', $order)) {
                        if (!isset($orderCrm)) {
                            $orderCrm = RCrmActions::apiMethod($api, 'orderGet', __METHOD__, $order['id']);
                        }
                        if ($orderCrm) {
                            self::paymentsUpdate($newOrder, $orderCrm['order'], $newHistoryPayments);
                        }
                    }

                    //delivery
                    if (array_key_exists('delivery', $order)) {
                        $itemUpdate = true;

                        //delete empty
                        if (!isset($orderCrm)) {
                            $orderCrm = RCrmActions::apiMethod($api, 'orderGet', __METHOD__, $order['id']);
                        }

                        if ($orderCrm) {
                            self::deliveryUpdate($newOrder, $optionsDelivTypes, $orderCrm['order']);
                        }
                    }

                    if ($itemUpdate === true && $newOrder->getField('CANCELED') !== 'Y') {
                        self::shipmentItemReset($newOrder);
                    }

                    if (isset($orderCrm)) {
                        unset($orderCrm);
                    }

                    if (isset($order['fullPaidAt']) && is_string($order['fullPaidAt'])) {
                        $newOrder->setFieldNoDemand('PAYED', 'Y');
                    }

                    if ($shipmentDeducted === 'Y') {
                        $collection = $newOrder->getShipmentCollection()->getNotSystemItems();

                        if (isset($order['shipped'])) {
                            if ($order['shipped']) {
                                if ($collection->count() === 0) {
                                    $collection = $newOrder->getShipmentCollection();
                                    $shipment = $collection->createItem();
                                    $shipment->setField('DEDUCTED', 'Y');
                                } else {
                                    foreach ($collection as $shipment) {
                                        $shipment->setField('DEDUCTED', 'Y');
                                    }
                                }
                            } else {
                                foreach ($collection as $shipment) {
                                    $shipment->setField('DEDUCTED', 'N');
                                }
                            }
                        }
                    }

                    $newOrder->setField('PRICE', $orderSumm);
                    self::orderSave($newOrder);

                    if ($optionsOrderNumbers === 'Y' && isset($order['number'])) {
                        $newOrder->setField('ACCOUNT_NUMBER', $order['number']);
                        self::orderSave($newOrder);
                    }

                    //items loyalty info to HL
                    if (!empty($editBasketInfo)) {
                        $newBasket = $newOrder->getBasket();
                        $calculateItemsInput = [];
                        $basketItemIds = [];

                        foreach ($editBasketInfo as $itemInfo) {
                            $basketItem = self::getExistsItem($newBasket, 'catalog', $itemInfo['productId']);
                            if (false !== $basketItem) {
                                $calculateItemsInput[$basketItem->getId()]['SHOP_ITEM_DISCOUNT'] = $itemInfo['productDiscount'];
                                $calculateItemsInput[$basketItem->getId()]['BONUSES_CHARGE'] = $itemInfo['bonusesCharge'];
                                $basketItemIds[] = $basketItem->getId();
                            }
                        }

                        $orderLoyaltyDataService->saveBonusAndDiscToOrderProps(
                            $newOrder->getPropertyCollection(),
                            $loyaltyDiscount,
                            $bonusesChargeTotal
                        );

                        $hlInfoBuilder = new LoyaltyDataBuilder();
                        $hlInfoBuilder->setOrder($newOrder);
                        $hlInfoBuilder->setCalculateItemsInput($calculateItemsInput);
                        $hlInfoBuilder->setBonusCountTotal($bonusesChargeTotal);
                        $orderLoyaltyDataService->saveLoyaltyInfoToHl($hlInfoBuilder->build($basketItemIds)->getResult());
                        self::orderSave($newOrder);
                    }

                    if (!empty($deleteBasketInfo)) {
                        $orderLoyaltyDataService->deleteLoyaltyInfoFromHl($deleteBasketInfo);
                    }

                    if (!empty($newHistoryPayments)) {
                        foreach ($newOrder->getPaymentCollection() as $orderPayment) {
                            if (is_array($newHistoryPayments)
                                && array_key_exists($orderPayment->getField('XML_ID'), $newHistoryPayments)
                            ) {
                                $paymentId = $orderPayment->getId();
                                $paymentExternalId = RCrmActions::generatePaymentExternalId($paymentId);
                                if (is_null($paymentId)) {
                                    RCrmActions::eventLog(
                                        'RetailCrmHistory::orderHistory',
                                        'paymentsUpdate',
                                        'Save payment error, order=' . $order['number']
                                    );

                                    continue;
                                }

                                $newHistoryPayments[$orderPayment->getField('XML_ID')]['externalId'] = $paymentExternalId;
                                RCrmActions::apiMethod(
                                    $api,
                                    'paymentEditById',
                                    __METHOD__,
                                    $newHistoryPayments[$orderPayment->getField('XML_ID')]
                                );

                                if ($paymentId) {
                                    PaymentTable::update($paymentId, array('XML_ID' => ''));
                                }
                            }
                        }
                    }

                    if (!$order['externalId']) {
                        $order["externalId"] = $newOrder->getId();

                        if (RCrmActions::apiMethod(
                                $api,
                                'ordersFixExternalIds',
                                __METHOD__,
                                array(array('id' => $order['id'], 'externalId' => $newOrder->getId()))) == false
                        ) {
                            continue;
                        }
                    }

                    if (function_exists('retailCrmAfterOrderSave')) {
                        retailCrmAfterOrderSave($order);
                    }
                }

                unset($newOrder);
            }

            $GLOBALS['RETAIL_CRM_HISTORY'] = false;

            //end id
            $end = array_pop($orderH);
            COption::SetOptionString(self::$MODULE_ID, self::$CRM_ORDER_HISTORY, $end['id']);

            if ($orderHistory['pagination']['totalPageCount'] == 1) {
                return true;
            }
            //new filter
            $historyFilter['sinceId'] = $end['id'];
        }

        return false;
    }

    /**
     * @param string $shopCode
     *
     * @return string|null
     */
    public static function getSite(string $shopCode): ?string
    {
        $optionsSitesList = RetailcrmConfigProvider::getSitesList();

        if ($optionsSitesList) {
            $searchResult = array_search($shopCode, $optionsSitesList, true);

            return is_string($searchResult) ? $searchResult : null;
        }

        $defaultSite = CSite::GetDefSite();

        return is_string($defaultSite) ? $defaultSite : null;
    }

    /**
     * @param $array
     * @param $value
     *
     * @return array
     */
    public static function search_array_by_value($array, $value)
    {
        $results = array();
        if (is_array($array)) {
            $found = array_search($value,$array);
            if ($found) {
                $results[] = $found;
            }
            foreach ($array as $subarray)
                $results = array_merge($results, static::search_array_by_value($subarray, $value));
        }
        return $results;
    }

    public static function assemblyCustomer($customerHistory)
    {
        $customerHistory = self::filterHistory($customerHistory, 'customer');
        $server = Context::getCurrent()->getServer()->getDocumentRoot();
        $fields = array();
        if (file_exists($server . '/bitrix/modules/intaro.retailcrm/classes/general/config/objects.xml')) {
            $objects = simplexml_load_file($server . '/bitrix/modules/intaro.retailcrm/classes/general/config/objects.xml');
            foreach ($objects->fields->field as $object) {
                $fields[(string)$object["group"]][(string)$object["id"]] = (string)$object;
            }
        }
        $customers = array();
        foreach ($customerHistory as $change) {
            $change['customer'] = self::removeEmpty($change['customer']);
            if ($customers[$change['customer']['id']]) {
                $customers[$change['customer']['id']] = array_merge($customers[$change['customer']['id']], $change['customer']);
            } else {
                $customers[$change['customer']['id']] = $change['customer'];
            }

            if ($change['customer']['contragent']['contragentType']) {
                $change['customer']['contragentType'] = self::newValue($change['customer']['contragent']['contragentType']);
                unset($change['customer']['contragent']);
            }

            if ($change['field'] == 'segments') {
                if ($change['newValue']['code'] == "genshchini") {
                    $customers[$change['customer']['id']]["sex"] = "F";
                }
            }

            if ($change['field'] == 'segments') {
                if ($change['newValue']['code'] == "mugchini") {
                    $customers[$change['customer']['id']]["sex"] = "M";
                }
            }

            if ($fields['customer'][$change['field']] == 'phones') {
                if (is_array($customers[$change['customer']['id']]['phones'])) {
                    $key = count($customers[$change['customer']['id']]['phones']);
                }

                if (isset($change['oldValue'])) {
                    $customers[$change['customer']['id']]['phones'][$key]['old_number'] = $change['oldValue'];
                }
                if (isset($change['newValue'])) {
                    $customers[$change['customer']['id']]['phones'][$key]['number'] = $change['newValue'];
                }
            } else {
                if ($fields['customerAddress'][$change['field']]) {
                    $customers[$change['customer']['id']]['address'][$fields['customerAddress'][$change['field']]] = $change['newValue'];
                } elseif ($fields['customerContragent'][$change['field']]) {
                    $customers[$change['customer']['id']]['contragent'][$fields['customerContragent'][$change['field']]] = $change['newValue'];
                } elseif ($fields['customer'][$change['field']]) {
                    $customers[$change['customer']['id']][$fields['customer'][$change['field']]] = self::newValue($change['newValue']);
                } elseif (strripos($change['field'], 'custom_') !== false) {
                    $customers[$change['customer']['id']]['customFields'][str_replace('custom_', '', $change['field'])] = self::newValue($change['newValue']);
                }

                if (isset($change['created'])) {
                    $customers[$change['customer']['id']]['create'] = 1;
                }

                if (isset($change['deleted'])) {
                    $customers[$change['customer']['id']]['deleted'] = 1;
                }
            }
        }

        return $customers;
    }

    public static function assemblyOrder($orderHistory)
    {
        $orderHistory = self::filterHistory($orderHistory, 'order');
        $server = Context::getCurrent()->getServer()->getDocumentRoot();

        if (file_exists($server . '/bitrix/modules/intaro.retailcrm/classes/general/config/objects.xml')) {
            $objects = simplexml_load_file(
                $server . '/bitrix/modules/intaro.retailcrm/classes/general/config/objects.xml'
            );

            foreach ($objects->fields->field as $object) {
                $fields[(string)$object["group"]][(string)$object["id"]] = (string)$object;
            }
        }

        $orders = array();

        foreach ($orderHistory as $change) {
            $change['order'] = self::removeEmpty($change['order']);

            if ($change['order']['items']) {
                $items = [];

                foreach ($change['order']['items'] as $item) {
                    if (isset($change['created'])) {
                        $item['create'] = 1;
                    }
                    $items[$item['id']] = $item;
                }
                $change['order']['items'] = $items;
            }

            if ($change['field'] == 'number') {
                $orders[$change['order']['id']]['number'] = $change['newValue'];
            }

            if ($change['field'] == 'manager') {
                $orders[$change['order']['id']]['managerId'] = $change['newValue'];
            }

            if (isset($change['oldValue']) && $change['field'] == 'customer') {
                $orders[$change['order']['id']]['customer'] = $change['newValue'];
            }

            if (isset($change['oldValue']) && $change['field'] == 'contact') {
                $orders[$change['order']['id']]['contact'] = $change['newValue'];
            }

            if (isset($change['oldValue']) && $change['field'] == 'company') {
                $orders[$change['order']['id']]['company'] = $change['newValue'];
            }

            if ($change['order']['payments']) {
                $payments = array();
                foreach ($change['order']['payments'] as $payment) {
                    $payments[$payment['id']] = $payment;
                }
                $change['order']['payments'] = $payments;
            }

            if (is_array($change['order']['contragent']) && isset($change['order']['contragent']) && count($change['order']['contragent']) > 0) {
                foreach ($change['order']['contragent'] as $name => $value) {
                    $change['order'][$name] = self::newValue($value);
                }
                unset($change['order']['contragent']);
            }

            if ($orders[$change['order']['id']]) {
                $orders[$change['order']['id']] = array_merge($orders[$change['order']['id']], $change['order']);
            } else {
                $orders[$change['order']['id']] = $change['order'];
            }

            if ($change['item']) {
                if ($orders[$change['order']['id']]['items'][$change['item']['id']]) {
                    $orders[$change['order']['id']]['items'][$change['item']['id']] = array_merge($orders[$change['order']['id']]['items'][$change['item']['id']], $change['item']);
                } else {
                    $orders[$change['order']['id']]['items'][$change['item']['id']] = $change['item'];
                }

                if (empty($change['oldValue']) && $change['field'] == 'order_product') {
                    $orders[$change['order']['id']]['items'][$change['item']['id']]['create'] = 1;
                    unset($orders[$change['order']['id']]['items'][$change['item']['id']]['delete']);
                }
                if (empty($change['newValue']) && $change['field'] == 'order_product') {
                    $orders[$change['order']['id']]['items'][$change['item']['id']]['delete'] = 1;
                }
                if (/*!$orders[$change['order']['id']]['items'][$change['item']['id']]['create'] && */$fields['item'][$change['field']]) {
                    $orders[$change['order']['id']]['items'][$change['item']['id']][$fields['item'][$change['field']]] = $change['newValue'];
                }
            } elseif ($change['payment']) {
                if ($orders[$change['order']['id']]['payments'][$change['payment']['id']]) {
                    $orders[$change['order']['id']]['payments'][$change['payment']['id']] = array_merge($orders[$change['order']['id']]['payments'][$change['payment']['id']], $change['payment']);
                } else {
                    $orders[$change['order']['id']]['payments'][$change['payment']['id']] = $change['payment'];
                }

                if (empty($change['oldValue']) && $change['field'] == 'payments') {
                    $orders[$change['order']['id']]['payments'][$change['payment']['id']]['create'] = 1;
                    unset($orders[$change['order']['id']]['payments'][$change['payment']['id']]['delete']);
                }
                if (empty($change['newValue']) && $change['field'] == 'payments') {
                    $orders[$change['order']['id']]['payments'][$change['payment']['id']]['delete'] = 1;
                }
                if (!$orders[$change['order']['id']]['payments'][$change['payment']['id']]['create'] && $fields['payment'][$change['field']]) {
                    $orders[$change['order']['id']]['payments'][$change['payment']['id']][$fields['payment'][$change['field']]] = $change['newValue'];
                }
            } else {
                if ($fields['delivery'][$change['field']] == 'service') {
                    $orders[$change['order']['id']]['delivery']['service']['code'] = self::newValue($change['newValue']);
                } elseif ($fields['delivery'][$change['field']]) {
                    $orders[$change['order']['id']]['delivery'][$fields['delivery'][$change['field']]] = self::newValue($change['newValue']);
                } elseif ($fields['orderAddress'][$change['field']]) {
                    $orders[$change['order']['id']]['delivery']['address'][$fields['orderAddress'][$change['field']]] = $change['newValue'];
                } elseif ($fields['integrationDelivery'][$change['field']]) {
                    $orders[$change['order']['id']]['delivery']['service'][$fields['integrationDelivery'][$change['field']]] = self::newValue($change['newValue']);
                } elseif ($fields['customerContragent'][$change['field']]) {
                    $orders[$change['order']['id']][$fields['customerContragent'][$change['field']]] = self::newValue($change['newValue']);
                } elseif (strripos($change['field'], 'custom_') !== false) {
                    $orders[$change['order']['id']]['customFields'][str_replace('custom_', '', $change['field'])] = self::newValue($change['newValue']);
                } elseif ($fields['order'][$change['field']]){
                    $orders[$change['order']['id']][$fields['order'][$change['field']]] = self::newValue($change['newValue']);
                }

                if (isset($change['created'])) {
                    $orders[$change['order']['id']]['create'] = 1;
                }

                if (isset($change['deleted'])) {
                    $orders[$change['order']['id']]['deleted'] = 1;
                }
            }

            if ($change['field'] == 'last_name') {
                if (true == is_null($change['newValue'])) {
                    $orders[$change['order']['id']]['lastName'] = 'clear';
                }
            }

            if ($change['field'] == 'first_Name') {
                if (true == is_null($change['newValue'])) {
                    $orders[$change['order']['id']]['firstName'] = 'clear';
                }
            }

            if ($change['field'] == 'patronymic') {
                if (true == is_null($change['newValue'])) {
                    $orders[$change['order']['id']]['patronymic'] = 'clear';
                }
            }
        }

        return $orders;
    }

    /**
     * Filters out history by these terms:
     *  - Changes from current API key will be added only if CMS changes are more actual than history.
     *  - All other changes will be merged as usual.
     * It fixes these problems:
     *  - Changes from current API key are merged when it's not needed.
     *  - Changes from CRM can overwrite more actual changes from CMS due to ignoring current API key changes.
     *
     * @param array  $historyEntries Raw history from CRM
     * @param string $recordType     Entity field name, e.g. `customer` or `order`.
     *
     * @return array
     */
    private static function filterHistory($historyEntries, $recordType)
    {
        $history          = [];
        $organizedHistory = [];
        $notOurChanges    = [];

        foreach ($historyEntries as $entry) {
            if (!isset($entry[$recordType]['externalId'])) {
                if ($entry['source'] == 'api'
                    && isset($change['apiKey']['current'])
                    && $entry['apiKey']['current'] == true
                    && $entry['field'] != 'externalId'
                ) {
                    continue;
                }

                $history[] = $entry;

                continue;
            }

            $externalId = $entry[$recordType]['externalId'];
            $field      = $entry['field'];

            if (!isset($organizedHistory[$externalId])) {
                $organizedHistory[$externalId] = [];
            }

            if (!isset($notOurChanges[$externalId])) {
                $notOurChanges[$externalId] = [];
            }

            if ($entry['source'] == 'api'
                && isset($entry['apiKey']['current'])
                && $entry['apiKey']['current'] == true
            ) {
                if (isset($notOurChanges[$externalId][$field]) || $entry['field'] == 'externalId') {
                    $organizedHistory[$externalId][] = $entry;
                } else {
                    continue;
                }
            } else {
                $organizedHistory[$externalId][]    = $entry;
                $notOurChanges[$externalId][$field] = true;
            }
        }

        unset($notOurChanges);

        foreach ($organizedHistory as $historyChunk) {
            $history = array_merge($history, $historyChunk);
        }

        return $history;
    }

    /**
     * Update shipment in order
     *
     * @param \Bitrix\Sale\Order $bitrixOrder
     * @param array              $optionsDelivTypes
     * @param array              $crmOrder
     *
     * @return bool|null
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ObjectNotFoundException
     * @throws \Bitrix\Main\SystemException
     */
    public static function deliveryUpdate(Bitrix\Sale\Order $bitrixOrder, $optionsDelivTypes, $crmOrder)
    {
        if (!$bitrixOrder instanceof Bitrix\Sale\Order) {
            return false;
        }

        if ($bitrixOrder->getId()) {
            $update = true;
        } else {
            $update = false;
        }

        $crmCode = $crmOrder['delivery']['code'] ?? false;
        $noDeliveryId = EmptyDeliveryService::getEmptyDeliveryServiceId();

        if ($crmCode === false || !isset($optionsDelivTypes[$crmCode])) {
            $deliveryId = $noDeliveryId;
        } else {
            $deliveryId = $optionsDelivTypes[$crmCode];

            if (isset($crmOrder['delivery']['service']['code'])) {
                $deliveryCode = Manager::getCodeById($deliveryId);
                $serviceCode = $crmOrder['delivery']['service']['code'];

                $service = Manager::getService($deliveryId);
                if (is_object($service)) {
                    $services = $service->getProfilesList();
                    if (!array_key_exists($serviceCode, $services)) {
                        $serviceCode = strtoupper($serviceCode);
                        $serviceCode = str_replace(array('-'), "_", $serviceCode);
                    }
                }

                if ($deliveryCode) {
                    try {
                        $deliveryService = Manager::getObjectByCode($deliveryCode . ':' . $serviceCode);
                    } catch (SystemException $systemException) {
                        RCrmActions::eventLog('RetailCrmHistory::deliveryEdit', '\Bitrix\Sale\Delivery\Services\Manager::getObjectByCode', $systemException->getMessage());
                    }

                    if (isset($deliveryService)) {
                        $deliveryId = $deliveryService->getId();
                    }
                }
            }
        }

        $delivery = Manager::getObjectById($deliveryId);
        $shipmentColl = $bitrixOrder->getShipmentCollection();

        if ($delivery) {
            //В коллекции всегда есть одна скрытая системная доставка, к которой относятся нераспределенные товары
            //Поэтому, если есть только системная доставка, то нужно создать новую
            if (!$update || $shipmentColl->count() === 1) {
                $shipment = $shipmentColl->createItem($delivery);
                $shipment->setFields([
                    'BASE_PRICE_DELIVERY'   => $crmOrder['delivery']['cost'],
                    'PRICE_DELIVERY'        => $crmOrder['delivery']['cost'],
                    'CURRENCY'              => $bitrixOrder->getCurrency(),
                    'DELIVERY_NAME'         => $delivery->getName(),
                    'CUSTOM_PRICE_DELIVERY' => 'Y'
                ]);
            } else {
                foreach ($shipmentColl as $shipment) {
                    if (!$shipment->isSystem()) {
                        $shipment->setFields([
                            'BASE_PRICE_DELIVERY'   => $crmOrder['delivery']['cost'],
                            'PRICE_DELIVERY'        => $crmOrder['delivery']['cost'],
                            'CURRENCY'              => $bitrixOrder->getCurrency(),
                            'DELIVERY_ID'           => $deliveryId,
                            'DELIVERY_NAME'         => $delivery->getName(),
                            'CUSTOM_PRICE_DELIVERY' => 'Y'
                        ]);
                    }
                }
            }
        }

        return true;
    }

    /**
     * Update shipment item colletion
     *
     * @param \Bitrix\Sale\Order $order
     *
     * @return void | boolean
     */
    public static function shipmentItemReset($order)
    {
        $shipmentCollection = $order->getShipmentCollection();
        $basket = $order->getBasket();

        foreach ($shipmentCollection as $shipment) {
            if (!$shipment->isSystem() && !$shipment->isShipped()) {
                $reserved = false;

                if ($shipment->needReservation()) {
                    $reserved = true;
                }

                $shipmentItemColl = $shipment->getShipmentItemCollection();

                if ($reserved === true) {
                    $shipment->tryUnreserve();
                }

                try {
                    $shipmentItemColl->resetCollection($basket);

                    if ($reserved === true) {
                        $shipment->tryReserve();
                    }
                } catch (\Bitrix\Main\NotSupportedException $NotSupportedException) {
                    RCrmActions::eventLog('RetailCrmHistory::shipmentItemReset', '\Bitrix\Sale\ShipmentItemCollection::resetCollection()', $NotSupportedException->getMessage());

                    return false;
                }
            }
        }
    }

    /**
     * Unreserve items if order canceled
     *
     * @param \Bitrix\Sale\Order $order
     *
     * @return void | boolean
     */
    public static function unreserveShipment($order)
    {
        $shipmentCollection = $order->getShipmentCollection();

        foreach ($shipmentCollection as $shipment) {
            if (!$shipment->isSystem()) {
                try {
                    $shipment->tryUnreserve();
                } catch (ArgumentOutOfRangeException $ArgumentOutOfRangeException) {
                    RCrmActions::eventLog('RetailCrmHistory::unreserveShipment', '\Bitrix\Sale\Shipment::tryUnreserve()', $ArgumentOutOfRangeException->getMessage());

                    return false;
                } catch (NotSupportedException $NotSupportedException) {
                    RCrmActions::eventLog('RetailCrmHistory::unreserveShipment', '\Bitrix\Sale\Shipment::tryUnreserve()', $NotSupportedException->getMessage());

                    return false;
                }
            }
        }
    }

    /**
     * Update payment in order
     *
     * @param object $order
     * @param array $paymentsCrm
     * @param array $newHistoryPayments
     *
     * @return void
     */
    public static function paymentsUpdate($order, $paymentsCrm, &$newHistoryPayments = array())
    {
        $optionsPayTypes = array_flip(unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_PAYMENT_TYPES, 0)));
        $optionsPayment = array_flip(unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_PAYMENT, 0)));
        $allPaymentSystems = RCrmActions::PaymentList();
        foreach ($allPaymentSystems as $allPaymentSystem) {
            $arPaySysmems[$allPaymentSystem['ID']] = $allPaymentSystem['NAME'];
        }
        $paymentsList = array();
        $paymentColl = $order->getPaymentCollection();
        foreach ($paymentColl as $paymentData) {
            $data = $paymentData->getFields()->getValues();
            $paymentsList[$data['ID']] = $paymentData;
        }

        //data from crm
        $paySumm = 0;
        foreach ($paymentsCrm['payments'] as $paymentCrm) {
            if (isset($paymentCrm['externalId']) && !empty($paymentCrm['externalId'])) {
                //find the payment
                $nowPaymentId = RCrmActions::getFromPaymentExternalId($paymentCrm['externalId']);
                $nowPayment = $paymentsList[$nowPaymentId];
                //update data
                if ($nowPayment instanceof Payment) {
                    $nowPayment->setField('SUM', $paymentCrm['amount']);
                    if ($optionsPayTypes[$paymentCrm['type']] != $nowPayment->getField('PAY_SYSTEM_ID')) {
                        $nowPayment->setField('PAY_SYSTEM_ID', $optionsPayTypes[$paymentCrm['type']]);
                        $nowPayment->setField('PAY_SYSTEM_NAME', $arPaySysmems[$optionsPayTypes[$paymentCrm['type']]]);
                    }
                    if (isset($optionsPayment[$paymentCrm['status']])) {
                        $nowPayment->setField('PAID', $optionsPayment[$paymentCrm['status']]);
                    }

                    unset($paymentsList[$nowPaymentId]);
                }
            } elseif (array_key_exists($paymentCrm['type'], $optionsPayTypes)) {
                $newHistoryPayments[$paymentCrm['id']] = $paymentCrm;
                $newPayment = $paymentColl->createItem();
                $newPayment->setField('SUM', $paymentCrm['amount']);
                $newPayment->setField('PAY_SYSTEM_ID', $optionsPayTypes[$paymentCrm['type']]);
                $newPayment->setField('PAY_SYSTEM_NAME', $arPaySysmems[$optionsPayTypes[$paymentCrm['type']]]);
                $newPayment->setField('PAID', $optionsPayment[$paymentCrm['status']] ? $optionsPayment[$paymentCrm['status']] : 'N');
                $newPayment->setField('CURRENCY', $order->getCurrency());
                $newPayment->setField('IS_RETURN', 'N');
                $newPayment->setField('PRICE_COD', '0.00');
                $newPayment->setField('EXTERNAL_PAYMENT', 'N');
                $newPayment->setField('UPDATED_1C', 'N');
                $newPayment->setField('XML_ID', $paymentCrm['id']);

                $newPaymentId = $newPayment->getId();

                unset($paymentsList[$newPaymentId]);
            } else {
                RCrmActions::eventLog('RetailCrmHistory::orderHistory', 'paymentsUpdate', 'Save payment error, incorrect type: '  . $paymentCrm['type']);
            }

            if ($optionsPayment[$paymentCrm['status']] == 'Y') {
                $paySumm += $paymentCrm['amount'];
            }
        }
        foreach ($paymentsList as $payment) {
            if ($payment->isPaid()) {
                $payment->setPaid("N");
            }
            $payment->delete();
        }

        if ($paymentsCrm['totalSumm'] == $paySumm) {
            $order->setFieldNoDemand('PAYED', 'Y');
        } else {
            $order->setFieldNoDemand('PAYED', 'N');
        }
    }

    public static function newValue($value)
    {
        if (is_array($value) && array_key_exists('code', $value)) {
            return $value['code'];
        } else {
            return $value;
        }
    }

    public static function removeEmpty($inputArray)
    {
        $outputArray = array();

        if (!empty($inputArray)) {
            foreach ($inputArray as $key => $element) {
                if (!empty($element) || $element === 0 || $element === '0') {
                    if (is_array($element)) {
                        $element = self::removeEmpty($element);
                    }

                    $outputArray[$key] = $element;
                }
            }
        }

        return $outputArray;
    }

    /**
     * setProp
     *
     * @param \Bitrix\Sale\PropertyValueBase|\Bitrix\Sale\Order $obj
     * @param string                                            $value
     * @param string                                            $prop
     *
     * @return bool
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     * @throws \Bitrix\Main\NotImplementedException
     * @throws \Bitrix\Main\ObjectNotFoundException
     * @throws \Bitrix\Main\ArgumentException
     */
    public static function setProp($obj, $value = '', $prop = '')
    {
        if (!isset($obj) || empty($obj)) {
            return false;
        }
        if ($prop && $value || $prop && !$value) {
            $obj->setField($prop, $value);
        } elseif ($value && !$prop) {
            $obj->setValue($value);
        } elseif (!$value && !$prop) {
            $obj->delete();
        }

        return true;
    }

    public static function getExistsItem($basket, $moduleId, $productId)
    {
        foreach ($basket as $basketItem) {
            $itemExists = ($basketItem->getField('PRODUCT_ID') == $productId && $basketItem->getField('MODULE') == $moduleId);

            if ($itemExists) {
                return $basketItem;
            }
        }

        return false;
    }

    public static function setManager($newOrder, $order)
    {
        if (array_key_exists('managerId', $order)) {
            $service = ManagerService::getInstance();

            $newOrder->setField('RESPONSIBLE_ID', $service->getManagerBitrixId($order['managerId']));

            // If the order exists in CRM, need to save the changes in the CMS order
            if (!empty($order['externalId'])) {
                self::orderSave($newOrder);
            }
        }
    }

    public static function getInfoElement($offerId)
    {
        $elementInfo = CIBlockElement::GetByID($offerId)->fetch();
        $url = CAllIBlock::ReplaceDetailUrl($elementInfo['DETAIL_PAGE_URL'], $elementInfo, false, 'E');
        $catalog = CCatalogProduct::GetByID($offerId);

        $info = array(
            'NAME' => $elementInfo['NAME'],
            'URL' => $url,
            'DIMENSIONS' => serialize(array(
                'WIDTH' => $catalog['WIDTH'],
                'HEIGHT' => $catalog['HEIGHT'],
                'LENGTH' => $catalog['LENGTH'],
            )),
            'WEIGHT' => $catalog['WEIGHT'],
            'XML_ID' => $elementInfo["XML_ID"],
            'IBLOCK_XML_ID' => $elementInfo["IBLOCK_EXTERNAL_ID"]
        );

        return $info;
    }

    /**
     * @param $order
     *
     * @return boolean
     */
    private static function orderSave($order)
    {
        try {
            $order->save();

            return true;
        } catch (\Exception $exception) {
            RCrmActions::eventLog(
                'RetailCrmHistory::orderHistory',
                'Order saving',
                $exception->getMessage()
            );

            return false;
        }
    }

    /**
     * Truncate a float number
     *
     * @param float $val
     * @param int f Number of precision
     *
     * @return float
     */
    public static function truncate($val, $precision = "0")
    {
        if(($p = strpos($val, '.')) !== false
            || ($p = strpos($val, ',')) !== false
        ) {
            $val = floatval(substr($val, 0, $p + 1 + $precision));
        }

        return $val;
    }

    /**
     * @param mixed $externalId
     *
     * @return void
     */
    public static function cancelOrder($externalId)
    {
        try {
            $newOrder = Order::load($externalId);
        } catch (ArgumentNullException $e) {
            RCrmActions::eventLog(
                'RetailCrmHistory::orderHistory',
                'Bitrix\Sale\Order::load',
                $e->getMessage() . ': ' . $externalId
            );

            return;
        }

        if (!$newOrder instanceof Order) {
            RCrmActions::eventLog(
                'RetailCrmHistory::orderHistory',
                'Bitrix\Sale\Order::load',
                'Error order load: ' . $externalId
            );

            return;
        }

        try {
            $newOrder->setField('CANCELED', 'Y');
            $newOrder->save();
        } catch (Exception $exception) {
            RCrmActions::eventLog(
                'RetailCrmHistory::orderHistory',
                'Bitrix\Sale\Order::cancelOrder',
                'Error order canceled: ' . $externalId
            );
        }
    }
}
