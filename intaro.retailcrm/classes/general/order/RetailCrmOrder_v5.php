<?php

/**
 * @category RetailCRM
 * @package  RetailCRM\Order
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

use Bitrix\Main\Context;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Context\Culture;
use Bitrix\Main\UserTable;
use Bitrix\Sale\Delivery\Services\Manager;
use Bitrix\Sale\Internals\Fields;
use Bitrix\Sale\Internals\OrderTable;
use Bitrix\Sale\Location\LocationTable;
use Bitrix\Sale\Order;
use Intaro\RetailCrm\Component\ApiClient\ClientAdapter;
use Intaro\RetailCrm\Component\Constants;
use Intaro\RetailCrm\Component\Factory\ClientFactory;
use Intaro\RetailCrm\Component\ServiceLocator;
use Intaro\RetailCrm\Service\LoyaltyService;
use RetailCrm\ApiClient;
use Intaro\RetailCrm\Service\ManagerService;
use Intaro\RetailCrm\Service\LoyaltyAccountService;
use RetailCrm\Response\ApiResponse;
use Intaro\RetailCrm\Component\ConfigProvider;
use Intaro\RetailCrm\Model\Api\Response\OrdersCreateResponse;

IncludeModuleLangFile(__FILE__);

/**
 * Class RetailCrmOrder
 *
 * @category RetailCRM
 * @package RetailCRM\Order
 */
