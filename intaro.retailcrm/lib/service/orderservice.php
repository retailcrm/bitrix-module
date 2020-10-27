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

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\UserTable;
use CModule;
use Intaro\RetailCrm\Component\Factory\ClientFactory;
use Intaro\RetailCrm\Model\Api\Address;
use Intaro\RetailCrm\Model\Api\Company;
use Intaro\RetailCrm\Model\Api\Contragent;
use Intaro\RetailCrm\Model\Api\Customer;
use Intaro\RetailCrm\Model\Api\CustomerContact;
use Intaro\RetailCrm\Model\Api\Request\Customers\CustomersCorporateAddressesCreateRequest;
use Intaro\RetailCrm\Model\Api\Request\Customers\CustomersCorporateCompaniesCreateRequest;
use Intaro\RetailCrm\Model\Api\Request\Customers\CustomersCorporateCompaniesRequest;
use Intaro\RetailCrm\Model\Api\Request\Customers\CustomersCorporateContactsCreateRequest;
use Intaro\RetailCrm\Model\Api\Request\Customers\CustomersCorporateListRequest;
use RCrmActions;
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
    public function saveOrderInCRM($event)
    {
        if (true === $GLOBALS['ORDER_DELETE_USER_ADMIN']) {
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
    
        try {
            $arOrder = RetailCrmOrder::orderObjToArr($obOrder);
        } catch (SystemException $exception) {
            AddMessage2Log($exception->getMessage());
        }
    
        /** @var \Intaro\RetailCrm\Component\ApiClient\ClientAdapter $client */
        $api = ClientFactory::createClientAdapter();
        
        if ($api === null) {
            return false;
        }
        
        //params
        $optionsOrderTypes     = RetailcrmConfigProvider::getOrderTypes();
        $optionsDeliveryTypes  = RetailcrmConfigProvider::getDeliveryTypes();
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
            'optionsDelivTypes'     => $optionsDeliveryTypes,
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
            if (isset($arOrder)
                && array_key_exists($arOrder['LID'], $optionsSitesList)
                && $optionsSitesList[$arOrder['LID']] !== null) {
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
            $contractor  = new Contragent();
            $userCorp    = [];
            $corpName    = RetailcrmConfigProvider::getCorporateClientName();
            $corpAddress = RetailcrmConfigProvider::getCorporateClientAddress();
            
            foreach ($arOrder['PROPS']['properties'] as $prop) {
                if ($prop['CODE'] === $corpName) {
                    $nickName = $prop['VALUE'][0];
                }
                
                if ($prop['CODE'] === $corpAddress) {
                    $address = $prop['VALUE'][0];
                }
                
                if (!empty($optionsLegalDetails)
                    && $search = array_search($prop['CODE'], $optionsLegalDetails[$arOrder['PERSON_TYPE_ID']], true)
                ) {
                    $contractor->{$search} = $prop['VALUE'][0];//legal order data
                }
            }
    
            $customersCorporateRequest = new CustomersCorporateListRequest();
            $customersCorporateRequest->filter->companyName = $nickName;
            $response           = $api->customersCorporateList($customersCorporateRequest);
            
            if ($response && $response->success === true) {
                $customersCorporate = $response['customersCorporate'];
                $singleCorp         = reset($customersCorporate);
                
                if (!empty($singleCorp)) {
                    $userCorp['customerCorporate'] = $singleCorp;
                    
                    $customersCorporateCompaniesRequest = new CustomersCorporateCompaniesRequest();
                    $customersCorporateCompaniesRequest->site = $site;
                    $customersCorporateCompaniesRequest->by = 'id';
                    $customersCorporateCompaniesRequest->idOrExternalId = $singleCorp['id'];
                    $customersCorporateCompaniesRequest->filter =[];
                    $customersCorporateCompaniesRequest->page =null;
                    $customersCorporateCompaniesRequest->limit =null;
                    
                    $companiesResponse             = $api->customersCorporateCompanies($customersCorporateCompaniesRequest);
                    
                    if ($companiesResponse && $companiesResponse->success === true) {
                        $orderCompany = array_reduce(
                            $companiesResponse['companies'],
                            static function ($carry, $item) use ($nickName) {
                                if (is_array($item) && $item['name'] === $nickName) {
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
                try {
                    $arUser = UserTable::getById($arOrder['USER_ID'])->fetch();
                } catch (ObjectPropertyException | ArgumentException | SystemException $exception) {
                    AddMessage2Log($exception);
                }
                
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
    
                $addressResult            = null;
                $companyResult            = null;
                $customerCorporateCompany = [];
                
                if (!empty($address)) {
                    $customersCorporateAddressesCreateRequest = new CustomersCorporateAddressesCreateRequest();
                    $customersCorporateAddressesCreateRequest->externalId = $resultUserCorp['id'];
                    $customersCorporateAddressesCreateRequest->address = new Address;
                    $customersCorporateAddressesCreateRequest->address->name = $nickName;
                    $customersCorporateAddressesCreateRequest->address->isMain = true;
                    $customersCorporateAddressesCreateRequest->address->text = $address;
                    $customersCorporateAddressesCreateRequest->by = 'id';
                    $customersCorporateAddressesCreateRequest->site = $site;
                    
                    $addressResult = $api->customersCorporateAddressesCreate($customersCorporateAddressesCreateRequest);
                }
    
                if ($addressResult->success === true) {
                    $customerCorporateCompany['address'] = [
                        'id' => $addressResult['id'],
                    ];
                }
                
                $customersCorporateCompaniesCreateRequest = new CustomersCorporateCompaniesCreateRequest();
                $customersCorporateCompaniesCreateRequest->externalId = $resultUserCorp['id'];
                $customersCorporateCompaniesCreateRequest->company = new Company();
                $customersCorporateCompaniesCreateRequest->company->name = $nickName;
                $customersCorporateCompaniesCreateRequest->company->isMain = true;
                $customersCorporateCompaniesCreateRequest->company->contragent = $contractor;
                $customersCorporateCompaniesCreateRequest->by = 'id';
                $customersCorporateCompaniesCreateRequest->site = $site;
    
                $companyResult = $api->customersCorporateCompaniesCreate($customersCorporateCompaniesCreateRequest);
    
                $customersCorporateContactsCreateRequest= new CustomersCorporateContactsCreateRequest();
                $customersCorporateContactsCreateRequest->contact = new CustomerContact;
                $customersCorporateContactsCreateRequest->contact->isMain = true;
                $customersCorporateContactsCreateRequest->contact->customer = new Customer;
                $customersCorporateContactsCreateRequest->contact->customer->externalId = $arOrder['USER_ID'];
                $customersCorporateContactsCreateRequest->contact->customer->site = $site;
                $customersCorporateContactsCreateRequest->site = $site;
                $customersCorporateContactsCreateRequest->idOrExternalId = $resultUserCorp['id'];
                
                if ($companyResult->success === true) {
                    $company = new Company();
                    $company->id = $companyResult->id;
                    $customersCorporateContactsCreateRequest->contact->companies[0] = $company;
                }
                
                $api->customersCorporateContactsCreate($customersCorporateContactsCreateRequest);
                
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
                try {
                    $arUser = UserTable::getById($arOrder['USER_ID'])->fetch();
                } catch (ObjectPropertyException | ArgumentException | SystemException $exception) {
                    AddMessage2Log($exception->getMessage());
                    
                    return false;
                }
                
                $resultUser = RetailCrmUser::customerSend($arUser, $api, $optionsContragentType[$arOrder['PERSON_TYPE_ID']], true, $site);
                
                if (!$resultUser) {
                    RCrmActions::eventLog('RetailCrmEvent::orderSave', 'RetailCrmUser::customerSend', 'error during creating customer');
                    
                    return false;
                }
            }
        }
        
        //order
        try {
            $resultOrder = RetailCrmOrder::orderSend($arOrder, $api, $arParams, true, $site, $methodApi);
            
            if (!$resultOrder) {
                RCrmActions::eventLog('RetailCrmEvent::orderSave', 'RetailCrmOrder::orderSend', 'error during creating order');
                
                return false;
            }
            
            return true;
        } catch (ObjectPropertyException | ArgumentException | SystemException $exception) {
            AddMessage2Log($exception->getMessage());
        }
    }
}
