<?php

use Bitrix\Main\UserTable;
use Intaro\RetailCrm\Component\ConfigProvider;
use Intaro\RetailCrm\Model\Api\Response\OrdersCreateResponse;
use Intaro\RetailCrm\Model\Api\Response\OrdersEditResponse;

/**
 * Class RetailCrmEvent
 */
class RetailCrmEvent
{
    protected static $MODULE_ID              = 'intaro.retailcrm';
    protected static $CRM_API_HOST_OPTION    = 'api_host';
    protected static $CRM_ORDER_TYPES_ARR    = 'order_types_arr';
    protected static $CRM_DELIVERY_TYPES_ARR = 'deliv_types_arr';
    protected static $CRM_PAYMENT_TYPES      = 'pay_types_arr';
    protected static $CRM_PAYMENT_STATUSES   = 'pay_statuses_arr';
    protected static $CRM_PAYMENT            = 'payment_arr';
    protected static $CRM_ORDER_LAST_ID      = 'order_last_id';
    protected static $CRM_ORDER_PROPS        = 'order_props';
    protected static $CRM_LEGAL_DETAILS      = 'legal_details';
    protected static $CRM_CUSTOM_FIELDS      = 'custom_fields';
    protected static $CRM_CONTRAGENT_TYPE    = 'contragent_type';
    protected static $CRM_ORDER_FAILED_IDS   = 'order_failed_ids';
    protected static $CRM_SITES_LIST         = 'sites_list';
    protected static $CRM_CC                 = 'cc';
    protected static $CRM_CORP_NAME          = 'nickName-corporate';
    protected static $CRM_CORP_ADRES         = 'adres-corporate';
    
    /**
     * @param $arFields
     *
     * @return bool
     * @throws InvalidArgumentException
     */
    function OnAfterUserUpdate($arFields)
    {
        if (isset($GLOBALS['RETAIL_CRM_HISTORY']) && $GLOBALS['RETAIL_CRM_HISTORY']) {
            return false;
        }
        
        if (!$arFields['RESULT']) {
            return false;
        }
        
        $optionsSitesList = RetailcrmConfigProvider::getSitesList();
        $api              = new RetailCrm\ApiClient(RetailcrmConfigProvider::getApiUrl(), RetailcrmConfigProvider::getApiKey());
        $resultOrder      = RetailCrmUser::customerEdit($arFields, $api, $optionsSitesList);
        
        if (!$resultOrder) {
            RCrmActions::eventLog('RetailCrmEvent::OnAfterUserUpdate', 'RetailCrmUser::customerEdit', 'error update customer');
        }
        
        return true;
    }
    
    /**
     * onUpdateOrder
     *
     * @param mixed $ID       - Order id
     * @param mixed $arFields - Order arFields
     */
    function onUpdateOrder($ID, $arFields)
    {
        if (isset($GLOBALS['RETAIL_CRM_HISTORY']) && $GLOBALS['RETAIL_CRM_HISTORY']) {
            $GLOBALS['RETAILCRM_ORDER_OLD_EVENT'] = false;
            return;
        }
        
        $GLOBALS['RETAILCRM_ORDER_OLD_EVENT'] = true;
        
        if (($arFields['CANCELED'] == 'Y')
            && (sizeof($arFields['BASKET_ITEMS']) == 0)
            && (sizeof($arFields['ORDER_PROP']) == 0)
        ) {
            $GLOBALS['ORDER_DELETE_USER_ADMIN'] = true;
        }
        
        return;
    }
    
    /**
     * orderDelete
     *
     * @param object $event - Order object
     */
    function orderDelete($event)
    {
        $GLOBALS['RETAILCRM_ORDER_DELETE'] = true;
        
        return;
    }
    
