<?php
/**
 * RCrmEvent
 */

class RetailCrmEvent {    
    protected static $MODULE_ID = 'intaro.retailcrm';
    protected static $CRM_API_HOST_OPTION = 'api_host';
    protected static $CRM_API_KEY_OPTION = 'api_key';
    protected static $CRM_ORDER_TYPES_ARR = 'order_types_arr';
    protected static $CRM_DELIVERY_TYPES_ARR = 'deliv_types_arr';
    protected static $CRM_PAYMENT_TYPES = 'pay_types_arr';
    protected static $CRM_PAYMENT_STATUSES = 'pay_statuses_arr';
    protected static $CRM_PAYMENT = 'payment_arr'; //order payment Y/N
    protected static $CRM_ORDER_LAST_ID = 'order_last_id';
    protected static $CRM_ORDER_PROPS = 'order_props';
    protected static $CRM_LEGAL_DETAILS = 'legal_details';
    protected static $CRM_CUSTOM_FIELDS = 'custom_fields';
    protected static $CRM_CONTRAGENT_TYPE = 'contragent_type';
    protected static $CRM_ORDER_FAILED_IDS = 'order_failed_ids';
    protected static $CRM_SITES_LIST = 'sites_list';
    
    /**
     * OnAfterUserUpdate
     * 
     * @param mixed $arFields - User arFields
     */
    function OnAfterUserUpdate($arFields) {        
        if(isset($GLOBALS['RETAIL_CRM_HISTORY']) && $GLOBALS['RETAIL_CRM_HISTORY']){
            return;
        }
        
        if(!$arFields['RESULT']){
            return;
        }
        
        $customer = array(
            'externalId'     => $arFields['ID'],
            'firstName'      => $arFields['NAME'],
            'lastName'       => $arFields['LAST_NAME'],
            'patronymic'     => $arFields['SECOND_NAME'],
            'email'          => $arFields['EMAIL']
        );
        if(isset($arFields['PERSONAL_PHONE'])){
            $customer['phones'][]['number'] = $arFields['PERSONAL_PHONE'];
        }
        if(isset($arFields['WORK_PHONE'])){
            $customer['phones'][]['number'] = $arFields['WORK_PHONE'];
        }
        
        if (function_exists('retailcrmBeforeCustomerSend')) {
            $newResCustomer = intarocrm_before_customer_send($customer);
            if (is_array($newResCustomer) && !empty($newResCustomer)) {
                $customer = $newResCustomer;
            }
        }
        $api_host = COption::GetOptionString(self::$MODULE_ID, self::$CRM_API_HOST_OPTION, 0);
        $api_key = COption::GetOptionString(self::$MODULE_ID, self::$CRM_API_KEY_OPTION, 0);

        $api = new RetailCrm\ApiClient($api_host, $api_key);
        
        //ищем юзера по id и др. данным.
        //апдейтим если находим
    }
    
    /**
     * onBeforeOrderAdd
     * 
     * @param mixed $arFields - User arFields
     */
    function onBeforeOrderAdd($arFields = array()) {
        $GLOBALS['RETAILCRM_ORDER_OLD_EVENT'] = false;
        return;
    }
    
    /**
     * OnOrderSave
     * 
     * @param mixed $ID - Order id  
     * @param mixed $arFields - Order arFields
     */
    function OnOrderSave($ID, $arFields, $arOrder, $isNew) {
        $GLOBALS['RETAILCRM_EVENT_OLD'] = true;
        return;
    }
    
    /**
     * onUpdateOrder
     * 
     * @param mixed $ID - Order id  
     * @param mixed $arFields - Order arFields
     */
    function onUpdateOrder($ID, $arFields) {
        if(isset($GLOBALS['RETAIL_CRM_HISTORY']) && $GLOBALS['RETAIL_CRM_HISTORY']){
            $GLOBALS['RETAILCRM_ORDER_OLD_EVENT'] = false;            
            return;
        }  
        
        $GLOBALS['RETAILCRM_ORDER_OLD_EVENT'] = true;        
        return;
    }
    
    /**
     * orderDelete
     * 
     * @param object $event - Order object
     */
    function orderDelete($event){
        $GLOBALS['RETAILCRM_ORDER_DELETE'] = true; 
        return;
    }
    
    /**
     * orderSave
     * 
     * @param object $event - Order object
     */

