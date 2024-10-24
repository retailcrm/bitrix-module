<?php

use Bitrix\Currency\CurrencyManager;
use Bitrix\Main\Application;
use Bitrix\Main\EventManager;
use Bitrix\Main\LoaderException;
use Bitrix\Main\SystemException;
use Bitrix\Main\UI\Extension;
use Bitrix\Sale\Delivery\Services\Manager;
use Intaro\RetailCrm\Component\ApiClient\ClientAdapter;
use Intaro\RetailCrm\Component\ConfigProvider;
use Intaro\RetailCrm\Component\Constants;
use Intaro\RetailCrm\Repository\AgreementRepository;
use Intaro\RetailCrm\Repository\TemplateRepository;
use Intaro\RetailCrm\Service\CurrencyService;
use Intaro\RetailCrm\Service\OrderLoyaltyDataService;
use Intaro\RetailCrm\Service\Utils as RetailCrmUtils;
use RetailCrm\Exception\CurlException;
use Intaro\RetailCrm\Component\Advanced\LoyaltyInstaller;

IncludeModuleLangFile(__FILE__);
include (__DIR__ . '/lib/component/advanced/loyaltyinstaller.php');

$mid = 'intaro.retailcrm';
$uri = $APPLICATION->GetCurPage() . '?mid=' . htmlspecialchars($mid) . '&lang=' . LANGUAGE_ID;

if (!CModule::IncludeModule('intaro.retailcrm') || !CModule::IncludeModule('sale') || !CModule::IncludeModule('iblock') || !CModule::IncludeModule('catalog')) {
    return;
}

$_GET['errc'] = htmlspecialchars(trim($_GET['errc']));
$_GET['ok'] = htmlspecialchars(trim($_GET['ok']));

if (RetailcrmConfigProvider::isPhoneRequired()) {
    echo ShowMessage(["TYPE" => "ERROR", "MESSAGE" => GetMessage('PHONE_REQUIRED')]);
}

if (array_key_exists('errc', $_GET) && is_string($_GET['errc']) && strlen($_GET['errc']) > 0) {
    echo CAdminMessage::ShowMessage(GetMessage($_GET['errc']));
}
if (!empty($_GET['ok']) && $_GET['ok'] === 'Y') {
    echo CAdminMessage::ShowNote(GetMessage('ICRM_OPTIONS_OK'));
}

$arResult = [];
$enabledCustom = false;
$loyaltySetup = new LoyaltyInstaller();

if (file_exists($_SERVER["DOCUMENT_ROOT"] . '/bitrix/modules/intaro.retailcrm/classes/general/config/options.xml')) {
    $options = simplexml_load_file($_SERVER["DOCUMENT_ROOT"] . '/bitrix/modules/intaro.retailcrm/classes/general/config/options.xml');

    foreach($options->contragents->contragent as $contragent) {
        $type["NAME"] = $APPLICATION->ConvertCharset((string)$contragent, 'utf-8', SITE_CHARSET);
        $type["ID"] = (string)$contragent["id"];
        $arResult['contragentType'][] = $type;
        unset ($type);
    }
    foreach ($options->fields->field as $field) {
        $type["NAME"] = $APPLICATION->ConvertCharset((string) $field, 'utf-8', SITE_CHARSET);
        $type["ID"]   = (string) $field["id"];

        if (!$field["group"]) {
            $arResult['orderProps'][] = $type;
        } else {
            $groups = explode(",", (string) $field["group"]);
            foreach ($groups as $group) {
                $type["GROUP"][] = trim($group);
            }
            $arResult['legalDetails'][] = $type;
        }
        unset($type);
    }
}

$arResult['arSites'] = RCrmActions::getSitesList();
$arResult['arCurrencySites'] = RCrmActions::getCurrencySites();
$arResult['bitrixOrdersCustomProp'] = [];
$arResult['bitrixCustomUserFields'] = [];

if (method_exists(RCrmActions::class, 'customOrderPropList')
    && method_exists(RCrmActions::class, 'customUserFieldList')
) {
    $arResult['bitrixOrdersCustomProp'] = RCrmActions::customOrderPropList();
    $arResult['bitrixCustomUserFields'] = RCrmActions::customUserFieldList();
}

//ajax update deliveryServices
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && (strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') && isset($_POST['ajax']) && ($_POST['ajax'] == 1)) {
    $api_host = COption::GetOptionString($mid, Constants::CRM_API_HOST_OPTION, 0);
    $api_key = COption::GetOptionString($mid, Constants::CRM_API_KEY_OPTION , 0);
    $api = new RetailCrm\ApiClient($api_host, $api_key);

    try {
        $api->paymentStatusesList();
    } catch (CurlException $e) {
        RCrmActions::eventLog(
            'intaro.retailcrm/options.php', 'RetailCrm\ApiClient::paymentStatusesList::CurlException',
            $e->getCode() . ': ' . $e->getMessage()
        );

        $APPLICATION->RestartBuffer();
        header('Content-Type: application/x-javascript; charset=' . LANG_CHARSET);
        die(json_encode(['success' => false, 'errMsg' => $e->getCode()]));
    }

    $optionsDelivTypes    = unserialize(COption::GetOptionString($mid, Constants::CRM_DELIVERY_TYPES_ARR, 0));
    $arDeliveryServiceAll = Manager::getActiveList();

    foreach ($optionsDelivTypes as $key => $deliveryType) {
        foreach ($arDeliveryServiceAll as $deliveryService) {
            if ($deliveryService['PARENT_ID'] != 0 && $deliveryService['PARENT_ID'] == $key) {
                try {
                    $api->deliveryServicesEdit(RCrmActions::clearArr([
                        'code'         => 'bitrix-' . $deliveryService['ID'],
                        'name'         => RCrmActions::toJSON($deliveryService['NAME']),
                        'deliveryType' => $deliveryType,
                    ]));
                } catch (CurlException $e) {
                    RCrmActions::eventLog(
                        'intaro.retailcrm/options.php', 'RetailCrm\ApiClient::deliveryServiceEdit::CurlException',
                        $e->getCode() . ': ' . $e->getMessage()
                    );
                }
            }
        }
    }

    $APPLICATION->RestartBuffer();
    header('Content-Type: application/x-javascript; charset=' . LANG_CHARSET);
    die(json_encode(['success' => true]));
}