    /**
     * @param mixed $event
     *
     * @return array|bool|null|OrdersCreateResponse|OrdersEditResponse
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function orderSave($event)
    {
        $isConfigValid = $this->checkConfig();
        
        if (!$isConfigValid) {
            return null;
        }
    
        $arOrder = $this->getOrderArray($event);
        
        $api = new RetailCrm\ApiClient(RetailcrmConfigProvider::getApiUrl(), RetailcrmConfigProvider::getApiKey());
        
        //params
        $optionsOrderTypes     = RetailcrmConfigProvider::getOrderTypes();
        $optionsDelivTypes     = RetailcrmConfigProvider::getDeliveryTypes();
        $optionsPayTypes       = RetailcrmConfigProvider::getPaymentTypes();
        $optionsPayStatuses    = RetailcrmConfigProvider::getPaymentStatuses(); // --statuses
        $optionsPayment        = RetailcrmConfigProvider::getPayment();
        $optionsSitesList      = RetailcrmConfigProvider::getSitesList();
        $optionsOrderProps     = RetailcrmConfigProvider::getOrderProps();
        $optionsLegalDetails   = RetailcrmConfigProvider::getLegalDetails();
        $optionsContragentType = RetailcrmConfigProvider::getContragentTypes();
        $optionsCustomFields   = RetailcrmConfigProvider::getCustomFields();
        
        //corp cliente swich
        $optionCorpClient = RetailcrmConfigProvider::getCorporateClientStatus();
        
        $arParams = RCrmActions::clearArr([
            'optionsOrderTypes'     => $optionsOrderTypes,
            'optionsDelivTypes'     => $optionsDelivTypes,
            'optionsPayTypes'       => $optionsPayTypes,
            'optionsPayStatuses'    => $optionsPayStatuses,
            'optionsPayment'        => $optionsPayment,
            'optionsOrderProps'     => $optionsOrderProps,
            'optionsLegalDetails'   => $optionsLegalDetails,
            'optionsContragentType' => $optionsContragentType,
            'optionsSitesList'      => $optionsSitesList,
            'optionsCustomFields'   => $optionsCustomFields,
        ]);
        
        //many sites?
        if ($optionsSitesList) {
            if (array_key_exists($arOrder['LID'], $optionsSitesList) && $optionsSitesList[$arOrder['LID']] !== null) {
                $site = $optionsSitesList[$arOrder['LID']];
            } else {
                return null;
            }
        } elseif (!$optionsSitesList) {
            $site = null;
        }
        
        //new order?
        $orderCrm = RCrmActions::apiMethod($api, 'ordersGet', __METHOD__, $arOrder['ID'], $site);
       
        if (isset($orderCrm['order'])) {
            $methodApi            = 'ordersEdit';
            $arParams['crmOrder'] = $orderCrm['order'];
        } else {
            $methodApi = 'ordersCreate';
        }
        
        $orderCompany = null;
        
        if ('Y' === $optionCorpClient && $optionsContragentType[$arOrder['PERSON_TYPE_ID']] == 'legal-entity') {
            //corparate cliente
            $nickName    = '';
            $address     = '';
            $corpAddress = '';
            $contragent  = [];
            $userCorp    = [];
            $corpName    = RetailcrmConfigProvider::getCorporateClientName();
            $corpAddress = RetailcrmConfigProvider::getCorporateClientAddress();
            
            foreach ($arOrder['PROPS']['properties'] as $prop) {
                if ($prop['CODE'] == $corpName) {
                    $nickName = $prop['VALUE'][0];
                }
                
                if ($prop['CODE'] == $corpAddress) {
                    $address = $prop['VALUE'][0];
                }
                
                if (!empty($optionsLegalDetails)
                    && $search = array_search($prop['CODE'], $optionsLegalDetails[$arOrder['PERSON_TYPE_ID']])
                ) {
                    $contragent[$search] = $prop['VALUE'][0];//legal order data
                }
            }
            
            if (!empty($contragentType)) {
                $contragent['contragentType'] = $contragentType;
            }
            
            $customersCorporate = false;
            $response           = $api->customersCorporateList(['companyName' => $nickName]);
            
            if ($response && $response->getStatusCode() == 200) {
                $customersCorporate = $response['customersCorporate'];
                $singleCorp         = reset($customersCorporate);
                
                if (!empty($singleCorp)) {
                    $userCorp['customerCorporate'] = $singleCorp;
                    $companiesResponse             = $api->customersCorporateCompanies(
                        $singleCorp['id'],
                        [],
                        null,
                        null,
                        'id',
                        $site
                    );
                    
                    if ($companiesResponse && $companiesResponse->isSuccessful()) {
                        $orderCompany = array_reduce(
                            $companiesResponse['companies'],
                            function ($carry, $item) use ($nickName) {
                                if (is_array($item) && $item['name'] == $nickName) {
                                    $carry = $item;
                                }
                                
                                return $carry;
                            },
                            null
                        );
                    }
                }
            } else {
                RCrmActions::eventLog(
                    'RetailCrmEvent::orderSave',
                    'ApiClient::customersCorporateList',
                    'error during fetching corporate customers'
                );
                
                return null;
            }
            
            //user
            $userCrm = RCrmActions::apiMethod($api, 'customersGet', __METHOD__, $arOrder['USER_ID'], $site);
            
            if (!isset($userCrm['customer'])) {
                $arUser = UserTable::getById($arOrder['USER_ID'])->fetch();
                
                if (!empty($address)) {
                    $arUser['PERSONAL_STREET'] = $address;
                }
                
                $resultUser = RetailCrmUser::customerSend($arUser, $api, "individual", true, $site);
                
                if (!$resultUser) {
                    RCrmActions::eventLog(
                        __CLASS__ . '::' . __METHOD__,
                        'RetailCrmUser::customerSend',
                        'error during creating customer'
                    );
                    
                    return null;
                }
                
                $userCrm = ['customer' => ['externalId' => $arOrder['USER_ID']]];
            }
            
            if (!isset($userCorp['customerCorporate'])) {
                $resultUserCorp = RetailCrmCorporateClient::clientSend(
                    $arOrder,
                    $api,
                    $optionsContragentType[$arOrder['PERSON_TYPE_ID']],
                    true,
                    false,
                    $site
                );
                
                Logger::getInstance()->write($resultUserCorp, 'resultUserCorp');
                
                if (!$resultUserCorp) {
                    RCrmActions::eventLog('RetailCrmEvent::orderSave', 'RetailCrmCorporateClient::clientSend', 'error during creating client');
                    
                    return null;
                }
                
                $arParams['customerCorporate'] = $resultUserCorp;
                $arParams['orderCompany']      = $resultUserCorp['mainCompany'] ?? null;
                
                $customerCorporateAddress = [];
                $customerCorporateCompany = [];
                $addressResult            = null;
                $companyResult            = null;
                
                if (!empty($address)) {
                    //TODO address builder add
                    $customerCorporateAddress = [
                        'name'   => $nickName,
                        'isMain' => true,
                        'text'   => $address,
                    ];
                    
                    $addressResult = $api->customersCorporateAddressesCreate($resultUserCorp['id'], $customerCorporateAddress, 'id', $site);
                }
                
                $customerCorporateCompany = [
                    'name'       => $nickName,
                    'isMain'     => true,
                    'contragent' => $contragent,
                ];
                
                if (!empty($addressResult)) {
                    $customerCorporateCompany['address'] = [
                        'id' => $addressResult['id'],
                    ];
                }
                
                $companyResult = $api->customersCorporateCompaniesCreate($resultUserCorp['id'], $customerCorporateCompany, 'id', $site);
                
                $customerCorporateContact = [
                    'isMain'   => true,
                    'customer' => [
                        'externalId' => $arOrder['USER_ID'],
                        'site'       => $site,
                    ],
                ];
                
                if (!empty($companyResult)) {
                    $orderCompany = [
                        'id' => $companyResult['id'],
                    ];
                    
                    $customerCorporateContact['companies'] = [
                        [
                            'company' => $orderCompany,
                        ],
                    ];
                }
                
                $api->customersCorporateContactsCreate(
                    $resultUserCorp['id'],
                    $customerCorporateContact,
                    'id',
                    $site
                );
                
                $arParams['orderCompany'] = array_merge(
                    $customerCorporateCompany,
                    ['id' => $companyResult['id']]
                );
            } else {
                RetailCrmCorporateClient::addCustomersCorporateAddresses(
                    $userCorp['customerCorporate']['id'],
                    $nickName,
                    $address,
                    $api,
                    $site = null
                );
                
                $arParams['customerCorporate'] = $userCorp['customerCorporate'];
                
                if (!empty($orderCompany)) {
                    $arParams['orderCompany'] = $orderCompany;
                }
            }
            
            $arParams['contactExId'] = $userCrm['customer']['externalId'];
        } else {
            //user
            $userCrm = RCrmActions::apiMethod($api, 'customersGet', __METHOD__, $arOrder['USER_ID'], $site);
            
            if (!isset($userCrm['customer'])) {
                $arUser     = Bitrix\Main\UserTable::getById($arOrder['USER_ID'])->fetch();
                $resultUser = RetailCrmUser::customerSend(
                    $arUser,
                    $api,
                    $optionsContragentType[$arOrder['PERSON_TYPE_ID']],
                    true,
                    $site
                );
                
                if (!$resultUser) {
                    RCrmActions::eventLog(
                        'RetailCrmEvent::orderSave',
                        'RetailCrmUser::customerSend',
                        'error during creating customer'
                    );
                    
                    return null;
                }
            }
        }

        $resultOrder = RetailCrmOrder::orderSend($arOrder, $api, $arParams, true, $site, $methodApi);

        if (!$resultOrder) {
            RCrmActions::eventLog(
                'RetailCrmEvent::orderSave',
                'RetailCrmOrder::orderSend',
                'error during creating order'
            );
            
            return null;
        }
        
        return $resultOrder;
    }
    
    /**
     * @param \Bitrix\Sale\Payment $event
     *
     * @return bool
     * @throws InvalidArgumentException
     *
     */
    function paymentSave($event)
    {
        $apiVersion = COption::GetOptionString(self::$MODULE_ID, 'api_version', 0);
        
        /** @var \Bitrix\Sale\Order $order */
        $order = $event->getCollection()->getOrder();
        
        if ((isset($GLOBALS['RETAIL_CRM_HISTORY']) && $GLOBALS['RETAIL_CRM_HISTORY'])
            || $apiVersion != 'v5'
            || $order->isNew()
        ) {
            return false;
        }
        
        $optionsSitesList    = RetailcrmConfigProvider::getSitesList();
        $optionsPaymentTypes = RetailcrmConfigProvider::getPaymentTypes();
        $optionsPayStatuses  = RetailcrmConfigProvider::getPayment();
        
        $arPayment = [
            'ID'            => $event->getId(),
            'ORDER_ID'      => $event->getField('ORDER_ID'),
            'PAID'          => $event->getField('PAID'),
            'PAY_SYSTEM_ID' => $event->getField('PAY_SYSTEM_ID'),
            'SUM'           => $event->getField('SUM'),
            'LID'           => $order->getSiteId(),
            'DATE_PAID'     => $event->getField('DATE_PAID'),
        ];
        
        if ($optionsSitesList) {
            if (array_key_exists($arPayment['LID'], $optionsSitesList) && $optionsSitesList[$arPayment['LID']] !== null) {
                $site = $optionsSitesList[$arPayment['LID']];
            } else {
                return false;
            }
        } elseif (!$optionsSitesList) {
            $site = null;
        }
        
        $apiHost = ConfigProvider::getApiUrl();
        $apiKey  = ConfigProvider::getApiKey();
        $api      = new RetailCrm\ApiClient($apiHost, $apiKey);
        $orderCrm = RCrmActions::apiMethod($api, 'ordersGet', __METHOD__, $arPayment['ORDER_ID'], $site);
        
        if (isset($orderCrm['order'])) {
            $payments = $orderCrm['order']['payments'];
        }
        
        if ($payments) {
            foreach ($payments as $payment) {
                if (isset($payment['externalId'])) {
                    if (RCrmActions::isNewExternalId($payment['externalId'])) {
                        $paymentsExternalIds[RCrmActions::getFromPaymentExternalId($payment['externalId'])] =
                            $payment;
                    } else {
                        $paymentsExternalIds[$payment['externalId']] = $payment;
                    }
                }
            }
        }
        
        if (!empty($arPayment['PAY_SYSTEM_ID']) && isset($optionsPaymentTypes[$arPayment['PAY_SYSTEM_ID']])) {
            $paymentToCrm = [
                'type' => $optionsPaymentTypes[$arPayment['PAY_SYSTEM_ID']],
            ];
            
            if (!empty($arPayment['ID'])) {
                $paymentToCrm['externalId'] = RCrmActions::generatePaymentExternalId($arPayment['ID']);
            }
            
            if (!empty($arPayment['DATE_PAID'])) {
                if (is_object($arPayment['DATE_PAID'])) {
                    $culture                = new Bitrix\Main\Context\Culture(["FORMAT_DATETIME" => "YYYY-MM-DD HH:MI:SS"]);
                    $paymentToCrm['paidAt'] = $arPayment['DATE_PAID']->toString($culture);
                } elseif (is_string($arPayment['DATE_PAID'])) {
                    $paymentToCrm['paidAt'] = $arPayment['DATE_PAID'];
                }
            }
            
            if (!empty($optionsPayStatuses[$arPayment['PAID']])) {
                $paymentToCrm['status'] = $optionsPayStatuses[$arPayment['PAID']];
            }
            
            if (!empty($arPayment['ORDER_ID'])) {
                $paymentToCrm['order']['externalId'] = $arPayment['ORDER_ID'];
            }
            
            if (RetailcrmConfigProvider::shouldSendPaymentAmount()) {
                $paymentToCrm['amount'] = $arPayment['SUM'];
            }
        } else {
            RCrmActions::eventLog('RetailCrmEvent::paymentSave', 'payments', 'OrderID = ' . $arPayment['ID'] . '. Payment not found.');
            return false;
        }
        
        $arPaymentExtId = RCrmActions::generatePaymentExternalId($arPayment['ID']);
        
        if (array_key_exists($arPaymentExtId, $paymentsExternalIds)) {
            $paymentData = $paymentsExternalIds[$arPaymentExtId];
        } elseif (array_key_exists($arPayment['ID'], $paymentsExternalIds)) {
            $paymentData = $paymentsExternalIds[$arPayment['ID']];
        } else {
            $paymentData = [];
        }
        
        if (empty($paymentData)) {
            RCrmActions::apiMethod($api, 'ordersPaymentCreate', __METHOD__, $paymentToCrm, $site);
        } elseif ($paymentData['type'] == $optionsPaymentTypes[$arPayment['PAY_SYSTEM_ID']]) {
            $paymentToCrm['externalId'] = $paymentData['externalId'];
            RCrmActions::apiMethod($api, 'paymentEditByExternalId', __METHOD__, $paymentToCrm, $site);
        } elseif ($paymentData['type'] != $optionsPaymentTypes[$arPayment['PAY_SYSTEM_ID']]) {
            RCrmActions::apiMethod(
                $api,
                'ordersPaymentDelete',
                __METHOD__,
                $paymentData['id']
            );
            RCrmActions::apiMethod($api, 'ordersPaymentCreate', __METHOD__, $paymentToCrm, $site);
        }
        
        return true;
    }
    
