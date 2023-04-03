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

namespace Intaro\RetailCrm\Component\ApiClient;

use Intaro\RetailCrm\Component\ApiClient\Traits\BaseClientTrait;
use Intaro\RetailCrm\Component\ApiClient\Traits\CartTrait;
use Intaro\RetailCrm\Component\ApiClient\Traits\CustomersCorporateTrait;
use Intaro\RetailCrm\Component\ApiClient\Traits\CustomersTrait;
use Intaro\RetailCrm\Component\ApiClient\Traits\LoyaltyTrait;
use Intaro\RetailCrm\Component\ApiClient\Traits\OrderTrait;
use Intaro\RetailCrm\Component\Json\Deserializer;
use Intaro\RetailCrm\Model\Api\Response\Settings\CredentialsResponse;
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
 * @method ApiResponse ordersFixExternalIds(array $ids)
 * @method ApiResponse ordersStatuses(array $ids = array(), array $externalIds = array())
 * @method ApiResponse ordersUpload(array $orders, $site = null)
 * @method ApiResponse ordersGet($id, $by = 'externalId', $site = null)
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
    use BaseClientTrait;
    use CustomersTrait;
    use CustomersCorporateTrait;
    use LoyaltyTrait;
    use OrderTrait;
    use CartTrait;

    /** @var string */
    public const ID = 'id';

    /** @var string */
    public const EXTERNAL_ID = 'externalId';

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

        throw new \RuntimeException(sprintf("Method '%s' doesn't exist.", $name));
    }

    /**
     * @return \Intaro\RetailCrm\Model\Api\Response\Settings\CredentialsResponse
     */
    public function getCredentials(): CredentialsResponse
    {
        $response = $this->client->getCredentials();

        return Deserializer::deserializeArray($response->getResponseBody(), CredentialsResponse::class);
    }
}
