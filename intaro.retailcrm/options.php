<?php
IncludeModuleLangFile(__FILE__);
$mid = 'intaro.retailcrm';
$uri = $APPLICATION->GetCurPage() . '?mid=' . htmlspecialchars($mid) . '&lang=' . LANGUAGE_ID;

$CRM_API_HOST_OPTION = 'api_host';
$CRM_API_KEY_OPTION = 'api_key';
$CRM_ORDER_TYPES_ARR = 'order_types_arr';
$CRM_DELIVERY_TYPES_ARR = 'deliv_types_arr';
$CRM_DELIVERY_SERVICES_ARR = 'deliv_services_arr';
$CRM_PAYMENT_TYPES = 'pay_types_arr';
$CRM_PAYMENT_STATUSES = 'pay_statuses_arr';
$CRM_PAYMENT = 'payment_arr'; //order payment Y/N
$CRM_ORDER_LAST_ID = 'order_last_id';
$CRM_ORDER_SITES = 'sites_ids';
$CRM_ORDER_DISCHARGE = 'order_discharge';
$CRM_ORDER_PROPS = 'order_props';
$CRM_LEGAL_DETAILS = 'legal_details';
$CRM_CUSTOM_FIELDS = 'custom_fields';
$CRM_CONTRAGENT_TYPE = 'contragent_type';
$CRM_SITES_LIST= 'sites_list';
$CRM_ORDER_NUMBERS = 'order_numbers';
$CRM_CANSEL_ORDER = 'cansel_order';

$CRM_INVENTORIES_UPLOAD = 'inventories_upload';
$CRM_STORES = 'stores';
$CRM_SHOPS = 'shops';
$CRM_IBLOCKS_INVENTORIES = 'iblocks_inventories';

$CRM_PRICES_UPLOAD = 'prices_upload';
$CRM_PRICES = 'prices';
$CRM_PRICE_SHOPS = 'price_shops';
$CRM_IBLOCKS_PRICES = 'iblock_prices';

$CRM_COLLECTOR = 'collector';
$CRM_COLL_KEY = 'coll_key';

$CRM_UA = 'ua';
$CRM_UA_KEYS = 'ua_keys';

$CRM_DISCOUNT_ROUND = 'discount_round';

$CRM_CC = 'cc';
$CRM_CORP_SHOPS = 'shops-corporate';
$CRM_CORP_NAME = 'nickName-corporate';
$CRM_CORP_ADRES = 'adres-corporate';

$CRM_API_VERSION = 'api_version';

$CRM_CURRENCY = 'currency';
$CRM_ADDRESS_OPTIONS = 'address_options';
$CRM_DIMENSIONS = 'order_dimensions';
$PROTOCOL = 'protocol';

$CRM_PURCHASE_PRICE_NULL = 'purchasePrice_null';

if(!CModule::IncludeModule('intaro.retailcrm') || !CModule::IncludeModule('sale') || !CModule::IncludeModule('iblock') || !CModule::IncludeModule('catalog'))
    return;

$_GET['errc'] = htmlspecialchars(trim($_GET['errc']));
$_GET['ok'] = htmlspecialchars(trim($_GET['ok']));

if (RetailcrmConfigProvider::isPhoneRequired()) {
    echo ShowMessage(array("TYPE"=>"ERROR", "MESSAGE"=>GetMessage('PHONE_REQUIRED')));
}

if($_GET['errc']) echo CAdminMessage::ShowMessage(GetMessage($_GET['errc']));
if($_GET['ok'] && $_GET['ok'] == 'Y') echo CAdminMessage::ShowNote(GetMessage('ICRM_OPTIONS_OK'));

$arResult = array();

if (file_exists($_SERVER["DOCUMENT_ROOT"] . '/bitrix/modules/intaro.retailcrm/classes/general/config/options.xml')) {
    $options = simplexml_load_file($_SERVER["DOCUMENT_ROOT"] . '/bitrix/modules/intaro.retailcrm/classes/general/config/options.xml');

    foreach($options->contragents->contragent as $contragent) {
        $type["NAME"] = $APPLICATION->ConvertCharset((string)$contragent, 'utf-8', SITE_CHARSET);
        $type["ID"] = (string)$contragent["id"];
        $arResult['contragentType'][] = $type;
        unset ($type);
    }
    foreach($options->fields->field as $field) {
        $type["NAME"] = $APPLICATION->ConvertCharset((string)$field, 'utf-8', SITE_CHARSET);
        $type["ID"] = (string)$field["id"];

        if ($field["group"] == 'custom') {
            $arResult['customFields'][] = $type;
        } elseif (!$field["group"]) {
            $arResult['orderProps'][] = $type;
        } else {
            $groups = explode(",", (string)$field["group"]);
            foreach ($groups as $group) {
                $type["GROUP"][] = trim($group);
            }
            $arResult['legalDetails'][] = $type;
        }
        unset($type);
    }
}

$arResult['arSites'] = RCrmActions::SitesList();
//ajax update deliveryServices
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && (strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') && isset($_POST['ajax']) && ($_POST['ajax'] == 1)) {
    $api_host = COption::GetOptionString($mid, $CRM_API_HOST_OPTION, 0);
    $api_key = COption::GetOptionString($mid, $CRM_API_KEY_OPTION, 0);
    $api = new RetailCrm\ApiClient($api_host, $api_key);

    try {
        $api->paymentStatusesList();
    } catch (\RetailCrm\Exception\CurlException $e) {
        RCrmActions::eventLog(
            'intaro.retailcrm/options.php', 'RetailCrm\ApiClient::paymentStatusesList::CurlException',
            $e->getCode() . ': ' . $e->getMessage()
        );

        $APPLICATION->RestartBuffer();
        header('Content-Type: application/x-javascript; charset=' . LANG_CHARSET);
        die(json_encode(array('success' => false, 'errMsg' => $e->getCode())));
    }

    $optionsDelivTypes = unserialize(COption::GetOptionString($mid, $CRM_DELIVERY_TYPES_ARR, 0));
    $arDeliveryServiceAll = \Bitrix\Sale\Delivery\Services\Manager::getActiveList();

    foreach ($optionsDelivTypes as $key => $deliveryType) {
        foreach ($arDeliveryServiceAll as $deliveryService) {
            if ($deliveryService['PARENT_ID'] != 0 && $deliveryService['PARENT_ID'] == $key) {
                $srv = explode(':', $deliveryService['CODE']);
                if (count($srv) == 2) {
                    try {
                        $api->deliveryServicesEdit(RCrmActions::clearArr(array(
                            'code' => $srv[1],
                            'name' => RCrmActions::toJSON($deliveryService['NAME']),
                            'deliveryType' => $deliveryType
                        )));
                    } catch (\RetailCrm\Exception\CurlException $e) {
                        RCrmActions::eventLog(
                            'intaro.retailcrm/options.php', 'RetailCrm\ApiClient::deliveryServiceEdit::CurlException',
                            $e->getCode() . ': ' . $e->getMessage()
                        );
                    }
                }
            }
        }
    }

    $APPLICATION->RestartBuffer();
    header('Content-Type: application/x-javascript; charset=' . LANG_CHARSET);
    die(json_encode(array('success' => true)));
}

//upload orders after install module
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && (strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') && isset($_POST['ajax']) && $_POST['ajax'] == 2) {
    $step = $_POST['step'];
    $orders = $_POST['orders'];
    $countStep = 50; // 50 orders on step

    if ($orders) {
        $ordersArr = explode(',', $orders);
        $orders = array();
        foreach ($ordersArr as $_ordersArr) {
            $ordersList = explode('-', trim($_ordersArr));
            if (count($ordersList) > 1) {
                for ($i = (int)trim($ordersList[0]); $i <= (int)trim($ordersList[count($ordersList) - 1]); $i++) {
                    $orders[] = $i;
                }
            } else{
                $orders[] = (int)$ordersList[0];
            }
        }

        $splitedOrders = array_chunk($orders, $countStep);
        $stepOrders = $splitedOrders[$step];

        RetailCrmOrder::uploadOrders($countStep, false, $stepOrders);

        $percent = round((($step * $countStep + count($stepOrders)) * 100 / count($orders)), 1);
        $step++;

        if (!$splitedOrders[$step]) {
            $step = 'end';
        }

        $res = array("step" => $step, "percent" => $percent, 'stepOrders' => $stepOrders);
    } else {
        $orders = array();
        for($i = 1; $i <= $countStep; $i++){
            $orders[] = $i + $step * $countStep;
        }

        RetailCrmOrder::uploadOrders($countStep, false, $orders);

        $step++;
        $countLeft = (int) CSaleOrder::GetList(array("ID" => "ASC"), array('>ID' => $step * $countStep), array());
        $countAll = (int) CSaleOrder::GetList(array("ID" => "ASC"), array(), array());
        $percent = round(100 - ($countLeft * 100 / $countAll), 1);

        if ($countLeft == 0) {
            $step = 'end';
        }

        $res = array("step" => $step, "percent" => $percent, 'stepOrders' => $orders);
    }

    $APPLICATION->RestartBuffer();
    header('Content-Type: application/x-javascript; charset=' . LANG_CHARSET);
    die(json_encode($res));
}

