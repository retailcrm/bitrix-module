<?php

/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Service
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

namespace Intaro\RetailCrm\Service;

use Exception;
use Intaro\RetailCrm\Component\Builder\Api\CustomerBuilder;
use Intaro\RetailCrm\Component\Builder\Exception\BuilderException;
use Intaro\RetailCrm\Component\ConfigProvider;
use Intaro\RetailCrm\Component\Constants;
use Intaro\RetailCrm\Component\Factory\ClientFactory;
use Intaro\RetailCrm\Model\Api\Customer;
use Intaro\RetailCrm\Model\Api\Request\Customers\CustomersCreateRequest;
use Intaro\RetailCrm\Model\Api\Request\Customers\CustomersEditRequest;
use Intaro\RetailCrm\Model\Api\Request\Customers\CustomersGetRequest;
use Intaro\RetailCrm\Repository\UserRepository;
use Logger;

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

        if ($response !== null && $response->success && $response->id > 0) {
            return $response->id;
        }

        Utils::handleApiErrors($response);

        return false;
    }

    /**
     * @param \Intaro\RetailCrm\Model\Api\Customer $customer
     *
     * @return false|int
     */
    public function createCustomer(Customer $customer)
    {
        $crmCustomer = $this->getCustomer($customer->externalId);

        if ($crmCustomer instanceof Customer) {
            return false;
        }

        $customersUploadRequest           = new CustomersCreateRequest();
        $customersUploadRequest->site     = $this->credentials->sitesAvailable[0];
        $customersUploadRequest->customer = $customer;
        $response                         = $this->client->customersCreate($customersUploadRequest);

        if ($response !== null && $response->success && $response->id > 0) {
            return $response->id;
        }

        Utils::handleApiErrors($response);

        return false;
    }

    /**
     * @param string $externalId
     *
     * @return \Intaro\RetailCrm\Model\Api\Customer|null
     */
    public function getCustomer(string $externalId): ?Customer
    {
        $customersGetRequest       = new CustomersGetRequest();
        $customersGetRequest->id   = $externalId;
        $customersGetRequest->by   = 'externalId';
        $customersGetRequest->site = $this->credentials->sitesAvailable[0];

        $response = $this->client->customersGet($customersGetRequest);

        if ($response !== null && isset($response->customer) && $response->customer->id > 0) {
            return $response->customer;
        }

        Utils::handleApiErrors($response);

        return null;
    }

    /**
     * @param int $userId
     * @return \Intaro\RetailCrm\Model\Api\Customer|mixed
     */
    public function createModel(int $userId)
    {
        $key = array_search('individual', ConfigProvider::getContragentTypes(), true);
        $builder = new CustomerBuilder();

        try {
            return $builder
                ->reset()
                ->setAttachDaemonCollectorId(true)
                ->setPersonTypeId($key)
                ->setUser(UserRepository::getById($userId))
                ->build()
                ->getResult();
        }catch (BuilderException $exception){
            Logger::getInstance()->write($exception->getMessage(), Constants::LOYALTY_ERROR);
        }
    }
}
