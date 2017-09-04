<?php
IncludeModuleLangFile(__FILE__);
class RetailCrmHistory
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
    public static $CRM_ORDER_HISTORY = 'order_history';
    public static $CRM_CUSTOMER_HISTORY = 'customer_history';
    public static $CRM_CATALOG_BASE_PRICE = 'catalog_base_price';
    public static $CRM_ORDER_NUMBERS = 'order_numbers';
    public static $CRM_CANSEL_ORDER = 'cansel_order';

    const CANCEL_PROPERTY_CODE = 'INTAROCRM_IS_CANCELED';

    public static function customerHistory()
    {
        if (!CModule::IncludeModule("iblock")) {
            RCrmActions::eventLog('RetailCrmHistory::orderHistory', 'iblock', 'module not found');

            return false;
        }
        if (!CModule::IncludeModule("sale")) {
            RCrmActions::eventLog('RetailCrmHistory::orderHistory', 'sale', 'module not found');

            return false;
        }
        if (!CModule::IncludeModule("catalog")) {
            RCrmActions::eventLog('RetailCrmHistory::orderHistory', 'catalog', 'module not found');

            return false;
        }

        $api_host = COption::GetOptionString(self::$MODULE_ID, self::$CRM_API_HOST_OPTION, 0);
        $api_key = COption::GetOptionString(self::$MODULE_ID, self::$CRM_API_KEY_OPTION, 0);
        
        $api = new RetailCrm\ApiClient($api_host, $api_key);
        
        $historyFilter = array();
        $historyStart = COption::GetOptionString(self::$MODULE_ID, self::$CRM_CUSTOMER_HISTORY);

        if ($historyStart && $historyStart > 0) {
            $historyFilter['sinceId'] = $historyStart;
        }

        while (true) {
            $customerHistory = RCrmActions::apiMethod($api, 'ordersHistory', __METHOD__, $historyFilter);

            $customerH = isset($customerHistory['history']) ? $customerHistory['history'] : array();

            $log = new Logger();
            $log->write($customerH, 'customerHistory');
            
            if (count($customerH) == 0) {
                if ($customerHistory['history']['totalPageCount'] > $customerHistory['history']['currentPage']) {
                    $historyFilter['page'] = $customerHistory['history']['currentPage'] + 1;
                    
                    continue;
                }
                
                return true;
            }
                        
            $customers = self::assemblyCustomer($customerH);
            
            $GLOBALS['RETAIL_CRM_HISTORY'] = true;

            $newUser = new CUser;
            
            foreach ($customers as $customer) {
                if (function_exists('retailCrmBeforeCustomerSave')) {
                    $newResCustomer = retailCrmBeforeCustomerSave($customer);
                    if (is_array($newResCustomer) && !empty($newResCustomer)) {
                        $customer = $newResCustomer;
                    } elseif ($newResCustomer === false) {
                        RCrmActions::eventLog('RetailCrmHistory::customerHistory', 'retailCrmBeforeCustomerSave()', 'UserCrmId = ' . $customer['id'] . '. Sending canceled after retailCrmBeforeCustomerSave');

                        continue;
                    }
                }
                                
                if (isset($customer['deleted'])) {
                    continue;
                }
                
                if (!isset($customer['externalId'])) {
                    if (!isset($customer['id'])) {
                        continue;
                    }

                    $registerNewUser = true;
                    if (!isset($customer['email']) || $customer['email'] == '') {
                        $login = $customer['email'] = uniqid('user_' . time()) . '@crm.com';
                    } else {
                        $dbUser = CUser::GetList(($by = 'ID'), ($sort = 'ASC'), array('=EMAIL' => $customer['email']));
                        switch ($dbUser->SelectedRowsCount()) {
                            case 0:
                                $login = $customer['email'];
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

                        $arFields = array(
                            "EMAIL"             => $customer['email'],
                            "LOGIN"             => $login,
                            "ACTIVE"            => "Y",
                            "PASSWORD"          => $userPassword,
                            "CONFIRM_PASSWORD"  => $userPassword
                        );
                        $registeredUserID = $newUser->Add($arFields);
                        if ($registeredUserID === false) {
                            RCrmActions::eventLog('RetailCrmHistory::orderHistory', 'CUser::Register', 'Error register user');
                            continue;
                        }

                        if(RCrmActions::apiMethod($api, 'customersFixExternalIds', __METHOD__, array(array('id' => $customer['id'], 'externalId' => $registeredUserID))) == false) {
                             continue; 
                        }
                    }

                    $customer['externalId'] = $registeredUserID;
                }
                
                if (isset($customer['externalId'])) {
                    $arUser = array();
                    if (array_key_exists('firstName', $customer)) {
                        $arUser["NAME"] = $customer['firstName'] ? RCrmActions::fromJSON($customer['firstName']) : '';
                    }
                    if (array_key_exists('lastName', $customer)) {
                        $arUser["LAST_NAME"] = $customer['lastName'] ? RCrmActions::fromJSON($customer['lastName']) : '';
                    }
                    if (array_key_exists('patronymic', $customer)) {
                        $arUser["SECOND_NAME"] = $customer['patronymic'] ? RCrmActions::fromJSON($customer['patronymic']) : '';
                    }
                    
//                    if (array_key_exists('email', $customer)) {
//                        $arUser["EMAIL"] = $customer['email'] ? RCrmActions::fromJSON($customer['email']) : '';
//                    }
                    
                    if (isset($customer['phones'])) {
                        $user = CUser::GetList(($by = "ID"), ($order = "desc"), array('ID' => $customer['externalId']), array('FIELDS' => array('PERSONAL_PHONE', 'PERSONAL_MOBILE')))->fetch();
                        foreach ($customer['phones'] as $phone) {
                            if (isset($phone['old_number']) && in_array($phone['old_number'], $user)) {
                                $key = array_search($phone['old_number'], $user);
                                if (isset($phone['number'])) {
                                    $arUser[$key] = $phone['number'];
                                    $user[$key] = $phone['number'];
                                } else {
                                    $arUser[$key] = '';
                                    $user[$key] = '';
                                }
                            }
                            if (isset($phone['number'])) {
                                if ((!isset($user['PERSONAL_PHONE']) || strlen($user['PERSONAL_PHONE']) == 0)  && $user['PERSONAL_MOBILE'] != $phone['number']) {
                                    $arUser['PERSONAL_PHONE'] = $phone['number'];
                                    $user['PERSONAL_PHONE'] = $phone['number'];
                                    continue;
                                }
                                if ((!isset($user['PERSONAL_MOBILE']) || strlen($user['PERSONAL_MOBILE']) == 0) && $user['PERSONAL_PHONE'] != $phone['number']) {
                                    $arUser['PERSONAL_MOBILE'] = $phone['number'];
                                    $user['PERSONAL_MOBILE'] = $phone['number'];
                                    continue;
                                }
                            }
                        }
                    }
                    if (array_key_exists('index', $customer['address'])) {
                        $arUser["PERSONAL_ZIP"] = $customer['address']['index'] ? RCrmActions::fromJSON($customer['address']['index']) : '';
                    }
                    if (array_key_exists('city', $customer['address'])) {
                        $arUser["PERSONAL_CITY"] = $customer['address']['city'] ? RCrmActions::fromJSON($customer['address']['city']) : '';
                    }

                    $u = $newUser->Update($customer['externalId'], $arUser);
                    if (!$u) {
                        RCrmActions::eventLog('RetailCrmHistory::customerHistory', 'Error update user', $newUser->LAST_ERROR);
                    }
                    
                    if (function_exists('retailCrmAfterCustomerSave')) {
                        retailCrmAfterCustomerSave($customer);
                    }
                }
            }
            
            $GLOBALS['RETAIL_CRM_HISTORY'] = false;
            
            //last id
            $end = array_pop($customerH);
            COption::SetOptionString(self::$MODULE_ID, self::$CRM_CUSTOMER_HISTORY, $end['id']);
            
            if ($customerHistory['pagination']['totalPageCount'] == 1) {
                return true;
            }
            //new filter
            $historyFilter['sinceId'] = $end['id'];
        }
    }
    
    public static function orderHistory()
    {
        global $USER;
        if (is_object($USER) == false) {
            $USER = new RetailUser;
        }
        if (!CModule::IncludeModule("iblock")) {
            RCrmActions::eventLog('RetailCrmHistory::orderHistory', 'iblock', 'module not found');

            return false;
        }
        if (!CModule::IncludeModule("sale")) {
            RCrmActions::eventLog('RetailCrmHistory::orderHistory', 'sale', 'module not found');

            return false;
        }
        if (!CModule::IncludeModule("catalog")) {
            RCrmActions::eventLog('RetailCrmHistory::orderHistory', 'catalog', 'module not found');

            return false;
        }

        $api_host = COption::GetOptionString(self::$MODULE_ID, self::$CRM_API_HOST_OPTION, 0);
        $api_key = COption::GetOptionString(self::$MODULE_ID, self::$CRM_API_KEY_OPTION, 0);

        $optionsOrderTypes = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_ORDER_TYPES_ARR, 0));
        $optionsDelivTypes = array_flip(unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_DELIVERY_TYPES_ARR, 0)));
        $optionsPayStatuses = array_flip(unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_PAYMENT_STATUSES, 0))); // --statuses
        $optionsOrderProps = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_ORDER_PROPS, 0));
        $optionsLegalDetails = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_LEGAL_DETAILS, 0));        
        $optionsSitesList = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_SITES_LIST, 0));
        $optionsOrderNumbers = COption::GetOptionString(self::$MODULE_ID, self::$CRM_ORDER_NUMBERS, 0);
        $optionsCanselOrder = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_CANSEL_ORDER, 0));

        $api = new RetailCrm\ApiClient($api_host, $api_key);
        
        $historyFilter = array();
        $historyStart = COption::GetOptionString(self::$MODULE_ID, self::$CRM_ORDER_HISTORY);
        if ($historyStart && $historyStart > 0) {
            $historyFilter['sinceId'] = $historyStart;
        }      

        while (true) {
            $orderHistory = RCrmActions::apiMethod($api, 'ordersHistory', __METHOD__, $historyFilter);

            $orderH = isset($orderHistory['history']) ? $orderHistory['history'] : array();

            $log = new Logger();
            $log->write($orderH, 'orderHistory');
            
            if (count($orderH) == 0) {
                if ($orderHistory['history']['totalPageCount'] > $orderHistory['history']['currentPage']) {
                    $historyFilter['page'] = $orderHistory['history']['currentPage'] + 1;
                    
                    continue;
                }
                
                return true;
            }
            
            $orders = self::assemblyOrder($orderH);
                        
            $GLOBALS['RETAIL_CRM_HISTORY'] = true;
            
            //orders with changes
            foreach ($orders as $order) {
                if (function_exists('retailCrmBeforeOrderSave')) {
                    $newResOrder = retailCrmBeforeOrderSave($order);
                    if (is_array($newResOrder) && !empty($newResOrder)) {
                        $order = $newResOrder;
                    } elseif ($newResOrder === false) {
                        RCrmActions::eventLog('RetailCrmHistory::orderHistory', 'retailCrmBeforeOrderSave()', 'OrderCrmId = ' . $order['id'] . '. Sending canceled after retailCrmBeforeOrderSave');

                        continue;
                    }
                }
                
                $log->write($order, 'assemblyOrderHistory');
                
                if (isset($order['deleted'])) {
                    if (isset($order['externalId'])) {
                        try {
                            $newOrder = Bitrix\Sale\Order::load($order['externalId']);
                        } catch (Bitrix\Main\ArgumentNullException $e) {
                            RCrmActions::eventLog('RetailCrmHistory::orderHistory', 'Bitrix\Sale\Order::load', $e->getMessage() . ': ' . $order['externalId']);
                            continue;
                        }

                        if (!$newOrder instanceof \Bitrix\Sale\Order) {
                            RCrmActions::eventLog('RetailCrmHistory::orderHistory', 'Bitrix\Sale\Order::load', 'Error order load: ' . $order['externalId']);
                            continue;
                        }
                        
                        $newOrder->setField('CANCELED', 'Y');
                        $newOrder->save();
                    }

                    continue;
                }

                if (!isset($order['externalId'])) {
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
                                "NAME"              => RCrmActions::fromJSON($order['customer']['firstName']),
                                "LAST_NAME"         => RCrmActions::fromJSON($order['customer']['lastName']),
                                "SECOND_NAME"       => RCrmActions::fromJSON($order['customer']['patronymic']),
                                "EMAIL"             => $order['customer']['email'],
                                "LOGIN"             => $login,
                                "ACTIVE"            => "Y",
                                "PASSWORD"          => $userPassword,
                                "CONFIRM_PASSWORD"  => $userPassword
                            );
                            if ($order['customer']['phones'][0]) {
                                $arFields['PERSONAL_PHONE'] = $order['customer']['phones'][0];
                            }
                            if ($order['customer']['phones'][1]) {
                                $arFields['PERSONAL_MOBILE'] = $order['customer']['phones'][1];
                            }

                            $registeredUserID = $newUser->Add($arFields);

                            if ($registeredUserID === false) {
                                RCrmActions::eventLog('RetailCrmHistory::orderHistory', 'CUser::Register', 'Error register user');

                                continue;
                            }

                            if(RCrmActions::apiMethod($api, 'customersFixExternalIds', __METHOD__, array(array('id' => $order['customer']['id'], 'externalId' => $registeredUserID))) == false) {
                                continue; 
                            }
                        }

                        $order['customer']['externalId'] = $registeredUserID;
                    }

                    if ($optionsSitesList) {
                        $site = array_search($order['site'], $optionsSitesList);
                    } else {
                        $site = CSite::GetDefSite();
                    }
                    if (empty($site)) {
                        RCrmActions::eventLog('RetailCrmHistory::orderHistory', 'Bitrix\Sale\Order::create', 'Site = ' . $order['site'] . ' not found in setting. Order crm id=' . $order['id']);
                        
                        continue;
                    }
                    
                    $newOrder = Bitrix\Sale\Order::create($site, $order['customer']['externalId']);
                    $newOrder->save();
                    $externalId = $newOrder->getId();

                    if (isset($externalId)) {
                        if(RCrmActions::apiMethod($api, 'ordersFixExternalIds', __METHOD__, array(array('id' => $order['id'], 'externalId' => $externalId))) == false){
                            continue;
                        }
                    } else {
                        RCrmActions::eventLog('RetailCrmHistory::orderHistory', 'Bitrix\Sale\Order::create', 'Error order create');
                    }
                    $order['externalId'] = $externalId;
                }

                if (isset($order['externalId']) && $order['externalId']) {
                    $itemUpdate = false;
                    try {
                        $newOrder = Bitrix\Sale\Order::load($order['externalId']);
                    } catch (Bitrix\Main\ArgumentNullException $e) {
                        RCrmActions::eventLog('RetailCrmHistory::orderHistory', 'Bitrix\Sale\Order::load', $e->getMessage() . ': ' . $order['externalId']);
                        
                        continue;
                    }

                    if (!$newOrder instanceof \Bitrix\Sale\Order) {
                        RCrmActions::eventLog('RetailCrmHistory::orderHistory', 'Bitrix\Sale\Order::load', 'Error order load id=' . $order['externalId']);
                        
                        continue;
                    }
                    
                    if ($optionsSitesList) {
                        $site = array_search($order['site'], $optionsSitesList);
                    } else {
                        $site = CSite::GetDefSite();
                    }
                    if (empty($site)) {
                        RCrmActions::eventLog('RetailCrmHistory::orderHistory', 'Bitrix\Sale\Order::edit', 'Site = ' . $order['site'] . ' not found in setting. Order id=' . $order['externalId']);
                        
                        continue;
                    }

                    if ($optionsOrderNumbers == 'Y' && isset($order['number'])) {
                        $searchFilter = array(
                                'filter' => array('ACCOUNT_NUMBER' => $order['number']),
                                'select' => array('ID'),
                        );
                        $searchOrder = reset(Bitrix\Sale\Order::loadByFilter($searchFilter));
                        if (!empty($searchOrder)) {
                            if ($searchOrder->getId() != $order['externalId']) {
                                RCrmActions::eventLog('RetailCrmHistory::orderHistory', 'setField("ACCOUNT_NUMBER")', 'Error order load id=' . $order['externalId']) . '. Number ' . $order['number'] . ' already exists';
                            
                                continue;
                            }
                        }
                        
                        $newOrder->setField('ACCOUNT_NUMBER', $order['number']);
                    }

                    $personType = $newOrder->getField('PERSON_TYPE_ID');

                    $nType = array();
                    $tList = RCrmActions::OrderTypesList(array(array('LID' => $site)));
                    foreach($tList as $type){
                        if (isset($optionsOrderTypes[$type['ID']])) {
                            $nType[$optionsOrderTypes[$type['ID']]] = $type['ID'];
                        }
                    }
                    $optionsOrderTypes = $nType;

                    if ($optionsOrderTypes[$order['orderType']]) {
                        if ($personType != $optionsOrderTypes[$order['orderType']] && $personType != 0) {
                            $propsRemove = true;
                        }
                        $personType = $optionsOrderTypes[$order['orderType']];
                        $newOrder->setField('PERSON_TYPE_ID', $personType);
                    } elseif ($personType == 0) {
                        RCrmActions::eventLog('RetailCrmHistory::orderHistory', 'orderType not found', 'PERSON_TYPE_ID = 0');
                    }

                    //status
                    if ($optionsPayStatuses[$order['status']]) {
                        $newOrder->setField('STATUS_ID', $optionsPayStatuses[$order['status']]);
                        if (in_array($optionsPayStatuses[$order['status']], $optionsCanselOrder)) {
                            $newOrder->setFieldNoDemand('CANCELED', 'Y');
                        } else {
                            $newOrder->setFieldNoDemand('CANCELED', 'N');
                        }
                    }

                    if (array_key_exists('statusComment', $order)) {
                        self::setProp($newOrder, RCrmActions::fromJSON($order['statusComment']), 'REASON_CANCELED');
                    }

                    //props
                    $propertyCollection = $newOrder->getPropertyCollection();
                    $propertyCollectionArr = $propertyCollection->getArray();
                    $nProps = array();
                    foreach ($propertyCollectionArr['properties'] as $orderProp) {
                        if ($orderProp['ID'][0] == 'n') {
                            $orderProp['ID'] = substr($orderProp['ID'], 1);
                            $orderProp['ID'] = $propertyCollection->getItemById($orderProp['ID'])->getField('ORDER_PROPS_ID');
                        }
                        $nProps[] = $orderProp;
                    }
                    $propertyCollectionArr['properties'] = $nProps;
                    
                    if ($propsRemove) {//delete props
                        foreach ($propertyCollectionArr['properties'] as $orderProp) {
                            $somePropValue = $propertyCollection->getItemByOrderPropertyId($orderProp['ID']);
                            self::setProp($somePropValue);
                        }
                        $orderCrm = RCrmActions::apiMethod($api, 'orderGet', __METHOD__, $order['id']);

                        $orderDump = $order;
                        $order = $orderCrm['order'];
                    }
                    
                    $propsKey = array();
                    foreach ($propertyCollectionArr['properties'] as $prop) {
                        $propsKey[$prop['CODE']]['ID'] = $prop['ID'];
                        $propsKey[$prop['CODE']]['TYPE'] = $prop['TYPE'];
                    }
                    //fio
                    if ($order['firstName'] || $order['lastName'] || $order['patronymic']) {
                        $fio = '';
                        foreach ($propertyCollectionArr['properties'] as $prop) {
                            if (in_array($optionsOrderProps[$personType]['fio'], $prop)) {
                                $getFio = $newOrder->getPropertyCollection()->getItemByOrderPropertyId($prop['ID']);
                                if (method_exists($getFio, 'getValue')) {
                                    $fio = $getFio->getValue();
                                }
                            }
                        }

                        $fio = RCrmActions::explodeFIO($fio);
                        $newFio = array();
                        if ($fio) {
                            $newFio[] = isset($order['lastName']) ? RCrmActions::fromJSON($order['lastName']) : (isset($fio['lastName']) ? $fio['lastName'] : '');
                            $newFio[] = isset($order['firstName']) ? RCrmActions::fromJSON($order['firstName']) : (isset($fio['firstName']) ? $fio['firstName'] : '');
                            $newFio[] = isset($order['patronymic']) ? RCrmActions::fromJSON($order['patronymic']) : (isset($fio['patronymic']) ? $fio['patronymic'] : '');
                            $order['fio'] = trim(implode(' ', $newFio));
                        } else {
                            $newFio[] = isset($order['lastName']) ? RCrmActions::fromJSON($order['lastName']) : '';
                            $newFio[] = isset($order['firstName']) ? RCrmActions::fromJSON($order['firstName']) : '';
                            $newFio[] = isset($order['patronymic']) ? RCrmActions::fromJSON($order['patronymic']) : '';
                            $order['fio'] = trim(implode(' ', $newFio));
                        }
                    }
                    
                    //optionsOrderProps
                    if ($optionsOrderProps[$personType]) {
                        foreach ($optionsOrderProps[$personType] as $key => $orderProp) {
                            if (array_key_exists($key, $order)) {
                                $somePropValue = $propertyCollection->getItemByOrderPropertyId($propsKey[$orderProp]['ID']);
                                if ($key == 'fio') {
                                    self::setProp($somePropValue, $order[$key]);
                                } else {
                                    self::setProp($somePropValue, RCrmActions::fromJSON($order[$key]));
                                }
                            } elseif (array_key_exists($key, $order['delivery']['address'])) {
                                if ($propsKey[$orderProp]['TYPE'] == 'LOCATION') {
                                    $order['delivery']['address'][$key] = trim($order['delivery']['address'][$key]);
                                    if(!empty($order['delivery']['address'][$key])){
                                        $parameters = array();
                                        $loc = explode('.', $order['delivery']['address'][$key]);
                                        if (count($loc) == 1) {
                                            $parameters['filter']['PHRASE'] = RCrmActions::fromJSON(trim($loc[0]));
                                        } elseif (count($loc) == 2) {
                                            $parameters['filter']['PHRASE'] = RCrmActions::fromJSON(trim($loc[1]));
                                        } else{
                                            RCrmActions::eventLog('RetailCrmHistory::orderHistory', 'RetailCrmHistory::setProp', 'Error location. ' . $order['delivery']['address'][$key] . ' not found add in order id=' . $order['externalId']);
                                            continue;
                                        }
                                        $parameters['filter']['NAME.LANGUAGE_ID'] = 'ru';
                                        $location = \Bitrix\Sale\Location\Search\Finder::find($parameters, array('USE_INDEX' => false, 'USE_ORM' => false))->fetch();

                                        $somePropValue = $propertyCollection->getItemByOrderPropertyId($propsKey[$orderProp]['ID']);
                                        self::setProp($somePropValue, $location['CODE']);
                                    }  else {
                                        RCrmActions::eventLog('RetailCrmHistory::orderHistory', 'RetailCrmHistory::setProp', 'Error location. ' . $order['delivery']['address'][$key] . ' is empty in order id=' . $order['externalId']);

                                        continue;
                                    }
                                } else {
                                    $somePropValue = $propertyCollection->getItemByOrderPropertyId($propsKey[$orderProp]['ID']);
                                    self::setProp($somePropValue, RCrmActions::fromJSON($order['delivery']['address'][$key]));
                                }
                            }
                        }
                    }
                    
                    //optionsLegalDetails
                    if ($optionsLegalDetails[$personType]) {
                        foreach ($optionsLegalDetails[$personType] as $key => $orderProp) {
                            if (array_key_exists($key, $order)) {
                                $somePropValue = $propertyCollection->getItemByOrderPropertyId($propsKey[$orderProp]['ID']);
                                self::setProp($somePropValue, $order[$key]);
                            }
                        }
                    }
                    if ($propsRemove) {
                        $order = $orderDump;
                    }

                    //comments
                    if (array_key_exists('customerComment', $order)) {
                        self::setProp($newOrder, RCrmActions::fromJSON($order['customerComment']), 'USER_DESCRIPTION');
                    }
                    if (array_key_exists('managerComment', $order)) {
                        self::setProp($newOrder, RCrmActions::fromJSON($order['managerComment']), 'COMMENTS');
                    }

                    //items
                    $basket = $newOrder->getBasket();
                    if (isset($order['items'])) {
                        $itemUpdate = true;
                        foreach ($order['items'] as $product) {
                            $item = self::getExistsItem($basket, 'catalog', $product['offer']['externalId']);
                            if (!$item) {
                                if($product['delete']){
                                    continue;
                                }
                                $item = $basket->createItem('catalog', $product['offer']['externalId']);
                                if ($item instanceof \Bitrix\Sale\BasketItem) {
                                    $elem = self::getInfoElement($product['offer']['externalId']);
                                    $item->setFields(array(
                                        'CURRENCY' => \Bitrix\Currency\CurrencyManager::getBaseCurrency(),
                                        'LID' => $site,
                                        'BASE_PRICE' => $product['initialPrice'],
                                        'NAME' => $product['name'] ? RCrmActions::fromJSON($product['name']) : $elem['NAME'],
                                        'DETAIL_PAGE_URL' => $elem['URL'],
                                        'PRODUCT_PROVIDER_CLASS' => 'CCatalogProductProvider',
                                        'DIMENSIONS' => $elem['DIMENSIONS'],
                                        'WEIGHT' => $elem['WEIGHT'],
                                        'NOTES' => GetMessage('PRICE_TYPE')
                                    ));
                                } else {
                                    RCrmActions::eventLog('RetailCrmHistory::orderHistory', 'createItem', 'Error item add');
                                    
                                    continue;
                                }
                            }
                            if ($product['delete']) {
                                $item->delete();
                                
                                continue;
                            }

                            if ($product['quantity']) {
                                $item->setFieldNoDemand('QUANTITY', $product['quantity']);
                            }

                            if (array_key_exists('discountTotal', $product)) {
                                $itemCost = $item->getField('BASE_PRICE');
                                if (isset($itemCost) && $itemCost > 0) {
                                    $item->setField('CUSTOM_PRICE', 'Y');
                                    $item->setField('PRICE', $itemCost - $product['discountTotal']);
                                    $item->setField('DISCOUNT_PRICE', $product['discountTotal']);
                                    $item->setField('DISCOUNT_NAME', '');
                                    $item->setField('DISCOUNT_VALUE', '');
                                }
                            }

                            $basket->save();  
                        }
                    }
                    
                    $orderSumm = 0;
                    foreach ($basket as $item) {
                        $orderSumm += $item->getFinalPrice(); 
                    }

                    if (array_key_exists('cost', $order['delivery'])) {
                        $deliverySumm = $order['delivery']['cost'];
                    } else {
                        $deliverySumm = $newOrder->getDeliveryPrice();
                    }

                    $orderSumm += $deliverySumm;

                    $newOrder->setField('PRICE', $orderSumm);
                    $order['summ'] = $orderSumm;
                    $newOrder->save();
                    
                    //payment
                    if (array_key_exists('payments', $order)) {
                        if (!isset($orderCrm)) {
                            $orderCrm = RCrmActions::apiMethod($api, 'orderGet', __METHOD__, $order['id']);
                        }
                        if ($orderCrm) {
                            $newOrder = self::paymentsUpdate($newOrder, $orderCrm['order'], $api);
                        }
                    }
                    
                    //delivery
                    if (array_key_exists('code', $order['delivery'])) {
                        $itemUpdate = true;
                        //delete empty
                        if (!isset($orderCrm)) {
                            $orderCrm = RCrmActions::apiMethod($api, 'orderGet', __METHOD__, $order['id']);
                        }
                        if ($orderCrm) {
                            self::shipmentUpdate($orderCrm['order'], $optionsDelivTypes, $newOrder->getField('ACCOUNT_NUMBER'));
                        }
                    }
                    if (isset($orderCrm)) {
                        unset($orderCrm); 
                    }
                    
                    //delivery cost
                    if (array_key_exists('cost', $order['delivery'])) {
                        $shipment = Bitrix\Sale\Internals\ShipmentTable::getList(array(
                            'filter' => array('ORDER_ID' => $order['externalId'], 'SYSTEM' => 'N'),
                            'order' => array('ID')
                        ))->fetch();
                        if ($shipment) {
                            Bitrix\Sale\Internals\ShipmentTable::update($shipment['ID'], array('BASE_PRICE_DELIVERY' => $order['delivery']['cost'], 'PRICE_DELIVERY' => $order['delivery']['cost'], 'CUSTOM_PRICE_DELIVERY' => 'Y'));
                        }
                        
                        Bitrix\Sale\OrderTable::update($order['externalId'], array('PRICE_DELIVERY' => $order['delivery']['cost']));
                    }  
                    
                    Bitrix\Sale\OrderTable::update($order['externalId'], array('MARKED' => 'N', 'EMP_MARKED_ID' => '', 'REASON_MARKED' => ''));
                    
                    if ($itemUpdate) {
                        self::updateShipmentItem($order['externalId']);
                    }
                    
                    if (function_exists('retailCrmAfterOrderSave')) {
                        retailCrmAfterOrderSave($order);
                    }
                }
            }
            
            $GLOBALS['RETAIL_CRM_HISTORY'] = false;
            
            //end id
            $end = array_pop($orderH);
            COption::SetOptionString(self::$MODULE_ID, self::$CRM_ORDER_HISTORY, $end['id']);
            
            if ($orderHistory['pagination']['totalPageCount'] == 1) {
                return true;
            }
            //new filter
            $historyFilter['sinceId'] = $end['id'];
        }
    }
    
    public static function assemblyCustomer($customerHistory)
    {
        $server = \Bitrix\Main\Context::getCurrent()->getServer()->getDocumentRoot();
        $fields = array();
        if (file_exists($server . '/bitrix/modules/intaro.retailcrm/classes/general/config/objects.xml')) {
            $objects = simplexml_load_file($server . '/bitrix/modules/intaro.retailcrm/classes/general/config/objects.xml'); 
            foreach ($objects->fields->field as $object) {
                $fields[(string)$object["group"]][(string)$object["id"]] = (string)$object;
            }
        }
        $customers = array();
        foreach ($customerHistory as $change) {
            $change['customer'] = self::removeEmpty($change['customer']);
            if ($customers[$change['customer']['id']]) {  
                $customers[$change['customer']['id']] = array_merge($customers[$change['customer']['id']], $change['customer']);
            } else {
                $customers[$change['customer']['id']] = $change['customer'];
            }
            
            if ($change['customer']['contragent']['contragentType']) {
                $change['customer']['contragentType'] = self::newValue($change['customer']['contragent']['contragentType']);
                unset($change['customer']['contragent']);
            }
            
            if ($fields['customer'][$change['field']] == 'phones') {
                $key = count($customers[$change['customer']['id']]['phones']);
                if (isset($change['oldValue'])) {
                    $customers[$change['customer']['id']]['phones'][$key]['old_number'] = $change['oldValue'];
                }
                if (isset($change['newValue'])) {
                    $customers[$change['customer']['id']]['phones'][$key]['number'] = $change['newValue'];
                }
            } else {
                if ($fields['customerAddress'][$change['field']]) {
                    $customers[$change['customer']['id']]['address'][$fields['customerAddress'][$change['field']]] = $change['newValue'];
                } elseif ($fields['customerContragent'][$change['field']]) {
                    $customers[$change['customer']['id']]['contragent'][$fields['customerContragent'][$change['field']]] = $change['newValue'];
                } elseif ($fields['customer'][$change['field']]) {
                    $customers[$change['customer']['id']][$fields['customer'][$change['field']]] = self::newValue($change['newValue']);
                }
                
                if (isset($change['created'])) {
                    $customers[$change['customer']['id']]['create'] = 1;
                }

                if (isset($change['deleted'])) {
                    $customers[$change['customer']['id']]['deleted'] = 1;
                }
            }
        }
        
        return $customers;
    }
    
    public static function assemblyOrder($orderHistory)
    {
        $server = \Bitrix\Main\Context::getCurrent()->getServer()->getDocumentRoot();
        if (file_exists($server . '/bitrix/modules/intaro.retailcrm/classes/general/config/objects.xml')) {
            $objects = simplexml_load_file($server . '/bitrix/modules/intaro.retailcrm/classes/general/config/objects.xml'); 
            foreach ($objects->fields->field as $object) {
                $fields[(string)$object["group"]][(string)$object["id"]] = (string)$object;
            }
        }
        $orders = array();
        foreach ($orderHistory as $change) {
            $change['order'] = self::removeEmpty($change['order']);
            if ($change['order']['items']) {
                $items = array();
                foreach ($change['order']['items'] as $item) {
                    if (isset($change['created'])) {
                        $item['create'] = 1;          
                    }
                    $items[$item['id']] = $item;
                }
                $change['order']['items'] = $items;
            }
            
            if ($change['order']['payments']) {
                $payments = array();
                foreach ($change['order']['payments'] as $payment) {
                    $payments[$payment['id']] = $payment;
                }
                $change['order']['payments'] = $payments;
            }

            if ($change['order']['contragent']['contragentType']) {
                $change['order']['contragentType'] = self::newValue($change['order']['contragent']['contragentType']);
                unset($change['order']['contragent']);
            }

            if ($orders[$change['order']['id']]) {  
                $orders[$change['order']['id']] = array_merge($orders[$change['order']['id']], $change['order']);
            } else {
                $orders[$change['order']['id']] = $change['order'];
            } 

            if ($change['item']) {
                if ($orders[$change['order']['id']]['items'][$change['item']['id']]) {   
                    $orders[$change['order']['id']]['items'][$change['item']['id']] = array_merge($orders[$change['order']['id']]['items'][$change['item']['id']], $change['item']);
                } else {
                    $orders[$change['order']['id']]['items'][$change['item']['id']] = $change['item'];
                }

                if (empty($change['oldValue']) && $change['field'] == 'order_product') {
                    $orders[$change['order']['id']]['items'][$change['item']['id']]['create'] = 1;
                    unset($orders[$change['order']['id']]['items'][$change['item']['id']]['delete']);
                }
                if (empty($change['newValue']) && $change['field'] == 'order_product') {
                    $orders[$change['order']['id']]['items'][$change['item']['id']]['delete'] = 1;
                }
                if (!$orders[$change['order']['id']]['items'][$change['item']['id']]['create'] && $fields['item'][$change['field']]) {
                    $orders[$change['order']['id']]['items'][$change['item']['id']][$fields['item'][$change['field']]] = $change['newValue'];
                }
            } elseif ($change['payment']) {
                if ($orders[$change['order']['id']]['payments'][$change['payment']['id']]) {   
                    $orders[$change['order']['id']]['payments'][$change['payment']['id']] = array_merge($orders[$change['order']['id']]['payments'][$change['payment']['id']], $change['payment']);
                } else {
                    $orders[$change['order']['id']]['payments'][$change['payment']['id']] = $change['payment'];
                }

                if (empty($change['oldValue']) && $change['field'] == 'payments') {
                    $orders[$change['order']['id']]['payments'][$change['payment']['id']]['create'] = 1;
                    unset($orders[$change['order']['id']]['payments'][$change['payment']['id']]['delete']);
                }
                if (empty($change['newValue']) && $change['field'] == 'payments') {
                    $orders[$change['order']['id']]['payments'][$change['payment']['id']]['delete'] = 1;
                }
                if (!$orders[$change['order']['id']]['payments'][$change['payment']['id']]['create'] && $fields['payment'][$change['field']]) {
                    $orders[$change['order']['id']]['payments'][$change['payment']['id']][$fields['payment'][$change['field']]] = $change['newValue'];
                }
            } else {
                if ($fields['delivery'][$change['field']] == 'service') {
                    $orders[$change['order']['id']]['delivery']['service']['code'] = self::newValue($change['newValue']);
                } elseif ($fields['delivery'][$change['field']]) {
                    $orders[$change['order']['id']]['delivery'][$fields['delivery'][$change['field']]] = self::newValue($change['newValue']);
                } elseif ($fields['orderAddress'][$change['field']]) {
                    $orders[$change['order']['id']]['delivery']['address'][$fields['orderAddress'][$change['field']]] = $change['newValue'];
                } elseif ($fields['integrationDelivery'][$change['field']]) {
                    $orders[$change['order']['id']]['delivery']['service'][$fields['integrationDelivery'][$change['field']]] = self::newValue($change['newValue']);
                } elseif ($fields['customerContragent'][$change['field']]) {
                    $orders[$change['order']['id']][$fields['customerContragent'][$change['field']]] = self::newValue($change['newValue']);
                } elseif (strripos($change['field'], 'custom_') !== false) {
                    $orders[$change['order']['id']]['customFields'][str_replace('custom_', '', $change['field'])] = self::newValue($change['newValue']);
                } elseif ($fields['order'][$change['field']]){
                    $orders[$change['order']['id']][$fields['order'][$change['field']]] = self::newValue($change['newValue']);
                }

                if (isset($change['created'])) {
                    $orders[$change['order']['id']]['create'] = 1;
                }

                if (isset($change['deleted'])) {
                    $orders[$change['order']['id']]['deleted'] = 1;
                }
            }    
        }
        
        return $orders;
    }
    
    public static function shipmentUpdate($orderCrm, $optionsDelivTypes, $accountNumber = '')
    {
        if (strlen($accountNumber) < 1) {
            RCrmActions::eventLog('RetailCrmHistory::orderHistory', 'shipmentUpdate', 'ACCOUNT_NUMBER not found');
            
            return false;
        }
        
        if (isset($orderCrm['delivery']['code'])) {
            $crmCode = $orderCrm['delivery']['code'];
            
            if (isset($orderCrm['delivery']['data']['deliveryType'])) {
                $crmService = $orderCrm['delivery']['data']['deliveryType'];
            } elseif (isset($orderCrm['delivery']['service'])) {
                $crmService = $orderCrm['delivery']['service']['code'];
            }

            //select bitrix service code
            $arDeliveryServiceAll = \Bitrix\Sale\Delivery\Services\Manager::getActiveList();
            foreach ($arDeliveryServiceAll as $arDeliveryService) {
                $arDeliveryCode[$arDeliveryService['CODE']] = $arDeliveryService['ID'];
                $arDeliveryID[$arDeliveryService['ID']] = $arDeliveryService;
                if ($arDeliveryService['ID'] == $optionsDelivTypes[$crmCode]) {
                    $dCode = $arDeliveryService['CODE'] . ':' . $crmService;
                } 
            }
            //We will change delivery to this id
            if ($crmService && $arDeliveryCode[$dCode]) {
                $nowDelivery = $arDeliveryCode[$dCode];
            } elseif (!empty($optionsDelivTypes[$crmCode])) {
                $nowDelivery = $optionsDelivTypes[$crmCode];
            } else {
                RCrmActions::eventLog('RetailCrmHistory::orderHistory', 'shipmentUpdate', 'Delivery ' . $crmCode . ' not found in options');
                
                return false;
            }
            
            //Find the current delivery in the order
            $cnt = Bitrix\Sale\Internals\ShipmentTable::getCount(array('ORDER_ID' => $orderCrm['externalId']));
            if ($cnt > 0) {//update 
                $obDeliverys = \Bitrix\Sale\Internals\ShipmentTable::getList(array('filter' => array('ORDER_ID' => $orderCrm['externalId']),
                                                                    'order' => array('ID')));
                while ($arDelivery = $obDeliverys->fetch()) {
                    if ($arDelivery['DELIVERY_ID'] != $nowDelivery) {
                        \Bitrix\Sale\OrderTable::update($orderCrm['externalId'], array('DELIVERY_ID' => $nowDelivery));
                        \Bitrix\Sale\Internals\ShipmentTable::update($arDelivery['ID'], array('DELIVERY_ID' => $nowDelivery, 'DELIVERY_NAME' => $arDeliveryID[$nowDelivery]['NAME']));
                    }
                }
                if ($cnt == 1 && $arDelivery['DELIVERY_ID'] == 0) {
                    $shipment = Bitrix\Sale\Internals\ShipmentTable::add(array(
                        'ORDER_ID' => $orderCrm['externalId'],
                        'STATUS_ID' => 'DN',
                        'PRICE_DELIVERY' => 0,
                        'BASE_PRICE_DELIVERY' => 0,
                        'CUSTOM_PRICE_DELIVERY' => 'N',
                        'ALLOW_DELIVERY' => 'N',
                        'DEDUCTED' => 'N',
                        'RESERVED' => 'N',
                        'DELIVERY_ID' => $nowDelivery,
                        'DELIVERY_NAME' => $arDeliveryID[$nowDelivery]['NAME'],
                        'CANCELED' => 'N',
                        'MARKED' => 'N',
                        'CURRENCY' => \Bitrix\Currency\CurrencyManager::getBaseCurrency(),
                        'SYSTEM' => 'N',
                        'ACCOUNT_NUMBER' => $accountNumber . '/2',
                        'EXTERNAL_DELIVERY' => 'N',
                        'UPDATED_1C' => 'N',
                        'DATE_INSERT'=> new \Bitrix\Main\Type\DateTime()
                    ));
                }
            } else {//create
                \Bitrix\Sale\OrderTable::update($orderCrm['externalId'], array('DELIVERY_ID' => $nowDelivery));
                $shipmentSystem = \Bitrix\Sale\Internals\ShipmentTable::add(array(
                    'ORDER_ID' => $orderCrm['externalId'],
                    'STATUS_ID' => 'DN',
                    'CUSTOM_PRICE_DELIVERY' => 'N',
                    'ALLOW_DELIVERY' => 'N',
                    'DEDUCTED' => 'N',
                    'RESERVED' => 'N',
                    'DELIVERY_ID' => $nowDelivery,
                    'DELIVERY_NAME' => $nowDelivery[$nowDelivery]['NAME'],
                    'CANCELED' => 'N',
                    'MARKED' => 'N',
                    'SYSTEM' => 'Y',
                    'ACCOUNT_NUMBER' => $accountNumber . '/1',
                    'EXTERNAL_DELIVERY' => 'N',
                    'UPDATED_1C' => 'N',
                    'DATE_INSERT'=> new \Bitrix\Main\Type\DateTime()
                ));
                $shipment = Bitrix\Sale\Internals\ShipmentTable::add(array(
                    'ORDER_ID' => $orderCrm['externalId'],
                    'STATUS_ID' => 'DN',
                    'PRICE_DELIVERY' => 0,
                    'BASE_PRICE_DELIVERY' => 0,
                    'CUSTOM_PRICE_DELIVERY' => 'N',
                    'ALLOW_DELIVERY' => 'N',
                    'DEDUCTED' => 'N',
                    'RESERVED' => 'N',
                    'DELIVERY_ID' => $nowDelivery,
                    'DELIVERY_NAME' => $arDeliveryID[$nowDelivery]['NAME'],
                    'CANCELED' => 'N',
                    'MARKED' => 'N',
                    'CURRENCY' => \Bitrix\Currency\CurrencyManager::getBaseCurrency(),
                    'SYSTEM' => 'N',
                    'ACCOUNT_NUMBER' => $accountNumber . '/2',
                    'EXTERNAL_DELIVERY' => 'N',
                    'UPDATED_1C' => 'N',
                    'DATE_INSERT'=> new \Bitrix\Main\Type\DateTime()
                ));
            }   
        } else {
            //search for the order on the delivery site and delete / replace with no delivery
            $noOrderId = \Bitrix\Sale\Delivery\Services\EmptyDeliveryService::getEmptyDeliveryServiceId();
            \Bitrix\Sale\OrderTable::update($orderCrm['externalId'], array('DELIVERY_ID' => $noOrderId));
            $obDeliverys = Bitrix\Sale\Internals\ShipmentTable::getList(array('filter' => array('ORDER_ID' => $orderCrm['externalId']),
                                                               'order' => array('ID')));
            $create = true;
            while ($arDelivery = $obDeliverys->fetch()) {
                \Bitrix\Sale\Internals\ShipmentTable::update($arDelivery['ID'], array('DELIVERY_ID' => $noOrderId, 'DELIVERY_NAME' => GetMessage('NO_DELIVERY')));
                $create = false;
            }
            if ($create) {
                $shipmentSystem = \Bitrix\Sale\Internals\ShipmentTable::add(array(
                    'ORDER_ID' => $orderCrm['externalId'],
                    'STATUS_ID' => 'DN',
                    'CUSTOM_PRICE_DELIVERY' => 'N',
                    'ALLOW_DELIVERY' => 'N',
                    'DEDUCTED' => 'N',
                    'RESERVED' => 'N',
                    'DELIVERY_ID' => $noOrderId,
                    'DELIVERY_NAME' => GetMessage('NO_DELIVERY'),
                    'CANCELED' => 'N',
                    'MARKED' => 'N',
                    'SYSTEM' => 'Y',
                    'ACCOUNT_NUMBER' => $accountNumber . '/1',
                    'EXTERNAL_DELIVERY' => 'N',
                    'UPDATED_1C' => 'N',
                    'DATE_INSERT'=> new \Bitrix\Main\Type\DateTime()
                ));
                $shipment = Bitrix\Sale\Internals\ShipmentTable::add(array(
                    'ORDER_ID' => $orderCrm['externalId'],
                    'STATUS_ID' => 'DN',
                    'PRICE_DELIVERY' => 0,
                    'BASE_PRICE_DELIVERY' => 0,
                    'CUSTOM_PRICE_DELIVERY' => 'N',
                    'ALLOW_DELIVERY' => 'N',
                    'DEDUCTED' => 'N',
                    'RESERVED' => 'N',
                    'DELIVERY_ID' => $noOrderId,
                    'DELIVERY_NAME' => GetMessage('NO_DELIVERY'),
                    'CANCELED' => 'N',
                    'MARKED' => 'N',
                    'CURRENCY' => \Bitrix\Currency\CurrencyManager::getBaseCurrency(),
                    'SYSTEM' => 'N',
                    'ACCOUNT_NUMBER' => $accountNumber . '/2',
                    'EXTERNAL_DELIVERY' => 'N',
                    'UPDATED_1C' => 'N',
                    'DATE_INSERT'=> new \Bitrix\Main\Type\DateTime()
                ));
            }
        }
        
        return true;
    }
    
    public static function updateShipmentItem($orderId)
    {
        $orderBasket = \Bitrix\Sale\Internals\BasketTable::getList(array(
            'filter' => array('ORDER_ID' => $orderId),
            'select' => array('ID', 'QUANTITY')
        ));

        $basketItems = array();
        while ($basketItem = $orderBasket->fetch()) {
            $basketItems[] = $basketItem;
            $bItems[] = $basketItem['ID'];
        }

        $obShipments = \Bitrix\Sale\Internals\ShipmentTable::getList(array(
            'filter' => array('ORDER_ID' => $orderId),
            'select' => array('ID')
        ));

        $shipmentItems = array();
        while ($arShipment = $obShipments->fetch()) {
            $dlvBaslet = \Bitrix\Sale\Internals\ShipmentItemTable::getList(array(
                'order'  => array('ORDER_DELIVERY_ID'),
                'filter' => array('ORDER_DELIVERY_ID' => $arShipment['ID'])
            ));
            $shipmentItems[$arShipment['ID']] = array();
            while ($item = $dlvBaslet->fetch()) {
                $shipmentItems[$arShipment['ID']][] = $item;
            }
        }

        foreach ($basketItems as $basketItem) {
            foreach ($shipmentItems as $key => $arShipmentItems) {
                $found = false;
                foreach ($arShipmentItems as $elShipmentItem) {
                    if (!in_array($elShipmentItem['BASKET_ID'], $bItems)) {
                        //delete the element
                        \Bitrix\Sale\Internals\ShipmentItemTable::delete($elShipmentItem['ID']);
                    }
                    if ($elShipmentItem['BASKET_ID'] == $basketItem['ID']) {
                        //found
                        $found = true;
                        //update quantity
                        if ($elShipmentItem['QUANTITY'] != $basketItem['QUANTITY']) {
                            \Bitrix\Sale\Internals\ShipmentItemTable::update($elShipmentItem['ID'], array('QUANTITY' => $basketItem['QUANTITY']));
                        }

                    }
                }
                if (!$found) {
                    //create 
                    \Bitrix\Sale\Internals\ShipmentItemTable::add(array(
                        'ORDER_DELIVERY_ID' => $key,
                        'BASKET_ID'         => $basketItem['ID'],
                        'DATE_INSERT'       => new \Bitrix\Main\Type\DateTime(),
                        'QUANTITY'          => $basketItem['QUANTITY'],
                        'RESERVED_QUANTITY' => '0.00',
                    ));
                }
            }
        }
    }
    
    public static function paymentsUpdate($order, $paymentsCrm, $api)
    {
        $optionsPayTypes = array_flip(unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_PAYMENT_TYPES, 0)));
        $optionsPayment = array_flip(unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_PAYMENT, 0)));
        $allPaymentSystems = RCrmActions::PaymentList();
        foreach ($allPaymentSystems as $allPaymentSystem) {
            $arPaySysmems[$allPaymentSystem['ID']] = $allPaymentSystem['NAME'];
        }
        $paymentsList = array();
        $paymentColl = $order->getPaymentCollection();
        foreach ($paymentColl as $paymentData) {
            $data = $paymentData->getFields()->getValues();
            $paymentsList[$data['ID']] = $paymentData;
        }
        
        //data from crm
        $paySumm = 0;
        foreach ($paymentsCrm['payments'] as $paymentCrm) {
            if (!empty($paymentCrm['externalId'])) {
                //find the payment
                $nowPayment = $paymentsList[$paymentCrm['externalId']];
                //update data
                if ($nowPayment instanceof \Bitrix\Sale\Payment) {
                    $nowPayment->setField('SUM', $paymentCrm['amount']);
                    if ($optionsPayTypes[$paymentCrm['type']] != $nowPayment->getField('PAY_SYSTEM_ID')) {
                        $nowPayment->setField('PAY_SYSTEM_ID', $optionsPayTypes[$paymentCrm['type']]);
                        $nowPayment->setField('PAY_SYSTEM_NAME', $arPaySysmems[$optionsPayTypes[$paymentCrm['type']]]);
                    }
                    if (isset($optionsPayment[$paymentCrm['status']])) {
                        $nowPayment->setField('PAID', $optionsPayment[$paymentCrm['status']]);
                    }
                    $nowPayment->save();

                    unset($paymentsList[$paymentCrm['externalId']]);
                }
            } else {
                $newPayment = $paymentColl->createItem();
                $newPayment->setField('SUM', $paymentCrm['amount']);
                $newPayment->setField('PAY_SYSTEM_ID', $optionsPayTypes[$paymentCrm['type']]);
                $newPayment->setField('PAY_SYSTEM_NAME', $arPaySysmems[$optionsPayTypes[$paymentCrm['type']]]);
                $newPayment->setField('PAID', $optionsPayment[$paymentCrm['status']] ? $optionsPayment[$paymentCrm['status']] : 'N');
                $newPayment->setField('CURRENCY', \Bitrix\Currency\CurrencyManager::getBaseCurrency());
                $newPayment->setField('IS_RETURN', 'N');
                $newPayment->setField('PRICE_COD', '0.00');
                $newPayment->setField('EXTERNAL_PAYMENT', 'N');
                $newPayment->setField('UPDATED_1C', 'N');
                $newPayment->save();

                $newPaymentId = $newPayment->getId();

                unset($paymentsList[$newPaymentId]);

                //RCrmActions::apiMethod($api, 'ordersPaymentEdit', __METHOD__, array('id' => $paymentCrm['id'], 'externalId' => $newPaymentId));
            }
            
            if ($optionsPayment[$paymentCrm['status']] == 'Y') {
                $paySumm += $paymentCrm['amount'];
            }
        }
        foreach ($paymentsList as $payment) {
            if ($payment->isPaid()) {
                $payment->setPaid("N");
            }
            $payment->delete();
        }
        $paymentColl->save();
        
        if ($paymentsCrm['totalSumm'] == $paySumm) {
            $order->setFieldNoDemand('PAYED', 'Y');
        } else {
            $order->setFieldNoDemand('PAYED', 'N');
        }
        $order->save();
        
        return $order;
    }
    
    public static function newValue($value)
    {
        if (array_key_exists('code', $value)) {
            return $value['code'];
        } else {
            return $value;
        }
    }
    
    public static function removeEmpty($inputArray)
    {
        $outputArray = array();

        if (!empty($inputArray)) {
            foreach ($inputArray as $key => $element) {
                if (!empty($element) || $element === 0 || $element === '0') {
                    if (is_array($element)) {
                        $element = self::removeEmpty($element);
                    }

                    $outputArray[$key] = $element;
                }
            }
        }

        return $outputArray;
    }
    
    public static function setProp($obj, $value = '', $prop)
    {
        if (!isset($obj)) {
            return false;
        }
        if ($prop) {
            $obj->setField($prop, $value);
        } elseif ($value) {
            $obj->setValue($value);
        } else {
            $obj->delete();
        }

        return true;
    }
    
    public static function getExistsItem($basket, $moduleId, $productId)
    {
        foreach ($basket as $basketItem) {
            $itemExists = ($basketItem->getField('PRODUCT_ID') == $productId && $basketItem->getField('MODULE') == $moduleId);

            if ($itemExists) {
                return $basketItem;
            }
        }

        return false;
    }
    
    public static function getInfoElement($offerId)
    {
        $elementInfo = CIBlockElement::GetByID($offerId)->fetch();
        $url = CAllIBlock::ReplaceDetailUrl($elementInfo['DETAIL_PAGE_URL'], $elementInfo, false, 'E');
        $catalog = CCatalogProduct::GetByID($offerId);

        $info = array(
            'NAME' => $elementInfo['NAME'],
            'URL' => $url,
            'DIMENSIONS' => serialize(serialize(array(
                'WIDTH' => $catalog['WIDTH'],
                'HEIGHT' => $catalog['HEIGHT'],
                'LENGTH' => $catalog['LENGTH'],
            ))),
            'WEIGHT' => $catalog['WEIGHT']
        );
        
        return $info;
    }
}   

class RetailUser extends CUser
{
    public function GetID()
    {
        $rsUser = CUser::GetList(($by = 'ID'), ($order = 'DESC'), array('LOGIN' => 'retailcrm%'));

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