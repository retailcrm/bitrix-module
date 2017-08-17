<?php
IncludeModuleLangFile(__FILE__);
class RetailCrmUser
{
    public static function customerSend($arFields, $api, $contragentType, $send = false, $site = null)
    {  
        if (!$api || empty($contragentType)) {
            return false;
        }
        if (empty($arFields)) {
            RCrmActions::eventLog('RetailCrmUser::customerSend', 'empty($arFields)', 'incorrect customer');
            return false;
        }
       
        $customer = array(
            'externalId'     => $arFields['ID'],
            'firstName'      => $arFields['NAME'],
            'lastName'       => $arFields['LAST_NAME'],
            'patronymic'     => $arFields['SECOND_NAME'],
            'email'          => $arFields['EMAIL'],
            'address'        => array('city' => $arFields['PERSONAL_CITY'], 'text' => $arFields['PERSONAL_STREET'], 'index' => $arFields['PERSONAL_ZIP']),
            'createdAt'      => new \DateTime($arFields['DATE_REGISTER']),
            'contragentType' => $contragentType
        );
        if (!empty($arFields['PERSONAL_PHONE'])) {
            $customer['phones'][]['number'] = $arFields['PERSONAL_PHONE'];
        }
        if (!empty($arFields['WORK_PHONE'])) {
            $customer['phones'][]['number'] = $arFields['WORK_PHONE'];
        }
        if ($send && isset($_COOKIE['_rc']) && $_COOKIE['_rc'] != '') {
            $customer['browserId'] = $_COOKIE['_rc'];
        }

        if (function_exists('retailCrmBeforeCustomerSend')) {
            $newResCustomer = retailCrmBeforeCustomerSend($customer);
            if (is_array($newResCustomer) && !empty($newResCustomer)) {
                $customer = $newResCustomer;
            } elseif ($newResCustomer === false) {
                RCrmActions::eventLog('RetailCrmUser::customerSend', 'retailCrmBeforeCustomerSend()', 'UserID = ' . $arFields['ID'] . '. Sending canceled after retailCrmBeforeCustomerSend');
                
                return false;
            }
        }

        $normalizer = new RestNormalizer();
        $customer = $normalizer->normalize($customer, 'customers');
        
        $log = new Logger();
        $log->write($customer, 'customer');
  
        if ($send) {
            if (!RCrmActions::apiMethod($api, 'customersCreate', __METHOD__, $customer, $site)) {
                return false;
            }
        }
 
        return $customer;
    }
    
    public static function customerEdit($arFields, $api, $optionsSitesList = array()){
        if (empty($arFields)) {
            RCrmActions::eventLog('RetailCrmUser::customerEdit', 'empty($arFields)', 'incorrect customer');
            return false;
        }
       
        $customer = array(
            'externalId'     => $arFields['ID'],
            'firstName'      => $arFields['NAME'],
            'lastName'       => $arFields['LAST_NAME'],
            'patronymic'     => $arFields['SECOND_NAME'],
            'email'          => $arFields['EMAIL'],
            'address'        => array('city' => $arFields['PERSONAL_CITY'], 'text' => $arFields['PERSONAL_STREET'], 'index' => $arFields['PERSONAL_ZIP']),
        );
        if (!empty($arFields['PERSONAL_PHONE'])) {
            $customer['phones'][]['number'] = $arFields['PERSONAL_PHONE'];
        }
        if (!empty($arFields['WORK_PHONE'])) {
            $customer['phones'][]['number'] = $arFields['WORK_PHONE'];
        }
        
        $found = false;
        if (count($optionsSitesList) > 0) {
            foreach ($optionsSitesList as $site) {
                $userCrm = RCrmActions::apiMethod($api, 'customersGet', __METHOD__, $arFields['ID'], $site);
                if (isset($userCrm['customer'])) {
                    $found = true;
                    break;
                }
            }
        } else {
            $site = null;
            $userCrm = RCrmActions::apiMethod($api, 'customersGet', __METHOD__, $arFields['ID'], $site);
            if (isset($userCrm['customer'])) {
                $found = true;
            }
        }

        if ($found) {
            $normalizer = new RestNormalizer();
            $customer = $normalizer->normalize($customer, 'customers');

            $log = new Logger();
            $log->write($customer, 'customer');
        
            if (function_exists('retailCrmBeforeCustomerSend')) {
                $newResCustomer = retailCrmBeforeCustomerSend($customer);
                if (is_array($newResCustomer) && !empty($newResCustomer)) {
                    $customer = $newResCustomer;
                } elseif ($newResCustomer === false) {
                    RCrmActions::eventLog('RetailCrmUser::customerEdit', 'retailCrmBeforeCustomerSend()', 'UserID = ' . $arFields['ID'] . '. Sending canceled after retailCrmBeforeCustomerSend');

                    return false;
                }
            }

            RCrmActions::apiMethod($api, 'customersEdit', __METHOD__, $customer, $site);
        }
        
        return true;
    }
}