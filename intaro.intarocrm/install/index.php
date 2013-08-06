<?php
/**
 * Module Install/Uninstall script
 * Module name:	intaro.intarocrm
 * Class name:	intaro_intarocrm
 */

global $MESS;
IncludeModuleLangFile(__FILE__);
if (class_exists('intaro_intarocrm'))
    return;

class intaro_intarocrm extends CModule
{
    var $MODULE_ID = 'intaro.intarocrm';
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    var $MODULE_GROUP_RIGHTS = 'N';
    var $PARTNER_NAME;
    var $PARTNER_URI;
    var $INTARO_CRM_API;

    var $CRM_API_HOST_OPTION = 'api_host';
    var $CRM_API_KEY_OPTION = 'api_key';
    var $CRM_ORDER_TYPES_ARR = 'order_types_arr';
    var $CRM_DELIVERY_TYPES_ARR = 'deliv_types_arr';
    var $CRM_PAYMENT_TYPES = 'pay_types_arr';
    var $CRM_PAYMENT_STATUSES = 'pay_statuses_arr';
    var $CRM_PAYMENT = 'payment_arr'; //order payment Y/N
    var $CRM_ORDER_LAST_ID = 'order_last_id';
    var $CRM_ORDER_PROPS = 'order_props';


    var $INSTALL_PATH;

