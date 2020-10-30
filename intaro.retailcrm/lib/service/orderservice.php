<?php

/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Service
 * @author   retailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Service;

use Bitrix\Main\UserTable;
use CModule;
use RCrmActions;
use RetailCrm\ApiClient;
use RetailcrmConfigProvider;
use RetailCrmCorporateClient;
use RetailCrmOrder;
use RetailCrmUser;

/**
 * Class LoyaltyService
 *
 * @package Intaro\RetailCrm\Service
 */
class OrderService
{
    //TODO перевести запросы с массивов на модели
    /**
     * @param $event
     * @return bool
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function saveOrderInCRM($event): bool
    {
        if (true == $GLOBALS['ORDER_DELETE_USER_ADMIN']) {
            return false;
        }
        
        if ($GLOBALS['RETAILCRM_ORDER_OLD_EVENT'] === false
            && $GLOBALS['RETAILCRM_ORDER_DELETE'] === true
        ) {
            return false;
        }
        
        if ($GLOBALS['RETAIL_CRM_HISTORY'] === true) {
            return false;
        }
        
        if (!CModule::IncludeModule('iblock')) {
            RCrmActions::eventLog('RetailCrmEvent::orderSave', 'iblock', 'module not found');
            
            return false;
        }
        
        if (!CModule::IncludeModule("sale")) {
            RCrmActions::eventLog('RetailCrmEvent::orderSave', 'sale', 'module not found');
            
            return false;
        }
        
        if (!CModule::IncludeModule("catalog")) {
            RCrmActions::eventLog('RetailCrmEvent::orderSave', 'catalog', 'module not found');
            
            return false;
        }
        
        //exists getParameter("ENTITY")
        if (method_exists($event, 'getId')) {
            $obOrder = $event;
        } elseif (method_exists($event, 'getParameter')) {
            $obOrder = $event->getParameter("ENTITY");
        } else {
            RCrmActions::eventLog('RetailCrmEvent::orderSave', 'events', 'event error');
            
            return false;
        }
        
        $arOrder = RetailCrmOrder::orderObjToArr($obOrder);
        
        $api = new ApiClient(RetailcrmConfigProvider::getApiUrl(), RetailcrmConfigProvider::getApiKey());
        
        //params
        $optionsOrderTypes     = RetailcrmConfigProvider::getOrderTypes();
        $optionsDelivTypes     = RetailcrmConfigProvider::getDeliveryTypes();
        $optionsPayTypes       = RetailcrmConfigProvider::getPaymentTypes();
        $optionsPayStatuses    = RetailcrmConfigProvider::getPaymentStatuses(); // --statuses
        $optionsPayment        = RetailcrmConfigProvider::getPayment();
        $optionsSitesList      = RetailcrmConfigProvider::getSitesList();
        $optionsOrderProps     = RetailcrmConfigProvider::getOrderProps();
        $optionsLegalDetails   = RetailcrmConfigProvider::getLegalDetails();
        $optionsContragentType = RetailcrmConfigProvider::getContragentTypes();
        $optionsCustomFields   = RetailcrmConfigProvider::getCustomFields();
        
        //corp cliente swich
        $optionCorpClient = RetailcrmConfigProvider::getCorporateClientStatus();
        
        $arParams = RCrmActions::clearArr([
            'optionsOrderTypes'     => $optionsOrderTypes,
            'optionsDelivTypes'     => $optionsDelivTypes,
            'optionsPayTypes'       => $optionsPayTypes,
            'optionsPayStatuses'    => $optionsPayStatuses,
            'optionsPayment'        => $optionsPayment,
            'optionsOrderProps'     => $optionsOrderProps,
            'optionsLegalDetails'   => $optionsLegalDetails,
            'optionsContragentType' => $optionsContragentType,
            'optionsSitesList'      => $optionsSitesList,
            'optionsCustomFields'   => $optionsCustomFields,
        ]);
        
        //many sites?
        if ($optionsSitesList) {
            if (array_key_exists($arOrder['LID'], $optionsSitesList) && $optionsSitesList[$arOrder['LID']] !== null) {
                $site = $optionsSitesList[$arOrder['LID']];
            } else {
                return false;
            }
        } elseif (!$optionsSitesList) {
            $site = null;
        }
        
        //new order?
        $orderCrm = RCrmActions::apiMethod($api, 'ordersGet', __METHOD__, $arOrder['ID'], $site);
        if (isset($orderCrm['order'])) {
            $methodApi            = 'ordersEdit';
            $arParams['crmOrder'] = $orderCrm['order'];
        } else {
            $methodApi = 'ordersCreate';
        }
        
        $orderCompany = null;
        
        if ("Y" === $optionCorpClient && $optionsContragentType[$arOrder['PERSON_TYPE_ID']] === 'legal-entity') {
            //corparate cliente
            $nickName    = '';
            $address     = '';
            $corpAddress = '';
            $contragent  = [];
            $userCorp    = [];
            $corpName    = RetailcrmConfigProvider::getCorporateClientName();
            $corpAddress = RetailcrmConfigProvider::getCorporateClientAddress();
            
            foreach ($arOrder['PROPS']['properties'] as $prop) {
                if ($prop['CODE'] == $corpName) {
                    $nickName = $prop['VALUE'][0];
                }
                
                if ($prop['CODE'] == $corpAddress) {
                    $address = $prop['VALUE'][0];
                }
                
                if (!empty($optionsLegalDetails)
                    && $search = array_search($prop['CODE'], $optionsLegalDetails[$arOrder['PERSON_TYPE_ID']], true)
                ) {
                    $contragent[$search] = $prop['VALUE'][0];//legal order data
                }
            }
            
            $customersCorporate = false;
            $response           = $api->customersCorporateList(['companyName' => $nickName]);
            
            if ($response && $response->getStatusCode() === 200) {
                $customersCorporate = $response['customersCorporate'];
                $singleCorp         = reset($customersCorporate);
                
                if (!empty($singleCorp)) {
                    $userCorp['customerCorporate'] = $singleCorp;
                    $companiesResponse             = $api->customersCorporateCompanies(
                        $singleCorp['id'],
                        [],
                        null,
                        null,
                        'id',
                        $site
                    );
                    
                    if ($companiesResponse && $companiesResponse->isSuccessful()) {
                        $orderCompany = array_reduce(
                            $companiesResponse['companies'],
                            function ($carry, $item) use ($nickName) {
                                if (is_array($item) && $item['name'] == $nickName) {
                                    $carry = $item;
                                }
                                
                                return $carry;
                            },
                            null
                        );
                    }
                }
            } else {
                RCrmActions::eventLog(
                    'RetailCrmEvent::orderSave',
                    'ApiClient::customersCorporateList',
                    'error during fetching corporate customers'
                );
                
                return false;
            }
            
            //user
            $userCrm = RCrmActions::apiMethod($api, 'customersGet', __METHOD__, $arOrder['USER_ID'], $site);
            
            if (!isset($userCrm['customer'])) {
                $arUser = UserTable::getById($arOrder['USER_ID'])->fetch();
                
                if (!empty($address)) {
                    $arUser['PERSONAL_STREET'] = $address;
                }
                
                $resultUser = RetailCrmUser::customerSend($arUser, $api, "individual", true, $site);
                
                if (!$resultUser) {
                    RCrmActions::eventLog(
                        __CLASS__ . '::' . __METHOD__,
                        'RetailCrmUser::customerSend',
                        'error during creating customer'
                    );
                    
                    return false;
                }
                
                $userCrm = ['customer' => ['externalId' => $arOrder['USER_ID']]];
            }
            
            if (!isset($userCorp['customerCorporate'])) {
                $resultUserCorp = RetailCrmCorporateClient::clientSend(
                    $arOrder,
                    $api,
                    $optionsContragentType[$arOrder['PERSON_TYPE_ID']],
                    true,
                    false,
                    $site
                );
                
                Logger::getInstance()->write($resultUserCorp, 'resultUserCorp');
                
                if (!$resultUserCorp) {
                    RCrmActions::eventLog('RetailCrmEvent::orderSave', 'RetailCrmCorporateClient::clientSend', 'error during creating client');
                    
                    return false;
                }
                
                $arParams['customerCorporate'] = $resultUserCorp;
                $arParams['orderCompany']      = $resultUserCorp['mainCompany'] ?? null;
                
                $customerCorporateAddress = [];
                $customerCorporateCompany = [];
                $addressResult            = null;
                $companyResult            = null;
                
                if (!empty($address)) {
                    //TODO address builder add
                    $customerCorporateAddress = [
                        'name'   => $nickName,
                        'isMain' => true,
                        'text'   => $address,
                    ];
                    
                    $addressResult = $api->customersCorporateAddressesCreate($resultUserCorp['id'], $customerCorporateAddress, 'id', $site);
                }
                
                $customerCorporateCompany = [
                    'name'       => $nickName,
                    'isMain'     => true,
                    'contragent' => $contragent,
                ];
                
                if ($addressResult !== null) {
                    $customerCorporateCompany['address'] = [
                        'id' => $addressResult['id'],
                    ];
                }
                
                $companyResult = $api->customersCorporateCompaniesCreate($resultUserCorp['id'], $customerCorporateCompany, 'id', $site);
                
                $customerCorporateContact = [
                    'isMain'   => true,
                    'customer' => [
                        'externalId' => $arOrder['USER_ID'],
                        'site'       => $site,
                    ],
                ];
                
                if ($companyResult !== null) {
                    $orderCompany = [
                        'id' => $companyResult['id'],
                    ];
                    
                    $customerCorporateContact['companies'] = [
                        [
                            'company' => $orderCompany,
                        ],
                    ];
                }
                
                $api->customersCorporateContactsCreate(
                    $resultUserCorp['id'],
                    $customerCorporateContact,
                    'id',
                    $site
                );
                
                $arParams['orderCompany'] = array_merge(
                    $customerCorporateCompany,
                    ['id' => $companyResult['id']]
                );
            } else {
                RetailCrmCorporateClient::addCustomersCorporateAddresses(
                    $userCorp['customerCorporate']['id'],
                    $nickName,
                    $address,
                    $api,
                    $site = null
                );
                
                $arParams['customerCorporate'] = $userCorp['customerCorporate'];
                
                if (!empty($orderCompany)) {
                    $arParams['orderCompany'] = $orderCompany;
                }
            }
            
            $arParams['contactExId'] = $userCrm['customer']['externalId'];
        } else {
            //user
            $userCrm = RCrmActions::apiMethod($api, 'customersGet', __METHOD__, $arOrder['USER_ID'], $site);
            if (!isset($userCrm['customer'])) {
                $arUser     = UserTable::getById($arOrder['USER_ID'])->fetch();
                $resultUser = RetailCrmUser::customerSend($arUser, $api, $optionsContragentType[$arOrder['PERSON_TYPE_ID']], true, $site);
                if (!$resultUser) {
                    RCrmActions::eventLog('RetailCrmEvent::orderSave', 'RetailCrmUser::customerSend', 'error during creating customer');
                    
                    return false;
                }
            }
        }
        
        //order
        $resultOrder = RetailCrmOrder::orderSend($arOrder, $api, $arParams, true, $site, $methodApi);
        
        if (!$resultOrder) {
            RCrmActions::eventLog('RetailCrmEvent::orderSave', 'RetailCrmOrder::orderSend', 'error during creating order');
            
            return false;
        }
        
        return true;
    }
}
