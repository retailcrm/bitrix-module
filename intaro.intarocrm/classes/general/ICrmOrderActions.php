<?php

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
    protected static $CRM_ORDER_SITES = 'sites_ids';
    protected static $CRM_ORDER_PROPS = 'order_props';
    protected static $CRM_ORDER_FAILED_IDS = 'order_failed_ids';
    protected static $CRM_ORDER_HISTORY_DATE = 'order_history_date';

    /**
     * Mass order uploading, without repeating; always returns true, but writes error log
     * @param $failed -- flag to export failed orders
     * @return boolean
     */
    public static function uploadOrders($pSize = 50, $failed = false) {

        //COption::SetOptionString(self::$MODULE_ID, self::$CRM_ORDER_LAST_ID, 0); // -- for test

        if (!CModule::IncludeModule("iblock")) {
            //handle err
            self::eventLog('ICrmOrderActions::uploadOrders', 'iblock', 'module not found');
            return true;
        }

        if (!CModule::IncludeModule("sale")) {
            //handle err
            self::eventLog('ICrmOrderActions::uploadOrders', 'sale', 'module not found');
            return true;
        }

        if (!CModule::IncludeModule("catalog")) {
            //handle err
            self::eventLog('ICrmOrderActions::uploadOrders', 'catalog', 'module not found');
            return true;
        }

        $resOrders = array();
        $resCustomers = array();

        $lastUpOrderId = COption::GetOptionString(self::$MODULE_ID, self::$CRM_ORDER_LAST_ID, 0);
        $lastOrderId = 0;

        $failedIds = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_ORDER_FAILED_IDS, 0));
        if (!$failedIds)
            $failedIds = array();

        $dbOrder = CSaleOrder::GetList(array("ID" => "ASC"), array('>ID' => $lastUpOrderId));
        $dbFailedOrder = CSaleOrder::GetList(array("ID" => "ASC"), array('ID' => $failedIds));

        $api_host = COption::GetOptionString(self::$MODULE_ID, self::$CRM_API_HOST_OPTION, 0);
        $api_key = COption::GetOptionString(self::$MODULE_ID, self::$CRM_API_KEY_OPTION, 0);

        //saved cat params
        $optionsOrderTypes = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_ORDER_TYPES_ARR, 0));
        $optionsDelivTypes = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_DELIVERY_TYPES_ARR, 0));
        $optionsPayTypes = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_PAYMENT_TYPES, 0));
        $optionsPayStatuses = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_PAYMENT_STATUSES, 0)); // --statuses
        $optionsPayment = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_PAYMENT, 0));
        $optionsSites = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_ORDER_SITES, 0));
        $optionsOrderProps = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_ORDER_PROPS, 0));

        $api = new IntaroCrm\RestApi($api_host, $api_key);

        $arParams = array(
            'optionsOrderTypes' => $optionsOrderTypes,
            'optionsDelivTypes' => $optionsDelivTypes,
            'optionsPayTypes' => $optionsPayTypes,
            'optionsPayStatuses' => $optionsPayStatuses,
            'optionsPayment' => $optionsPayment,
            'optionSites' => $optionsSites,
            'optionsOrderProps' => $optionsOrderProps
        );

        if (!$failed) {

            //packmode

            $orderCount = 0;

            while ($arOrder = $dbOrder->GetNext()) { // here orders by id asc
                if (is_array($optionsSites))
                    if (!empty($optionsSites))
                        if (!in_array($arOrder['LID'], $optionsSites))
                            continue;

                $result = self::orderCreate($arOrder, $api, $arParams);

                if (!$result['order'] || !$result['customer'])
                    continue;

                $orderCount++;

                $resOrders[] = $result['order'];
                $resCustomers[] = $result['customer'];

                $lastOrderId = $arOrder['ID'];

                if ($orderCount >= $pSize) {
                    $customers = $api->customerUpload($resCustomers);

                    // error pushing customers
                    if ($api->getStatusCode() != 201) {
                        //handle err
                        //self::eventLog('ICrmOrderActions::uploadOrders', 'IntaroCrm\RestApi::customerUpload', $api->getLastError());

                        if ($api->getStatusCode() != 460) // some orders were sent
                            return false; // in pack mode return errors
                    }

                    $orders = $api->orderUpload($resOrders);

                    // error pushing orders
                    if ($api->getStatusCode() != 201) {
                        //handle err
                        self::eventLog('ICrmOrderActions::uploadOrders', 'IntaroCrm\RestApi::orderUpload', $api->getLastError());

                        if ($api->getStatusCode() != 460) // some orders were sent
                            return false; // in pack mode return errors
                    }

                    if ($lastOrderId)
                        COption::SetOptionString(self::$MODULE_ID, self::$CRM_ORDER_LAST_ID, $lastOrderId);

                    return true; // end of pack
                }
            }
            if (!empty($resOrders)) {
                $customers = $api->customerUpload($resCustomers);

                // error pushing customers
                if ($api->getStatusCode() != 201) {
                    //handle err
                    //self::eventLog('ICrmOrderActions::uploadOrders', 'IntaroCrm\RestApi::customerUpload', $api->getLastError());

                    if ($api->getStatusCode() != 460) // some orders were sent
                        return false; // in pack mode return errors
                }

                $orders = $api->orderUpload($resOrders);

                // error pushing orders
                if ($api->getStatusCode() != 201) {
                    //handle err
                    self::eventLog('ICrmOrderActions::uploadOrders', 'IntaroCrm\RestApi::orderUpload', $api->getLastError());

                    if ($api->getStatusCode() != 460) // some orders were sent
                        return false; // in pack mode return errors
                }
            }

            if ($lastOrderId)
                COption::SetOptionString(self::$MODULE_ID, self::$CRM_ORDER_LAST_ID, $lastOrderId);
            
        } else {
            
            // failed orders upload 
            $orderCount = 0;
            $recOrders = array();

            while ($arOrder = $dbFailedOrder->GetNext()) { // here orders by id asc
                if (is_array($optionsSites))
                    if (!empty($optionsSites))
                        if (!in_array($arOrder['LID'], $optionsSites))
                            continue;

                $result = self::orderCreate($arOrder, $api, $arParams);

                if (!$result['order'] || !$result['customer'])
                    continue;

                $orderCount++;

                $resOrders[] = $result['order'];
                $resCustomers[] = $result['customer'];

                $recOrders[] = $arOrder['ID'];

                if ($orderCount >= $pSize) {
                    $customers = $api->customerUpload($resCustomers);

                    // error pushing customers
                    if ($api->getStatusCode() != 201) {
                        //handle err
                        //self::eventLog('ICrmOrderActions::uploadOrders', 'IntaroCrm\RestApi::customerUpload', $api->getLastError());

                        if ($api->getStatusCode() != 460) // some orders were sent
                            return false; // in pack mode return errors
                    }

                    $orders = $api->orderUpload($resOrders);

                    // error pushing orders
                    if ($api->getStatusCode() != 201) {
                        //handle err
                        self::eventLog('ICrmOrderActions::uploadOrders', 'IntaroCrm\RestApi::orderUpload', $api->getLastError());

                        if ($api->getStatusCode() != 460) // some orders were sent
                            return false; // in pack mode return errors
                    }

                    if (!empty($recOrders)) {
                        $failedIds = array_merge(array_diff($failedIds, $recOrders)); // clear success ids
                        COption::SetOptionString(self::$MODULE_ID, self::$CRM_ORDER_FAILED_IDS, serialize($failedIds));
                    }

                    return true; // end of pack
                }
            }
            if (!empty($resOrders)) {
                $customers = $api->customerUpload($resCustomers);

                // error pushing customers
                if ($api->getStatusCode() != 201) {
                    //handle err
                    //self::eventLog('ICrmOrderActions::uploadOrders', 'IntaroCrm\RestApi::customerUpload', $api->getLastError());

                    if ($api->getStatusCode() != 460) // some orders were sent
                        return false; // in pack mode return errors
                }

                $orders = $api->orderUpload($resOrders);

                // error pushing orders
                if ($api->getStatusCode() != 201) {
                    //handle err
                    self::eventLog('ICrmOrderActions::uploadOrders', 'IntaroCrm\RestApi::orderUpload', $api->getLastError());

                    if ($api->getStatusCode() != 460) // some orders were sent
                        return false; // in pack mode return errors
                }
            }

            if (!empty($recOrders)) {
                $failedIds = array_merge(array_diff($failedIds, $recOrders)); // clear success ids
                COption::SetOptionString(self::$MODULE_ID, self::$CRM_ORDER_FAILED_IDS, serialize($failedIds));
            }
        }

        return true; //all ok!
    }
    
    /**
     * 
     * History update
     * @global CUser $USER
     * @return boolean
     */
    public static function orderHistory() {
        global $USER;
        
        if(!isset($USER) || !$USER) { // for agent; to add order User
            $USER = new CUser;
            $USER->Update(1, array());
        }
        
        if (!CModule::IncludeModule("iblock")) {
            //handle err
            self::eventLog('ICrmOrderActions::orderHistory', 'iblock', 'module not found');
            return true;
        }
        
        if (!CModule::IncludeModule("sale")) {
            //handle err
            self::eventLog('ICrmOrderActions::orderHistory', 'sale', 'module not found');
            return true;
        }
        
        if (!CModule::IncludeModule("catalog")) {
            //handle err
            self::eventLog('ICrmOrderActions::orderHistory', 'catalog', 'module not found');
            return true;
        }
        
        $defaultSiteId = 0;
        $rsSites = CSite::GetList($by, $sort, array('DEF' => 'Y'));
            while ($ar = $rsSites->Fetch()) {
                $defaultSiteId = $ar['LID'];
                break;
            }
        
        $api_host = COption::GetOptionString(self::$MODULE_ID, self::$CRM_API_HOST_OPTION, 0);
        $api_key = COption::GetOptionString(self::$MODULE_ID, self::$CRM_API_KEY_OPTION, 0);

        //saved cat params (crm -> bitrix)
        $optionsOrderTypes = array_flip(unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_ORDER_TYPES_ARR, 0)));
        $optionsDelivTypes = array_flip(unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_DELIVERY_TYPES_ARR, 0)));
        $optionsPayTypes = array_flip(unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_PAYMENT_TYPES, 0)));
        $optionsPayStatuses = array_flip(unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_PAYMENT_STATUSES, 0))); // --statuses
        $optionsPayment = array_flip(unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_PAYMENT, 0)));
        $optionsSites = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_ORDER_SITES, 0));
        $optionsOrderProps = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_ORDER_PROPS, 0));

        $api = new IntaroCrm\RestApi($api_host, $api_key);
 
        $dateStart = COption::GetOptionString(self::$MODULE_ID, self::$CRM_ORDER_HISTORY_DATE, null);

        $orderHistory = $api->orderHistory($dateStart);
        
        if($dateStart)
            $dateStart = new \DateTime($dateStart);
        
        // pushing existing orders
        foreach ($orderHistory as $order) {

            if(!isset($order['externalId']) && !$order['externalId']) {

                // we dont need new orders without any customers (can check only for externalId)
                if(!isset($order['customer']['externalId']) && !$order['customer']['externalId'])
                    continue;

                // new order
               $newOrderFields = array(
                    'LID'              => $defaultSiteId,
                    'PERSON_TYPE_ID'   => $optionsOrderTypes[$order['orderType']],
                    'PAYED'            => 'N',
                    'CANCELED'         => 'N',
                    'STATUS_ID'        => 'N',
                    'PRICE'            => 0,
                    'CURRENCY'         => 'RUB',
                    'USER_ID'          => $order['customer']['externalId'],
                    'PAY_SYSTEM_ID'    => 0,
                    'PRICE_DELIVERY'   => 0,
                    'DELIVERY_ID'      => 0,
                    'DISCOUNT_VALUE'   => 0,
                    'USER_DESCRIPTION' => ''
                );
               
                if(isset($order['number']) && $order['number'])
                    $GLOBALS['ICRM_ACCOUNT_NUMBER'] = $order['number'];
                
                $order['externalId'] = CSaleOrder::Add($newOrderFields);

                if(isset($GLOBALS['ICRM_ACCOUNT_NUMBER']))
                    unset($GLOBALS['ICRM_ACCOUNT_NUMBER']);

                $api->orderFixExternalIds(array(array('id' => $order['id'], 'externalId' => $order['externalId'])));
                
                if ($api->getStatusCode() != 200) { 
                    //handle err - write log & continue
                    self::eventLog('ICrmOrderActions::orderHistory', 'IntaroCrm\RestApi::orderFixExternalIds', $api->getLastError());
                    continue;
                } 
            }
            
            if(isset($order['externalId']) && $order['externalId']) { 
                $arFields = CSaleOrder::GetById($order['externalId']);
                
                // incorrect order
                if(!$arFields || empty($arFields))
                    continue;
                
                $LID = $arFields['LID'];
                $userId = $arFields['USER_ID'];

                if(isset($order['customer']['externalId']) && $order['customer']['externalId']) 
                    $userId = $order['customer']['externalId'];
                
                $rsOrderProps = CSaleOrderPropsValue::GetList(array(), array('ORDER_ID' => $arFields['ID']));
                
                while ($ar = $rsOrderProps->Fetch()) {
                    if (isset($order['deliveryAddress']) && $order['deliveryAddress']) {
                        switch ($ar['CODE']) {
                            case $optionsOrderProps[$arFields['PERSON_TYPE_ID']]['index']: if (isset($order['deliveryAddress']['index']))
                                    CSaleOrderPropsValue::Update($ar['ID'], array('VALUE' => self::fromJSON($order['deliveryAddress']['index'])));
                                break;
                            case 'CITY': if (isset($order['deliveryAddress']['city']))
                                    CSaleOrderPropsValue::Update($ar['ID'], array('VALUE' => self::fromJSON($order['deliveryAddress']['city'])));
                                break;
                            case $optionsOrderProps[$arFields['PERSON_TYPE_ID']]['text']: if (isset($order['deliveryAddress']['text']))
                                    CSaleOrderPropsValue::Update($ar['ID'], array('VALUE' => self::fromJSON($order['deliveryAddress']['text'])));
                                break;
                            case 'LOCATION': if (isset($order['deliveryAddress']['city'])) {
                                    $cityId = self::getLocationCityId($order['deliveryAddress']['city']);
                                    if (!$cityId)
                                        break;
                                    CSaleOrderPropsValue::Update($ar['ID'], array('VALUE' => $cityId));
                                }
                                break;
                        }

                        if (count($optionsOrderProps[$arFields['PERSON_TYPE_ID']] > 4)) {
                            switch ($ar['CODE']) {
                                /* case $optionsOrderProps[$arFields['PERSON_TYPE_ID']]['country']: $resOrderDeliveryAddress['country'] = self::toJSON($ar['VALUE']);
                                  break;
                                  case $optionsOrderProps[$arFields['PERSON_TYPE_ID']]['region']: $resOrderDeliveryAddress['region'] = self::toJSON($ar['VALUE']);
                                  break;
                                  case $optionsOrderProps[$arFields['PERSON_TYPE_ID']]['city']: $resOrderDeliveryAddress['city'] = self::toJSON($ar['VALUE']);
                                  break; */
                                case $optionsOrderProps[$arFields['PERSON_TYPE_ID']]['street']: if (isset($order['deliveryAddress']['street']))
                                        CSaleOrderPropsValue::Update($ar['ID'], array('VALUE' => self::fromJSON($order['deliveryAddress']['street'])));
                                    break;
                                case $optionsOrderProps[$arFields['PERSON_TYPE_ID']]['building']: if (isset($order['deliveryAddress']['building']))
                                        CSaleOrderPropsValue::Update($ar['ID'], array('VALUE' => self::fromJSON($order['deliveryAddress']['building'])));
                                    break;
                                case $optionsOrderProps[$arFields['PERSON_TYPE_ID']]['flat']: if (isset($order['deliveryAddress']['flat']))
                                        CSaleOrderPropsValue::Update($ar['ID'], array('VALUE' => self::fromJSON($order['deliveryAddress']['flat'])));
                                    break;
                                case $optionsOrderProps[$arFields['PERSON_TYPE_ID']]['intercomcode']: if (isset($order['deliveryAddress']['intercomcode']))
                                        CSaleOrderPropsValue::Update($ar['ID'], array('VALUE' => self::fromJSON($order['deliveryAddress']['intercomcode'])));
                                    break;
                                case $optionsOrderProps[$arFields['PERSON_TYPE_ID']]['floor']: if (isset($order['deliveryAddress']['floor']))
                                        CSaleOrderPropsValue::Update($ar['ID'], array('VALUE' => self::fromJSON($order['deliveryAddress']['floor'])));
                                    break;
                                case $optionsOrderProps[$arFields['PERSON_TYPE_ID']]['block']: if (isset($order['deliveryAddress']['block']))
                                        CSaleOrderPropsValue::Update($ar['ID'], array('VALUE' => self::fromJSON($order['deliveryAddress']['block'])));
                                    break;
                                case $optionsOrderProps[$arFields['PERSON_TYPE_ID']]['house']: if (isset($order['deliveryAddress']['house']))
                                        CSaleOrderPropsValue::Update($ar['ID'], array('VALUE' => self::fromJSON($order['deliveryAddress']['house'])));
                                    break;
                            }
                        }
                    }

                    switch ($ar['CODE']) {
                        case $optionsOrderProps[$arFields['PERSON_TYPE_ID']]['fio']:
                                if (isset($order['firstName']))
                                    $contactName['firstName'] = self::fromJSON($order['firstName']);
                                if (isset($order['lastName']))
                                    $contactName['lastName'] = self::fromJSON($order['lastName']);
                                if (isset($order['patronymic']))
                                    $contactName['patronymic'] = self::fromJSON($order['patronymic']);

                                if (!isset($contactName) || empty($contactName))
                                    break;

                                CSaleOrderPropsValue::Update($ar['ID'], array('VALUE' => implode(" ", $contactName)));
                            break;
                        case $optionsOrderProps[$arFields['PERSON_TYPE_ID']]['phone']: if (isset($order['phone']))
                                CSaleOrderPropsValue::Update($ar['ID'], array('VALUE' => self::fromJSON($order['phone'])));
                            break;
                        case $optionsOrderProps[$arFields['PERSON_TYPE_ID']]['email']: if (isset($order['email']))
                                CSaleOrderPropsValue::Update($ar['ID'], array('VALUE' => self::fromJSON($order['email'])));
                            break;
                    }

                }
                
                // here check if smth wasnt added or new propetties
                if (isset($order['deliveryAddress']) && $order['deliveryAddress']) {
                    if (isset($order['deliveryAddress']['index']))
                        self::addOrderProperty($optionsOrderProps[$arFields['PERSON_TYPE_ID']]['index'], 
                                self::fromJSON($order['deliveryAddress']['index']), $order['externalId']);
                    
                    if (isset($order['deliveryAddress']['city']))
                        self::addOrderProperty($optionsOrderProps[$arFields['PERSON_TYPE_ID']]['city'], self::fromJSON($order['deliveryAddress']['city']), $order['externalId']);

                    if (isset($order['deliveryAddress']['text']))
                        self::addOrderProperty($optionsOrderProps[$arFields['PERSON_TYPE_ID']]['text'], self::fromJSON($order['deliveryAddress']['text']), $order['externalId']);

                    if (count($optionsOrderProps[$arFields['PERSON_TYPE_ID']] > 4)) {
                        if (isset($order['deliveryAddress']['street']))
                            self::addOrderProperty($optionsOrderProps[$arFields['PERSON_TYPE_ID']]['street'], 
                                    self::fromJSON($order['deliveryAddress']['street']), $order['externalId']);
                        
                        if (isset($order['deliveryAddress']['building']))
                            self::addOrderProperty($optionsOrderProps[$arFields['PERSON_TYPE_ID']]['building'], 
                                    self::fromJSON($order['deliveryAddress']['bulding']), $order['externalId']);
                        
                        if (isset($order['deliveryAddress']['flat']))
                            self::addOrderProperty($optionsOrderProps[$arFields['PERSON_TYPE_ID']]['flat'], 
                                    self::fromJSON($order['deliveryAddress']['flat']), $order['externalId']);
                        
                        if (isset($order['deliveryAddress']['intercomcode']))
                            self::addOrderProperty($optionsOrderProps[$arFields['PERSON_TYPE_ID']]['intercomcode'], 
                                    self::fromJSON($order['deliveryAddress']['intercomcode']), $order['externalId']);

                        if (isset($order['deliveryAddress']['floor']))
                            self::addOrderProperty($optionsOrderProps[$arFields['PERSON_TYPE_ID']]['floor'], 
                                    self::fromJSON($order['deliveryAddress']['floor']), $order['externalId']);
                        
                        if (isset($order['deliveryAddress']['block']))
                            self::addOrderProperty($optionsOrderProps[$arFields['PERSON_TYPE_ID']]['block'], 
                                    self::fromJSON($order['deliveryAddress']['block']), $order['externalId']);
                        
                        if (isset($order['deliveryAddress']['house']))
                            self::addOrderProperty($optionsOrderProps[$arFields['PERSON_TYPE_ID']]['house'], 
                                    self::fromJSON($order['deliveryAddress']['house']), $order['externalId']);
                    }
                }
                
                if (isset($order['phone']))
                    self::addOrderProperty($optionsOrderProps[$arFields['PERSON_TYPE_ID']]['phone'],
                            self::fromJSON($order['phone']), $order['externalId']);

                if (isset($order['email']))
                    self::addOrderProperty($optionsOrderProps[$arFields['PERSON_TYPE_ID']]['email'],
                            self::fromJSON($order['email']), $order['externalId']);

                if (isset($order['firstName']))
                    $contactName['firstName'] = self::fromJSON($order['firstName']);
                if (isset($order['lastName']))
                    $contactName['lastName'] = self::fromJSON($order['lastName']);
                if (isset($order['patronymic']))
                    $contactName['patronymic'] = self::fromJSON($order['patronymic']);

                if (isset($contactName) && !empty($contactName))
                    self::addOrderProperty($optionsOrderProps[$arFields['PERSON_TYPE_ID']]['fio'],
                            implode(" ", $contactName), $order['externalId']);

                foreach($order['items'] as $item) {
                    // del from basket
                    if(isset($item['deleted']) && $item['deleted']) {
                        $p = CSaleBasket::GetList(
                            array('PRODUCT_ID' => 'ASC'),
                            array('ORDER_ID' => $order['externalId'], 'PRODUCT_ID' => $item['id']))->Fetch();

                        if($p)
                            CSaleBasket::Delete($p['ID']);

                         continue;
                    }

                    if(!isset($item['offer']) && !$item['offer']['externalId'])
                        continue;
                    
                    $p = CSaleBasket::GetList(
                            array('PRODUCT_ID' => 'ASC'),
                            array('ORDER_ID' => $order['externalId'], 'PRODUCT_ID' => $item['offer']['externalId']))->Fetch();
                    
                    if(!$p)
                        $p = CIBlockElement::GetByID($item['offer']['externalId'])->Fetch();

                    // change existing basket items
                    $arProduct = array();
                    
                    // create new
                    if(isset($item['created']) && $item['created']) {
                        
                        $productPrice = GetCatalogProductPrice($item['offer']['externalId'], 1);
                                
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
                            'NOTES'                  => $p['NOTES'],
                            'PRODUCT_PROVIDER_CLASS' => $p['PRODUCT_PROVIDER_CLASS'],
                            'DETAIL_PAGE_URL'        => $p['DETAIL_PAGE_URL'],
                            'CATALOG_XML_ID'         => $p['CATALOG_XML_ID'],
                            'PRODUCT_XML_ID'         => $p['PRODUCT_XML_ID']
                        );

                        if (isset($item['initialPrice']) && $item['initialPrice'])
                            $arProduct['PRICE'] = (double) $item['initialPrice'];

                        if (isset($item['discount'])) {
                            $arProduct['DISCOUNT_PRICE'] = $item['discount'];
                            $arProduct['PRICE'] -= $arProduct['DISCOUNT_PRICE'];                        
                        }

                        if (isset($item['discountPercent'])) {                            
                            $arProduct['DISCOUNT_VALUE'] = $item['discountPercent'];
                            $newPrice = floor ($arProduct['PRICE'] / 100 * (100 - $arProduct['DISCOUNT_VALUE']));
                            $arProduct['DISCOUNT_PRICE'] = $arProduct['PRICE'] - $newPrice;
                            $arProduct['PRICE'] -= $arProduct['DISCOUNT_PRICE'];
                        }

                        if (isset($item['offer']['name']) && $item['offer']['name'])
                            $arProduct['NAME'] = $item['offer']['name'];

                        CSaleBasket::Add($arProduct);
                        continue;
                    }

                    // update old
                    if (isset($item['initialPrice']) && $item['initialPrice'])
                        $arProduct['PRICE'] = (double) $item['initialPrice'];

                    if (isset($item['discount'])) {                         
                        $arProduct['DISCOUNT_PRICE'] = $item['discount'];
                        $arProduct['PRICE'] -= $arProduct['DISCOUNT_PRICE'];
                    }                

                    if (isset($item['discountPercent'])) {                            
                            $arProduct['DISCOUNT_VALUE'] = $item['discountPercent'];
                            $newPrice = floor ($arProduct['PRICE'] / 100 * (100 - $arProduct['DISCOUNT_VALUE']));
                            $arProduct['DISCOUNT_PRICE'] = $arProduct['PRICE'] - $newPrice;
                            $arProduct['PRICE'] -= $arProduct['DISCOUNT_PRICE'];
                        }

                    if (isset($item['quantity']) && $item['quantity'])
                        $arProduct['QUANTITY'] = $item['quantity'];

                    if (isset($item['offer']['name']) && $item['offer']['name'])
                        $arProduct['NAME'] = $item['offer']['name'];

                    CSaleBasket::Update($p['ID'], $arProduct);
                    CSaleBasket::DeleteAll($userId);
                } 

                if(!isset($order['deliveryCost']))
                    $order['deliveryCost'] = $arFields['PRICE_DELIVERY'];

                if(!isset($order['summ']) || (isset($order['summ']) && !$order['summ'])) 
                    $order['summ'] = $arFields['PRICE'] - $arFields['PRICE_DELIVERY'];

                $wasCanaceled = false;
                if($arFields['CANCELED'] == 'Y')
                    $wasCanaceled = true;                      

                // orderUpdate
                $arFields = self::clearArr(array(
                    'PRICE_DELIVERY'   => $order['deliveryCost'],
                    'PRICE'            => $order['summ'] + (double) $order['deliveryCost'],
                    'DATE_MARKED'      => $order['markDatetime'],
                    'USER_ID'          => $userId, //$order['customer']
                    'PAY_SYSTEM_ID'    => $optionsPayTypes[$order['paymentType']],
                    //'PAYED'            => $optionsPayment[$order['paymentStatus']],
                    //'PERSON_TYPE_ID' => $optionsOrderTypes[$order['orderType']],
                    'DELIVERY_ID'      => $optionsDelivTypes[$order['deliveryType']],
                    'STATUS_ID'        => $optionsPayStatuses[$order['status']],
                    'REASON_CANCELED'  => $order['statusComment'],
                    'USER_DESCRIPTION' => $order['customerComment'],
                    'COMMENTS'         => $order['managerComment']
                ));
                
                $GLOBALS['INTARO_CRM_FROM_HISTORY'] = true;

                CSaleOrder::Update($order['externalId'], $arFields);

                // set STATUS_ID
                if($optionsPayStatuses[$order['status']])
                    CSaleOrder::StatusOrder($order['externalId'], $optionsPayStatuses[$order['status']]);

                // uncancel order
                if($wasCanaceled && ($optionsPayStatuses[$order['status']] != 'YY'))
                    CSaleOrder::CancelOrder($order['externalId'], "N", $order['statusComment']);

                // cancel order
                if($optionsPayStatuses[$order['status']] == 'YY')
                    CSaleOrder::CancelOrder($order['externalId'], "Y", $order['statusComment']);

                // set PAYED
                if($optionsPayment[$order['paymentStatus']])
                    CSaleOrder::PayOrder($order['externalId'], $optionsPayment[$order['paymentStatus']]);

                $dateStart = new \DateTime();
            } 
        }
        
        if(count($orderHistory))
            COption::SetOptionString(self::$MODULE_ID, self::$CRM_ORDER_HISTORY_DATE, $dateStart->format('Y-m-d H:i:s'));
        
        return true;
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
        if(is_array($failedIds) && !empty($failedIds))
            self::uploadOrders(50, true); // upload failed orders
        
        return 'ICrmOrderActions::uploadOrdersAgent();';
    }
    
    /**
     *
     * Agent function
     *
     * @return self name
     */

    public static function orderHistoryAgent() {
        self::orderHistory();
        return 'ICrmOrderActions::orderHistoryAgent();';
    }
    
    /**
     * 
     * creates order or returns array of order and customer for mass upload
     * 
     * @param type $orderId
     * @param type $api
     * @param type $arParams
     * @param type $send
     * @return boolean
     * @return array - array('order' = $order, 'customer' => $customer)
     */
    public static function orderCreate($arFields, $api, $arParams, $send = false) {
        if(!$api || empty($arParams)) { // add cond to check $arParams
            return false;
        }
        
        if (empty($arFields)) {
            //handle err
            self::eventLog('ICrmOrderActions::orderCreate', 'empty($arFields)', 'incorrect order');

            return false;
        }

        $rsUser = CUser::GetByID($arFields['USER_ID']);
        $arUser = $rsUser->Fetch();

        $createdAt = new \DateTime($arUser['DATE_REGISTER']);
        $createdAt = $createdAt->format('Y-m-d H:i:s');

        // push customer (for crm)
        $firstName = self::toJSON($arUser['NAME']);
        $lastName = self::toJSON($arUser['LAST_NAME']);
        $patronymic = self::toJSON($arUser['SECOND_NAME']);

        $phones = array();

        $phonePersonal = array(
            'number' => self::toJSON($arUser['PERSONAL_PHONE']),
            'type'   => 'mobile'
        );

        if($phonePersonal['number'])
            $phones[] = $phonePersonal;

        $phoneWork = array(
            'number' => self::toJSON($arUser['WORK_PHONE']),
            'type'   => 'work'
        );

        if($phoneWork['number'])
            $phones[] = $phoneWork;

        $customer = self::clearArr(array(
            'externalId' => $arFields['USER_ID'],
            'lastName'   => $lastName,
            'firstName'  => $firstName,
            'patronymic' => $patronymic,
            'phones'     => $phones,
            'createdAt'  => $createdAt
        ));
        
        if($send)
            $customer = $api->customerEdit($customer);
        
        // error pushing customer
        if (!$customer) {
            //handle err
            self::eventLog('ICrmOrderActions::orderCreate', 'IntaroCrm\RestApi::customerEdit', $api->getLastError());
            return false;
        }

        // delivery types
        $arId = array();
        if (strpos($arFields['DELIVERY_ID'], ":") !== false)
            $arId = explode(":", $arFields["DELIVERY_ID"]);

        if ($arId)
            $resultDeliveryTypeId = $arId[0];
        else
            $resultDeliveryTypeId = $arFields['DELIVERY_ID'];


        $resOrder = array();
        $resOrderDeliveryAddress = array();
        $contactNameArr = array();

        $rsOrderProps = CSaleOrderPropsValue::GetList(array(), array('ORDER_ID' => $arFields['ID']));
        while ($ar = $rsOrderProps->Fetch()) {
            switch ($ar['CODE']) {
                case $arParams['optionsOrderProps'][$arFields['PERSON_TYPE_ID']]['index']: $resOrderDeliveryAddress['index'] = self::toJSON($ar['VALUE']);
                    break;
                case 'CITY': $resOrderDeliveryAddress['city'] = self::toJSON($ar['VALUE']);
                    break;
                case $arParams['optionsOrderProps'][$arFields['PERSON_TYPE_ID']]['text']: $resOrderDeliveryAddress['text'] = self::toJSON($ar['VALUE']);
                    break;
                case 'LOCATION': if(!isset($resOrderDeliveryAddress['city']) && !$resOrderDeliveryAddress['city']) {
                        $resOrderDeliveryAddress['city'] = CSaleLocation::GetByID($ar['VALUE']);
                        $resOrderDeliveryAddress['city'] = self::toJSON($resOrderDeliveryAddress['city']['CITY_NAME_LANG']);
                    }
                    break;
                case $arParams['optionsOrderProps'][$arFields['PERSON_TYPE_ID']]['fio']: $contactNameArr = self::explodeFIO($ar['VALUE']);
                    break;
                case $arParams['optionsOrderProps'][$arFields['PERSON_TYPE_ID']]['phone']: $resOrder['phone'] = $ar['VALUE'];
                    break;
                case $arParams['optionsOrderProps'][$arFields['PERSON_TYPE_ID']]['email']: $resOrder['email'] = $ar['VALUE'];
                    break;
            }
            
            if (count($arParams['optionsOrderProps'][$arFields['PERSON_TYPE_ID']] > 4)) {
                switch ($ar['CODE']) {
                    /*case $arParams['optionsOrderProps'][$arFields['PERSON_TYPE_ID']]['country']: $resOrderDeliveryAddress['country'] = self::toJSON($ar['VALUE']);
                        break;
                    case $arParams['optionsOrderProps'][$arFields['PERSON_TYPE_ID']]['region']: $resOrderDeliveryAddress['region'] = self::toJSON($ar['VALUE']);
                        break;
                    case $arParams['optionsOrderProps'][$arFields['PERSON_TYPE_ID']]['city']: $resOrderDeliveryAddress['city'] = self::toJSON($ar['VALUE']);
                        break; */
                    case $arParams['optionsOrderProps'][$arFields['PERSON_TYPE_ID']]['street']: $resOrderDeliveryAddress['street'] = self::toJSON($ar['VALUE']);
                        break;
                    case $arParams['optionsOrderProps'][$arFields['PERSON_TYPE_ID']]['building']: $resOrderDeliveryAddress['building'] = self::toJSON($ar['VALUE']);
                        break;
                    case $arParams['optionsOrderProps'][$arFields['PERSON_TYPE_ID']]['flat']: $resOrderDeliveryAddress['flat'] = self::toJSON($ar['VALUE']);
                        break;
                    case $arParams['optionsOrderProps'][$arFields['PERSON_TYPE_ID']]['intercomcode']: $resOrderDeliveryAddress['intercomcode'] = self::toJSON($ar['VALUE']);
                        break;
                    case $arParams['optionsOrderProps'][$arFields['PERSON_TYPE_ID']]['floor']: $resOrderDeliveryAddress['floor'] = self::toJSON($ar['VALUE']);
                        break;
                    case $arParams['optionsOrderProps'][$arFields['PERSON_TYPE_ID']]['block']: $resOrderDeliveryAddress['block'] = self::toJSON($ar['VALUE']);
                        break;
                    case $arParams['optionsOrderProps'][$arFields['PERSON_TYPE_ID']]['house']: $resOrderDeliveryAddress['house'] = self::toJSON($ar['VALUE']);
                        break;
                }
            }
        }

        $items = array();

        $rsOrderBasket = CSaleBasket::GetList(array('PRODUCT_ID' => 'ASC'), array('ORDER_ID' => $arFields['ID']));
        while ($p = $rsOrderBasket->Fetch()) {
            $pr = CCatalogProduct::GetList(array('ID' => $p['PRODUCT_ID']))->Fetch();
            if ($pr)
                $pr = $pr['PURCHASING_PRICE'];
            else
                $pr = '';

            $items[] = array(
                'initialPrice'    => (double) $p['PRICE'] + (double) $p['DISCOUNT_PRICE'],
                //'purchasePrice'   => $pr,
                'discount'        => $p['DISCOUNT_PRICE'],
                'discountPercent' => $p['DISCOUNT_VALUE'],
                'quantity'        => $p['QUANTITY'],
                'productId'       => $p['PRODUCT_ID'],
                'productName'     => self::toJSON($p['NAME'])
            );
        }

        if($arFields['CANCELED'] == 'Y')
            $arFields['STATUS_ID'] = $arFields['CANCELED'].$arFields['CANCELED'];

        $createdAt = new \DateTime($arFields['DATE_INSERT']);
        $createdAt = $createdAt->format('Y-m-d H:i:s');

        $resOrder = array(
            'number'          => $arFields['ACCOUNT_NUMBER'],
            'phone'           => $resOrder['phone'],
            'email'           => $resOrder['email'],
            'deliveryCost'    => $arFields['PRICE_DELIVERY'],
            'summ'            => $arFields['PRICE'],
            'markDateTime'    => $arFields['DATE_MARKED'],
            'externalId'      => $arFields['ID'],
            'customerId'      => $arFields['USER_ID'],
            'paymentType'     => $arParams['optionsPayTypes'][$arFields['PAY_SYSTEM_ID']],
            'paymentStatus'   => $arParams['optionsPayment'][$arFields['PAYED']],
            'orderType'       => $arParams['optionsOrderTypes'][$arFields['PERSON_TYPE_ID']],
            'deliveryType'    => $arParams['optionsDelivTypes'][$resultDeliveryTypeId],
            'status'          => $arParams['optionsPayStatuses'][$arFields['STATUS_ID']],
            'statusComment'   => $arFields['REASON_CANCELED'],
            'customerComment' => $arFields['USER_DESCRIPTION'],
            'managerComment'  => $arFields['COMMENTS'],
            'createdAt'       => $createdAt,
            'deliveryAddress' => $resOrderDeliveryAddress,
            'discount'        => $arFields['DISCOUNT_PRICE'],
            'discountPercent' => $arFields['DISCOUNT_VALUE'],
            'items'           => $items
        );


        if(isset($arParams['optionsSites']) && is_array($arParams['optionsSites'])
                && in_array($arFields['LID'], $arParams['optionsSites']))
            $resOrder['site'] = $arFields['LID'];
        
        // parse fio
        if(count($contactNameArr) == 1) {
            $resOrder['firstName'] = $contactNameArr[0];
        } else {
            $resOrder['lastName'] = $contactNameArr[0];
            $resOrder['firstName'] = $contactNameArr[1];
            $resOrder['patronymic'] = $contactNameArr[2];
        }
       
        $resOrder = self::clearArr($resOrder);

        if($send)
            return $api->orderEdit($resOrder);
        
        return array(
            'order'    => $resOrder,
            'customer' => $customer
        );
    }

    /**
     * removes all empty fields from arrays
     * working with nested arrs
     *
     * @param type $arr
     * @return boolean
     */
    public static function clearArr($arr) {
        if(!$arr || !is_array($arr))
            return false;

        foreach($arr as $key => $value) {
            if(!$value || (is_array($value) && empty($value)))
                unset($arr[$key]);

            if(is_array($value) && !empty($value))
                $arr[$key] = self::clearArr($value);
        }

        return $arr;
    }

    /**
     *
     * @global type $APPLICATION
     * @param type $str in SITE_CHARSET
     * @return type $str in utf-8
     */
    public static function toJSON($str) {
        global $APPLICATION;

        return $APPLICATION->ConvertCharset($str, SITE_CHARSET, 'utf-8');
    }

    /**
     *
     * @global type $APPLICATION
     * @param type $str in utf-8
     * @return type $str in SITE_CHARSET
     */
    public static function fromJSON($str) {
        global $APPLICATION;

        return $APPLICATION->ConvertCharset($str, 'utf-8', SITE_CHARSET);
    }

    public static function explodeFIO($str) {
        if(!$str)
            return array();

        $array = explode(" ", self::toJSON($str), 3);
        $newArray = array();

        foreach($array as $ar) {
            if(!$ar)
                continue;
            
            $newArray[] = $ar;
        }
        
        return $newArray;
    }
    
    public static function addOrderProperty($code, $value, $order) {
        if (!$code)
            return;

        if (!CModule::IncludeModule('sale'))
            return;
        
        if ($arProp = CSaleOrderProps::GetList(array(), array('CODE' => $code))->Fetch()) {
            return CSaleOrderPropsValue::Add(array(
                        'NAME' => $arProp['NAME'],
                        'CODE' => $arProp['CODE'],
                        'ORDER_PROPS_ID' => $arProp['ID'],
                        'ORDER_ID' => $order,
                        'VALUE' => $value,
            ));
        }
    }
    
    public static function getLocationCityId($cityName) {
        if(!$cityName)
            return;
        
        $dbLocation = CSaleLocation::GetList(
                        array(
                            "SORT" => "ASC",
                            "CITY_NAME_LANG" => "ASC"
                        ), 
                        array("LID" => "ru", "CITY_NAME" => $cityName), false, false, array());
        
        if($location = $dbLocation->Fetch())
                return $location['ID'];
    }
}