    function intaro_intarocrm()
    {
        $arModuleVersion = array();
        $path = str_replace("\\", "/", __FILE__);
        $path = substr($path, 0, strlen($path) - strlen("/index.php"));
        $this->INSTALL_PATH = $path;
        include($path."/version.php");
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

    function DoInstall()
    {
        global $APPLICATION, $step, $arResult;

		if (!in_array('curl', get_loaded_extensions())) {
			$APPLICATION->ThrowException( GetMessage("INTAROCRM_CURL_ERR") );
			return false;
		}

        include($this->INSTALL_PATH . '/../classes/general/RestApi.php');
        include($this->INSTALL_PATH . '/../classes/general/ICrmOrderActions.php');

        $step = intval($_REQUEST['step']);

        if ($step <= 1) {
            if(!CModule::IncludeModule("sale")) {
                $arResult['errCode'] = 'ERR_SALE';
            }

            if(!CModule::IncludeModule("iblock")) {
                $arResult['errCode'] = 'ERR_IBLOCK';
            }

            if(!CModule::IncludeModule("catalog")) {
                $arResult['errCode'] = 'ERR_CATALOG';
            }

            $APPLICATION->IncludeAdminFile(
                GetMessage('MODULE_INSTALL_TITLE'),
                $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/step1.php'
            );
        } else if ($step == 2) {
            if(!CModule::IncludeModule("sale")) {
                $arResult['errCode'] = 'ERR_SALE';
            }

            if(!CModule::IncludeModule("iblock")) {
                $arResult['errCode'] = 'ERR_IBLOCK';
            }

            if(!CModule::IncludeModule("catalog")) {
                $arResult['errCode'] = 'ERR_CATALOG';
            }

            if(isset($arResult['errCode']) && $arResult['errCode']) {
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
            $api_host = $api_host['scheme'] . '://' . $api_host['host'];

            if(!$api_host || !$api_key) {
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
            if((int) $this->INTARO_CRM_API->getStatusCode() != 200) {
                $arResult['errCode'] = 'ERR_' . $this->INTARO_CRM_API->getStatusCode();

                $APPLICATION->IncludeAdminFile(
                    GetMessage('MODULE_INSTALL_TITLE'),
                    $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/step1.php'
                );

                return;
            }

            COption::SetOptionString($this->MODULE_ID, $this->CRM_API_HOST_OPTION, $api_host);
            COption::SetOptionString($this->MODULE_ID, $this->CRM_API_KEY_OPTION, $api_key);

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
                ),
                array(
                    "ACTIVE" => "Y",
                ),
                false,
                false,
                array()
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
                ),
                array(
                    "ACTIVE" => "Y",
                ),
                false,
                false,
                array()
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
                ),
                array(
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
                ),
                array(
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
                'ID'   => 'Y',
                'NAME' => GetMessage('CANCELED')
            );

            $APPLICATION->IncludeAdminFile(
                GetMessage('MODULE_INSTALL_TITLE'),
                $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/step2.php'
            );

        } else if ($step == 3) {
            if(!CModule::IncludeModule("sale")) {
                //handler
            }

            if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && (strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
                    && isset($_POST['ajax']) && ($_POST['ajax'] == 1)) {
                ICrmOrderActions::uploadOrders(true); // each 50

                $lastUpOrderId = COption::GetOptionString($this->MODULE_ID, $this->CRM_ORDER_LAST_ID, 0);
                $countLeft = (int) CSaleOrder::GetList(array("ID" => "ASC"), array('>ID' => $lastUpOrderId), array());
                $countAll = (int) CSaleOrder::GetList(array("ID" => "ASC"), array(), array());

                if(!isset($_POST['finish']))
                    $finish = 0;
                else
                    $finish = (int) $_POST['finish'];

                $percent = 100 - round(($countLeft * 100 / $countAll), 1);

                if(!$countLeft) {
                    $api_host = COption::GetOptionString($mid, $this->CRM_API_HOST_OPTION, 0);
                    $api_key = COption::GetOptionString($mid, $this->CRM_API_KEY_OPTION, 0);
                    $this->INTARO_CRM_API = new \IntaroCrm\RestApi($api_host, $api_key);
                    $this->INTARO_CRM_API->statisticUpdate();
                    $finish = 1;
                }


                $APPLICATION->RestartBuffer();
		header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
		die(json_encode(array("finish" => $finish, "percent" => $percent)));
            }

            if (isset($_POST['back']) && $_POST['back']) {
                $APPLICATION->IncludeAdminFile(
                   GetMessage('MODULE_INSTALL_TITLE'),
                   $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/step1.php'
                );
            }

            //bitrix orderTypesList -- personTypes
            $dbOrderTypesList = CSalePersonType::GetList(
                array(
                    "SORT" => "ASC",
                    "NAME" => "ASC"
                ),
                array(
                    "ACTIVE" => "Y",
                ),
                false,
                false,
                array()
            );

            //form order types ids arr
            $orderTypesArr = array();
            if ($arOrderTypesList = $dbOrderTypesList->Fetch()) {
                do {
                    $orderTypesArr[$arOrderTypesList['ID']] = htmlspecialchars(trim($_POST['order-type-' . $arOrderTypesList['ID']]));
                } while ($arOrderTypesList = $dbOrderTypesList->Fetch());
            }

            //bitrix deliveryTypesList
            $dbDeliveryTypesList = CSaleDelivery::GetList(
                array(
                    "SORT" => "ASC",
                    "NAME" => "ASC"
                ),
                array(
                    "ACTIVE" => "Y",
                ),
                false,
                false,
                array()
            );

            //form delivery types ids arr
            $deliveryTypesArr = array();
            if ($arDeliveryTypesList = $dbDeliveryTypesList->Fetch()) {
                do {
                    $deliveryTypesArr[$arDeliveryTypesList['ID']] = htmlspecialchars(trim($_POST['delivery-type-' . $arDeliveryTypesList['ID']]));
                } while ($arDeliveryTypesList = $dbDeliveryTypesList->Fetch());
            }

            //bitrix paymentTypesList
            $dbPaymentTypesList = CSalePaySystem::GetList(
                array(
                    "SORT" => "ASC",
                    "NAME" => "ASC"
                ),
                array(
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
                ),
                array(
                    "LID" => "ru", //ru
                    "ACTIVE" => "Y"
                )
            );

            //form payment statuses ids arr
            $paymentStatusesArr['Y'] = htmlspecialchars(trim($_POST['payment-status-Y']));

            if ($arPaymentStatusesList = $dbPaymentStatusesList->Fetch()) {
                do {
                    $paymentStatusesArr[$arPaymentStatusesList['ID']] = htmlspecialchars(trim($_POST['payment-status-' . $arPaymentStatusesList['ID']]));
                } while ($arPaymentStatusesList = $dbPaymentStatusesList->Fetch());
            }

            //form payment ids arr
            $paymentArr = array();
            $paymentArr['Y'] = htmlspecialchars(trim($_POST['payment-Y']));
            $paymentArr['N'] = htmlspecialchars(trim($_POST['payment-N']));
            
            // orderProps assoc arr
            $orderPropsArr = array(
                'fio'   => 'FIO',
                'index' => 'ZIP',
                'text'  => 'ADDRESS',
                'phone' => 'PHONE',
                'email' => 'EMAIL'
            );

            COption::SetOptionString($this->MODULE_ID, $this->CRM_ORDER_TYPES_ARR, serialize($orderTypesArr));
            COption::SetOptionString($this->MODULE_ID, $this->CRM_DELIVERY_TYPES_ARR, serialize($deliveryTypesArr));
            COption::SetOptionString($this->MODULE_ID, $this->CRM_PAYMENT_TYPES, serialize($paymentTypesArr));
            COption::SetOptionString($this->MODULE_ID, $this->CRM_PAYMENT_STATUSES, serialize($paymentStatusesArr));
            COption::SetOptionString($this->MODULE_ID, $this->CRM_PAYMENT, serialize($paymentArr));
            COption::SetOptionString($this->MODULE_ID, $this->CRM_ORDER_LAST_ID, 0);
            COption::SetOptionString($this->MODULE_ID, $this->CRM_ORDER_PROPS, serialize($orderPropsArr));

            $APPLICATION->IncludeAdminFile(
                GetMessage('MODULE_INSTALL_TITLE'),
                $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/step3.php'
            );
        } else if ($step == 4) {

            RegisterModule($this->MODULE_ID);
            RegisterModuleDependences("sale", "OnSaleCancelOrder", $this->MODULE_ID, "ICrmOrderEvent", "onSaleCancelOrder");

            //agent
            $dateAgent = new DateTime();
            $intAgent = new DateInterval('PT60S'); // PT60S - 60 sec;
            $dateAgent->add($intAgent);

            CAgent::AddAgent(
                "ICrmOrderActions::uploadOrdersAgent();",
                 $this->MODULE_ID,
                 "N",
                 600, // interval - 10 mins
                 $dateAgent->format('d.m.Y H:i:s'), // date of first check
                 "Y", // агент активен
                 $dateAgent->format('d.m.Y H:i:s'), // date of first start
                 30
            );

            $this->CopyFiles();

            $APPLICATION->IncludeAdminFile(
                GetMessage('MODULE_INSTALL_TITLE'),
                $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/step4.php'
            );
        }
    }

