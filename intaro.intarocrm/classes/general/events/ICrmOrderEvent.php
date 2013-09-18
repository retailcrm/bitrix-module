<?php
/**
 * OrderEvent
 */
class ICrmOrderEvent {
    
    protected static $MODULE_ID = 'intaro.intarocrm';
    protected static $CRM_API_HOST_OPTION = 'api_host';
    protected static $CRM_API_KEY_OPTION = 'api_key';
    protected static $CRM_ORDER_TYPES_ARR = 'order_types_arr';
    protected static $CRM_DELIVERY_TYPES_ARR = 'deliv_types_arr';
    protected static $CRM_PAYMENT_TYPES = 'pay_types_arr';
    protected static $CRM_PAYMENT_STATUSES = 'pay_statuses_arr';
    protected static $CRM_PAYMENT = 'payment_arr'; //order payment Y/N
    protected static $CRM_ORDER_LAST_ID = 'order_last_id';
    protected static $CRM_ORDER_PROPS = 'order_props';
    
    /**
     * onBeforeOrderAdd
     * 
     * @param mixed $arFields - Order arFields
     */
    function onBeforeOrderAdd($arFields = array()) {
        $GLOBALS['INTARO_CRM_ORDER_ADD'] = true;
        return;
    }
    
    /**
     * onUpdateOrder
     * 
     * @param mixed $ID - Order id  
     * @param mixed $arFields - Order arFields
     */
    function onUpdateOrder($ID, $arFields = array()) {
        if(isset($GLOBALS['INTARO_CRM_ORDER_ADD']) && $GLOBALS['INTARO_CRM_ORDER_ADD'])
            return;
        
        if(isset($GLOBALS['INTARO_CRM_FROM_HISTORY']) && $GLOBALS['INTARO_CRM_FROM_HISTORY'])
            return;
        
        self::writeDataOnOrderCreate($ID);
    }
    

    /**
     * onSendOrderMail
     * in: sale.order.ajax, sale.order.full
     * 
     * @param mixed $ID - Order id
     * @param mixed $eventName - Event type
     * @param mixed $arFields - Order arFields for sending template
     */
    function onSendOrderMail($ID, &$eventName, &$arFields) {
        self::writeDataOnOrderCreate($ID);
        COption::SetOptionString(self::$MODULE_ID, self::$CRM_ORDER_LAST_ID, $ID);
    }

