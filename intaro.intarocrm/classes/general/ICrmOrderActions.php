<?php
if (file_exists($_SERVER["DOCUMENT_ROOT"]."/bitrix/php_interface/retailcrm/ICrmOrderActions.php")){
    require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/php_interface/retailcrm/ICrmOrderActions.php");
}
else{
    IncludeModuleLangFile(__FILE__);
    class ICrmOrderActions
    {
        protected static $MODULE_ID = 'intaro.intarocrm';
        protected static $CRM_API_HOST_OPTION = 'api_host';
        protected static $CRM_API_KEY_OPTION = 'api_key';
        protected static $CRM_ORDER_TYPES_ARR = 'order_types_arr';
        protected static $CRM_DELIVERY_TYPES_ARR = 'deliv_types_arr';
        protected static $CRM_PAYMENT_TYPES = 'pay_types_arr';
        protected static $CRM_PAYMENT_STATUSES = 'pay_statuses_arr';
        protected static $CRM_PAYMENT = 'payment_arr'; //order payment Y/N
        protected static $CRM_ORDER_LAST_ID = 'order_last_id';
        protected static $CRM_SITES_LIST = 'sites_list';
        protected static $CRM_ORDER_PROPS = 'order_props';
        protected static $CRM_LEGAL_DETAILS = 'legal_details';
        protected static $CRM_CUSTOM_FIELDS = 'custom_fields';
        protected static $CRM_CONTRAGENT_TYPE = 'contragent_type';
        protected static $CRM_ORDER_FAILED_IDS = 'order_failed_ids';
        protected static $CRM_ORDER_HISTORY_DATE = 'order_history_date';
        protected static $CRM_CATALOG_BASE_PRICE = 'catalog_base_price';
        
        const CANCEL_PROPERTY_CODE = 'INTAROCRM_IS_CANCELED';

        /**
         * Mass order uploading, without repeating; always returns true, but writes error log
         * @param $pSize
         * @param $failed -- flag to export failed orders
         * @return boolean
         */
        public static function uploadOrders($pSize = 50, $failed = false, $orderList = false) {

            if (!CModule::IncludeModule("iblock")) {
                self::eventLog('ICrmOrderActions::uploadOrders', 'iblock', 'module not found');
                return true;
            }
            if (!CModule::IncludeModule("sale")) {
                self::eventLog('ICrmOrderActions::uploadOrders', 'sale', 'module not found');
                return true;
            }
            if (!CModule::IncludeModule("catalog")) {
                self::eventLog('ICrmOrderActions::uploadOrders', 'catalog', 'module not found');
                return true;
            }

            $resOrders = array();
            $resCustomers = array();

            $lastUpOrderId = COption::GetOptionString(self::$MODULE_ID, self::$CRM_ORDER_LAST_ID, 0);
            $failedIds = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_ORDER_FAILED_IDS, 0));

            $arFilter = array();
            $arCount = false;
            if ($failed == true && $failedIds !== false && count($failedIds) > 0) {
                $arFilter['ID'] = $failedIds;
            } elseif ($orderList !== false && count($orderList) > 0) {
                $arFilter['ID'] = $orderList;
            } else {
                $arFilter['>ID'] = $lastUpOrderId;
                $arCount['nTopCount'] = $pSize;
            }

            if ( (isset($arFilter['ID']) && count($arFilter['ID']) > 0) || isset($arFilter['>ID']) ) {
                $dbOrder = CSaleOrder::GetList(array("ID" => "ASC"), $arFilter, false, $arCount);
                if ($dbOrder->SelectedRowsCount() <= 0) {
                    return false;
                }
            } else {
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

            $api = new RetailCrm\RestApi($api_host, $api_key);

            $arParams = array(
                'optionsOrderTypes'     => $optionsOrderTypes,
                'optionsDelivTypes'     => $optionsDelivTypes,
                'optionsPayTypes'       => $optionsPayTypes,
                'optionsPayStatuses'    => $optionsPayStatuses,
                'optionsPayment'        => $optionsPayment,
                'optionsOrderProps'     => $optionsOrderProps,
                'optionsLegalDetails'   => $optionsLegalDetails,
                'optionsContragentType' => $optionsContragentType,
                'optionsSitesList'      => $optionsSitesList ,
                'optionsCustomFields'   => $optionsCustomFields,
            );

            $recOrders = array();
            while ($arOrder = $dbOrder->GetNext()) {
                $result = self::orderCreate($arOrder, $api, $arParams);
                if (!$result['order'] || !$result['customer']){
                    continue;
                }

                $resOrders[$arOrder['LID']][] = $result['order'];
                $resCustomers[$arOrder['LID']][] = $result['customer'];

                $recOrders[] = $arOrder['ID'];
            }
            if(count($resOrders) > 0){
                foreach($resCustomers as $key => $customerLoad){
                    $site = count($optionsSitesList) > 1 ? $optionsSitesList[$key] : null;
                    if (self::apiMethod($api, 'customerUpload', __METHOD__, $customerLoad, $site) === false) {
                        return false;
                    }
                    if (count($optionsSitesList) > 1) {
                        time_nanosleep(0, 250000000);
                    }
                }
                foreach($resOrders as $key => $orderLoad){
                    $site = count($optionsSitesList) > 1 ? $optionsSitesList[$key] : null;
                    if (self::apiMethod($api, 'orderUpload', __METHOD__, $orderLoad, $site) === false) {
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

        /**
         *
         * Creates order or returns array of order and customer for mass upload
         *
         * @param array $arFields
         * @param $api
         * @param $arParams
         * @param $send
         * @return boolean
         * @return array - array('order' = $order, 'customer' => $customer)
         */
        public static function orderCreate($arFields, $api, $arParams, $send = false, $site = null) {
            if(!$api || empty($arParams)) { // add cond to check $arParams
                return false;
            }
            if (empty($arFields)) {
                self::eventLog('ICrmOrderActions::orderCreate', 'empty($arFields)', 'incorrect order');
                return false;
            }

            if (isset($arParams['optionsOrderProps'][$arFields['PERSON_TYPE_ID']]['city']) == false) {
                $rsOrderProps = CSaleOrderPropsValue::GetList(array(), array('ORDER_ID' => $arFields['ID'], 'CODE' => 'LOCATION'));
                $arParams['optionsOrderProps'][$arFields['PERSON_TYPE_ID']]['city'] = $rsOrderProps->SelectedRowsCount() < 1 ? 'CITY' : 'LOCATION';
            }

            $normalizer = new RestNormalizer();
            $normalizer->setValidation($_SERVER["DOCUMENT_ROOT"].'/bitrix/modules/intaro.intarocrm/classes/general/config/retailcrm.json');

            $customer = array();

            if ($arFields['CANCELED'] == 'Y') {
                $arFields['STATUS_ID'] = $arFields['CANCELED'].$arFields['CANCELED'];
            }

            $order = array(
                'number'          => $arFields['ACCOUNT_NUMBER'],
                'externalId'      => $arFields['ID'],
                'createdAt'       => new \DateTime($arFields['DATE_INSERT']),
                'customerId'      => $arFields['USER_ID'],
                'discount'        => $arFields['DISCOUNT_VALUE'],
                'markDateTime'    => $arFields['DATE_MARKED'],
                'paymentType'     => isset($arParams['optionsPayTypes'][$arFields['PAY_SYSTEM_ID']]) ?
                                         $arParams['optionsPayTypes'][$arFields['PAY_SYSTEM_ID']] : '',
                'paymentStatus'   => isset($arParams['optionsPayment'][$arFields['PAYED']]) ?
                                         $arParams['optionsPayment'][$arFields['PAYED']] : '',
                'orderType'       => isset($arParams['optionsOrderTypes'][$arFields['PERSON_TYPE_ID']]) ?
                                         $arParams['optionsOrderTypes'][$arFields['PERSON_TYPE_ID']] : '',
                'contragentType'  => isset($arParams['optionsContragentType'][$arFields['PERSON_TYPE_ID']]) ?
                                         $arParams['optionsContragentType'][$arFields['PERSON_TYPE_ID']] : '',
                'status'          => isset($arParams['optionsPayStatuses'][$arFields['STATUS_ID']]) ?
                                         $arParams['optionsPayStatuses'][$arFields['STATUS_ID']] : '',
                'statusComment'   => $arFields['REASON_CANCELED'],
                'customerComment' => $arFields['USER_DESCRIPTION'],
                'managerComment'  => $arFields['COMMENTS'],
                'delivery' => array(
                    'cost' => $arFields['PRICE_DELIVERY']
                ),
            );

            $rsOrderProps = CSaleOrderPropsValue::GetList(array(), array('ORDER_ID' => $arFields['ID']));
            while ($ar = $rsOrderProps->Fetch()) {
                if ($search = array_search($ar['CODE'], $arParams['optionsLegalDetails'][$arFields['PERSON_TYPE_ID']])) {
                    $order[$search] = $ar['VALUE'];
                    $customer[$search] = $ar['VALUE'];
                } elseif ($search = array_search($ar['CODE'], $arParams['optionsCustomFields'][$arFields['PERSON_TYPE_ID']])) {
                    $order['customFields'][$search] = $ar['VALUE'];
                } elseif ($search = array_search($ar['CODE'], $arParams['optionsOrderProps'][$arFields['PERSON_TYPE_ID']])) {
                    if (in_array($search, array('fio', 'phone', 'email'))) {
                        if ($search == 'fio') {
                            $order = array_merge($order, self::explodeFIO($ar['VALUE']));
                        } else {
                            $order[$search] = $ar['VALUE'];
                        }
                    } else {
                        $prop = CSaleOrderProps::GetByID($ar['ORDER_PROPS_ID']);
                        if ($prop['TYPE'] == 'LOCATION') {
                            $ar['VALUE'] = CSaleLocation::GetByID(
                                    method_exists('CSaleLocation', 'getLocationIDbyCODE') ?
                                    CSaleLocation::getLocationIDbyCODE($ar['VALUE']) : $ar['VALUE']
                            );
                            $ar['VALUE'] = $ar['VALUE']['CITY_NAME_LANG'];
                        }

                        $order['delivery']['address'][$search] = $ar['VALUE'];
                    }
                }
            }
            if (strpos($arFields['DELIVERY_ID'], ":") !== false){
                $arFields["DELIVERY_ID"] = explode(":", $arFields["DELIVERY_ID"], 2);
                if ($arDeliveryType = CSaleDeliveryHandler::GetBySID(reset($arFields["DELIVERY_ID"]))->GetNext()) {
                    if (array_key_exists(end($arFields["DELIVERY_ID"]), $arDeliveryType['PROFILES'])) {
                        $arFields["DELIVERY_SERVICE"] = array(
                            'code' => implode('-', $arFields["DELIVERY_ID"]),
                            'name' => $arDeliveryType['PROFILES'][end($arFields["DELIVERY_ID"])]['TITLE']
                        );
                    }
                }
                $arFields["DELIVERY_ID"] = reset($arFields["DELIVERY_ID"]);
            }

            if (array_key_exists($arFields['DELIVERY_ID'], $arParams['optionsDelivTypes'])) {
                $order['delivery']['code'] = $arParams['optionsDelivTypes'][$arFields["DELIVERY_ID"]];
                if (isset($arFields["DELIVERY_SERVICE"])) {
                    $order['delivery']['service'] = $arFields["DELIVERY_SERVICE"];
                }
            }

            $rsOrderBasket = CSaleBasket::GetList(array('ID' => 'ASC'), array('ORDER_ID' => $arFields['ID']));
            while ($p = $rsOrderBasket->Fetch()) {
                $item = array(
                    'quantity'        => $p['QUANTITY'],
                    'productId'       => $p['PRODUCT_ID'],
                    'xmlId'           => $p['PRODUCT_XML_ID'],
                    'productName'     => $p['NAME'],
                    'comment'         => $p['NOTES'],
                    'createdAt'       => new \DateTime($p['DATE_INSERT'])
                );

                $pp = CCatalogProduct::GetByID($p['PRODUCT_ID']);
                if (is_null($pp['PURCHASING_PRICE']) == false) {
                    $item['purchasePrice'] = $pp['PURCHASING_PRICE'];
                }

                $propCancel = CSaleBasket::GetPropsList(array(), array('BASKET_ID' => $p['ID'], 'CODE' => self::CANCEL_PROPERTY_CODE))->Fetch();
                if (!$propCancel || ($propCancel && !(int)$propCancel['VALUE'])) {
                    $item['discount'] = (double) $p['DISCOUNT_PRICE'];
                    $item['initialPrice'] = (double) $p['PRICE'] + (double) $p['DISCOUNT_PRICE'];
                }

                $order['items'][] = $item;
            }

            $arUser = CUser::GetByID($arFields['USER_ID'])->Fetch();

            $customer = array(
                'externalId'     => $arFields['USER_ID'],
                'lastName'       => $arUser['LAST_NAME'],
                'firstName'      => $arUser['NAME'],
                'patronymic'     => $arUser['SECOND_NAME'],
                'phones'         => array(
                    array('number' => $arUser['PERSONAL_PHONE']),
                    array('number' => $arUser['WORK_PHONE'])
                ),
                'createdAt'      => new \DateTime($arUser['DATE_REGISTER']),
                'contragentType' => $arParams['optionsContragentType'][$arFields['PERSON_TYPE_ID']]
            );

            if(function_exists('intarocrm_get_order_type')) {
                $orderType = intarocrm_get_order_type($arFields);
                if ($orderType) {
                    $order['orderType'] = $orderType;
                }
            }
            if (function_exists('intarocrm_before_order_send')) {
                $newResOrder = intarocrm_before_order_send($order);
                if (is_array($newResOrder) && !empty($newResOrder)) {
                    $order = $newResOrder;
                }
            }

            $customer = $normalizer->normalize($customer, 'customers');
            $order = $normalizer->normalize($order, 'orders');

            if (isset($arParams['optionsSitesList']) && is_array($arParams['optionsSitesList']) &&
                    array_key_exists($arFields['LID'], $arParams['optionsSitesList'])) {
                $site = $arParams['optionsSitesList'][$arFields['LID']];
            }
            
            $log = new Logger();
            $log->write($customer, 'customer');
            $log->write($order, 'order');
            
            if($send) {
                if (!self::apiMethod($api, 'customerEdit', __METHOD__, $customer, $site)) {
                    return false;
                }
                if ($orderEdit = self::apiMethod($api, 'orderEdit', __METHOD__, $order, $site)) {
                    return $orderEdit;
                } else {
                    return false;
                }
            }

            return array(
                'order'    => $order,
                'customer' => $customer
            );
        }

        /**
         *
         * History update, cron usage only
         * @global CUser $USER
         * @return boolean
         */
        public static function orderHistory() {
            global $USER;
            if (is_object($USER) == false) {
                $USER = new RetailUser;
            }

            if (!CModule::IncludeModule("iblock")) {
                self::eventLog('ICrmOrderActions::orderHistory', 'iblock', 'module not found');
                return false;
            }
            if (!CModule::IncludeModule("sale")) {
                self::eventLog('ICrmOrderActions::orderHistory', 'sale', 'module not found');
                return false;
            }
            if (!CModule::IncludeModule("catalog")) {
                self::eventLog('ICrmOrderActions::orderHistory', 'catalog', 'module not found');
                return false;
            }

            $api_host = COption::GetOptionString(self::$MODULE_ID, self::$CRM_API_HOST_OPTION, 0);
            $api_key = COption::GetOptionString(self::$MODULE_ID, self::$CRM_API_KEY_OPTION, 0);

            $optionsOrderTypes = array_flip(unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_ORDER_TYPES_ARR, 0)));
            $optionsDelivTypes = array_flip(unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_DELIVERY_TYPES_ARR, 0)));
            $optionsPayTypes = array_flip(unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_PAYMENT_TYPES, 0)));
            $optionsPayStatuses = array_flip(unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_PAYMENT_STATUSES, 0))); // --statuses
            $optionsPayment = array_flip(unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_PAYMENT, 0)));
            $optionsOrderProps = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_ORDER_PROPS, 0));
            $optionsLegalDetails = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_LEGAL_DETAILS, 0));
            $optionsContragentType = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_CONTRAGENT_TYPE, 0));
            $optionsSitesList = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_SITES_LIST, 0));
            $optionsCustomFields = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_CUSTOM_FIELDS, 0));

            foreach ($optionsOrderProps as $code => $value) {
                if (isset($optionsLegalDetails[$code])) {
                    $optionsOrderProps[$code] = array_merge($optionsOrderProps[$code], $optionsLegalDetails[$code]);
                }
                if (isset($optionsCustomFields[$code])) {
                    $optionsOrderProps[$code] = array_merge($optionsOrderProps[$code], $optionsCustomFields[$code]);
                }
                $optionsOrderProps[$code]['location'] = 'LOCATION';
                if (array_search('CITY', $optionsOrderProps[$code]) == false) {
                    $optionsOrderProps[$code]['city'] = 'CITY';
                }
                if (array_search('ZIP', $optionsOrderProps[$code]) == false) {
                    $optionsOrderProps[$code]['index'] = 'ZIP';
                }
            }

            $api = new RetailCrm\RestApi($api_host, $api_key);

            $dateStart = COption::GetOptionString(self::$MODULE_ID, self::$CRM_ORDER_HISTORY_DATE, null);

            if (is_null($dateStart)) {
                $dateStart = new \DateTime();
                $dateStart = $dateStart->format('Y-m-d H:i:s');
            }

            try {
                $orderHistory = $api->orderHistory($dateStart);
            } catch (\RetailCrm\Exception\CurlException $e) {
                self::eventLog(
                    'ICrmOrderActions::orderHistory', 'RetailCrm\RestApi::orderHistory::CurlException',
                    $e->getCode() . ': ' . $e->getMessage()
                );

                return false;
            }

            $orderHistory = isset($orderHistory['orders']) ? $orderHistory['orders'] : array();
            
            $log = new Logger();
            $log->write($orderHistory, 'history');
            
            $dateFinish = $api->getGeneratedAt();
            if (is_null($dateFinish) || $dateFinish == false) {
                $dateFinish = new \DateTime();
            }

            $defaultOrderType = 1;
            $dbOrderTypesList = CSalePersonType::GetList(array(), array("ACTIVE" => "Y"));
            if ($arOrderTypesList = $dbOrderTypesList->Fetch()) {
                $defaultOrderType = $arOrderTypesList['ID'];
            }

            $GLOBALS['INTARO_CRM_FROM_HISTORY'] = true;

            foreach ($orderHistory as $order) {
                if (function_exists('intarocrm_order_pre_persist')) {
                    $order = intarocrm_order_pre_persist($order);
                }

                if (!isset($order['externalId'])) {

                    // custom orderType function
                    if (function_exists('intarocrm_set_order_type')) {
                        $orderType = intarocrm_set_order_type($order);
                        if ($orderType) {
                            $optionsOrderTypes[$order['orderType']] = $orderType;
                        } else {
                            $optionsOrderTypes[$order['orderType']] = $defaultOrderType;
                        }
                    }

                    // we dont need new orders without any customers (can check only for externalId)
                    if (!isset($order['customer']['externalId'])) {
                        if (!isset($order['customer']['id'])) {
                            continue;
                        }

                        $registerNewUser = true;

                        if (!isset($order['customer']['email']) || $order['customer']['email'] == '') {
                            $login = $order['customer']['email'] = uniqid('user_' . time()) . '@crm.com';
                        } else {
                            $dbUser = CUser::GetList(($by = 'ID'), ($sort = 'ASC'), array('=EMAIL' => $order['email']));
                            switch ($dbUser->SelectedRowsCount()) {
                                case 0:
                                    $login = $order['customer']['email'];
                                    break;
                                case 1:
                                    $arUser = $dbUser->Fetch();
                                    $registeredUserID = $arUser['ID'];
                                    $registerNewUser = false;
                                    break;
                                default:
                                    $login = uniqid('user_' . time()) . '@crm.com';
                                    break;
                            }
                        }

                        if ($registerNewUser === true) {
                            $userPassword = uniqid();

                            $newUser = new CUser;
                            $arFields = array(
                                "NAME"              => self::fromJSON($order['customer']['firstName']),
                                "LAST_NAME"         => self::fromJSON($order['customer']['lastName']),
                                "EMAIL"             => $order['customer']['email'],
                                "LOGIN"             => $login,
                                "LID"               => "ru",
                                "ACTIVE"            => "Y",
                                "PASSWORD"          => $userPassword,
                                "CONFIRM_PASSWORD"  => $userPassword
                            );
                            $registeredUserID = $newUser->Add($arFields);
                            if ($registeredUserID === false) {
                                self::eventLog('ICrmOrderActions::orderHistory', 'CUser::Register', 'Error register user');
                                continue;
                            }

                            try {
                                $api->customerFixExternalIds(array(array('id' => $order['customer']['id'], 'externalId' => $registeredUserID)));
                            } catch (\RetailCrm\Exception\CurlException $e) {
                                self::eventLog(
                                    'ICrmOrderActions::orderHistory', 'RetailCrm\RestApi::customerFixExternalIds::CurlException',
                                    $e->getCode() . ': ' . $e->getMessage()
                                );

                                continue;
                            }
                        }

                        $order['customer']['externalId'] = $registeredUserID;
                    }

                    // new order
                   $newOrderFields = array(
                        'LID'              => CSite::GetDefSite(),
                        'PERSON_TYPE_ID'   => isset($optionsOrderTypes[$order['orderType']]) ? $optionsOrderTypes[$order['orderType']] : $defaultOrderType,
                        'PAYED'            => 'N',
                        'CANCELED'         => 'N',
                        'STATUS_ID'        => 'N',
                        'PRICE'            => 0,
                        'CURRENCY'         => CCurrency::GetBaseCurrency(),
                        'USER_ID'          => $order['customer']['externalId'],
                        'PAY_SYSTEM_ID'    => 0,
                        'PRICE_DELIVERY'   => 0,
                        'DELIVERY_ID'      => 0,
                        'DISCOUNT_VALUE'   => 0,
                        'USER_DESCRIPTION' => ''
                    );

                    if(count($optionsSitesList) > 1 && $lid = array_search($order['site'], $optionsSitesList)){
                        $newOrderFields['LID'] = $lid;
                    }

                    $externalId = CSaleOrder::Add($newOrderFields);

                    if (!isset($order['externalId'])) {
                        try {
                            $api->orderFixExternalIds(array(array('id' => $order['id'], 'externalId' => $externalId)));
                        } catch (\RetailCrm\Exception\CurlException $e) {
                            self::eventLog(
                                'ICrmOrderActions::orderHistory', 'RetailCrm\RestApi::orderFixExternalIds::CurlException',
                                $e->getCode() . ': ' . $e->getMessage()
                            );

                            continue;
                        }
                    }
                    $order['externalId'] = $externalId;
                }

                if (isset($order['externalId']) && $order['externalId']) {

                    // custom orderType function
                    if (function_exists('intarocrm_set_order_type')) {
                        $orderType = intarocrm_set_order_type($order);
                        if ($orderType) {
                            $optionsOrderTypes[$order['orderType']] = $orderType;
                        } else {
                            $optionsOrderTypes[$order['orderType']] = $defaultOrderType;
                        }
                    }

                    $arFields = CSaleOrder::GetById($order['externalId']);

                    // incorrect order
                    if ($arFields === false || empty($arFields)) {
                        continue;
                    }

                    $LID = $arFields['LID'];
                    $userId = $arFields['USER_ID'];

                    if(isset($order['customer']['externalId']) && !is_null($order['customer']['externalId'])) {
                        $userId = $order['customer']['externalId'];
                    }

                    $rsOrderProps = CSaleOrderPropsValue::GetList(array(), array('ORDER_ID' => $arFields['ID']));
                    $arUpdateProps = array();
                    while ($ar = $rsOrderProps->Fetch()) {
                        $prop = CSaleOrderProps::GetByID($ar['ORDER_PROPS_ID']);
                        $arUpdateProps[ $ar['CODE'] ] = array('ID' => $ar['ID'], 'TYPE' => $prop['TYPE'], 'VALUE' => $ar['VALUE']);
                    }

                    $order['fio'] = trim(
                        implode(
                            ' ',
                            array(
                                isset($order['lastName']) ? $order['lastName'] : '',
                                isset($order['firstName']) ? $order['firstName'] : '',
                                isset($order['patronymic']) ? $order['patronymic'] : '',
                            )
                        )
                    );

                    if (isset($order['delivery']['address']['city'])) {
                        $order['location'] = $order['delivery']['address']['city'];
                    }

                    if (isset($order['orderType']) && isset($optionsOrderTypes[ $order['orderType'] ])) {
                        if (isset($optionsOrderProps[$arFields['PERSON_TYPE_ID']])) {
                            foreach ($optionsOrderProps[$arFields['PERSON_TYPE_ID']] as $code => $value) {
                                if (in_array($code, array_keys($order)) === false && isset($optionsOrderProps[$optionsOrderTypes[$order['orderType']]][$code])) {
                                    $order[ $code ] = $arUpdateProps[$optionsOrderProps[$arFields['PERSON_TYPE_ID']][$code]]['VALUE'];
                                }
                            }
                        }

                        //update ordertype
                        CSaleOrder::Update($order['externalId'], array('PERSON_TYPE_ID' => $optionsOrderTypes[ $order['orderType'] ]));

                        $arProp = CSaleOrderProps::GetList(array(), array('PERSON_TYPE_ID' => $optionsOrderTypes[ $order['orderType'] ]));
                        $typeParam = array();
                        while ($ar = $arProp->Fetch()) {
                            $typeParam[ $ar['CODE'] ] = $ar['CODE'];
                        }
                        foreach (array_diff_key($arUpdateProps, $typeParam) as $code => $param) {
                            if (isset($arUpdateProps[$code])) {
                                CSaleOrderPropsValue::Delete($param['ID']);
                            }
                        }
                        $arFields['PERSON_TYPE_ID'] = $optionsOrderTypes[ $order['orderType'] ];
                    }

                    array_walk_recursive(
                        self::clearArr($order),
                        'self::recursiveUpdate',
                        array(
                            'update'  => $arUpdateProps,
                            'type'    => $arFields['PERSON_TYPE_ID'],
                            'options' => $optionsOrderProps,
                            'orderId' => $order['externalId']
                        )
                    );

                    foreach($order['items'] as $item) {
                        if(isset($item['deleted']) && $item['deleted']) {
                            if ($p = CSaleBasket::GetList(array(), array('ORDER_ID' => $order['externalId'], 'PRODUCT_ID' => $item['id']))->Fetch()) {
                                if(!CSaleBasket::Delete($p['ID'])){
                                    self::eventLog('ICrmOrderActions::orderHistory', 'CSaleBasket::Delete', 'Error element delete');
                                }
                            }
                            else{
                                $prp = CSaleBasket::GetPropsList(array(), array("ORDER_ID" => $order['externalId'], "CODE" => 'ID', "VALUE" => $item['id']))->Fetch();
                                if(!CSaleBasket::Delete($prp['BASKET_ID'])){
                                    self::eventLog('ICrmOrderActions::orderHistory', 'CSaleBasket::Delete', 'Error castom element delete');
                                }
                            }
                            
                            continue;
                        }
                        
                        if (isset($item['offer']) === false && isset($item['offer']['externalId']) === false) {
                            continue;
                        }
                        
                        $res = CIBlockElement::GetByID($item['offer']['externalId'])->Fetch();    

                        if($res){
                            $p = CSaleBasket::GetList(array(),array('ORDER_ID' => $order['externalId'], 'PRODUCT_ID' => $item['offer']['externalId']))->Fetch();

                            if ($p == false) {
                                $p = CIBlockElement::GetByID($item['offer']['externalId'])->GetNext();
                                $iblock = CIBlock::GetByID($p['IBLOCK_ID'])->Fetch();
                                $p['CATALOG_XML_ID'] = $iblock['XML_ID'];
                                $p['PRODUCT_XML_ID'] = $p['XML_ID'];
                                unset($p['XML_ID']); 
                            } elseif ($propResult = CSaleBasket::GetPropsList(array(''),array('BASKET_ID' => $p['ID']))) {
                                while ($r = $propResult->Fetch()) {
                                    unset($r['ID']);
                                    unset($r['BASKET_ID']);
                                    $p['PROPS'][] = $r;
                                }
                            }
                            
                            $arProduct = array();

                            if (isset($item['created']) && $item['created'] == true) {
                                $productPrice = GetCatalogProductPrice($item['offer']['externalId'], COption::GetOptionString(self::$MODULE_ID, self::$CRM_CATALOG_BASE_PRICE, 0));
                                $arProduct = array(
                                    'FUSER_ID'               => $userId,
                                    'ORDER_ID'               => $order['externalId'],
                                    'QUANTITY'               => $item['quantity'],
                                    'CURRENCY'               => $productPrice['CURRENCY'],
                                    'LID'                    => $LID,
                                    'PRODUCT_ID'             => $item['offer']['externalId'],
                                    'PRODUCT_PRICE_ID'       => $p['PRODUCT_PRICE_ID'],
                                    'WEIGHT'                 => $p['WEIGHT'],
                                    'DELAY'                  => $p['DELAY'],
                                    'CAN_BUY'                => $p['CAN_BUY'],
                                    'MODULE'                 => $p['MODULE'],
                                    'NOTES'                  => $item['comment'] ?: $p['NOTES'],
                                    'PRODUCT_PROVIDER_CLASS' => $p['PRODUCT_PROVIDER_CLASS'],
                                    'DETAIL_PAGE_URL'        => $p['DETAIL_PAGE_URL'],
                                    'CATALOG_XML_ID'         => $p['CATALOG_XML_ID'],
                                    'PRODUCT_XML_ID'         => $p['PRODUCT_XML_ID'],
                                    'CUSTOM_PRICE'           => 'Y'
                                );
                            }
                            
                            if (isset($item['isCanceled']) == false) {
                                if (isset($item['initialPrice']) && $item['initialPrice']) {
                                    $arProduct['PRICE'] = (double) $item['initialPrice'];
                                }
                                if (isset($item['discount'])) {
                                    $arProduct['DISCOUNT_PRICE'] = $item['discount'];
                                }
                                if (isset($item['discountPercent'])) {
                                    $arProduct['DISCOUNT_VALUE'] = $item['discountPercent'];
                                    $newPrice = round($arProduct['PRICE'] / 100 * (100 - $arProduct['DISCOUNT_VALUE']), 2);
                                    $arProduct['DISCOUNT_PRICE'] = $arProduct['DISCOUNT_PRICE'] + $arProduct['PRICE'] - $newPrice;
                                }
                                if(isset($item['discount']) || isset($item['discountPercent'])) {
                                    $arProduct['PRICE'] -= $arProduct['DISCOUNT_PRICE'];
                                }
                                if (isset($item['offer']['name']) && $item['offer']['name']) {
                                    $arProduct['NAME'] = self::fromJSON($item['offer']['name']);
                                }
                                $arProduct = self::updateCancelProp($arProduct, 0);
                            } elseif (isset($item['isCanceled'])) {
                                $arProduct['PRICE'] = 0;
                                $arProduct = self::updateCancelProp($arProduct, 1);
                            }

                            if (isset($item['created']) && $item['created'] == true) {
                                if(!Add2BasketByProductID($item['offer']['externalId'], $item['quantity'], $arProduct, $p['PROPS'])){
                                    self::eventLog('ICrmOrderActions::orderHistory', 'Add2BasketByProductID', 'Error element add');
                                }
                                
                                continue;
                            }

                            if (count($p['PROPS']) > 0) {
                                $arProduct['PROPS'] = $p['PROPS'];
                            }
                            if (isset($item['quantity']) && $item['quantity']) {
                                $arProduct['QUANTITY'] = $item['quantity'];
                            }
                            if (isset($item['offer']['name']) && $item['offer']['name']) {
                                $arProduct['NAME'] = self::fromJSON($item['offer']['name']);
                            }

                            if(!CSaleBasket::Update($p['ID'], $arProduct)){
                                self::eventLog('ICrmOrderActions::orderHistory', 'CSaleBasket::Update', 'Error element update');
                            }
                            CSaleBasket::DeleteAll($userId);
                        }
                        else{
                            $arProduct = array();
                            
                            if (isset($item['created']) && $item['created'] == true) {
                                $arProduct = array(
                                    'FUSER_ID'               => $userId,
                                    'ORDER_ID'               => $order['externalId'],
                                    'LID'                    => $LID,
                                    'NOTES'                  => $item['comment'],
                                );
                            }
                            
                            if (isset($item['isCanceled']) == false) {
                                if (isset($item['initialPrice']) && $item['initialPrice']) {
                                    $arProduct['PRICE'] = (double) $item['initialPrice'];
                                }
                                if (isset($item['discount'])) {
                                    $arProduct['DISCOUNT_PRICE'] = $item['discount'];
                                }
                                if (isset($item['discountPercent'])) {
                                    $arProduct['DISCOUNT_VALUE'] = $item['discountPercent'];
                                    $newPrice = round($arProduct['PRICE'] / 100 * (100 - $arProduct['DISCOUNT_VALUE']), 2);
                                    $arProduct['DISCOUNT_PRICE'] = $arProduct['DISCOUNT_PRICE'] + $arProduct['PRICE'] - $newPrice;
                                }
                                if(isset($item['discount']) || isset($item['discountPercent'])) {
                                    $arProduct['PRICE'] -= $arProduct['DISCOUNT_PRICE'];
                                }
                                if (isset($item['offer']['name']) && $item['offer']['name']) {
                                    $arProduct['NAME'] = self::fromJSON($item['offer']['name']);
                                }
                                $arProduct = self::updateCancelProp($arProduct, 0);
                            } elseif (isset($item['isCanceled'])) {
                                $arProduct['PRICE'] = 0;
                                $arProduct = self::updateCancelProp($arProduct, 1);
                            }
                            
                            if (isset($item['quantity']) && $item['quantity']) {
                                $arProduct['QUANTITY'] = $item['quantity'];
                            }
                            if (isset($item['offer']['name']) && $item['offer']['name']) {
                                $arProduct['NAME'] = self::fromJSON($item['offer']['name']);
                            }
                            
                            if (isset($item['created']) && $item['created'] == true) {
                                $iBlocks = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_CATALOG_IBLOCKS, 0));
                                $iBlock = array_shift($iBlocks);
                                
                                $newSection = new CIBlockSection;
                                $newSectionFields = Array(
                                    "ACTIVE"    => 'N',
                                    "IBLOCK_ID" => $iBlock,
                                    "NAME"      => 'RetailCRM',
                                    "CODE"      => 'RetailCRM',
                                );
                                $resSection = $newSection->Add($newSectionFields);
                                if(!$resSection){
                                    self::eventLog('ICrmOrderActions::orderHistory', 'CIBlockSection::Add', 'Error castom section add');
                                    
                                    continue;
                                }

                                $arLoadProductArray = Array(
                                    "IBLOCK_SECTION_ID" => $resSection,
                                    "IBLOCK_ID"         => $iBlock,
                                    "NAME"              => $item['offer']['name'] ? $item['offer']['name'] : 'RetailCrmElement',
                                    "CODE"              => 'RetailCrmElement',
                                    "ACTIVE"            => 'Y'
                                );
                                $el = new CIBlockElement;
                                $PRODUCT_ID = $el->Add($arLoadProductArray, false, false, true); 
                                if(!$PRODUCT_ID){
                                    self::eventLog('ICrmOrderActions::orderHistory', 'CIBlockElement::Add', 'Error castom element add');
                                    
                                    continue;
                                }

                                if(!CCatalogProduct::Add(array("ID" => $PRODUCT_ID))){
                                    self::eventLog('ICrmOrderActions::orderHistory', 'CCatalogProduct::Add', 'Error product add');
                                    
                                    continue;
                                }

                                $arFields = Array(
                                    "PRODUCT_ID" => $PRODUCT_ID,
                                    "CATALOG_GROUP_ID" => COption::GetOptionString(self::$MODULE_ID, self::$CRM_CATALOG_BASE_PRICE, 0),
                                    "PRICE" => $item['initialPrice'] ? $item['initialPrice'] : 1,
                                    "CURRENCY" => CCurrency::GetBaseCurrency(),
                                );
                                if(!CPrice::Add($arFields)){
                                    self::eventLog('ICrmOrderActions::orderHistory', 'CPrice::Add', 'Error price add');
                                    
                                    continue;
                                }
                                
                                $Params = array(
                                    array(
                                        'NAME' => 'id',
                                        'CODE' => 'ID',
                                        'VALUE' => $item['offer']['externalId']
                                    )
                                );
                                if(!Add2BasketByProductID($PRODUCT_ID, $item['quantity'], $arProduct, $Params)){
                                    self::eventLog('ICrmOrderActions::orderHistory', 'Add2BasketByProductID', 'Error add to basket');
                                    
                                    continue;
                                } 

                                if(!CIBlockSection::Delete($resSection)){
                                    self::eventLog('ICrmOrderActions::orderHistory', 'CIBlockSection::Delete', 'Error delete section');
                                    
                                    continue;
                                } 
                                
                                continue;
                            }
                            
                            $prp = CSaleBasket::GetPropsList(array(), array("ORDER_ID" => $order['externalId'], "CODE" => 'ID', "VALUE" => $item['offer']['externalId']))->Fetch();
                            CSaleBasket::Update($prp['BASKET_ID'], $arProduct);
                        }
                    }

                    if (isset($order['delivery']) === false || isset($order['delivery']['cost']) === false) {
                        $order['delivery']['cost'] = $arFields['PRICE_DELIVERY'];
                    }

                    if (isset($order['summ']) === false || $order['summ'] <= 0) {
                        $order['summ'] = $arFields['PRICE'] - $arFields['PRICE_DELIVERY'];
                    }

                    $wasCanaceled = $arFields['CANCELED'] == 'Y' ? true : false;

                    if (isset($optionsDelivTypes[$order['delivery']['code']])) {
                        $resultDeliveryTypeId = $optionsDelivTypes[$order['delivery']['code']];
                    } else {
                        $resultDeliveryTypeId = isset($order['delivery']['service']) && isset($order['delivery']['service']['code']) ?
                                                    reset(explode(":", $arFields['DELIVERY_ID'], 1)) :
                                                    $arFields['DELIVERY_ID'];
                    }

                    if(isset($order['delivery']['service']) && isset($order['delivery']['service']['code'])) {
                        $deliveryHandler = reset(CSaleDeliveryHandler::GetBySID($resultDeliveryTypeId)->arResult);
                        if (count($deliveryHandler) > 0 && array_key_exists($order['delivery']['service']['code'], $deliveryHandler['PROFILES'])) {
                            $resultDeliveryTypeId = $resultDeliveryTypeId . ':' . $order['delivery']['service']['code'];
                        }
                    }

                    // orderUpdate
                    $arFields = self::clearArr(array(
                        'PRICE_DELIVERY'   => $order['delivery']['cost'],
                        'PRICE'            => $order['summ'] + (double) $order['delivery']['cost'],
                        'DATE_MARKED'      => $order['markDatetime'],
                        'USER_ID'          => $userId,
                        'PAY_SYSTEM_ID'    => $optionsPayTypes[$order['paymentType']],
                        'DELIVERY_ID'      => $resultDeliveryTypeId,
                        'STATUS_ID'        => $optionsPayStatuses[$order['status']],
                        'REASON_CANCELED'  => self::fromJSON($order['statusComment']),
                        'USER_DESCRIPTION' => self::fromJSON($order['customerComment']),
                        'COMMENTS'         => self::fromJSON($order['managerComment'])
                    ));

                    if (! date_create_from_format('Y-m-d H:i:s', $arFields['DATE_MARKED'])) {
                        unset($arFields['DATE_MARKED']);
                    }

                    if (isset($order['discount'])) {
                        $arFields['DISCOUNT_VALUE'] = $order['discount'];
                        $arFields['PRICE'] -= $order['discount'];
                    }

                    if(!empty($arFields)) {
                        try {
                            CSaleOrder::Update($order['externalId'], $arFields);
                        } catch (Exception $e) {
                            self::eventLog(
                                'ICrmOrderActions::orderHistory', 'CSaleOrder::Update',
                                $e->getCode() . ': ' . $e->getMessage() . ' (order external id: '.$order['externalId'].')'
                            );
                            continue;
                        }
                    }

                    if(isset($order['status']) && $order['status']) {
                        if(isset($optionsPayStatuses[$order['status']]) && $optionsPayStatuses[$order['status']]) {
                            // set STATUS_ID
                            CSaleOrder::StatusOrder($order['externalId'], $optionsPayStatuses[$order['status']]);

                            if($wasCanaceled && $optionsPayStatuses[ $order['status'] ] != 'YY') {
                                CSaleOrder::CancelOrder($order['externalId'], "N", $order['statusComment']);
                            } elseif ($optionsPayStatuses[ $order['status'] ] == 'YY') {
                                CSaleOrder::CancelOrder($order['externalId'], "Y", $order['statusComment']);
                            }
                        }
                    }

                    // set PAYED
                    if(isset($order['paymentStatus']) && $order['paymentStatus'] && $optionsPayment[$order['paymentStatus']]) {
                        CSaleOrder::PayOrder($order['externalId'], $optionsPayment[$order['paymentStatus']]);
                    }

                    if(function_exists('intarocrm_order_post_persist')) {
                        intarocrm_order_post_persist($order);
                    }
                }
            }

            if (count($orderHistory) > 0) {
                COption::SetOptionString(self::$MODULE_ID, self::$CRM_ORDER_HISTORY_DATE, $dateFinish->format('Y-m-d H:i:s'));
            }

            $GLOBALS['INTARO_CRM_FROM_HISTORY'] = false;

            return true;
        }

        protected static function recursiveUpdate($value, $code, $param)
        {
            $value = self::fromJSON($value);
            if (in_array($code, array('customer', 'items')) === false && isset($param['options'][$param['type']][$code])) {
                self::updateProps($value, $code, $param);
            }
        }

        protected static function updateProps($value, $code, $param)
        {
            if ($value == '' || !CModule::IncludeModule('sale')) {
                return false;
            }
            $add = false;
            if (isset($param['update'][ $param['options'][$param['type']][$code] ]) == false) {
                if ($arProp = CSaleOrderProps::GetList(array(), array('CODE' => $param['options'][$param['type']][$code]))->Fetch()) {
                    $param['update'][ $param['options'][$param['type']][$code] ] = array(
                        'NAME' => $arProp['NAME'],
                        'CODE' => $arProp['CODE'],
                        'ORDER_PROPS_ID' => $arProp['ID'],
                        'TYPE' => $arProp['TYPE'],
                        'ORDER_ID' => $param['orderId'],
                        'VALUE' => ''
                    );
                    $add = true;
                } else {
                    return false;
                }
            }

            if ($param['update'][ $param['options'][$param['type']][$code] ]['TYPE'] == 'LOCATION') {
                $value = self::getLocation($value);
                if ($value == false) {
                    return false;
                }
            }

            if ($param['update'][ $param['options'][$param['type']][$code] ]['VALUE'] != $value) {
                if ($add === true) {
                    $param['update'][ $param['options'][$param['type']][$code] ]['VALUE'] = $value;
                    CSaleOrderPropsValue::Add($param['update'][ $param['options'][$param['type']][$code] ]);
                } else {
                    CSaleOrderPropsValue::Update($param['update'][ $param['options'][$param['type']][$code] ]['ID'], array('VALUE' => $value));
                }
            }
        }

        protected static function updateCancelProp($arProduct, $value) {
            if (isset($arProduct['PROPS'])) {
                foreach($arProduct['PROPS'] as $key => $item) {
                    if ($item['CODE'] == self::CANCEL_PROPERTY_CODE) {
                        $arProduct['PROPS'][$key]['VALUE'] = $value;
                        break;
                    }
                }
                $arProduct['PROPS'][] = array(
                    'NAME' => GetMessage('PRODUCT_CANCEL'),
                    'CODE' => self::CANCEL_PROPERTY_CODE,
                    'VALUE' => $value,
                    'SORT' => 10,
                );
            }

            return $arProduct;
        }

        public static function getLocation($value) {
            if (is_string($value) === false) {
                return false;
            } elseif ($location = CSaleLocation::GetList(array(), array("LID" => LANGUAGE_ID, "CITY_NAME" => $value))->Fetch()) {
                return method_exists('CSaleLocation', 'getLocationCODEbyID') ? 
                            CSaleLocation::getLocationCODEbyID($location['ID']) : $location['ID'];
            } else {
                return false;
            }
        }

        /**
         *
         * w+ event in bitrix log
         */

        public static function eventLog($auditType, $itemId, $description) {

            CEventLog::Add(array(
                "SEVERITY"      => "SECURITY",
                "AUDIT_TYPE_ID" => $auditType,
                "MODULE_ID"     => self::$MODULE_ID,
                "ITEM_ID"       => $itemId,
                "DESCRIPTION"   => $description,
            ));
        }

        /**
         *
         * Agent function
         *
         * @return self name
         */

        public static function uploadOrdersAgent() {
            self::uploadOrders();
            $failedIds = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_ORDER_FAILED_IDS, 0));
            if (is_array($failedIds) && !empty($failedIds)) {
                self::uploadOrders(50, true);
            }

            return 'ICrmOrderActions::uploadOrdersAgent();';
        }

        /**
         *
         * Agent function
         *
         * @return self name
         */

        public static function orderAgent() {
            if(COption::GetOptionString('main', 'agents_use_crontab', 'N') != 'N') {
                define('NO_AGENT_CHECK', true);
            }

            self::uploadOrdersAgent();
            self::orderHistory();

            return 'ICrmOrderActions::orderAgent();';
        }

        /**
         * removes all empty fields from arrays
         * working with nested arrs
         *
         * @param array $arr
         * @return array
         */
        public static function clearArr($arr) {
            if (is_array($arr) === false) {
                return $arr;
            }

            $result = array();
            foreach ($arr as $index => $node ) {
                $result[ $index ] = is_array($node) === true ? self::clearArr($node) : trim($node);
                if ($result[ $index ] == '' || $result[ $index ] === null || count($result[ $index ]) < 1) {
                    unset($result[ $index ]);
                }
            }

            return $result;
        }

        /**
         *
         * @global $APPLICATION
         * @param $str in SITE_CHARSET
         * @return  $str in utf-8
         */
        public static function toJSON($str) {
            global $APPLICATION;

            return $APPLICATION->ConvertCharset($str, SITE_CHARSET, 'utf-8');
        }

        /**
         *
         * @global $APPLICATION
         * @param $str in utf-8
         * @return $str in SITE_CHARSET
         */
        public static function fromJSON($str) {
            global $APPLICATION;

            return $APPLICATION->ConvertCharset($str, 'utf-8', SITE_CHARSET);
        }

        public static function explodeFIO($fio) {
            $newFio = empty($fio) ? false : explode(" ", $fio, 3);
            $result = array();
            switch (count($newFio)) {
                default:
                case 0:
                    $result['firstName']  = $fio;
                    break;
                case 1:
                    $result['firstName']  = $newFio[0];
                    break;
                case 2:
                    $result = array(
                        'lastName'  => $newFio[0],
                        'firstName' => $newFio[1]
                    );
                    break;
                case 3:
                    $result = array(
                        'lastName'   => $newFio[0],
                        'firstName'  => $newFio[1],
                        'patronymic' => $newFio[2]
                    );
                    break;
            }

            return $result;
        }

        public static function apiMethod($api, $methodApi, $method, $params, $site = null) {
            switch($methodApi){
                case 'ordersGet':
                case 'orderEdit':
                case 'customerGet':
                case 'customerEdit':
                    try {
                        $result = $api->$methodApi($params, 'externalId', $site);
                    } catch (\RetailCrm\Exception\CurlException $e) {
                        self::eventLog(
                            __CLASS__.'::'.$method, 'RetailCrm\RestApi::'.$methodApi.'::CurlException',
                            $e->getCode() . ': ' . $e->getMessage()
                        );

                        return false;
                    }
                    return $result;

                default:
                    try {
                        $result = $api->$methodApi($params, $site);
                    } catch (\RetailCrm\Exception\CurlException $e) {
                        self::eventLog(
                            __CLASS__.'::'.$method, 'RetailCrm\RestApi::'.$methodApi.'::CurlException',
                            $e->getCode() . ': ' . $e->getMessage()
                        );

                        return false;
                    }
                    return $result;
            }        
        }
    }

    class RetailUser extends CUser
    {
        public function GetID()
        {
            $rsUser = CUser::GetList(($by='ID'), ($order='DESC'), array('LOGIN' => 'retailcrm%'));
            if ($arUser = $rsUser->Fetch()) {
                return $arUser['ID'];
            } else {
                $retailUser = new CUser;
                $userPassword = uniqid();
                $arFields = array(
                               "NAME"             => 'retailcrm',
                               "LAST_NAME"        => 'retailcrm',
                               "EMAIL"            => 'retailcrm@retailcrm.com',
                               "LOGIN"            => 'retailcrm',
                               "LID"              => "ru",
                               "ACTIVE"           => "Y",
                               "GROUP_ID"         => array(2),
                               "PASSWORD"         => $userPassword,
                               "CONFIRM_PASSWORD" => $userPassword
                            );
                $id = $retailUser->Add($arFields);
                if (!$id) {
                    return null;
                } else {
                    return $id;
                }
            }
        }
    }
}
