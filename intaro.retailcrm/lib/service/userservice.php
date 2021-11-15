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
 * Class UserService
 */
class UserService
{
    /**
     * @var \Intaro\RetailCrm\Component\ApiClient\ClientAdapter
     */
    private $client;
    
    /**
     * LoyaltyService constructor.
     */
    public function __construct()
    {
        IncludeModuleLangFile(__FILE__);
        $this->client = ClientFactory::createClientAdapter();
    }
    
    /**
     * @param \Intaro\RetailCrm\Model\Api\Customer $customer
     * @return \Intaro\RetailCrm\Model\Api\Response\CustomersUploadResponse|null
     */
    public function addNewUser(Customer $customer): ?CustomersUploadResponse
    {
        $customersGetRequest       = new CustomersGetRequest();
        $customersGetRequest->id   = $customer->id;
        $customersGetRequest->by   = 'externalId';
        $customersGetRequest->site = $customer->site;
        $customerResponse          = $this->client->customersGet($customersGetRequest);
        
        if ($customerResponse !== null
            && $customerResponse->success
            && isset($customerResponse->customer->id)
        ) {
            $customersEditRequest           = new CustomersEditRequest();
            $customersEditRequest->customer = $customer;
            $customersEditRequest->site     = $customer->site;
            
            $this->client->customersEdit($customersEditRequest);
        } else {
            $customersUploadRequest               = new CustomersUploadRequest();
            $customersUploadRequest->site         = $customer->site;
            $customersUploadRequest->customers[0] = $customer;
    
            return $this->client->customersUpload($customersUploadRequest);
        }
    }
}
