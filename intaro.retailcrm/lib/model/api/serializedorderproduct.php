<?php
/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Api
 * @author   retailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Model\Api;

use Intaro\RetailCrm\Component\Json\Mapping;

/**
 * Class SerializedOrderProduct
 *
 * @package Intaro\RetailCrm\Model\Api
 */
class SerializedOrderProduct
{
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
     * Денежная скидка на единицу товара
     *
     * @var double $discountManualAmount
     *
     * @Mapping\Type("double")
     * @Mapping\SerializedName("discountManualAmount")
     */
    public $discountManualAmount;
    
    /**
     * Процентная скидка на единицу товара. Система округляет это значение до 2 знаков после запятой
     *
     * @var double $discountManualPercent
     *
     * @Mapping\Type("double")
     * @Mapping\SerializedName("discountManualPercent")
     */
    public $discountManualPercent;
    
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
     * Торговое предложение
     *
     * @var \Intaro\RetailCrm\Model\Api\SerializedOrderProductOffer
     *
     * @Mapping\Type("\Intaro\RetailCrm\Model\Api\SerializedOrderProductOffer")
     * @Mapping\SerializedName("offer")
     */
    public $offer;
    
    /**
     * Тип цены
     *
     * @var \Intaro\RetailCrm\Model\Api\PriceType
     *
     * @Mapping\Type("\Intaro\RetailCrm\Model\Api\PriceType")
     * @Mapping\SerializedName("priceType")
     */
    public $priceType;
    
    /**
     * Данные о доставке
     *
     * @var \Intaro\RetailCrm\Model\Api\SerializedOrderDelivery
     *
     * @Mapping\Type("\Intaro\RetailCrm\Model\Api\SerializedOrderDelivery")
     * @Mapping\SerializedName("delivery")
     */
    public $delivery;
}
