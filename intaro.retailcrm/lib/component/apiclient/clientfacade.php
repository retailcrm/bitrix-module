<?php
/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Component\ApiClient
 * @author   retailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Component\ApiClient;

use Intaro\RetailCrm\Component\Json\Deserializer;
use Intaro\RetailCrm\Component\Json\Serializer;
use Intaro\RetailCrm\Model\Api\Customer;
use Intaro\RetailCrm\Model\Api\Response\CustomerChangeResponse;
use Intaro\RetailCrm\Model\Api\Response\CustomerCorporateResponse;
use Intaro\RetailCrm\Model\Api\Response\CustomerResponse;
use Intaro\RetailCrm\Model\Api\Response\CustomersUploadResponse;
use RetailCrm\ApiClient;

/**
 * Class ClientFacade
 *
 * @package Intaro\RetailCrm\Component\ApiClient
 */
class ClientFacade
{
    /** @var string */
    public const ID = 'id';

    /** @var string */
    public const EXTERNAL_ID = 'externalId';

    /** @var \RetailCrm\ApiClient */
    private $client;

    /**
     * ClientFacade constructor.
     *
     * @param string      $url
     * @param string      $apiKey
     * @param string|null $site
     */
    public function __construct(string $url, string $apiKey, string $site = null)
    {
        $this->client = new ApiClient($url, $apiKey, $site);
    }

    /**
     * Proxy call for all methods we don't care about... or didn't implemented yet.
     *
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if (method_exists($this->client, $name)) {
            return \call_user_func_array([$this->client, $name], $arguments);
        }

        throw new \RuntimeException(sprintf('Method "%s" doesn\'t exist.', $name));
    }

    /**
     * Create customer
     *
     * @param \Intaro\RetailCrm\Model\Api\Customer $customer
     * @param string                               $site (default: null)
     *
     * @return \Intaro\RetailCrm\Model\Api\Response\CustomerChangeResponse|null
     * @throws \RetailCrm\Exception\CurlException
     * @throws \RetailCrm\Exception\InvalidJsonException
     *
     * @throws \InvalidArgumentException
     */
    public function customersCreate(Customer $customer, $site = null): ?CustomerChangeResponse
    {
        $response = $this->client->customersCreate(
            Serializer::serializeArray($customer),
            $site
        );

        return Deserializer::deserializeArray($response->getResponseBody(), CustomerChangeResponse::class);
    }

    /**
     * Upload array of the customers
     *
     * @param array  $customers array of customers
     * @param string $site      (default: null)
     *
     * @throws \InvalidArgumentException
     * @throws \RetailCrm\Exception\CurlException
     * @throws \RetailCrm\Exception\InvalidJsonException
     *
     * @return \Intaro\RetailCrm\Model\Api\Response\CustomersUploadResponse|null
     */
    public function customersUpload(array $customers, $site = null): ?CustomersUploadResponse
    {
        $serializedCustomers = Serializer::serializeArray(
            $customers,
            '\Intaro\RetailCrm\Model\Api\Customer[]'
        );
        $response = $this->client->customersUpload($serializedCustomers, $site);

        return Deserializer::deserializeArray($response->getResponseBody(), CustomersUploadResponse::class);
    }

    /**
     * Get customer by id or externalId
     *
     * @param int    $id   customer identifier
     * @param string $by   (default: 'externalId')
     * @param string $site (default: null)
     *
     * @return \Intaro\RetailCrm\Model\Api\Response\CustomerResponse|null
     */
    public function customerseGet(int $id, string $by = self::EXTERNAL_ID, $site = null): ?CustomerResponse
    {
        $response = $this->client->customersGet((string) $id, $by, $site);

        return Deserializer::deserializeArray($response->getResponseBody(), CustomerResponse::class);
    }

    /**
     * Create customers corporate
     *
     * @param \Intaro\RetailCrm\Model\Api\Customer $customer
     * @param string                               $site (default: null)
     *
     * @return \Intaro\RetailCrm\Model\Api\Response\CustomerChangeResponse|null
     * @throws \RetailCrm\Exception\CurlException
     * @throws \RetailCrm\Exception\InvalidJsonException
     *
     * @throws \InvalidArgumentException
     */
    public function customersCorporateCreate(Customer $customer, $site = null): ?CustomerChangeResponse
    {
        $response = $this->client->customersCorporateCreate(
            Serializer::serializeArray($customer),
            $site
        );

        return Deserializer::deserializeArray($response->getResponseBody(), CustomerChangeResponse::class);
    }

    /**
     * Upload array of the customers corporate
     *
     * @param array  $customers array of customers
     * @param string $site      (default: null)
     *
     * @throws \InvalidArgumentException
     * @throws \RetailCrm\Exception\CurlException
     * @throws \RetailCrm\Exception\InvalidJsonException
     *
     * @return \Intaro\RetailCrm\Model\Api\Response\CustomersUploadResponse|null
     */
    public function customersCorporateUpload(array $customers, $site = null): ?CustomersUploadResponse
    {
        $serializedCustomers = Serializer::serializeArray(
            $customers,
            '\Intaro\RetailCrm\Model\Api\Customer[]'
        );
        $response = $this->client->customersCorporateUpload($serializedCustomers, $site);

        return Deserializer::deserializeArray($response->getResponseBody(), CustomersUploadResponse::class);
    }

    /**
     * Get customer corporate by id or externalId
     *
     * @param int    $id   customer identifier
     * @param string $by   (default: 'externalId')
     * @param string $site (default: null)
     *
     * @return \Intaro\RetailCrm\Model\Api\Response\CustomerCorporateResponse|null
     */
    public function customersCorporateGet(int $id, string $by = self::EXTERNAL_ID, $site = null): ?CustomerCorporateResponse
    {
        $response = $this->client->customersCorporateGet((string) $id, $by, $site);

        return Deserializer::deserializeArray($response->getResponseBody(), CustomerCorporateResponse::class);
    }
}