    function DoUninstall() {
        global $APPLICATION;

	CAgent::RemoveAgent("ICrmOrderActions::uploadOrdersAgent();", $this->MODULE_ID);

        COption::RemoveOption($this->MODULE_ID, $this->CRM_API_HOST_OPTION);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_API_KEY_OPTION);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_DELIVERY_TYPES_ARR);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_PAYMENT_TYPES);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_PAYMENT_STATUSES);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_PAYMENT);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_ORDER_LAST_ID);
        UnRegisterModuleDependences("sale", "OnSaleCancelOrder", $this->MODULE_ID, "ICrmOrderEvent", "onSaleCancelOrder");
        
        $this->DeleteFiles();
        
        UnRegisterModule($this->MODULE_ID);

        $APPLICATION->IncludeAdminFile(
            GetMessage('MODULE_UNINSTALL_TITLE'),
            $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/unstep1.php'
        );

    }

    function CopyFiles() {
        CopyDirFiles(
            $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/export/',
            $_SERVER['DOCUMENT_ROOT'] . '/bitrix/php_interface/include/catalog_export/',
            true, true
        );
    }

    function DeleteFiles() {
        unlink($_SERVER['DOCUMENT_ROOT'] . '/bitrix/php_interface/include/catalog_export/intarocrm_run.php');
        unlink($_SERVER['DOCUMENT_ROOT'] . '/bitrix/php_interface/include/catalog_export/intarocrm_setup.php');
    }
}