<?php

/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Api
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

namespace Intaro\RetailCrm\Model\Api\Order;

use Intaro\RetailCrm\Component\Json\Mapping;
use Intaro\RetailCrm\Model\Api\AbstractApiModel;

/**
 * Class OrderProduct
 *
 * @package Intaro\RetailCrm\Model\Api\Order
 */
class OrderProduct extends AbstractApiModel
{
    /**
     * ID позиции в заказе
     *
     * @var int $id
     *
     * @Mapping\Type("integer")
     * @Mapping\SerializedName("id")
     */
    public $id;
    
    /**
     * Внешние идентификаторы позиции в заказе
     *
     * @var array $externalIds
     *
     * @Mapping\Type("array<Intaro\RetailCrm\Model\Api\CodeValueModel>")
     * @Mapping\SerializedName("externalIds")
     */
    public $externalIds;
    
    /**
     * Торговое предложение
     *
     * @var array $offer
     *
     * @Mapping\Type("array")
     * @Mapping\SerializedName("offer")
     */
    public $offer;
    
    /**
     * Цена товара/SKU
     *
     * @var double $initialPrice
     *
     * @Mapping\Type("double")
     * @Mapping\SerializedName("initialPrice")
     */
    public $initialPrice;

    /**
     * Количество
     *
     * @var float $quantity
     *
     * @Mapping\Type("float")
     * @Mapping\SerializedName("quantity")
     */
    public $quantity;

    /**
     * Набор итоговых скидок на товарную позицию
     *
     * @var array $discounts
     *
     * @Mapping\Type("array<Intaro\RetailCrm\Model\Api\Order\OrderProductDiscountItem>")
     * @Mapping\SerializedName("discounts")
     */
    public $discounts;

    /**
     * Итоговая денежная скидка на единицу товара c учетом всех скидок на товар и заказ
     *
     * @var double $discountTotal
     *
     * @Mapping\Type("double")
     * @Mapping\SerializedName("discountTotal")
     */
    public $discountTotal;
    
    /**
     * Набор итоговых цен реализации с указанием количества
     *
     * @var array $prices
     *
     * @Mapping\Type("array<Intaro\RetailCrm\Model\Api\Order\OrderProductPriceItem>")
     * @Mapping\SerializedName("prices")
     */
    public $prices;
    
    /**
     * Тип цены
     *
     * @var array $priceType
     *
     * @Mapping\Type("array<Intaro\RetailCrm\Model\Api\PriceType>")
     * @Mapping\SerializedName("priceType")
     */
    public $priceType;
    
    /**
     * Дополнительные свойства позиции в заказе
     *
     * @var array $properties
     *
     * @Mapping\Type("array")
     * @Mapping\SerializedName("properties")
     */
    public $properties;
}
