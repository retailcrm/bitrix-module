<?php
/**
 * Class RetailCrmEvent
 */
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

        $api_host = COption::GetOptionString(self::$MODULE_ID, self::$CRM_API_HOST_OPTION, 0);
        $api_key = COption::GetOptionString(self::$MODULE_ID, self::$CRM_API_KEY_OPTION, 0);
        $optionsSitesList = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_SITES_LIST, 0));

        $api = new RetailCrm\ApiClient($api_host, $api_key);
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

        if (($arFields['CANCELED'] == 'Y')
            && (sizeof($arFields['BASKET_ITEMS']) == 0 )
            && (sizeof($arFields['ORDER_PROP']) == 0 )
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
     * @param $event
     *
     * @return bool
     * @throws InvalidArgumentException
     */
    function orderSave($event)
    {
        if (true == $GLOBALS['ORDER_DELETE_USER_ADMIN']) {
            return false;
        }

        if ($GLOBALS['RETAILCRM_ORDER_OLD_EVENT'] === false
            && $GLOBALS['RETAIL_CRM_HISTORY'] === true
            && $GLOBALS['RETAILCRM_ORDER_DELETE'] === true
        ) {
            return false;
        }

        if (!CModule::IncludeModule('iblock')) {
            RCrmActions::eventLog('RetailCrmEvent::orderSave', 'iblock', 'module not found');

            return false;
        }

        if (!CModule::IncludeModule("sale")) {
            RCrmActions::eventLog('RetailCrmEvent::orderSave', 'sale', 'module not found');

            return false;
        }

        if (!CModule::IncludeModule("catalog")) {
            RCrmActions::eventLog('RetailCrmEvent::orderSave', 'catalog', 'module not found');

            return false;
        }

       //exists getParameter("ENTITY")
        if (method_exists($event, 'getId')) {
            $obOrder = $event;
        } elseif (method_exists($event, 'getParameter')) {
            $obOrder = $event->getParameter("ENTITY");
        } else {
            RCrmActions::eventLog('RetailCrmEvent::orderSave', 'events', 'event error');

            return false;
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
                return false;
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
        }

        //user
        $userCrm = RCrmActions::apiMethod($api, 'customersGet', __METHOD__, $arOrder['USER_ID'], $site);
        if (!isset($userCrm['customer'])) {
            $arUser = Bitrix\Main\UserTable::getById($arOrder['USER_ID'])->fetch();
            $resultUser = RetailCrmUser::customerSend($arUser, $api, $optionsContragentType[$arOrder['PERSON_TYPE_ID']], true, $site);
            if (!$resultUser) {
                RCrmActions::eventLog('RetailCrmEvent::orderSave', 'RetailCrmUser::customerSend', 'error during creating customer');

                return false;
            }
        }

        //order
        $resultOrder = RetailCrmOrder::orderSend($arOrder, $api, $arParams, true, $site, $methodApi);
        if (!$resultOrder) {
            RCrmActions::eventLog('RetailCrmEvent::orderSave', 'RetailCrmOrder::orderSend', 'error during creating order');

            return false;
        }

        return true;
    }

    /**
     * @param \Bitrix\Sale\Payment $event
     *
     * @throws InvalidArgumentException
     *
     * @return bool
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

        $optionsSitesList = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_SITES_LIST, 0));
        $optionsPaymentTypes = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_PAYMENT_TYPES, 0));
        $optionsPayStatuses = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_PAYMENT, 0));

        $arPayment = array(
            'ID'            => $event->getId(),
            'ORDER_ID'      => $event->getField('ORDER_ID'),
            'PAID'          => $event->getField('PAID'),
            'PAY_SYSTEM_ID' => $event->getField('PAY_SYSTEM_ID'),
            'SUM'           => $event->getField('SUM'),
            'LID'           => $order->getSiteId(),
            'DATE_PAID'     => $event->getField('DATE_PAID'),
        );

        if ($optionsSitesList) {
            if (array_key_exists($arPayment['LID'], $optionsSitesList) && $optionsSitesList[$arPayment['LID']] !== null) {
                $site = $optionsSitesList[$arPayment['LID']];
            } else {
                return false;
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
            $paymentToCrm = array(
                'type' => $optionsPaymentTypes[$arPayment['PAY_SYSTEM_ID']],
                'amount' => $arPayment['SUM']
            );

            if (!empty($arPayment['ID'])) {
                $paymentToCrm['externalId'] = RCrmActions::generatePaymentExternalId($arPayment['ID']);
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
            return false;
        }

        $arPaymentExtId = RCrmActions::generatePaymentExternalId($arPayment['ID']);

        if (array_key_exists($arPaymentExtId, $paymentsExternalIds)) {
            $paymentData = $paymentsExternalIds[$arPaymentExtId];
        } elseif (array_key_exists($arPayment['ID'], $paymentsExternalIds)) {
            $paymentData = $paymentsExternalIds[$arPayment['ID']];
        } else {
            $paymentData = array();
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

        $arPayment = array(
            'ID'            => $event->getId(),
            'ORDER_ID'      => $event->getField('ORDER_ID'),
            'LID'           => $event->getCollection()->getOrder()->getSiteId()
        );

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
                if (isset($payment['externalId'])
                    && ($payment['externalId'] == $event->getId()
                        || RCrmActions::getFromPaymentExternalId($payment['externalId']) == $event->getId())
                ) {
                    RCrmActions::apiMethod($api, 'ordersPaymentDelete', __METHOD__, $payment['id']);
                }
            }
        }
    }
}
