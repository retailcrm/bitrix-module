<?php

/**
 * Module Install/Uninstall script
 * Module name: intaro.intarocrm
 * Class name:  intaro_intarocrm
 */
global $MESS;
IncludeModuleLangFile(__FILE__);
if (class_exists('intaro_intarocrm'))
    return;

class intaro_intarocrm extends CModule {

    var $MODULE_ID = 'intaro.intarocrm';
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    var $MODULE_GROUP_RIGHTS = 'N';
    var $PARTNER_NAME;
    var $PARTNER_URI;
    var $INTARO_CRM_API;
    var $INTARO_CRM_EXPORT = 'intarocrm';
    var $CRM_API_HOST_OPTION = 'api_host';
    var $CRM_API_KEY_OPTION = 'api_key';
    var $CRM_SITES_LIST= 'sites_list';
    var $CRM_ORDER_TYPES_ARR = 'order_types_arr';
    var $CRM_DELIVERY_TYPES_ARR = 'deliv_types_arr';
    var $CRM_DELIVERY_SERVICES_ARR = 'deliv_services_arr';
    var $CRM_PAYMENT_TYPES = 'pay_types_arr';
    var $CRM_PAYMENT_STATUSES = 'pay_statuses_arr';
    var $CRM_PAYMENT = 'payment_arr'; //order payment Y/N
    var $CRM_ORDER_LAST_ID = 'order_last_id';
    var $CRM_ORDER_PROPS = 'order_props';
    var $CRM_LEGAL_DETAILS = 'legal_details';
    var $CRM_CUSTOM_FIELDS = 'custom_fields';
    var $CRM_CONTRAGENT_TYPE = 'contragent_type';
    var $CRM_ORDER_DISCHARGE = 'order_discharge';
    var $CRM_ORDER_FAILED_IDS = 'order_failed_ids';
    var $CRM_ORDER_HISTORY_DATE = 'order_history_date';
    var $CRM_CATALOG_BASE_PRICE = 'catalog_base_price';
    var $INSTALL_PATH;

    function intaro_intarocrm() {
        $arModuleVersion = array();
        $path = str_replace("\\", "/", __FILE__);
        $path = substr($path, 0, strlen($path) - strlen("/index.php"));
        $this->INSTALL_PATH = $path;
        include($path . "/version.php");
        $this->MODULE_VERSION = $arModuleVersion["VERSION"];
        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        $this->MODULE_NAME = GetMessage('INTARO_MODULE_NAME');
        $this->MODULE_DESCRIPTION = GetMessage('MODULE_DESCRIPTION');
        $this->PARTNER_NAME = GetMessage('MODULE_PARTNER_NAME');
        $this->PARTNER_URI = GetMessage('MODULE_PARTNER_URI');
    }

