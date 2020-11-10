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

use Exception;
use Intaro\RetailCrm\Component\Factory\ClientFactory;
use Intaro\RetailCrm\Model\Api\Customer;
use Intaro\RetailCrm\Model\Api\Request\Customers\CustomersCreateRequest;
use Intaro\RetailCrm\Model\Api\Request\Customers\CustomersEditRequest;
use Intaro\RetailCrm\Model\Api\Request\Customers\CustomersGetRequest;
use Intaro\RetailCrm\Model\Api\Request\Customers\CustomersUploadRequest;
use Intaro\RetailCrm\Model\Api\Request\Loyalty\Account\LoyaltyAccountActivateRequest;
use Intaro\RetailCrm\Model\Api\Request\Loyalty\Account\LoyaltyAccountCreateRequest;
use Intaro\RetailCrm\Model\Api\Request\SmsVerification\SmsVerificationConfirmRequest;
use Intaro\RetailCrm\Model\Api\Response\CustomersUploadResponse;
use Intaro\RetailCrm\Model\Api\Response\Loyalty\Account\LoyaltyAccountActivateResponse;
use Intaro\RetailCrm\Model\Api\Response\Loyalty\Account\LoyaltyAccountCreateResponse;
use Intaro\RetailCrm\Model\Api\Response\SmsVerification\SmsVerificationConfirmResponse;
use Intaro\RetailCrm\Model\Api\Response\SmsVerification\SmsVerificationStatusRequest;
use Intaro\RetailCrm\Model\Api\SerializedCreateLoyaltyAccount;
use Intaro\RetailCrm\Model\Api\SmsVerificationConfirm;
use Intaro\RetailCrm\Model\Api\User;
use RuntimeException;

/**
 * Class CustomerService
 */
class CustomerService
{
    /**
     * @var \Intaro\RetailCrm\Component\ApiClient\ClientAdapter
     */
    private $client;
    
    /**
     * @var \Intaro\RetailCrm\Model\Api\Response\Settings\CredentialsResponse
     */
    private $credentials;
    
    /**
     * LoyaltyService constructor.
     */
    public function __construct()
    {
        IncludeModuleLangFile(__FILE__);
        $this->client      = ClientFactory::createClientAdapter();
        $this->credentials = $this->client->getCredentials();
    }
    
    /**
     * @param \Intaro\RetailCrm\Model\Api\Customer $customer
     *
     * @return false|int
     */
    public function createOrUpdateCustomer(Customer $customer)
    {
        $extCustomer = $this->getCustomer($customer->externalId);
        $customer->site   = $this->credentials->sitesAvailable[0];
        
        if ($extCustomer !== null) {
            return $this->editCustomer($customer);
        }
        
        return $this->createCustomer($customer);
    }
    
    /**
     * @param \Intaro\RetailCrm\Model\Api\Customer $customer
     *
     * @return false|int
     */
    public function editCustomer(Customer $customer)
    {
        $customersEditRequest           = new CustomersEditRequest();
        $customersEditRequest->customer = $customer;
        $customersEditRequest->site     = $this->credentials->sitesAvailable[0];
        $customersEditRequest->by       = 'externalId';
    
        $response = $this->client->customersEdit($customersEditRequest);
    
        if ($response!==null && $response->success && $response->id > 0) {
            return $response->id;
        }
        
        Utils::handleErrors($response);
    
        return false;
    }
    
    /**
     * @param \Intaro\RetailCrm\Model\Api\Customer $customer
     *
     * @return false|int
     */
    public function createCustomer(Customer $customer)
    {
        $customersUploadRequest           = new CustomersCreateRequest();
        $customersUploadRequest->site     = $this->credentials->sitesAvailable[0];
        $customersUploadRequest->customer = $customer;
        $response                         = $this->client->customersCreate($customersUploadRequest);
        
        if ($response!==null && $response->success && $response->id > 0) {
            return $response->id;
        }
    
        Utils::handleErrors($response);
        
        return false;
    }
    
    /**
     * @param string $externalId
     *
     * @return \Intaro\RetailCrm\Model\Api\Customer|null
     */
    public function getCustomer(string $externalId)
    {
        $customersGetRequest       = new CustomersGetRequest();
        $customersGetRequest->id   = (int) $externalId;
        $customersGetRequest->by   = 'externalId';
        $customersGetRequest->site = $this->credentials->sitesAvailable[0];

        $response = $this->client->customersGet($customersGetRequest);
        
        if (isset($response->customer) && $response->customer->id > 0) {
            return $response->customer;
        }
    
        Utils::handleErrors($response);
        
        return null;
    }
}
