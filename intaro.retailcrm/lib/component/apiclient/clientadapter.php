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

use Intaro\RetailCrm\Model\Api\Request\Customers\CustomersCorporateContactsRequest;
use Intaro\RetailCrm\Model\Api\Request\Customers\CustomersCorporateListRequest;
use Intaro\RetailCrm\Model\Api\Request\Customers\CustomersCorporateNotesRequest;
use Intaro\RetailCrm\Model\Api\Request\Customers\CustomersNotesCreateRequest;
use Intaro\RetailCrm\Model\Api\Request\Customers\CustomersNotesRequest;
use Intaro\RetailCrm\Model\Api\Response\CreateResponse;
use Intaro\RetailCrm\Model\Api\Response\CustomersCorporateContactsResponse;
use Intaro\RetailCrm\Model\Api\Response\CustomersCorporateResponse;
use Intaro\RetailCrm\Model\Api\Response\CustomersNotesResponse;
use RetailCrm\ApiClient;
use Intaro\RetailCrm\Component\Json\Serializer;
use Intaro\RetailCrm\Component\Json\Deserializer;
use Intaro\RetailCrm\Model\Api\Response\HistoryResponse;
use Intaro\RetailCrm\Model\Api\Response\CustomerResponse;
use Intaro\RetailCrm\Model\Api\Response\CompaniesResponse;
use Intaro\RetailCrm\Model\Api\Response\CustomersResponse;
use Intaro\RetailCrm\Model\Api\Response\OperationResponse;
use Intaro\RetailCrm\Model\Api\Response\CustomerChangeResponse;
use Intaro\RetailCrm\Model\Api\Response\CustomersUploadResponse;
use Intaro\RetailCrm\Model\Api\Response\CustomerCorporateResponse;
use Intaro\RetailCrm\Model\Api\Request\Customers\CustomersGetRequest;
use Intaro\RetailCrm\Model\Api\Request\Customers\CustomersListRequest;
use Intaro\RetailCrm\Model\Api\Request\Customers\CustomersEditRequest;
use Intaro\RetailCrm\Model\Api\Request\Customers\CustomersUploadRequest;
use Intaro\RetailCrm\Model\Api\Request\Customers\CustomersCreateRequest;
use Intaro\RetailCrm\Model\Api\Request\Customers\CustomersHistoryRequest;
use Intaro\RetailCrm\Model\Api\Request\Customers\CustomersFixExternalIdsRequest;
use Intaro\RetailCrm\Model\Api\Request\Customers\CustomersCorporateUploadRequest;
use Intaro\RetailCrm\Model\Api\Request\Customers\CustomersCorporateCreateRequest;
use Intaro\RetailCrm\Model\Api\Request\Customers\CustomersCorporateCompaniesRequest;
use Intaro\RetailCrm\Model\Api\Request\Customers\CustomersCorporateFixExternalIdsRequest;

/**
 * Class ClientAdapter
 *
 * @package Intaro\RetailCrm\Component\ApiClient
 */
