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

    /**
     * Mass order uploading, without repeating; always returns true, but writes error log
     * @return boolean
     */
    public static function uploadOrders($pSize = 50) {

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

        $dbOrder = CSaleOrder::GetList(array("ID" => "ASC"), array('>ID' => $lastUpOrderId));

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
            'optionsOrderTypes'  => $optionsOrderTypes,
            'optionsDelivTypes'  => $optionsDelivTypes,
            'optionsPayTypes'    => $optionsPayTypes,
            'optionsPayStatuses' => $optionsPayStatuses,
            'optionsPayment'     => $optionsPayment,
            'optionSites'        => $optionsSites,
            'optionsOrderProps'  => $optionsOrderProps
        );

        //packmode
		
        $orderCount = 0;

        while ($arOrder = $dbOrder->GetNext()) { // here orders by id asc
            
            if(is_array($optionsSites)) 
                if(!empty($optionsSites)) 
                    if(!in_array($arOrder['LID'], $optionsSites))
                        continue;
                
            $result = self::orderCreate($arOrder, $api, $arParams);

            if (!$result['order'] || !$result['customer'])
                 continue;
            
            $orderCount++;
                
            $resOrders[] = $result['order'];
            $resCustomers[] = $result['customer'];

            $lastOrderId = $arOrder['ID'];

            if($orderCount >= $pSize) {
                $customers = $api->customerUpload($resCustomers);
                    
            // error pushing customers
            if ($api->getStatusCode() != 201) {
                //handle err
                //self::eventLog('ICrmOrderActions::uploadOrders', 'IntaroCrm\RestApi::customerUpload', $api->getLastError());
                        
                if($api->getStatusCode() != 460) // some orders were sent
                    return false; // in pack mode return errors
                }
                    
                $orders = $api->orderUpload($resOrders);

                // error pushing orders
                if ($api->getStatusCode() != 201) {
                    //handle err
                    self::eventLog('ICrmOrderActions::uploadOrders', 'IntaroCrm\RestApi::orderUpload', $api->getLastError());

                    if($api->getStatusCode() != 460) // some orders were sent
                        return false; // in pack mode return errors
                }
                    
                if($lastOrderId)
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
        
        if($lastOrderId)
            COption::SetOptionString(self::$MODULE_ID, self::$CRM_ORDER_LAST_ID, $lastOrderId);

        return true; //all ok!
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
        return 'ICrmOrderActions::uploadOrdersAgent();';
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

        $phonePersonal = array(
            'number' => self::toJSON($arUser['PERSONAL_PHONE']),
            'type'   => 'mobile'
        );
        $phones[] = $phonePersonal;

        $phoneWork = array(
            'number' => self::toJSON($arUser['WORK_PHONE']),
            'type'   => 'work'
        );
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
                case $arParams['optionsOrderProps']['index']: $resOrderDeliveryAddress['index'] = self::toJSON($ar['VALUE']);
                    break;
                case 'CITY': $resOrderDeliveryAddress['city'] = self::toJSON($ar['VALUE']);
                    break;
                case $arParams['optionsOrderProps']['text']: $resOrderDeliveryAddress['text'] = self::toJSON($ar['VALUE']);
                    break;
                case 'LOCATION': if(!isset($resOrderDeliveryAddress['city']) && !$resOrderDeliveryAddress['city']) {
                        $resOrderDeliveryAddress['city'] = CSaleLocation::GetByID($ar['VALUE']);
                        $resOrderDeliveryAddress['city'] = self::toJSON($resOrderDeliveryAddress['city']['CITY_NAME_LANG']);
                    }
                    break;
                case $arParams['optionsOrderProps']['fio']:$contactNameArr = self::explodeFIO($ar['VALUE']);
                    break;
                case $arParams['optionsOrderProps']['phone']: $resOrder['phone'] = $ar['VALUE'];
                    break;
                case $arParams['optionsOrderProps']['email']: $resOrder['email'] = $ar['VALUE'];
                    break;
            }
            
            if (count($arParams['optionsOrderProps'] > 5)) {
                switch ($ar['CODE']) {
                    /*case $arParams['optionsOrderProps']['country']: $resOrderDeliveryAddress['country'] = self::toJSON($ar['VALUE']);
                        break;
                    case $arParams['optionsOrderProps']['region']: $resOrderDeliveryAddress['region'] = self::toJSON($ar['VALUE']);
                        break;
                    case $arParams['optionsOrderProps']['city']: $resOrderDeliveryAddress['city'] = self::toJSON($ar['VALUE']);
                        break; */
                    case $arParams['optionsOrderProps']['street']: $resOrderDeliveryAddress['street'] = self::toJSON($ar['VALUE']);
                        break;
                    case $arParams['optionsOrderProps']['building']: $resOrderDeliveryAddress['building'] = self::toJSON($ar['VALUE']);
                        break;
                    case $arParams['optionsOrderProps']['flat']: $resOrderDeliveryAddress['flat'] = self::toJSON($ar['VALUE']);
                        break;
                    case $arParams['optionsOrderProps']['inercomcode']: $resOrderDeliveryAddress['intercomcode'] = self::toJSON($ar['VALUE']);
                        break;
                    case $arParams['optionsOrderProps']['floor']: $resOrderDeliveryAddress['floor'] = self::toJSON($ar['VALUE']);
                        break;
                    case $arParams['optionsOrderProps']['block']: $resOrderDeliveryAddress['block'] = self::toJSON($ar['VALUE']);
                        break;
                    case $arParams['optionsOrderProps']['house']: $resOrderDeliveryAddress['house'] = self::toJSON($ar['VALUE']);
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

            if($p['DISCOUNT_VALUE'])
                $p['DISCOUNT_PRICE'] = null;

            $items[] = array(
                'initialPrice'    => (double) $p['PRICE'] + (double) $p['DISCOUNT_PRICE'],
                'purchasePrice'   => $pr,
                'discount'        => $p['DISCOUNT_PRICE'],
                'discountPercent' => $p['DISCOUNT_VALUE'],
                'quantity'        => $p['QUANTITY'],
                'productId'       => $p['PRODUCT_ID'],
                'productName'     => self::toJSON($p['NAME'])
            );
        }

        if($arFields['CANCELED'] == 'Y')
            $arFields['STATUS_ID'] = $arFields['CANCELED'];

        $createdAt = new \DateTime($arFields['DATE_INSERT']);
        $createdAt = $createdAt->format('Y-m-d H:i:s');

        $resOrder = array(
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
            'createdAt'       => $createdAt,
            'deliveryAddress' => $resOrderDeliveryAddress,
            'items'           => $items
        );
        
        // parse fio
        if(count($contactNameArr) == 1) {
            $resOrder['firstName'] = $contactNameArr[0];
        } else {
            $resOrder['lastName'] = $contactNameArr['contactName'][0];
            $resOrder['firstName'] = $contactNameArr['contactName'][1];
            $resOrder['patronymic'] = $contactNameArr['contactName'][2];
        }

        if(isset($arParams['optionsSites']) && is_array($arParams['optionsSites'])
                && in_array($arFields['LID'], $arParams['optionsSites']))
            $resOrder['site'] = $arFields['LID'];
        $resOrder = self::clearArr($resOrder);
        
        if($send)
            $api->createOrder($resOrder);
        
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
}