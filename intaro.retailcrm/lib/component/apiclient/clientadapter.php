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
use Intaro\RetailCrm\Model\Api\Request\Customers\CustomersCorporateAddressesCreateRequest;
use Intaro\RetailCrm\Model\Api\Request\Customers\CustomersCorporateAddressesEditRequest;
use Intaro\RetailCrm\Model\Api\Request\Customers\CustomersCorporateAddressesRequest;
use Intaro\RetailCrm\Model\Api\Request\Customers\CustomersCorporateCompaniesCreateRequest;
use Intaro\RetailCrm\Model\Api\Request\Customers\CustomersCorporateCompaniesEditRequest;
use Intaro\RetailCrm\Model\Api\Request\Customers\CustomersCorporateCompaniesRequest;
use Intaro\RetailCrm\Model\Api\Request\Customers\CustomersCorporateContactsCreateRequest;
use Intaro\RetailCrm\Model\Api\Request\Customers\CustomersCorporateContactsEditRequest;
use Intaro\RetailCrm\Model\Api\Request\Customers\CustomersCorporateContactsRequest;
use Intaro\RetailCrm\Model\Api\Request\Customers\CustomersCorporateCreateRequest;
use Intaro\RetailCrm\Model\Api\Request\Customers\CustomersCorporateFixExternalIdsRequest;
use Intaro\RetailCrm\Model\Api\Request\Customers\CustomersCorporateListRequest;
use Intaro\RetailCrm\Model\Api\Request\Customers\CustomersCorporateUploadRequest;
use Intaro\RetailCrm\Model\Api\Request\Customers\CustomersCreateRequest;
use Intaro\RetailCrm\Model\Api\Request\Customers\CustomersEditRequest;
use Intaro\RetailCrm\Model\Api\Request\Customers\CustomersFixExternalIdsRequest;
use Intaro\RetailCrm\Model\Api\Request\Customers\CustomersGetRequest;
use Intaro\RetailCrm\Model\Api\Request\Customers\CustomersHistoryRequest;
use Intaro\RetailCrm\Model\Api\Request\Customers\CustomersListRequest;
use Intaro\RetailCrm\Model\Api\Request\Customers\CustomersNotesCreateRequest;
use Intaro\RetailCrm\Model\Api\Request\Customers\CustomersNotesRequest;
use Intaro\RetailCrm\Model\Api\Request\Customers\CustomersUploadRequest;
use Intaro\RetailCrm\Model\Api\Response\CompaniesResponse;
use Intaro\RetailCrm\Model\Api\Response\CreateResponse;
use Intaro\RetailCrm\Model\Api\Response\CustomerChangeResponse;
use Intaro\RetailCrm\Model\Api\Response\CustomerCorporateResponse;
use Intaro\RetailCrm\Model\Api\Response\CustomerResponse;
use Intaro\RetailCrm\Model\Api\Response\CustomersCorporateAddressesResponse;
use Intaro\RetailCrm\Model\Api\Response\CustomersCorporateContactsResponse;
use Intaro\RetailCrm\Model\Api\Response\CustomersCorporateResponse;
use Intaro\RetailCrm\Model\Api\Response\CustomersNotesResponse;
use Intaro\RetailCrm\Model\Api\Response\CustomersResponse;
use Intaro\RetailCrm\Model\Api\Response\CustomersUploadResponse;
use Intaro\RetailCrm\Model\Api\Response\HistoryResponse;
use Intaro\RetailCrm\Model\Api\Response\OperationResponse;
use RetailCrm\ApiClient;
use RetailCrm\Response\ApiResponse;

