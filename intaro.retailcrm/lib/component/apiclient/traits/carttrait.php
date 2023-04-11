<?php
/**
 * PHP version 8.0
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
use Intaro\RetailCrm\Model\Api\Response\Cart\CartGetResponse;
use Intaro\RetailCrm\Model\Api\Response\Cart\CartResponse;

/**
 * Trait CartTrait
 * @package Intaro\RetailCrm\Component\ApiClient\Traits
 */
trait CartTrait
{
    /**
     * Получение текущей корзины клиента
     *
     * @param int $id
     * @param string $site
     * @param string $by
     *
     * @return CartGetResponse|null
     */
    public function cartGet(int $id, string $site, string $by = 'externalId'): ?CartGetResponse
    {
        $response = $this->client->cartGet($id, $site, $by);

        return Deserializer::deserializeArray($response->getResponseBody(), CartGetResponse::class);
    }

    /**
     * Создание или перезапись данных корзины
     *
     * @param array $cart
     * @param string $site
     *
     * @return CartResponse|null
     */
    public function cartSet(array $cart, string $site): ?CartResponse
    {
        $response = $this->client->cartSet($cart, $site);

        return Deserializer::deserializeArray($response->getResponseBody(), CartResponse::class);
    }

    /**
     * Очистка текущей корзины клиента
     *
     * @param array $cart
     * @param string $site
     *
     * @return CartResponse|null
     */
    public function cartClear(array $cart, string $site): ?CartResponse
    {
        $response = $this->client->cartClear($cart, $site);

        return Deserializer::deserializeArray($response->getResponseBody(), CartResponse::class);
    }
}
