<?php
/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Component\ApiClient
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Component\ApiClient\Traits;

use Intaro\RetailCrm\Component\Json\Deserializer;
use Intaro\RetailCrm\Component\Json\Serializer;
use Intaro\RetailCrm\Model\Api\Request\Customers as Request;
use Intaro\RetailCrm\Model\Api\Response\CompaniesResponse;
use Intaro\RetailCrm\Model\Api\Response\CreateResponse;
use Intaro\RetailCrm\Model\Api\Response\CustomerChangeResponse;
use Intaro\RetailCrm\Model\Api\Response\CustomerCorporateResponse;
use Intaro\RetailCrm\Model\Api\Response\CustomersCorporateAddressesResponse;
use Intaro\RetailCrm\Model\Api\Response\CustomersCorporateContactsResponse;
use Intaro\RetailCrm\Model\Api\Response\CustomersCorporateResponse;
use Intaro\RetailCrm\Model\Api\Response\CustomersNotesResponse;
use Intaro\RetailCrm\Model\Api\Response\CustomersUploadResponse;
use Intaro\RetailCrm\Model\Api\Response\HistoryResponse;
use Intaro\RetailCrm\Model\Api\Response\OperationResponse;

/**
 * Trait CustomersCorporateTrait
 *
 * @package Intaro\RetailCrm\Component\ApiClient\Traits
 */
