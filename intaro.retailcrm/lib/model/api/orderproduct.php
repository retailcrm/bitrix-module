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

/**
 * Class OrderProduct
 *
 * @package Intaro\RetailCrm\Model\Api
 */
class OrderProduct
{
    /**
     * Количество списанных бонусов
     *
     * @var double $bonusesChargeTotal
     *
     * @Mapping\Type("double")
     * @Mapping\SerializedName("bonusesChargeTotal")
     */
    public $bonusesChargeTotal;
    
    /**
     * Количество начисленных бонусов
     *
     * @var double $bonusesCreditTotal
     *
     * @Mapping\Type("double")
     * @Mapping\SerializedName("bonusesCreditTotal")
     */
    public $bonusesCreditTotal;
    
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
     * Цена товара/SKU
     *
     * @var double $initialPrice
     *
     * @Mapping\Type("double")
     * @Mapping\SerializedName("initialPrice")
     */
    public $initialPrice;
    
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
     * Ставка НДС
     *
     * @var string $vatRate
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("vatRate")
     */
    public $vatRate;
    
    /**
     * Количество
     *
     * @var float $quantity
     *
     * @Mapping\Type("float")
     * @Mapping\SerializedName("quantity")
     */
    public $quantity;
}