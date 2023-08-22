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
use Intaro\RetailCrm\Model\Api\Request\Customers\CustomersCreateRequest;
use Intaro\RetailCrm\Model\Api\Request\Customers\CustomersEditRequest;
use Intaro\RetailCrm\Model\Api\Request\Customers\CustomersFixExternalIdsRequest;
use Intaro\RetailCrm\Model\Api\Request\Customers\CustomersGetRequest;
use Intaro\RetailCrm\Model\Api\Request\Customers\CustomersHistoryRequest;
use Intaro\RetailCrm\Model\Api\Request\Customers\CustomersListRequest;
use Intaro\RetailCrm\Model\Api\Request\Customers\CustomersNotesCreateRequest;
use Intaro\RetailCrm\Model\Api\Request\Customers\CustomersNotesRequest;
use Intaro\RetailCrm\Model\Api\Request\Customers\CustomersUploadRequest;
use Intaro\RetailCrm\Model\Api\Response\CreateResponse;
use Intaro\RetailCrm\Model\Api\Response\CustomerChangeResponse;
use Intaro\RetailCrm\Model\Api\Response\CustomerResponse;
use Intaro\RetailCrm\Model\Api\Response\CustomersNotesResponse;
use Intaro\RetailCrm\Model\Api\Response\CustomersResponse;
use Intaro\RetailCrm\Model\Api\Response\CustomersUploadResponse;
use Intaro\RetailCrm\Model\Api\Response\HistoryResponse;
use Intaro\RetailCrm\Model\Api\Response\OperationResponse;

trait CustomersTrait
{
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
        $serialized = $this->setBooleanParameters($serialized);
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
    public function customersEdit(CustomersEditRequest $request): ?CustomerChangeResponse
    {
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
            $request->limit
        );

        return Deserializer::deserializeArray($response->getResponseBody(), HistoryResponse::class);
    }

    /**
     * @param array $serializedRequest
     * @return array
     */
    private function setBooleanParameters($serializedRequest)
    {
        if (empty($serializedRequest['customer']['subscribed']))
        {
            $serializedRequest['customer']['subscribed'] = false;
        }

        return $serializedRequest;
    }
}