//update connection settings
if (isset($_POST['Update']) && ($_POST['Update'] == 'Y')) {
    $api_host = htmlspecialchars(trim($_POST['api_host']));
    $api_key = htmlspecialchars(trim($_POST['api_key']));

    //bitrix site list
    $siteListArr = array();
    foreach ($arResult['arSites'] as $arSites) {
        if (count($arResult['arSites']) > 1) {
            if ($_POST['sites-id-' . $arSites['LID']]) {
                $siteListArr[$arSites['LID']] = htmlspecialchars(trim($_POST['sites-id-' . $arSites['LID']]));
            } else {
                $siteListArr[$arSites['LID']] = null;
            }
        }
    }

    if ($api_host && $api_key) {
        $api = new RetailCrm\ApiClient($api_host, $api_key);
        try {
            $api->paymentStatusesList();
        } catch (\RetailCrm\Exception\CurlException $e) {
            RCrmActions::eventLog(
                'intaro.retailcrm/options.php', 'RetailCrm\ApiClient::paymentStatusesList::CurlException',
                $e->getCode() . ': ' . $e->getMessage()
            );

            $uri .= '&errc=ERR_' . $e->getCode();
            LocalRedirect($uri);
        }

        COption::SetOptionString($mid, 'api_host', $api_host);
        COption::SetOptionString($mid, 'api_key', $api_key);
    }

    //form order types ids arr
    $orderTypesList = RCrmActions::OrderTypesList($arResult['arSites']);

    $orderTypesArr = array();
    foreach ($orderTypesList as $orderType) {
        $orderTypesArr[$orderType['ID']] = htmlspecialchars(trim($_POST['order-type-' . $orderType['ID']]));
    }

    //form delivery types ids arr
    $arResult['bitrixDeliveryTypesList'] = RCrmActions::DeliveryList();

    $deliveryTypesArr = array();
    foreach ($arResult['bitrixDeliveryTypesList'] as $delivery) {
        $deliveryTypesArr[$delivery['ID']] = htmlspecialchars(trim($_POST['delivery-type-' . $delivery['ID']]));
    }

    //form payment types ids arr
    $arResult['bitrixPaymentTypesList'] = RCrmActions::PaymentList();

    $paymentTypesArr = array();
    foreach ($arResult['bitrixPaymentTypesList'] as $payment) {
        $paymentTypesArr[$payment['ID']] = htmlspecialchars(trim($_POST['payment-type-' . $payment['ID']]));
    }

    //form payment statuses ids arr
    $arResult['bitrixStatusesList'] = RCrmActions::StatusesList();

    $paymentStatusesArr = array();
    $canselOrderArr = array();
    //$paymentStatusesArr['YY'] = htmlspecialchars(trim($_POST['payment-status-YY']));
    foreach ($arResult['bitrixStatusesList'] as $status) {
        $paymentStatusesArr[$status['ID']] = htmlspecialchars(trim($_POST['payment-status-' . $status['ID']]));
        if (trim($_POST['order-cansel-' . $status['ID']]) == 'Y') {
            $canselOrderArr[] = $status['ID'];
        }
    }

    //form payment ids arr
    $paymentArr = array();
    $paymentArr['Y'] = htmlspecialchars(trim($_POST['payment-Y']));
    $paymentArr['N'] = htmlspecialchars(trim($_POST['payment-N']));

    $previousDischarge = COption::GetOptionString($mid, $CRM_ORDER_DISCHARGE, 0);
    //order discharge mode
    // 0 - agent
    // 1 - event
    $orderDischarge = 0;
    $orderDischarge = (int) htmlspecialchars(trim($_POST['order-discharge']));
    if (($orderDischarge != $previousDischarge) && ($orderDischarge == 0)) {
        // remove depenedencies
        UnRegisterModuleDependences("sale", \Bitrix\sale\EventActions::EVENT_ON_ORDER_SAVED, $mid, "RetailCrmEvent", "orderSave");
        UnRegisterModuleDependences("sale", "OnOrderUpdate", $mid, "RetailCrmEvent", "onUpdateOrder");
        UnRegisterModuleDependences("sale", "OnSaleOrderDeleted", $mid, "RetailCrmEvent", "orderDelete");
    } elseif (($orderDischarge != $previousDischarge) && ($orderDischarge == 1)) {
        // event dependencies
        RegisterModuleDependences("sale", \Bitrix\sale\EventActions::EVENT_ON_ORDER_SAVED, $mid, "RetailCrmEvent", "orderSave");
        RegisterModuleDependences("sale", "OnOrderUpdate", $mid, "RetailCrmEvent", "onUpdateOrder");
        RegisterModuleDependences("sale", "OnSaleOrderDeleted", $mid, "RetailCrmEvent", "orderDelete");
    }

    $orderPropsArr = array();
    foreach ($orderTypesList as $orderType) {
        $propsCount = 0;
        $_orderPropsArr = array();
        foreach ($arResult['orderProps'] as $orderProp) {
            if (isset($_POST['address-detail-' . $orderType['ID']])) {
                $addressDatailOptions[$orderType['ID']] = $_POST['address-detail-' . $orderType['ID']];
            }

            if ((!(int) htmlspecialchars(trim($_POST['address-detail-' . $orderType['ID']]))) && $propsCount > 4) {
                break;
            }
            $_orderPropsArr[$orderProp['ID']] = htmlspecialchars(trim($_POST['order-prop-' . $orderProp['ID'] . '-' . $orderType['ID']]));
            $propsCount++;
        }
        $orderPropsArr[$orderType['ID']] = $_orderPropsArr;
    }

    //legal details props
    $legalDetailsArr = array();
    foreach ($orderTypesList as $orderType) {
        $_legalDetailsArr = array();
        foreach ($arResult['legalDetails'] as $legalDetails) {
            $_legalDetailsArr[$legalDetails['ID']] = htmlspecialchars(trim($_POST['legal-detail-' . $legalDetails['ID'] . '-' . $orderType['ID']]));
        }
        $legalDetailsArr[$orderType['ID']] = $_legalDetailsArr;
    }

    $customFieldsArr = array();
    foreach ($orderTypesList as $orderType) {
        $_customFieldsArr = array();
        foreach ($arResult['customFields'] as $custom) {
            $_customFieldsArr[$custom['ID']] = htmlspecialchars(trim($_POST['custom-fields-' . $custom['ID'] . '-' . $orderType['ID']]));
        }
        $customFieldsArr[$orderType['ID']] = $_customFieldsArr;
    }

    //contragents type list
    $contragentTypeArr = array();
    foreach ($orderTypesList as $orderType) {
        $contragentTypeArr[$orderType['ID']] = htmlspecialchars(trim($_POST['contragent-type-' . $orderType['ID']]));
    }
    //order numbers
    $orderNumbers = htmlspecialchars(trim($_POST['order-numbers'])) ? htmlspecialchars(trim($_POST['order-numbers'])) : 'N';
    $orderDimensions = htmlspecialchars(trim($_POST[$CRM_DIMENSIONS])) ? htmlspecialchars(trim($_POST[$CRM_DIMENSIONS])) : 'N';
    $sendPaymentAmount = htmlspecialchars(trim($_POST[RetailcrmConstants::SEND_PAYMENT_AMOUNT])) ? htmlspecialchars(trim($_POST[RetailcrmConstants::SEND_PAYMENT_AMOUNT])) : 'N';

    //stores
    $bitrixStoresArr = array();
    $bitrixShopsArr = array();
    $bitrixIblocksInventories = array();
    if(htmlspecialchars(trim($_POST['inventories-upload'])) == 'Y'){
        $inventoriesUpload = 'Y';
        $dateAgent = new DateTime();
        $intAgent = new DateInterval('PT60S'); // PT60S - 60 sec;
        $dateAgent->add($intAgent);

        CAgent::AddAgent(
            "RetailCrmInventories::inventoriesUpload();", $mid, "N", 3600, // interval - 1 hour
            $dateAgent->format('d.m.Y H:i:s'), // date of first check
            "Y", // agent is active
            $dateAgent->format('d.m.Y H:i:s'), // date of first start
            30
        );

        $arResult['bitrixStoresExportList'] = RCrmActions::StoresExportList();
        foreach($arResult['bitrixStoresExportList'] as $bitrixStores){
            $bitrixStoresArr[$bitrixStores['ID']] = htmlspecialchars(trim($_POST['stores-export-' . $bitrixStores['ID']]));
        }

        function maskInv($var){
            return preg_match("/^shops-exoprt/", $var);
        }
        $bitrixShopsArr = str_replace('shops-exoprt-', '', array_filter(array_keys($_POST), 'maskInv'));

        $arResult['bitrixIblocksExportList'] = RCrmActions::IblocksExportList();
        foreach($arResult['bitrixIblocksExportList'] as $bitrixIblocks){
            if(htmlspecialchars(trim($_POST['iblocks-stores-' . $bitrixIblocks['ID']])) === 'Y'){
                $bitrixIblocksInventories[] = $bitrixIblocks['ID'];
            }
        }
    } else {
        $inventoriesUpload = 'N';
        CAgent::RemoveAgent("RetailCrmInventories::inventoriesUpload();", $mid);
    }

    //prices
    $bitrixPricesArr = array();
    $bitrixIblocksPrices = array();
    $bitrixPriceShopsArr = array();
    if(htmlspecialchars(trim($_POST['prices-upload'])) == 'Y'){
        $pricesUpload = 'Y';

        $dateAgent = new DateTime();
        $intAgent = new DateInterval('PT60S'); // PT60S - 60 sec;
        $dateAgent->add($intAgent);

        CAgent::AddAgent(
            "RetailCrmPrices::pricesUpload();", $mid, "N", 86400, // interval - 24 hours
            $dateAgent->format('d.m.Y H:i:s'), // date of first check
            "Y", // agent is active
            $dateAgent->format('d.m.Y H:i:s'), // date of first start
            30
        );

        $arResult['bitrixPricesExportList'] = RCrmActions::PricesExportList();
        foreach($arResult['bitrixPricesExportList'] as $bitrixPrices){
            $bitrixPricesArr[$bitrixPrices['ID']] = htmlspecialchars(trim($_POST['price-type-export-' . $bitrixPrices['ID']]));
        }

        function maskPrice($var){
            return preg_match("/^shops-price/", $var);
        }
        $bitrixPriceShopsArr = str_replace('shops-price-', '', array_filter(array_keys($_POST), 'maskPrice'));

        $arResult['bitrixIblocksExportList'] = RCrmActions::IblocksExportList();
        foreach($arResult['bitrixIblocksExportList'] as $bitrixIblocks){
            if(htmlspecialchars(trim($_POST['iblocks-prices-' . $bitrixIblocks['ID']])) === 'Y'){
                $bitrixIblocksPrices[] = $bitrixIblocks['ID'];
            }
        }
    } else {
        $pricesUpload = 'N';
        CAgent::RemoveAgent("RetailCrmPrices::pricesUpload();", $mid);
    }

    //demon
    $collectorKeys = array();
    if (htmlspecialchars(trim($_POST['collector'])) == 'Y') {
        $collector = 'Y';
        foreach ($arResult['arSites'] as $site) {
            $collectorKeys[$site['LID']] = trim($_POST['collector-id-' . $site['LID']]);
        }
        RegisterModuleDependences("main", "OnBeforeProlog", $mid, "RetailCrmCollector", "add");
    } else  {
        $collector = 'N';
        UnRegisterModuleDependences("main", "OnBeforeProlog", $mid, "RetailCrmCollector", "add");
    }

    //UA
    $uaKeys = array();
    if (htmlspecialchars(trim($_POST['ua-integration'])) == 'Y') {
        $ua = 'Y';
        foreach ($arResult['arSites'] as $site) {
            $uaKeys[$site['LID']]['ID'] = trim($_POST['ua-id-' . $site['LID']]);
            $uaKeys[$site['LID']]['INDEX'] = trim($_POST['ua-index-' . $site['LID']]);
        }
        RegisterModuleDependences("main", "OnBeforeProlog", $mid, "RetailCrmUa", "add");
    } else  {
        $ua = 'N';
        UnRegisterModuleDependences("main", "OnBeforeProlog", $mid, "RetailCrmUa", "add");
    }

    //online_consultant
    if (htmlspecialchars(trim($_POST['online_consultant'] == 'Y'))) {
        $onlineConsultant = 'Y';
        $onlineConsultantScript = trim($_POST['online_consultant_script']);
        RegisterModuleDependences("main", "OnBeforeProlog", $mid, "RetailCrmOnlineConsultant", "add");
    } else {
        $onlineConsultant = 'N';
        $onlineConsultantScript = RetailcrmConfigProvider::getOnlineConsultantScript();
        UnRegisterModuleDependences("main", "OnBeforeProlog", $mid, "RetailCrmOnlineConsultant", "add");
    }

    //discount_round
    if (htmlspecialchars(trim($_POST['discount_round'])) == 'Y') {
        $discount_round = 'Y';
        RegisterModuleDependences("main", "OnBeforeProlog", $mid, "RetailCrmDc", "add");
    } else {
        $discount_round = 'N';
        UnRegisterModuleDependences("main", "OnBeforeProlog", $mid, "RetailCrmDc", "add");
    }

    //shipment
    if (htmlspecialchars(trim($_POST['shipment_deducted'])) == 'Y') {
        $shipment_deducted = 'Y';
    } else {
        $shipment_deducted = 'N';
    }

    //corporate-cliente
    if (htmlspecialchars(trim($_POST['corp-client'])) == 'Y') {
        $cc = 'Y';
        $bitrixCorpName = htmlspecialchars(trim($_POST['nickName-corporate']));
        $bitrixCorpAdres = htmlspecialchars(trim($_POST['adres-corporate']));
        function maskCorp($var) {
            return preg_match("/^shops-corporate/", $var);
        }
        $bitrixCorpShopsArr = str_replace('shops-corporate-', '', array_filter(array_keys($_POST), 'maskCorp'));

        RegisterModuleDependences("main", "OnBeforeProlog", $mid, "RetailCrmCc", "add");
    } else  {
        $cc = 'N';
        UnRegisterModuleDependences("main", "OnBeforeProlog", $mid, "RetailCrmCc", "add");
    }

    //purchasePrice_null
    if (htmlspecialchars(trim($_POST['purchasePrice_null'])) == 'Y') {
        $purchasePrice_null = 'Y';
        RegisterModuleDependences("main", "OnBeforeProlog", $mid, "RetailCrmPricePrchase", "add");
    } else  {
        $purchasePrice_null = 'N';
        UnRegisterModuleDependences("main", "OnBeforeProlog", $mid, "RetailCrmPricePrchase", "add");
    }

    //version

    $version = COption::GetOptionString($mid, $CRM_API_VERSION);

    if (htmlspecialchars(trim($_POST['api_version'])) != $version) {
        if (htmlspecialchars(trim($_POST['api_version'])) == 'v4') {
            $version = 'v4';
        } elseif (htmlspecialchars(trim($_POST['api_version'])) == 'v5') {
            $version = 'v5';
        } else {
            LocalRedirect($uri);
            echo CAdminMessage::ShowMessage(GetMessage('API_NOT_FOUND'));
        }

        //api request with $version
        $crmUrl = htmlspecialchars(trim($_POST['api_host']));
        $apiKey = htmlspecialchars(trim($_POST['api_key']));

        if ('/' !== $crmUrl[strlen($crmUrl) - 1]) {
            $crmUrl .= '/';
        }

        $crmUrl = $crmUrl . 'api/' . $version;

        $client = new RetailCrm\Http\Client($crmUrl, array('apiKey' => $apiKey));
        $result = $client->makeRequest(
            '/reference/payment-statuses',
            'GET'
        );

        if ($result->getStatusCode() == 200) {
            COption::SetOptionString($mid, $CRM_API_VERSION, $version);
        } else {
            LocalRedirect($uri);
            echo CAdminMessage::ShowMessage(GetMessage('API_NOT_WORK'));
        }
    }

    if ($_POST[$CRM_CURRENCY]) {
        COption::SetOptionString($mid, $CRM_CURRENCY, $_POST['currency']);
    }

    COption::SetOptionString($mid, $CRM_ADDRESS_OPTIONS, serialize($addressDatailOptions));
    COption::SetOptionString($mid, $CRM_SITES_LIST, serialize($siteListArr));
    COption::SetOptionString($mid, $CRM_ORDER_TYPES_ARR, serialize(RCrmActions::clearArr($orderTypesArr)));
    COption::SetOptionString($mid, $CRM_DELIVERY_TYPES_ARR, serialize(RCrmActions::clearArr($deliveryTypesArr)));
    COption::SetOptionString($mid, $CRM_PAYMENT_TYPES, serialize(RCrmActions::clearArr($paymentTypesArr)));
    COption::SetOptionString($mid, $CRM_PAYMENT_STATUSES, serialize(RCrmActions::clearArr($paymentStatusesArr)));
    COption::SetOptionString($mid, $CRM_PAYMENT, serialize(RCrmActions::clearArr($paymentArr)));
    COption::SetOptionString($mid, $CRM_ORDER_DISCHARGE, $orderDischarge);
    COption::SetOptionString($mid, $CRM_ORDER_PROPS, serialize(RCrmActions::clearArr($orderPropsArr)));
    COption::SetOptionString($mid, $CRM_CONTRAGENT_TYPE, serialize(RCrmActions::clearArr($contragentTypeArr)));
    COption::SetOptionString($mid, $CRM_LEGAL_DETAILS, serialize(RCrmActions::clearArr($legalDetailsArr)));
    COption::SetOptionString($mid, $CRM_CUSTOM_FIELDS, serialize(RCrmActions::clearArr($customFieldsArr)));
    COption::SetOptionString($mid, $CRM_ORDER_NUMBERS, $orderNumbers);
    COption::SetOptionString($mid, $CRM_CANSEL_ORDER, serialize(RCrmActions::clearArr($canselOrderArr)));

    COption::SetOptionString($mid, $CRM_INVENTORIES_UPLOAD, $inventoriesUpload);
    COption::SetOptionString($mid, $CRM_STORES, serialize(RCrmActions::clearArr($bitrixStoresArr)));
    COption::SetOptionString($mid, $CRM_SHOPS, serialize(RCrmActions::clearArr($bitrixShopsArr)));
    COption::SetOptionString($mid, $CRM_IBLOCKS_INVENTORIES, serialize(RCrmActions::clearArr($bitrixIblocksInventories)));

    COption::SetOptionString($mid, $CRM_PRICES_UPLOAD, $pricesUpload);
    COption::SetOptionString($mid, $CRM_PRICES, serialize(RCrmActions::clearArr($bitrixPricesArr)));
    COption::SetOptionString($mid, $CRM_PRICE_SHOPS, serialize(RCrmActions::clearArr($bitrixPriceShopsArr)));
    COption::SetOptionString($mid, $CRM_IBLOCKS_PRICES, serialize(RCrmActions::clearArr($bitrixIblocksPrices)));

    COption::SetOptionString($mid, $CRM_COLLECTOR, $collector);
    COption::SetOptionString($mid, $CRM_COLL_KEY, serialize(RCrmActions::clearArr($collectorKeys)));

    RetailCrmConfigProvider::setOnlineConsultant($onlineConsultant);
    RetailCrmConfigProvider::setOnlineConsultantScript($onlineConsultantScript);

    COption::SetOptionString($mid, $CRM_UA, $ua);
    COption::SetOptionString($mid, $CRM_UA_KEYS, serialize(RCrmActions::clearArr($uaKeys)));
    COption::SetOptionString($mid, $CRM_DIMENSIONS, $orderDimensions);
    RetailcrmConfigProvider::setSendPaymentAmount($sendPaymentAmount);

    COption::SetOptionString($mid, $CRM_DISCOUNT_ROUND, $discount_round);
    COption::SetOptionString($mid, $CRM_PURCHASE_PRICE_NULL, $purchasePrice_null);
    COption::SetOptionString($mid, RetailcrmConstants::CRM_SHIPMENT_DEDUCTED, $shipment_deducted);

    COption::SetOptionString($mid, $CRM_CC, $cc);
    COption::SetOptionString($mid, $CRM_CORP_SHOPS, serialize(RCrmActions::clearArr($bitrixCorpShopsArr)));
    COption::SetOptionString($mid, $CRM_CORP_NAME, serialize(RCrmActions::clearArr($bitrixCorpName)));
    COption::SetOptionString($mid, $CRM_CORP_ADRES, serialize(RCrmActions::clearArr($bitrixCorpAdres)));

    $request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();

    if ($request->isHttps() === true) {
        COption::SetOptionString($mid, $PROTOCOL, 'https://');
    } else {
        COption::SetOptionString($mid, $PROTOCOL, 'http://');
    }

    $uri .= '&ok=Y';
    LocalRedirect($uri);
} else {
    $api_host = COption::GetOptionString($mid, $CRM_API_HOST_OPTION, 0);
    $api_key = COption::GetOptionString($mid, $CRM_API_KEY_OPTION, 0);
    $api = new RetailCrm\ApiClient($api_host, $api_key);

    //prepare crm lists
    try {
        $arResult['orderTypesList'] = $api->orderTypesList()->orderTypes;
        $arResult['deliveryTypesList'] = $api->deliveryTypesList()->deliveryTypes;
        $arResult['deliveryServicesList'] = $api->deliveryServicesList()->deliveryServices;
        $arResult['paymentTypesList'] = $api->paymentTypesList()->paymentTypes;
        $arResult['paymentStatusesList'] = $api->paymentStatusesList()->paymentStatuses; // --statuses
        $arResult['paymentList'] = $api->statusesList()->statuses;
        $arResult['paymentGroupList'] = $api->statusGroupsList()->statusGroups; // -- statuses groups
        $arResult['sitesList'] = $APPLICATION->ConvertCharsetArray($api->sitesList()->sites, 'utf-8', SITE_CHARSET);
        $arResult['inventoriesList'] = $APPLICATION->ConvertCharsetArray($api->storesList()->stores, 'utf-8', SITE_CHARSET);
        $arResult['priceTypeList'] = $APPLICATION->ConvertCharsetArray($api->pricesTypes()->priceTypes, 'utf-8', SITE_CHARSET);
    } catch (\RetailCrm\Exception\CurlException $e) {
        RCrmActions::eventLog(
            'intaro.retailcrm/options.php', 'RetailCrm\ApiClient::*List::CurlException',
            $e->getCode() . ': ' . $e->getMessage()
        );

        echo CAdminMessage::ShowMessage(GetMessage('ERR_' . $e->getCode()));
    } catch (InvalidArgumentException $e) {
        $badKey = true;
        echo CAdminMessage::ShowMessage(GetMessage('ERR_403'));
    } catch (\RetailCrm\Exception\InvalidJsonException $e) {
        $badJson = true;
        echo CAdminMessage::ShowMessage(GetMessage('ERR_JSON'));
    }

    $deliveryTypes = array();
    $deliveryIntegrationCode = array();
    foreach ($arResult['deliveryTypesList'] as $deliveryType) {
        if ($deliveryType['active'] === true) {
            $deliveryTypes[$deliveryType['code']] = $deliveryType;
            $deliveryIntegrationCode[$deliveryType['code']] = $deliveryType['integrationCode'];
        }
    }

    $arResult['deliveryTypesList'] = $deliveryTypes;
    COption::SetOptionString($mid, RetailcrmConstants::CRM_INTEGRATION_DELIVERY, serialize(RCrmActions::clearArr($deliveryIntegrationCode)));

    //bitrix orderTypesList -- personTypes
    $arResult['bitrixOrderTypesList'] = RCrmActions::OrderTypesList($arResult['arSites']);

    //bitrix deliveryTypesList
    $arResult['bitrixDeliveryTypesList'] = RCrmActions::DeliveryList();

    //bitrix paymentTypesList
    $arResult['bitrixPaymentTypesList'] = RCrmActions::PaymentList();

    //bitrix statusesList
    $arResult['bitrixPaymentStatusesList'] = RCrmActions::StatusesList();

    //bitrix pyament Y/N
    $arResult['bitrixPaymentList'][0]['NAME'] = GetMessage('PAYMENT_Y');
    $arResult['bitrixPaymentList'][0]['ID'] = 'Y';
    $arResult['bitrixPaymentList'][1]['NAME'] = GetMessage('PAYMENT_N');
    $arResult['bitrixPaymentList'][1]['ID'] = 'N';

    //bitrix orderPropsList
    $arResult['arProp'] = RCrmActions::OrderPropsList();

    $arResult['bitrixIblocksExportList'] = RCrmActions::IblocksExportList();
    $arResult['bitrixStoresExportList'] = RCrmActions::StoresExportList();
    $arResult['bitrixPricesExportList'] = RCrmActions::PricesExportList();

    //saved cat params
    $optionsOrderTypes = unserialize(COption::GetOptionString($mid, $CRM_ORDER_TYPES_ARR, 0));
    $optionsDelivTypes = unserialize(COption::GetOptionString($mid, $CRM_DELIVERY_TYPES_ARR, 0));
    $optionsPayTypes = unserialize(COption::GetOptionString($mid, $CRM_PAYMENT_TYPES, 0));
    $optionsPayStatuses = unserialize(COption::GetOptionString($mid, $CRM_PAYMENT_STATUSES, 0)); // --statuses
    $optionsPayment = unserialize(COption::GetOptionString($mid, $CRM_PAYMENT, 0));
    $optionsSitesList = unserialize(COption::GetOptionString($mid, $CRM_SITES_LIST, 0));
    $optionsDischarge = COption::GetOptionString($mid, $CRM_ORDER_DISCHARGE, 0);
    $optionsOrderProps = unserialize(COption::GetOptionString($mid, $CRM_ORDER_PROPS, 0));
    $optionsContragentType = unserialize(COption::GetOptionString($mid, $CRM_CONTRAGENT_TYPE, 0));
    $optionsLegalDetails = unserialize(COption::GetOptionString($mid, $CRM_LEGAL_DETAILS, 0));
    $optionsCustomFields = unserialize(COption::GetOptionString($mid, $CRM_CUSTOM_FIELDS, 0));
    $optionsOrderNumbers = COption::GetOptionString($mid, $CRM_ORDER_NUMBERS, 0);
    $canselOrderArr = unserialize(COption::GetOptionString($mid, $CRM_CANSEL_ORDER, 0));

    $optionInventotiesUpload = COption::GetOptionString($mid, $CRM_INVENTORIES_UPLOAD, 0);
    $optionStores = unserialize(COption::GetOptionString($mid, $CRM_STORES, 0));
    $optionShops = unserialize(COption::GetOptionString($mid, $CRM_SHOPS, 0));
    $optionIblocksInventories = unserialize(COption::GetOptionString($mid, $CRM_IBLOCKS_INVENTORIES, 0));
    $optionShopsCorporate = unserialize(COption::GetOptionString($mid, $CRM_SHOPS, 0));

    $optionPricesUpload = COption::GetOptionString($mid, $CRM_PRICES_UPLOAD, 0);
    $optionPrices = unserialize(COption::GetOptionString($mid, $CRM_PRICES, 0));
    $optionPriceShops = unserialize(COption::GetOptionString($mid, $CRM_PRICE_SHOPS, 0));
    $optionIblocksPrices = unserialize(COption::GetOptionString($mid, $CRM_IBLOCKS_PRICES, 0));

    $optionCollector = COption::GetOptionString($mid, $CRM_COLLECTOR, 0);
    $optionCollectorKeys = unserialize(COption::GetOptionString($mid, $CRM_COLL_KEY));
    
    $optionOnlineConsultant = RetailcrmConfigProvider::isOnlineConsultantEnabled();
    $optionOnlineConsultantScript = RetailcrmConfigProvider::getOnlineConsultantScript();

    $optionUa = COption::GetOptionString($mid, $CRM_UA, 0);
    $optionUaKeys = unserialize(COption::GetOptionString($mid, $CRM_UA_KEYS));

    $optionDiscRound = COption::GetOptionString($mid, $CRM_DISCOUNT_ROUND, 0);
    $optionPricePrchaseNull = COption::GetOptionString($mid, $CRM_PURCHASE_PRICE_NULL, 0);
    $optionShipmentDeducted = RetailcrmConfigProvider::getShipmentDeducted();

    //corporate-cliente
    $optionCorpClient = COption::GetOptionString($mid, $CRM_CC, 0);
    $optionCorpShops = unserialize(COption::GetOptionString($mid, $CRM_CORP_SHOPS, 0));
    $optionsCorpComName = unserialize(COption::GetOptionString($mid, $CRM_CORP_NAME, 0));
    $optionsCorpAdres = unserialize(COption::GetOptionString($mid, $CRM_CORP_ADRES, 0));

    $version = COption::GetOptionString($mid, $CRM_API_VERSION, 0);

    //currency
    $baseCurrency = \Bitrix\Currency\CurrencyManager::getBaseCurrency();
    $currencyOption = COption::GetOptionString($mid, $CRM_CURRENCY, 0) ? COption::GetOptionString($mid, $CRM_CURRENCY, 0) : $baseCurrency;
    $currencyList = \Bitrix\Currency\CurrencyManager::getCurrencyList();

    $optionsOrderDimensions = COption::GetOptionString($mid, $CRM_DIMENSIONS, 'N');
    $addressOptions = unserialize(COption::GetOptionString($mid, $CRM_ADDRESS_OPTIONS, 0));

    $aTabs = array(
        array(
            "DIV" => "edit1",
            "TAB" => GetMessage('ICRM_OPTIONS_GENERAL_TAB'),
            "ICON" => "",
            "TITLE" => GetMessage('ICRM_OPTIONS_GENERAL_CAPTION')
        ),
        array(
            "DIV" => "edit2",
            "TAB" => GetMessage('ICRM_OPTIONS_CATALOG_TAB'),
            "ICON" => '',
            "TITLE" => GetMessage('ICRM_OPTIONS_CATALOG_CAPTION')
        ),
        array(
            "DIV" => "edit3",
            "TAB" => GetMessage('ICRM_OPTIONS_ORDER_PROPS_TAB'),
            "ICON" => '',
            "TITLE" => GetMessage('ICRM_OPTIONS_ORDER_PROPS_CAPTION')
        ),
        array(
            "DIV" => "edit4",
            "TAB" => GetMessage('OTHER_OPTIONS'),
            "ICON" => '',
            "TITLE" => GetMessage('ICRM_OPTIONS_ORDER_DISCHARGE_CAPTION')
        ),
        array(
            "DIV" => "edit5",
            "TAB" => GetMessage('UPLOAD_ORDERS_OPTIONS'),
            "ICON" => '',
            "TITLE" => GetMessage('ORDER_UPLOAD'),
        )
    );
    $tabControl = new CAdminTabControl("tabControl", $aTabs);
    $tabControl->Begin();
    ?>
    <?php $APPLICATION->AddHeadString('<script type="text/javascript" src="/bitrix/js/main/jquery/jquery-1.7.min.js"></script>'); ?>
    <script type="text/javascript">
        $(document).ready(function() {
            $('input.addr').change(function(){
                splitName = $(this).attr('name').split('-');
                orderType = splitName[2];

                if(parseInt($(this).val()) === 1)
                    $('tr.address-detail-' + orderType).show('slow');
                else if(parseInt($(this).val()) === 0)
                    $('tr.address-detail-' + orderType).hide('slow');
            });

            $('tr.contragent-type select').change(function(){
                splitName = $(this).attr('name').split('-');
                contragentType = $(this).val();
                orderType = splitName[2];

                $('tr.legal-detail-' + orderType).hide();
                $('.legal-detail-title-' + orderType).hide();

                $('tr.legal-detail-' + orderType).each(function(){
                    if($(this).hasClass(contragentType)){
                        $(this).show();
                        $('.legal-detail-title-' + orderType).show();
                    }
                });
            });

            $('.inventories-batton label').change(function(){
                if($(this).find('input').is(':checked') === true){
                    $('tr.inventories').show('slow');
                } else if($(this).find('input').is(':checked') === false){
                    $('tr.inventories').hide('slow');
                }

                return true;
            });

            $('.prices-batton label').change(function(){
                if($(this).find('input').is(':checked') === true){
                    $('tr.prices').show('slow');
                } else if($(this).find('input').is(':checked') === false){
                    $('tr.prices').hide('slow');
                }

                return true;
            });

            $('.r-ua-button label').change(function(){
                if($(this).find('input').is(':checked') === true){
                    $('tr.r-ua').show('slow');
                } else if($(this).find('input').is(':checked') === false){
                    $('tr.r-ua').hide('slow');
                }

                return true;
            });

            $('.r-dc-button label').change(function() {
                if ($(this).find('input').is(':checked') === true) {
                    $('tr.r-dc').show('slow');
                } else if ($(this).find('input').is(':checked') === false) {
                    $('tr.r-dc').hide('slow');
                }

                return true;
            });

            $('.r-cc-button label').change(function(){
                if($(this).find('input').is(':checked') === true) {
                    $('tr.r-cc').show('slow');
                } else if($(this).find('input').is(':checked') === false){
                    $('tr.r-cc').hide('slow');
                }

                return true;
            });

            $('.r-coll-button label').change(function(){
                if($(this).find('input').is(':checked') === true){
                    $('tr.r-coll').show('slow');
                } else if($(this).find('input').is(':checked') === false){
                    $('tr.r-coll').hide('slow');
                }

                return true;
            });

            $('.r-consultant-button label').change(function(){
                if($(this).find('input').is(':checked') === true){
                    $('tr.r-consultant').show('slow');
                } else if($(this).find('input').is(':checked') === false){
                    $('tr.r-consultant').hide('slow');
                }

                return true;
            });

            $('.r-purchaseprice-button label').change(function() {
                if($(this).find('input').is(':checked') === true) {
                    $('tr.r-purchaseprice').show('slow');
                } else if($(this).find('input').is(':checked') === false) {
                    $('tr.r-purchaseprice').hide('slow');
                }

                return true;
            });

        });

        $('input[name="update-delivery-services"]').live('click', function() {
            BX.showWait();
            var updButton = this;
            // hide next step button
            $(updButton).css('opacity', '0.5').attr('disabled', 'disabled');

            var handlerUrl = $(this).parents('form').attr('action');
            var data = 'ajax=1';

            $.ajax({
                type: 'POST',
                url: handlerUrl,
                data: data,
                dataType: 'json',
                success: function(response) {
                    BX.closeWait();
                    $(updButton).css('opacity', '1').removeAttr('disabled');

                    if(!response.success)
                        alert('<?php echo GetMessage('MESS_1'); ?>');
                },
                error: function () {
                    BX.closeWait();
                    $(updButton).css('opacity', '1').removeAttr('disabled');

                    alert('<?php echo GetMessage('MESS_2'); ?>');
                }
            });

            return false;
        });
    </script>
    <style type="text/css">
        .option-other-bottom {
            border-bottom: 0px !important;
        }
        .option-other-top{
            border-top: 1px solid #f5f9f9 !important;
        }
        .option-other-center{
            border-top: 5px solid #f5f9f9 !important;
            border-bottom: 5px solid #f5f9f9 !important;
        }
        .option-other-heading{
            border-top: 25px solid #f5f9f9 !important;
            border-bottom: 0px solid #f5f9f9 !important;
        }
        .option-other-empty{
            border-bottom: 15px solid #f5f9f9 !important;
        }
        .option-head{
            text-align: center;
            padding: 10px;
            font-size: 14px;
            color: #4b6267;
        }

    </style>
    <form method="POST" action="<?php echo $uri; ?>" id="FORMACTION">
        <?php
        echo bitrix_sessid_post();
        $tabControl->BeginNextTab();
        ?>
        <input type="hidden" name="tab" value="catalog">
        <tr class="heading">
            <td colspan="2"><b><?php echo GetMessage('ICRM_CONN_SETTINGS'); ?></b></td>
        </tr>
        <tr>
            <td width="50%" class="adm-detail-content-cell-l"><?php echo GetMessage('ICRM_API_HOST'); ?></td>
            <td width="50%" class="adm-detail-content-cell-r"><input type="text" id="api_host" name="api_host" value="<?php echo $api_host; ?>"></td>
        </tr>
        <tr>
            <td width="50%" class="adm-detail-content-cell-l"><?php echo GetMessage('ICRM_API_KEY'); ?></td>
            <td width="50%" class="adm-detail-content-cell-r"><input type="text" id="api_key" name="api_key" value="<?php echo $api_key; ?>"></td>
        </tr>
        <?php if(count($arResult['arSites'])>1):?>
            <tr class="heading">
                <td colspan="2" style="background-color: transparent;">
                    <b>
                        <?php echo GetMessage('ICRM_SITES'); ?>
                    </b>
                </td>
            </tr>
            <?php foreach ($arResult['arSites'] as $site): ?>
                <tr>
                    <td width="50%" class="adm-detail-content-cell-l"><?php echo $site['NAME'] . ' (' . $site['LID'] . ')'; ?></td>
                    <td width="50%" class="adm-detail-content-cell-r">
                        <select class="typeselect" name="sites-id-<?php echo $site['LID']?>">
                            <option value=""></option>
                            <?php foreach ($arResult['sitesList'] as $sitesList): ?>
                                <option value="<?php echo $sitesList['code'] ?>" <?php if($sitesList['code'] == $optionsSitesList[$site['LID']]) echo 'selected="selected"'; ?>><?php echo $sitesList['name']?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif;?>
        <?php if(!$badKey && !$badJson):?>
            <?php $tabControl->BeginNextTab(); ?>
            <input type="hidden" name="tab" value="catalog">
            <tr class="option-head">
                <td colspan="2"><b><?php echo GetMessage('INFO_1'); ?></b></td>
            </tr>
            <tr class="heading">
                <td colspan="2"><b><?php echo GetMessage('DELIVERY_TYPES_LIST'); ?></b></td>
            </tr>
            <?php foreach($arResult['bitrixDeliveryTypesList'] as $bitrixDeliveryType): ?>
                <tr>
                    <td width="50%" class="adm-detail-content-cell-l" name="<?php echo $bitrixDeliveryType['ID']; ?>">
                        <?php echo $bitrixDeliveryType['NAME']; ?>
                    </td>
                    <td width="50%" class="adm-detail-content-cell-r">
                        <select name="delivery-type-<?php echo $bitrixDeliveryType['ID']; ?>" class="typeselect">
                            <option value=""></option>
                            <?php foreach($arResult['deliveryTypesList'] as $deliveryType): ?>
                                <?php if($deliveryType['active'] == true){?>
                                    <option value="<?php echo $deliveryType['code']; ?>" <?php if ($optionsDelivTypes[$bitrixDeliveryType['ID']] == $deliveryType['code']) echo 'selected'; ?>>
                                        <?php echo $APPLICATION->ConvertCharset($deliveryType['name'], 'utf-8', SITE_CHARSET); ?>
                                    </option>
                                <?php }?>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            <?php endforeach; ?>
            <tr class="heading">
                <td colspan="2">
                    <input type="submit" name="update-delivery-services" value="<?php echo GetMessage('UPDATE_DELIVERY_SERVICES'); ?>" class="adm-btn-save">
                </td>
            </tr>
            <tr class="heading">
                <td colspan="2"><b><?php echo GetMessage('PAYMENT_TYPES_LIST'); ?></b>
                    <p><small>Для интеграционных оплат, статус не передается</small></p></td>
            </tr>
            <?php foreach($arResult['bitrixPaymentTypesList'] as $bitrixPaymentType): ?>
                <tr>
                    <td width="50%" class="adm-detail-content-cell-l" name="<?php echo $bitrixPaymentType['ID']; ?>">
                        <?php echo $bitrixPaymentType['NAME']; ?>
                    </td>
                    <td width="50%" class="adm-detail-content-cell-r">
                        <select name="payment-type-<?php echo $bitrixPaymentType['ID']; ?>" class="typeselect">
                            <option value="" selected=""></option>
                            <?php foreach($arResult['paymentTypesList'] as $paymentType): ?>
                                <option value="<?php echo $paymentType['code']; ?>" <?php if ($optionsPayTypes[$bitrixPaymentType['ID']] == $paymentType['code']) echo 'selected'; ?>>
                                    <?php
                                    $nameType = isset($paymentType['integrationModule']) ? $APPLICATION->ConvertCharset($paymentType['name'] . '(интеграционная)', 'utf-8', SITE_CHARSET) : $APPLICATION->ConvertCharset($paymentType['name'], 'utf-8', SITE_CHARSET);
                                    echo $nameType;?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            <?php endforeach; ?>
            <tr class="heading">
                <td colspan="2"><b><?php echo GetMessage('PAYMENT_STATUS_LIST'); ?></b></td>
            </tr>
            <? if (empty($arResult['bitrixPaymentStatusesList'])) :?>
                <td colspan="2" class="option-head option-other-top option-other-bottom">
                    <b><label><?echo GetMessage('STATUS_NOT_SETTINGS');?></label></b>
                </td>
            <?else:?>
                <tr>
                    <td width="50%"></td>
                    <td width="50%">
                        <table width="100%">
                            <tr>
                                <td width="50%"></td>
                                <td width="50%" style="text-align: center;">
                                    <?php echo GetMessage('CANCELED'); ?>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            <?endif;?>

            <?php foreach($arResult['bitrixPaymentStatusesList'] as $bitrixPaymentStatus): ?>
                <tr>
                    <td width="50%" class="adm-detail-content-cell-l" name="<?php echo $bitrixPaymentStatus['ID']; ?>">
                        <?php echo $bitrixPaymentStatus['NAME']; ?>
                    </td>
                    <td width="50%" class="adm-detail-content-cell-r">
                        <table width="100%">
                            <tr>
                                <td width="70%">
                                    <select name="payment-status-<?php echo $bitrixPaymentStatus['ID']; ?>" class="typeselect">
                                        <option value=""></option>
                                        <?php foreach($arResult['paymentGroupList'] as $orderStatusGroup): if(!empty($orderStatusGroup['statuses'])) : ?>
                                            <optgroup label="<?php echo $APPLICATION->ConvertCharset($orderStatusGroup['name'], 'utf-8', SITE_CHARSET); ?>">
                                                <?php foreach($orderStatusGroup['statuses'] as $payment): ?>
                                                    <?php if(isset($arResult['paymentList'][$payment])): ?>
                                                        <option value="<?php echo $arResult['paymentList'][$payment]['code']; ?>" <?php if ($optionsPayStatuses[$bitrixPaymentStatus['ID']] == $arResult['paymentList'][$payment]['code']) echo 'selected'; ?>>
                                                            <?php echo $APPLICATION->ConvertCharset($arResult['paymentList'][$payment]['name'], 'utf-8', SITE_CHARSET); ?>
                                                        </option>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </optgroup>
                                        <?php endif; endforeach; ?>
                                    </select>
                                </td>
                                <td width="30%">
                                    <input name="order-cansel-<?php echo $bitrixPaymentStatus['ID']; ?>" <?php if(in_array($bitrixPaymentStatus['ID'], $canselOrderArr)) echo "checked";?> value="Y" type="checkbox" />
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            <?php endforeach; ?>
            <tr class="heading">
                <td colspan="2"><b><?php echo GetMessage('PAYMENT_LIST'); ?></b></td>
            </tr>
            <?php foreach($arResult['bitrixPaymentList'] as $bitrixPayment): ?>
                <tr>
                    <td width="50%" class="adm-detail-content-cell-l" name="<?php echo $bitrixPayment['ID']; ?>">
                        <?php echo $bitrixPayment['NAME']; ?>
                    </td>
                    <td width="50%" class="adm-detail-content-cell-r">
                        <select name="payment-<?php echo $bitrixPayment['ID']; ?>" class="typeselect">
                            <option value=""></option>
                            <?php foreach($arResult['paymentStatusesList'] as $paymentStatus): ?>
                                <option value="<?php echo $paymentStatus['code']; ?>" <?php if ($optionsPayment[$bitrixPayment['ID']] == $paymentStatus['code']) echo 'selected'; ?>>
                                    <?php echo $APPLICATION->ConvertCharset($paymentStatus['name'], 'utf-8', SITE_CHARSET); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            <?php endforeach; ?>
            <tr class="heading">
                <td colspan="2"><b><?php echo GetMessage('ORDER_TYPES_LIST'); ?></b></td>
            </tr>
            <?php if($isCustomOrderType): ?>
                <tr>
                    <td colspan="2" style="text-align: center!important; padding-bottom:10px;"><b style="color:#c24141;"><?php echo GetMessage('ORDER_TYPES_LIST_CUSTOM'); ?></b></td>
                </tr>
            <?php endif; ?>
            <?php foreach($arResult['bitrixOrderTypesList'] as $bitrixOrderType): ?>
                <tr>
                    <td width="50%" class="adm-detail-content-cell-l" name="<?php echo $bitrixOrderType['ID']; ?>">
                        <?php echo $bitrixOrderType['NAME']; ?>
                    </td>
                    <td width="50%" class="adm-detail-content-cell-r">
                        <select name="order-type-<?php echo $bitrixOrderType['ID']; ?>" class="typeselect">
                            <option value=""></option>
                            <?php foreach($arResult['orderTypesList'] as $orderType): ?>
                                <option value="<?php echo $orderType['code']; ?>" <?php if ($optionsOrderTypes[$bitrixOrderType['ID']] == $orderType['code']) echo 'selected'; ?>>
                                    <?php echo $APPLICATION->ConvertCharset($orderType['name'], 'utf-8', SITE_CHARSET); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php $tabControl->BeginNextTab(); ?>
            <input type="hidden" name="tab" value="catalog">
            <tr class="option-head">
                <td colspan="2"><b><?php echo GetMessage('INFO_2'); ?></b></td>
            </tr>
            <?php foreach($arResult['bitrixOrderTypesList'] as $bitrixOrderType): ?>
                <tr class="heading">
                    <td colspan="2"><b><?php echo GetMessage('ORDER_TYPE_INFO') . ' ' . $bitrixOrderType['NAME']; ?></b></td>
                </tr>
                <tr class="contragent-type">
                    <td width="50%" class="adm-detail-content-cell-l">
                        <?php echo GetMessage('CONTRAGENTS_TYPES_LIST'); ?>
                    </td>
                    <td width="50%" class="adm-detail-content-cell-r">
                        <select name="contragent-type-<?php echo $bitrixOrderType['ID']; ?>" class="typeselect">
                            <?php foreach ($arResult['contragentType'] as $contragentType): ?>
                                <option value="<?php echo $contragentType["ID"]; ?>" <?php if ($optionsContragentType[$bitrixOrderType['ID']] == $contragentType['ID']) echo 'selected'; ?>>
                                    <?php echo $contragentType["NAME"]; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <?php $countProps = 1; foreach($arResult['orderProps'] as $orderProp): ?>
                    <?php if($orderProp['ID'] == 'text'): ?>
                        <tr class="heading">
                            <td colspan="2" style="background-color: transparent;">
                                <b>
                                    <label><input class="addr" type="radio" name="address-detail-<?php echo $bitrixOrderType['ID']; ?>" value="0" <?php if($addressOptions[$bitrixOrderType['ID']] == 0) echo "checked"; ?>><?php echo GetMessage('ADDRESS_SHORT'); ?></label>
                                    <label><input class="addr" type="radio" name="address-detail-<?php echo $bitrixOrderType['ID']; ?>" value="1" <?php if($addressOptions[$bitrixOrderType['ID']] == 1) echo "checked"; ?>><?php echo GetMessage('ADDRESS_FULL'); ?></label>
                                </b>
                            </td>
                        </tr>
                    <?php endif; ?>
                    <tr <?php if ($countProps > 4) echo 'class="address-detail-' . $bitrixOrderType['ID'] . '"'; if(($countProps > 4) && ($addressOptions[$bitrixOrderType['ID']] == 0)) echo 'style="display:none;"';?>>
                        <td width="50%" class="adm-detail-content-cell-l" name="<?php echo $orderProp['ID']; ?>">
                            <?php echo $orderProp['NAME']; ?>
                        </td>
                        <td width="50%" class="adm-detail-content-cell-r">
                            <select name="order-prop-<?php echo $orderProp['ID'] . '-' . $bitrixOrderType['ID']; ?>" class="typeselect">
                                <option value=""></option>
                                <?php foreach ($arResult['arProp'][$bitrixOrderType['ID']] as $arProp): ?>
                                    <option value="<?php echo $arProp['CODE']; ?>" <?php if ($optionsOrderProps[$bitrixOrderType['ID']][$orderProp['ID']] == $arProp['CODE']) echo 'selected'; ?>>
                                        <?php echo $arProp['NAME']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <?php $countProps++; endforeach; ?>
                <?if (isset($arResult['customFields']) && count($arResult['customFields']) > 0):?>
                    <tr class="heading custom-detail-title">
                        <td colspan="2" style="background-color: transparent;">
                            <b>
                                <?=GetMessage("ORDER_CUSTOM"); ?>
                            </b>
                        </td>
                    </tr>
                    <?foreach($arResult['customFields'] as $customFields):?>
                        <tr class="custom-detail-<?=$customFields['ID'];?>">
                            <td width="50%" class="" name="">
                                <?=$customFields['NAME']; ?>
                            </td>
                            <td width="50%" class="">
                                <select name="custom-fields-<?=$customFields['ID'] . '-' . $bitrixOrderType['ID']; ?>" class="typeselect">
                                    <option value=""></option>
                                    <?foreach ($arResult['arProp'][$bitrixOrderType['ID']] as $arProp):?>
                                        <option value="<?=$arProp['CODE']?>" <?php if ($optionsCustomFields[$bitrixOrderType['ID']][$customFields['ID']] == $arProp['CODE']) echo 'selected'; ?>>
                                            <?=$arProp['NAME']; ?>
                                        </option>
                                    <?endforeach;?>
                                </select>
                            </td>
                        </tr>
                    <?endforeach;?>
                <?endif;?>
                <tr class="heading legal-detail-title-<?php echo $bitrixOrderType['ID'];?>" <?php if(count($optionsLegalDetails[$bitrixOrderType['ID']])<1) echo 'style="display:none"'; ?>>
                    <td colspan="2" style="background-color: transparent;">
                        <b>
                            <?php echo GetMessage('LEGAL_DETAIL'); ?>
                        </b>
                    </td>
                </tr>
                <?php foreach($arResult['legalDetails'] as $legalDetails): ?>
                    <tr class="legal-detail-<?php echo $bitrixOrderType['ID'];?> <?php foreach($legalDetails['GROUP'] as $gr) echo $gr . ' ';?>" <?php if(!in_array($optionsContragentType[$bitrixOrderType['ID']], $legalDetails['GROUP'])) echo 'style="display:none"'; ?>>
                        <td width="50%" class="" name="<?php ?>">
                            <?php echo $legalDetails['NAME']; ?>
                        </td>
                        <td width="50%" class="">
                            <select name="legal-detail-<?php echo $legalDetails['ID'] . '-' . $bitrixOrderType['ID']; ?>" class="typeselect">
                                <option value=""></option>
                                <?php foreach ($arResult['arProp'][$bitrixOrderType['ID']] as $arProp): ?>
                                    <option value="<?php echo $arProp['CODE']; ?>" <?php if ($optionsLegalDetails[$bitrixOrderType['ID']][$legalDetails['ID']] == $arProp['CODE']) echo 'selected'; ?>>
                                        <?php echo $arProp['NAME']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endforeach; ?>

            <?php $tabControl->BeginNextTab(); ?>
            <input type="hidden" name="tab" value="catalog">
            <tr class="heading">
                <td colspan="2" class="option-other-bottom"><b><?php echo GetMessage('ORDERS_OPTIONS'); ?></b></td>
            </tr>
            <tr>
                <td colspan="2" class="option-head option-other-top option-other-bottom">
                    <b>
                        <label><input class="addr" type="checkbox" name="order-numbers" value="Y" <?php if($optionsOrderNumbers == 'Y') echo "checked"; ?>> <?php echo GetMessage('ORDER_NUMBERS'); ?></label>
                    </b>
                </td>
            </tr>
            <tr>
                <td colspan="2" class="option-head option-other-top option-other-bottom">
                    <b>
                        <label>
                            <input class="addr" type="checkbox" name="order_dimensions" value="Y" <?php if($optionsOrderDimensions == 'Y') echo "checked"; ?>> <?php echo GetMessage('ORDER_DIMENSIONS'); ?>
                        </label>
                    </b>
                </td>
            </tr>
            <tr>
                <td colspan="2" class="option-head option-other-top option-other-bottom">
                    <b>
                        <label>
                            <input class="addr" type="checkbox" name="<?php echo RetailcrmConstants::SEND_PAYMENT_AMOUNT; ?>" value="Y" <?php if(RetailcrmConfigProvider::shouldSendPaymentAmount()) echo "checked"; ?>> <?php echo GetMessage('SEND_PAYMENT_AMOUNT'); ?>
                        </label>
                    </b>
                </td>
            </tr>
            <tr>
                <td colspan="2" class="option-head option-other-top option-other-bottom">
                    <b>
                        <label><input class="addr" type="radio" name="order-discharge" value="1" <?php if($optionsDischarge == 1) echo "checked"; ?>><?php echo GetMessage('DISCHARGE_EVENTS'); ?></label>
                        <label><input class="addr" type="radio" name="order-discharge" value="0" <?php if($optionsDischarge == 0) echo "checked"; ?>><?php echo GetMessage('DISCHARGE_AGENT'); ?></label>
                    </b>
                </td>
            </tr>
            <tr class="heading">
                <td colspan="2" class="option-other-heading"><b><?php echo GetMessage('CRM_API_VERSION'); ?></b></td>
            </tr>
            <tr>
                <td colspan="2" class="option-head option-other-top option-other-bottom">
                    <select name="api_version" class="typeselect">
                        <?php for($v = 4; $v <= 5; $v++) {
                            $ver = 'v' . $v; ?>
                            <option value="<?php echo $ver; ?>" <?php if ($ver == $version) echo 'selected'; ?>>
                                API V<?php echo $v; ?>
                            </option>
                        <?php } ?>
                    </select>
                </td>
            </tr>
            <tr class="heading">
                <td colspan="2" class="option-other-heading"><b><?php echo GetMessage('CURRENCY'); ?></b></td>
            </tr>
            <tr>
                <td colspan="2" class="option-head option-other-top option-other-bottom">
                    <select name="currency" class="typeselect">
                        <?php foreach ($currencyList as $currencyCode => $currencyName) : ?>
                            <option value="<?php echo $currencyCode; ?>" <?php if ($currencyCode == $currencyOption) echo 'selected'; ?>>
                                <?php echo $currencyName; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <?php if ($optionInventotiesUpload === 'Y' || count($arResult['bitrixStoresExportList']) > 0) :?>
                <tr class="heading inventories-batton">
                    <td colspan="2" class="option-other-heading">
                        <b>
                            <label><input class="addr" type="checkbox" name="inventories-upload" value="Y" <?php if($optionInventotiesUpload === 'Y') echo "checked"; ?>><?php echo GetMessage('INVENTORIES_UPLOAD'); ?></label>
                        </b>
                    </td>
                </tr>
                <tr class="inventories" <?php if($optionInventotiesUpload !== 'Y') echo 'style="display: none;"'; ?>>
                    <td colspan="2" class="option-head option-other-top option-other-bottom">
                        <b><label><?php echo GetMessage('INVENTORIES'); ?></label></b>
                    </td>
                </tr>
                <?php foreach ($arResult['bitrixStoresExportList'] as $catalogExportStore): ?>
                    <tr class="inventories" <?php if($optionInventotiesUpload !== 'Y') echo 'style="display: none;"'; ?>>
                        <td width="50%" class="adm-detail-content-cell-l"><?php echo $catalogExportStore['TITLE'] ?></td>
                        <td width="50%" class="adm-detail-content-cell-r">
                            <select class="typeselect" name="stores-export-<?php echo $catalogExportStore['ID']?>">
                                <option value=""></option>
                                <?php foreach ($arResult['inventoriesList'] as $inventoriesList): ?>
                                    <option value="<?php echo $inventoriesList['code'] ?>" <?php if($optionStores[$catalogExportStore['ID']] == $inventoriesList['code']) echo 'selected="selected"'; ?>><?php echo $inventoriesList['name']?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr class="inventories" <?php if($optionInventotiesUpload !== 'Y') echo 'style="display: none;"'; ?>>
                    <td colspan="2" class="option-head option-other-top option-other-bottom">
                        <b>
                            <label><?php echo GetMessage('SHOPS_INVENTORIES_UPLOAD'); ?></label>
                        </b>
                    </td>
                </tr>
                <?php foreach ($arResult['sitesList'] as $sitesList): ?>
                    <tr class="inventories" align="center" <?php if($optionInventotiesUpload !== 'Y') echo 'style="display: none;"'; ?>>
                        <td colspan="2" class="option-other-center">
                            <label><input class="addr" type="checkbox" name="shops-exoprt-<?echo $sitesList['code'];?>" value="Y" <?php if(in_array($sitesList['code'], $optionShops)) echo "checked"; ?>> <?php echo $sitesList['name'].' ('.$sitesList['code'].')'; ?></label>
                        </td>
                    </tr>
                <?php endforeach;?>
                <tr class="inventories" <?php if($optionInventotiesUpload !== 'Y') echo 'style="display: none;"'; ?>>
                    <td colspan="2" class="option-head option-other-top option-other-bottom">
                        <b>
                            <label><?php echo GetMessage('IBLOCKS_UPLOAD'); ?></label>
                        </b>
                    </td>
                </tr>
                <?php foreach ($arResult['bitrixIblocksExportList'] as $catalogExportIblock) :?>
                    <tr class="inventories" align="center" <?php if($optionInventotiesUpload !== 'Y') echo 'style="display: none;"'; ?>>
                        <td colspan="2" class="option-other-center">
                            <label><input class="addr" type="checkbox" name="iblocks-stores-<?echo $catalogExportIblock['ID'];?>" value="Y" <?php if(in_array($catalogExportIblock['ID'], $optionIblocksInventories)) echo "checked"; ?>> <?php echo '['. $catalogExportIblock['CODE']. '] ' . $catalogExportIblock['NAME'] . ' (' . $catalogExportIblock['LID'] . ')'; ?></label>
                        </td>
                    </tr>
                <?php endforeach;?>
            <?php endif;?>
            <?php if ($optionPricesUpload === 'Y' || count($arResult['bitrixPricesExportList']) > 0) :?>
                <tr class="heading prices-batton">
                    <td colspan="2" class="option-other-heading">
                        <b>
                            <label><input class="addr" type="checkbox" name="prices-upload" value="Y" <?php if($optionPricesUpload === 'Y') echo "checked"; ?>><?php echo GetMessage('PRICES_UPLOAD'); ?></label>
                        </b>
                    </td>
                </tr>
                <tr class="prices" <?php if($optionPricesUpload !== 'Y') echo 'style="display: none;"'; ?>>
                    <td colspan="2" class="option-head option-other-top option-other-bottom">
                        <b>
                            <label><?php echo GetMessage('PRICE_TYPES'); ?></label>
                        </b>
                    </td>
                </tr>
                <?php foreach ($arResult['bitrixPricesExportList'] as $catalogExportPrice) :?>
                    <tr class="prices" <?php if($optionPricesUpload !== 'Y') echo 'style="display: none;"'; ?>>
                        <td width="50%" class="adm-detail-content-cell-l"><?php echo $catalogExportPrice['NAME_LANG'] . ' (' . $catalogExportPrice['NAME'] . ')'; ?></td>
                        <td width="50%" class="adm-detail-content-cell-r">
                            <select class="typeselect" name="price-type-export-<?php echo $catalogExportPrice['ID'];?>">
                                <option value=""></option>
                                <?php foreach ($arResult['priceTypeList'] as $priceTypeList): ?>
                                    <option value="<?php echo $priceTypeList['code'] ?>" <?php if($optionPrices[$catalogExportPrice['ID']] == $priceTypeList['code']) echo 'selected="selected"'; ?>><?php echo $priceTypeList['name']?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                <?php endforeach;?>
                <tr class="prices" <?php if($optionPricesUpload !== 'Y') echo 'style="display: none;"'; ?>>
                    <td colspan="2" class="option-head option-other-top option-other-bottom">
                        <b>
                            <label><?php echo GetMessage('SHOPS_PRICES_UPLOAD'); ?></label>
                        </b>
                    </td>
                </tr>
                <?php foreach ($arResult['sitesList'] as $sitesList): ?>
                    <tr class="prices" align="center" <?php if($optionPricesUpload !== 'Y') echo 'style="display: none;"'; ?>>
                        <td colspan="2" class="option-other-center">
                            <label><input class="addr" type="checkbox" name="shops-price-<?echo $sitesList['code'];?>" value="Y" <?php if(in_array($sitesList['code'], $optionPriceShops)) echo "checked"; ?>> <?php echo $sitesList['name'].' ('.$sitesList['code'].')'; ?></label>
                        </td>
                    </tr>
                <?php endforeach;?>
                <tr class="prices" <?php if($optionPricesUpload !== 'Y') echo 'style="display: none;"'; ?>>
                    <td colspan="2" class="option-head option-other-top option-other-bottom">
                        <b>
                            <label><?php echo GetMessage('IBLOCKS_UPLOAD'); ?></label>
                        </b>
                    </td>
                </tr>
                <?php foreach ($arResult['bitrixIblocksExportList'] as $catalogExportIblock) :?>
                    <tr class="prices" align="center" <?php if($optionPricesUpload !== 'Y') echo 'style="display: none;"'; ?>>
                        <td colspan="2" class="option-other-center">
                            <label><input class="addr" type="checkbox" name="iblocks-prices-<?echo $catalogExportIblock['ID'];?>" value="Y" <?php if(in_array($catalogExportIblock['ID'], $optionIblocksPrices)) echo "checked"; ?>> <?php echo '['. $catalogExportIblock['CODE']. '] ' . $catalogExportIblock['NAME'] . ' (' . $catalogExportIblock['LID'] . ')'; ?></label>
                        </td>
                    </tr>
                <?php endforeach;?>
            <?php endif;?>

            <tr class="heading r-coll-button">
                <td colspan="2" class="option-other-heading">
                    <b>
                        <label><input class="addr" type="checkbox" name="collector" value="Y" <?php if($optionCollector === 'Y') echo "checked"; ?>><?php echo GetMessage('DEMON_COLLECTOR'); ?></label>
                    </b>
                </td>
            </tr>
            <tr class="r-coll" <?php if($optionCollector !== 'Y') echo 'style="display: none;"'; ?>>
                <td class="option-head" colspan="2">
                    <b><?php echo GetMessage('ICRM_SITES'); ?></b>
                </td>
            </tr>
            <?php foreach ($arResult['arSites'] as $sitesList): ?>
                <tr class="r-coll" <?php if($optionCollector !== 'Y') echo 'style="display: none;"'; ?>>
                    <td class="adm-detail-content-cell-l" width="50%"><?php echo GetMessage('DEMON_KEY'); ?> <?php echo $sitesList['NAME']; ?> (<?php echo $sitesList['LID']; ?>)</td>
                    <td class="adm-detail-content-cell-r" width="50%">
                        <input name="collector-id-<?echo $sitesList['LID'];?>" value="<?php echo $optionCollectorKeys[$sitesList['LID']]; ?>" type="text">
                    </td>
                </tr>
            <?php endforeach;?>
            <tr class="heading r-ua-button">
                <td colspan="2" class="option-other-heading">
                    <b>
                        <label><input class="addr" type="checkbox" name="ua-integration" value="Y" <?php if($optionUa === 'Y') echo "checked"; ?>><?php echo GetMessage('UNIVERSAL_ANALYTICS'); ?></label>
                    </b>
                </td>
            </tr>
            <?php foreach ($arResult['arSites'] as $sitesList): ?>
                <tr class="r-ua" <?php if($optionUa !== 'Y') echo 'style="display: none;"'; ?>>
                    <td class="option-head" colspan="2">
                        <b><?php echo $sitesList['NAME']; ?> (<?php echo $sitesList['LID']; ?>)</b>
                    </td>
                </tr>
                <tr class="r-ua" <?php if($optionUa !== 'Y') echo 'style="display: none;"'; ?>>
                    <td class="adm-detail-content-cell-l" width="50%"><?php echo GetMessage('ID_UA'); ?></td>
                    <td class="adm-detail-content-cell-r" width="50%">
                        <input name="ua-id-<?echo $sitesList['LID'];?>" value="<?php echo $optionUaKeys[$sitesList['LID']]['ID']; ?>" type="text">
                    </td>
                </tr>
                <tr class="r-ua" <?php if($optionUa !== 'Y') echo 'style="display: none;"'; ?>>
                    <td class="adm-detail-content-cell-l" width="50%"><?php echo GetMessage('INDEX_UA'); ?></td>
                    <td class="adm-detail-content-cell-r" width="50%">
                        <input name="ua-index-<?echo $sitesList['LID'];?>" value="<?php echo $optionUaKeys[$sitesList['LID']]['INDEX']; ?>" type="text">
                    </td>
                </tr>
            <?php endforeach;?>
            
            <tr class="heading r-consultant-button">
                <td colspan="2" class="option-other-heading">
                    <b>
                        <label><input class="addr" type="checkbox" name="online_consultant" value="Y" <?php if ($optionOnlineConsultant) echo "checked"; ?>><?php echo GetMessage('ONLINE_CONSULTANT'); ?></label>
                    </b>
                </td>
            </tr>

            <tr class="r-consultant" <?php if (!$optionOnlineConsultant) echo 'style="display: none;"'; ?>> 
                <td class="adm-detail-content-cell-l" width="45%"><?php echo GetMessage('ONLINE_CONSULTANT_LABEL')?></td>
                <td class="adm-detail-content-cell-r" width="55%">
                    <textarea name="online_consultant_script"><?php echo $optionOnlineConsultantScript; ?></textarea>
                </td>
            </tr>

            <tr class="heading r-dc-button">
                <td colspan="2" class="option-other-heading">
                    <b>
                        <label><input class="addr" type="checkbox" name="discount_round" value="Y" <?php if($optionDiscRound === 'Y') echo "checked"; ?>><?php echo GetMessage('ROUND_PRICE_FOR_SAME_POSITIONS'); ?></label>
                    </b>
                </td>
            </tr>

            <tr class="r-dc" <?php if($optionDiscRound !== 'Y') echo 'style="display: none;"'; ?>>
                <td class="option-head" colspan="2">
                    <b><?php echo GetMessage('ROUND_LABEL'); ?></b>
                </td>
            </tr>

            <tr class="heading r-cc-button">
                <td colspan="2" class="option-other-heading">
                    <b>
                        <label><input class="addr" type="checkbox" name="corp-client" value="Y" <?php if($optionCorpClient === 'Y') echo "checked"; ?>><?php echo GetMessage('CORP_CLIENTE'); ?></label>
                    </b>
                </td>
            </tr>

            <tr class="r-cc" <?php if($optionCorpClient !== 'Y') echo 'style="display: none;"'; ?>>
                <td width="50%" class="" name="<?php ?>">
                    <?php echo GetMessage('CORP_NAME');?>
                </td>
                <td width="50%" class="">
                    <select name="nickName-corporate" class="typeselect">
                        <option value=""></option>
                        <?php foreach ($arResult['arProp'][$bitrixOrderType['ID']] as $arProp): ?>
                            <option value="<?php echo $arProp['CODE']; ?>" <?php if ($optionsCorpComName == $arProp['CODE']) echo 'selected'; ?>>
                                <?php echo $arProp['NAME']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>

            <tr class="r-cc" <?php if($optionCorpClient !== 'Y') echo 'style="display: none;"'; ?>>
                <td width="50%" class="" name="<?php ?>">
                    <?php echo GetMessage('CORP_ADRESS');?>
                </td>
                <td width="50%" class="">
                    <select name="adres-corporate" class="typeselect">
                        <option value=""></option>
                        <?php foreach ($arResult['arProp'][$bitrixOrderType['ID']] as $arProp): ?>
                            <option value="<?php echo $arProp['CODE']; ?>" <?php if ($optionsCorpAdres == $arProp['CODE']) echo 'selected'; ?>>
                                <?php echo $arProp['NAME']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr class="r-cc" <?php if($optionCorpClient !== 'Y') echo 'style="display: none;"'; ?>>
                <td colspan="2" class="option-head option-other-top option-other-bottom">
                    <b>
                        <label><?php echo GetMessage('CORP_LABEL');?></label>
                    </b>
                </td>
            </tr>

            <tr class="r-cc" <?php if($optionCorpClient !== 'Y') echo 'style="display: none;"'; ?>>
                <td width="50%" class="" name="<?php ?>" align="center">
                    <?php foreach ($arResult['sitesList'] as $sitesList): ?>
                <td colspan="2" class="option-other-center">
                    <label><input class="addr" type="checkbox" name="shops-corporate-<?echo $sitesList['code'];?>" value="Y" <?php if(in_array($sitesList['code'], $optionCorpShops)) echo "checked"; ?>> <?php echo $sitesList['name'].' ('.$sitesList['code'].')'; ?></label>
                </td>
                <?php endforeach;?>
                </td>
            </tr>

            <tr class="heading">
                <td colspan="2" class="option-other-heading">
                    <b>
                        <label><input class="addr" type="checkbox" name="shipment_deducted" value="Y" <?php if($optionShipmentDeducted === 'Y') echo "checked"; ?>><?php echo GetMessage('CHANGE_SHIPMENT_STATUS_FROM_CRM'); ?></label>
                    </b>
                </td>
            </tr>
        <?php endif;?>

        <?php //manual order upload?>
        <?php $tabControl->BeginNextTab(); ?>

            <style type="text/css">
                .instal-load-label {
                    color: #000;
                    margin-bottom: 15px;
                }

                .instal-progress-bar-outer {
                    height: 32px;
                    border:1px solid;
                    border-color:#9ba6a8 #b1bbbe #bbc5c9 #b1bbbe;
                    -webkit-box-shadow: 1px 1px 0 #fff, inset 0 2px 2px #c0cbce;
                    box-shadow: 1px 1px 0 #fff, inset 0 2px 2px #c0cbce;
                    background-color:#cdd8da;
                    background-image:-webkit-linear-gradient(top, #cdd8da, #c3ced1);
                    background-image:-moz-linear-gradient(top, #cdd8da, #c3ced1);
                    background-image:-ms-linear-gradient(top, #cdd8da, #c3ced1);
                    background-image:-o-linear-gradient(top, #cdd8da, #c3ced1);
                    background-image:linear-gradient(top, #ced9db, #c3ced1);
                    border-radius: 2px;
                    text-align: center;
                    color: #6a808e;
                    text-shadow: 0 1px rgba(255,255,255,0.85);
                    font-size: 18px;
                    line-height: 35px;
                    font-weight: bold;
                }

                .instal-progress-bar-alignment {
                    height: 28px;
                    margin: 0;
                    position: relative;
                }

                .instal-progress-bar-inner {
                    height: 28px;
                    border-radius: 2px;
                    border-top: solid 1px #52b9df;
                    background-color:#2396ce;
                    background-image:-webkit-linear-gradient(top, #27a8d7, #2396ce, #1c79c0);
                    background-image:-moz-linear-gradient(top, #27a8d7, #2396ce, #1c79c0);
                    background-image:-ms-linear-gradient(top, #27a8d7, #2396ce, #1c79c0);
                    background-image:-o-linear-gradient(top, #27a8d7, #2396ce, #1c79c0);
                    background-image:linear-gradient(top, #27a8d7, #2396ce, #1c79c0);
                    position: absolute;
                    overflow: hidden;
                    top: 1px;
                    left:0;
                }

                .instal-progress-bar-inner-text {
                    color: #fff;
                    text-shadow: 0 1px rgba(0,0,0,0.2);
                    font-size: 18px;
                    line-height: 32px;
                    font-weight: bold;
                    text-align: center;
                    position: absolute;
                    left: -2px;
                    top: -2px;
                }

                .order-upload-button{
                    padding: 1px 13px 2px;
                    height:28px;
                }

                .order-upload-button div{
                    float:right;
                    position:relative;
                    visible: none;
                }
            </style>

            <script type="text/javascript">
                $(document).ready(function() {
                    $('#percent').width($('.instal-progress-bar-outer').width());

                    $(window).resize(function(){ // strechin progress bar
                        $('#percent').width($('.instal-progress-bar-outer').width());
                    });

                    // orderUpload function
                    function orderUpload() {
                        var handlerUrl = $('#upload-orders').attr('action');
                        var step       = encodeURIComponent($('input[name="step"]').val());
                        var orders     = encodeURIComponent($('input[name="orders"]').val());
                        var data = 'orders=' + orders + '&step=' + step + '&ajax=2';

                        // ajax request
                        $.ajax({
                            type: 'POST',
                            url: handlerUrl,
                            data: data,
                            dataType: 'json',
                            success: function(response) {
                                $('input[name="step"]').val(response.step);
                                if(response.step == 'end'){
                                    $('input[name="step"]').val(0);
                                    BX.closeWait();
                                } else {
                                    orderUpload();
                                }

                                $('#indicator').css('width', response.percent + '%');
                                $('#percent').html(response.percent + '%');
                                $('#percent2').html(response.percent + '%');
                            },
                            error: function () {
                                BX.closeWait();
                                $('#status').text('<?php echo GetMessage('MESS_4'); ?>');

                                alert('<?php echo GetMessage('MESS_5'); ?>');
                            }
                        });
                    }

                    $('input[name="start"]').live('click', function() {
                        BX.showWait();
                        $('#indicator').css('width', 0);
                        $('#percent2').html('0%');
                        $('#percent').css('width', '100%');

                        orderUpload();

                        return false;
                    });
                });
            </script>

            <form id="upload-orders" action="<?php echo $uri; ?>" method="POST">
                <input type="hidden" name="step" value="0">
                <div>
                    <?php echo GetMessage('ORDER_NUMBER'); ?>
                    <input id="order-nombers" style="width:86%" type="text" value="" name="orders">
                </div>
                <br>
                <div class="instal-load-block" id="result">
                    <div class="instal-load-label" id="status"><?php echo GetMessage('ORDER_UPLOAD_INFO'); ?></div>

                    <div class="instal-progress-bar-outer">
                        <div class="instal-progress-bar-alignment" style="width: 100%;">
                            <div class="instal-progress-bar-inner" id="indicator" style="width: 0%;">
                                <div class="instal-progress-bar-inner-text" style="width: 100%;" id="percent">0%</div>
                            </div>
                            <span id="percent2">0%</span>
                        </div>
                    </div>
                </div>
                <br />
                <div class="order-upload-button">
                    <div align="left">
                        <input type="submit" name="start" value="<?php echo GetMessage('ORDER_UPL_START'); ?>" class="adm-btn-save">
                    </div>
                </div>
            </form>

        <?php $tabControl->Buttons(); ?>
        <input type="hidden" name="Update" value="Y" />
        <input type="submit" title="<?php echo GetMessage('ICRM_OPTIONS_SUBMIT_TITLE'); ?>" value="<?php echo GetMessage('ICRM_OPTIONS_SUBMIT_VALUE'); ?>" name="btn-update" class="adm-btn-save" />
        <?php $tabControl->End(); ?>
    </form>
<?php } ?>