    /**
     * @param \Bitrix\Sale\Payment $event
     *
     * @throws InvalidArgumentException
     */
    function paymentDelete($event)
    {
        $apiVersion = COption::GetOptionString(self::$MODULE_ID, 'api_version', 0);
        
        if ((isset($GLOBALS['RETAIL_CRM_HISTORY']) && $GLOBALS['RETAIL_CRM_HISTORY'])
            || $apiVersion != 'v5'
            || !$event->getId()
        ) {
            return;
        }
        
        $optionsSitesList = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_SITES_LIST, 0));
        
        $arPayment = [
            'ID'       => $event->getId(),
            'ORDER_ID' => $event->getField('ORDER_ID'),
            'LID'      => $event->getCollection()->getOrder()->getSiteId(),
        ];
        
        if ($optionsSitesList) {
            if (array_key_exists($arPayment['LID'], $optionsSitesList) && $optionsSitesList[$arPayment['LID']] !== null) {
                $site = $optionsSitesList[$arPayment['LID']];
            } else {
                return;
            }
        } elseif (!$optionsSitesList) {
            $site = null;
        }
        
        $apiHost = ConfigProvider::getApiUrl();
        $apiKey  = ConfigProvider::getApiKey();
        $api      = new RetailCrm\ApiClient($apiHost, $apiKey);
        $orderCrm = RCrmActions::apiMethod($api, 'ordersGet', __METHOD__, $arPayment['ORDER_ID'], $site);
        
        if (isset($orderCrm['order']['payments']) && $orderCrm['order']['payments']) {
            foreach ($orderCrm['order']['payments'] as $payment) {
                if (isset($payment['externalId'])
                    && ($payment['externalId'] == $event->getId()
                        || RCrmActions::getFromPaymentExternalId($payment['externalId']) == $event->getId())
                ) {
                    RCrmActions::apiMethod($api, 'ordersPaymentDelete', __METHOD__, $payment['id']);
                }
            }
        }
    }
    
    /**
     * @return bool
     */
    private function checkConfig(): bool
    {
        if (true == $GLOBALS['ORDER_DELETE_USER_ADMIN']) {
            return false;
        }
    
        if ($GLOBALS['RETAILCRM_ORDER_OLD_EVENT'] === false
            && $GLOBALS['RETAILCRM_ORDER_DELETE'] === true
        ) {
            return false;
        }
    
        if ($GLOBALS['RETAIL_CRM_HISTORY'] === true) {
            return false;
        }
    
        if (!CModule::IncludeModule('iblock')) {
            RCrmActions::eventLog('RetailCrmEvent::orderSave', 'iblock', 'module not found');
        
            return false;
        }
    
        if (!CModule::IncludeModule('sale')) {
            RCrmActions::eventLog('RetailCrmEvent::orderSave', 'sale', 'module not found');
        
            return false;
        }
    
        if (!CModule::IncludeModule('catalog')) {
            RCrmActions::eventLog('RetailCrmEvent::orderSave', 'catalog', 'module not found');
        
            return false;
        }
        
        return true;
    }
    
    /**
     * @throws \Bitrix\Main\SystemException
     */
    private function getOrderArray($event): ?array
    {
        //exists getParameter("ENTITY")
        if (method_exists($event, 'getId')) {
            $obOrder = $event;
        } elseif (method_exists($event, 'getParameter')) {
            $obOrder = $event->getParameter('ENTITY');
        } else {
            RCrmActions::eventLog('RetailCrmEvent::orderSave', 'events', 'event error');
        
            return null;
        }
    
        return RetailCrmOrder::orderObjToArr($obOrder);
    }
}
