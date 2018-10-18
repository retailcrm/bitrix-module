<?php
IncludeModuleLangFile(__FILE__);
class RetailCrmOrder
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
    public static $CRM_ORDER_HISTORY_DATE = 'order_history_date';
    public static $CRM_CATALOG_BASE_PRICE = 'catalog_base_price';
    public static $CRM_ORDER_NUMBERS = 'order_numbers';
    public static $CRM_ORDER_DIMENSIONS = 'order_dimensions';

    const CANCEL_PROPERTY_CODE = 'INTAROCRM_IS_CANCELED';

    /**
     *
     * Creates order or returns order for mass upload
     *
     * @param array $arFields
     * @param $api
     * @param $arParams
     * @param $send
     * @return boolean
     * @return array - array('order' = $order, 'customer' => $customer)
     */
    public static function orderSend($arFields, $api, $arParams, $send = false, $site = null, $methodApi = 'ordersEdit')
    {
        if (!$api || empty($arParams)) { // add cond to check $arParams
            return false;
        }
        if (empty($arFields)) {
            RCrmActions::eventLog('RetailCrmOrder::orderSend', 'empty($arFields)', 'incorrect order');
            return false;
        }

        $dimensionsSetting = COption::GetOptionString(self::$MODULE_ID, self::$CRM_ORDER_DIMENSIONS, 'N');

        $order = array(
            'number'          => $arFields['NUMBER'],
            'externalId'      => $arFields['ID'],
            'createdAt'       => $arFields['DATE_INSERT'],
            'customer'        => array('externalId' => $arFields['USER_ID']),
            'orderType'       => isset($arParams['optionsOrderTypes'][$arFields['PERSON_TYPE_ID']]) ?
                                     $arParams['optionsOrderTypes'][$arFields['PERSON_TYPE_ID']] : '',
            'status'          => isset($arParams['optionsPayStatuses'][$arFields['STATUS_ID']]) ?
                                     $arParams['optionsPayStatuses'][$arFields['STATUS_ID']] : '',
            'customerComment' => $arFields['USER_DESCRIPTION'],
            'managerComment'  => $arFields['COMMENTS'],
            'delivery' => array(
                'cost' => $arFields['PRICE_DELIVERY']
            ),
        );
        if ($send && isset($_COOKIE['_rc']) && $_COOKIE['_rc'] != '') {
            $order['customer']['browserId'] = $_COOKIE['_rc'];
        }
        $order['contragent']['contragentType'] = $arParams['optionsContragentType'][$arFields['PERSON_TYPE_ID']];

        if ($methodApi == 'ordersEdit') {
            $order['discountManualAmount'] = 0;
            $order['discountManualPercent'] = 0;
        }

        //fields
        foreach ($arFields['PROPS']['properties'] as $prop) {
            if (!empty($arParams['optionsLegalDetails'])
                && $search = array_search($prop['CODE'], $arParams['optionsLegalDetails'][$arFields['PERSON_TYPE_ID']])
            ) {
                $order['contragent'][$search] = $prop['VALUE'][0];//legal order data
            } elseif (!empty($arParams['optionsCustomFields'])
                && $search = array_search($prop['CODE'], $arParams['optionsCustomFields'][$arFields['PERSON_TYPE_ID']])
            ) {
                $order['customFields'][$search] = $prop['VALUE'][0];//custom properties
            } elseif ($search = array_search($prop['CODE'], $arParams['optionsOrderProps'][$arFields['PERSON_TYPE_ID']])) {//other
                if (in_array($search, array('fio', 'phone', 'email'))) {//fio, phone, email
                    if ($search == 'fio') {
                        $order = array_merge($order, RCrmActions::explodeFIO($prop['VALUE'][0]));//add fio fields
                    } else {
                        $order[$search] = $prop['VALUE'][0];//phone, email
                    }
                } else {//address
                    if ($prop['TYPE'] == 'LOCATION' && isset($prop['VALUE'][0]) && $prop['VALUE'][0] != '') {
                        $arLoc = \Bitrix\Sale\Location\LocationTable::getByCode($prop['VALUE'][0])->fetch();
                        if ($arLoc) {
                            $server = \Bitrix\Main\Context::getCurrent()->getServer()->getDocumentRoot();
                            $countrys = array();
                            if (file_exists($server . '/bitrix/modules/intaro.retailcrm/classes/general/config/country.xml')) {
                                $countrysFile = simplexml_load_file($server . '/bitrix/modules/intaro.retailcrm/classes/general/config/country.xml');
                                foreach ($countrysFile->country as $country) {
                                    $countrys[RCrmActions::fromJSON((string) $country->name)] = (string) $country->alpha;
                                }
                            }
                            $location = \Bitrix\Sale\Location\Name\LocationTable::getList(array(
                                'filter' => array('=LOCATION_ID' => $arLoc['CITY_ID'], 'LANGUAGE_ID' => 'ru')
                            ))->fetch();
                            if (count($countrys) > 0) {
                                $countryOrder = \Bitrix\Sale\Location\Name\LocationTable::getList(array(
                                    'filter' => array('=LOCATION_ID' => $arLoc['COUNTRY_ID'], 'LANGUAGE_ID' => 'ru')
                                ))->fetch();
                                if(isset($countrys[$countryOrder['NAME']])){
                                    $order['countryIso'] = $countrys[$countryOrder['NAME']];
                                }
                            }
                        }
                        $prop['VALUE'][0] = $location['NAME'];
                    }

                    $order['delivery']['address'][$search] = $prop['VALUE'][0];
                }
            }
        }

        //deliverys
        if (array_key_exists($arFields['DELIVERYS'][0]['id'], $arParams['optionsDelivTypes'])) {
            $order['delivery']['code'] = $arParams['optionsDelivTypes'][$arFields['DELIVERYS'][0]['id']];
            if (isset($arFields['DELIVERYS'][0]['service']) && $arFields['DELIVERYS'][0]['service'] != '') {
                $order['delivery']['service']['code'] = $arFields['DELIVERYS'][0]['service'];
            }
        }

        $weight = 0;
        $width = 0;
        $height = 0;
        $length = 0;

        //basket
        foreach ($arFields['BASKET'] as $product) {
            $item = array(
                'quantity'        => $product['QUANTITY'],
                'offer'           => array('externalId' => $product['PRODUCT_ID'],
                                           'xmlId' => $product['PRODUCT_XML_ID']
                                        ),
                'productName'     => $product['NAME']
            );

            $pp = CCatalogProduct::GetByID($product['PRODUCT_ID']);
            if (is_null($pp['PURCHASING_PRICE']) == false) {
                $item['purchasePrice'] = $pp['PURCHASING_PRICE'];
            }

            $discount = (double) $product['DISCOUNT_PRICE'];

            if ($discount < 0) {
                $item['discountManualAmount'] = 0;
                $initialPrice = (double) $product['PRICE'];
            } else {
                $item['discountManualAmount'] = (double) $product['DISCOUNT_PRICE'];
                $initialPrice = (double) $product['PRICE'] + (double) $product['DISCOUNT_PRICE'];
            }

            $item['discountManualPercent'] = 0;
            $item['initialPrice'] = $initialPrice;

            $order['items'][] = $item;

            if ($send && $dimensionsSetting == 'Y') {
                $dimensions = RCrmActions::unserializeArrayRecursive($product['DIMENSIONS']);

                if ($dimensions !== false) {
                    $width += $dimensions['WIDTH'];
                    $height += $dimensions['HEIGHT'];
                    $length += $dimensions['LENGTH'];
                    $weight += $product['WEIGHT'];
                }
            }
        }

        if ($send && $dimensionsSetting == 'Y') {
            $order['width'] = $width;
            $order['height'] = $height;
            $order['length'] = $length;
            $order['weight'] = $weight;
        }

        //payments
        $payments = array();
        foreach ($arFields['PAYMENTS'] as $payment) {
            if (!empty($payment['PAY_SYSTEM_ID']) && isset($arParams['optionsPayTypes'][$payment['PAY_SYSTEM_ID']])) {
                $pm = array(
                    'type' => $arParams['optionsPayTypes'][$payment['PAY_SYSTEM_ID']],
                    'amount' => $payment['SUM']
                );
                if (!empty($payment['ID'])) {
                    $pm['externalId'] = $payment['ID'];
                }
                if (!empty($payment['DATE_PAID'])) {
                    $pm['paidAt'] = new \DateTime($payment['DATE_PAID']);
                }
                if (!empty($arParams['optionsPayment'][$payment['PAID']])) {
                    $pm['status'] = $arParams['optionsPayment'][$payment['PAID']];
                }
                $payments[] = $pm;
            } else {
                RCrmActions::eventLog(
                    'RetailCrmOrder::orderSend',
                    'payments',
                    'OrderID = ' . $arFields['ID'] . '. Payment not found.'
                );

                continue;
            }
        }
        if (count($payments) > 0) {
            $order['payments'] = $payments;
        }

        //send
        if (function_exists('retailCrmBeforeOrderSend')) {
            $newResOrder = retailCrmBeforeOrderSend($order, $arFields);
            if (is_array($newResOrder) && !empty($newResOrder)) {
                $order = $newResOrder;
            } elseif ($newResOrder === false) {
                RCrmActions::eventLog(
                    'RetailCrmOrder::orderSend',
                    'retailCrmBeforeOrderSend()',
                    'OrderID = ' . $arFields['ID'] . '. Sending canceled after retailCrmBeforeOrderSend'
                );

                return false;
            }
        }

        $normalizer = new RestNormalizer();
        $order = $normalizer->normalize($order, 'orders');

        $log = new Logger();
        $log->write($order, 'orderSend');

        if($send) {
            if (!RCrmActions::apiMethod($api, $methodApi, __METHOD__, $order, $site)) {
                return false;
            }
        }

        return $order;
    }

    /**
     * Mass order uploading, without repeating; always returns true, but writes error log
     * @param $pSize
     * @param $failed -- flag to export failed orders
     * @return boolean
     */
    public static function uploadOrders($pSize = 50, $failed = false, $orderList = false)
    {
        if (!CModule::IncludeModule("iblock")) {
            RCrmActions::eventLog('RetailCrmOrder::uploadOrders', 'iblock', 'module not found');
            return true;
        }
        if (!CModule::IncludeModule("sale")) {
            RCrmActions::eventLog('RetailCrmOrder::uploadOrders', 'sale', 'module not found');
            return true;
        }
        if (!CModule::IncludeModule("catalog")) {
            RCrmActions::eventLog('RetailCrmOrder::uploadOrders', 'catalog', 'module not found');
            return true;
        }

        $resOrders = array();
        $resCustomers = array();
        $orderIds = array();

        $lastUpOrderId = COption::GetOptionString(self::$MODULE_ID, self::$CRM_ORDER_LAST_ID, 0);
        $failedIds = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_ORDER_FAILED_IDS, 0));

        if ($failed == true && $failedIds !== false && count($failedIds) > 0) {
            $orderIds = $failedIds;
        } elseif ($orderList !== false && count($orderList) > 0) {
            $orderIds = $orderList;
        } else {
            $dbOrder = \Bitrix\Sale\Internals\OrderTable::GetList(array(
                'order'   => array("ID" => "ASC"),
                'filter'  => array('>ID' => $lastUpOrderId),
                'limit'   => $pSize,
                'select'  => array('ID')
            ));
            while ($arOrder = $dbOrder->fetch()) {
                $orderIds[] = $arOrder['ID'];
            }
        }

        if (count($orderIds) <= 0) {
            return false;
        }

        $api_host = COption::GetOptionString(self::$MODULE_ID, self::$CRM_API_HOST_OPTION, 0);
        $api_key = COption::GetOptionString(self::$MODULE_ID, self::$CRM_API_KEY_OPTION, 0);

        $optionsSitesList = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_SITES_LIST, 0));
        $optionsOrderTypes = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_ORDER_TYPES_ARR, 0));
        $optionsDelivTypes = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_DELIVERY_TYPES_ARR, 0));
        $optionsPayTypes = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_PAYMENT_TYPES, 0));
        $optionsPayStatuses = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_PAYMENT_STATUSES, 0)); // --statuses
        $optionsPayment = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_PAYMENT, 0));
        $optionsOrderProps = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_ORDER_PROPS, 0));
        $optionsLegalDetails = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_LEGAL_DETAILS, 0));
        $optionsContragentType = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_CONTRAGENT_TYPE, 0));
        $optionsCustomFields = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_CUSTOM_FIELDS, 0));

        $api = new RetailCrm\ApiClient($api_host, $api_key);

        $arParams = array(
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
        );

        $recOrders = array();
        foreach ($orderIds as $orderId) {
            $id = \Bitrix\Sale\Order::load($orderId);
            if (!$id) {
                continue;
            }
            $order = self::orderObjToArr($id);
            $user = Bitrix\Main\UserTable::getById($order['USER_ID'])->fetch();

            $arCustomers = RetailCrmUser::customerSend($user, $api, $optionsContragentType[$order['PERSON_TYPE_ID']], false, $site);
            $arOrders = self::orderSend($order, $api, $arParams, false, $site);

            if (!$arCustomers || !$arOrders) {
                continue;
            }

            $resCustomers[$order['LID']][] = $arCustomers;
            $resOrders[$order['LID']][] = $arOrders;

            $recOrders[] = $orderId;
        }

        if (count($resOrders) > 0) {
            foreach ($resCustomers as $key => $customerLoad) {
                if ($optionsSitesList) {
                    if (array_key_exists($key, $optionsSitesList) && $optionsSitesList[$key] != null) {
                        $site = $optionsSitesList[$key];
                    } else {
                        continue;
                    }
                } elseif (!$optionsSitesList) {
                    $site = null;
                }
                if (RCrmActions::apiMethod($api, 'customersUpload', __METHOD__, $customerLoad, $site) === false) {
                    return false;
                }
                if (count($optionsSitesList) > 1) {
                    time_nanosleep(0, 250000000);
                }
            }
            foreach ($resOrders as $key => $orderLoad) {
                if ($optionsSitesList) {
                    if (array_key_exists($key, $optionsSitesList) && $optionsSitesList[$key] != null) {
                        $site = $optionsSitesList[$key];
                    } else {
                        continue;
                    }
                } elseif (!$optionsSitesList) {
                    $site = null;
                }
                if (RCrmActions::apiMethod($api, 'ordersUpload', __METHOD__, $orderLoad, $site) === false) {
                    return false;
                }
                if (count($optionsSitesList) > 1) {
                    time_nanosleep(0, 250000000);
                }
            }
            if ($failed == true && $failedIds !== false && count($failedIds) > 0) {
                COption::SetOptionString(self::$MODULE_ID, self::$CRM_ORDER_FAILED_IDS, serialize(array_diff($failedIds, $recOrders)));
            } elseif ($lastUpOrderId < max($recOrders) && $orderList === false) {
                COption::SetOptionString(self::$MODULE_ID, self::$CRM_ORDER_LAST_ID, max($recOrders));
            }
        }

        return true;
    }

    public static function orderObjToArr($obOrder)
    {
        $culture = new \Bitrix\Main\Context\Culture(array("FORMAT_DATETIME" => "Y-m-d HH:i:s"));
        $arOrder = array(
            'ID'               => $obOrder->getId(),
            'NUMBER'           => $obOrder->getField('ACCOUNT_NUMBER'),
            'LID'              => $obOrder->getSiteId(),
            'DATE_INSERT'      => $obOrder->getDateInsert()->toString($culture),
            'STATUS_ID'        => $obOrder->getField('STATUS_ID'),
            'USER_ID'          => $obOrder->getUserId(),
            'PERSON_TYPE_ID'   => $obOrder->getPersonTypeId(),
            'CURRENCY'         => $obOrder->getCurrency(),
            'PAYMENTS'         => array(),
            'DELIVERYS'        => array(),
            'PRICE_DELIVERY'   => $obOrder->getDeliveryPrice(),
            'PROPS'            => $obOrder->getPropertyCollection()->getArray(),
            'DISCOUNTS'        => $obOrder->getDiscount()->getApplyResult(),
            'BASKET'           => array(),
            'USER_DESCRIPTION' => $obOrder->getField('USER_DESCRIPTION'),
            'COMMENTS'         => $obOrder->getField('COMMENTS'),
            'REASON_CANCELED'  => $obOrder->getField('REASON_CANCELED'),
        );

        $shipmentList = $obOrder->getShipmentCollection();
        foreach ($shipmentList as $shipmentData) {
            if ($shipmentData->isSystem()) {
                continue;
            }

            if ($shipmentData->getDeliveryId()) {
                $delivery = \Bitrix\Sale\Delivery\Services\Manager::getById($shipmentData->getDeliveryId());
                $siteDeliverys = RCrmActions::DeliveryList();
                foreach ($siteDeliverys as $siteDelivery) {
                    if ($siteDelivery['ID'] == $delivery['ID'] && $siteDelivery['PARENT_ID'] == 0) {
                        unset($delivery['PARENT_ID']);
                    }
                }
                if ($delivery['PARENT_ID']) {
                    $servise = explode(':', $delivery['CODE']);
                    $shipment = array('id' => $delivery['PARENT_ID'], 'service' => $servise[1]);
                } else {
                    $shipment = array('id' => $delivery['ID']);
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
}
