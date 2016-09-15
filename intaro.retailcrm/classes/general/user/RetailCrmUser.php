<?php
IncludeModuleLangFile(__FILE__);
class RetailCrmUser
{
    public static function customerSend($arFields, $api, $contragentType, $send = false, $site = null){//только на создание  
        if(!$api || empty($contragentType)) { // add cond to check $arParams
            return false;
        }
        if (empty($arFields)) {
            RCrmActions::eventLog('ICrmOrderActions::orderCreate', 'empty($arFields)', 'incorrect customer');
            return false;
        }
       
        $customer = array(
            'externalId'     => $arFields['ID'],
            'firstName'      => $arFields['NAME'],
            'lastName'       => $arFields['LAST_NAME'],
            'patronymic'     => $arFields['SECOND_NAME'],
            'createdAt'      => new \DateTime($arFields['DATE_REGISTER']),
            'contragentType' => $contragentType
        );
        if(isset($arFields['PERSONAL_PHONE'])){
            $customer['phones'][]['number'] = $arFields['PERSONAL_PHONE'];
        }
        if(isset($arUser['WORK_PHONE'])){
            $customer['phones'][]['number'] = $arFields['WORK_PHONE'];
        }
        if(isset($_COOKIE['_rc']) && $_COOKIE['_rc'] != ''){
            $customer['browserId'] = $_COOKIE['_rc'];
        }

        if (function_exists('retailCrmBeforeCustomerSend')) {
            $newResCustomer = retailCrmBeforeCustomerSend($customer);
            if (is_array($newResCustomer) && !empty($newResCustomer)) {
                $customer = $newResCustomer;
            }
        }

        $normalizer = new RestNormalizer();
        $customer = $normalizer->normalize($customer, 'customers');
        
        $log = new Logger();
        $log->write($customer, 'customer');
  
        if($send) {
            if (!RCrmActions::apiMethod($api, 'customersCreate', __METHOD__, $customer, $site)) {
                return false;
            }
        }
 
        return $customer;
    }
    
}