/**
 * Class ClientAdapter. It's sole purpose is to allow models usage via old API client.
 * Currently, it only implements customers and corporate customers methods (except combine method).
 * Better solution can be found later. For now, this will work just fine.
 *
 * @package Intaro\RetailCrm\Component\ApiClient
 *
 * @method ApiResponse usersList(array $filter = array(), $page = null, $limit = null)
 * @method ApiResponse usersGroups($page = null, $limit = null)
 * @method ApiResponse usersStatus($id, $status)
 * @method ApiResponse usersGet($id)
 * @method ApiResponse ordersList(array $filter = array(), $page = null, $limit = null)
 * @method ApiResponse customersCorporateNotesDelete($id)
 * @method ApiResponse ordersCreate(array $order, $site = null)
 * @method ApiResponse ordersFixExternalIds(array $ids)
 * @method ApiResponse ordersStatuses(array $ids = array(), array $externalIds = array())
 * @method ApiResponse ordersUpload(array $orders, $site = null)
 * @method ApiResponse ordersGet($id, $by = 'externalId', $site = null)
 * @method ApiResponse ordersEdit(array $order, $by = 'externalId', $site = null)
 * @method ApiResponse ordersHistory(array $filter = array(), $page = null, $limit = null)
 * @method ApiResponse ordersCombine($order, $resultOrder, $technique = 'ours')
 * @method ApiResponse ordersPaymentCreate(array $payment, $site = null)
 * @method ApiResponse ordersPaymentEdit(array $payment, $by = 'id', $site = null)
 * @method ApiResponse ordersPaymentDelete($id)
 * @method ApiResponse customersCombine(array $customers, $resultCustomer)
 * @method ApiResponse customersNotesDelete($id)
 * @method ApiResponse customFieldsList(array $filter = array(), $limit = null, $page = null)
 * @method ApiResponse customFieldsCreate($entity, $customField)
 * @method ApiResponse customFieldsEdit($entity, $customField)
 * @method ApiResponse customFieldsGet($entity, $code)
 * @method ApiResponse customDictionariesList(array $filter = array(), $limit = null, $page = null)
 * @method ApiResponse customDictionariesCreate($customDictionary)
 * @method ApiResponse customDictionariesEdit($customDictionary)
 * @method ApiResponse customDictionariesGet($code)
 * @method ApiResponse tasksList(array $filter = array(), $limit = null, $page = null)
 * @method ApiResponse tasksCreate($task, $site = null)
 * @method ApiResponse tasksEdit($task, $site = null)
 * @method ApiResponse tasksGet($id)
 * @method ApiResponse ordersPacksList(array $filter = array(), $page = null, $limit = null)
 * @method ApiResponse ordersPacksCreate(array $pack, $site = null)
 * @method ApiResponse ordersPacksHistory(array $filter = array(), $page = null, $limit = null)
 * @method ApiResponse ordersPacksGet($id)
 * @method ApiResponse ordersPacksDelete($id)
 * @method ApiResponse ordersPacksEdit(array $pack, $site = null)
 * @method ApiResponse storeInventories(array $filter = array(), $page = null, $limit = null)
 * @method ApiResponse storeInventoriesUpload(array $offers, $site = null)
 * @method ApiResponse storePricesUpload(array $prices, $site = null)
 * @method ApiResponse storeProducts(array $filter = array(), $page = null, $limit = null)
 * @method ApiResponse integrationModulesGet($code)
 * @method ApiResponse integrationModulesEdit(array $configuration)
 * @method ApiResponse deliveryTracking($code, array $statusUpdate)
 * @method ApiResponse countriesList()
 * @method ApiResponse deliveryServicesList()
 * @method ApiResponse deliveryServicesEdit(array $data)
 * @method ApiResponse deliveryTypesList()
 * @method ApiResponse deliveryTypesEdit(array $data)
 * @method ApiResponse orderMethodsList()
 * @method ApiResponse orderMethodsEdit(array $data)
 * @method ApiResponse orderTypesList()
 * @method ApiResponse orderTypesEdit(array $data)
 * @method ApiResponse paymentStatusesList()
 * @method ApiResponse paymentStatusesEdit(array $data)
 * @method ApiResponse paymentTypesList()
 * @method ApiResponse paymentTypesEdit(array $data)
 * @method ApiResponse productStatusesList()
 * @method ApiResponse productStatusesEdit(array $data)
 * @method ApiResponse storeProductsGroups(array $filter = array(), $page = null, $limit = null)
 * @method ApiResponse sitesList()
 * @method ApiResponse sitesEdit(array $data)
 * @method ApiResponse statusGroupsList()
 * @method ApiResponse statusesList()
 * @method ApiResponse statusesEdit(array $data)
 * @method ApiResponse storesList()
 * @method ApiResponse storesEdit(array $data)
 * @method ApiResponse pricesTypes()
 * @method ApiResponse pricesEdit(array $data)
 * @method ApiResponse telephonyCallsUpload(array $calls)
 * @method ApiResponse telephonyCallManager($phone, $details)
 * @method ApiResponse segmentsList(array $filter = array(), $limit = null, $page = null)
 * @method ApiResponse statisticUpdate()
 * @method ApiResponse getSite()
 * @method ApiResponse setSite($site)
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

    /**
     * Get corporate customer addresses
     *
     * @param \Intaro\RetailCrm\Model\Api\Request\Customers\CustomersCorporateAddressesRequest $request
     *
     * @return \Intaro\RetailCrm\Model\Api\Response\CustomersCorporateAddressesResponse|null
     */
    public function customersCorporateAddresses(CustomersCorporateAddressesRequest $request): ?CustomersCorporateAddressesResponse
    {
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
    public function customersCorporateAddressesCreate(CustomersCorporateAddressesCreateRequest $request): ?CreateResponse
    {
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
    public function customersCorporateAddressesEdit(CustomersCorporateAddressesEditRequest $request): ?OperationResponse
    {
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
    public function customersCorporateCompanies(CustomersCorporateCompaniesRequest $request): ?CompaniesResponse {
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
    public function customersCorporateCompaniesCreate(CustomersCorporateCompaniesCreateRequest $request): ?CreateResponse {
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
    public function customersCorporateCompaniesEdit(CustomersCorporateCompaniesEditRequest $request): ?OperationResponse {
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
     * Create corporate customer contact
     *
     * @param \Intaro\RetailCrm\Model\Api\Request\Customers\CustomersCorporateContactsCreateRequest $request
     *
     * @return \Intaro\RetailCrm\Model\Api\Response\CreateResponse|null
     */
    public function customersCorporateContactsCreate(CustomersCorporateContactsCreateRequest $request): ?CreateResponse
    {
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
    public function customersCorporateContactsEdit(CustomersCorporateContactsEditRequest $request): ?OperationResponse {
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