class ClientAdapter
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
    public function __construct(string $url, string $apiKey, $site = null)
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
     * Returns filtered customers list
     *
     * @param \Intaro\RetailCrm\Model\Api\Request\Customers\CustomersListRequest $request
     *
     * @return CustomersResponse|null
     */
    public function customersList(CustomersListRequest $request): ?CustomersResponse
    {
        $serialized = Serializer::serializeArray($request);
        $response = $this->client->customersList(
            Serializer::serializeArray($serialized['filter'] ?? []),
            $request->page,
            $request->limit
        );

        return Deserializer::deserializeArray($response->getResponseBody(), CustomersResponse::class);
    }

    /**
     * Returns filtered corporate customers notes list
     *
     * @param \Intaro\RetailCrm\Model\Api\Request\Customers\CustomersNotesRequest $request
     *
     * @return \Intaro\RetailCrm\Model\Api\Response\CustomersNotesResponse|null
     */
    public function customersNotesList(CustomersNotesRequest $request): ?CustomersNotesResponse
    {
        $response = $this->client->customersNotesList(
            Serializer::serializeArray($request->filter),
            $request->page,
            $request->limit
        );

        return Deserializer::deserializeArray($response->getResponseBody(), CustomersNotesResponse::class);
    }

    /**
     * Create customer note
     *
     * @param \Intaro\RetailCrm\Model\Api\Request\Customers\CustomersNotesCreateRequest $request
     *
     * @return \Intaro\RetailCrm\Model\Api\Response\CreateResponse|null
     */
    public function customersNotesCreate(CustomersNotesCreateRequest $request): ?CreateResponse
    {
        $response = $this->client->customersNotesCreate(
            Serializer::serializeArray($request->note),
            $request->site
        );

        return Deserializer::deserializeArray($response->getResponseBody(), CreateResponse::class);
    }

    /**
     * Create customer
     *
     * @param \Intaro\RetailCrm\Model\Api\Request\Customers\CustomersCreateRequest $request
     *
     * @return \Intaro\RetailCrm\Model\Api\Response\CustomerChangeResponse|null
     */
    public function customersCreate(CustomersCreateRequest $request): ?CustomerChangeResponse
    {
        $serialized = Serializer::serializeArray($request);
        $response = $this->client->customersCreate($serialized['customer'] ?? [], $request->site);

        return Deserializer::deserializeArray($response->getResponseBody(), CustomerChangeResponse::class);
    }

    /**
     * Save customer IDs' (id and externalId) association in the CRM
     *
     * @param CustomersFixExternalIdsRequest $ids ids mapping
     *
     * @throws \InvalidArgumentException
     * @throws \RetailCrm\Exception\CurlException
     * @throws \RetailCrm\Exception\InvalidJsonException
     *
     * @return OperationResponse|null
     */
    public function customersFixExternalIds(CustomersFixExternalIdsRequest $ids): ?OperationResponse
    {
        $request = $this->client->customersFixExternalIds(Serializer::serializeArray($ids)['customers']);

        return Deserializer::deserializeArray($request->getResponseBody(), OperationResponse::class);
    }

    /**
     * Upload array of the customers
     *
     * @param \Intaro\RetailCrm\Model\Api\Request\Customers\CustomersUploadRequest $request
     *
     * @return \Intaro\RetailCrm\Model\Api\Response\CustomersUploadResponse|null
     */
    public function customersUpload(CustomersUploadRequest $request): ?CustomersUploadResponse
    {
        $serialized = Serializer::serializeArray($request);
        $response = $this->client->customersUpload($serialized['customers'] ?? [], $request->site);

        return Deserializer::deserializeArray($response->getResponseBody(), CustomersUploadResponse::class);
    }

    /**
     * Get customer by id or externalId
     *
     * @param \Intaro\RetailCrm\Model\Api\Request\Customers\CustomersGetRequest $request
     *
     * @return \Intaro\RetailCrm\Model\Api\Response\CustomerResponse|null
     */
    public function customersGet(CustomersGetRequest $request): ?CustomerResponse
    {
        $response = $this->client->customersGet((string) $request->id, $request->by, $request->site);

        return Deserializer::deserializeArray($response->getResponseBody(), CustomerResponse::class);
    }

    /**
     * Edit customer
     *
     * @param \Intaro\RetailCrm\Model\Api\Request\Customers\CustomersEditRequest $request
     *
     * @return CustomerChangeResponse|null
     */
    public function customersEdit(CustomersEditRequest $request): ?CustomerChangeResponse {
        $serialized = Serializer::serializeArray($request);
        $response = $this->client->customersEdit($serialized['customer'], $request->by, $request->site);

        return Deserializer::deserializeArray($response->getResponseBody(), CustomerChangeResponse::class);
    }

    /**
     * Get customers history
     *
     * @param \Intaro\RetailCrm\Model\Api\Request\Customers\CustomersHistoryRequest $request
     *
     * @return HistoryResponse|null
     */
    public function customersHistory(CustomersHistoryRequest $request): ?HistoryResponse
    {
        $response = $this->client->customersHistory(
            Serializer::serializeArray($request->filter),
            $request->page,
            $request->limit
        );

        return Deserializer::deserializeArray($response->getResponseBody(), HistoryResponse::class);
    }

    /**
     * Create customers corporate
     *
     * @param \Intaro\RetailCrm\Model\Api\Request\Customers\CustomersCorporateCreateRequest $request
     *
     * @return \Intaro\RetailCrm\Model\Api\Response\CustomerChangeResponse|null
     */
    public function customersCorporateCreate(CustomersCorporateCreateRequest $request): ?CustomerChangeResponse
    {
        $response = $this->client->customersCorporateCreate(
            Serializer::serializeArray($request->customerCorporate),
            $request->site
        );

        return Deserializer::deserializeArray($response->getResponseBody(), CustomerChangeResponse::class);
    }

    /**
     * Save corporate customer IDs' (id and externalId) association in the CRM
     *
     * @param CustomersCorporateFixExternalIdsRequest $ids ids mapping
     *
     * @throws \InvalidArgumentException
     * @throws \RetailCrm\Exception\CurlException
     * @throws \RetailCrm\Exception\InvalidJsonException
     *
     * @return OperationResponse|null
     */
    public function customersCorporateFixExternalIds(CustomersCorporateFixExternalIdsRequest $ids): ?OperationResponse
    {
        $request = $this->client->customersCorporateFixExternalIds(
            Serializer::serializeArray($ids)['customersCorporate']
        );

        return Deserializer::deserializeArray($request->getResponseBody(), OperationResponse::class);
    }

    /**
     * Upload array of the customers corporate
     *
     * @param \Intaro\RetailCrm\Model\Api\Request\Customers\CustomersCorporateUploadRequest $request
     *
     * @return \Intaro\RetailCrm\Model\Api\Response\CustomersUploadResponse|null
     */
    public function customersCorporateUpload(CustomersCorporateUploadRequest $request): ?CustomersUploadResponse
    {
        $serialized = Serializer::serializeArray($request);
        $response = $this->client->customersCorporateUpload($serialized['customersCorporate'], $request->site);

        return Deserializer::deserializeArray($response->getResponseBody(), CustomersUploadResponse::class);
    }

    /**
     * Get customer corporate by id or externalId
     *
     * @param \Intaro\RetailCrm\Model\Api\Request\Customers\CustomersGetRequest $request
     *
     * @return \Intaro\RetailCrm\Model\Api\Response\CustomerCorporateResponse|null
     */
    public function customersCorporateGet(CustomersGetRequest $request): ?CustomerCorporateResponse
    {
        $response = $this->client->customersCorporateGet((string) $request->id, $request->by, $request->site);

        return Deserializer::deserializeArray($response->getResponseBody(), CustomerCorporateResponse::class);
    }

    /**
     * Get corporate customer companies by id or externalId
     *
     * @param \Intaro\RetailCrm\Model\Api\Request\Customers\CustomersCorporateCompaniesRequest $request
     *
     * @return CompaniesResponse|null
     */
    public function customersCorporateCompanies(CustomersCorporateCompaniesRequest $request): ?CompaniesResponse {
        $response = $this->client->customersCorporateCompanies(
            (string) $request->id,
            Serializer::serializeArray($request->filter),
            $request->page,
            $request->limit,
            $request->by,
            $request->site
        );

        return Deserializer::deserializeArray($response->getResponseBody(), CompaniesResponse::class);
    }

    /**
     * Edit a customer corporate
     *
     * @param \Intaro\RetailCrm\Model\Api\Request\Customers\CustomersEditRequest $request
     *
     * @return CustomerChangeResponse|null
     */
    public function customersCorporateEdit(CustomersEditRequest $request): ?CustomerChangeResponse {
        $response = $this->client->customersCorporateEdit(
            Serializer::serializeArray($request->customer),
            $request->by,
            $request->site
        );

        return Deserializer::deserializeArray($response->getResponseBody(), CustomerChangeResponse::class);
    }

    /**
     * Get customers corporate history
     *
     * @param \Intaro\RetailCrm\Model\Api\Request\Customers\CustomersHistoryRequest $request
     *
     * @return HistoryResponse|null
     */
    public function customersCorporateHistory(CustomersHistoryRequest $request): ?HistoryResponse
    {
        $response = $this->client->customersCorporateHistory(
            Serializer::serializeArray($request->filter),
            $request->page,
            $request->limit
        );

        return Deserializer::deserializeArray($response->getResponseBody(), HistoryResponse::class);
    }

    /**
     * Create corporate customer contact
     *
     * @param \Intaro\RetailCrm\Model\Api\Request\Customers\CustomersCorporateCompaniesRequest $request
     *
     * @return \Intaro\RetailCrm\Model\Api\Response\CreateResponse|null
     */
    public function customersCorporateContactsCreate(CustomersCorporateCompaniesRequest $request): ?CreateResponse
    {
        $response = $this->client->customersCorporateContactsCreate(
            (string) $request->id,
            Serializer::serializeArray($request->filter),
            $request->by,
            $request->site
        );

        return Deserializer::deserializeArray($response->getResponseBody(), CreateResponse::class);
    }

    /**
     * Get corporate customer contacts by id or externalId
     *
     * @param \Intaro\RetailCrm\Model\Api\Request\Customers\CustomersCorporateContactsRequest $request
     *
     * @return \Intaro\RetailCrm\Model\Api\Response\CustomersCorporateContactsResponse|null
     */
    public function customersCorporateContacts(CustomersCorporateContactsRequest $request): ?CustomersCorporateContactsResponse
    {
        $response = $this->client->customersCorporateContacts(
            $request->externalId,
            Serializer::serializeArray($request->filter),
            $request->page,
            $request->limit,
            $request->by,
            $request->site
        );

        return Deserializer::deserializeArray(
            $response->getResponseBody(),
            CustomersCorporateContactsResponse::class
        );
    }

    /**
     * Returns filtered corporate customers list
     *
     * @param \Intaro\RetailCrm\Model\Api\Request\Customers\CustomersCorporateListRequest $request
     *
     * @return \Intaro\RetailCrm\Model\Api\Response\CustomersCorporateResponse|null
     */
    public function customersCorporateList(CustomersCorporateListRequest $request): ?CustomersCorporateResponse
    {
        $response = $this->client->customersCorporateList(
            Serializer::serializeArray($request->filter),
            $request->page,
            $request->limit
        );

        return Deserializer::deserializeArray($response->getResponseBody(), CustomersCorporateResponse::class);
    }

    /**
     * Returns filtered corporate customers notes list
     *
     * @param \Intaro\RetailCrm\Model\Api\Request\Customers\CustomersNotesRequest $request
     *
     * @return \Intaro\RetailCrm\Model\Api\Response\CustomersNotesResponse|null
     */
    public function customersCorporateNotesList(CustomersNotesRequest $request): ?CustomersNotesResponse
    {
        $response = $this->client->customersCorporateNotesList(
            Serializer::serializeArray($request->filter),
            $request->page,
            $request->limit
        );

        return Deserializer::deserializeArray($response->getResponseBody(), CustomersNotesResponse::class);
    }

    /**
     * Create corporate customer note
     *
     * @param \Intaro\RetailCrm\Model\Api\Request\Customers\CustomersNotesCreateRequest $request
     *
     * @return \Intaro\RetailCrm\Model\Api\Response\CreateResponse|null
     */
    public function customersCorporateNotesCreate(CustomersNotesCreateRequest $request): ?CreateResponse
    {
        $response = $this->client->customersCorporateNotesCreate(
            Serializer::serializeArray($request->note),
            $request->site
        );

        return Deserializer::deserializeArray($response->getResponseBody(), CreateResponse::class);
    }
}