    function orderSave($event){
        if($GLOBALS['RETAILCRM_ORDER_OLD_EVENT'] !== false && $GLOBALS['RETAIL_CRM_HISTORY'] !== true && $GLOBALS['RETAILCRM_ORDER_DELETE'] !== true){

            if (!CModule::IncludeModule('iblock')) {
                RCrmActions::eventLog('RetailCrmEvent::orderSave', 'iblock', 'module not found');
                return true;
            }

            if (!CModule::IncludeModule("sale")) {
                RCrmActions::eventLog('RetailCrmEvent::orderSave', 'sale', 'module not found');
                return true;
            }

            if (!CModule::IncludeModule("catalog")) {
                RCrmActions::eventLog('RetailCrmEvent::orderSave', 'catalog', 'module not found');
                return true;
            }

           //проверка на существование getParameter("ENTITY")
            if(method_exists($event, 'getId')){
                $obOrder = $event;
            }
            elseif(method_exists($event, 'getParameter')){
                $obOrder = $event->getParameter("ENTITY");
            }
            else{
                RCrmActions::eventLog('RetailCrmEvent::orderSave', 'events', 'event error');
                return true;
            }

            $arOrder = RetailCrmOrder::orderObjToArr($obOrder);

            //api
            $api_host = COption::GetOptionString(self::$MODULE_ID, self::$CRM_API_HOST_OPTION, 0);
            $api_key = COption::GetOptionString(self::$MODULE_ID, self::$CRM_API_KEY_OPTION, 0);
            $api = new RetailCrm\ApiClient($api_host, $api_key);

            //params
            $optionsOrderTypes = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_ORDER_TYPES_ARR, 0));
            $optionsDelivTypes = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_DELIVERY_TYPES_ARR, 0));
            $optionsPayTypes = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_PAYMENT_TYPES, 0));
            $optionsPayStatuses = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_PAYMENT_STATUSES, 0)); // --statuses
            $optionsPayment = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_PAYMENT, 0));
            $optionsSitesList = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_SITES_LIST, 0));
            $optionsOrderProps = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_ORDER_PROPS, 0));
            $optionsLegalDetails = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_LEGAL_DETAILS, 0));
            $optionsContragentType = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_CONTRAGENT_TYPE, 0));
            $optionsCustomFields = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_CUSTOM_FIELDS, 0));  

            $arParams = RCrmActions::clearArr(array(
                'optionsOrderTypes'     => $optionsOrderTypes,
                'optionsDelivTypes'     => $optionsDelivTypes,
                'optionsPayTypes'       => $optionsPayTypes,
                'optionsPayStatuses'    => $optionsPayStatuses,
                'optionsPayment'        => $optionsPayment,
                'optionsOrderProps'     => $optionsOrderProps,
                'optionsLegalDetails'   => $optionsLegalDetails,
                'optionsContragentType' => $optionsContragentType,
                'optionsSitesList'      => $optionsSitesList,
                'optionsCustomFields'   => $optionsCustomFields
            ));
             
            //многосайтовость
            $site = count($optionsSitesList)>1 ? $optionsSitesList[$arOrder['LID']] : null;
            
            //проверка на новый заказ
            $orderCrm = RCrmActions::apiMethod($api, 'ordersGet', __METHOD__, $arOrder['ID'], $site);
            if(isset($orderCrm['order'])){
                $methodApi = 'ordersEdit';
            }
            else{
                $methodApi = 'ordersCreate';
            }

            //user
            $userCrm = RCrmActions::apiMethod($api, 'customersGet', __METHOD__, $arOrder['USER_ID'], $site);
            if(!isset($userCrm['customer'])){
                $arUser = Bitrix\Main\UserTable::getById($arOrder['USER_ID'])->fetch();
                $resultUser = RetailCrmUser::customerSend($arUser, $api, $optionsContragentType[$arOrder['PERSON_TYPE_ID']], true, $site);
                if(!$resultUser) {
                    RCrmActions::eventLog('RetailCrmEvent::orderSave', 'RetailCrmUser::customerSend', 'error during creating customer');
                    return true;
                }
            }

            //order
            $resultOrder = RetailCrmOrder::orderSend($arOrder, $api, $arParams, true, $site, $methodApi);
            if(!$resultOrder) {
                RCrmActions::eventLog('RetailCrmEvent::orderSave', 'RetailCrmOrder::orderSend', 'error during creating order');
                return true;
            }

            return true;
        }
        
        return;
    }
}