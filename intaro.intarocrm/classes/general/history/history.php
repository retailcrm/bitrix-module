<?
$idOrderCRM = (int) $_REQUEST['idOrderCRM'];
if($idOrderCRM && $idOrderCRM > 0){
    require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

    if (!CModule::IncludeModule("iblock")) {
        ICrmOrderActions::eventLog('ICrmOrderActions::orderHistory', 'iblock', 'module not found');
        return false;
    }
    if (!CModule::IncludeModule("sale")) {
        ICrmOrderActions::eventLog('ICrmOrderActions::orderHistory', 'sale', 'module not found');
        return false;
    }
    if (!CModule::IncludeModule("catalog")) {
        ICrmOrderActions::eventLog('ICrmOrderActions::orderHistory', 'catalog', 'module not found');
        return false;
    }
    if (!CModule::IncludeModule("intaro.intarocrm")) {
        ICrmOrderActions::eventLog('ICrmOrderActions::orderHistory', 'intaro.intarocrm', 'module not found');
        return false;
    }
    
    global $USER;
    if (is_object($USER) == false) {
        $USER = new RetailUser;
    }
    
    $api_host = COption::GetOptionString(ICrmOrderActions::$MODULE_ID, ICrmOrderActions::$CRM_API_HOST_OPTION, 0);
    $api_key = COption::GetOptionString(ICrmOrderActions::$MODULE_ID, ICrmOrderActions::$CRM_API_KEY_OPTION, 0);

    $optionsOrderTypes = array_flip(unserialize(COption::GetOptionString(ICrmOrderActions::$MODULE_ID, ICrmOrderActions::$CRM_ORDER_TYPES_ARR, 0)));
    $optionsDelivTypes = array_flip(unserialize(COption::GetOptionString(ICrmOrderActions::$MODULE_ID, ICrmOrderActions::$CRM_DELIVERY_TYPES_ARR, 0)));
    $optionsPayTypes = array_flip(unserialize(COption::GetOptionString(ICrmOrderActions::$MODULE_ID, ICrmOrderActions::$CRM_PAYMENT_TYPES, 0)));
    $optionsPayStatuses = array_flip(unserialize(COption::GetOptionString(ICrmOrderActions::$MODULE_ID, ICrmOrderActions::$CRM_PAYMENT_STATUSES, 0))); // --statuses
    $optionsPayment = array_flip(unserialize(COption::GetOptionString(ICrmOrderActions::$MODULE_ID, ICrmOrderActions::$CRM_PAYMENT, 0)));
    $optionsOrderProps = unserialize(COption::GetOptionString(ICrmOrderActions::$MODULE_ID, ICrmOrderActions::$CRM_ORDER_PROPS, 0));
    $optionsLegalDetails = unserialize(COption::GetOptionString(ICrmOrderActions::$MODULE_ID, ICrmOrderActions::$CRM_LEGAL_DETAILS, 0));
    $optionsContragentType = unserialize(COption::GetOptionString(ICrmOrderActions::$MODULE_ID, ICrmOrderActions::$CRM_CONTRAGENT_TYPE, 0));
    $optionsSitesList = unserialize(COption::GetOptionString(ICrmOrderActions::$MODULE_ID, ICrmOrderActions::$CRM_SITES_LIST, 0));
    $optionsCustomFields = unserialize(COption::GetOptionString(ICrmOrderActions::$MODULE_ID, ICrmOrderActions::$CRM_CUSTOM_FIELDS, 0));

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
    
    $order = $api->orderGet($idOrderCRM, $by = 'id');
    
    $log = new Logger();
    $log->write($order, 'order');
        
    $defaultOrderType = 1;
    $dbOrderTypesList = CSalePersonType::GetList(array(), array("ACTIVE" => "Y"));
    if ($arOrderTypesList = $dbOrderTypesList->Fetch()) {
        $defaultOrderType = $arOrderTypesList['ID'];
    }
    
    $GLOBALS['INTARO_CRM_FROM_HISTORY'] = true;

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
                die();
            }

            $registerNewUser = true;

            if (!isset($order['customer']['email']) && $order['customer']['email'] != '') {
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
                    "NAME"              => ICrmOrderActions::fromJSON($order['customer']['firstName']),
                    "LAST_NAME"         => ICrmOrderActions::fromJSON($order['customer']['lastName']),
                    "EMAIL"             => $order['customer']['email'],
                    "LOGIN"             => $login,
                    "LID"               => "ru",
                    "ACTIVE"            => "Y",
                    "PASSWORD"          => $userPassword,
                    "CONFIRM_PASSWORD"  => $userPassword
                );
                $registeredUserID = $newUser->Add($arFields);
                if ($registeredUserID === false) {
                    ICrmOrderActions::eventLog('ICrmOrderActions::orderHistory', 'CUser::Register', 'Error register user');
                    die();
                }

                try {
                    $api->customerFixExternalIds(array(array('id' => $order['customer']['id'], 'externalId' => $registeredUserID)));
                } catch (\IntaroCrm\Exception\CurlException $e) {
                    ICrmOrderActions::eventLog(
                        'ICrmOrderActions::orderHistory', 'RetailCrm\RestApi::customerFixExternalIds::CurlException',
                        $e->getCode() . ': ' . $e->getMessage()
                    );

                    die();
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
            } catch (\IntaroCrm\Exception\CurlException $e) {
                ICrmOrderActions::eventLog(
                    'ICrmOrderActions::orderHistory', 'RetailCrm\RestApi::orderFixExternalIds::CurlException',
                    $e->getCode() . ': ' . $e->getMessage()
                );

                die();
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
            die();
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
            ICrmOrderActions::clearArr($order),
            'ICrmOrderActions::recursiveUpdate',
            array(
                'update'  => $arUpdateProps,
                'type'    => $arFields['PERSON_TYPE_ID'],
                'options' => $optionsOrderProps,
                'orderId' => $order['externalId']
            )
        );

        //выбираем товары по текущему заказу с сайта
        $bItms = array();
        $p = CSaleBasket::GetList(array(), array('ORDER_ID' => $order['externalId']));
        while($bItm = $p->Fetch()){
            $bItms[$bItm['PRODUCT_ID']] = $bItm;
        }
        
        //перебираем хистори
        $CrmItms = array();      
        foreach($order['items'] as $item) {
            $CrmItms[] = $item['id'];
            //если такой товар есть
            if(in_array($item['offer']['externalId'], $bItms)){
                if ((int) $item['quantity'] != (int) $bItms[$item['offer']['externalId']]['QUANTITY']) {
                    $arProduct['QUANTITY'] = $item['quantity'];                    
                    $g = CSaleBasket::Update($bItms[$item['offer']['externalId']]['ID'], $arProduct);
                    //резерв
                    $ar_res = CCatalogProduct::GetByID($item['offer']['externalId']);                   
                    $arFields = array(
                        'QUANTITY' => (int)$ar_res['QUANTITY'] + (int)$bItms[$item['offer']['externalId']]['QUANTITY'] - (int) $item['quantity'],
                        'QUANTITY_RESERVED' => (int)$ar_res['QUANTITY_RESERVED'] - (int)$bItms[$item['offer']['externalId']]['QUANTITY'] + (int) $item['quantity'],
                    );
                    $d = CCatalogProduct::Update($item['offer']['externalId'], $arFields);
                }
            }//если нет, добавляем
            else{
                $p = CIBlockElement::GetByID($item['offer']['externalId'])->Fetch();
                $iblock = CIBlock::GetByID($p['IBLOCK_ID'])->Fetch();
                $p['CATALOG_XML_ID'] = $iblock['XML_ID'];
                $p['PRODUCT_XML_ID'] = $p['XML_ID'];
                $arProduct = array(
                    'FUSER_ID'               => $userId,
                    'ORDER_ID'               => $order['externalId'],
                    'QUANTITY'               => $item['quantity'],
                    'CURRENCY'               => CCurrency::GetBaseCurrency(),
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
                    $arProduct['NAME'] = ICrmOrderActions::fromJSON($item['offer']['name']);
                }
 
                $op = CSaleBasket::Add($arProduct);
                //резерв
                $ar_res = CCatalogProduct::GetByID($item['offer']['externalId']);
                $arFields = array(
                    'QUANTITY' => (int)$ar_res['QUANTITY'] - (int)$item['quantity'],
                    'QUANTITY_RESERVED' => (int)$ar_res['QUANTITY_RESERVED'] + (int)$item['quantity'],
                );
                $d = CCatalogProduct::Update($item['offer']['externalId'], $arFields);
            }
        }
        //удаляем лишние товары
        foreach($bItms as $bItm){
            if(!in_array($bItm['PRODUCT_ID'], $CrmItms)){
                CSaleBasket::Delete($bItm['ID']);
                //удаляем товары из резерва
                $ar_res = CCatalogProduct::GetByID($bItm['PRODUCT_ID']);
                $arFields = array(
                    'QUANTITY' => (int)$ar_res['QUANTITY'] + (int)$bItm['QUANTITY'],
                    'QUANTITY_RESERVED' => (int)$ar_res['QUANTITY_RESERVED'] - (int)$bItm['QUANTITY'],
                );
                $d = CCatalogProduct::Update($bItm['PRODUCT_ID'], $arFields);
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
        $arFields = ICrmOrderActions::clearArr(array(
            'PRICE_DELIVERY'   => $order['delivery']['cost'],
            'PRICE'            => $order['summ'] + (double) $order['delivery']['cost'],
            'DATE_MARKED'      => $order['markDatetime'],
            'USER_ID'          => $userId,
            'PAY_SYSTEM_ID'    => $optionsPayTypes[$order['paymentType']],
            'DELIVERY_ID'      => $resultDeliveryTypeId,
            'STATUS_ID'        => $optionsPayStatuses[$order['status']],
            'REASON_CANCELED'  => ICrmOrderActions::fromJSON($order['statusComment']),
            'USER_DESCRIPTION' => ICrmOrderActions::fromJSON($order['customerComment']),
            'COMMENTS'         => ICrmOrderActions::fromJSON($order['managerComment'])
        ));

        if (isset($order['discount'])) {
            $arFields['DISCOUNT_VALUE'] = $order['discount'];
            $arFields['PRICE'] -= $order['discount'];
        }

        if(!empty($arFields)) {
            CSaleOrder::Update($order['externalId'], $arFields);
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
    $GLOBALS['INTARO_CRM_FROM_HISTORY'] = false;
}
?>