trait CustomersCorporateTrait
{
    /**
     * Create customers corporate
     *
     * @param \Intaro\RetailCrm\Model\Api\Request\Customers\CustomersCorporateCreateRequest $request
     *
     * @return \Intaro\RetailCrm\Model\Api\Response\CustomerChangeResponse|null
     */
    public function customersCorporateCreate(Request\CustomersCorporateCreateRequest $request): ?CustomerChangeResponse
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
     * @param Request\CustomersCorporateFixExternalIdsRequest $ids ids mapping
     *
     * @throws \InvalidArgumentException
     * @throws \RetailCrm\Exception\CurlException
     * @throws \RetailCrm\Exception\InvalidJsonException
     *
     * @return OperationResponse|null
     */
    public function customersCorporateFixExternalIds(
        Request\CustomersCorporateFixExternalIdsRequest $ids
    ): ?OperationResponse {
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
    public function customersCorporateUpload(Request\CustomersCorporateUploadRequest $request): ?CustomersUploadResponse
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
    public function customersCorporateGet(Request\CustomersGetRequest $request): ?CustomerCorporateResponse
    {
        $response = $this->client->customersCorporateGet((string) $request->id, $request->by, $request->site);

        return Deserializer::deserializeArray($response->getResponseBody(), CustomerCorporateResponse::class);
    }

    /**
     * Edit a customer corporate
     *
     * @param \Intaro\RetailCrm\Model\Api\Request\Customers\CustomersEditRequest $request
     *
     * @return CustomerChangeResponse|null
     */
    public function customersCorporateEdit(Request\CustomersEditRequest $request): ?CustomerChangeResponse {
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
    public function customersCorporateHistory(Request\CustomersHistoryRequest $request): ?HistoryResponse
    {
        $response = $this->client->customersCorporateHistory(
            Serializer::serializeArray($request->filter),
            $request->page,
            $request->limit
        );

        return Deserializer::deserializeArray($response->getResponseBody(), HistoryResponse::class);
    }

    /**
     * Returns filtered corporate customers list
     *
     * @param \Intaro\RetailCrm\Model\Api\Request\Customers\CustomersCorporateListRequest $request
     *
     * @return \Intaro\RetailCrm\Model\Api\Response\CustomersCorporateResponse|null
     */
    public function customersCorporateList(Request\CustomersCorporateListRequest $request): ?CustomersCorporateResponse
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
    public function customersCorporateNotesList(Request\CustomersNotesRequest $request): ?CustomersNotesResponse
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
    public function customersCorporateNotesCreate(Request\CustomersNotesCreateRequest $request): ?CreateResponse
    {
        $response = $this->client->customersCorporateNotesCreate(
            Serializer::serializeArray($request->note),
            $request->site
        );

        return Deserializer::deserializeArray($response->getResponseBody(), CreateResponse::class);
    }

    /**
     * Get corporate customer addresses
     *
     * @param \Intaro\RetailCrm\Model\Api\Request\Customers\CustomersCorporateAddressesRequest $request
     *
     * @return \Intaro\RetailCrm\Model\Api\Response\CustomersCorporateAddressesResponse|null
     */
    public function customersCorporateAddresses(
        Request\CustomersCorporateAddressesRequest $request
    ): ?CustomersCorporateAddressesResponse {
        $response = $this->client->customersCorporateAddresses(
            $request->externalId,
            Serializer::serializeArray($request->filter),
            $request->page,
            $request->limit,
            $request->by,
            $request->site
        );

        return Deserializer::deserializeArray(
            $response->getResponseBody(),
            CustomersCorporateAddressesResponse::class
        );
    }

    /**
     * Create corporate customer note
     *
     * @param \Intaro\RetailCrm\Model\Api\Request\Customers\CustomersCorporateAddressesCreateRequest $request
     *
     * @return \Intaro\RetailCrm\Model\Api\Response\CreateResponse|null
     */
    public function customersCorporateAddressesCreate(
        Request\CustomersCorporateAddressesCreateRequest $request
    ): ?CreateResponse {
        $response = $this->client->customersCorporateAddressesCreate(
            $request->externalId,
            Serializer::serializeArray($request->address),
            $request->externalId,
            $request->site
        );

        return Deserializer::deserializeArray($response->getResponseBody(), CreateResponse::class);
    }

    /**
     * Edit corporate customer note
     *
     * @param \Intaro\RetailCrm\Model\Api\Request\Customers\CustomersCorporateAddressesEditRequest $request
     *
     * @return \Intaro\RetailCrm\Model\Api\Response\CreateResponse|null
     */
    public function customersCorporateAddressesEdit(
        Request\CustomersCorporateAddressesEditRequest $request
    ): ?OperationResponse {
        $response = $this->client->customersCorporateAddressesEdit(
            $request->externalId,
            $request->entityBy === self::EXTERNAL_ID ? $request->address->externalId : $request->address->id,
            Serializer::serializeArray($request->address),
            $request->by,
            $request->entityBy,
            $request->site
        );

        return Deserializer::deserializeArray($response->getResponseBody(), OperationResponse::class);
    }

    /**
     * Get corporate customer companies by id or externalId
     *
     * @param \Intaro\RetailCrm\Model\Api\Request\Customers\CustomersCorporateCompaniesRequest $request
     *
     * @return CompaniesResponse|null
     */
    public function customersCorporateCompanies(
        Request\CustomersCorporateCompaniesRequest $request
    ): ?CompaniesResponse {
        $response = $this->client->customersCorporateCompanies(
            $request->idOrExternalId,
            Serializer::serializeArray($request->filter),
            $request->page,
            $request->limit,
            $request->by,
            $request->site
        );

        return Deserializer::deserializeArray($response->getResponseBody(), CompaniesResponse::class);
    }

    /**
     * Create corporate customer companies
     *
     * @param \Intaro\RetailCrm\Model\Api\Request\Customers\CustomersCorporateCompaniesCreateRequest $request
     *
     * @return CompaniesResponse|null
     */
    public function customersCorporateCompaniesCreate(
        Request\CustomersCorporateCompaniesCreateRequest $request
    ): ?CreateResponse {
        $response = $this->client->customersCorporateCompaniesCreate(
            $request->externalId,
            Serializer::serializeArray($request->company),
            $request->by,
            $request->site
        );

        return Deserializer::deserializeArray($response->getResponseBody(), CreateResponse::class);
    }


    /**
     * Edit corporate customer companies by id or externalId
     *
     * @param \Intaro\RetailCrm\Model\Api\Request\Customers\CustomersCorporateCompaniesEditRequest $request
     *
     * @return CompaniesResponse|null
     */
    public function customersCorporateCompaniesEdit(
        Request\CustomersCorporateCompaniesEditRequest $request
    ): ?OperationResponse {
        $response = $this->client->customersCorporateAddressesEdit(
            $request->externalId,
            $request->entityBy === self::EXTERNAL_ID ? $request->company->externalId : $request->company->id,
            Serializer::serializeArray($request->company),
            $request->by,
            $request->entityBy,
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
    public function customersCorporateContacts(
        Request\CustomersCorporateContactsRequest $request
    ): ?CustomersCorporateContactsResponse {
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
     * Create corporate customer contact
     *
     * @param \Intaro\RetailCrm\Model\Api\Request\Customers\CustomersCorporateContactsCreateRequest $request
     *
     * @return \Intaro\RetailCrm\Model\Api\Response\CreateResponse|null
     */
    public function customersCorporateContactsCreate(
        Request\CustomersCorporateContactsCreateRequest $request
    ): ?CreateResponse {
        $response = $this->client->customersCorporateContactsCreate(
            $request->idOrExternalId,
            Serializer::serializeArray($request->contact),
            $request->by,
            $request->site
        );

        return Deserializer::deserializeArray($response->getResponseBody(), CreateResponse::class);
    }

    /**
     * Edit corporate customer contacts by id or externalId
     *
     * @param \Intaro\RetailCrm\Model\Api\Request\Customers\CustomersCorporateContactsEditRequest $request
     *
     * @return CompaniesResponse|null
     */
    public function customersCorporateContactsEdit(
        Request\CustomersCorporateContactsEditRequest $request
    ): ?OperationResponse {
        $response = $this->client->customersCorporateContactsEdit(
            $request->idOrExternalId,
            $request->entityBy === self::EXTERNAL_ID ? $request->contact->externalId : $request->contact->id,
            Serializer::serializeArray($request->contact),
            $request->by,
            $request->entityBy,
            $request->site
        );

        return Deserializer::deserializeArray($response->getResponseBody(), CreateResponse::class);
    }
}
