<?php

/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Component\ApiClient\Traits
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

namespace Intaro\RetailCrm\Component\ApiClient\Traits;

use Intaro\RetailCrm\Component\Json\Deserializer;
use Intaro\RetailCrm\Model\Api\Response\OrdersCreateResponse;
use Intaro\RetailCrm\Model\Api\Response\OrdersEditResponse;
use Intaro\RetailCrm\Model\Api\Response\OrdersGetResponse;

/**
 * Trait OrderTrait
 * @package Intaro\RetailCrm\Component\ApiClient\Traits
 */
trait OrderTrait
{
    /**
     * @param array       $request
     * @param string|null $site
     * @return \Intaro\RetailCrm\Model\Api\Response\OrdersCreateResponse|null
     */
    public function createOrder(array $request, string $site = null): ?OrdersCreateResponse
    {
        $response = $this->client->ordersCreate($request, 'externalId', $site);
        
        return Deserializer::deserializeArray($response->getResponseBody(), OrdersCreateResponse::class);
    }
    
    /**
     * @param array       $request
     * @param string|null $site
     * @return \Intaro\RetailCrm\Model\Api\Response\OrdersEditResponse|null
     */
    public function editOrder(array $request, string $site = null): ?OrdersEditResponse
    {
        $response = $this->client->ordersEdit($request, 'externalId', $site);
        
        return Deserializer::deserializeArray($response->getResponseBody(), OrdersEditResponse::class);
    }
    
    /**
     * @param int         $orderId
     * @param string|null $site
     * @return \Intaro\RetailCrm\Model\Api\Response\OrdersGetResponse|null
     */
    public function getOrder(int $orderId, string $site = null): ?OrdersGetResponse
    {
        $response = $this->client->ordersGet($orderId, 'externalId', $site);
        
        return Deserializer::deserializeArray($response->getResponseBody(), OrdersGetResponse::class);
    }
}