    /**
     * Functions DoInstall and DoUninstall are
     * All other functions are optional
     */
    function DoInstall() {
        global $APPLICATION, $step, $arResult;

        if (!in_array('curl', get_loaded_extensions())) {
            $APPLICATION->ThrowException(GetMessage("INTAROCRM_CURL_ERR"));
            return false;
        }

        if (!date_default_timezone_get()) {
            if (!ini_get('date.timezone')) {
                $APPLICATION->ThrowException(GetMessage("DATE_TIMEZONE_ERR"));
                return false;
            }
        }

        include($this->INSTALL_PATH . '/../classes/general/RestApi.php');
        include($this->INSTALL_PATH . '/../classes/general/Response/ApiResponse.php');
        include($this->INSTALL_PATH . '/../classes/general/ICrmOrderActions.php');
        include($this->INSTALL_PATH . '/../classes/general/ICMLLoader.php');
        include($this->INSTALL_PATH . '/../classes/general/Exception/InvalidJsonException.php');
        include($this->INSTALL_PATH . '/../classes/general/Exception/CurlException.php');
        include($this->INSTALL_PATH . '/../classes/general/RestNormalizer.php');

        $step = intval($_REQUEST['step']);

        if (file_exists($_SERVER["DOCUMENT_ROOT"] . '/bitrix/modules/intaro.intarocrm/classes/general/config/options.xml')) {
            $options = simplexml_load_file($_SERVER["DOCUMENT_ROOT"] . '/bitrix/modules/intaro.intarocrm/classes/general/config/options.xml'); 

            foreach($options->contragents->contragent as $contragent)
            {
                $type["NAME"] = $APPLICATION->ConvertCharset((string)$contragent, 'utf-8', SITE_CHARSET);
                $type["ID"] = (string)$contragent["id"];
                $arResult['contragentType'][] = $type;
                unset ($type);
            }
            foreach($options->fields->field as $field)
            {
                $type["NAME"] = $APPLICATION->ConvertCharset((string)$field, 'utf-8', SITE_CHARSET);
                $type["ID"] = (string)$field["id"];

                if ($field["group"] == 'custom') {
                    $arResult['customFields'][] = $type;
                } elseif(!$field["group"]){
                    $arResult['orderProps'][] = $type;
                } else{
                    $groups = explode(",", (string)$field["group"]);
                    foreach($groups as $group){   
                        $type["GROUP"][] = trim($group);   
                    }
                    $arResult['legalDetails'][] = $type;
                }
                unset($type);
            }
        }

        if($step == 11){
            $arResult['arSites'] = array();
            $rsSites = CSite::GetList($by, $sort, array('ACTIVE' => 'Y'));
            while ($ar = $rsSites->Fetch()){
                $arResult['arSites'][] = $ar;
            }
            if(count($arResult['arSites'])<2){
                $step = 2;
            }
        }
        if ($step <= 1) {
            if (!CModule::IncludeModule("sale")) {
                $arResult['errCode'] = 'ERR_SALE';
            }

            if (!CModule::IncludeModule("iblock")) {
                $arResult['errCode'] = 'ERR_IBLOCK';
            }

            if (!CModule::IncludeModule("catalog")) {
                $arResult['errCode'] = 'ERR_CATALOG';
            }
 
            $arResult['arSites'] = array();
            $rsSites = CSite::GetList($by, $sort, array('ACTIVE' => 'Y'));
            while ($ar = $rsSites->Fetch())
                $arResult['arSites'][] = $ar;

            $APPLICATION->IncludeAdminFile(
                    GetMessage('MODULE_INSTALL_TITLE'), $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/step1.php'
            );
        } else if ($step == 11) {
            //new page
            if (!CModule::IncludeModule("sale")) {
                $arResult['errCode'] = 'ERR_SALE';
            }

            if (!CModule::IncludeModule("iblock")) {
                $arResult['errCode'] = 'ERR_IBLOCK';
            }

            if (!CModule::IncludeModule("catalog")) {
                $arResult['errCode'] = 'ERR_CATALOG';
            }

            if (isset($arResult['errCode']) && $arResult['errCode']) {
                $APPLICATION->IncludeAdminFile(
                        GetMessage('MODULE_INSTALL_TITLE'), 
                        $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/step1.php'
                );
                return;
            }
            
            $api_host = htmlspecialchars(trim($_POST[$this->CRM_API_HOST_OPTION]));
            $api_key = htmlspecialchars(trim($_POST[$this->CRM_API_KEY_OPTION]));

            // form correct url
            $api_host = parse_url($api_host);
            if($api_host['scheme'] != 'https') $api_host['scheme'] = 'https';
            $api_host = $api_host['scheme'] . '://' . $api_host['host'];

            if (!$api_host || !$api_key) {
                $arResult['errCode'] = 'ERR_FIELDS_API_HOST';
                $APPLICATION->IncludeAdminFile(
                        GetMessage('MODULE_INSTALL_TITLE'), 
                        $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/step1.php'
                );
                return;
            }

            $this->INTARO_CRM_API = new RetailCrm\RestApi($api_host, $api_key);
            //api key ok and sites list
            try {
                $arResult['sitesList'] = $this->INTARO_CRM_API->sitesList()->sites;
            } catch (\RetailCrm\Exception\CurlException $e) {
                ICrmOrderActions::eventLog(
                    'intaro.crm/install/index.php', 'RetailCrm\RestApi::sitesList',
                    $e->getCode() . ': ' . $e->getMessage()
                );

                $arResult['errCode'] = 'ERR_' . $e->getCode();

                $APPLICATION->IncludeAdminFile(
                    GetMessage('MODULE_INSTALL_TITLE'),
                    $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/step1.php'
                );
                
                return;
            }
            
            COption::SetOptionString($this->MODULE_ID, $this->CRM_API_HOST_OPTION, $api_host);
            COption::SetOptionString($this->MODULE_ID, $this->CRM_API_KEY_OPTION, $api_key);

            $APPLICATION->IncludeAdminFile(
                GetMessage('MODULE_INSTALL_TITLE'),
                $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/step11.php'
            );
        } else if ($step == 2) {//доставки, оплаты, типы заказов

            if (!CModule::IncludeModule("sale")) {
                $arResult['errCode'] = 'ERR_SALE';
            }

            if (!CModule::IncludeModule("iblock")) {
                $arResult['errCode'] = 'ERR_IBLOCK';
            }

            if (!CModule::IncludeModule("catalog")) {
                $arResult['errCode'] = 'ERR_CATALOG';
            }

            if (isset($arResult['errCode']) && $arResult['errCode']) {
                $APPLICATION->IncludeAdminFile(
                        GetMessage('MODULE_INSTALL_TITLE'), 
                        $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/step1.php'
                );
                return;
            }
            
            $arResult['arSites'] = array();
            $rsSites = CSite::GetList($by, $sort, array('ACTIVE' => 'Y'));
            while ($ar = $rsSites->Fetch()){
                if(!$ar["SERVER_NAME"]){
                    $arResult['errCode'] = 'URL_NOT_FOUND';
                    $APPLICATION->IncludeAdminFile(
                            GetMessage('MODULE_INSTALL_TITLE'), 
                            $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/step1.php'
                    );
                    return;
                }
                else{
                    $arResult['arSites'][] = $ar;
                }
            }
            
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && (strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') && isset($_POST['ajax']) && ($_POST['ajax'] == 1)) {

                $api_host = COption::GetOptionString($this->MODULE_ID, $this->CRM_API_HOST_OPTION, 0);
                $api_key = COption::GetOptionString($this->MODULE_ID, $this->CRM_API_KEY_OPTION, 0);
                $this->INTARO_CRM_API = new \RetailCrm\RestApi($api_host, $api_key);

                //prepare crm lists
                try {
                    $arResult['orderTypesList'] = $this->INTARO_CRM_API->orderTypesList()->orderTypes;
                } catch (\RetailCrm\Exception\CurlException $e) {
                    ICrmOrderActions::eventLog(
                        'intaro.crm/install/index.php', 'RetailCrm\RestApi::orderTypesList::CurlException',
                        $e->getCode() . ': ' . $e->getMessage()
                    );

                    $APPLICATION->RestartBuffer();
                    header('Content-Type: application/x-javascript; charset=' . LANG_CHARSET);
                    die(json_encode(array("success" => false)));
                }

                try {
                    $arResult['deliveryTypesList'] = $this->INTARO_CRM_API->deliveryTypesList()->deliveryTypes;
                    $arResult['deliveryServicesList'] = $this->INTARO_CRM_API->deliveryServicesList()->deliveryServices;
                    $arResult['paymentTypesList'] = $this->INTARO_CRM_API->paymentTypesList()->paymentTypes;
                    $arResult['paymentStatusesList'] = $this->INTARO_CRM_API->paymentStatusesList()->paymentStatuses; // --statuses
                    $arResult['paymentList'] = $this->INTARO_CRM_API->orderStatusesList()->statuses;
                    $arResult['paymentGroupList'] = $this->INTARO_CRM_API->orderStatusGroupsList()->statusGroups; // -- statuses groups
                } catch (\RetailCrm\Exception\CurlException $e) {
                    ICrmOrderActions::eventLog(
                        'intaro.crm/install/index.php', 'RetailCrm\RestApi::*List::CurlException',
                        $e->getCode() . ': ' . $e->getMessage()
                    );
                }
                //bitrix orderTypesList -- personTypes
                $dbOrderTypesList = CSalePersonType::GetList(
                                array(
                            "SORT" => "ASC",
                            "NAME" => "ASC"
                                ), array(
                            "ACTIVE" => "Y",
                                ), false, false, array()
                );


                //form order types ids arr
                $orderTypesArr = array();
                if ($arOrderTypesList = $dbOrderTypesList->Fetch()) {
                    do {
                        $arResult['bitrixOrderTypesList'][] = $arOrderTypesList;
                        $orderTypesArr[$arOrderTypesList['ID']] = htmlspecialchars(trim($_POST['order-type-' . $arOrderTypesList['ID']]));
                    } while ($arOrderTypesList = $dbOrderTypesList->Fetch());
                }

                //bitrix deliveryTypesList
                $dbDeliveryTypesList = CSaleDelivery::GetList(
                                array(
                            "SORT" => "ASC",
                            "NAME" => "ASC"
                                ), array(
                            "ACTIVE" => "Y",
                                ), false, false, array()
                );

                //form delivery types ids arr
                $deliveryTypesArr = array();
                if ($arDeliveryTypesList = $dbDeliveryTypesList->Fetch()) {
                    do {
                        $arResult['bitrixDeliveryTypesList'][] = $arDeliveryTypesList;
                        $deliveryTypesArr[$arDeliveryTypesList['ID']] = htmlspecialchars(trim($_POST['delivery-type-' . $arDeliveryTypesList['ID']]));
                    } while ($arDeliveryTypesList = $dbDeliveryTypesList->Fetch());
                }

                //bitrix deliveryServicesList
                $dbDeliveryServicesList = CSaleDeliveryHandler::GetList(
                    array(
                        'SORT' => 'ASC',
                        'NAME' => 'ASC'
                    ),
                    array(
                        'ACTIVE'  => 'Y',
                        'SITE_ID' => $arResult['arSites'][0]['LID']
                    )
                );

                //form delivery services ids arr
                if ($arDeliveryServicesList = $dbDeliveryServicesList->Fetch()) {
                    do {
                        //auto delivery types
                        $deliveryTypesArr[$arDeliveryServicesList['SID']] = htmlspecialchars(trim($_POST['delivery-type-' . $arDeliveryServicesList['SID']]));
                    } while ($arDeliveryServicesList = $dbDeliveryServicesList->Fetch());
                }

                //bitrix paymentTypesList
                $dbPaymentTypesList = CSalePaySystem::GetList(
                                array(
                            "SORT" => "ASC",
                            "NAME" => "ASC"
                                ), array(
                            "ACTIVE" => "Y"
                                )
                );

                //form payment types ids arr
                $paymentTypesArr = array();
                if ($arPaymentTypesList = $dbPaymentTypesList->Fetch()) {
                    do {
                        $arResult['bitrixPaymentTypesList'][] = $arPaymentTypesList;
                        $paymentTypesArr[$arPaymentTypesList['ID']] = htmlspecialchars(trim($_POST['payment-type-' . $arPaymentTypesList['ID']]));
                    } while ($arPaymentTypesList = $dbPaymentTypesList->Fetch());
                }

                //bitrix paymentStatusesList
                $dbPaymentStatusesList = CSaleStatus::GetList(
                                array(
                            "SORT" => "ASC",
                            "NAME" => "ASC"
                                ), array(
                            "LID" => "ru", //ru 
                            "ACTIVE" => "Y"
                                )
                );

                //form payment statuses ids arr
                $paymentStatusesArr['YY'] = htmlspecialchars(trim($_POST['payment-status-YY']));
                if ($arPaymentStatusesList = $dbPaymentStatusesList->Fetch()) {
                    do {
                        $arResult['bitrixPaymentStatusesList'][] = $arPaymentStatusesList;
                        $paymentStatusesArr[$arPaymentStatusesList['ID']] = htmlspecialchars(trim($_POST['payment-status-' . $arPaymentStatusesList['ID']]));
                    } while ($arPaymentStatusesList = $dbPaymentStatusesList->Fetch());
                }

                $arResult['bitrixPaymentStatusesList'][] = array(
                    'ID' => 'YY',
                    'NAME' => GetMessage('CANCELED')
                );

                //form payment ids arr
                $paymentArr = array();
                $paymentArr['Y'] = htmlspecialchars(trim($_POST['payment-Y']));
                $paymentArr['N'] = htmlspecialchars(trim($_POST['payment-N']));

                COption::SetOptionString($this->MODULE_ID, $this->CRM_ORDER_TYPES_ARR, serialize(ICrmOrderActions::clearArr($orderTypesArr)));
                COption::SetOptionString($this->MODULE_ID, $this->CRM_DELIVERY_TYPES_ARR, serialize(ICrmOrderActions::clearArr($deliveryTypesArr)));
                COption::SetOptionString($this->MODULE_ID, $this->CRM_PAYMENT_TYPES, serialize(ICrmOrderActions::clearArr($paymentTypesArr)));
                COption::SetOptionString($this->MODULE_ID, $this->CRM_PAYMENT_STATUSES, serialize(ICrmOrderActions::clearArr($paymentStatusesArr)));
                COption::SetOptionString($this->MODULE_ID, $this->CRM_PAYMENT, serialize(ICrmOrderActions::clearArr($paymentArr)));

                // generate updated select inputs  
                $input = array();

                foreach ($arResult['bitrixDeliveryTypesList'] as $bitrixDeliveryType) {
                    $input['delivery-type-' . $bitrixDeliveryType['ID']] =
                            '<select name="delivery-type-' . $bitrixDeliveryType['ID'] . '" class="typeselect">';
                    $input['delivery-type-' . $bitrixDeliveryType['ID']] .= '<option value=""></option>';

                    foreach ($arResult['deliveryTypesList'] as $deliveryType) {
                        if ($deliveryTypesArr[$bitrixDeliveryType['ID']] == $deliveryType['code']) {
                            $input['delivery-type-' . $bitrixDeliveryType['ID']] .=
                                    '<option value="' . $deliveryType['code'] . '" selected>';
                        } else {
                            $input['delivery-type-' . $bitrixDeliveryType['ID']] .=
                                    '<option value="' . $deliveryType['code'] . '">';
                        }

                        $input['delivery-type-' . $bitrixDeliveryType['ID']] .=
                                $APPLICATION->ConvertCharset($deliveryType['name'], 'utf-8', SITE_CHARSET);
                        $input['delivery-type-' . $bitrixDeliveryType['ID']] .= '</option>';
                    }

                    $input['delivery-type-' . $bitrixDeliveryType['ID']] .= '</select>';
                }

                foreach ($arResult['bitrixPaymentTypesList'] as $bitrixPaymentType) {
                    $input['payment-type-' . $bitrixPaymentType['ID']] =
                            '<select name="payment-type-' . $bitrixPaymentType['ID'] . '" class="typeselect">';
                    $input['payment-type-' . $bitrixPaymentType['ID']] .= '<option value=""></option>';

                    foreach ($arResult['paymentTypesList'] as $paymentType) {
                        if ($paymentTypesArr[$bitrixPaymentType['ID']] == $paymentType['code']) {
                            $input['payment-type-' . $bitrixPaymentType['ID']] .=
                                    '<option value="' . $paymentType['code'] . '" selected>';
                        } else {
                            $input['payment-type-' . $bitrixPaymentType['ID']] .=
                                    '<option value="' . $paymentType['code'] . '">';
                        }

                        $input['payment-type-' . $bitrixPaymentType['ID']] .=
                                $APPLICATION->ConvertCharset($paymentType['name'], 'utf-8', SITE_CHARSET);
                        $input['payment-type-' . $bitrixPaymentType['ID']] .= '</option>';
                    }

                    $input['payment-type-' . $bitrixPaymentType['ID']] .= '</select>';
                }

                foreach ($arResult['bitrixPaymentStatusesList'] as $bitrixPaymentStatus) {
                    $input['payment-status-' . $bitrixPaymentStatus['ID']] =
                            '<select name="payment-status-' . $bitrixPaymentStatus['ID'] . '" class="typeselect">';
                    $input['payment-status-' . $bitrixPaymentStatus['ID']] .= '<option value=""></option>';

                    foreach ($arResult['paymentGroupList'] as $orderStatusGroup) {
                        if (empty($orderStatusGroup['statuses']))
                            continue;

                        $input['payment-status-' . $bitrixPaymentStatus['ID']].=
                                '<optgroup label="' . $orderStatusGroup['name'] . '">';

                        foreach ($orderStatusGroup['statuses'] as $payment) {
                            if(!isset($arResult['paymentList'][$payment])) continue;

                            if ($paymentStatusesArr[$bitrixPaymentStatus['ID']] == $arResult['paymentList'][$payment]['code']) {
                                $input['payment-status-' . $bitrixPaymentStatus['ID']] .=
                                        '<option value="' . $arResult['paymentList'][$payment]['code'] . '" selected>';
                            } else {
                                $input['payment-status-' . $bitrixPaymentStatus['ID']] .=
                                        '<option value="' . $arResult['paymentList'][$payment]['code'] . '">';
                            }

                            $input['payment-status-' . $bitrixPaymentStatus['ID']] .=
                                    $APPLICATION->ConvertCharset($arResult['paymentList'][$payment]['name'], 'utf-8', SITE_CHARSET);
                            $input['payment-status-' . $bitrixPaymentStatus['ID']] .= '</option>';
                        }

                        $input['payment-status-' . $bitrixPaymentStatus['ID']] .= '</optgroup>';
                    }

                    $input['payment-status-' . $bitrixPaymentStatus['ID']] .= '</select>';
                }

                foreach ($arResult['bitrixPaymentList'] as $bitrixPayment) {
                    $input['payment-' . $bitrixPayment['ID']] =
                            '<select name="payment-' . $bitrixPayment['ID'] . '" class="typeselect">';
                    $input['payment-' . $bitrixPayment['ID']] .= '<option value=""></option>';

                    foreach ($arResult['paymentStatusesList'] as $paymentStatus) {
                        if ($paymentArr[$bitrixPayment['ID']] == $paymentStatus['code']) {
                            $input['payment-' . $bitrixPayment['ID']] .=
                                    '<option value="' . $paymentStatus['code'] . '" selected>';
                        } else {
                            $input['payment-' . $bitrixPayment['ID']] .=
                                    '<option value="' . $paymentStatus['code'] . '">';
                        }

                        $input['payment-' . $bitrixPayment['ID']] .=
                                $APPLICATION->ConvertCharset($paymentStatus['name'], 'utf-8', SITE_CHARSET);
                        $input['payment-' . $bitrixPayment['ID']] .= '</option>';
                    }

                    $input['payment-' . $bitrixPayment['ID']] .= '</select>';
                }

                foreach ($arResult['bitrixOrderTypesList'] as $bitrixOrderType) {
                    $input['order-type-' . $bitrixOrderType['ID']] =
                            '<select name="order-type-' . $bitrixOrderType['ID'] . '" class="typeselect">';
                    $input['order-type-' . $bitrixOrderType['ID']] .= '<option value=""></option>';

                    foreach ($arResult['orderTypesList'] as $orderType) {
                        if ($orderTypesArr[$bitrixOrderType['ID']] == $orderType['code']) {
                            $input['order-type-' . $bitrixOrderType['ID']] .=
                                    '<option value="' . $orderType['code'] . '" selected>';
                        } else {
                            $input['order-type-' . $bitrixOrderType['ID']] .=
                                    '<option value="' . $orderType['code'] . '">';
                        }

                        $input['order-type-' . $bitrixOrderType['ID']] .=
                                $APPLICATION->ConvertCharset($orderType['name'], 'utf-8', SITE_CHARSET);
                        $input['order-type-' . $bitrixOrderType['ID']] .= '</option>';
                    }

                    $input['order-type-' . $bitrixOrderType['ID']] .= '</select>';
                }



                $APPLICATION->RestartBuffer();
                header('Content-Type: application/x-javascript; charset=' . LANG_CHARSET);
                die(json_encode(array("success" => true, "result" => $input)));
            }
            
            if(count($arResult['arSites'])>1){
                // api load
                $api_host = COption::GetOptionString($this->MODULE_ID, $this->CRM_API_HOST_OPTION, 0);
                $api_key = COption::GetOptionString($this->MODULE_ID, $this->CRM_API_KEY_OPTION, 0);

                foreach($arResult['arSites'] as $site){
                    if($_POST['sites-id-'.$site['LID']] && !empty($_POST['sites-id-'.$site['LID']])){
                        $siteCode[$site['LID']] = htmlspecialchars(trim($_POST['sites-id-'.$site['LID']]));
                    }
                }
                if (count($arResult['arSites'])!=count($siteCode)) {
                    $arResult['errCode'] = 'ERR_FIELDS_API_HOST';
                    $APPLICATION->IncludeAdminFile(
                            GetMessage('MODULE_INSTALL_TITLE'), 
                            $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/step11.php'
                    );
                    return;
                }

                $this->INTARO_CRM_API = new \RetailCrm\RestApi($api_host, $api_key);
                COption::SetOptionString($this->MODULE_ID, $this->CRM_SITES_LIST, serialize($siteCode));
            }
            else{//если 1 сайт
                $api_host = htmlspecialchars(trim($_POST[$this->CRM_API_HOST_OPTION]));
                $api_key = htmlspecialchars(trim($_POST[$this->CRM_API_KEY_OPTION]));

                // form correct url
                $api_host = parse_url($api_host);
                if($api_host['scheme'] != 'https') $api_host['scheme'] = 'https';
                $api_host = $api_host['scheme'] . '://' . $api_host['host'];

                if (!$api_host || !$api_key) {
                    $arResult['errCode'] = 'ERR_FIELDS_API_HOST';
                    $APPLICATION->IncludeAdminFile(
                            GetMessage('MODULE_INSTALL_TITLE'), 
                            $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/step1.php'
                    );
                    return;
                }
                
                $this->INTARO_CRM_API = new \RetailCrm\RestApi($api_host, $api_key);

                try {
                    $this->INTARO_CRM_API->paymentStatusesList()->paymentStatuses;
                } catch (\RetailCrm\Exception\CurlException $e) {
                    ICrmOrderActions::eventLog(
                        'intaro.crm/install/index.php', 'RetailCrm\RestApi::paymentStatusesList::CurlException',
                        $e->getCode() . ': ' . $e->getMessage()
                    );

                    $arResult['errCode'] = 'ERR_' . $e->getCode();

                    $APPLICATION->IncludeAdminFile(
                        GetMessage('MODULE_INSTALL_TITLE'),
                        $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/step1.php'
                    );

                    return;
                }
                COption::SetOptionString($this->MODULE_ID, $this->CRM_API_HOST_OPTION, $api_host);
                COption::SetOptionString($this->MODULE_ID, $this->CRM_API_KEY_OPTION, $api_key);
            }
            
            //prepare crm lists
            try {                   
                $arResult['orderTypesList'] = $this->INTARO_CRM_API->orderTypesList()->orderTypes;
                $arResult['deliveryTypesList'] = $this->INTARO_CRM_API->deliveryTypesList()->deliveryTypes;
                $arResult['deliveryServicesList'] = $this->INTARO_CRM_API->deliveryServicesList()->deliveryServices;
                $arResult['paymentTypesList'] = $this->INTARO_CRM_API->paymentTypesList()->paymentTypes;
                $arResult['paymentStatusesList'] = $this->INTARO_CRM_API->paymentStatusesList()->paymentStatuses; // --statuses
                $arResult['paymentList'] = $this->INTARO_CRM_API->orderStatusesList()->statuses;
                $arResult['paymentGroupList'] = $this->INTARO_CRM_API->orderStatusGroupsList()->statusGroups; // -- statuses groups
            } catch (\RetailCrm\Exception\CurlException $e) {
                ICrmOrderActions::eventLog(
                    'intaro.crm/install/index.php', 'RetailCrm\RestApi::*List::CurlException',
                    $e->getCode() . ': ' . $e->getMessage()
                );
            }
            //bitrix orderTypesList -- personTypes
            $dbOrderTypesList = CSalePersonType::GetList(
                            array(
                        "SORT" => "ASC",
                        "NAME" => "ASC"
                            ), array(
                        "ACTIVE" => "Y",
                            ), false, false, array()
            );

            if ($arOrderTypesList = $dbOrderTypesList->Fetch()) {
                do {
                    $arResult['bitrixOrderTypesList'][] = $arOrderTypesList;
                } while ($arOrderTypesList = $dbOrderTypesList->Fetch());
            }

            //bitrix deliveryTypesList
            $dbDeliveryTypesList = CSaleDelivery::GetList(
                            array(
                        "SORT" => "ASC",
                        "NAME" => "ASC"
                            ), array(
                        "ACTIVE" => "Y",
                            ), false, false, array()
            );

            if ($arDeliveryTypesList = $dbDeliveryTypesList->Fetch()) {
                do {
                    $arResult['bitrixDeliveryTypesList'][] = $arDeliveryTypesList;
                } while ($arDeliveryTypesList = $dbDeliveryTypesList->Fetch());
            }

            // bitrix deliveryServicesList
            $dbDeliveryServicesList = CSaleDeliveryHandler::GetList(
                array(
                    'SORT' => 'ASC',
                    'NAME' => 'ASC'
                ),
                array(
                    'ACTIVE'  => 'Y',
                    'SITE_ID' => $arResult['arSites'][0]['LID']
                )
            );

            if ($arDeliveryServicesList = $dbDeliveryServicesList->Fetch()) {
                do {
                    $arResult['bitrixDeliveryTypesList'][] = array('ID' => $arDeliveryServicesList['SID'], 'NAME' => $arDeliveryServicesList['NAME']);
                } while ($arDeliveryServicesList = $dbDeliveryServicesList->Fetch());
            }

            //bitrix paymentTypesList
            $dbPaymentTypesList = CSalePaySystem::GetList(
                            array(
                        "SORT" => "ASC",
                        "NAME" => "ASC"
                            ), array(
                        "ACTIVE" => "Y"
                            )
            );

            if ($arPaymentTypesList = $dbPaymentTypesList->Fetch()) {
                do {
                    $arResult['bitrixPaymentTypesList'][] = $arPaymentTypesList;
                } while ($arPaymentTypesList = $dbPaymentTypesList->Fetch());
            }

            //bitrix paymentStatusesList --statuses
            $dbPaymentStatusesList = CSaleStatus::GetList(
                            array(
                        "SORT" => "ASC",
                        "NAME" => "ASC"
                            ), array(
                        "LID" => "ru", //ru
                        "ACTIVE" => "Y"
                            )
            );

            if ($arPaymentStatusesList = $dbPaymentStatusesList->Fetch()) {
                do {
                    $arResult['bitrixPaymentStatusesList'][] = $arPaymentStatusesList;
                } while ($arPaymentStatusesList = $dbPaymentStatusesList->Fetch());
            }

            $arResult['bitrixPaymentStatusesList'][] = array(
                'ID' => 'YY',
                'NAME' => GetMessage('CANCELED')
            );

            $APPLICATION->IncludeAdminFile(
                    GetMessage('MODULE_INSTALL_TITLE'),
                    $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/step2.php'
            );
        } else if ($step == 3) {//сопостовление свойств заказа
            if (!CModule::IncludeModule("sale")) {
                //handler
            }

            if (isset($_POST['back']) && $_POST['back']) {
                $APPLICATION->IncludeAdminFile(
                        GetMessage('MODULE_INSTALL_TITLE'),
                        $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/step1.php'
                );
            }

            // api load
            $api_host = COption::GetOptionString($this->MODULE_ID, $this->CRM_API_HOST_OPTION, 0);
            $api_key = COption::GetOptionString($this->MODULE_ID, $this->CRM_API_KEY_OPTION, 0);
            $this->INTARO_CRM_API = new \RetailCrm\RestApi($api_host, $api_key);

            //bitrix orderTypesList -- personTypes
            $dbOrderTypesList = CSalePersonType::GetList(
                            array(
                        "SORT" => "ASC",
                        "NAME" => "ASC"
                            ), array(
                        "ACTIVE" => "Y",
                            ), false, false, array()
            );

            //form order types ids arr
            $orderTypesArr = array();
            $arResult['bitrixOrderTypesList'] = array();
            if ($arOrderTypesList = $dbOrderTypesList->Fetch()) {
                do {
                    $orderTypesArr[$arOrderTypesList['ID']] = htmlspecialchars(trim($_POST['order-type-' . $arOrderTypesList['ID']]));
                    $arResult['bitrixOrderTypesList'][] = $arOrderTypesList;
                } while ($arOrderTypesList = $dbOrderTypesList->Fetch());
            }

            //bitrix deliveryTypesList
            $dbDeliveryTypesList = CSaleDelivery::GetList(
                            array(
                        "SORT" => "ASC",
                        "NAME" => "ASC"
                            ), array(
                        "ACTIVE" => "Y",
                            ), false, false, array()
            );
            
            //bitrix deliveryServicesList
            $rsSites = CSite::GetList($by, $sort, array());
            while ($ar = $rsSites->Fetch()){
                $arResult['arSites'][] = $ar;
            }
            $dbDeliveryServicesList = CSaleDeliveryHandler::GetList(
                array(
                    'SORT' => 'ASC',
                    'NAME' => 'ASC'
                ),
                array(
                    'ACTIVE'  => 'Y',
                    'SITE_ID' => $arResult['arSites'][0]['LID']
                )
            );

            //form delivery types / services ids arr
            $deliveryTypesArr = array();

            if (htmlspecialchars(trim($_POST['delivery-types-export'])) == 'false') {
                if ($arDeliveryTypesList = $dbDeliveryTypesList->Fetch()) {
                    do {
                        $deliveryTypesArr[$arDeliveryTypesList['ID']] = htmlspecialchars(trim($_POST['delivery-type-' . $arDeliveryTypesList['ID']]));
                    } while ($arDeliveryTypesList = $dbDeliveryTypesList->Fetch());
                }

                if ($arDeliveryServicesList = $dbDeliveryServicesList->Fetch()) {
                    do {
                        //auto delivery types
                        $deliveryTypesArr[$arDeliveryServicesList['SID']] = htmlspecialchars(trim($_POST['delivery-type-' . $arDeliveryServicesList['SID']]));

                    } while ($arDeliveryServicesList = $dbDeliveryServicesList->Fetch());
                }
            } elseif (htmlspecialchars(trim($_POST['delivery-types-export'])) == 'true') {
                // send to intaro crm and save delivery types!
                if ($arDeliveryTypesList = $dbDeliveryTypesList->Fetch()) {
                    do {
                        // parse id
                        $arId = array();
                        $resultDeliveryTypeId = 0;
                        if (strpos($arDeliveryTypesList['ID'], ":") !== false)
                            $arId = explode(":", $arDeliveryTypesList['ID']);

                        if ($arId)
                            $resultDeliveryTypeId = $arId[0];
                        else
                            $resultDeliveryTypeId = $arDeliveryTypesList['ID'];

                        $deliveryTypesArr[$arDeliveryTypesList['ID']] = $resultDeliveryTypeId;

                        // send to crm
                        try {
                            $this->INTARO_CRM_API->deliveryTypeEdit(ICrmOrderActions::clearArr(array(
                                'code' => $resultDeliveryTypeId,
                                'name' => ICrmOrderActions::toJSON($arDeliveryTypesList['NAME']),
                                'defaultCost' => $arDeliveryTypesList['PRICE'],
                                'description' => ICrmOrderActions::toJSON($arDeliveryTypesList['DESCRIPTION']),
                                'paymentTypes' => ''
                            )));
                        } catch (\RetailCrm\Exception\CurlException $e) {
                            ICrmOrderActions::eventLog(
                                'intaro.crm/install/index.php', 'RetailCrm\RestApi::deliveryTypeEdit::CurlException',
                                $e->getCode() . ': ' . $e->getMessage()
                            );
                        }

                    } while ($arDeliveryTypesList = $dbDeliveryTypesList->Fetch());
                }

                if ($arDeliveryServicesList = $dbDeliveryServicesList->Fetch()) {
                    do {

                        $deliveryTypesArr[$arDeliveryServicesList['SID']] = $arDeliveryServicesList['SID'];

                        // send to crm
                        try {
                            $this->INTARO_CRM_API->deliveryTypeEdit(ICrmOrderActions::clearArr(array(
                                'code' => $arDeliveryServicesList['SID'],
                                'name' => ICrmOrderActions::toJSON($arDeliveryServicesList['NAME']),
                                'defaultCost' => 0,
                                'description' => ICrmOrderActions::toJSON($arDeliveryTypesList['DESCRIPTION']),
                                'paymentTypes' => ''
                            )));
                        } catch (\RetailCrm\Exception\CurlException $e) {
                            ICrmOrderActions::eventLog(
                                'intaro.crm/install/index.php', 'RetailCrm\RestApi::deliveryTypeEdit::CurlException',
                                $e->getCode() . ': ' . $e->getMessage()
                            );
                        }

                        foreach($arDeliveryServicesList['PROFILES'] as $id => $profile) {
                            // send to crm
                            try {
                                $this->INTARO_CRM_API->deliveryServiceEdit(ICrmOrderActions::clearArr(array(
                                    'code' => $arDeliveryServicesList['SID'] . '-' . $id,
                                    'name' => ICrmOrderActions::toJSON($profile['TITLE']),
                                    'deliveryType' => $arDeliveryServicesList['SID']
                                )));
                            } catch (\RetailCrm\Exception\CurlException $e) {
                                ICrmOrderActions::eventLog(
                                    'intaro.crm/install/index.php', 'IntaroCrm\RestApi::deliveryServiceEdit::CurlException',
                                    $e->getCode() . ': ' . $e->getMessage()
                                );
                            }
                        }

                    } while ($arDeliveryServicesList = $dbDeliveryServicesList->Fetch());
                }
            }

            //bitrix paymentTypesList
            $dbPaymentTypesList = CSalePaySystem::GetList(
                            array(
                        "SORT" => "ASC",
                        "NAME" => "ASC"
                            ), array(
                        "ACTIVE" => "Y"
                            )
            );

            //form payment types ids arr
            $paymentTypesArr = array();
            if ($arPaymentTypesList = $dbPaymentTypesList->Fetch()) {
                do {
                    $paymentTypesArr[$arPaymentTypesList['ID']] = htmlspecialchars(trim($_POST['payment-type-' . $arPaymentTypesList['ID']]));
                } while ($arPaymentTypesList = $dbPaymentTypesList->Fetch());
            }

            //bitrix paymentStatusesList
            $dbPaymentStatusesList = CSaleStatus::GetList(
                            array(
                        "SORT" => "ASC",
                        "NAME" => "ASC"
                            ), array(
                        "LID" => "ru", //ru
                        "ACTIVE" => "Y"
                            )
            );

            //form payment statuses ids arr
            $paymentStatusesArr['YY'] = htmlspecialchars(trim($_POST['payment-status-YY']));

            if ($arPaymentStatusesList = $dbPaymentStatusesList->Fetch()) {
                do {
                    $paymentStatusesArr[$arPaymentStatusesList['ID']] = htmlspecialchars(trim($_POST['payment-status-' . $arPaymentStatusesList['ID']]));
                } while ($arPaymentStatusesList = $dbPaymentStatusesList->Fetch());
            }

            //form payment ids arr
            $paymentArr = array();
            $paymentArr['Y'] = htmlspecialchars(trim($_POST['payment-Y']));
            $paymentArr['N'] = htmlspecialchars(trim($_POST['payment-N']));
            
            //form orderProps
            $dbProp = CSaleOrderProps::GetList(array(), array());
            while ($arProp = $dbProp->GetNext()) {
                $arResult['arProp'][$arProp['PERSON_TYPE_ID']][] = $arProp;
            }

            COption::SetOptionString($this->MODULE_ID, $this->CRM_ORDER_TYPES_ARR, serialize(ICrmOrderActions::clearArr($orderTypesArr)));
            COption::SetOptionString($this->MODULE_ID, $this->CRM_DELIVERY_TYPES_ARR, serialize(ICrmOrderActions::clearArr($deliveryTypesArr)));
            COption::SetOptionString($this->MODULE_ID, $this->CRM_PAYMENT_TYPES, serialize(ICrmOrderActions::clearArr($paymentTypesArr)));
            COption::SetOptionString($this->MODULE_ID, $this->CRM_PAYMENT_STATUSES, serialize(ICrmOrderActions::clearArr($paymentStatusesArr)));
            COption::SetOptionString($this->MODULE_ID, $this->CRM_PAYMENT, serialize(ICrmOrderActions::clearArr($paymentArr)));
            COption::SetOptionString($this->MODULE_ID, $this->CRM_ORDER_LAST_ID, 0);
            COption::SetOptionString($this->MODULE_ID, $this->CRM_ORDER_DISCHARGE, 1);
            COption::SetOptionString($this->MODULE_ID, $this->CRM_ORDER_FAILED_IDS, serialize(array()));
            
            $APPLICATION->IncludeAdminFile(
                    GetMessage('MODULE_INSTALL_TITLE'),
                    $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/step3.php'
            );
        } else if ($step == 4) {//выгрузка старых заказов
            if (!CModule::IncludeModule("sale")) {
                //handler
            }

            if (isset($_POST['back']) && $_POST['back']) {
                $APPLICATION->IncludeAdminFile(
                    GetMessage('MODULE_INSTALL_TITLE'),
                    $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/step2.php'
                );
            }

            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && (strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') && isset($_POST['ajax']) && ($_POST['ajax'] == 1)) {
                ICrmOrderActions::uploadOrders(); // each 50

                $lastUpOrderId = COption::GetOptionString($this->MODULE_ID, $this->CRM_ORDER_LAST_ID, 0);
                $countLeft = (int) CSaleOrder::GetList(array("ID" => "ASC"), array('>ID' => $lastUpOrderId), array());
                $countAll = (int) CSaleOrder::GetList(array("ID" => "ASC"), array(), array());

                if (!isset($_POST['finish']))
                    $finish = 0;
                else
                    $finish = (int) $_POST['finish'];

                $percent = round(100 - ($countLeft * 100 / $countAll), 1);

                if (!$countLeft)
                    $finish = 1;

                $APPLICATION->RestartBuffer();
                header('Content-Type: application/x-javascript; charset=' . LANG_CHARSET);
                die(json_encode(array("finish" => $finish, "percent" => $percent)));
            }
            
            if (isset($_POST['back']) && $_POST['back']) {
                $APPLICATION->IncludeAdminFile(
                        GetMessage('MODULE_INSTALL_TITLE'), $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/step2.php'
                );
            }
            
            //bitrix orderTypesList -- personTypes
            $dbOrderTypesList = CSalePersonType::GetList(
                            array(
                        "SORT" => "ASC",
                        "NAME" => "ASC"
                            ), array(
                        "ACTIVE" => "Y",
                            ), false, false, array()
            );

            //form order types ids arr
            $orderTypesArr = array();
            $orderTypesList = array();
            if ($arOrderTypesList = $dbOrderTypesList->Fetch()) {
                do {
                    $orderTypesArr[$arOrderTypesList['ID']] = htmlspecialchars(trim($_POST['order-type-' . $arOrderTypesList['ID']]));
                    $orderTypesList[] = $arOrderTypesList;
                } while ($arOrderTypesList = $dbOrderTypesList->Fetch());
            }

            $orderPropsArr = array();
            foreach ($orderTypesList as $orderType) {
                $propsCount = 0;
                $_orderPropsArr = array();
                foreach ($arResult['orderProps'] as $orderProp) {
                    if ((!(int) htmlspecialchars(trim($_POST['address-detail-' . $orderType['ID']]))) && $propsCount > 4)
                        break;
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
            $contragentTypeArr = array();//сделать проверки
            foreach ($orderTypesList as $orderType) {
                $contragentTypeArr[$orderType['ID']] = htmlspecialchars(trim($_POST['contragent-type-' . $orderType['ID']]));
            }

            COption::SetOptionString($this->MODULE_ID, $this->CRM_ORDER_PROPS, serialize(ICrmOrderActions::clearArr($orderPropsArr)));
            COption::SetOptionString($this->MODULE_ID, $this->CRM_CUSTOM_FIELDS, serialize(ICrmOrderActions::clearArr($customFieldsArr)));
            COption::SetOptionString($this->MODULE_ID, $this->CRM_LEGAL_DETAILS, serialize(ICrmOrderActions::clearArr($legalDetailsArr)));
            COption::SetOptionString($this->MODULE_ID, $this->CRM_CONTRAGENT_TYPE, serialize(ICrmOrderActions::clearArr($contragentTypeArr)));
   
            $APPLICATION->IncludeAdminFile(
                    GetMessage('MODULE_INSTALL_TITLE'),
                    $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/step4.php'
            );

        } else if ($step == 5) {//экспорт каталога
            if (!CModule::IncludeModule("iblock")) {
                $arResult['errCode'] = 'ERR_IBLOCK';
            }

            if (!CModule::IncludeModule("catalog")) {
                $arResult['errCode'] = 'ERR_CATALOG';
            }

            $arResult['PRICE_TYPES'] = array();

            $dbPriceType = CCatalogGroup::GetList(
                array("SORT" => "ASC"), array(), array(), array(), array("ID", "NAME", "BASE")
            );

            while ($arPriceType = $dbPriceType->Fetch()) {
                $arResult['PRICE_TYPES'][$arPriceType['ID']] = $arPriceType;
            }

            $APPLICATION->IncludeAdminFile(
                    GetMessage('MODULE_INSTALL_TITLE'),
                    $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/step5.php'
            );
        } else if ($step == 6) {//регистрация модуля

            if (!CModule::IncludeModule("iblock")) {
                $arResult['errCode'] = 'ERR_IBLOCK';
            }

            if (!CModule::IncludeModule("catalog")) {
                $arResult['errCode'] = 'ERR_CATALOG';
            }

            if (isset($arResult['errCode']) && $arResult['errCode']) {
                $APPLICATION->IncludeAdminFile(
                        GetMessage('MODULE_INSTALL_TITLE'), 
                        $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/step5.php'
                );
                return;
            }

            if (isset($_POST['back']) && $_POST['back']) {
                $APPLICATION->IncludeAdminFile(
                        GetMessage('MODULE_INSTALL_TITLE'), 
                        $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/step3.php'
                );
            }

            if (!isset($_POST['IBLOCK_EXPORT']))
                $arResult['errCode'] = 'ERR_FIELDS_IBLOCK';
            else
                $iblocks = $_POST['IBLOCK_EXPORT'];
            
            $iblockProperties = Array(
                    "article" => "article",
                    "manufacturer" => "manufacturer",
                    "color" =>"color",
                    "weight" => "weight",
                    "size" => "size",
                    "length" => "length",
                    "width" => "width",
                    "height" => "height",
                );
            
            $propertiesSKU = array();
            $propertiesUnitSKU = array();
            foreach ($iblockProperties as $prop) {
                foreach ($_POST['IBLOCK_PROPERTY_SKU'. '_' . $prop] as $iblock => $val) {
                    $propertiesSKU[$iblock][$prop] = $val;
                }
                foreach ($_POST['IBLOCK_PROPERTY_UNIT_SKU'. '_' . $prop] as $iblock => $val) {
                    $propertiesUnitSKU[$iblock][$prop] = $val;
                }
            }
            
            $propertiesProduct = array();
            $propertiesUnitProduct = array();
            foreach ($iblockProperties as $prop) {
                foreach ($_POST['IBLOCK_PROPERTY_PRODUCT'. '_' . $prop] as $iblock => $val) {
                    $propertiesProduct[$iblock][$prop] = $val;
                }
                foreach ($_POST['IBLOCK_PROPERTY_UNIT_PRODUCT'. '_' . $prop] as $iblock => $val) {
                    $propertiesUnitProduct[$iblock][$prop] = $val;
                }
            }

            if (!isset($_POST['SETUP_FILE_NAME']))
                $arResult['errCode'] = 'ERR_FIELDS_FILE';
            else
                $filename = $_POST['SETUP_FILE_NAME'];
            
            if (!isset($_POST['TYPE_LOADING']))
                $typeLoading = 0;
            else
                $typeLoading = $_POST['TYPE_LOADING'];

            if (!isset($_POST['SETUP_PROFILE_NAME']))
                $profileName = "";
            else
                $profileName = $_POST['SETUP_PROFILE_NAME'];

            if ($typeLoading != 'none' && $profileName == "")
                $arResult['errCode'] = 'ERR_FIELDS_PROFILE';

            if ($filename == "")
                $arResult['errCode'] = 'ERR_FIELDS_FILE';

            if (isset($arResult['errCode']) && $arResult['errCode']) {
                
                $arOldValues = Array(
                    'IBLOCK_EXPORT' => $iblocks,
                    'IBLOCK_PROPERTY_SKU' => $propertiesSKU,
                    'IBLOCK_PROPERTY_UNIT_SKU' => $propertiesUnitSKU,
                    'IBLOCK_PROPERTY_PRODUCT' => $propertiesProduct,
                    'IBLOCK_PROPERTY_UNIT_PRODUCT' => $propertiesUnitProduct,
                    'SETUP_FILE_NAME' => $filename,
                    'SETUP_PROFILE_NAME' => $profileName
                );
                global $oldValues;
                $oldValues = $arOldValues;
                $APPLICATION->IncludeAdminFile(
                    GetMessage('MODULE_INSTALL_TITLE'),
                    $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/step5.php'
                );
                return;
            }

            RegisterModule($this->MODULE_ID);
            RegisterModuleDependences("sale", "OnSalePayOrder", $this->MODULE_ID, "ICrmOrderEvent", "onSalePayOrder");
            RegisterModuleDependences("sale", "OnSaleCancelOrder", $this->MODULE_ID, "ICrmOrderEvent", "onSaleCancelOrder");
            RegisterModuleDependences("sale", "OnBeforeOrderAccountNumberSet", $this->MODULE_ID, "ICrmOrderEvent", "onBeforeOrderAccountNumberSet");
            RegisterModuleDependences("sale", "OnOrderNewSendEmail", $this->MODULE_ID, "ICrmOrderEvent", "onSendOrderMail");
            RegisterModuleDependences("sale", "OnOrderUpdate", $this->MODULE_ID, "ICrmOrderEvent", "onUpdateOrder");
            RegisterModuleDependences("sale", "OnBeforeOrderAdd", $this->MODULE_ID, "ICrmOrderEvent", "onBeforeOrderAdd");
            RegisterModuleDependences("sale", "OnSaleBeforeReserveOrder", $this->MODULE_ID, "ICrmOrderEvent", "OnSaleBeforeReserveOrder");
            RegisterModuleDependences("sale", "OnSaleReserveOrder", $this->MODULE_ID, "ICrmOrderEvent", "onSaleReserveOrder");

            COption::SetOptionString($this->MODULE_ID, $this->CRM_CATALOG_BASE_PRICE, htmlspecialchars(trim($_POST['price-types'])));

            $this->CopyFiles();
            if (isset($_POST['LOAD_NOW'])) {
                $rsSites = CSite::GetList($by, $sort, array('ACTIVE' => 'Y'));
                while ($ar = $rsSites->Fetch()){
                    if($ar['DEF'] == 'Y'){
                        $SERVER_NAME = $ar['SERVER_NAME'];//разделить потом с учетом многосайтовости
                    }
                }
                
                $loader = new ICMLLoader();
                $loader->iblocks = $iblocks;
                $loader->propertiesUnitProduct = $propertiesUnitProduct;
                $loader->propertiesProduct = $propertiesProduct;
                $loader->propertiesUnitSKU = $propertiesUnitSKU;
                $loader->propertiesSKU = $propertiesSKU;
                $loader->filename = $filename;
                $loader->serverName = $SERVER_NAME;
                $loader->application = $APPLICATION;
                $loader->Load();
                
            } 
            
            if ($typeLoading == 'agent' || $typeLoading == 'cron') {
                if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/bitrix/php_interface/include/catalog_export/' . $this->INTARO_CRM_EXPORT . '_run.php')) {
                    $dbProfile = CCatalogExport::GetList(array(), array("FILE_NAME" => $this->INTARO_CRM_EXPORT));

                    while ($arProfile = $dbProfile->Fetch()) {
                        if ($arProfile["DEFAULT_PROFILE"] != "Y") {
                            CAgent::RemoveAgent("CCatalogExport::PreGenerateExport(" . $arProfile['ID'] . ");", "catalog");
                            CCatalogExport::Delete($arProfile['ID']);
                        }
                    }
                }
                $ar = $this->GetProfileSetupVars($iblocks, $propertiesProduct, $propertiesUnitProduct, $propertiesSKU, $propertiesUnitSKU, $filename);
                $PROFILE_ID = CCatalogExport::Add(array(
                    "LAST_USE"      => false,
                    "FILE_NAME"     => $this->INTARO_CRM_EXPORT,
                    "NAME"      => $profileName,
                    "DEFAULT_PROFILE"   => "N",
                    "IN_MENU"       => "N",
                    "IN_AGENT"      => "N",
                    "IN_CRON"       => "N",
                    "NEED_EDIT"     => "N",
                    "SETUP_VARS"    => $ar
                    ));
                if (intval($PROFILE_ID) <= 0) {
                    $arResult['errCode'] = 'ERR_IBLOCK';
                    return;
                }
                if ($typeLoading == 'agent') {

                    $dateAgent = new DateTime();
                    $intAgent = new DateInterval('PT60S'); // PT60S - 60 sec;
                    $dateAgent->add($intAgent);
                    CAgent::AddAgent(
                            "CCatalogExport::PreGenerateExport(" . $PROFILE_ID . ");", "catalog", "N", 86400, $dateAgent->format('d.m.Y H:i:s'), // date of first check
                            "Y", // agent is active
                            $dateAgent->format('d.m.Y H:i:s'), // date of first start
                            30
                    );

                    CCatalogExport::Update($PROFILE_ID, array(
                        "IN_AGENT" => "Y"
                    ));
                } else {
                    $agent_period = 24;
                    $agent_php_path = "/usr/local/php/bin/php";

                    if (!file_exists($_SERVER["DOCUMENT_ROOT"] . CATALOG_PATH2EXPORTS . "cron_frame.php")) {
                        CheckDirPath($_SERVER["DOCUMENT_ROOT"] . CATALOG_PATH2EXPORTS);
                        $tmp_file_size = filesize($_SERVER["DOCUMENT_ROOT"] . CATALOG_PATH2EXPORTS_DEF . "cron_frame.php");
                        $fp = fopen($_SERVER["DOCUMENT_ROOT"] . CATALOG_PATH2EXPORTS_DEF . "cron_frame.php", "rb");
                        $tmp_data = fread($fp, $tmp_file_size);
                        fclose($fp);

                        $tmp_data = str_replace("#DOCUMENT_ROOT#", $_SERVER["DOCUMENT_ROOT"], $tmp_data);
                        $tmp_data = str_replace("#PHP_PATH#", $agent_php_path, $tmp_data);

                        $fp = fopen($_SERVER["DOCUMENT_ROOT"] . CATALOG_PATH2EXPORTS . "cron_frame.php", "ab");
                        fwrite($fp, $tmp_data);
                        fclose($fp);
                    }

                    $cfg_data = "";
                    if (file_exists($_SERVER["DOCUMENT_ROOT"] . "/bitrix/crontab/crontab.cfg")) {
                        $cfg_file_size = filesize($_SERVER["DOCUMENT_ROOT"] . "/bitrix/crontab/crontab.cfg");
                        $fp = fopen($_SERVER["DOCUMENT_ROOT"] . "/bitrix/crontab/crontab.cfg", "rb");
                        $cfg_data = fread($fp, $cfg_file_size);
                        fclose($fp);
                    }

                    CheckDirPath($_SERVER["DOCUMENT_ROOT"] . CATALOG_PATH2EXPORTS . "logs/");

                    if ($arProfile["IN_CRON"] == "Y") {
                        // remove
                        $cfg_data = preg_replace("#^.*?" . preg_quote(CATALOG_PATH2EXPORTS) . "cron_frame.php +" . $PROFILE_ID . " *>.*?$#im", "", $cfg_data);
                    } else {
                        $strTime = "0 */" . $agent_period . " * * * ";
                        if (strlen($cfg_data) > 0)
                            $cfg_data .= "\n";

                        $cfg_data .= $strTime . $agent_php_path . " -f " . $_SERVER["DOCUMENT_ROOT"] . CATALOG_PATH2EXPORTS . "cron_frame.php " . $PROFILE_ID . " >" . $_SERVER["DOCUMENT_ROOT"] . CATALOG_PATH2EXPORTS . "logs/" . $PROFILE_ID . ".txt\n";
                    }

                    CCatalogExport::Update($PROFILE_ID, array(
                        "IN_CRON" => "Y"
                    ));

                    CheckDirPath($_SERVER["DOCUMENT_ROOT"] . "/bitrix/crontab/");
                    $cfg_data = preg_replace("#[\r\n]{2,}#im", "\n", $cfg_data);
                    $fp = fopen($_SERVER["DOCUMENT_ROOT"] . "/bitrix/crontab/crontab.cfg", "wb");
                    fwrite($fp, $cfg_data);
                    fclose($fp);

                    $arRetval = array();
                    @exec("crontab " . $_SERVER["DOCUMENT_ROOT"] . "/bitrix/crontab/crontab.cfg", $arRetval, $return_var);
                }
            }

            //agent
            $dateAgent = new DateTime();
            $intAgent = new DateInterval('PT60S'); // PT60S - 60 sec;
            $dateAgent->add($intAgent);

            CAgent::AddAgent(
                    "ICrmOrderActions::orderAgent();", $this->MODULE_ID, "N", 600, // interval - 10 mins
                    $dateAgent->format('d.m.Y H:i:s'), // date of first check
                    "Y", // agent is active
                    $dateAgent->format('d.m.Y H:i:s'), // date of first start
                    30
            );

            $api_host = COption::GetOptionString($this->MODULE_ID, $this->CRM_API_HOST_OPTION, 0);
            $api_key = COption::GetOptionString($this->MODULE_ID, $this->CRM_API_KEY_OPTION, 0);
            $this->INTARO_CRM_API = new \RetailCrm\RestApi($api_host, $api_key);
            try {
                $this->INTARO_CRM_API->statisticUpdate();
            } catch (\RetailCrm\Exception\CurlException $e) {
                ICrmOrderActions::eventLog(
                    'intaro.crm/install/index.php', 'RetailCrm\RestApi::statisticUpdate::CurlException',
                    $e->getCode() . ': ' . $e->getMessage()
                );
            }
            // in fin order
            COption::SetOptionString($this->MODULE_ID, $this->CRM_ORDER_HISTORY_DATE, date('Y-m-d H:i:s'));

            $APPLICATION->IncludeAdminFile(
                    GetMessage('MODULE_INSTALL_TITLE'), 
                    $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/step6.php'
            );
        }
    }

    function DoUninstall() {
        global $APPLICATION;

        CAgent::RemoveAgent("ICrmOrderActions::uploadOrdersAgent();", $this->MODULE_ID);
        CAgent::RemoveAgent("ICrmOrderActions::orderHistoryAgent();", $this->MODULE_ID);
        CAgent::RemoveAgent("ICrmOrderActions::orderAgent();", $this->MODULE_ID);

        COption::RemoveOption($this->MODULE_ID, $this->CRM_API_HOST_OPTION);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_API_KEY_OPTION);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_DELIVERY_TYPES_ARR);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_PAYMENT_TYPES);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_PAYMENT_STATUSES);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_PAYMENT);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_ORDER_LAST_ID);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_ORDER_SITES);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_ORDER_PROPS);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_LEGAL_DETAILS);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_CONTRAGENT_TYPE);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_CUSTOM_FIELDS);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_SITES_LIST);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_ORDER_DISCHARGE);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_ORDER_FAILED_IDS);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_ORDER_HISTORY_DATE);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_CATALOG_BASE_PRICE);

        UnRegisterModuleDependences("sale", "OnSalePayOrder", $this->MODULE_ID, "ICrmOrderEvent", "onSalePayOrder");
        UnRegisterModuleDependences("sale", "OnSaleCancelOrder", $this->MODULE_ID, "ICrmOrderEvent", "onSaleCancelOrder");
        UnRegisterModuleDependences("sale", "OnOrderNewSendEmail", $this->MODULE_ID, "ICrmOrderEvent", "onSendOrderMail");
        UnRegisterModuleDependences("sale", "OnOrderUpdate", $this->MODULE_ID, "ICrmOrderEvent", "onUpdateOrder");
        UnRegisterModuleDependences("sale", "OnBeforeOrderAdd", $this->MODULE_ID, "ICrmOrderEvent", "onBeforeOrderAdd");
        UnRegisterModuleDependences("sale", "OnBeforeOrderAccountNumberSet", $this->MODULE_ID, "ICrmOrderEvent", "onBeforeOrderAccountNumberSet");
        UnRegisterModuleDependences("sale", "OnSaleBeforeReserveOrder", $this->MODULE_ID, "ICrmOrderEvent", "onSaleBeforeReserveOrder");
        UnRegisterModuleDependences("sale", "OnSaleReserveOrder", $this->MODULE_ID, "ICrmOrderEvent", "onSaleReserveOrder");
        if (CModule::IncludeModule("catalog")) {
            if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/bitrix/php_interface/include/catalog_export/' . $this->INTARO_CRM_EXPORT . '_run.php')) {
                $dbProfile = CCatalogExport::GetList(array(), array("FILE_NAME" => $this->INTARO_CRM_EXPORT));

                while ($arProfile = $dbProfile->Fetch()) {
                    if ($arProfile["DEFAULT_PROFILE"] != "Y") {
                        CAgent::RemoveAgent("CCatalogExport::PreGenerateExport(" . $arProfile['ID'] . ");", "catalog");
                        CCatalogExport::Delete($arProfile['ID']);
                    }
                }
            }
        }

        $this->DeleteFiles();

        UnRegisterModule($this->MODULE_ID);

        $APPLICATION->IncludeAdminFile(
                GetMessage('MODULE_UNINSTALL_TITLE'), $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/unstep1.php'
        );
    }

    function CopyFiles() {
        CopyDirFiles(
                $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/export/', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/php_interface/include/catalog_export/', true, true
        );

        $rsSites = CSite::GetList($by, $sort, array('DEF' => 'Y'));
        $defaultSite = array();
        while ($ar = $rsSites->Fetch()) {
            $defaultSite = $ar;
            break;
        }

        if(mkdir($defaultSite['ABS_DOC_ROOT'] . '/retailcrm/')) {
            CopyDirFiles(
                $defaultSite['ABS_DOC_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/retailcrm/', $defaultSite['ABS_DOC_ROOT'] . '/retailcrm/', true, true
            );
        }
    }

    function DeleteFiles() {
        $rsSites = CSite::GetList($by, $sort, array('DEF' => 'Y'));
        $defaultSite = array();
        while ($ar = $rsSites->Fetch()) {
            $defaultSite = $ar;
            break;
        }

        unlink($_SERVER['DOCUMENT_ROOT'] . '/bitrix/php_interface/include/catalog_export/intarocrm_run.php');
        unlink($_SERVER['DOCUMENT_ROOT'] . '/bitrix/php_interface/include/catalog_export/intarocrm_setup.php');
        unlink($defaultSite['ABS_DOC_ROOT'] . '/retailcrm/agent.php');
        rmdir($defaultSite['ABS_DOC_ROOT'] . '/retailcrm/');
    }

    function GetProfileSetupVars($iblocks, $propertiesProduct, $propertiesUnitProduct, $propertiesSKU, $propertiesUnitSKU, $filename) {
        // Get string like IBLOCK_EXPORT[0]=3&
        // IBLOCK_EXPORT[1]=6&
        // IBLOCK_PROPERTY_ARTICLE[0]=ARTICLE&
        // IBLOCK_PROPERTY_ARTICLE[1]=ARTNUMBER&
        // SETUP_FILE_NAME=%2Fbitrix%2Fcatalog_export%2Ftestintarocrm.xml

        //$arProfileFields = explode(",", $SETUP_FIELDS_LIST);
        $strVars = "";
        foreach ($iblocks as $key => $val) 
            $strVars .= 'IBLOCK_EXPORT[' . $key . ']=' . $val . '&';
        foreach ($propertiesSKU as $iblock => $arr) 
            foreach ($arr as $id => $val)
                $strVars .= 'IBLOCK_PROPERTY_SKU_' . $id . '[' . $iblock . ']=' . $val . '&';
        foreach ($propertiesUnitSKU as $iblock => $arr) 
            foreach ($arr as $id => $val)
                $strVars .= 'IBLOCK_PROPERTY_UNIT_SKU_' . $id . '[' . $iblock . ']=' . $val . '&';
        foreach ($propertiesProduct as $iblock => $arr) 
            foreach ($arr as $id => $val)
                $strVars .= 'IBLOCK_PROPERTY_PRODUCT_' . $id . '[' . $iblock . ']=' . $val . '&';
        foreach ($propertiesUnitProduct as $iblock => $arr) 
            foreach ($arr as $id => $val)
                $strVars .= 'IBLOCK_PROPERTY_UNIT_PRODUCT_' . $id . '[' . $iblock . ']=' . $val . '&';
        
        $strVars .= 'SETUP_FILE_NAME=' . urlencode($filename);
        
        return $strVars;
    }
}