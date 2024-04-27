<?php

/**
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Api
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

namespace Intaro\RetailCrm\Model\Api\Cart;

use Intaro\RetailCrm\Component\Json\Mapping;
use Intaro\RetailCrm\Model\Api\AbstractApiModel;

/**
 * Class CartItem
 *
 * @package Intaro\RetailCrm\Model\Api\Cart
 */
class CartItem extends AbstractApiModel
{
    /**
     * ID элемента корзины
     *
     * @var int $id
     *
     * @Mapping\Type("integer")
     * @Mapping\SerializedName("id")
     */
    public $id;

    /**
     * Количество
     *
     * @var $quantity
     *
     * @Mapping\Type("float")
     * @Mapping\SerializedName("quantity")
     */
    public $quantity;

    /**
     * Цена
     *
     * @var float $price
     *
     * @Mapping\Type("float")
     * @Mapping\SerializedName("price")
     */
    public $price;

    /**
     * Дата добавления в корзину
     *
     * @var \DateTime $createdAt
     *
     * @Mapping\Type("DateTime<'Y-m-d H:i:s'>")
     * @Mapping\SerializedName("createdAt")
     */
    public $createdAt;

    /**
     * Дата обновления элемента корзины
     *
     * @var \DateTime $updatedAt
     *
     * @Mapping\Type("DateTime<'Y-m-d H:i:s'>")
     * @Mapping\SerializedName("updatedAt")
     */
    public $updatedAt;

    /**
     * Торговое предложение
     *
     * @var array $offer
     *
     * @Mapping\Type("array")
     * @Mapping\SerializedName("offer")
     */
    public $offer;
}