    /**
     * writeDataOnOrderCreate via api
     * 
     * @param integer $ID - Order Id
     */
    function writeDataOnOrderCreate($ID) {
        
        if (!CModule::IncludeModule('iblock')) {
            //handle err
            ICrmOrderActions::eventLog('ICrmOrderEvent::writeDataOnOrderCreate', 'iblock', 'module not found');
            return true;
        }

        if (!CModule::IncludeModule("sale")) {
            //handle err
            ICrmOrderActions::eventLog('ICrmOrderEvent::writeDataOnOrderCreate', 'sale', 'module not found');
            return true;
        }

        if (!CModule::IncludeModule("catalog")) {
            //handle err
            ICrmOrderActions::eventLog('ICrmOrderEvent::writeDataOnOrderCreate', 'catalog', 'module not found');
            return true;
        }
        
        $GLOBALS['INTARO_CRM_ORDER_ADD'] = false;
        $GLOBALS['INTARO_CRM_FROM_HISTORY'] = false;

        $api_host = COption::GetOptionString(self::$MODULE_ID, self::$CRM_API_HOST_OPTION, 0);
        $api_key = COption::GetOptionString(self::$MODULE_ID, self::$CRM_API_KEY_OPTION, 0);

        //saved cat params
        $optionsOrderTypes = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_ORDER_TYPES_ARR, 0));
        $optionsDelivTypes = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_DELIVERY_TYPES_ARR, 0));
        $optionsPayTypes = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_PAYMENT_TYPES, 0));
        $optionsPayStatuses = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_PAYMENT_STATUSES, 0)); // --statuses
        $optionsPayment = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_PAYMENT, 0));
        $optionsOrderProps = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_ORDER_PROPS, 0));

        $api = new IntaroCrm\RestApi($api_host, $api_key);

        $arParams = array(
            'optionsOrderTypes'  => $optionsOrderTypes,
            'optionsDelivTypes'  => $optionsDelivTypes,
            'optionsPayTypes'    => $optionsPayTypes,
            'optionsPayStatuses' => $optionsPayStatuses,
            'optionsPayment'     => $optionsPayment,
            'optionsOrderProps'  => $optionsOrderProps
        );
        
        $arOrder = CSaleOrder::GetById($ID);
        $result = ICrmOrderActions::orderCreate($arOrder, $api, $arParams, true);
        
        if(!$result) {
            ICrmOrderActions::eventLog('ICrmOrderEvent::writeDataOnOrderCreate', 'ICrmOrderActions::orderCreate', 'error during creating order');
            return true;
        }
        
        return true;
    }
    
    /**
     * 
     * @param type $ID -- orderId
     * @param type $cancel -- Y / N - cancel order status
     * @param type $reason -- cancel reason
     * @return boolean
     */
    function onSaleCancelOrder($ID, $cancel, $reason) {
        if(!$ID || !$cancel || ($cancel != 'Y'))
            return true;
        
        if (!CModule::IncludeModule('iblock')) {
            //handle err
            ICrmOrderActions::eventLog('ICrmOrderEvent::onSaleCancelOrder', 'iblock', 'module not found');
            return true;
        }

        if (!CModule::IncludeModule("sale")) {
            //handle err
            ICrmOrderActions::eventLog('ICrmOrderEvent::onSaleCancelOrder', 'sale', 'module not found');
            return true;
        }

        if (!CModule::IncludeModule("catalog")) {
            //handle err
            ICrmOrderActions::eventLog('ICrmOrderEvent::onSaleCancelOrder', 'catalog', 'module not found');
            return true;
        }

        $api_host = COption::GetOptionString(self::$MODULE_ID, self::$CRM_API_HOST_OPTION, 0);
        $api_key = COption::GetOptionString(self::$MODULE_ID, self::$CRM_API_KEY_OPTION, 0);

        //saved cat params
        $optionsPayStatuses = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_PAYMENT_STATUSES, 0)); // --statuses

        $api = new IntaroCrm\RestApi($api_host, $api_key);
        
        $order = array(
            'externalId'    => (int) $ID,
            'status'        => $optionsPayStatuses[$cancel],
            'statusComment' => ICrmOrderActions::toJSON($reason)
        );
        
        $api->orderEdit($order);
 
        // error pushing order
        if ($api->getStatusCode() != 201)
            ICrmOrderActions::eventLog('ICrmOrderEvent::onSaleCancelOrder', 'IntaroCrm\RestApi::orderEdit', $api->getLastError());
        
        return true;
    }
    
    /**
     * 
     * @param type $ID -- orderId
     * @param type $payed -- Y / N - pay order status
     * @return boolean
     */
    function onSalePayOrder($ID, $payed) {
        if(!$ID || !$payed || ($payed != 'Y'))
            return true;
        
        if (!CModule::IncludeModule('iblock')) {
            //handle err
            ICrmOrderActions::eventLog('ICrmOrderEvent::onSalePayOrder', 'iblock', 'module not found');
            return true;
        }

        if (!CModule::IncludeModule("sale")) {
            //handle err
            ICrmOrderActions::eventLog('ICrmOrderEvent::onSalePayOrder', 'sale', 'module not found');
            return true;
        }

        if (!CModule::IncludeModule("catalog")) {
            //handle err
            ICrmOrderActions::eventLog('ICrmOrderEvent::onSalePayOrder', 'catalog', 'module not found');
            return true;
        }

        $api_host = COption::GetOptionString(self::$MODULE_ID, self::$CRM_API_HOST_OPTION, 0);
        $api_key = COption::GetOptionString(self::$MODULE_ID, self::$CRM_API_KEY_OPTION, 0);

        //saved cat params
        $optionsPayment = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_PAYMENT, 0));

        $api = new IntaroCrm\RestApi($api_host, $api_key);
        
        $order = array(
            'externalId'    => (int) $ID,
            'paymentStatus' => $optionsPayment[$payed]
        );
        
        $api->orderEdit($order);
        
 
        // error pushing order
        if ($api->getStatusCode() != 201)
            ICrmOrderActions::eventLog('ICrmOrderEvent::onSalePayOrder', 'IntaroCrm\RestApi::orderEdit', $api->getLastError());
        
        return true;
    }
}