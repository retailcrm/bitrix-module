<?php
/**
 * RCrmEvent
 */
use \Bitrix\Main\Event;
class RetailCrmEvent
{    
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
    function OnAfterUserUpdate($arFields)
    {        
        if (isset($GLOBALS['RETAIL_CRM_HISTORY']) && $GLOBALS['RETAIL_CRM_HISTORY']) {
            return;
        }
        
        if (!$arFields['RESULT']) {
            return;
        }
        
        $api_host = COption::GetOptionString(self::$MODULE_ID, self::$CRM_API_HOST_OPTION, 0);
        $api_key = COption::GetOptionString(self::$MODULE_ID, self::$CRM_API_KEY_OPTION, 0);
        $api = new RetailCrm\ApiClient($api_host, $api_key);
        
        $optionsSitesList = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_SITES_LIST, 0));
        
        $resultOrder = RetailCrmUser::customerEdit($arFields, $api, $optionsSitesList);
        if (!$resultOrder) {
            RCrmActions::eventLog('RetailCrmEvent::OnAfterUserUpdate', 'RetailCrmUser::customerEdit', 'error update customer');
        }

        return true; 
    }    
    
    /**
     * onUpdateOrder
     * 
     * @param mixed $ID - Order id  
     * @param mixed $arFields - Order arFields
     */
    function onUpdateOrder($ID, $arFields)
    {
        if (isset($GLOBALS['RETAIL_CRM_HISTORY']) && $GLOBALS['RETAIL_CRM_HISTORY']) {
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
    function orderDelete($event)
    {
        $GLOBALS['RETAILCRM_ORDER_DELETE'] = true;

        return;
    }
    
    /**
     * orderSave
     * 
     * @param object $event - Order object
     */

    function orderSave($event)
    {
        if ($GLOBALS['RETAILCRM_ORDER_OLD_EVENT'] !== false && $GLOBALS['RETAIL_CRM_HISTORY'] !== true && $GLOBALS['RETAILCRM_ORDER_DELETE'] !== true) {
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

           //exists getParameter("ENTITY")
            if (method_exists($event, 'getId')) {
                $obOrder = $event;
            } elseif (method_exists($event, 'getParameter')) {
                $obOrder = $event->getParameter("ENTITY");
            } else {
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
             
            //many sites?
            if ($optionsSitesList) {
                if (array_key_exists($arOrder['LID'], $optionsSitesList) && $optionsSitesList[$arOrder['LID']] !== null) {
                    $site = $optionsSitesList[$arOrder['LID']];
                } else {
                    return;
                }
            } elseif (!$optionsSitesList) {
                $site = null;
            }

            //new order?
            $orderCrm = RCrmActions::apiMethod($api, 'ordersGet', __METHOD__, $arOrder['ID'], $site);
            if (isset($orderCrm['order'])) {
                $methodApi = 'ordersEdit';
                $arParams['crmOrder'] = $orderCrm['order'];
            } else {
                $methodApi = 'ordersCreate';
                $GLOBALS['RETAILCRM_ORDER_NEW_ORDER'] = true;
            }

            //user
            $userCrm = RCrmActions::apiMethod($api, 'customersGet', __METHOD__, $arOrder['USER_ID'], $site);
            if (!isset($userCrm['customer'])) {
                $arUser = Bitrix\Main\UserTable::getById($arOrder['USER_ID'])->fetch();
                $resultUser = RetailCrmUser::customerSend($arUser, $api, $optionsContragentType[$arOrder['PERSON_TYPE_ID']], true, $site);
                if (!$resultUser) {
                    RCrmActions::eventLog('RetailCrmEvent::orderSave', 'RetailCrmUser::customerSend', 'error during creating customer');

                    return true;
                }
            }

            //order
            $resultOrder = RetailCrmOrder::orderSend($arOrder, $api, $arParams, true, $site, $methodApi);
            if (!$resultOrder) {
                RCrmActions::eventLog('RetailCrmEvent::orderSave', 'RetailCrmOrder::orderSend', 'error during creating order');

                return true;
            }

            return true;
        }

        return true;
    }

    /**
     * paymentSave
     * 
     * @param object $event - Payment object
     */
    function paymentSave($event)
    {
        $apiVersion = COption::GetOptionString(self::$MODULE_ID, 'api_version', 0);

        if ((isset($GLOBALS['RETAIL_CRM_HISTORY']) && $GLOBALS['RETAIL_CRM_HISTORY']) || $apiVersion != 'v5') {
            return;
        }

        $optionsSitesList = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_SITES_LIST, 0));
        $optionsPaymentTypes = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_PAYMENT_TYPES, 0));
        $optionsPayStatuses = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_PAYMENT, 0));

        $arPayment = array(
            'ID'            => $event->getId(),
            'ORDER_ID'      => $event->getField('ORDER_ID'),
            'PAID'          => $event->getField('PAID'),
            'PAY_SYSTEM_ID' => $event->getField('PAY_SYSTEM_ID'),
            'SUM'           => $event->getField('SUM'),
            'LID'           => $event->getField('LID'),
            'DATE_PAID'     => $event->getField('DATE_PAID'),
            'METHOD'        => $GLOBALS['RETAILCRM_ORDER_NEW_ORDER'],
        );

        try {
            $newOrder = Bitrix\Sale\Order::load($arPayment['ORDER_ID']);
            $arPayment['LID'] = $newOrder->getField('LID');
        } catch (Bitrix\Main\ArgumentNullException $e) {
            RCrmActions::eventLog('RetailCrmEvent::paymentSave', 'Bitrix\Sale\Order::load', $e->getMessage() . ': ' . $arPayment['ORDER_ID']);
            return;
        }

        if ($optionsSitesList) {
            if (array_key_exists($arPayment['LID'], $optionsSitesList) && $optionsSitesList[$arPayment['LID']] !== null) {
                $site = $optionsSitesList[$arPayment['LID']];
            } else {
                return;
            }
        } elseif (!$optionsSitesList) {
            $site = null;
        }

        $api_host = COption::GetOptionString(self::$MODULE_ID, self::$CRM_API_HOST_OPTION, 0);
        $api_key = COption::GetOptionString(self::$MODULE_ID, self::$CRM_API_KEY_OPTION, 0);
        $api = new RetailCrm\ApiClient($api_host, $api_key);
        $orderCrm = RCrmActions::apiMethod($api, 'ordersGet', __METHOD__, $arPayment['ORDER_ID'], $site);

        if (isset($orderCrm['order'])) {
            $payments = $orderCrm['order']['payments'];
        }

        if ($arPayment['METHOD'] === true) {
            if ($payments) {
                foreach ($payments as $payment) {
                    if (!isset($payment['externalId'])) {
                        if ($payment['type'] == $optionsPaymentTypes[$arPayment['PAY_SYSTEM_ID']]) {
                            $payment['externalId'] = $arPayment['ID'];
                            RCrmActions::apiMethod($api, 'paymentEditById', __METHOD__, $payment, $site);
                        }
                    }
                }
            }
        } else {
            if ($payments) {
                foreach ($payments as $payment) {
                    if (isset($payment['externalId'])) {
                        $paymentsExternalIds[$payment['externalId']] = $payment;
                    }
                }
            }

            if (!empty($arPayment['PAY_SYSTEM_ID']) && isset($optionsPaymentTypes[$arPayment['PAY_SYSTEM_ID']])) {
                $paymentToCrm = array(
                    'type' => $optionsPaymentTypes[$arPayment['PAY_SYSTEM_ID']],
                    'amount' => $arPayment['SUM']
                );

                if (!empty($arPayment['ID'])) {
                    $paymentToCrm['externalId'] = $arPayment['ID'];
                }

                if (!empty($arPayment['DATE_PAID'])) {
                    if (is_object($arPayment['DATE_PAID'])) {
                        $culture = new Bitrix\Main\Context\Culture(array("FORMAT_DATETIME" => "YYYY-MM-DD HH:MI:SS"));
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
            } else {
                RCrmActions::eventLog('RetailCrmEvent::paymentSave', 'payments', 'OrderID = ' . $arPayment['ID'] . '. Payment not found.');
                return;
            }

            if (!array_key_exists($arPayment['ID'], $paymentsExternalIds)) {
                RCrmActions::apiMethod($api, 'ordersPaymentCreate', __METHOD__, $paymentToCrm, $site);
            } elseif (array_key_exists($arPayment['ID'], $paymentsExternalIds) && $paymentsExternalIds[$arPayment['ID']]['type'] == $optionsPaymentTypes[$arPayment['PAY_SYSTEM_ID']]) {
                RCrmActions::apiMethod($api, 'paymentEditByExternalId', __METHOD__, $paymentToCrm, $site);
            } elseif (array_key_exists($arPayment['ID'], $paymentsExternalIds) && $paymentsExternalIds[$arPayment['ID']]['type'] != $optionsPaymentTypes[$arPayment['PAY_SYSTEM_ID']]) {
                RCrmActions::apiMethod($api, 'ordersPaymentDelete', __METHOD__, $paymentsExternalIds[$arPayment['ID']]['id']);
                RCrmActions::apiMethod($api, 'ordersPaymentCreate', __METHOD__, $paymentToCrm, $site);
            }
        }
    }

    /**
     * paymentDelete
     * 
     * @param object $event - Payment object
     */

    function paymentDelete($event)
    {
        $apiVersion = COption::GetOptionString(self::$MODULE_ID, 'api_version', 0);

        if ((isset($GLOBALS['RETAIL_CRM_HISTORY']) && $GLOBALS['RETAIL_CRM_HISTORY']) || $apiVersion != 'v5' || !$event->getId()) {
            return;
        }

        $optionsSitesList = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_SITES_LIST, 0));

        $arPayment = array(
            'ID'            => $event->getId(),
            'ORDER_ID'      => $event->getField('ORDER_ID')
        );

        try {
            $newOrder = Bitrix\Sale\Order::load($arPayment['ORDER_ID']);
            $arPayment['LID'] = $newOrder->getField('LID');
        } catch (Bitrix\Main\ArgumentNullException $e) {
            RCrmActions::eventLog('RetailCrmEvent::paymentDelete', 'Bitrix\Sale\Order::load', $e->getMessage() . ': ' . $arPayment['ORDER_ID']);
            return;
        }

        if ($optionsSitesList) {
            if (array_key_exists($arPayment['LID'], $optionsSitesList) && $optionsSitesList[$arPayment['LID']] !== null) {
                $site = $optionsSitesList[$arPayment['LID']];
            } else {
                return;
            }
        } elseif (!$optionsSitesList) {
            $site = null;
        }

        $api_host = COption::GetOptionString(self::$MODULE_ID, self::$CRM_API_HOST_OPTION, 0);
        $api_key = COption::GetOptionString(self::$MODULE_ID, self::$CRM_API_KEY_OPTION, 0);
        $api = new RetailCrm\ApiClient($api_host, $api_key);
        $orderCrm = RCrmActions::apiMethod($api, 'ordersGet', __METHOD__, $arPayment['ORDER_ID'], $site);

        if (isset($orderCrm['order']['payments']) && $orderCrm['order']['payments']) {
            foreach ($orderCrm['order']['payments'] as $payment) {
                if (isset($payment['externalId']) && $payment['externalId'] == $event->getId()) {
                    RCrmActions::apiMethod($api, 'ordersPaymentDelete', __METHOD__, $payment['id']);
                }
            }
        }
    }
}