//upload orders after install module
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && (strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') && isset($_POST['ajax']) && $_POST['ajax'] == 2) {
    $step      = $_POST['step'];
    $orders    = $_POST['orders'];
    $countStep = 50; // 50 orders on step

    if ($orders) {
        $ordersArr = explode(',', $orders);
        $orders    = [];
        foreach ($ordersArr as $_ordersArr) {
            $ordersList = explode('-', trim($_ordersArr));
            if (count($ordersList) > 1) {
                for ($i = (int) trim($ordersList[0]); $i <= (int) trim($ordersList[count($ordersList) - 1]); $i++) {
                    $orders[] = $i;
                }
            } else {
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

        $res = ["step" => $step, "percent" => $percent, 'stepOrders' => $stepOrders];
    } else {
        $orders = [];
        for ($i = 1; $i <= $countStep; $i++) {
            $orders[] = $i + $step * $countStep;
        }

        RetailCrmOrder::uploadOrders($countStep, false, $orders);

        $step++;
        $countLeft = (int)CSaleOrder::GetList(["ID" => "ASC"], ['>ID' => $step * $countStep], []);
        $countAll  = (int)CSaleOrder::GetList(["ID" => "ASC"], [], []);
        $percent   = round(100 - ($countLeft * 100 / $countAll), 1);

        if ($countLeft === 0) {
            $step = 'end';
        }

        $res = ["step" => $step, "percent" => $percent, 'stepOrders' => $orders];
    }

    $APPLICATION->RestartBuffer();
    header('Content-Type: application/x-javascript; charset=' . LANG_CHARSET);
    die(json_encode($res));
}

if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && (strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') && isset($_POST['ajax']) && $_POST['ajax'] == 3) {
    $dateAgent = new DateTime();
    $intAgent = new DateInterval('PT60S');
    $dateAgent->add($intAgent);

    CAgent::AddAgent(
        "RetailCrmUser::fixDateCustomer();",
        $mid,
        "N",
        9999999,
        $dateAgent->format('d.m.Y H:i:s'),
        "Y",
        $dateAgent->format('d.m.Y H:i:s'),
        30
    );

    $APPLICATION->RestartBuffer();
    header('Content-Type: application/x-javascript; charset=' . LANG_CHARSET);
    die(json_encode(['success' => true]));
}

$availableSites = RetailcrmConfigProvider::getSitesList();

if (!empty($availableSites)) {
    $availableSites = array_flip($availableSites);
} else {
    $site = RetailcrmConfigProvider::getSitesAvailable();
    $availableSites[$site] = $site;
}

//update connection settings
if (isset($_POST['Update']) && ($_POST['Update'] === 'Y')) {
    $error = null;
    $api_host = htmlspecialchars(trim($_POST['api_host']));
    $api_key = htmlspecialchars(trim($_POST['api_key']));

    //bitrix site list
    $siteListArr = [];

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
        $api = new ClientAdapter($api_host, $api_key);

        try {
            $credentials = $api->getCredentials();

            if (!empty($credentials->errorMsg)) {
                $uri .= '&errc=ERR_' . $credentials->errorMsg;
                LocalRedirect($uri);
            }

            ConfigProvider::setSitesAvailable(
                count($credentials->sitesAvailable) > 0 ? $credentials->sitesAvailable[0] : ''
            );
        } catch (CurlException $e) {
            RCrmActions::eventLog(
                'intaro.retailcrm/options.php', 'RetailCrm\ApiClient::paymentStatusesList::CurlException',
                $e->getCode() . ': ' . $e->getMessage()
            );

            $uri .= '&errc=ERR_' . $e->getCode();
            LocalRedirect($uri);
        }

        COption::SetOptionString($mid, 'api_host', $api_host);
        COption::SetOptionString($mid, 'api_key', $api_key);
    } else {
        $uri .= '&errc=ERR_WRONG_CREDENTIALS';

        LocalRedirect($uri);
    }

    //form order types ids arr
    $orderTypesList = RCrmActions::OrderTypesList($arResult['arSites']);
    $orderTypesArr = [];

    foreach ($orderTypesList as $orderType) {
        $orderTypesArr[$orderType['ID']] = htmlspecialchars(trim($_POST['order-type-' . $orderType['ID']]));
    }

    //form delivery types ids arr
    $arResult['bitrixDeliveryTypesList'] = RCrmActions::DeliveryList();
    $deliveryTypesArr = [];

    foreach ($arResult['bitrixDeliveryTypesList'] as $delivery) {
        $deliveryTypesArr[$delivery['ID']] = htmlspecialchars(trim($_POST['delivery-type-' . $delivery['ID']]));
    }

    //form payment types ids arr
    $arResult['bitrixPaymentTypesList'] = RCrmActions::PaymentList();
    $paymentTypesArr = [];

    foreach ($arResult['bitrixPaymentTypesList'] as $payment) {
        $paymentTypesArr[$payment['ID']] = htmlspecialchars(trim($_POST['payment-type-' . $payment['ID']]));
    }

    //form payment statuses ids arr
    $arResult['bitrixStatusesList'] = RCrmActions::StatusesList();
    $paymentStatusesArr = [];
    $canselOrderArr     = [];

    foreach ($arResult['bitrixStatusesList'] as $status) {
        $paymentStatusesArr[$status['ID']] = htmlspecialchars(trim($_POST['payment-status-' . $status['ID']]));
        if (trim($_POST['order-cansel-' . $status['ID']]) === 'Y') {
            $canselOrderArr[] = $status['ID'];
        }
    }

    //form payment ids arr
    $paymentArr      = [];
    $paymentArr['Y'] = htmlspecialchars(trim($_POST['payment-Y']));
    $paymentArr['N'] = htmlspecialchars(trim($_POST['payment-N']));

    $previousDischarge = COption::GetOptionString($mid, Constants::CRM_ORDER_DISCHARGE, 0);
    //order discharge mode
    // 0 - agent
    // 1 - event
    $orderDischarge = (int) htmlspecialchars(trim($_POST['order-discharge']));

    if (($orderDischarge != $previousDischarge) && ($orderDischarge === 0)) {
        // remove depenedencies
        UnRegisterModuleDependences('sale', 'OnOrderUpdate', $mid, 'RetailCrmEvent', "onUpdateOrder");
        UnRegisterModuleDependences('sale', 'OnSaleOrderDeleted', $mid, 'RetailCrmEvent', "orderDelete");
    } elseif (($orderDischarge != $previousDischarge) && ($orderDischarge === 1)) {
        // event dependencies
        RegisterModuleDependences('sale', 'OnOrderUpdate', $mid, 'RetailCrmEvent', "onUpdateOrder");
        RegisterModuleDependences('sale', 'OnSaleOrderDeleted', $mid, 'RetailCrmEvent', "orderDelete");
    }

    $optionCart = COption::GetOptionString($mid, Constants::CART, 'N');

    $cart = htmlspecialchars(trim($_POST['cart']));

    if ($cart != $optionCart) {
        if ($cart === 'Y') {
            $optionCart = 'Y';
            RegisterModuleDependences('sale', 'OnSaleBasketSaved', $mid, 'RetailCrmEvent', 'onChangeBasket');
        } else {
            $optionCart = 'N';
            UnRegisterModuleDependences('sale', 'OnSaleBasketSaved', $mid, 'RetailCrmEvent', 'onChangeBasket');
        }
    }

    $orderPropsArr = [];
    foreach ($orderTypesList as $orderType) {
        $propsCount     = 0;
        $_orderPropsArr = [];

        foreach ($arResult['orderProps'] as $orderProp) {
            if (isset($_POST['address-detail-' . $orderType['ID']])) {
                $addressDatailOptions[$orderType['ID']] = $_POST['address-detail-' . $orderType['ID']];
            }

            if (
                (!(int) htmlspecialchars(trim($_POST['address-detail-' . $orderType['ID']])))
                && $propsCount > 4
            ) {
                break;
            }

            $_orderPropsArr[$orderProp['ID']] = htmlspecialchars(
                trim($_POST['order-prop-' . $orderProp['ID'] . '-' . $orderType['ID']])
            );
            $propsCount++;
        }

        $orderPropsArr[$orderType['ID']] = $_orderPropsArr;
    }

    //legal details props
    $legalDetailsArr = [];
    foreach ($orderTypesList as $orderType) {
        $_legalDetailsArr = [];
        foreach ($arResult['legalDetails'] as $legalDetails) {
            $_legalDetailsArr[$legalDetails['ID']] = htmlspecialchars(trim($_POST['legal-detail-' . $legalDetails['ID'] . '-' . $orderType['ID']]));
        }
        $legalDetailsArr[$orderType['ID']] = $_legalDetailsArr;
    }

    //contragents type list
    $contragentTypeArr = [];

    foreach ($orderTypesList as $orderType) {
        $contragentTypeArr[$orderType['ID']] = htmlspecialchars(trim($_POST['contragent-type-' . $orderType['ID']]));
    }

    //stores
    $bitrixStoresArr          = [];
    $bitrixShopsArr           = [];
    $bitrixIblocksInventories = [];

    if (htmlspecialchars(trim($_POST['inventories-upload'])) === 'Y') {
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
        foreach ($arResult['bitrixStoresExportList'] as $bitrixStores) {
            $bitrixStoresArr[$bitrixStores['ID']] = htmlspecialchars(trim($_POST['stores-export-' . $bitrixStores['ID']]));
        }

        function maskInv($var) {
            return preg_match("/^shops-exoprt/", $var);
        }

        $bitrixShopsArr = array_values(array_filter($_POST, 'maskInv', ARRAY_FILTER_USE_KEY));
        $arResult['bitrixIblocksExportList'] = RCrmActions::IblocksExportList();

        foreach ($arResult['bitrixIblocksExportList'] as $bitrixIblocks) {
            if (htmlspecialchars(trim($_POST['iblocks-stores-' . $bitrixIblocks['ID']])) === 'Y') {
                $bitrixIblocksInventories[] = $bitrixIblocks['ID'];
            }
        }
    } else {
        $inventoriesUpload = 'N';
        CAgent::RemoveAgent("RetailCrmInventories::inventoriesUpload();", $mid);
    }

    //prices
    $bitrixPricesArr     = [];
    $bitrixIblocksPrices = [];
    $bitrixPriceShopsArr = [];

    if (htmlspecialchars(trim($_POST['prices-upload'])) === 'Y') {
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

        foreach ($arResult['bitrixPricesExportList'] as $bitrixPrices) {
            $bitrixPricesArr[$bitrixPrices['ID']] = htmlspecialchars(trim($_POST['price-type-export-' . $bitrixPrices['ID']]));
        }

        function maskPrice($var) {
            return preg_match("/^shops-price/", $var);
        }

        $bitrixPriceShopsArr = array_values(array_filter($_POST, 'maskPrice', ARRAY_FILTER_USE_KEY));
        $arResult['bitrixIblocksExportList'] = RCrmActions::IblocksExportList();

        foreach ($arResult['bitrixIblocksExportList'] as $bitrixIblocks) {
            if (htmlspecialchars(trim($_POST['iblocks-prices-' . $bitrixIblocks['ID']])) === 'Y') {
                $bitrixIblocksPrices[] = $bitrixIblocks['ID'];
            }
        }
    } else {
        $pricesUpload = 'N';
        CAgent::RemoveAgent("RetailCrmPrices::pricesUpload();", $mid);
    }


    $useCrmOrderMethods = htmlspecialchars(trim($_POST['use_crm_order_methods'])) === 'Y' ? 'Y' : 'N';
    $crmOrderMethod = [];

    if ($useCrmOrderMethods === 'Y') {
        $crmOrderMethod = $_POST['crm_order_methods'];
    }

    //demon
    $collectorKeys = [];
    if (htmlspecialchars(trim($_POST['collector'])) === 'Y') {
        $collector = 'Y';
        foreach ($arResult['arSites'] as $site) {
            $collectorKeys[$site['LID']] = trim($_POST['collector-id-' . $site['LID']]);
        }
        RegisterModuleDependences("main", "OnBeforeProlog", $mid, "RetailCrmCollector", "add");
    } else {
        $collector = 'N';
        UnRegisterModuleDependences("main", "OnBeforeProlog", $mid, "RetailCrmCollector", "add");
    }

    //UA
    $uaKeys = [];
    if (htmlspecialchars(trim($_POST['ua-integration'])) === 'Y') {
        $ua = 'Y';
        foreach ($arResult['arSites'] as $site) {
            $uaKeys[$site['LID']]['ID'] = trim($_POST['ua-id-' . $site['LID']]);
            $uaKeys[$site['LID']]['INDEX'] = trim($_POST['ua-index-' . $site['LID']]);
        }
        RegisterModuleDependences("main", "OnBeforeProlog", $mid, "RetailCrmUa", "add");
    } else {
        $ua = 'N';
        UnRegisterModuleDependences("main", "OnBeforeProlog", $mid, "RetailCrmUa", "add");
    }

    //online_consultant
    if (htmlspecialchars(trim($_POST['online_consultant'] === 'Y'))) {
        $onlineConsultant = 'Y';
        $onlineConsultantScript = trim($_POST['online_consultant_script']);
        RegisterModuleDependences("main", "OnBeforeProlog", $mid, "RetailCrmOnlineConsultant", "add");
    } else {
        $onlineConsultant = 'N';
        $onlineConsultantScript = RetailcrmConfigProvider::getOnlineConsultantScript();
        UnRegisterModuleDependences("main", "OnBeforeProlog", $mid, "RetailCrmOnlineConsultant", "add");
    }

    //discount_round
    if (htmlspecialchars(trim($_POST['discount_round'])) === 'Y') {
        $discount_round = 'Y';
    } else {
        $discount_round = 'N';
    }

    //shipment
    if (htmlspecialchars(trim($_POST['shipment_deducted'])) === 'Y') {
        $shipment_deducted = 'Y';
    } else {
        $shipment_deducted = 'N';
    }

    //corporate-cliente
    if (htmlspecialchars(trim($_POST['corp-client'])) === 'Y') {
        $cc              = 'Y';
        $bitrixCorpName  = htmlspecialchars(trim($_POST['nickName-corporate']));
        $bitrixCorpAdres = htmlspecialchars(trim($_POST['adres-corporate']));

        function maskCorp($var) {
            return preg_match("/^shops-corporate/", $var);
        }

        $bitrixCorpShopsArr = array_values(array_filter($_POST, 'maskCorp', ARRAY_FILTER_USE_KEY));
    } else {
        $cc = 'N';
    }

    //purchasePrice_null
    if (htmlspecialchars(trim($_POST['purchasePrice_null'])) === 'Y') {
        $purchasePrice_null = 'Y';
    } else {
        $purchasePrice_null = 'N';
    }

    //version

    $version = COption::GetOptionString($mid, Constants::CRM_API_VERSION);

    if (htmlspecialchars(trim($_POST['api_version'])) != $version) {
        if (htmlspecialchars(trim($_POST['api_version'])) === 'v5') {
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

        $crmUrl .= 'api/' . $version;
        $client = new RetailCrm\Http\Client($crmUrl, ['apiKey' => $apiKey]);
        $result = $client->makeRequest(
            '/reference/payment-statuses',
            'GET'
        );

        if ($result->getStatusCode() === 200) {
            COption::SetOptionString($mid, Constants::CRM_API_VERSION, $version);
        } else {
            LocalRedirect($uri);
            echo CAdminMessage::ShowMessage(GetMessage('API_NOT_WORK'));
        }
    }

    if ($_POST[Constants::CRM_CURRENCY]) {
        COption::SetOptionString($mid, Constants::CRM_CURRENCY, $_POST['currency']);
    }

    if (isset($_POST['loyalty_toggle']) && $_POST['loyalty_toggle'] === 'on') {
        try {
            $loyaltySetup->CopyFiles();
            $loyaltySetup->addEvents();
            $loyaltySetup->addAgreement();
            $loyaltySetup->addUserFields();

            $hlName = RetailCrmUtils::getHlClassByName(Constants::HL_LOYALTY_CODE);

            if (empty($hlName)) {
                OrderLoyaltyDataService::createLoyaltyHlBlock();
                $service = new OrderLoyaltyDataService();
                $service->addCustomersLoyaltyFields();
            }
        } catch (Exception $exception) {
            RCrmActions::eventLog(
                'intaro.retailcrm/options.php', 'OrderLoyaltyDataService::createLoyaltyHlBlock',
                $e->getCode() . ': ' . $exception->getMessage()
            );
        }

        ConfigProvider::setLoyaltyProgramStatus('Y');
    } else {
        ConfigProvider::setLoyaltyProgramStatus('N');
        $loyaltyEventClass = 'Intaro\RetailCrm\Component\Handlers\EventsHandlers';
        UnRegisterModuleDependences('sale', 'OnSaleOrderSaved', 'intaro.retailcrm', $loyaltyEventClass, 'OnSaleOrderSavedHandler');
        UnRegisterModuleDependences('sale', 'OnSaleComponentOrderResultPrepared', 'intaro.retailcrm', $loyaltyEventClass, 'OnSaleComponentOrderResultPreparedHandler');
    }

    try {
        $arResult['paymentTypesList'] = RetailCrmService::getAvailableTypes(
            $availableSites,
            $api->paymentTypesList()->paymentTypes
        );
        $arResult['deliveryTypesList'] = RetailCrmService::getAvailableTypes(
            $availableSites,
            $api->deliveryTypesList()->deliveryTypes
        );
    } catch (CurlException $e) {
        RCrmActions::eventLog(
            'intaro.retailcrm/options.php', 'RetailCrm\ApiClient::*List::CurlException',
            $e->getCode() . ': ' . $e->getMessage()
        );

        CAdminMessage::ShowMessage(GetMessage('ERR_' . $e->getCode()));
    }

    $integrationPayments = RetailCrmService::selectIntegrationPayments($arResult['paymentTypesList']);
    $integrationDeliveries = RetailCrmService::selectIntegrationDeliveries($arResult['deliveryTypesList']);

    RetailcrmConfigProvider::setIntegrationPaymentTypes($integrationPayments);
    RetailcrmConfigProvider::setIntegrationDelivery($integrationDeliveries);

    $moduleDeactivateParam =  htmlspecialchars(trim($_POST['module-deactivate'])) ?? 'N';

    if ('Y' === $moduleDeactivateParam) {
        global $DB;

        $agents = $DB->Query("SELECT * FROM `b_agent` WHERE `MODULE_ID` = 'intaro.retailcrm';");
        $events = $DB->Query("SELECT * FROM `b_module_to_module` WHERE `TO_MODULE_ID` = 'intaro.retailcrm';");
        $deactivateAgents = [];
        $deactivateEvents = [];

        // Fetch - If the last record is reached (or there are no records as a result), the method returns false
        while ($agent = $agents->Fetch()) {
            $deactivateAgents[] = $agent;

            CAgent::RemoveAgent($agent['NAME'], $agent['MODULE_ID'], $agent['USER_ID']);
        }

        // Fetch - If the last record is reached (or there are no records as a result), the method returns false
        while ($event = $events->Fetch()) {
            $deactivateEvents[] = $event;

            UnRegisterModuleDependences(
                $event['FROM_MODULE_ID'],
                $event['MESSAGE_ID'],
                $event['TO_MODULE_ID'],
                $event['TO_CLASS'],
                $event['TO_METHOD']
            );
        }

        if ($deactivateAgents !== []) {
            COption::SetOptionString($mid, Constants::AGENTS_DEACTIVATE, serialize($deactivateAgents));
        }

        if ($deactivateEvents !== []) {
            COption::SetOptionString($mid, Constants::EVENTS_DEACTIVATE, serialize($deactivateEvents));
        }

        RCrmActions::sendConfiguration($api, false);
    } else {
        $deactivateAgents = unserialize(COption::GetOptionString($mid, Constants::AGENTS_DEACTIVATE, ''));
        $deactivateEvents = unserialize(COption::GetOptionString($mid, Constants::EVENTS_DEACTIVATE, ''));

        if (!empty($deactivateAgents)) {
            $dateAgent = new DateTime();

            // PT60S - 60 sec;
            $dateAgent->add(new DateInterval('PT60S'));

            foreach ($deactivateAgents as $agent) {
                CAgent::AddAgent(
                        $agent['NAME'],
                        $agent['MODULE_ID'],
                        'N',
                        $agent['AGENT_INTERVAL'],
                        $dateAgent->format('d.m.Y H:i:s'),
                        $agent['ACTIVE'],
                        $dateAgent->format('d.m.Y H:i:s')
                );
            }

            COption::SetOptionString($mid, Constants::AGENTS_DEACTIVATE, serialize([]));
        }

        if (!empty($deactivateEvents)) {
            $eventManager = EventManager::getInstance();

            foreach ($deactivateEvents as $event) {
                if (strpos($event['TO_METHOD'], 'Handler') !== false) {
                    $eventManager->registerEventHandler(
                        $event['FROM_MODULE_ID'],
                        $event['MESSAGE_ID'],
                        $event['TO_MODULE_ID'],
                        $event['TO_CLASS'],
                        $event['TO_METHOD']
                    );
                } else {
                    RegisterModuleDependences(
                        $event['FROM_MODULE_ID'],
                        $event['MESSAGE_ID'],
                        $event['TO_MODULE_ID'],
                        $event['TO_CLASS'],
                        $event['TO_METHOD']
                    );
                }
            }

            COption::SetOptionString($mid, Constants::EVENTS_DEACTIVATE, serialize([]));
        }

        RCrmActions::sendConfiguration($api);
    }

    COption::SetOptionString(
        $mid,
        Constants::MODULE_DEACTIVATE,
        serialize($moduleDeactivateParam)
    );
    COption::SetOptionString(
        $mid,
        Constants::CRM_ADDRESS_OPTIONS,
        serialize($addressDatailOptions)
    );
    COption::SetOptionString(
        $mid,
        Constants::CRM_SITES_LIST,
        serialize($siteListArr)
    );
    COption::SetOptionString(
        $mid,
        Constants::CRM_ORDER_TYPES_ARR,
        serialize(RCrmActions::clearArr(is_array($orderTypesArr) ? $orderTypesArr : []))
    );
    COption::SetOptionString(
        $mid,
        Constants::CRM_DELIVERY_TYPES_ARR,
        serialize(RCrmActions::clearArr(is_array($deliveryTypesArr) ? $deliveryTypesArr : []))
    );
    COption::SetOptionString(
        $mid,
        Constants::CRM_PAYMENT_TYPES,
        serialize(RCrmActions::clearArr(is_array($paymentTypesArr) ? $paymentTypesArr : []))
    );
    COption::SetOptionString(
        $mid,
        Constants::CRM_PAYMENT_STATUSES,
        serialize(RCrmActions::clearArr(is_array($paymentStatusesArr) ? $paymentStatusesArr : []))
    );
    COption::SetOptionString(
        $mid,
        Constants::CRM_PAYMENT,
        serialize(RCrmActions::clearArr(is_array($paymentArr) ? $paymentArr : []))
    );
    COption::SetOptionString(
        $mid,
        Constants::CRM_ORDER_DISCHARGE,
        $orderDischarge
    );
    COption::SetOptionString(
        $mid,
        Constants::CRM_ORDER_PROPS,
        serialize(RCrmActions::clearArr(is_array($orderPropsArr) ? $orderPropsArr : []))
    );
    COption::SetOptionString(
        $mid,
        Constants::CRM_CONTRAGENT_TYPE,
        serialize(RCrmActions::clearArr(is_array($contragentTypeArr) ? $contragentTypeArr : []))
    );
    COption::SetOptionString(
        $mid,
        Constants::CRM_LEGAL_DETAILS,
        serialize(RCrmActions::clearArr(is_array($legalDetailsArr) ? $legalDetailsArr : []))
    );
    COption::SetOptionString(
        $mid,
        Constants::CRM_ORDER_NUMBERS,
        htmlspecialchars(trim($_POST['order-numbers'])) ?: 'N'
    );
    COption::SetOptionString(
        $mid,
        Constants::CRM_ORDER_VAT,
        htmlspecialchars(trim($_POST['order-vat'])) ?: 'N'
    );

    ConfigProvider::setTrackNumberStatus(htmlspecialchars(trim($_POST['track-number'])) ?: 'N');

    $syncIntegrationPayment = htmlspecialchars(trim($_POST['sync-integration-payment'])) ?: 'N';

    if ($syncIntegrationPayment === 'Y') {
        $substitutionPaymentList = [];

        foreach (RetailcrmConfigProvider::getIntegrationPaymentTypes() as $integrationPayment) {
            if (in_array($integrationPayment, $paymentTypesArr)) {
                $originalPayment = $arResult['paymentTypesList'][$integrationPayment];
                $codePayment = $integrationPayment . Constants::CRM_PART_SUBSTITUTED_PAYMENT_CODE;

                $response = $api->paymentTypesEdit([
                    'name' => $originalPayment['name'] . ' ' . GetMessage('NO_INTEGRATION_PAYMENT'),
                    'code' => $codePayment,
                    'active' => true,
                    'description' => GetMessage('DESCRIPTION_AUTO_PAYMENT_TYPE'),
                    'sites' => $originalPayment['sites'],
                    'paymentStatuses' => $originalPayment['paymentStatuses']
                ]);

                $statusCode = $response->getStatusCode();

                if ($response->isSuccessful()) {
                    $substitutionPaymentList[$integrationPayment] = $codePayment;

                    foreach ($originalPayment['deliveryTypes'] as $codeDelivery) {
                        if (!isset($arResult['deliveryTypesList'][$codeDelivery])) {
                            continue;
                        }

                        $currentDelivery = $arResult['deliveryTypesList'][$codeDelivery];
                        $deliveryPaymentTypes = $currentDelivery['paymentTypes'];
                        $deliveryPaymentTypes[] = $codePayment;

                        $response = $api->deliveryTypesEdit([
                            'code' => $codeDelivery,
                            'paymentTypes' => $deliveryPaymentTypes,
                            'name' => $currentDelivery['name']
                        ]);

                        if (!$response->isSuccessful()) {
                            RCrmActions::eventLog(
                                'Retailcrm::options.php',
                                'syncIntegrationPayment::UpdateDelivery',
                                GetMessage('ERROR_LINK_INTEGRATION_PAYMENT') . ' : ' . $response->getResponseBody()
                            );

                            $error = 'ERR_CHECK_JOURNAL';
                        }
                    }
                } else {
                    RCrmActions::eventLog(
                        'Retailcrm::options.php',
                        'syncIntegrationPayment',
                        GetMessage('ERROR_LINK_INTEGRATION_PAYMENT') . ' : ' . $response->getResponseBody()
                    );

                    $syncIntegrationPayment = 'N';
                    $error = 'ERR_CHECK_JOURNAL';
                }
            }
        }

        RetailcrmConfigProvider::setSubstitutionPaymentList($substitutionPaymentList);
    }

    ConfigProvider::setSyncIntegrationPayment($syncIntegrationPayment);

    COption::SetOptionString(
        $mid,
        Constants::CRM_COUPON_FIELD,
        htmlspecialchars(trim($_POST['crm-coupon-field'])) ?: 'N'
    );
    COption::SetOptionString(
        $mid,
        Constants::CRM_SEND_PICKUP_POINT_ADDRESS,
        htmlspecialchars(trim($_POST['send-pickup-point-address'])) ?: 'N'
    );
    COption::SetOptionString(
        $mid,
        Constants::CRM_CANCEL_ORDER,
        serialize(RCrmActions::clearArr(is_array($canselOrderArr) ? $canselOrderArr : []))
    );
    COption::SetOptionString(
        $mid,
        Constants::CRM_INVENTORIES_UPLOAD,
        $inventoriesUpload
    );
    COption::SetOptionString(
        $mid,
        Constants::CRM_STORES,
        serialize(RCrmActions::clearArr(is_array($bitrixStoresArr) ? $bitrixStoresArr : []))
    );
    COption::SetOptionString(
        $mid,
        Constants::CRM_SHOPS,
        serialize(RCrmActions::clearArr(is_array($bitrixShopsArr) ? $bitrixShopsArr : []))
    );
    COption::SetOptionString(
        $mid,
        Constants::CRM_IBLOCKS_INVENTORIES,
        serialize(RCrmActions::clearArr(is_array($bitrixIblocksInventories) ? $bitrixIblocksInventories : []))
    );
    COption::SetOptionString(
        $mid,
        Constants::CRM_PRICES_UPLOAD,
        $pricesUpload
    );
    COption::SetOptionString(
        $mid,
        Constants::USE_CRM_ORDER_METHODS,
        $useCrmOrderMethods
    );
    COption::SetOptionString(
        $mid,
        Constants::CRM_ORDER_METHODS,
        serialize(RCrmActions::clearArr(is_array($crmOrderMethod) ? $crmOrderMethod : []))
    );
    COption::SetOptionString(
        $mid,
        Constants::CRM_PRICES,
        serialize(RCrmActions::clearArr(is_array($bitrixPricesArr) ? $bitrixPricesArr : []))
    );
    COption::SetOptionString(
        $mid,
        Constants::CRM_PRICE_SHOPS,
        serialize(RCrmActions::clearArr(is_array($bitrixPriceShopsArr) ? $bitrixPriceShopsArr : []))
    );
    COption::SetOptionString(
        $mid,
        Constants::CRM_IBLOCKS_PRICES,
        serialize(RCrmActions::clearArr(is_array($bitrixIblocksPrices) ? $bitrixIblocksPrices : []))
    );
    COption::SetOptionString(
        $mid,
        Constants::CRM_COLLECTOR,
        $collector
    );
    COption::SetOptionString(
        $mid,
        Constants::CRM_COLL_KEY,
        serialize(RCrmActions::clearArr(is_array($collectorKeys) ? $collectorKeys : []))
    );

    RetailCrmConfigProvider::setOnlineConsultant($onlineConsultant);
    RetailCrmConfigProvider::setOnlineConsultantScript($onlineConsultantScript);

    COption::SetOptionString(
        $mid,
        Constants::CRM_UA,
        $ua
    );
    COption::SetOptionString(
        $mid,
        Constants::CRM_UA_KEYS,
        serialize(RCrmActions::clearArr(is_array($uaKeys) ? $uaKeys : []))
    );
    COption::SetOptionString(
        $mid,
        Constants::CRM_DIMENSIONS,
        htmlspecialchars(trim($_POST[Constants::CRM_DIMENSIONS])) ?: 'N'
    );
    RetailcrmConfigProvider::setSendPaymentAmount(htmlspecialchars(trim($_POST[Constants::SEND_PAYMENT_AMOUNT])) ?: 'N');
    RetailCrmConfigProvider::setDiscountRound($discount_round);
    RetailcrmConfigProvider::setCart($optionCart);
    COption::SetOptionString(
        $mid,
        Constants::CRM_PURCHASE_PRICE_NULL,
        $purchasePrice_null
    );
    COption::SetOptionString(
        $mid,
        RetailcrmConstants::CRM_SHIPMENT_DEDUCTED, $shipment_deducted);
    COption::SetOptionString(
        $mid,
        Constants::CRM_CC,
        $cc
    );
    COption::SetOptionString(
        $mid,
        Constants::CRM_CORP_SHOPS,
        serialize(RCrmActions::clearArr(is_array($bitrixCorpShopsArr) ? $bitrixCorpShopsArr : []))
    );
    COption::SetOptionString(
        $mid,
        Constants::CRM_CORP_NAME,
        $bitrixCorpName
    );
    COption::SetOptionString(
        $mid,
        Constants::CRM_CORP_ADDRESS,
        $bitrixCorpAdres
    );

    if (isset($_POST['custom_fields_toggle']) && $_POST['custom_fields_toggle'] === 'on') {
        $counter = 1;
        $customOrderProps = [];
        $customUserFields = [];

        foreach ($arResult['bitrixOrdersCustomProp'] as $list) {
            foreach ($list as $code => $text) {
                if (!empty($_POST['bitrixOrderFields_' . $code]) && !empty($_POST['crmOrderFields_' . $code])) {
                    $customOrderProps[htmlspecialchars($_POST['bitrixOrderFields_' . $code])] = htmlspecialchars($_POST['crmOrderFields_' . $code]);
                }
            }
        }

        foreach ($arResult['bitrixCustomUserFields'] as $list) {
            foreach ($list as $code => $text) {
                if (!empty($_POST['bitrixUserFields_' . $code]) && !empty($_POST['crmUserFields_' . $code])) {
                    $customUserFields[htmlspecialchars($_POST['bitrixUserFields_' . $code])] = htmlspecialchars($_POST['crmUserFields_' . $code]);
                }
            }
        }

        ConfigProvider::setCustomFieldsStatus('Y');
        ConfigProvider::setMatchedOrderProps($customOrderProps);
        ConfigProvider::setMatchedUserFields($customUserFields);
    } else {
        ConfigProvider::setCustomFieldsStatus('N');
    }

    $request = Application::getInstance()->getContext()->getRequest();

    if ($request->isHttps() === true) {
        COption::SetOptionString($mid, Constants::PROTOCOL, 'https://');
    } else {
        COption::SetOptionString($mid, Constants::PROTOCOL, 'http://');
    }

    if ($error !== null) {
        $uri .= '&errc=' . $error;
    } else {
        $uri .= '&ok=Y';
    }

    LocalRedirect($uri);
} else {
    $api_host = COption::GetOptionString($mid, Constants::CRM_API_HOST_OPTION, 0);
    $api_key = COption::GetOptionString($mid, Constants::CRM_API_KEY_OPTION , 0);
    $api = new RetailCrm\ApiClient($api_host, $api_key);

    // Prepare crm lists
    try {
        $credentialsApi = $api->getCredentials()->getResponseBody();
        $requiredApiScopes = Constants::REQUIRED_API_SCOPES;

        if (ConfigProvider::getCustomFieldsStatus() === 'Y') {
            $requiredApiScopes = array_merge($requiredApiScopes, Constants::REQUIRED_API_SCOPES_CUSTOM);
        }

        $residualRight = array_diff($requiredApiScopes, $credentialsApi['scopes']);

        if (count($residualRight) !== 0) {
            throw new InvalidArgumentException(sprintf(GetMessage('ERR_403_LABEL'), implode(', ', $residualRight)));
        }

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
        $arResult['crmCustomOrderFields'] = [];
        $arResult['crmCustomUserFields'] = [];

        if (count(array_diff(Constants::REQUIRED_API_SCOPES_CUSTOM, $credentialsApi['scopes'])) === 0) {
            $arResult['crmCustomOrderFields'] = $APPLICATION->ConvertCharsetArray(
                $api->customFieldsList(['entity' => 'order', 'type' => ['string','text', 'numeric', 'boolean', 'date']], 250)->customFields,
                'utf-8',
                SITE_CHARSET
            );
            $arResult['crmCustomUserFields'] = $APPLICATION->ConvertCharsetArray(
                $api->customFieldsList(['entity' => 'customer', 'type' => ['string', 'text', 'integer', 'numeric', 'boolean', 'date']], 250)->customFields,
                'utf-8',
                SITE_CHARSET
            );
            $enabledCustom = true;
        }

        $orderMethods = [];
        $getOrderMethods = $api->orderMethodsList();

        if ($getOrderMethods !== null && $getOrderMethods->isSuccessful()) {
            foreach ($getOrderMethods->orderMethods as $method) {
                if (!$method['active']) {
                    continue;
                }

                $orderMethods[$method['code']] = $method['name'];
            }
        }

        $arResult['orderMethods'] = $orderMethods;
    } catch (CurlException $e) {
        RCrmActions::eventLog(
            'intaro.retailcrm/options.php', 'RetailCrm\ApiClient::*List::CurlException',
            $e->getCode() . ': ' . $e->getMessage()
        );

        echo CAdminMessage::ShowMessage(GetMessage('ERR_' . $e->getCode()));
    } catch (InvalidArgumentException $e) {
        $badKey = true;
        echo CAdminMessage::ShowMessage(['MESSAGE' => sprintf(GetMessage('ERR_403'), $e->getMessage()), 'HTML' => true]);
    } catch (\RetailCrm\Exception\InvalidJsonException $e) {
        $badJson = true;
        echo CAdminMessage::ShowMessage(GetMessage('ERR_JSON'));
    }

    $crmCustomOrderFieldsList = [];
    $crmCustomUserFieldsList = [];

    foreach ($arResult['crmCustomOrderFields'] as $customField) {
        $type = $customField['type'];

        if ($type === 'text') {
            $type = 'string';
        }

        $crmCustomOrderFieldsList[strtoupper($type) . '_TYPE'][] = ['name' => $customField['name'], 'code' => $customField['code']];
    }

    foreach ($arResult['crmCustomUserFields'] as $customField) {
        $type = $customField['type'];

        if ($type === 'text') {
            $type = 'string';
        }

        $crmCustomUserFieldsList[strtoupper($type). '_TYPE'][] = ['name' => $customField['name'], 'code' => $customField['code']];
    }

    ksort($crmCustomOrderFieldsList);
    ksort($crmCustomUserFieldsList);

    $arResult['crmCustomOrderFields'] = $crmCustomOrderFieldsList;
    $arResult['crmCustomUserFields'] = $crmCustomUserFieldsList;

    unset($crmCustomOrderFieldsList, $crmCustomUserFieldsList);

    $arResult['matchedOrderProps'] = ConfigProvider::getMatchedOrderProps();
    $arResult['matchedUserFields'] = ConfigProvider::getMatchedUserFields();

    $arResult['paymentTypesList'] = RetailCrmService::getAvailableTypes(
        $availableSites,
        $api->paymentTypesList()->paymentTypes
    );

    $arResult['paymentTypesList'] = array_filter(
            $arResult['paymentTypesList'],
            function ($payment) {
                return strripos($payment['code'], Constants::CRM_PART_SUBSTITUTED_PAYMENT_CODE) === false;
            }
    );

    $arResult['deliveryTypesList'] = RetailCrmService::getAvailableTypes(
        $availableSites,
        $api->deliveryTypesList()->deliveryTypes
    );

    $integrationPayments = RetailCrmService::selectIntegrationPayments($arResult['paymentTypesList']);
    $integrationDeliveries = RetailCrmService::selectIntegrationDeliveries($arResult['deliveryTypesList']);

    RetailcrmConfigProvider::setIntegrationPaymentTypes($integrationPayments);
    RetailcrmConfigProvider::setIntegrationDelivery($integrationDeliveries);

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
    $arResult['locationProp'] = RCrmActions::getLocationProps();

    $arResult['bitrixIblocksExportList'] = RCrmActions::IblocksExportList();
    $arResult['bitrixStoresExportList'] = RCrmActions::StoresExportList();
    $arResult['bitrixPricesExportList'] = RCrmActions::PricesExportList();

    //saved params
    $useCrmOrderMethods = ConfigProvider::useCrmOrderMethods();
    $crmOrderMethods = unserialize(COption::GetOptionString($mid, Constants::CRM_ORDER_METHODS, 0));
    $moduleDeactivate = unserialize(COption::GetOptionString($mid, Constants::MODULE_DEACTIVATE, 'N'));
    $optionsOrderTypes = unserialize(COption::GetOptionString($mid, Constants::CRM_ORDER_TYPES_ARR, 0));
    $optionsDelivTypes = unserialize(COption::GetOptionString($mid, Constants::CRM_DELIVERY_TYPES_ARR, 0));
    $optionsPayTypes = unserialize(COption::GetOptionString($mid, Constants::CRM_PAYMENT_TYPES, 0));
    $optionsPayStatuses = unserialize(COption::GetOptionString($mid, Constants::CRM_PAYMENT_STATUSES, 0));
    $optionsPayment = unserialize(COption::GetOptionString($mid, Constants::CRM_PAYMENT, 0));
    $optionsSitesList = unserialize(COption::GetOptionString($mid, Constants::CRM_SITES_LIST, 0));
    $optionsDischarge = (int) COption::GetOptionString($mid, Constants::CRM_ORDER_DISCHARGE, 0);
    $optionsOrderProps = unserialize(COption::GetOptionString($mid, Constants::CRM_ORDER_PROPS, 0));
    $optionsContragentType = unserialize(COption::GetOptionString($mid, Constants::CRM_CONTRAGENT_TYPE, 0));
    $optionsLegalDetails = unserialize(COption::GetOptionString($mid, Constants::CRM_LEGAL_DETAILS, 0));
    $optionsCustomFields = unserialize(COption::GetOptionString($mid, Constants::CRM_CUSTOM_FIELDS, 0));
    $optionsOrderNumbers = COption::GetOptionString($mid, Constants::CRM_ORDER_NUMBERS, 0);
    $optionsOrderVat = COption::GetOptionString($mid, Constants::CRM_ORDER_VAT, 0);
    $optionsOrderTrackNumber = ConfigProvider::getTrackNumberStatus();
    $optionsSyncIntegrationPayment = ConfigProvider::getSyncIntegrationPayment();
    $canselOrderArr = unserialize(COption::GetOptionString($mid, Constants::CRM_CANCEL_ORDER, 0));
    $sendPickupPointAddress = COption::GetOptionString($mid, Constants::CRM_SEND_PICKUP_POINT_ADDRESS, 'N');

    $optionInventotiesUpload = COption::GetOptionString($mid, Constants::CRM_INVENTORIES_UPLOAD, 0);
    $optionStores = unserialize(COption::GetOptionString($mid, Constants::CRM_STORES, 0));
    $optionShops = unserialize(COption::GetOptionString($mid, Constants::CRM_SHOPS, 0));
    $optionIblocksInventories = unserialize(COption::GetOptionString($mid, Constants::CRM_IBLOCKS_INVENTORIES, 0));
    $optionShopsCorporate = unserialize(COption::GetOptionString($mid, Constants::CRM_SHOPS, 0));

    $optionPricesUpload = COption::GetOptionString($mid, Constants::CRM_PRICES_UPLOAD, 0);
    $optionPrices = unserialize(COption::GetOptionString($mid, Constants::CRM_PRICES, 0));
    $optionPriceShops = unserialize(COption::GetOptionString($mid, Constants::CRM_PRICE_SHOPS, 0));
    $optionIblocksPrices = unserialize(COption::GetOptionString($mid, Constants::CRM_IBLOCKS_PRICES, 0));

    $optionCollector = COption::GetOptionString($mid, Constants::CRM_COLLECTOR, 0);
    $optionCollectorKeys = unserialize(COption::GetOptionString($mid, Constants::CRM_COLL_KEY));

    $optionOnlineConsultant = RetailcrmConfigProvider::isOnlineConsultantEnabled();
    $optionOnlineConsultantScript = RetailcrmConfigProvider::getOnlineConsultantScript();

    $optionUa = COption::GetOptionString($mid, Constants::CRM_UA, 0);
    $optionUaKeys = unserialize(COption::GetOptionString($mid, Constants::CRM_UA_KEYS));

    $optionDiscRound = COption::GetOptionString($mid, Constants::CRM_DISCOUNT_ROUND, 0);
    $optionPricePrchaseNull = COption::GetOptionString($mid, Constants::CRM_PURCHASE_PRICE_NULL, 0);
    $optionShipmentDeducted = RetailcrmConfigProvider::getShipmentDeducted();

    //corporate-cliente
    $optionCorpClient = COption::GetOptionString($mid, Constants::CRM_CC, 0);
    $optionCorpShops = unserialize(COption::GetOptionString($mid, Constants::CRM_CORP_SHOPS, 0));
    $optionsCorpComName = COption::GetOptionString($mid, Constants::CRM_CORP_NAME, 0);
    $optionsCorpAdres = COption::GetOptionString($mid, Constants::CRM_CORP_ADDRESS, 0);

    $version = COption::GetOptionString($mid, Constants::CRM_API_VERSION, 0);

    $optionsFixDateCustomer = COption::GetOptionString($mid, RetailcrmConstants::OPTION_FIX_DATE_CUSTOMER, 0);

    // Old functional
    $currencyOption = COption::GetOptionString($mid, Constants::CRM_CURRENCY, 0) ?: CCurrency::GetBaseCurrency();

    //Validate currency
    $currencyList = CurrencyManager::getCurrencyList();

    $errorsText = [];

    if (preg_match('/&errc=ERR_(.*)/is', $APPLICATION->GetCurUri(), $matches)) {
        $errorsText[] = GetMessage(urldecode($matches[1]));
    }

    if (empty($errorsText)) {
        if (count($arResult['arSites']) === 1 && count($arResult['sitesList']) > 1) {
            $errorsText[] = GetMessage('ERR_COUNT_SITES');
        }

        if (count($arResult['arSites']) > 1) {
            foreach ($optionsSitesList as $LID => $crmCode) {
                if (empty($crmCode)) {
                    continue;
                }

                $cmsCurrency = $arResult['arCurrencySites'][$LID] ?? null;
                $crmCurrency = $arResult['sitesList'][$crmCode]['currency'] ?? null;
                $crmSiteName = $arResult['sitesList'][$crmCode]['name'] ?? null;

                $errorCode = CurrencyService::validateCurrency($cmsCurrency, $crmCurrency);

                if ($errorCode === 'ERR_CMS_CURRENCY') {
                    $errorsText[] = GetMessage($errorCode) . ' (' . $LID . ')';
                } elseif($errorCode !== '') {
                    $errorsText[] = GetMessage($errorCode) . ' (' . GetMessage('CRM_STORE') . $crmSiteName . ')';
                }
            }
        } else {
            $LID = $arResult['arSites'][0]['LID'] ?? null;
            $cmsCurrency = $arResult['arCurrencySites'][$LID] ?? null;

            $crmSiteData = reset($arResult['sitesList']);
            $crmCurrency = $crmSiteData['currency'] ?? null;

            $errorsText[] = GetMessage(CurrencyService::validateCurrency($cmsCurrency, $crmCurrency));
        }
    }

    $customFields = [['code' => '__default_empty_value__', 'name' => GetMessage('SELECT_VALUE')]];
    $crmCouponFieldOption = COption::GetOptionString($mid, Constants::CRM_COUPON_FIELD, 0) ?: null;
    $page = 1;

    do {
        $getCustomFields = $api->customFieldsList(['entity' => 'order', 'type' => ['string', 'text']], 100, $page);

        if (!$getCustomFields->isSuccessful() && empty($getCustomFields['customFields'])) {
            break;
        }

        foreach ($getCustomFields['customFields'] as $customField) {
            $customFields[] = $customField;
        }

        $page++;
    } while($getCustomFields['pagination']['currentPage'] < $getCustomFields['pagination']['totalPageCount']);

    $optionsOrderDimensions = COption::GetOptionString($mid, Constants::CRM_DIMENSIONS, 'N');
    $addressOptions = unserialize(COption::GetOptionString($mid, Constants::CRM_ADDRESS_OPTIONS, 0));

    $optionCart = COption::GetOptionString($mid, Constants::CART, 'N');

    //loyalty program options
    $loyaltyProgramToggle = ConfigProvider::getLoyaltyProgramStatus();

    $aTabs      = [
        [
            "DIV"   => "edit1",
            "TAB"   => GetMessage('ICRM_OPTIONS_GENERAL_TAB'),
            "ICON"  => "",
            "TITLE" => GetMessage('ICRM_OPTIONS_GENERAL_CAPTION'),
        ],
        [
            "DIV"   => "edit2",
            "TAB"   => GetMessage('ICRM_OPTIONS_CATALOG_TAB'),
            "ICON"  => '',
            "TITLE" => GetMessage('ICRM_OPTIONS_CATALOG_CAPTION'),
        ],
        [
            "DIV"   => "edit3",
            "TAB"   => GetMessage('ICRM_OPTIONS_ORDER_PROPS_TAB'),
            "ICON"  => '',
            "TITLE" => GetMessage('ICRM_OPTIONS_ORDER_PROPS_CAPTION'),
        ],
        [
            "DIV"   => "edit4",
            "TAB"   => GetMessage('LOYALTY_PROGRAM_TITLE'),
            "ICON"  => '',
            "TITLE" => GetMessage('ICRM_OPTIONS_LOYALTY_PROGRAM_CAPTION'),
        ],
        [
            "DIV" => "edit5",
            "TAB" => GetMessage('CUSTOM_FIELDS_TITLE'),
            "ICON" => '',
            "TITLE" => GetMessage('CUSTOM_FIELDS_CAPTION'),
        ],
        [
            "DIV"   => "edit6",
            "TAB"   => GetMessage('UPLOAD_ORDERS_OPTIONS'),
            "ICON"  => '',
            "TITLE" => GetMessage('ORDER_UPLOAD'),
        ],
        [
            "DIV"   => "edit7",
            "TAB"   => GetMessage('OTHER_OPTIONS'),
            "ICON"  => '',
            "TITLE" => GetMessage('ICRM_OPTIONS_OTHER_CAPTION'),
        ]
    ];
    $tabControl = new CAdminTabControl("tabControl", $aTabs);
    $tabControl->Begin();
    ?>
    <?php
    CJSCore::Init(array("jquery"));

    try {
        Extension::load("ui.notification");
    } catch (LoaderException $exception) {
        RCrmActions::eventLog(
            'intaro.retailcrm/options.php', 'Extension::load',
            $e->getCode() . ': ' . $exception->getMessage()
        );
    }
    ?>

    <?php CJSCore::Init(['jquery']);?>

    <script type="text/javascript">
        function createTemplates(donor) {
            BX.ajax.runAction('intaro:retailcrm.api.adminpanel.createTemplate',
                {
                    data: {
                        sessid:    BX.bitrix_sessid(),
                        templates: [
                            {
                                'location': '/local/templates/',
                                'name':     '.default'
                            }
                        ],
                        donor:     donor
                    }
                }
            ).then(result => {
                if (result.data.status !== undefined && result.data.status === true) {
                    BX.UI.Notification.Center.notify({
                        content: "<?= GetMessage('TEMPLATE_SUCCESS_COPING') ?>"
                    });
                } else {
                    BX.UI.Notification.Center.notify({
                        content: "<?= GetMessage('TEMPLATE_COPING_ERROR') ?>"
                    });
                }

            });
        }

        function replaceDefaultTemplates(donor) {
            let templates = [];
            let i = 0;

            let node;

            if (donor === 'sale.order.ajax') {
                node = $('#lp-sale-templates input:checkbox:checked');
            }

            if (donor === 'main.register') {
                node = $('#lp-reg-templates input:checkbox:checked');
            }

            if (donor === 'sale.basket.basket') {
                node = $('#lp-basket-templates input:checkbox:checked');
            }

            node.each(
                function(index, checkbox){
                    templates[i] = {
                        'name': $(checkbox).val(),
                        'location': $(checkbox).attr('templateFolder')
                    };
                    i++;
                }
            );

            BX.ajax.runAction('intaro:retailcrm.api.adminpanel.createTemplate',
                {
                    data: {
                        sessid:     BX.bitrix_sessid(),
                        templates:  templates,
                        donor:      donor,
                        replaceDefaultTemplate: 'Y'
                    }
                }
            ).then(result => {
                if (result.data.status !== undefined && result.data.status === true) {
                    BX.UI.Notification.Center.notify({
                        content: "<?= GetMessage('TEMPLATES_SUCCESS_COPING') ?>"
                    });
                } else {
                    BX.UI.Notification.Center.notify({
                        content: "<?= GetMessage('TEMPLATES_COPING_ERROR') ?>"
                    });
                }
            });;
        }

        function editSaleTemplates(method) {
            let templates = [];
            let i = 0;

            $('#lp-templates input:checkbox:checked')
                .each(
                    function(index, checkbox){
                        templates[i] = $(checkbox).val();
                        i++;
                    }
                );
            let requestAdress = 'intaro:retailcrm.api.adminpanel.' + method;
            BX.ajax.runAction(requestAdress,
                {
                    data: {
                        sessid: BX.bitrix_sessid(),
                        templates: templates
                    }
                }
            );
        }

        function replaceDefSaleTemplate() {
            console.log($('#lp-templates').serializeArray());
            BX.ajax.runAction('intaro:retailcrm.api.adminpanel.replaceDefSaleTemplate',
                {
                    data: {
                        sessid: BX.bitrix_sessid()
                    }
                }
            )
        }

        function replaceDefSaleTemplate() {
            console.log($('#lp-templates').serializeArray());
            BX.ajax.runAction('intaro:retailcrm.api.adminpanel.replaceDefSaleTemplate',
                {
                    data: {
                        sessid: BX.bitrix_sessid()
                    }
                }
            )
        }

        function switchCrmOrderMethods() {
            $('#crm_order_methods').toggle(500);
        }

        function switchPLStatus() {
            $('#loyalty_main_settings').toggle(500);
        }

        function switchCustomFieldsStatus() {
            $('#custom_fields_settings').toggle(500);
        }

        function createMatched(type)
        {
            let bitrixName = "bitrix" + type + "Fields";
            let crmName = "crm" + type + "Fields";

            let elements = document.getElementsByClassName("adm-list-table-row matched-" + type);
            let nextId = 1;

            if (elements.length >= 1) {
                let lastElement = elements[elements.length - 1];
                nextId = parseInt(lastElement.id.replace("matched" + type + "Fields_", "")) + 1;
            }

            let matchedBlank = document.getElementById(type + "MatchedFieldsBlank");
            let matchedElement = matchedBlank.cloneNode(true);

            matchedElement.classList.add("adm-list-table-row");
            matchedElement.classList.add("matched-" + type);
            matchedElement.setAttribute("id", "matched" + type + "Fields_" + nextId);
            matchedElement.querySelector(`select[name=${bitrixName}`).setAttribute("name", bitrixName + "_" + nextId);
            matchedElement.querySelector(`select[name=${crmName}`).setAttribute("name", crmName + "_" + nextId);
            matchedElement.removeAttribute("hidden");

            let element = document.getElementById(type + "_matched");

            if (element) {
                element.appendChild(matchedElement);
            }
        }

        function deleteMatched(element)
        {
            element.parentNode.parentNode.remove();
        }

        function generateEmptyMatched()
        {
            let elements = document.getElementsByClassName("adm-list-table-row matched-Order");

            if (elements.length < 1) {
                createMatched("Order");
            }

            elements = document.getElementsByClassName("adm-list-table-row matched-User");

            if (elements.length < 1) {
                createMatched("User");
            }
        }

        function changeSelectBitrixValue(element, nameBitrix, nameCrm)
        {
            let name = element.getAttribute("name");
            let uniqIdSelect = name.replace(nameBitrix, "");
            let selectedValue = element.value;
            let checkElements = document.getElementsByName(nameCrm + selectedValue);

            if (checkElements.length === 0) {
                let selectCrm = document.getElementsByName(nameCrm + uniqIdSelect);
                selectCrm[0].setAttribute('name', nameCrm + selectedValue);
                element.setAttribute('name', nameBitrix + selectedValue);
            } else {
                let text = element.options[element.selectedIndex].text;
                element.value = uniqIdSelect;

                alert('Поле: "' + text +'" уже используется в обмене');
            }
        }

        function changeSelectCrmValue(element, nameElement)
        {
            let selectedValue = element.value;
            let checkElement = document.getElementById(nameElement + selectedValue)

            if (checkElement === null) {
                element.id = nameElement + selectedValue;
            } else {
                let currentId = element.id;
                let code = '';

                if (currentId !== null) {
                    code = currentId.replace(nameElement, "");
                }

                let text = element.options[element.selectedIndex].text;
                element.value = code;

                alert('Поле: "' + text + '" уже используется в обмене');
            }
        }

        function updateAddressList()
        {
            splitName = $(this).attr('name').split('-');
            orderType = splitName[2];

            if (parseInt($(this).val()) === 1) {
                let locationElement = document.getElementById('locationElement-' + orderType);
                let replacedSelect = document.getElementsByName('order-prop-text-' + orderType);
                let replacedElement = replacedSelect[0].parentNode.parentNode;
                let addedLocation = locationElement.cloneNode(true);

                addedLocation.querySelector(`select`).setAttribute("name", 'order-prop-text-' + orderType);
                addedLocation.removeAttribute("hidden");
                addedLocation.removeAttribute("id");
                replacedElement.replaceWith(addedLocation);

                $('tr.address-detail-' + orderType).show('slow');

            } else if (parseInt($(this).val()) === 0) {
                let locationElement = document.getElementById('textAddressElement-' + orderType);
                let replacedSelect = document.getElementsByName('order-prop-text-' + orderType);
                let replacedElement = replacedSelect[0].parentNode.parentNode;
                let addedLocation = locationElement.cloneNode(true);

                addedLocation.querySelector(`select`).setAttribute("name", 'order-prop-text-' + orderType);
                addedLocation.removeAttribute("hidden");
                addedLocation.removeAttribute("id");
                replacedElement.replaceWith(addedLocation);

                $('tr.address-detail-' + orderType).hide('slow');
            }
        }

        $(document).ready(function() {
            $('input[name^="address-detail-"]').change(updateAddressList);
            $('input:checked[name^="address-detail-"]').each(updateAddressList);

            $('tr.contragent-type select').change(function() {
                splitName      = $(this).attr('name').split('-');
                contragentType = $(this).val();
                orderType = splitName[2];

                $('tr.legal-detail-' + orderType).hide();
                $('.legal-detail-title-' + orderType).hide();

                $('tr.legal-detail-' + orderType).each(function() {
                    if ($(this).hasClass(contragentType)) {
                        $(this).show();
                        $('.legal-detail-title-' + orderType).show();
                    }
                });
            });

            $('.inventories-batton label').change(function() {
                if ($(this).find('input').is(':checked') === true) {
                    $('tr.inventories').show('slow');
                } else if ($(this).find('input').is(':checked') === false) {
                    $('tr.inventories').hide('slow');
                }

                return true;
            });

            $('.prices-batton label').change(function() {
                if ($(this).find('input').is(':checked') === true) {
                    $('tr.prices').show('slow');
                } else if ($(this).find('input').is(':checked') === false) {
                    $('tr.prices').hide('slow');
                }

                return true;
            });

            $('.r-ua-button label').change(function() {
                if ($(this).find('input').is(':checked') === true) {
                    $('tr.r-ua').show('slow');
                } else if ($(this).find('input').is(':checked') === false) {
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

            $('.r-sync-payment-button label').change(function() {
                if ($(this).find('input').is(':checked') === true) {
                    $('tr.r-sync-payment').show('slow');
                } else if ($(this).find('input').is(':checked') === false) {
                    $('tr.r-sync-payment').hide('slow');
                }

                return true;
            });

            $('.r-ac-button label').change(function() {
                if ($(this).find('input').is(':checked') === true) {
                    $('tr.r-ac').show('slow');
                } else if ($(this).find('input').is(':checked') === false) {
                    $('tr.r-ac').hide('slow');
                }

                return true;
            })

            $('.r-cc-button label').change(function() {
                if ($(this).find('input').is(':checked') === true) {
                    $('tr.r-cc').show('slow');
                } else if ($(this).find('input').is(':checked') === false) {
                    $('tr.r-cc').hide('slow');
                }

                return true;
            });

            $('.r-coll-button label').change(function() {
                if ($(this).find('input').is(':checked') === true) {
                    $('tr.r-coll').show('slow');
                } else if ($(this).find('input').is(':checked') === false) {
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
                if ($(this).find('input').is(':checked') === true) {
                    $('tr.r-purchaseprice').show('slow');
                } else if ($(this).find('input').is(':checked') === false) {
                    $('tr.r-purchaseprice').hide('slow');
                }

                return true;
            });

            $('input[name="update-delivery-services"]').on('click', function() {
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

                        if (!response.success)
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
        });
    </script>
    <style type="text/css">
		.option-other-bottom {
			border-bottom: 0px !important;
		}

		.option-other-top {
			border-top: 1px solid #f5f9f9 !important;
		}

		.option-other-center {
			border-top: 5px solid #f5f9f9 !important;
			border-bottom: 5px solid #f5f9f9 !important;
		}

		.option-other-heading {
			border-top: 25px solid #f5f9f9 !important;
			border-bottom: 0px solid #f5f9f9 !important;
		}

		.option-other-empty {
			border-bottom: 15px solid #f5f9f9 !important;
		}

		.option-head {
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

        <tr >
            <td width="50%" class="adm-detail-content-cell-l"><?php echo GetMessage('ICRM_API_HOST'); ?></td>
            <td width="50%" class="adm-detail-content-cell-r"><input type="text" id="api_host" name="api_host" value="<?php echo $api_host; ?>"></td>
        </tr>
        <tr>
            <td width="50%" class="adm-detail-content-cell-l"><?php echo GetMessage('ICRM_API_KEY'); ?></td>
            <td width="50%" class="adm-detail-content-cell-r"><input type="text" id="api_key" name="api_key" value="<?php echo $api_key; ?>"></td>
        </tr>

        <?php if ($errorsText): ?>
            <?php foreach ($errorsText as $error): ?>
                <tr align="center">
                    <td colspan="2">
                        <strong style="color:red" >
                            <?php echo $error; ?>
                        </strong>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (count($arResult['arSites']) > 1): ?>
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
                        <select class="typeselect" name="sites-id-<?php echo $site['LID'] ?>">
                            <option value=""></option>
                            <?php foreach ($arResult['sitesList'] as $sitesList): ?>
                                <option value="<?php echo $sitesList['code'] ?>" <?php if ($sitesList['code'] === $optionsSitesList[$site['LID']]) {
                                    echo 'selected="selected"';
                                } ?>><?php echo $sitesList['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        <?php if (!$badKey && !$badJson): ?>
            <?php $tabControl->BeginNextTab(); ?>
            <input type="hidden" name="tab" value="catalog">
            <tr class="option-head">
                <td colspan="2"><b><?php echo GetMessage('INFO_1'); ?></b></td>
            </tr>
            <tr class="heading">
                <td colspan="2"><b><?php echo GetMessage('DELIVERY_TYPES_LIST'); ?></b></td>
            </tr>
        <?php foreach ($arResult['bitrixDeliveryTypesList'] as $bitrixDeliveryType): ?>
            <tr>
                <td width="50%" class="adm-detail-content-cell-l" name="<?php echo $bitrixDeliveryType['ID']; ?>">
                    <?php echo $bitrixDeliveryType['NAME']; ?>
                </td>
                <td width="50%" class="adm-detail-content-cell-r">
                    <label>
                        <select name="delivery-type-<?php echo $bitrixDeliveryType['ID']; ?>" class="typeselect">
                            <option value=""></option>
                            <?php foreach ($arResult['deliveryTypesList'] as $deliveryType): ?>
                                <option value="<?php echo $deliveryType['code']; ?>" <?php if ($optionsDelivTypes[$bitrixDeliveryType['ID']] === $deliveryType['code']) {
                                    echo 'selected';
                                } ?>>
                                    <?php echo $APPLICATION->ConvertCharset($deliveryType['name'], 'utf-8', SITE_CHARSET); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </td>
            </tr>
        <?php endforeach; ?>
            <tr class="heading">
                <td colspan="2">
                    <input type="submit" name="update-delivery-services" value="<?php echo GetMessage('UPDATE_DELIVERY_SERVICES'); ?>" class="adm-btn-save">
                </td>
            </tr>
            <tr class="heading">
                <td colspan="2"><b><?php echo GetMessage('PAYMENT_TYPES_LIST'); ?></b></td>
            </tr>
        <?php foreach ($arResult['bitrixPaymentTypesList'] as $bitrixPaymentType): ?>
            <tr>
                <td width="50%" class="adm-detail-content-cell-l" name="<?php echo $bitrixPaymentType['ID']; ?>">
                    <?php echo $bitrixPaymentType['NAME']; ?>
                </td>
                <td width="50%" class="adm-detail-content-cell-r">
                    <label>
                        <select name="payment-type-<?php echo $bitrixPaymentType['ID']; ?>" class="typeselect">
                            <option value="" selected=""></option>
                            <?php foreach ($arResult['paymentTypesList'] as $paymentType): ?>
                                <option value="<?php echo $paymentType['code']; ?>"
                                    <?php if ($optionsPayTypes[$bitrixPaymentType['ID']] === $paymentType['code']) {
                                    echo 'selected';
                                } ?>>
                                    <?php echo $APPLICATION->ConvertCharset($paymentType['name'], 'utf-8', SITE_CHARSET); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </td>
            </tr>
        <?php endforeach; ?>

            <tr class="heading r-sync-payment-button">
                <td colspan="2" class="option-other-heading">
                    <b>
                        <label>
                            <input class="addr" type="checkbox" name="sync-integration-payment" value="Y" <?php if ($optionsSyncIntegrationPayment === 'Y') {
                                echo "checked";
                            } ?>> <?php echo GetMessage('SYNC_INTEGRATION_PAYMENT'); ?>
                        </label>
                    </b>
                </td>
            </tr>

            <tr class="r-sync-payment" <?php if ($optionsSyncIntegrationPayment !== 'Y') {
                echo 'style="display: none;"';
            } ?>>
                <td class="option-head" colspan="2">
                    <p><b><?php echo GetMessage('INTEGRATION_PAYMENT_LABEL'); ?></b></p>
                    <p><b><?php echo GetMessage('NEED_PERMISSIONS_REFERENCE_LABEL'); ?></b></p>
                </td>
            </tr>

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
                                    <?php foreach ($arResult['paymentGroupList'] as $orderStatusGroup): if (!empty($orderStatusGroup['statuses'])) : ?>
                                        <optgroup label="<?php echo $APPLICATION->ConvertCharset($orderStatusGroup['name'], 'utf-8', SITE_CHARSET); ?>">
                                            <?php foreach ($orderStatusGroup['statuses'] as $payment): ?>
                                                <?php if (isset($arResult['paymentList'][$payment])): ?>
                                                    <?php if($arResult['paymentList'][$payment]['active'] === true): ?>
                                                        <option value="<?php echo $arResult['paymentList'][$payment]['code']; ?>" <?php if ($optionsPayStatuses[$bitrixPaymentStatus['ID']]
                                                            === $arResult['paymentList'][$payment]['code']) {
                                                            echo 'selected';
                                                        } ?>>
                                                            <?php echo $APPLICATION->ConvertCharset($arResult['paymentList'][$payment]['name'], 'utf-8', SITE_CHARSET); ?>
                                                        </option>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endif; endforeach; ?>
                                </select>
                            </td>
                            <td width="30%">
                                <label>
                                    <input name="order-cansel-<?php echo $bitrixPaymentStatus['ID']; ?>" <?php if (is_array($canselOrderArr) && in_array($bitrixPaymentStatus['ID'], $canselOrderArr)) {
                                        echo "checked";
                                    } ?> value="Y" type="checkbox"/>
                                </label>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        <?php endforeach; ?>
            <tr class="heading">
                <td colspan="2"><b><?php echo GetMessage('PAYMENT_LIST'); ?></b></td>
            </tr>
        <?php foreach ($arResult['bitrixPaymentList'] as $bitrixPayment): ?>
            <tr>
                <td width="50%" class="adm-detail-content-cell-l" name="<?php echo $bitrixPayment['ID']; ?>">
                    <?php echo $bitrixPayment['NAME']; ?>
                </td>
                <td width="50%" class="adm-detail-content-cell-r">
                    <select name="payment-<?php echo $bitrixPayment['ID']; ?>" class="typeselect">
                        <option value=""></option>
                        <?php foreach ($arResult['paymentStatusesList'] as $paymentStatus): ?>
                            <?php if($paymentStatus['active'] === true): ?>
                                <option value="<?php echo $paymentStatus['code']; ?>" <?php if ($optionsPayment[$bitrixPayment['ID']] === $paymentStatus['code']) {
                                    echo 'selected';
                                } ?>>
                                    <?php echo $APPLICATION->ConvertCharset($paymentStatus['name'], 'utf-8', SITE_CHARSET); ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        <?php endforeach; ?>
            <tr class="heading">
                <td colspan="2"><b><?php echo GetMessage('ORDER_TYPES_LIST'); ?></b></td>
            </tr>
        <?php if (isset($isCustomOrderType) && $isCustomOrderType): ?>
            <tr>
                <td colspan="2" style="text-align: center!important; padding-bottom:10px;"><b style="color:#c24141;"><?php echo GetMessage('ORDER_TYPES_LIST_CUSTOM'); ?></b></td>
            </tr>
        <?php endif; ?>
        <?php foreach ($arResult['bitrixOrderTypesList'] as $bitrixOrderType): ?>
            <tr>
                <td width="50%" class="adm-detail-content-cell-l" name="<?php echo $bitrixOrderType['ID']; ?>">
                    <?php echo $bitrixOrderType['NAME']; ?>
                </td>
                <td width="50%" class="adm-detail-content-cell-r">
                    <select name="order-type-<?php echo $bitrixOrderType['ID']; ?>" class="typeselect">
                        <option value=""></option>
                        <?php foreach ($arResult['orderTypesList'] as $orderType): ?>
                            <?php if($orderType['active'] === true): ?>
                                <option value="<?php echo $orderType['code']; ?>"
                                    <?php if ($optionsOrderTypes[$bitrixOrderType['ID']] === $orderType['code']) {
                                        echo 'selected';
                                    } ?>>
                                    <?= $APPLICATION
                                        ->ConvertCharset($orderType['name'], 'utf-8', SITE_CHARSET)
                                    ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        <?php endforeach; ?>
            <tr class="heading">
                <td colspan="2"><b><?php echo GetMessage('CRM_ORDER_METHODS'); ?></b></td>
            </tr>

            <tr>
                <td colspan="2" style="text-align: center!important;">
                    <label><input class="addr" type="checkbox" name="use_crm_order_methods" value="Y" onclick="switchCrmOrderMethods();" <?php if ($useCrmOrderMethods === 'Y') {
                            echo "checked";
                        } ?>><?php echo GetMessage('CRM_ORDER_METHODS_OPTION'); ?></label>
                </td>
            </tr>

            <tr id="crm_order_methods" style="display:<?php echo $useCrmOrderMethods !== 'Y' ? 'none' : '';?>">
                <td colspan="2" style="text-align: center!important;">
                    <br><br>
                    <select multiple size="<?php echo count($arResult['orderMethods']);?>" name="crm_order_methods[]">
                        <?php foreach ($arResult['orderMethods'] as $key => $name): ?>
                            <option value="<?php echo $key;?>"<?php if (is_array($crmOrderMethods) && in_array($key, $crmOrderMethods)) {
                                echo 'selected';
                            } ?>>
                                <?php echo $name;?>
                            </option>
                        <?php endforeach;?>
                    </select>
                </td>
            </tr>
        <?php $tabControl->BeginNextTab(); ?>
            <input type="hidden" name="tab" value="catalog">
            <tr class="option-head">
                <td colspan="2"><b><?php echo GetMessage('INFO_2'); ?></b></td>
            </tr>
        <?php foreach ($arResult['bitrixOrderTypesList'] as $bitrixOrderType): ?>
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
                            <option value="<?php echo $contragentType["ID"]; ?>" <?php if ($optionsContragentType[$bitrixOrderType['ID']] === $contragentType['ID']) {
                                echo 'selected';
                            } ?>>
                                <?php echo $contragentType["NAME"]; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <?php $countProps = 1;
        foreach ($arResult['orderProps'] as $orderProp): ?>
            <?php if ($orderProp['ID'] === 'text'): ?>
                <tr class="heading">
                    <td colspan="2" style="background-color: transparent;">
                        <b>
                            <label>
                                <input class="addr" type="radio" name="address-detail-<?php echo $bitrixOrderType['ID']; ?>" value="0"
                                    <?php
                                    if ($addressOptions[$bitrixOrderType['ID']] === '0') {
                                        echo 'checked';
                                    }
                                    ?>>
                                <?= GetMessage('ADDRESS_SHORT')?>
                            </label>
                            <label>
                                <input class="addr" type="radio" name="address-detail-<?php echo $bitrixOrderType['ID']; ?>" value="1"
                                    <?php
                                    if ($addressOptions[$bitrixOrderType['ID']] === '1') {
                                        echo 'checked';
                                    }
                                    ?>>
                                <?= GetMessage('ADDRESS_FULL')?>
                            </label>
                        </b>
                    </td>
                </tr>
            <?php endif; ?>
            <tr <?php if ($countProps > 4) {
                echo 'class="address-detail-' . $bitrixOrderType['ID'] . '"';
            }
            if (($countProps > 4) && ($addressOptions[$bitrixOrderType['ID']] === 0)) {
                echo 'style="display:none;"';
            } ?>>
                <td width="50%" class="adm-detail-content-cell-l" name="<?php echo $orderProp['ID']; ?>">
                    <?php echo $orderProp['NAME']; ?>
                </td>
                <td width="50%" class="adm-detail-content-cell-r">
                    <select name="order-prop-<?php echo $orderProp['ID'] . '-' . $bitrixOrderType['ID']; ?>" class="typeselect">
                        <option value=""></option>
                        <?php foreach ($arResult['arProp'][$bitrixOrderType['ID']] as $arProp): ?>
                            <option value="<?php echo $arProp['CODE']; ?>" <?php if ($optionsOrderProps[$bitrixOrderType['ID']][$orderProp['ID']] === $arProp['CODE']) {
                                echo 'selected';
                            } ?>>
                                <?php echo $arProp['NAME']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <?php $countProps++; endforeach; ?>
            <tr id="<?php echo 'locationElement-' . $bitrixOrderType['ID']; ?>" hidden="hidden">
                <td class="adm-detail-content-cell-l" width="50%" name="text"><?php echo GetMessage('LOCATION_LABEL'); ?></td>
                <td class="adm-detail-content-cell-r" width="50%">
                    <select class="typeselect">
                        <option value=""></option>
                        <?php foreach ($arResult['locationProp'][$bitrixOrderType['ID']] as $arProp): ?>
                            <option value="<?php echo $arProp['CODE']; ?>"
                                <?php if ($optionsOrderProps[$bitrixOrderType['ID']]['text'] === $arProp['CODE']) {
                                    echo 'selected';
                                } ?>
                            >
                                <?php echo $arProp['NAME'];?>
                            </option>
                        <?php endforeach;?>
                    </select>
                </td>
            </tr>
            <tr id="<?php echo 'textAddressElement-' . $bitrixOrderType['ID']; ?>" hidden="hidden">
                <td class="adm-detail-content-cell-l" width="50%" name="text"><?php echo GetMessage('TEXT_ADDRESS_LABEL'); ?></td>
                <td class="adm-detail-content-cell-r" width="50%">
                    <select class="typeselect">
                        <option value=""></option>
                        <?php foreach ($arResult['arProp'][$bitrixOrderType['ID']] as $arProp): ?>
                            <option value="<?php echo $arProp['CODE']; ?>"
                                <?php if ($optionsOrderProps[$bitrixOrderType['ID']]['text'] === $arProp['CODE']) {
                                    echo 'selected';
                                } ?>
                            >
                                <?php echo $arProp['NAME']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>

            <tr class="heading legal-detail-title-<?php echo $bitrixOrderType['ID']; ?>" <?php if (is_array($optionsLegalDetails[$bitrixOrderType['ID']]) && count($optionsLegalDetails[$bitrixOrderType['ID']]) < 1) {
                echo 'style="display:none"';
            } ?>>
                <td colspan="2" style="background-color: transparent;">
                    <b>
                        <?php echo GetMessage('LEGAL_DETAIL'); ?>
                    </b>
                </td>
            </tr>
            <?php foreach ($arResult['legalDetails'] as $legalDetails): ?>
            <tr class="legal-detail-<?php echo $bitrixOrderType['ID']; ?> <?php foreach ($legalDetails['GROUP'] as $gr) {
                echo $gr . ' ';
            } ?>" <?php if (!in_array($optionsContragentType[$bitrixOrderType['ID']], $legalDetails['GROUP'], true)) {
                echo 'style="display:none"';
            } ?>>
                <td width="50%" class="" name="<?php ?>">
                    <?php echo $legalDetails['NAME']; ?>
                </td>
                <td width="50%" class="">
                    <select name="legal-detail-<?php echo $legalDetails['ID'] . '-' . $bitrixOrderType['ID']; ?>" class="typeselect">
                        <option value=""></option>
                        <?php foreach ($arResult['arProp'][$bitrixOrderType['ID']] as $arProp): ?>
                            <option value="<?php echo $arProp['CODE']; ?>" <?php if ($optionsLegalDetails[$bitrixOrderType['ID']][$legalDetails['ID']] === $arProp['CODE']) {
                                echo 'selected';
                            } ?>>
                                <?php echo $arProp['NAME']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php endforeach; ?>

        <?php $tabControl->BeginNextTab(); ?>
        <?php
        //loyalty program options
        $loyaltyProgramToggle = ConfigProvider::getLoyaltyProgramStatus();
        ?>
            <tr class="heading">
                <td colspan="2" class="option-other-heading">
                    <b>
                        <label>
                            <input class="addr" type="checkbox" id="loyalty_toggle" name="loyalty_toggle" onclick="switchPLStatus();" <?php if ($loyaltyProgramToggle === 'Y') {
                                echo "checked";
                            } ?>>
                            <?php echo GetMessage('LOYALTY_PROGRAM_TOGGLE_MSG'); ?>
                        </label>
                    </b>
                </td>
            </tr>
            <tr>
                <td>
                    <div id="loyalty_main_settings" <?php if ($loyaltyProgramToggle !== 'Y') {
                        echo "hidden";
                    } ?>>
                        <table width="100%">
                            <tr class="heading">
                                <td colspan="2" class="option-other-heading">
                                    <?php echo GetMessage('LP_SALE_ORDER_AJAX_HEAD'); ?>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <div style="text-align: center;">
                                        <h4>
                                            <?= GetMessage('CREATING_AN_ADDITIONAL_TEMPLATE') ?>
                                        </h4>
                                    </div>
                                    <?= sprintf(GetMessage('LP_CUSTOM_TEMP_CREATE_MSG'), 'sale.order.ajax') ?>
                                    <div style="text-align: center;">
                                        <input type="button" onclick="createTemplates('sale.order.ajax')" class="adm-btn-save" value="<?php echo GetMessage('LP_CREATE_TEMPLATE'); ?>"/>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <div style="text-align: center;">
                                        <h4>
                                            <?= GetMessage('REPLACING_THE_STANDARD_TEMPLATE') ?>
                                        </h4>
                                    </div>
                                    <?= sprintf(GetMessage('LP_DEF_TEMP_CREATE_MSG'), 'sale.order.ajax') ?>
                                    <hr>
                                    <?php echo GetMessage('LP_TEMP_CHOICE_MSG'); ?>
                                </td>
                            </tr>
                            <tr>
                                <td width="50%" align="center">
                                    <input type="button" onclick="replaceDefaultTemplates('sale.order.ajax')" class="adm-btn-save" value="<?php echo GetMessage('LP_REPLACE_TEMPLATE'); ?>" />
                                </td>
                                <td width="50%" >
                                    <div id="lp-sale-templates">
                                        <?php
                                        $templates = TemplateRepository::getAllIds();
                                        foreach ($templates as $template) {
                                            ?>
                                            <p><input type="checkbox" name="<?= $template['name']?>" value="<?= $template['name']?>" templateFolder="<?= $template['folder']?>"> <?= $template['name']?> (<?= $template['folder']?>)</p>
                                        <?php } ?>
                                    </div>
                                </td>
                            </tr>
                        </table>
                        <table width="100%">
                            <tr class="heading">
                                <td colspan="2" class="option-other-heading">
                                    <?php echo GetMessage('LP_MAIN_REGISTER_HEAD'); ?>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <div style="text-align: center;">
                                        <h4>
                                            <?=GetMessage('CREATING_AN_ADDITIONAL_TEMPLATE')?>
                                        </h4>
                                    </div>
                                    <?= sprintf(GetMessage('LP_CUSTOM_TEMP_CREATE_MSG'), 'main.register') ?>
                                    <div style="text-align: center;">
                                        <input type="button" onclick="createTemplates('main.register')" class="adm-btn-save" value="<?php echo GetMessage('LP_CREATE_TEMPLATE'); ?>"/>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <div style="text-align: center;">
                                        <h4>
                                            <?= GetMessage('REPLACING_THE_STANDARD_TEMPLATE') ?>
                                        </h4>
                                    </div>
                                    <?= sprintf(GetMessage('LP_DEF_TEMP_CREATE_MSG'), 'main.register') ?>
                                    <hr>
                                    <?php echo GetMessage('LP_TEMP_CHOICE_MSG'); ?>
                                </td>
                            </tr>
                            <tr>
                                <td width="50%" align="center">
                                    <input type="button" onclick="replaceDefaultTemplates('main.register')" class="adm-btn-save" value="<?php echo GetMessage('LP_REPLACE_TEMPLATE'); ?>" />
                                </td>
                                <td width="50%" >
                                    <div id="lp-reg-templates">
                                        <?php
                                        $templates = TemplateRepository::getAllIds();
                                        foreach ($templates as $template) {
                                            ?>
                                            <p><input type="checkbox" name="<?= $template['name']?>" value="<?= $template['name']?>" templateFolder="<?= $template['folder']?>"> <?= $template['name']?> (<?= $template['folder']?>)</p>
                                        <?php } ?>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td style="text-align: center;" colspan="2">

                                    <?php
                                    $isAgreementPersonalProgram = AgreementRepository::getFirstByWhere(
                                        ['ID'],
                                        [
                                            ['CODE', '=', 'AGREEMENT_PERSONAL_DATA_CODE']
                                        ]
                                    );
                                    $isAgreementLoyaltyProgram = AgreementRepository::getFirstByWhere(
                                        ['ID'],
                                        [
                                            ['CODE', '=', 'AGREEMENT_LOYALTY_PROGRAM_CODE']
                                        ]
                                    );
                                    ?>
                                    <h4><?= GetMessage('EDITING_AGREEMENTS')?></h4>
                                    <?php if (isset($isAgreementLoyaltyProgram['ID']) && isset($isAgreementLoyaltyProgram['ID'])) { ?>
                                        <a href="<?= SITE_SERVER_NAME . '/bitrix/admin/agreement_edit.php?ID=' . $isAgreementLoyaltyProgram['ID']?>" target="_blank"><?= GetMessage('AGREEMENT_PROCESSING_PERSONAL_DATA')?></a>
                                        <br>
                                        <a href="<?= SITE_SERVER_NAME . '/bitrix/admin/agreement_edit.php?ID=' . $isAgreementLoyaltyProgram['ID']?>" target="_blank"><?= GetMessage('ACCEPTANCE_TERMS_LOYALTY_PROGRAM')?></a>
                                    <?php } ?>
                                </td>
                            </tr>

                        </table>
                        <table width="100%">
                            <tr class="heading">
                                <td colspan="2" class="option-other-heading">
                                    <?php echo GetMessage('LP_MAIN_BASKET_HEAD'); ?>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <div style="text-align: center;">
                                        <h4>
                                            <?=GetMessage('CREATING_AN_ADDITIONAL_TEMPLATE')?>
                                        </h4>
                                    </div>
                                    <?= sprintf(GetMessage('LP_CUSTOM_TEMP_CREATE_MSG'), 'sale.basket.basket') ?>
                                    <div style="text-align: center;">
                                        <input type="button" onclick="createTemplates('sale.basket.basket')" class="adm-btn-save" value="<?php echo GetMessage('LP_CREATE_TEMPLATE'); ?>"/>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <div style="text-align: center;">
                                        <h4>
                                            <?= GetMessage('REPLACING_THE_STANDARD_TEMPLATE') ?>
                                        </h4>
                                    </div>
                                    <?= sprintf(GetMessage('LP_DEF_TEMP_CREATE_MSG'), 'sale.basket.basket') ?>
                                    <hr>
                                    <?php echo GetMessage('LP_TEMP_CHOICE_MSG'); ?>
                                </td>
                            </tr>
                            <tr>
                                <td width="50%" align="center">
                                    <input type="button" onclick="replaceDefaultTemplates('sale.basket.basket')" class="adm-btn-save" value="<?php echo GetMessage('LP_REPLACE_TEMPLATE'); ?>" />
                                </td>
                                <td width="50%" >
                                    <div id="lp-basket-templates">
                                        <?php
                                        $templates = TemplateRepository::getAllIds();
                                        foreach ($templates as $template) {
                                            ?>
                                            <p><input type="checkbox" name="<?= $template['name']?>" value="<?= $template['name']?>" templateFolder="<?= $template['folder']?>"> <?= $template['name']?> (<?= $template['folder']?>)</p>
                                        <?php } ?>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>

        <?php $tabControl->BeginNextTab(); ?>
        <?php
        $customFieldsToggle = ConfigProvider::getCustomFieldsStatus();
        ?>
            <tr class="">
                <td class="option-head" colspan="2">
                    <p><b><?php echo GetMessage('NOTATION_CUSTOM_FIELDS'); ?></b></p>
                    <p><b><?php echo GetMessage('NOTATION_MATCHED_CUSTOM_FIELDS'); ?></b></p>
                </td>
            </tr>
            <tr class="heading">
                <td colspan="2" class="option-other-heading">
                    <b>
                        <label>
                            <input class="addr" type="checkbox" id="custom_fields_toggle" name="custom_fields_toggle" onclick="switchCustomFieldsStatus();" <?php if ($customFieldsToggle === 'Y') {
                                echo "checked";
                            } ?>>
                            <?php echo GetMessage('CUSTOM_FIELDS_TOGGLE_MSG'); ?>
                        </label>
                    </b>
                </td>
            </tr>
            <tr>
                <td>
                    <div id="custom_fields_settings" <?php if ($customFieldsToggle !== 'Y') {
                        echo "hidden";
                    } ?>>
                        <?php if ($enabledCustom): ?>
                            <br>

                            <table class="adm-list-table">
                                <thead>
                                    <tr class="adm-list-table-header">
                                        <th class="adm-list-table-cell option-head option-other-top option-other-bottom" colspan="4">
                                            <?php echo GetMessage('CUSTOM_FIELDS_ORDER_LABEL');?>
                                        </th>
                                    </tr>
                                </thead>
                                <tfoot>
                                    <tr>
                                        <th class="option-head option-other-top option-other-bottom" colspan="4">
                                            <button class="adm-btn-save" type="button" onclick="createMatched(`Order`)"><?php echo GetMessage('ADD_LABEL'); ?></button>
                                        </th>
                                    </tr>
                                </tfoot>
                                <tbody id="Order_matched">
                                    <?php
                                        $matchedPropsNum = 1;
                                        foreach ($arResult['matchedOrderProps'] as $bitrixProp => $crmField) {?>
                                            <tr class="adm-list-table-row matched-Order" id="matchedOrderFields_<?php echo $matchedPropsNum ?>">
                                                <td class="adm-list-table-cell adm-detail-content-cell-l" colspan="2" width="50%">
                                                    <select
                                                        style="width: 200px;" class="typeselect"
                                                        name="bitrixOrderFields_<?php echo $bitrixProp ?>"
                                                        onchange="changeSelectBitrixValue(this, 'bitrixOrderFields_', 'crmOrderFields_');"
                                                    >
                                                        <option value=""></option>

                                                        <?php foreach ($arResult['bitrixOrdersCustomProp'] as $type => $mass) {?>
                                                            <optgroup label="<?php echo GetMessage($type); ?>">
                                                                <?php foreach ($mass as $code => $prop) {?>
                                                                    <option
                                                                            value="<?php echo $code ?>"
                                                                        <?php if ($bitrixProp === $code) echo 'selected'; ?>
                                                                    >
                                                                        <?php echo $prop ?>
                                                                    </option>
                                                                <?php } ?>
                                                            </optgroup>
                                                        <?php } ?>
                                                    </select>
                                                </td>
                                                <td class="adm-list-table-cell adm-detail-content-cell-r" colspan="2" width="50%">
                                                    &nbsp;&nbsp;&nbsp;&nbsp;
                                                    <select
                                                            style="width: 200px;" class="typeselect"
                                                            name="crmOrderFields_<?php echo $bitrixProp ?>"
                                                            id="crmOrder_<?php echo $crmField?>"
                                                            onchange="changeSelectCrmValue(this, 'crmOrder_')"
                                                    >
                                                        <option value=""></option>
                                                        <?php foreach ($arResult['crmCustomOrderFields'] as $type => $mass) {?>
                                                            <optgroup label="<?php echo GetMessage($type); ?>">
                                                                <?php foreach ($mass as $crmProp) {?>
                                                                    <option
                                                                            value="<?php echo $crmProp['code'] ?>"
                                                                        <?php if ($crmField === $crmProp['code']) echo 'selected'; ?>
                                                                    >
                                                                        <?php echo $crmProp['name'] ?>
                                                                    </option>
                                                                <?php } ?>
                                                            </optgroup>
                                                        <?php } ?>
                                                    </select>
                                                    &nbsp;
                                                    <a onclick="deleteMatched(this)" style="cursor: pointer"><?php echo GetMessage('DELETE_MATCHED'); ?></a>
                                                </td>
                                            </tr>
                                    <?php $matchedPropsNum++; }?>
                                </tbody>
                            </table>

                            <br>

                            <table class="adm-list-table">
                                <thead>
                                <tr class="adm-list-table-header">
                                    <th class="adm-list-table-cell option-head option-other-top option-other-bottom" colspan="4">
                                        <?php echo GetMessage('CUSTOM_FIELDS_USER_LABEL');?>
                                    </th>
                                </tr>
                                </thead>
                                <tfoot>
                                <tr>
                                    <th class="option-head option-other-top option-other-bottom" colspan="4">
                                        <button class="adm-btn-save" type="button" onclick="createMatched(`User`)"><?php echo GetMessage('ADD_LABEL'); ?></button>
                                    </th>
                                </tr>
                                </tfoot>
                                <tbody id="User_matched">
                                <?php
                                $matchedFieldsNum = 1;
                                foreach ($arResult['matchedUserFields'] as $bitrixProp => $crmField) {?>
                                    <tr class="adm-list-table-row matched-User" id="matchedUserFields_<?php echo $matchedFieldsNum ?>">
                                        <td class="adm-list-table-cell adm-detail-content-cell-l" colspan="2" width="50%">
                                            <select
                                                    style="width: 200px;" class="typeselect"
                                                    name="bitrixUserFields_<?php echo $bitrixProp ?>"
                                                    onchange="changeSelectBitrixValue(this, 'bitrixUserFields_', 'crmUserFields_');"
                                            >
                                                <option value=""></option>
                                                <?php foreach ($arResult['bitrixCustomUserFields'] as $type => $mass) {?>
                                                    <optgroup label="<?php echo GetMessage($type); ?>">
                                                        <?php foreach ($mass as $code => $prop) {?>
                                                            <option
                                                                    value="<?php echo $code ?>"
                                                                <?php if ($bitrixProp === $code) echo 'selected'; ?>
                                                            >
                                                                <?php echo $prop ?>
                                                            </option>
                                                        <?php } ?>
                                                    </optgroup>
                                                <?php } ?>
                                            </select>
                                        </td>
                                        <td class="adm-list-table-cell adm-detail-content-cell-r" colspan="2" width="50%">
                                            &nbsp;&nbsp;&nbsp;&nbsp;
                                            <select
                                                    style="width: 200px;" class="typeselect"
                                                    name="crmUserFields_<?php echo $bitrixProp ?>"
                                                    id="crmClient_<?php echo $crmField?>"
                                                    onchange="changeSelectCrmValue(this, 'crmClient_')"
                                            >
                                                <option value=""></option>
                                                <?php foreach ($arResult['crmCustomUserFields'] as $type => $mass) {?>
                                                    <optgroup label="<?php echo GetMessage($type); ?>">
                                                        <?php foreach ($mass as $crmProp) {?>
                                                            <option
                                                                    value="<?php echo $crmProp['code'] ?>"
                                                                <?php if ($crmField === $crmProp['code']) echo 'selected'; ?>
                                                            >
                                                                <?php echo $crmProp['name'] ?>
                                                            </option>
                                                        <?php } ?>
                                                    </optgroup>
                                                <?php } ?>
                                            </select>
                                            &nbsp;
                                            <a onclick="deleteMatched(this)" style="cursor: pointer"><?php echo GetMessage('DELETE_MATCHED'); ?></a>
                                        </td>
                                    </tr>
                                    <?php $matchedFieldsNum++; }?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <tr class="">
                                <td class="option-head" colspan="2">
                                    <div class="adm-info-message-wrap adm-info-message-red">
                                        <div class="adm-info-message">
                                            <div class="adm-info-message-title"><a target="_blank" href="https://docs.retailcrm.ru/Users/Integration/SiteModules/1CBitrix/CreatingOnlineStore1CBitrix"><?php echo GetMessage('ERR_403_CUSTOM'); ?></a></div>

                                            <div class="adm-info-message-icon"></div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>

        <tr id="OrderMatchedFieldsBlank" hidden="hidden">
            <td class="adm-list-table-cell adm-detail-content-cell-l" colspan="2" width="50%">
                <select
                        style="width: 200px;" class="typeselect"
                        name="bitrixOrderFields"
                        onchange="changeSelectBitrixValue(this, 'bitrixOrderFields_', 'crmOrderFields_');"
                >
                    <option value="" selected></option>
                    <?php foreach ($arResult['bitrixOrdersCustomProp'] as $type => $mass) {?>
                        <optgroup label="<?php echo GetMessage($type); ?>">
                            <?php foreach ($mass as $code => $prop) {?>
                                <option value="<?php echo $code ?>">
                                    <?php echo $prop ?>
                                </option>
                            <?php } ?>
                        </optgroup>
                    <?php } ?>

                </select>
            </td>
            <td class="adm-list-table-cell adm-detail-content-cell-r" colspan="2" width="50%">
                &nbsp;&nbsp;&nbsp;&nbsp;
                <select
                        style="width: 200px;" class="typeselect"
                        name="crmOrderFields"
                        onchange="changeSelectCrmValue(this, 'crmOrder_')"
                >
                    <option value="" selected></option>
                    <?php foreach ($arResult['crmCustomOrderFields'] as $type => $mass) {?>
                        <optgroup label="<?php echo GetMessage($type); ?>">
                            <?php foreach ($mass as $crmProp) {?>
                                <option value="<?php echo $crmProp['code'] ?>">
                                    <?php echo $crmProp['name'] ?>
                                </option>
                            <?php } ?>
                        </optgroup>
                    <?php } ?>
                </select>
                &nbsp;
                <a onclick="deleteMatched(this)" style="cursor: pointer"><?php echo GetMessage('DELETE_MATCHED'); ?></a>
            </td>
        </tr>

        <tr id="UserMatchedFieldsBlank" hidden="hidden">
            <td class="adm-list-table-cell adm-detail-content-cell-l" colspan="2" width="50%">
                <select
                        style="width: 200px;" class="typeselect"
                        name="bitrixUserFields"
                        onchange="changeSelectBitrixValue(this, 'bitrixUserFields_', 'crmUserFields_');"
                >
                    <option value="" selected></option>
                    <?php foreach ($arResult['bitrixCustomUserFields'] as $type => $mass) {?>
                        <optgroup label="<?php echo GetMessage($type); ?>">
                            <?php foreach ($mass as $code => $prop) {?>
                                <option value="<?php echo $code ?>">
                                    <?php echo $prop ?>
                                </option>
                            <?php } ?>
                        </optgroup>
                    <?php } ?>
                </select>
            </td>
            <td class="adm-list-table-cell adm-detail-content-cell-r" colspan="2" width="50%">
                &nbsp;&nbsp;&nbsp;&nbsp;
                <select
                        style="width: 200px;" class="typeselect"
                        name="crmUserFields"
                        onchange="changeSelectCrmValue(this, 'crmClient_')"
                >
                    <option value="" selected></option>
                    <?php foreach ($arResult['crmCustomUserFields'] as $type => $mass) {?>
                        <optgroup label="<?php echo GetMessage($type); ?>">
                            <?php foreach ($mass as $crmProp) {?>
                                <option value="<?php echo $crmProp['code'] ?>">
                                    <?php echo $crmProp['name'] ?>
                                </option>
                            <?php } ?>
                        </optgroup>
                    <?php } ?>
                </select>
                &nbsp;
                <a onclick="deleteMatched(this)" style="cursor: pointer"><?php echo GetMessage('DELETE_MATCHED'); ?></a>
            </td>
        </tr>

        <?php // Manual orders upload. ?>
        <?php $tabControl->BeginNextTab(); ?>
            <style type="text/css">
				.install-load-label {
					color: #000;
					margin-bottom: 15px;
				}

				.install-progress-bar-outer {
					height: 32px;
					border: 1px solid;
					border-color: #9ba6a8 #b1bbbe #bbc5c9 #b1bbbe;
					-webkit-box-shadow: 1px 1px 0 #fff, inset 0 2px 2px #c0cbce;
					box-shadow: 1px 1px 0 #fff, inset 0 2px 2px #c0cbce;
					background-color: #cdd8da;
					background-image: -webkit-linear-gradient(top, #cdd8da, #c3ced1);
					background-image: -moz-linear-gradient(top, #cdd8da, #c3ced1);
					background-image: -ms-linear-gradient(top, #cdd8da, #c3ced1);
					background-image: -o-linear-gradient(top, #cdd8da, #c3ced1);
					background-image: linear-gradient(top, #ced9db, #c3ced1);
					border-radius: 2px;
					text-align: center;
					color: #6a808e;
					text-shadow: 0 1px rgba(255, 255, 255, 0.85);
					font-size: 18px;
					line-height: 35px;
					font-weight: bold;
				}

				.install-progress-bar-alignment {
					height: 28px;
					margin: 0;
					position: relative;
				}

				.install-progress-bar-inner {
					height: 28px;
					border-radius: 2px;
					border-top: solid 1px #52b9df;
					background-color: #2396ce;
					background-image: -webkit-linear-gradient(top, #27a8d7, #2396ce, #1c79c0);
					background-image: -moz-linear-gradient(top, #27a8d7, #2396ce, #1c79c0);
					background-image: -ms-linear-gradient(top, #27a8d7, #2396ce, #1c79c0);
					background-image: -o-linear-gradient(top, #27a8d7, #2396ce, #1c79c0);
					background-image: linear-gradient(top, #27a8d7, #2396ce, #1c79c0);
					position: absolute;
					overflow: hidden;
					top: 1px;
					left: 0;
				}

				.install-progress-bar-inner-text {
					color: #fff;
					text-shadow: 0 1px rgba(0, 0, 0, 0.2);
					font-size: 18px;
					line-height: 32px;
					font-weight: bold;
					text-align: center;
					position: absolute;
					left: -2px;
					top: -2px;
				}

				.order-upload-button {
					padding: 1px 13px 2px;
					height: 28px;
				}

				.order-upload-button div {
					float: right;
					position: relative;
					visible: none;
				}
            </style>

            <script type="text/javascript">
                $(document).ready(function() {
                    generateEmptyMatched();

                    $('#percent').width($('.install-progress-bar-outer').width());

                    $(window).resize(function() { // strechin progress bar
                        $('#percent').width($('.install-progress-bar-outer').width());
                    });

                    // orderUpload function
                    function orderUpload() {

                        var handlerUrl = $('#upload-orders').attr('action');
                        var step       = $('input[name="step"]').val();
                        var orders     = $('input[name="orders"]').val();
                        var data       = 'orders=' + orders + '&step=' + step + '&ajax=2';

                        // ajax request
                        $.ajax({
                            type:     'POST',
                            url:      handlerUrl,
                            data:     data,
                            dataType: 'json',
                            success:  function(response) {
                                $('input[name="step"]').val(response.step);
                                if (response.step === 'end') {
                                    $('input[name="step"]').val(0);
                                    BX.closeWait();
                                } else {
                                    orderUpload();
                                }
                                $('#indicator').css('width', response.percent + '%');
                                $('#percent').html(response.percent + '%');
                                $('#percent2').html(response.percent + '%');

                            },
                            error:    function() {
                                BX.closeWait();
                                $('#status').text('<?php echo GetMessage('MESS_4'); ?>');

                                alert('<?php echo GetMessage('MESS_5'); ?>');
                            }
                        });
                    }

                    $('input[name="start"]').on('click', function() {
                        BX.showWait();
                        $('#indicator').css('width', 0);
                        $('#percent2').html('0%');
                        $('#percent').css('width', '100%');

                        orderUpload();

                        return false;
                    });

                    $('input[name="send-pickup-point-address"]').change(
                        function(){
                            if ($(this).is(':checked')) {
                                alert('<?php echo GetMessage('SEND_PICKUP_POINT_ADDRESS_WARNING'); ?>');
                            }
                    });

                    function customerFixDate() {
                        var handleUrl = $('#fix-upload_customer').attr('action');
                        var data = 'ajax=3';

                        $.ajax({
                            type: 'POST',
                            url: handleUrl,
                            data: data,
                            dataType: 'json',
                            success: function () {
                                $('#block-fix-customer-date').html("<p><b><?php echo GetMessage('FIX_UPLOAD_CUSTOMER_AFTER_SUBMIT'); ?></b></p>");
                            },
                            error: function () {
                                $('#block-fix-customer-date').html("<p><b><?php echo GetMessage('FIX_UPLOAD_CUSTOMER_AFTER_SUBMIT_ERROR'); ?></b></p>");
                            }
                        })
                    }

                    $('input[name="start-fix-date-customer"]').on('click', function () {
                        customerFixDate();
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
                <div class="install-load-block" id="result">
                    <div class="install-load-label" id="status"><?php echo GetMessage('ORDER_UPLOAD_INFO'); ?></div>
                    <div class="install-progress-bar-outer">
                        <div class="install-progress-bar-alignment" style="width: 100%;">
                            <div class="install-progress-bar-inner" id="indicator" style="width: 0%;">
                                <div class="install-progress-bar-inner-text" style="width: 100%;" id="percent">0%</div>
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

        <?php $tabControl->BeginNextTab(); ?>
            <input type="hidden" name="tab" value="catalog">
            <tr class="heading">
                <td colspan="2" class="option-other-bottom"><b><?php echo GetMessage('ORDERS_OPTIONS'); ?></b></td>
            </tr>
            <tr>
                <td colspan="2" class="option-head option-other-top option-other-bottom">
                    <b>
                        <label><input class="addr" type="checkbox" name="track-number" value="Y" <?php if ($optionsOrderTrackNumber === 'Y') {
                                echo "checked";
                            } ?>> <?php echo GetMessage('ORDER_TRACK_NUMBER'); ?></label>
                    </b>
                </td>
            </tr>
            <tr>
                <td colspan="2" class="option-head option-other-top option-other-bottom">
                    <b>
                        <label><input class="addr" type="checkbox" name="order-vat" value="Y" <?php if ($optionsOrderVat === 'Y') {
                                echo "checked";
                            } ?>> <?php echo GetMessage('ORDER_VAT'); ?></label>
                    </b>
                </td>
            </tr>
            <tr>
                <td colspan="2" class="option-head option-other-top option-other-bottom">
                    <b>
                        <label>
                            <input class="addr" type="checkbox" name="send-pickup-point-address" value="Y" <?php if($sendPickupPointAddress === 'Y') {echo "checked";} ?>> <?php echo GetMessage('SEND_PICKUP_POINT_ADDRESS'); ?>
                        </label>
                    </b>
                </td>
            </tr>

        <?php if($sendPickupPointAddress === 'Y') {
            $warningMessage = GetMessage('SEND_PICKUP_POINT_ADDRESS_WARNING');

            echo sprintf('<tr>
                <td colspan="2" class="option-head option-other-top option-other-bottom">
                    <b>
                        <label style="color: darkorange">
                            %s
                        </label>
                    </b>
                </td>
            </tr>', $warningMessage);
        } ?>



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
                        <label>
                            <input class="addr" type="checkbox" name="order_dimensions" value="Y" <?php if ($optionsOrderDimensions === 'Y') {
                                echo "checked";
                            } ?>> <?php echo GetMessage('ORDER_DIMENSIONS'); ?>
                        </label>
                    </b>
                </td>
            </tr>
            <tr>
                <td colspan="2" class="option-head option-other-top option-other-bottom">
                    <b>
                        <label><input class="addr" type="checkbox" name="order-numbers" value="Y" <?php if ($optionsOrderNumbers === 'Y') {
                                echo "checked";
                            } ?>> <?php echo GetMessage('ORDER_NUMBERS'); ?></label>
                    </b>
                </td>
            </tr>
            <tr>
                <td colspan="2" class="option-head option-other-top option-other-bottom">
                    <b>
                        <label><input class="addr" type="radio" name="order-discharge" value="1" <?php if ($optionsDischarge === 1) {
                                echo "checked";
                            } ?>><?php echo GetMessage('DISCHARGE_EVENTS'); ?></label>
                        <label><input class="addr" type="radio" name="order-discharge" value="0" <?php if ($optionsDischarge === 0) {
                                echo "checked";
                            } ?>><?php echo GetMessage('DISCHARGE_AGENT'); ?></label>
                    </b>
                </td>
            </tr>
            <tr>
                <td colspan="2" class="option-head option-other-top option-other-bottom">
                    <b><?php echo GetMessage('COUPON_CUSTOM_FIELD'); ?></b>
                    <br><br>
                    <select name="crm-coupon-field" class="typeselect">
                        <?php foreach ($customFields as $customField) : ?>
                            <option value="<?php echo $customField['code']; ?>" <?php if ($customField['code'] === $crmCouponFieldOption) {
                                echo 'selected';
                            } ?>>
                                <?php echo $customField['name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>

            <?php //По умолчанию используем апи V5. При добавлении в будущем V6 выводить пользователю данный блок ?>
            <tr class="heading" hidden="hidden">
                <td colspan="2" class="option-other-heading"><b><?php echo GetMessage('CRM_API_VERSION'); ?></b></td>
            </tr>
            <tr hidden="hidden">
                <td colspan="2" class="option-head option-other-top option-other-bottom">
                    <select name="api_version" class="typeselect">
                        <?php for ($v = 5; $v <= 5; $v++) {
                            $ver = 'v' . $v; ?>
                            <option value="<?php echo $ver; ?>" <?php if ($ver === $version) {
                                echo 'selected';
                            } ?>>
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
                            <option value="<?php echo $currencyCode; ?>" <?php if ($currencyCode === $currencyOption) {
                                echo 'selected';
                            } ?>>
                                <?php echo $currencyName; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        <?php if ($optionInventotiesUpload === 'Y' || count($arResult['bitrixStoresExportList']) > 0) : ?>
            <tr class="heading inventories-batton">
                <td colspan="2" class="option-other-heading">
                    <b>
                        <label><input class="addr" type="checkbox" name="inventories-upload" value="Y" <?php if ($optionInventotiesUpload === 'Y') {
                                echo "checked";
                            } ?>><?php echo GetMessage('INVENTORIES_UPLOAD'); ?></label>
                    </b>
                </td>
            </tr>
            <tr class="inventories" <?php if ($optionInventotiesUpload !== 'Y') {
                echo 'style="display: none;"';
            } ?>>
                <td colspan="2" class="option-head option-other-top option-other-bottom">
                    <b><label><?php echo GetMessage('INVENTORIES'); ?></label></b>
                </td>
            </tr>
            <?php foreach ($arResult['bitrixStoresExportList'] as $catalogExportStore): ?>
            <tr class="inventories" <?php if ($optionInventotiesUpload !== 'Y') {
                echo 'style="display: none;"';
            } ?>>
                <td width="50%" class="adm-detail-content-cell-l"><?php echo $catalogExportStore['TITLE'] ?></td>
                <td width="50%" class="adm-detail-content-cell-r">
                    <select class="typeselect" name="stores-export-<?php echo $catalogExportStore['ID'] ?>">
                        <option value=""></option>
                        <?php foreach ($arResult['inventoriesList'] as $inventoriesList): ?>
                            <?php if ($inventoriesList['active'] === true): ?>
                                <option value="<?php echo $inventoriesList['code'] ?>" <?php if ($optionStores[$catalogExportStore['ID']] === $inventoriesList['code']) {
                                    echo 'selected="selected"';
                                } ?>><?php echo $inventoriesList['name'] ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        <?php endforeach; ?>
            <tr class="inventories" <?php if ($optionInventotiesUpload !== 'Y') {
                echo 'style="display: none;"';
            } ?>>
                <td colspan="2" class="option-head option-other-top option-other-bottom">
                    <b>
                        <label><?php echo GetMessage('SHOPS_INVENTORIES_UPLOAD'); ?></label>
                    </b>
                </td>
            </tr>
            <?php foreach ($arResult['sitesList'] as $sitesList): ?>
            <tr class="inventories" align="center" <?php if ($optionInventotiesUpload !== 'Y') {
                echo 'style="display: none;"';
            } ?>>
                <td colspan="2" class="option-other-center">
                    <label><input class="addr" type="checkbox" name="shops-exoprt-<?= $sitesList['code']; ?>" value="<?= $sitesList['code']?>" <?php if (is_array($optionShops) && in_array($sitesList['code'], $optionShops)) {
                            echo "checked";
                        } ?>> <?php echo $sitesList['name'] . ' (' . $sitesList['code'] . ')'; ?>
                    </label>
                </td>
            </tr>
        <?php endforeach; ?>
            <tr class="inventories" <?php if ($optionInventotiesUpload !== 'Y') {
                echo 'style="display: none;"';
            } ?>>
                <td colspan="2" class="option-head option-other-top option-other-bottom">
                    <b>
                        <label><?php echo GetMessage('IBLOCKS_UPLOAD'); ?></label>
                    </b>
                </td>
            </tr>
            <?php foreach ($arResult['bitrixIblocksExportList'] as $catalogExportIblock) : ?>
            <tr class="inventories" align="center" <?php if ($optionInventotiesUpload !== 'Y') {
                echo 'style="display: none;"';
            } ?>>
                <td colspan="2" class="option-other-center">
                    <label><input class="addr" type="checkbox" name="iblocks-stores-<? echo $catalogExportIblock['ID']; ?>" value="Y" <?php if (is_array($optionIblocksInventories) && in_array($catalogExportIblock['ID'], $optionIblocksInventories)) {
                            echo "checked";
                        } ?>> <?php echo '[' . $catalogExportIblock['CODE'] . '] ' . $catalogExportIblock['NAME'] . ' (' . $catalogExportIblock['LID'] . ')'; ?></label>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php endif; ?>
        <?php if ($optionPricesUpload === 'Y' || count($arResult['bitrixPricesExportList']) > 0) : ?>
            <tr class="heading prices-batton">
                <td colspan="2" class="option-other-heading">
                    <b>
                        <label><input class="addr" type="checkbox" name="prices-upload" value="Y" <?php if ($optionPricesUpload === 'Y') {
                                echo "checked";
                            } ?>><?php echo GetMessage('PRICES_UPLOAD'); ?></label>
                    </b>
                </td>
            </tr>
            <tr class="prices" <?php if ($optionPricesUpload !== 'Y') {
                echo 'style="display: none;"';
            } ?>>
                <td colspan="2" class="option-head option-other-top option-other-bottom">
                    <b>
                        <label><?php echo GetMessage('PRICE_TYPES'); ?></label>
                    </b>
                </td>
            </tr>
            <?php foreach ($arResult['bitrixPricesExportList'] as $catalogExportPrice) : ?>
            <tr class="prices" <?php if ($optionPricesUpload !== 'Y') {
                echo 'style="display: none;"';
            } ?>>
                <td width="50%" class="adm-detail-content-cell-l"><?php echo $catalogExportPrice['NAME_LANG'] . ' (' . $catalogExportPrice['NAME'] . ')'; ?></td>
                <td width="50%" class="adm-detail-content-cell-r">
                    <select class="typeselect" name="price-type-export-<?php echo $catalogExportPrice['ID']; ?>">
                        <option value=""></option>
                        <?php foreach ($arResult['priceTypeList'] as $priceTypeList): ?>
                            <?php if ($priceTypeList['active'] === true): ?>
                                <option value="<?php echo $priceTypeList['code'] ?>" <?php if ($optionPrices[$catalogExportPrice['ID']] === $priceTypeList['code']) {
                                    echo 'selected="selected"';
                                } ?>><?php echo $priceTypeList['name'] ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        <?php endforeach; ?>
            <tr class="prices" <?php if ($optionPricesUpload !== 'Y') {
                echo 'style="display: none;"';
            } ?>>
                <td colspan="2" class="option-head option-other-top option-other-bottom">
                    <b>
                        <label><?php echo GetMessage('SHOPS_PRICES_UPLOAD'); ?></label>
                    </b>
                </td>
            </tr>
            <?php foreach ($arResult['sitesList'] as $sitesList): ?>
            <tr class="prices" align="center" <?php if ($optionPricesUpload !== 'Y') {
                echo 'style="display: none;"';
            } ?>>
                <td colspan="2" class="option-other-center">
                    <label><input class="addr" type="checkbox" name="shops-price-<? echo $sitesList['code']; ?>" value="<? echo $sitesList['code']; ?>" <?php if (is_array($optionPriceShops) && in_array($sitesList['code'], $optionPriceShops)) {
                            echo "checked";
                        } ?>> <?php echo $sitesList['name'] . ' (' . $sitesList['code'] . ')'; ?>
                    </label>
                </td>
            </tr>
        <?php endforeach; ?>
            <tr class="prices" <?php if ($optionPricesUpload !== 'Y') {
                echo 'style="display: none;"';
            } ?>>
                <td colspan="2" class="option-head option-other-top option-other-bottom">
                    <b>
                        <label><?php echo GetMessage('IBLOCKS_UPLOAD'); ?></label>
                    </b>
                </td>
            </tr>
            <?php foreach ($arResult['bitrixIblocksExportList'] as $catalogExportIblock) : ?>
            <tr class="prices" align="center" <?php if ($optionPricesUpload !== 'Y') {
                echo 'style="display: none;"';
            } ?>>
                <td colspan="2" class="option-other-center">
                    <label><input class="addr" type="checkbox" name="iblocks-prices-<? echo $catalogExportIblock['ID']; ?>" value="Y" <?php if (is_array($optionIblocksPrices) && in_array($catalogExportIblock['ID'], $optionIblocksPrices)) {
                            echo "checked";
                        } ?>> <?php echo '[' . $catalogExportIblock['CODE'] . '] ' . $catalogExportIblock['NAME'] . ' (' . $catalogExportIblock['LID'] . ')'; ?></label>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php endif; ?>

            <tr class="heading r-coll-button">
                <td colspan="2" class="option-other-heading">
                    <b>
                        <label><input class="addr" type="checkbox" name="collector" value="Y" <?php if ($optionCollector === 'Y') {
                                echo "checked";
                            } ?>><?php echo GetMessage('DEMON_COLLECTOR'); ?></label>
                    </b>
                </td>
            </tr>
            <tr class="r-coll" <?php if ($optionCollector !== 'Y') {
                echo 'style="display: none;"';
            } ?>>
                <td class="option-head" colspan="2">
                    <b><?php echo GetMessage('ICRM_SITES'); ?></b>
                </td>
            </tr>
        <?php foreach ($arResult['arSites'] as $sitesList): ?>
            <tr class="r-coll" <?php if ($optionCollector !== 'Y') {
                echo 'style="display: none;"';
            } ?>>
                <td class="adm-detail-content-cell-l" width="50%"><?php echo GetMessage('DEMON_KEY'); ?> <?php echo $sitesList['NAME']; ?> (<?php echo $sitesList['LID']; ?>)</td>
                <td class="adm-detail-content-cell-r" width="50%">
                    <label>
                        <input name="collector-id-<? echo $sitesList['LID']; ?>" value="<?php echo $optionCollectorKeys[$sitesList['LID']]; ?>" type="text">
                    </label>
                </td>
            </tr>
        <?php endforeach; ?>
            <tr class="heading r-ua-button">
                <td colspan="2" class="option-other-heading">
                    <b>
                        <label><input class="addr" type="checkbox" name="ua-integration" value="Y" <?php if ($optionUa === 'Y') {
                                echo "checked";
                            } ?>><?php echo GetMessage('UNIVERSAL_ANALYTICS'); ?></label>
                    </b>
                </td>
            </tr>
        <?php foreach ($arResult['arSites'] as $sitesList): ?>
            <tr class="r-ua" <?php if ($optionUa !== 'Y') {
                echo 'style="display: none;"';
            } ?>>
                <td class="option-head" colspan="2">
                    <b><?php echo $sitesList['NAME']; ?> (<?php echo $sitesList['LID']; ?>)</b>
                </td>
            </tr>
            <tr class="r-ua" <?php if ($optionUa !== 'Y') {
                echo 'style="display: none;"';
            } ?>>
                <td class="adm-detail-content-cell-l" width="50%"><?php echo GetMessage('ID_UA'); ?></td>
                <td class="adm-detail-content-cell-r" width="50%">
                    <input name="ua-id-<? echo $sitesList['LID']; ?>" value="<?php echo $optionUaKeys[$sitesList['LID']]['ID']; ?>" type="text">
                </td>
            </tr>
            <tr class="r-ua" <?php if ($optionUa !== 'Y') {
                echo 'style="display: none;"';
            } ?>>
                <td class="adm-detail-content-cell-l" width="50%"><?php echo GetMessage('INDEX_UA'); ?></td>
                <td class="adm-detail-content-cell-r" width="50%">
                    <input name="ua-index-<? echo $sitesList['LID']; ?>" value="<?php echo $optionUaKeys[$sitesList['LID']]['INDEX']; ?>" type="text">
                </td>
            </tr>
        <?php endforeach; ?>

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

            <tr class="r-dc" <?php if ($optionDiscRound !== 'Y') {
                echo 'style="display: none;"';
            } ?>>
                <td class="option-head" colspan="2">
                    <b><?php echo GetMessage('ROUND_LABEL'); ?></b>
                </td>
            </tr>

            <tr class="heading r-ac-button">
                <td colspan="2" class="option-other-heading">
                    <b>
                        <label><input class="addr" type="checkbox" name="cart" value="Y" <?php if ($optionCart === 'Y') echo "checked"; ?>><?php echo GetMessage('CART'); ?></label>
                    </b>
                </td>
            </tr>

            <tr class="r-ac" <?php if ($optionCart !== 'Y') {
                echo 'style="display: none;"';
            } ?>>
                <td class="option-head" colspan="2">
                    <b><?php echo GetMessage('CART_DESCRIPTION'); ?></b>
                </td>
            </tr>

            <tr class="heading r-cc-button">
                <td colspan="2" class="option-other-heading">
                    <b>
                        <label><input class="addr" type="checkbox" name="corp-client" value="Y" <?php if ($optionCorpClient === 'Y') {
                                echo "checked";
                            } ?>><?php echo GetMessage('CORP_CLIENTE'); ?></label>
                    </b>
                </td>
            </tr>

            <tr class="r-cc" <?php if ($optionCorpClient !== 'Y') {
                echo 'style="display: none;"';
            } ?>>
                <td width="50%" class="" name="<?php ?>">
                    <?php echo GetMessage('CORP_NAME'); ?>
                </td>
                <td width="50%" class="">
                    <select name="nickName-corporate" class="typeselect">
                        <option value=""></option>
                        <?php foreach ($arResult['arProp'][$bitrixOrderType['ID']] as $arProp): ?>
                            <option value="<?php echo $arProp['CODE']; ?>" <?php if ($optionsCorpComName === $arProp['CODE']) {
                                echo 'selected';
                            } ?>>
                                <?php echo $arProp['NAME']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>

            <tr class="r-cc" <?php if ($optionCorpClient !== 'Y') {
                echo 'style="display: none;"';
            } ?>>
                <td width="50%" class="" name="<?php ?>">
                    <?php echo GetMessage('CORP_ADRESS'); ?>
                </td>
                <td width="50%" class="">
                    <select name="adres-corporate" class="typeselect">
                        <option value=""></option>
                        <?php foreach ($arResult['arProp'][$bitrixOrderType['ID']] as $arProp): ?>
                            <option value="<?php echo $arProp['CODE']; ?>" <?php if ($optionsCorpAdres === $arProp['CODE']) {
                                echo 'selected';
                            } ?>>
                                <?php echo $arProp['NAME']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr class="r-cc" <?php if ($optionCorpClient !== 'Y') {
                echo 'style="display: none;"';
            } ?>>
                <td colspan="2" class="option-head option-other-top option-other-bottom">
                    <b>
                        <label><?php echo GetMessage('CORP_LABEL'); ?></label>
                    </b>
                </td>
            </tr>

            <tr class="r-cc" <?php if ($optionCorpClient !== 'Y') {
                echo 'style="display: none;"';
            } ?>>
                <td width="50%" class="" name="<?php ?>" align="center">
                    <?php foreach ($arResult['sitesList'] as $sitesList): ?>
                <td colspan="2" class="option-other-center">
                    <label><input class="addr" type="checkbox" name="shops-corporate-<? echo $sitesList['code']; ?>" value="<? echo $sitesList['code']; ?>" <?php if (is_array($optionCorpShops) && in_array($sitesList['code'], $optionCorpShops)) {
                            echo "checked";
                        } ?>> <?php echo $sitesList['name'] . ' (' . $sitesList['code'] . ')'; ?>
                    </label>
                </td>
                <?php endforeach; ?>
                </td>
            </tr>

            <tr class="heading">
                <td colspan="2" class="option-other-heading">
                    <b>
                        <label><input class="addr" type="checkbox" name="shipment_deducted" value="Y" <?php if ($optionShipmentDeducted === 'Y') {
                                echo "checked";
                            } ?>><?php echo GetMessage('CHANGE_SHIPMENT_STATUS_FROM_CRM'); ?></label>
                    </b>
                </td>
            </tr>
        <?php endif; ?>

        <tr class="heading">
            <td colspan="2" class="option-other-bottom"><b><?php echo GetMessage('ACTIVITY_SETTINGS'); ?></b></td>
        </tr>
        <tr>
            <td colspan="2" class="option-head option-other-top option-other-bottom">
                <b>
                    <label><input class="addr" type="checkbox" name="module-deactivate" value="Y" <?php if ($moduleDeactivate === 'Y') {
                            echo "checked";
                        } ?>> <?php echo GetMessage('DEACTIVATE_MODULE'); ?></label>
                </b>
            </td>
        </tr>


        <?php if ($optionsFixDateCustomer !== 'Y'): ?>
            <tr class="heading">
                <td colspan="2" class="option-other-bottom"><b><?php echo GetMessage('FIX_UPLOAD_CUSTOMER_HEADER'); ?></b></td>
            </tr>
            <tr>
                <td id="block-fix-customer-date" colspan="2" class="option-head option-other-top option-other-bottom">
                    <p><b><?php echo GetMessage('FIX_UPLOAD_CUSTOMER_INFO'); ?></b></p>
                    <b>
                        <label><input type="button" name="start-fix-date-customer" value="<?php echo GetMessage('FIX_UPLOAD_CUSTOMER_BUTTON_LABEL'); ?>" class="adm-btn-save"></label>
                    </b>
                </td>
            </tr>
        <?php endif;?>

        <?php $tabControl->Buttons(); ?>
        <input type="hidden" name="Update" value="Y"/>
        <input type="submit" title="<?php echo GetMessage('ICRM_OPTIONS_SUBMIT_TITLE'); ?>" value="<?php echo GetMessage('ICRM_OPTIONS_SUBMIT_VALUE'); ?>" name="btn-update" class="adm-btn-save"/>
        <?php $tabControl->End(); ?>
    </form>
<?php } ?>