class RetailCrmOrder
{
    /**
     * Creates order or returns order for mass upload
     *
     * @param array  $arOrder
     * @param        $api
     * @param        $arParams
     * @param bool   $send
     * @param null   $site
     * @param string $methodApi
     *
     * @return array|false|\Intaro\RetailCrm\Model\Api\Response\OrdersCreateResponse|\Intaro\RetailCrm\Model\Api\Response\OrdersEditResponse|null
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function orderSend(
        array $arOrder,
        $api,
        $arParams,
        bool $send = false,
        $site = null,
        string $methodApi = 'ordersEdit'
    ) {
        if (!$api || empty($arParams)) { // add cond to check $arParams
            return null;
        }

        if (empty($arOrder)) {
            RCrmActions::eventLog('RetailCrmOrder::orderSend', 'empty($arFields)', 'incorrect order');
            return null;
        }

        $dimensionsSetting = RetailcrmConfigProvider::getOrderDimensions();
        $currency = RetailcrmConfigProvider::getCurrencyOrDefault();

        $order = [
            'number' => $arOrder['NUMBER'],
            'externalId' => $arOrder['ID'],
            'createdAt' => $arOrder['DATE_INSERT'],
            'customer' => isset($arParams['customerCorporate'])
                ? ['id' => $arParams['customerCorporate']['id']]
                : ['externalId' => $arOrder['USER_ID']],
            'orderType' => $arParams['optionsOrderTypes'][$arOrder['PERSON_TYPE_ID']] ?? '',
            'status' => $arParams['optionsPayStatuses'][$arOrder['STATUS_ID']] ?? '',
            'customerComment' => $arOrder['USER_DESCRIPTION'],
            'managerComment'  => $arOrder['COMMENTS'],
            'managerId'  => $arParams['managerId'] ?? null,
            'delivery' => ['cost' => $arOrder['PRICE_DELIVERY']],
        ];

        $orderCouponField = ConfigProvider::getOrderCouponField();

        if ($orderCouponField !== '__default_empty_value__') {
            $couponList = \Bitrix\Sale\Internals\OrderCouponsTable::getList([
                'select' => ['COUPON'],
                'filter' => ['=ORDER_ID' => $arOrder['ID']],
            ]);
            $appliedCoupons = [];

            while ($coupon = $couponList->fetch())
            {
                $appliedCoupons[] = $coupon['COUPON'];
            }

            if (!empty($orderCouponField) && $appliedCoupons !== []) {
                $order['customFields'][$orderCouponField] = implode('; ', $appliedCoupons);
            }
        }

        if (!empty($arOrder['REASON_CANCELED'])) {
            $order['statusComment'] = $arOrder['REASON_CANCELED'];
        }

        if (isset($arParams['contactExId'])) {
            $order['contact']['externalId'] = $arParams['contactExId'];
        }

        if (isset($arParams['orderCompany']) && !empty($arParams['orderCompany'])) {
            $company = $arParams['orderCompany'];

            if (isset($company['id'])) {
                $order['company']['id'] = $company['id'];
            }

            if (isset($company['name'])) {
                $order['contragent']['legalName'] = $company['name'];
            }
        }

        if ($send && isset($_COOKIE['_rc']) && $_COOKIE['_rc'] != '') {
            $order['customer']['browserId'] = $_COOKIE['_rc'];
        }

        $order['contragent']['contragentType'] = $arParams['optionsContragentType'][$arOrder['PERSON_TYPE_ID']];

        $countryList = BitrixOrderService::getCountryList();
        $isSendCustomFields = 'N';

        if (method_exists(RCrmActions::class, 'convertCmsFieldToCrmValue')) {
            $isSendCustomFields = RetailcrmConfigProvider::getCustomFieldsStatus();
        }

        //Order fields
        foreach ($arOrder['PROPS']['properties'] as $prop) {
            if (!empty($arParams['optionsLegalDetails'])
                && is_array($arParams['optionsLegalDetails'][$arOrder['PERSON_TYPE_ID']])
                && $search = array_search($prop['CODE'], $arParams['optionsLegalDetails'][$arOrder['PERSON_TYPE_ID']])
            ) {
                $order['contragent'][$search] = $prop['VALUE'][0];//legal order data
            } elseif ($isSendCustomFields === 'Y'
                && !empty($arParams['customOrderProps'])
                && isset($arParams['customOrderProps'][$prop['ID'] . '#' . $prop['CODE']])
            ) {
                $order['customFields'][$arParams['customOrderProps'][$prop['ID'] . '#' . $prop['CODE']]] = RCrmActions::convertCmsFieldToCrmValue($prop['VALUE'][0], $prop['TYPE']);
            } elseif (is_array($arParams['optionsOrderProps'][$arOrder['PERSON_TYPE_ID']])
                && $search = array_search($prop['CODE'], $arParams['optionsOrderProps'][$arOrder['PERSON_TYPE_ID']])) {//other
                if (in_array($search, ['fio', 'phone', 'email'])) {//fio, phone, email
                    if ($search === 'fio') {
                        $order = array_merge($order, RCrmActions::explodeFio($prop['VALUE'][0]));//add fio fields
                    } elseif ($search === 'email' && mb_strlen($prop['VALUE'][0]) > 100) {
                        continue;
                    } else {
                        // ignoring a property with a non-set group if the field value is already set
                        if (!empty($order[$search]) && $prop['PROPS_GROUP_ID'] == 0) {
                            continue;
                        }

                        $order[$search] = $prop['VALUE'][0];//phone, email
                    }
                } else {
                    if ($prop['TYPE'] === 'LOCATION' && isset($prop['VALUE'][0]) && $prop['VALUE'][0] != '') {
                        $arLoc = LocationTable::getByCode($prop['VALUE'][0])->fetch();

                        if ($arLoc) {
                            $deliveryLocation = CSaleLocation::GetByID($arLoc['ID']);
                            $order['delivery']['address']['region'] = $deliveryLocation['REGION_NAME'] ?? '';
                            $city = $deliveryLocation['CITY_NAME'] ?? null;

                            if (count($countryList) > 0 && isset($countryList[$deliveryLocation['COUNTRY_NAME']])) {
                                $order['countryIso'] = $countryList[$deliveryLocation['COUNTRY_NAME']];
                            }
                        }

                        $prop['VALUE'][0] = null;
                    }

                    if (!empty($prop['VALUE'][0])) {
                        $order['delivery']['address'][$search] = $prop['VALUE'][0];
                    }
                }
            }
        }

        //Перезапись города при его наличии в свойстве типа LOCATION
        if (isset($city)) {
            $order['delivery']['address']['city'] = $city;
        }

        // Пункт самовывоза
        $pickupPointAddress = '';

        if (RetailcrmConfigProvider::needSendPickupPointAddress() === 'Y') {
            $pickupPointAddress = BitrixOrderService::getPickupPointAddress($arOrder);
        }

        if ($pickupPointAddress !== '') {
            $order['delivery']['address']['text'] = $pickupPointAddress;
        }

        //Deliverys
        if (array_key_exists($arOrder['DELIVERYS'][0]['id'], $arParams['optionsDelivTypes'])) {
            $order['delivery']['code'] = $arParams['optionsDelivTypes'][$arOrder['DELIVERYS'][0]['id']];

            if (isset($arOrder['DELIVERYS'][0]['service']) && $arOrder['DELIVERYS'][0]['service'] != '') {
                $order['delivery']['service']['code'] = $arOrder['DELIVERYS'][0]['service'];
            }
        }

        $weight = 0;
        $width = 0;
        $height = 0;
        $length = 0;

        if ('ordersEdit' === $methodApi) {
            $response = RCrmActions::apiMethod($api, 'ordersGet', __METHOD__, $order['externalId'], $site);

            if (isset($response['order'])) {
                foreach ($response['order']['items'] as $k => $item) {
                    $externalId = $k .'_'. $item['offer']['externalId'];
                    $orderItems[$externalId] = $item;
                }
            }
        }

        //basket

        foreach ($arOrder['BASKET'] as $position => $product) {
            $itemId = null;
            $externalId = $position . '_' . $product['PRODUCT_ID'];

            if (isset($orderItems[$externalId])) { //update
                $externalIds = $orderItems[$externalId]['externalIds'];
                $itemId = $orderItems[$externalId]['id'];

                $key = null;
                $keyBasketId = null;

                if (is_array($externalIds)) {
                    $key = array_search('bitrix', array_column($externalIds, 'code'));
                    $keyBasketId = array_search('bitrixBasketId', array_column($externalIds, 'code'));
                }

                if (isset($externalIds[$key]['code']) && $externalIds[$key]['code'] === 'bitrix') {
                    $externalIds[$key] = [
                        'code' => 'bitrix',
                        'value' => $externalId,
                    ];
                } else {
                    $externalIds[] = [
                        'code' => 'bitrix',
                        'value' => $externalId,
                    ];
                }

                if (isset($externalIds[$keyBasketId]['code'])
                    && $externalIds[$keyBasketId]['code'] === 'bitrixBasketId'
                ) {
                    $externalIds[$keyBasketId] = [
                        'code' => 'bitrixBasketId',
                        'value' => $product['ID'] . '#' . $externalId,
                    ];
                } else {
                    $externalIds[] = [
                        'code' => 'bitrixBasketId',
                        'value' => $product['ID'] . '#' . $externalId,
                    ];
                }
            } else { //create
                $externalIds = [
                    [
                        'code' => 'bitrix',
                        'value' => $externalId,
                    ],
                    [
                        'code' => 'bitrixBasketId',
                        'value' => $product['ID'] . '#' . $externalId,
                    ],
                ];
            }

            $item = [
                'externalIds' => $externalIds,
                'quantity' => $product['QUANTITY'],
                'offer' => [
                    'externalId' => $product['PRODUCT_ID'],
                    'xmlId' => $product['PRODUCT_XML_ID'],
                ],
                'productName' => $product['NAME'],
            ];

            if (isset($itemId)) {
                $item['id'] = $itemId;
            }

            $catalogProduct = CCatalogProduct::GetByID($product['PRODUCT_ID']);

            if (is_null($catalogProduct['PURCHASING_PRICE']) === false) {
                if ($catalogProduct['PURCHASING_CURRENCY'] && $currency != $catalogProduct['PURCHASING_CURRENCY']) {
                    $purchasePrice = CCurrencyRates::ConvertCurrency(
                        (double) $catalogProduct['PURCHASING_PRICE'],
                        $catalogProduct['PURCHASING_CURRENCY'],
                        $currency
                    );
                } else {
                    $purchasePrice = $catalogProduct['PURCHASING_PRICE'];
                }

                $item['purchasePrice'] = $purchasePrice;
            }

            $discount = (double) $product['DISCOUNT_PRICE'];
            $dpItem = $product['BASE_PRICE'] - $product['PRICE'];

            if ( $dpItem > 0 && $discount <= 0) {
                $discount = $dpItem;
            }

            $item['discountManualPercent'] = 0;
            $item['initialPrice'] = (double) $product['BASE_PRICE'];

            if (
                $product['BASE_PRICE'] >= $product['PRICE']
                && $methodApi === 'ordersEdit'
                && ConfigProvider::getLoyaltyProgramStatus() === 'Y'
            ) {
                /** @var LoyaltyService $service */
                $service = ServiceLocator::get(LoyaltyService::class);
                $item['discountManualAmount'] = $service->getInitialDiscount((int) $product['ID']) ?? $discount;
            } elseif ($product['BASE_PRICE'] >= $product['PRICE']) {
                $item['discountManualAmount'] = self::getDiscountManualAmount($product);
                $item['initialPrice'] = (double) $product['BASE_PRICE'];
                $order['discountManualAmount'] = 0;
                $order['discountManualPercent'] = 0;
            } else {
                $item['discountManualAmount'] = 0;
                $item['initialPrice'] = $product['PRICE'];
            }

            if (
                !empty($product['VAT_INCLUDED'])
                && $product['VAT_INCLUDED'] === 'Y'
                && RetailcrmConfigProvider::getOrderVat() === 'Y'
            ) {
                $item['vatRate'] = $product['VAT_RATE'] * 100;
            }

            $order['items'][] = $item;

            if ($dimensionsSetting === 'Y') {
                $dimensions = RCrmActions::unserializeArrayRecursive($product['DIMENSIONS']);

                if ($dimensions !== false) {
                    $width += $dimensions['WIDTH'];
                    $height += $dimensions['HEIGHT'];
                    $length += $dimensions['LENGTH'];
                    $weight += $product['WEIGHT'] * $product['QUANTITY'];
                }
            }
        }

        if ($dimensionsSetting === 'Y') {
            $order['width'] = $width;
            $order['height'] = $height;
            $order['length'] = $length;
            $order['weight'] = $weight;
        }

        //payments
        $payments = [];

        foreach ($arOrder['PAYMENTS'] as $payment) {
            if (!empty($payment['PAY_SYSTEM_ID']) && isset($arParams['optionsPayTypes'][$payment['PAY_SYSTEM_ID']])) {
                $crmPayment = [];

                if (!empty($payment['DATE_PAID'])) {
                    $crmPayment['paidAt'] = new \DateTime($payment['DATE_PAID']);
                }

                if (!empty($arParams['optionsPayment'][$payment['PAID']])) {
                    $crmPayment['status'] = $arParams['optionsPayment'][$payment['PAID']];
                }

                if (!empty($payment['ID'])) {
                    $crmPayment['externalId'] = RCrmActions::generatePaymentExternalId($payment['ID']);
                }

                if (RetailcrmConfigProvider::shouldSendPaymentAmount()) {
                    $crmPayment['amount'] = $payment['SUM'];
                }

                $crmPayment = RetailCrmService::preparePayment($crmPayment, $payment, $arParams['optionsPayTypes']);
                $payments[] = $crmPayment;
            } else {
                RCrmActions::eventLog(
                    'RetailCrmOrder::orderSend',
                    'payments',
                    'OrderID = ' . $arOrder['ID'] . '. Payment not found.'
                );
            }
        }

        if (count($payments) > 0) {
            $order['payments'] = $payments;
        }

        //send
        if (function_exists('retailCrmBeforeOrderSend')) {
            $newResOrder = retailCrmBeforeOrderSend($order, $arOrder);
            if (is_array($newResOrder) && !empty($newResOrder)) {
                $order = $newResOrder;
            } elseif ($newResOrder === false) {
                RCrmActions::eventLog(
                    'RetailCrmOrder::orderSend',
                    'retailCrmBeforeOrderSend()',
                    'OrderID = ' . $arOrder['ID'] . '. Sending canceled after retailCrmBeforeOrderSend'
                );

                return false;
            }
        }
        
        if ('ordersEdit' === $methodApi) {
            $order = RetailCrmService::unsetIntegrationDeliveryFields($order);
        }

        $normalizer = new RestNormalizer();
        $order = $normalizer->normalize($order, 'orders');

        Logger::getInstance()->write($order, 'orderSend');

        /** @var ClientAdapter $client */
        $client = ClientFactory::createClientAdapter();
        $userId = $arOrder['USER_ID'];
        $arUser = UserTable::getById($userId)->fetch();

        // Check and set privilegeType
        $order['privilegeType'] = LoyaltyAccountService::getPrivilegeType($client, $userId, $arParams);

        $fioCrm = [$order['firstName'] ?? null, $order['lastName'] ?? null, $order['patronymic'] ?? null];

        if (!empty($arUser['NAME']) && in_array($arUser['NAME'], $fioCrm)) {
            $order['firstName'] = $arUser['NAME'];
        }

        if (!empty($arUser['LAST_NAME']) && in_array($arUser['LAST_NAME'], $fioCrm)) {
            $order['lastName'] = $arUser['LAST_NAME'];
        }

        if ($send) {
            if ($methodApi === 'ordersCreate') {
                if (isset($arParams['customerCorporate']) && !empty($order['contact']['externalId'])) {
                    $externalId = $order['contact']['externalId'];
                } else {
                    $externalId = $order['customer']['externalId'];
                }

                if ($site === null) {
                    $site = RetailcrmConfigProvider::getSitesAvailable();
                }

                $crmBasket = RCrmActions::apiMethod($api, 'cartGet', __METHOD__, $externalId, $site);
                $orderResponse = $client->createOrder($order, $site);

                if ($orderResponse instanceof OrdersCreateResponse && !$orderResponse->success) {

                    Logger::getInstance()->write([
                        'methodApi' => 'orderCreate',
                        'errorMsg' => $orderResponse->errorMsg ?? '',
                        'errors' => $orderResponse->errors ?? '',
                        'params' => ['number' => $order['number'], 'externalId' => $order['externalId']]
                    ], 'apiErrors');

                }

                if (!empty($crmBasket['cart']) && $orderResponse instanceof OrdersCreateResponse && !empty($orderResponse->id) ) {
                    RCrmActions::apiMethod(
                        $api,
                        'cartClear',
                        __METHOD__,
                        [
                            'clearedAt' => date('Y-m-d H:i:sP'),
                            'customer' => [
                                'externalId' => $externalId
                            ],
                            'order' => [
                                'id' => $orderResponse->id
                            ]
                        ],
                        $site
                    );
                }

                return $orderResponse;
            }

            if ($methodApi === 'ordersEdit') {
                return $client->editOrder($order, $site);
            }
        }

        return $order;
    }

    /**
     * Mass order uploading, without repeating; always returns true, but writes error log
     *
     * @param int        $pSize
     * @param bool       $failed -- flag to export failed orders
     * @param array|null $orderList
     *
     * @return bool
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function uploadOrders(int $pSize = 50, bool $failed = false, array $orderList = []): bool
    {
        if (!RetailcrmDependencyLoader::loadDependencies()) {
            return true;
        }

        $ordersPack = [];
        $resCustomers = [];
        $resCustomersAdded = [];
        $resCustomersCorporate = [];
        $orderIds = [];
        $orderUpdateIds = [];

        $lastUpOrderId = RetailcrmConfigProvider::getLastOrderId();
        $failedIds = RetailcrmConfigProvider::getFailedOrdersIds();
        $uploadMethod = RetailcrmConfigProvider::getOrderDischarge();

        if ($failed == true && $failedIds !== false && count($failedIds) > 0) {
            $orderIds = $failedIds;
        } elseif (count($orderList) > 0) {
            $orderIds = $orderList;
        } else {
            $dbOrder = OrderTable::GetList([
                'order' => ['ID' => 'ASC'],
                'filter' => ['>ID' => $lastUpOrderId],
                'limit' => $pSize,
                'select' => ['ID'],
            ]);

            while ($arOrder = $dbOrder->fetch()) {
                $orderIds[] = $arOrder['ID'];
            }
        }

        if((int)$uploadMethod === 0) {
            $dateOfLastUpdate = RetailcrmConfigProvider::getLastOrderUpdate();
            $dbOrderUpdate = OrderTable::GetList([
                'order' => ['ID' => 'ASC'],
                'filter' => ['>DATE_UPDATE' => DateTime::createFromTimestamp(strtotime($dateOfLastUpdate))],
                'limit' => $pSize,
                'select' => ['ID'],
            ]);

            while ($arOrderUpdate = $dbOrderUpdate->fetch()) {
                $orderUpdateIds[] = $arOrderUpdate['ID'];
            }
        }

        $orderIds = array_unique(array_merge($orderIds, $orderUpdateIds));

        if (count($orderIds) <= 0) {
            return false;
        }

        $optionsOrderTypes = RetailcrmConfigProvider::getOrderTypes();
        $optionsDelivTypes = RetailcrmConfigProvider::getDeliveryTypes();
        $optionsPayTypes = RetailcrmConfigProvider::getPaymentTypes();
        $optionsPayStatuses = RetailcrmConfigProvider::getPaymentStatuses(); // --statuses
        $optionsPayment = RetailcrmConfigProvider::getPayment();
        $optionsOrderProps = RetailcrmConfigProvider::getOrderProps();
        $optionsLegalDetails = RetailcrmConfigProvider::getLegalDetails();
        $optionsContragentType = RetailcrmConfigProvider::getContragentTypes();
        $optionsCustomFields = RetailcrmConfigProvider::getCustomFields();

        $api = new ApiClient(RetailcrmConfigProvider::getApiUrl(), RetailcrmConfigProvider::getApiKey());

        $arParams = [
            'optionsOrderTypes'     => $optionsOrderTypes,
            'optionsDelivTypes'     => $optionsDelivTypes,
            'optionsPayTypes'       => $optionsPayTypes,
            'optionsPayStatuses'    => $optionsPayStatuses,
            'optionsPayment'        => $optionsPayment,
            'optionsOrderProps'     => $optionsOrderProps,
            'optionsLegalDetails'   => $optionsLegalDetails,
            'optionsContragentType' => $optionsContragentType,
            'optionsSitesList'      => RetailcrmConfigProvider::getSitesList(),
            'optionsCustomFields'   => $optionsCustomFields,
            'customOrderProps'      => RetailcrmConfigProvider::getMatchedOrderProps(),
        ];

        $recOrders = [];
        $maxUpdateDate = $dateOfLastUpdate;

        foreach ($orderIds as $orderId) {
            $bitrixOrder = Order::load($orderId);

            if (!$bitrixOrder) {
                continue;
            }

            $arCustomer = [];
            $arCustomerCorporate = [];
            $methodApi = 'ordersCreate';

            $order = self::orderObjToArr($bitrixOrder);
            $site = self::getCrmShopCodeByLid($order['LID'], $arParams['optionsSitesList']);

            if (null === $site && count($arParams['optionsSitesList']) > 0) {
                continue;
            }

            if (empty($order['USER_ID'])) {
                RCrmActions::eventLog(
                    'RetailCrmOrder::uploadOrders',
                    'Order::load',
                    'The user does not exist in order: ' . $order['ID']
                );

                continue;
            }

            if((int)$uploadMethod === 0) {
                $orderCrm = RCrmActions::apiMethod($api, 'ordersGet', __METHOD__, $orderId, $site);

                if (isset($orderCrm['order'])) {
                    $methodApi = 'ordersEdit';
                    $arParams['crmOrder'] = $orderCrm['order'];
                }
            }

            if (isset($order['RESPONSIBLE_ID']) && !empty($order['RESPONSIBLE_ID'])) {
                $managerService = ManagerService::getInstance();
                $arParams['managerId']  = $managerService->getManagerCrmId((int) $order['RESPONSIBLE_ID']);
            }

            if ($methodApi === 'ordersEdit') {
                $updateDate = $bitrixOrder->getField('DATE_UPDATE')->format("Y-m-d H:i:s");
                $maxUpdateDate = $updateDate > $maxUpdateDate ? $updateDate : $maxUpdateDate;

                self::orderSend($order, $api, $arParams, true, $site);
                continue;
            }

            self::createCustomerForOrder($api, $arCustomer, $arCustomerCorporate,$arParams, $order, $site);
            $arOrders = self::orderSend($order, $api, $arParams, false, $site, $methodApi);

            if (!$arCustomer || !$arOrders) {
                continue;
            }

            if (!empty($arCustomerCorporate) && !empty($arCustomerCorporate['nickName'])) {
                $resCustomersCorporate[$arCustomerCorporate['nickName']] = $arCustomerCorporate;
            }

            if (
                array_key_exists('externalId', $arCustomer)
                && !in_array($arCustomer['externalId'], $resCustomersAdded, true)
            ) {
                $resCustomersAdded[] = $arCustomer['externalId'];
                $resCustomers[$order['LID']][] = $arCustomer;
            }

            $ordersPack[$order['LID']][] = $arOrders;
            $recOrders[] = $orderId;
        }

        RetailcrmConfigProvider::setLastOrderUpdate($maxUpdateDate);

        if (count($ordersPack) > 0) {
            if (false === RetailCrmOrder::uploadCustomersList($resCustomers, $api, $arParams['optionsSitesList'])) {
                return false;
            }

            if ('Y' === RetailcrmConfigProvider::getCorporateClientStatus()) {
                $cachedCorporateIds = [];

                foreach ($ordersPack as $lid => $lidOrdersList) {
                    $site = self::getCrmShopCodeByLid($lid, $arParams['optionsSitesList']);

                    foreach ($lidOrdersList as $key => $orderData) {
                        $lidOrdersList[$key] = self::addCorporateCustomerToOrder(
                            $orderData,
                            $api,
                            $resCustomersCorporate,
                            $cachedCorporateIds,
                            $site
                        );
                    }

                    $ordersPack[$lid] = $lidOrdersList;
                }
            }

            if (false === RetailCrmOrder::uploadOrdersList($ordersPack, $api, $arParams['optionsSitesList'])) {
                return false;
            }

            if ($failed == true && $failedIds !== false && count($failedIds) > 0) {
                RetailcrmConfigProvider::setFailedOrdersIds(array_diff($failedIds, $recOrders));
            } elseif (count($orderList) === 0 && $lastUpOrderId < max($recOrders)) {
                RetailcrmConfigProvider::setLastOrderId(max($recOrders));
            }
        }

        return true;
    }

    /**
     * @param \RetailCrm\ApiClient $api
     * @param array                $arCustomer
     * @param array                $arCustomerCorporate
     * @param array                $arParams
     * @param array                $order
     * @param                      $site
     *
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     * @throws \Exception
     */
    public static function createCustomerForOrder(
        ApiClient $api,
        array &$arCustomer,
        array &$arCustomerCorporate,
        array &$arParams,
        array $order,
        $site
    ): void {
        $optionsContragentType = RetailcrmConfigProvider::getContragentTypes();
        $user = UserTable::getById($order['USER_ID'])->fetch();

        if (!$user) {
            RCrmActions::eventLog(
                'RetailCrmOrder::createCustomerForOrder',
                'UserTable::getById',
                'Error find user: ' . $order['USER_ID'] . ' in order: ' . $order['ID']
            );

            return;
        }

        if ('Y' === RetailcrmConfigProvider::getCorporateClientStatus()) {
            if (true === RetailCrmCorporateClient::isCorpTookExternalId((string) $user['ID'], $api, $site)) {
                RetailCrmCorporateClient::setPrefixForExternalId((string) $user['ID'], $api, $site);
            }
        }

        if (
            'Y' === RetailcrmConfigProvider::getCorporateClientStatus()
            && in_array($optionsContragentType[$order['PERSON_TYPE_ID']], ['legal-entity', 'enterpreneur'])
        ) {
            // TODO check if order is corporate, and if it IS - make corporate order
            $arCustomer = RetailCrmUser::customerSend(
                $user,
                $api,
                'individual',
                false,
                $site
            );

            $arCustomerCorporate = RetailCrmCorporateClient::clientSend(
                $order,
                $api,
                $optionsContragentType[$order['PERSON_TYPE_ID']],
                false,
                true,
                $site
            );

            $arParams['orderCompany'] = isset($arCustomerCorporate['companies'])
                ? reset($arCustomerCorporate['companies'])
                : null;
            $arParams['contactExId'] = $user['ID'];

            return;
        }

        $arCustomer = RetailCrmUser::customerSend(
            $user,
            $api,
            $optionsContragentType[$order['PERSON_TYPE_ID']],
            false,
            $site
        );

        if (isset($arParams['contactExId'])) {
            unset($arParams['contactExId']);
        }
    }

    /**
     * @param array                $orderData
     * @param \RetailCrm\ApiClient $api
     * @param array                $resCustomersCorporate
     * @param array                $cachedCorporateIds
     *
     * @return array
     */
    public static function addCorporateCustomerToOrder(
        array $orderData,
        ApiClient $api,
        array $resCustomersCorporate,
        array &$cachedCorporateIds,
        $site = null
    ): array {
        $customerLegalName = $orderData['contragent']['legalName'];

        if (
            isset($orderData['contragent']['contragentType'])
            && in_array($orderData['contragent']['contragentType'], ['legal-entity', 'enterpreneur'])
            && !empty($customerLegalName)
        ) {
            if (isset($cachedCorporateIds[$customerLegalName])) {
                $orderData['customer'] = ['id' => $cachedCorporateIds[$customerLegalName]];
            } else {
                $corpListResponse = $api->customersCorporateList(['nickName' => [$customerLegalName]]);

                if (
                    $corpListResponse
                    && $corpListResponse->isSuccessful()
                    && $corpListResponse->offsetExists('customersCorporate')
                    && !empty($corpListResponse['customersCorporate'])
                ) {
                    $corpListResponse = $corpListResponse['customersCorporate'];
                    $corpListResponse = reset($corpListResponse);

                    $orderData['customer'] = ['id' => $corpListResponse['id']];
                    $cachedCorporateIds[$customerLegalName] = $corpListResponse['id'];

                    RetailCrmCorporateClient::addCustomersCorporateAddresses(
                        $orderData['customer']['id'],
                        $customerLegalName,
                        $orderData['delivery']['address']['text'],
                        $api,
                        null
                    );
                } elseif (array_key_exists($customerLegalName, $resCustomersCorporate)) {
                    $createResponse = $api->customersCorporateCreate(
                            $resCustomersCorporate[$customerLegalName],
                            $site
                        );

                    if ($createResponse && $createResponse->isSuccessful()) {
                        $orderData['customer'] = ['id' => $createResponse['id']];
                        $cachedCorporateIds[$customerLegalName] = $createResponse['id'];
                    }
                }

                time_nanosleep(0, 250000000);
            }
        }

        return $orderData;
    }

    /**
     * @param array $resCustomers
     * @param RetailCrm\ApiClient $api
     * @param array $optionsSitesList
     *
     * @return array|false
     */
    public static function uploadCustomersList(array $resCustomers, ApiClient $api, array $optionsSitesList)
    {
        return self::uploadItems(
            $resCustomers,
            'customersUpload',
            'uploadedCustomers',
            $api,
            $optionsSitesList
        );
    }

    /**
     * @param array $resOrders
     * @param RetailCrm\ApiClient $api
     * @param array $optionsSitesList
     *
     * @return array|false
     */
    public static function uploadOrdersList(array $resOrders, ApiClient $api, array $optionsSitesList)
    {
        return self::uploadItems(
            $resOrders,
            'ordersUpload',
            'uploadedOrders',
            $api,
            $optionsSitesList
        );
    }

    /**
     * @param string $orderLid
     * @param array  $optionsSitesList
     *
     * @return string|null
     */
    public static function getCrmShopCodeByLid(string $orderLid, array $optionsSitesList): ?string
    {
        return $optionsSitesList[$orderLid] ?? null;
    }

    /**
     * @param array $pack
     * @param string $method
     * @param string $keyResponse
     * @param RetailCrm\ApiClient $api
     * @param array $optionsSitesList
     *
     * @return array|false
     */
    public static function uploadItems(
        array $pack,
        string $method,
        string $keyResponse,
        ApiClient $api,
        array $optionsSitesList
    ) {
        $uploaded = [];
        $sizePack = 50;

        foreach ($pack as $key => $itemLoad) {
            $site = self::getCrmShopCodeByLid($key, $optionsSitesList);

            if (null === $site && count($optionsSitesList) > 0) {
                continue;
            }

            $chunkList = array_chunk($itemLoad, $sizePack, true);

            foreach ($chunkList as $chunk) {
                time_nanosleep(0, 250000000);

                /** @var \RetailCrm\Response\ApiResponse|bool $response */
                $response = RCrmActions::apiMethod(
                    $api,
                    $method,
                    __METHOD__,
                    $chunk,
                    $site
                );

                if ($response === false) {
                    return false;
                }

                if (($response instanceof ApiResponse) && $response->offsetExists($keyResponse)) {
                    $uploaded = array_merge($uploaded, $response[$keyResponse]);
                }
            }
        }

        return $uploaded;
    }

    /**
     * Returns true if provided order array is corporate order data
     *
     * @param array|\ArrayAccess $order
     *
     * @return bool
     */
    public static function isOrderCorporate($order): bool
    {
        return isset($order['customer'], $order['customer']['type'])
            && (is_array($order) || $order instanceof ArrayAccess)
            && $order['customer']['type'] === 'customer_corporate';
    }

    /**
     * Converts order object to array
     *
     * @param \Bitrix\Sale\Order $obOrder
     *
     * @return array
     * @throws \Bitrix\Main\SystemException
     */
    public static function orderObjToArr(Order $obOrder): array
    {
        $culture = new Culture(['FORMAT_DATETIME' => 'Y-m-d HH:i:s']);
        $arOrder = [
            'ID'               => $obOrder->getId(),
            'NUMBER'           => $obOrder->getField('ACCOUNT_NUMBER'),
            'LID'              => $obOrder->getSiteId(),
            'DATE_INSERT'      => $obOrder->getDateInsert()->toString($culture),
            'STATUS_ID'        => $obOrder->getField('STATUS_ID'),
            'USER_ID'          => $obOrder->getUserId(),
            'PERSON_TYPE_ID'   => $obOrder->getPersonTypeId(),
            'CURRENCY'         => $obOrder->getCurrency(),
            'PAYMENTS'         => [],
            'DELIVERYS'        => [],
            'PRICE_DELIVERY'   => $obOrder->getDeliveryPrice(),
            'PROPS'            => $obOrder->getPropertyCollection()->getArray(),
            'DISCOUNTS'        => $obOrder->getDiscount()->getApplyResult(),
            'BASKET'           => [],
            'USER_DESCRIPTION' => $obOrder->getField('USER_DESCRIPTION'),
            'COMMENTS'         => $obOrder->getField('COMMENTS'),
            'REASON_CANCELED'  => $obOrder->getField('REASON_CANCELED'),
            'RESPONSIBLE_ID'   => $obOrder->getField('RESPONSIBLE_ID'),
        ];

        $shipmentList = $obOrder->getShipmentCollection();

        foreach ($shipmentList as $shipmentData) {
            if ($shipmentData->isSystem()) {
                continue;
            }

            if ($shipmentData->getDeliveryId()) {
                $delivery = Manager::getById($shipmentData->getDeliveryId());
                $siteDeliverys = RCrmActions::DeliveryList();

                foreach ($siteDeliverys as $siteDelivery) {
                    if ($siteDelivery['ID'] == $delivery['ID'] && $siteDelivery['PARENT_ID'] == 0) {
                        unset($delivery['PARENT_ID']);
                    }
                }
                if ($delivery['PARENT_ID']) {
                    $service = explode(':', $delivery['CODE']);
                    $shipment = $delivery['CLASS_NAME'] === '\Sale\Handlers\Delivery\RussianpostProfile'
                        ? ['id' => $delivery['PARENT_ID'], 'service' => 'bitrix-' . $delivery['ID']]
                        : ['id' => $delivery['PARENT_ID'], 'service' => $service[1]];
                } else {
                    $shipment = ['id' => $delivery['ID']];
                }
                $arOrder['DELIVERYS'][] = $shipment;
            }
        }

        $paymentList = $obOrder->getPaymentCollection();

        foreach ($paymentList as $paymentData) {
            $arOrder['PAYMENTS'][] = $paymentData->getFields()->getValues();
        }

        $basketItems = $obOrder->getBasket();

        foreach ($basketItems as $item) {
            $arOrder['BASKET'][] = $item->getFields();
        }

        return $arOrder;
    }

    /**
     * @param \Bitrix\Sale\Internals\Fields $product
     *
     * @return float
     */
    public static function getDiscountManualAmount(Fields $product): float
    {
        if ($product->get('CUSTOM_PRICE') === 'Y') {
            $sumDifference = $product->get('BASE_PRICE') - $product->get('PRICE');
            return $sumDifference > 0 ? $sumDifference : 0.0;
        }

        $discount = (double) $product->get('DISCOUNT_PRICE');
        $dpItem = $product->get('BASE_PRICE') - $product->get('PRICE');

        if ($dpItem > 0 && $discount <= 0) {
            return $dpItem;
        }

        return $discount;
    }
}
