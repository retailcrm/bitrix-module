<?php
/**
 * Module Install/Uninstall script
 * Module name:	intaro.crm
 * Class name:	intaro_crm
 */

global $MESS;
IncludeModuleLangFile(__FILE__);
if (class_exists('intaro_crm'))
    return;

class intaro_crm extends CModule 
{
    var $MODULE_ID = 'intaro.crm';
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
    
    var $INSTALL_PATH;
    
    function intaro_crm()
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
        
        include($this->INSTALL_PATH . '/../classes/general/ICrmApi.php');
        
        $step = intval($_REQUEST['step']);
            
        if ($step <= 1) {  
            $APPLICATION->IncludeAdminFile(
                GetMessage('MODULE_INSTALL_TITLE'), 
                $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/step1.php'
            );
        } else if ($step == 2) {
            if(!CModule::IncludeModule("sale")) {
                //handler
            }
            
            $api_host = htmlspecialchars(trim($_POST[$this->CRM_API_HOST_OPTION]));
            $api_key = htmlspecialchars(trim($_POST[$this->CRM_API_KEY_OPTION]));
            
            if(!$api_host || !$api_key) { 
                $arResult['errCode'] = 'ERR_FIELDS_API_HOST';
                $APPLICATION->IncludeAdminFile(
                    GetMessage('MODULE_INSTALL_TITLE'),
                    $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/step1.php'
                );
                return;
            }
            
            $this->INTARO_CRM_API = new ICrmApi($api_host, $api_key);
            
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
            $arResult['paymentStatusesList'] = $this->INTARO_CRM_API->paymentStatusesList();
            
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
        
            if ($arPaymentStatusesList = $dbPaymentStatusesList->Fetch()) {
                do {
                    $arResult['bitrixPaymentStatusesList'][] = $arPaymentStatusesList;     
                } while ($arPaymentStatusesList = $dbPaymentStatusesList->Fetch());
            }
            
			$APPLICATION->IncludeAdminFile(
                GetMessage('MODULE_INSTALL_TITLE'), 
                $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/step2.php'
            );
          
		} else if ($step == 3) {
            if(!CModule::IncludeModule("sale")) {
                //handler
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
                    $orderTypesArr[$arOrderTypesList['ID']] = $_POST['order-type-' . $arOrderTypesList['ID']];     
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
                    $deliveryTypesArr[$arDeliveryTypesList['ID']] = $_POST['delivery-type-' . $arDeliveryTypesList['ID']];   
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
                    $paymentTypesArr[$arPaymentTypesList['ID']] = $_POST['payment-type-' . $arPaymentTypesList['ID']];         
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
            $paymentStatusesArr = array();
            if ($arPaymentStatusesList = $dbPaymentStatusesList->Fetch()) {
                do {
                    $paymentStatusesArr[$arPaymentStatusesList['ID']] = $_POST['payment-status-' . $arPaymentStatusesList['ID']];     
                } while ($arPaymentStatusesList = $dbPaymentStatusesList->Fetch());
            }
		    
            COption::SetOptionString($this->MODULE_ID, $this->CRM_ORDER_TYPES_ARR, serialize($orderTypesArr));
		    COption::SetOptionString($this->MODULE_ID, $this->CRM_DELIVERY_TYPES_ARR, serialize($deliveryTypesArr));
            COption::SetOptionString($this->MODULE_ID, $this->CRM_PAYMENT_TYPES, serialize($paymentTypesArr));
            COption::SetOptionString($this->MODULE_ID, $this->CRM_PAYMENT_STATUSES, serialize($paymentStatusesArr));
            RegisterModule($this->MODULE_ID);
            
            $APPLICATION->IncludeAdminFile(
                GetMessage('MODULE_INSTALL_TITLE'), 
                $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/step3.php'
            );
        } 
    }

    function DoUninstall() {
        global $APPLICATION;
		
        UnRegisterModule($this->MODULE_ID);
			
        COption::RemoveOption($this->MODULE_ID, $this->CRM_API_HOST_OPTION);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_API_KEY_OPTION);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_DELIVERY_TYPES_ARR);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_PAYMENT_TYPES);
        COption::RemoveOption($this->MODULE_ID, $this->CRM_PAYMENT_STATUSES);
		
        $APPLICATION->IncludeAdminFile(
            GetMessage('MODULE_UNINSTALL_TITLE'), 
            $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/unstep1.php'
		);	
    }
}
?>
