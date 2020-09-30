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
 * Метод применения бонусов по программе лояльности
 * Class SerializedLoyaltyOrder
 *
 * @package Intaro\RetailCrm\Model\Api
 */
class SerializedLoyaltyOrder
{
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
     * Количество списанных бонусов
     *
     * @var double $bonusesChargeTotal
     *
     * @Mapping\Type("double")
     * @Mapping\SerializedName("bonusesChargeTotal")
     */
    public $bonusesChargeTotal;
    
    /**
     * Общая сумма с учетом скидки
     *
     * @var double $totalSumm
     *
     * @Mapping\Type("double")
     * @Mapping\SerializedName("totalSumm")
     */
    public $totalSumm;
    
    /**
     * Персональная скидка на заказ
     *
     * @var double $personalDiscountPercent
     *
     * @Mapping\Type("double")
     * @Mapping\SerializedName("personalDiscountPercent")
     */
    public $personalDiscountPercent;
    
    /**
     * @var \Intaro\RetailCrm\Model\Api\LoyaltyAccount
     *
     * @Mapping\Type("\Intaro\RetailCrm\Model\Api\LoyaltyAccount")
     * @Mapping\SerializedName("loyaltyAccount")
     */
    public $loyaltyAccount;
    
    /**
     * @var \Intaro\RetailCrm\Model\Api\LoyaltyLevel
     *
     * @Mapping\Type("\Intaro\RetailCrm\Model\Api\LoyaltyLevel")
     * @Mapping\SerializedName("loyaltyLevel")
     */
    public $loyaltyLevel;
    
    /**
     * @var \Intaro\RetailCrm\Model\Api\AbstractLoyaltyEvent
     *
     * @Mapping\Type("\Intaro\RetailCrm\Model\Api\AbstractLoyaltyEvent")
     * @Mapping\SerializedName("loyaltyEvent")
     */
    public $loyaltyEvent;
    
    /**
     * @var \Intaro\RetailCrm\Model\Api\Customer
     *
     * @Mapping\Type("\Intaro\RetailCrm\Model\Api\Customer")
     * @Mapping\SerializedName("customer")
     */
    public $customer;
    
    /**
     * @var \Intaro\RetailCrm\Model\Api\SerializedOrderDelivery
     *
     * @Mapping\Type("\Intaro\RetailCrm\Model\Api\SerializedOrderDelivery")
     * @Mapping\SerializedName("delivery")
     */
    public $delivery;
    
    /**
     * Магазин
     *
     * @var string $site
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("site")
     */
    public $site;
    
    /**
     * Позиция в заказе
     *
     * @var array $items
     *
     * @Mapping\Type("array<Intaro\RetailCrm\Model\Api\OrderProduct>")
     * @Mapping\SerializedName("items")
     */
    public $items;
}
