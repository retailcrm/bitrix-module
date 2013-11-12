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
    var $CRM_ORDER_TYPES_ARR = 'order_types_arr';
    var $CRM_DELIVERY_TYPES_ARR = 'deliv_types_arr';
    var $CRM_PAYMENT_TYPES = 'pay_types_arr';
    var $CRM_PAYMENT_STATUSES = 'pay_statuses_arr';
    var $CRM_PAYMENT = 'payment_arr'; //order payment Y/N
    var $CRM_ORDER_LAST_ID = 'order_last_id';
    var $CRM_ORDER_SITES = 'sites_ids';
    var $CRM_ORDER_PROPS = 'order_props';
    var $CRM_ORDER_DISCHARGE = 'order_discharge';
    var $CRM_ORDER_FAILED_IDS = 'order_failed_ids';
    var $CRM_ORDER_HISTORY_DATE = 'order_history_date';
    var $INSTALL_PATH;

    function intaro_intarocrm() {
        $arModuleVersion = array();
        $path = str_replace("\\", "/", __FILE__);
        $path = substr($path, 0, strlen($path) - strlen("/index.php"));
        $this->INSTALL_PATH = $path;
        include($path . "/version.php");
        $this->MODULE_VERSION = $arModuleVersion["VERSION"];
        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        $this->MODULE_NAME = GetMessage('MODULE_NAME');
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
        include($this->INSTALL_PATH . '/../classes/general/ICrmOrderActions.php');
        include($this->INSTALL_PATH . '/../classes/general/ICMLLoader.php');

        $step = intval($_REQUEST['step']);

        $arResult['orderProps'] = array(
            array(
                'NAME' => GetMessage('FIO'),
                'ID'   => 'fio'
            ),
            array(
                'NAME' => GetMessage('PHONE'),
                'ID'   => 'phone'
            ),
            array(
                'NAME' => GetMessage('EMAIL'),
                'ID'   => 'email'
            ),
            array(
                'NAME' => GetMessage('ADDRESS'),
                'ID'   => 'text'
            ),
            // address
            /* array(
              'NAME' => GetMessage('COUNTRY'),
              'ID'   => 'country'
              ),
              array(
              'NAME' => GetMessage('REGION'),
              'ID'   => 'region'
              ),
              array(
              'NAME' => GetMessage('CITY'),
              'ID'   => 'city'
              ), */
            array(
                'NAME' => GetMessage('ZIP'),
                'ID'   => 'index'
            ),
            array(
                'NAME' => GetMessage('STREET'),
                'ID'   => 'street'
            ),
            array(
                'NAME' => GetMessage('BUILDING'),
                'ID'   => 'building'
            ),
            array(
                'NAME' => GetMessage('FLAT'),
                'ID'   => 'flat'
            ),
            array(
                'NAME' => GetMessage('INTERCOMCODE'),
                'ID'   => 'intercomcode'
            ),
            array(
                'NAME' => GetMessage('FLOOR'),
                'ID'   => 'floor'
            ),
            array(
                'NAME' => GetMessage('BLOCK'),
                'ID'   => 'block'
            ),
            array(
                'NAME' => GetMessage('HOUSE'),
                'ID'   => 'house'
            )
        );

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
            $rsSites = CSite::GetList($by, $sort, array());
            while ($ar = $rsSites->Fetch())
                $arResult['arSites'][] = $ar;

            $APPLICATION->IncludeAdminFile(
                    GetMessage('MODULE_INSTALL_TITLE'), $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/step1.php'
            );
        } else if ($step == 2) {

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
            $rsSites = CSite::GetList($by, $sort, array());
            while ($ar = $rsSites->Fetch())
                $arResult['arSites'][] = $ar;

            if (isset($arResult['errCode']) && $arResult['errCode']) {
                $APPLICATION->IncludeAdminFile(
                        GetMessage('MODULE_INSTALL_TITLE'), 
                        $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/step1.php'
                );
                return;
            }

            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && (strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') && isset($_POST['ajax']) && ($_POST['ajax'] == 1)) {

                $api_host = COption::GetOptionString($this->MODULE_ID, $this->CRM_API_HOST_OPTION, 0);
                $api_key = COption::GetOptionString($this->MODULE_ID, $this->CRM_API_KEY_OPTION, 0);
                $this->INTARO_CRM_API = new \IntaroCrm\RestApi($api_host, $api_key);

                //prepare crm lists
                $arResult['orderTypesList'] = $this->INTARO_CRM_API->orderTypesList();

                if ((int) $this->INTARO_CRM_API->getStatusCode() != 200) {
                    $APPLICATION->RestartBuffer();
                    header('Content-Type: application/x-javascript; charset=' . LANG_CHARSET);
                    die(json_encode(array("success" => false)));
                }

                $arResult['deliveryTypesList'] = $this->INTARO_CRM_API->deliveryTypesList();
                $arResult['paymentTypesList'] = $this->INTARO_CRM_API->paymentTypesList();
                $arResult['paymentStatusesList'] = $this->INTARO_CRM_API->paymentStatusesList(); // --statuses
                $arResult['paymentList'] = $this->INTARO_CRM_API->orderStatusesList();
                $arResult['paymentGroupList'] = $this->INTARO_CRM_API->orderStatusGroupsList(); // -- statuses groups
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

                COption::SetOptionString($this->MODULE_ID, $this->CRM_ORDER_TYPES_ARR, serialize($orderTypesArr));
                COption::SetOptionString($this->MODULE_ID, $this->CRM_DELIVERY_TYPES_ARR, serialize($deliveryTypesArr));
                COption::SetOptionString($this->MODULE_ID, $this->CRM_PAYMENT_TYPES, serialize($paymentTypesArr));
                COption::SetOptionString($this->MODULE_ID, $this->CRM_PAYMENT_STATUSES, serialize($paymentStatusesArr));
                COption::SetOptionString($this->MODULE_ID, $this->CRM_PAYMENT, serialize($paymentArr));

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

            $api_host = htmlspecialchars(trim($_POST[$this->CRM_API_HOST_OPTION]));
            $api_key = htmlspecialchars(trim($_POST[$this->CRM_API_KEY_OPTION]));

            // empty == select all
            $orderSites = array();
            /* foreach ($_POST[$this->CRM_ORDER_SITES] as $site) {
                $orderSites[] = htmlspecialchars(trim($site));
            } */

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

            $this->INTARO_CRM_API = new \IntaroCrm\RestApi($api_host, $api_key);

            $this->INTARO_CRM_API->paymentStatusesList();

            //check connection & apiKey valid
            if ((int) $this->INTARO_CRM_API->getStatusCode() != 200) {
                $arResult['errCode'] = 'ERR_' . $this->INTARO_CRM_API->getStatusCode();

                $APPLICATION->IncludeAdminFile(
                        GetMessage('MODULE_INSTALL_TITLE'), 
                        $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/step1.php'
                );

                return;
            }

            COption::SetOptionString($this->MODULE_ID, $this->CRM_API_HOST_OPTION, $api_host);
            COption::SetOptionString($this->MODULE_ID, $this->CRM_API_KEY_OPTION, $api_key);
            COption::SetOptionString($this->MODULE_ID, $this->CRM_ORDER_SITES, serialize($orderSites));

            //prepare crm lists
            $arResult['orderTypesList'] = $this->INTARO_CRM_API->orderTypesList();
            $arResult['deliveryTypesList'] = $this->INTARO_CRM_API->deliveryTypesList();
            $arResult['paymentTypesList'] = $this->INTARO_CRM_API->paymentTypesList();
            $arResult['paymentStatusesList'] = $this->INTARO_CRM_API->paymentStatusesList(); // --statuses
            $arResult['paymentList'] = $this->INTARO_CRM_API->orderStatusesList();
            $arResult['paymentGroupList'] = $this->INTARO_CRM_API->orderStatusGroupsList(); // -- statuses groups
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
        } else if ($step == 3) {
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
            $this->INTARO_CRM_API = new \IntaroCrm\RestApi($api_host, $api_key);

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

            //form delivery types ids arr
            $deliveryTypesArr = array();

            if (htmlspecialchars(trim($_POST['delivery-types-export'])) == 'false') {
                if ($arDeliveryTypesList = $dbDeliveryTypesList->Fetch()) {
                    do {
                        $deliveryTypesArr[$arDeliveryTypesList['ID']] = htmlspecialchars(trim($_POST['delivery-type-' . $arDeliveryTypesList['ID']]));
                    } while ($arDeliveryTypesList = $dbDeliveryTypesList->Fetch());
                }
            } elseif (htmlspecialchars(trim($_POST['delivery-types-export'])) == 'true') {
                // send to intaro crm and save
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
                        $this->INTARO_CRM_API->deliveryTypeEdit(ICrmOrderActions::clearArr(array(
                                    'code' => $resultDeliveryTypeId,
                                    'name' => ICrmOrderActions::toJSON($arDeliveryTypesList['NAME']),
                                    'defaultCost' => $arDeliveryTypesList['PRICE'],
                                    'description' => ICrmOrderActions::toJSON($arDeliveryTypesList['DESCRIPTION']),
                                    'paymentTypes' => ''
                        )));

                        // error pushing customer
                        if ($this->INTARO_CRM_API->getStatusCode() != 200) {
                            if ($this->INTARO_CRM_API->getStatusCode() != 201) {
                                //handle err
                                ICrmOrderActions::eventLog('install/index.php', 'IntaroCrm\RestApi::deliveryTypeEdit', $this->INTARO_CRM_API->getLastError());
                            }
                        }
                    } while ($arDeliveryTypesList = $dbDeliveryTypesList->Fetch());
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

            COption::SetOptionString($this->MODULE_ID, $this->CRM_ORDER_TYPES_ARR, serialize($orderTypesArr));
            COption::SetOptionString($this->MODULE_ID, $this->CRM_DELIVERY_TYPES_ARR, serialize($deliveryTypesArr));
            COption::SetOptionString($this->MODULE_ID, $this->CRM_PAYMENT_TYPES, serialize($paymentTypesArr));
            COption::SetOptionString($this->MODULE_ID, $this->CRM_PAYMENT_STATUSES, serialize($paymentStatusesArr));
            COption::SetOptionString($this->MODULE_ID, $this->CRM_PAYMENT, serialize($paymentArr));
            COption::SetOptionString($this->MODULE_ID, $this->CRM_ORDER_LAST_ID, 0);
            COption::SetOptionString($this->MODULE_ID, $this->CRM_ORDER_DISCHARGE, 0);
            COption::SetOptionString($this->MODULE_ID, $this->CRM_ORDER_FAILED_IDS, serialize(array()));
            COption::SetOptionString($this->MODULE_ID, $this->CRM_ORDER_HISTORY_DATE, date('Y-m-d H:i:s'));
            
            $APPLICATION->IncludeAdminFile(
                    GetMessage('MODULE_INSTALL_TITLE'),
                    $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/step3.php'
            );
        } else if ($step == 4) {
            if (!CModule::IncludeModule("sale")) {
                //handler
            }

            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && (strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
                    && isset($_POST['ajax']) && ($_POST['ajax'] == 1)) {
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
            
            COption::SetOptionString($this->MODULE_ID, $this->CRM_ORDER_PROPS, serialize($orderPropsArr));
                       
            $APPLICATION->IncludeAdminFile(
                    GetMessage('MODULE_INSTALL_TITLE'),
                    $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/step4.php'
            );

        } else if ($step == 5) {
            if (!CModule::IncludeModule("iblock")) {
                $arResult['errCode'] = 'ERR_IBLOCK';
            }

            if (!CModule::IncludeModule("catalog")) {
                $arResult['errCode'] = 'ERR_CATALOG';
            }
            $APPLICATION->IncludeAdminFile(
                    GetMessage('MODULE_INSTALL_TITLE'),
                    $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/step5.php'
            );
        } else if ($step == 6) {

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
                );
            
            $propertiesSKU = array();
            foreach ($iblockProperties as $prop) {
                foreach ($_POST['IBLOCK_PROPERTY_SKU'. '_' . $prop] as $iblock => $val) {
                    $propertiesSKU[$iblock][$prop] = $val;
                }
            }
            
            $propertiesProduct = array();
            foreach ($iblockProperties as $prop) {
                foreach ($_POST['IBLOCK_PROPERTY_PRODUCT'. '_' . $prop] as $iblock => $val) {
                    $propertiesProduct[$iblock][$prop] = $val;
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
                    'IBLOCK_PROPERTY_PRODUCT' => $propertiesProduct,
                    'SETUP_FILE_NAME' => $filename,
                    'SETUP_PROFILE_NAME' => $profileName
                );
                global $oldValues;
                $oldValues = $arOldValues;
                $APPLICATION->IncludeAdminFile(
                    GetMessage('MODULE_INSTALL_TITLE'),
                    $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/step4.php'
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
            $this->CopyFiles();
            if (isset($_POST['LOAD_NOW'])) {

                $loader = new ICMLLoader();
                $loader->iblocks = $iblocks;
                $loader->propertiesProduct = $propertiesProduct;
                $loader->propertiesSKU = $propertiesSKU;
                $loader->filename = $filename;
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
                $ar = $this->GetProfileSetupVars($iblocks, $propertiesProduct, $propertiesSKU, $filename);
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
                    "ICrmOrderActions::uploadOrdersAgent();", $this->MODULE_ID, "N", 600, // interval - 10 mins
                    $dateAgent->format('d.m.Y H:i:s'), // date of first check
                    "Y", // agent is active
                    $dateAgent->format('d.m.Y H:i:s'), // date of first start
                    30
            );
            
            CAgent::AddAgent(
                "ICrmOrderActions::orderHistoryAgent();",
                 $this->MODULE_ID,
                 "N",
                 600, // interval - 10 mins
                 $dateAgent->format('d.m.Y H:i:s'), // date of first check
                 "Y", // agent is active
                 $dateAgent->format('d.m.Y H:i:s'), // date of first start
                 30
            );

            $api_host = COption::GetOptionString($this->MODULE_ID, $this->CRM_API_HOST_OPTION, 0);
            $api_key = COption::GetOptionString($this->MODULE_ID, $this->CRM_API_KEY_OPTION, 0);
            $this->INTARO_CRM_API = new \IntaroCrm\RestApi($api_host, $api_key);
            $this->INTARO_CRM_API->statisticUpdate();

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

        COption::RemoveOption($this->MODULE_ID, $this->CRM_API_HOST_OPTION);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_API_KEY_OPTION);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_DELIVERY_TYPES_ARR);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_PAYMENT_TYPES);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_PAYMENT_STATUSES);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_PAYMENT);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_ORDER_LAST_ID);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_ORDER_SITES);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_ORDER_PROPS);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_ORDER_DISCHARGE);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_ORDER_FAILED_IDS);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_ORDER_HISTORY_DATE);

        UnRegisterModuleDependences("sale", "OnSalePayOrder", $this->MODULE_ID, "ICrmOrderEvent", "onSalePayOrder");
        UnRegisterModuleDependences("sale", "OnSaleCancelOrder", $this->MODULE_ID, "ICrmOrderEvent", "onSaleCancelOrder");
        UnRegisterModuleDependences("sale", "OnOrderNewSendEmail", $this->MODULE_ID, "ICrmOrderEvent", "onSendOrderMail");
        UnRegisterModuleDependences("sale", "OnOrderUpdate", $this->MODULE_ID, "ICrmOrderEvent", "onUpdateOrder");
        UnRegisterModuleDependences("sale", "OnBeforeOrderAdd", $this->MODULE_ID, "ICrmOrderEvent", "onBeforeOrderAdd");
        UnRegisterModuleDependences("sale", "OnBeforeOrderAccountNumberSet", $this->MODULE_ID, "ICrmOrderEvent", "onBeforeOrderAccountNumberSet");
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
    }

    function DeleteFiles() {
        unlink($_SERVER['DOCUMENT_ROOT'] . '/bitrix/php_interface/include/catalog_export/intarocrm_run.php');
        unlink($_SERVER['DOCUMENT_ROOT'] . '/bitrix/php_interface/include/catalog_export/intarocrm_setup.php');
    }

    function GetProfileSetupVars($iblocks, $propertiesProduct, $propertiesSKU, $filename) {
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
        foreach ($propertiesProduct as $iblock => $arr) 
            foreach ($arr as $id => $val)
                $strVars .= 'IBLOCK_PROPERTY_PRODUCT_' . $id . '[' . $iblock . ']=' . $val . '&';
        
        $strVars .= 'SETUP_FILE_NAME=' . urlencode($filename);
        
        return $strVars;
    }
}