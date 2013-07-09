<?php
//namespace IntaroCrm;

class ICrmOrderActions
{
    public static $MODULE_ID = 'intaro.crm';
    public static $CRM_API_HOST_OPTION = 'api_host';
    public static $CRM_API_KEY_OPTION = 'api_key';
    public static $CRM_ORDER_TYPES_ARR = 'order_types_arr';
    public static $CRM_DELIVERY_TYPES_ARR = 'deliv_types_arr';
    public static $CRM_PAYMENT_TYPES = 'pay_types_arr';
    public static $CRM_PAYMENT_STATUSES = 'pay_statuses_arr';
    public static $CRM_PAYMENT = 'payment_arr'; //order payment Y/N
    public static $CRM_ORDER_LAST_ID = 'order_last_id';
    
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
    
    /**
     * Mass order uploading func
     */
    function uploadOrders() {
        
        //ini_set('display_errors', 1);
        //error_reporting(E_ALL);
        
        COption::SetOptionString(self::$MODULE_ID, self::$CRM_ORDER_LAST_ID, 0);
        
        if (!CModule::IncludeModule('iblock')) {
            //ShowError(GetMessage("SOA_MODULE_NOT_INSTALL"));
            return;
        }
        if (!CModule::IncludeModule("sale")) {
            //ShowError(GetMessage("SOA_MODULE_NOT_INSTALL"));
            return;
        }

        $resOrders = array();
     
        $dbOrder = CSaleOrder::GetList();
        
        $lastUpOrderId = COption::GetOptionString(self::$MODULE_ID, self::$CRM_ORDER_LAST_ID, 0);
        $lastUpOrderIdNew = 0;
        
        $api_host = COption::GetOptionString(self::$MODULE_ID, self::$CRM_API_HOST_OPTION, 0);
        $api_key = COption::GetOptionString(self::$MODULE_ID, self::$CRM_API_KEY_OPTION, 0);

        //saved cat params
        $optionsOrderTypes = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_ORDER_TYPES_ARR, 0));
        $optionsDelivTypes = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_DELIVERY_TYPES_ARR, 0));
        $optionsPayTypes = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_PAYMENT_TYPES, 0));
        $optionsPayStatuses = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_PAYMENT_STATUSES, 0)); // --statuses
        $optionsPayment = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_PAYMENT, 0));

        $api = new IntaroCrm\RestApi($api_host, $api_key);

        while ($arOrder = $dbOrder->GetNext()) {
            if ($arOrder['ID'] < $lastUpOrderId) //old orders not to upload
                break;
            
            if(!$lastUpOrderIdNew)
                $lastUpOrderIdNew = $arOrder['ID'];
            
            $arFields = CSaleOrder::GetById($arOrder['ID']);

            if (empty($arFields)) { 
                //handle err
                CEventLog::Add(array(
                    "SEVERITY"      => "SECURITY",
                    "AUDIT_TYPE_ID" => "IntroCrm\uploadOrders",
                    "MODULE_ID"     => self::$MODULE_ID,
                    "ITEM_ID"       => 'empty($arFields)',
                    "DESCRIPTION"   => "Не корректный заказ.",
                ));
                
                return;
            }

            $rsUser = CUser::GetByID($arFields['USER_ID']);
            $arUser = $rsUser->Fetch();

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

            $addressPersonal = array(
                'index'    => self::toJSON($arUser['PERSONAL_ZIP']),
                'country'  => self::toJSON(GetCountryByID($arUser['PERSONAL_COUNTRY'])),
                'city'     => self::toJSON($arUser['PERSONAL_CITY']),
                'street'   => self::toJSON($arUser['PERSONAL_STREET']),
                'building' => self::toJSON($arUser['UF_PERSONAL_BUILDING']),
                'flat'     => self::toJSON($arUser['UF_PERSONAL_FLAT']),
                'notes'    => self::toJSON($arUser['PERSONAL_NOTES']),
                'text'     => self::toJSON($arUser['UF_PERSONAL_TEXT']),
                'type'     => 'home'
            );
            $addresses[] = $addressPersonal;

            $addressWork = array(
                'index'    => self::toJSON($arUser['WORK_ZIP']),
                'country'  => self::toJSON(GetCountryByID($arUser['WORK_COUNTRY'])),
                'city'     => self::toJSON($arUser['WORK_CITY']),
                'street'   => self::toJSON($arUser['WORK_STREET']),
                'building' => self::toJSON($arUser['UF_WORK_BUILDING']), // -- 
                'flat'     => self::toJSON($arUser['UF_WORK_FLAT']),
                'notes'    => self::toJSON($arUser['PERSONAL_NOTES']),
                'text'     => self::toJSON($arUser['UF_WORK_TEXT']),
                'type'     => 'work'
            );
            $addresses[] = $addressWork;

            $result = array(
                'externalId'         => $arFields['USER_ID'],
                'lastName'   => $lastName,
                'firstName'  => $firstName,
                'patronymic' => $patronymic,
                'phones'     => $phones,
                'addresses'  => $addresses
            );

            $customer = $api->customerEdit($result);
            
            // error pushing customer
            if (!$customer) {
                // handle
                CEventLog::Add(array(
                    "SEVERITY"      => "SECURITY",
                    "AUDIT_TYPE_ID" => "IntroCrm\uploadOrders",
                    "MODULE_ID"     => self::$MODULE_ID,
                    "ITEM_ID"       => "customerEdit",
                    "DESCRIPTION"   => $api->getLastError(),
                ));
                
                return;
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

            $rsOrderProps = CSaleOrderPropsValue::GetList(array(), array('ORDER_ID' => $arFields['ID']));
            while ($ar = $rsOrderProps->Fetch()) {
                switch ($ar['CODE']) {
                    case 'ZIP': $resOrderDeliveryAddress['index'] = self::toJSON($ar['VALUE']);
                        break;
                    case 'CITY': $resOrderDeliveryAddress['city'] = self::toJSON($ar['VALUE']);
                        break;
                    case 'ADDRESS': $resOrderDeliveryAddress['text'] = self::toJSON($ar['VALUE']);
                        break;
                    case 'FIO': $resOrder['contactName'] = self::toJSON($ar['VALUE']);
                        break;
                    case 'PHONE': $resOrder['phone'] = $ar['VALUE'];
                        break;
                    case 'EMAIL': $resOrder['email'] = $ar['VALUE'];
                        break;
                }
            }

            $resOrder['deliveryCost'] = $arFields['PRICE_DELIVERY'];
            $resOrder['summ'] = $arFields['PRICE'];
            $resOrder['markDateTime'] = $arFields['DATE_MARKED'];
            $resOrder['externalId'] = $arFields['ID'];
            $resOrder['customerId'] = $arFields['USER_ID'];

            $resOrder['paymentType'] = $optionsPayTypes[$arFields['PAY_SYSTEM_ID']];
            $resOrder['paymentStatus'] = $optionsPayment[$arFields['PAYED']];
            $resOrder['orderType'] = $optionsOrderTypes[$arFields['PERSON_TYPE_ID']];
            $resOrder['deliveryType'] = $optionsDelivTypes[$resultDeliveryTypeId];
            $resOrder['status'] = $optionsPayStatuses[$arFields['STATUS_ID']];

            $resOrder['deliveryAddress'] = $resOrderDeliveryAddress;

            $items = array();

            $rsOrderBasket = CSaleBasket::GetList(array('PRODUCT_ID' => 'ASC'), array('ORDER_ID' => $arFields['ID']));
            while ($p = $rsOrderBasket->Fetch()) {
                $items[] = array(
                    'price'       => $p['PRICE'],
                    'discount'    => $p['DISCOUNT_VALUE'],
                    'quantity'    => $p['QUANTITY'],
                    'productId'   => $p['PRODUCT_ID'],
                    'productName' => self::toJSON($p['NAME'])
                );
            }

            $resOrder['items'] = $items;
            $resOrders[] = $resOrder;
        }
        
        $orders = $api->orderUpload($resOrders);
        
        // error pushing orders
        if(!$orders) {
            //handle err
            CEventLog::Add(array(
                "SEVERITY"      => "SECURITY",
                "AUDIT_TYPE_ID" => "IntroCrm\uploadOrders",
                "MODULE_ID"     => self::$MODULE_ID,
                "ITEM_ID"       => "orderUpload",
                "DESCRIPTION"   => $api->getLastError(),
            ));
            
            return;
        }
        
        COption::SetOptionString(self::$MODULE_ID, self::$CRM_ORDER_LAST_ID, $lastUpOrderIdNew);
        
        return; //all ok!
    }
}

?>
