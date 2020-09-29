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
    private $bonusesCreditTotal;
    
    /**
     * Количество списанных бонусов
     *
     * @var double $bonusesChargeTotal
     *
     * @Mapping\Type("double")
     * @Mapping\SerializedName("bonusesChargeTotal")
     */
    private $bonusesChargeTotal;
    
    /**
     * Общая сумма с учетом скидки
     *
     * @var double $totalSumm
     *
     * @Mapping\Type("double")
     * @Mapping\SerializedName("totalSumm")
     */
    private $totalSumm;
    
    /**
     * Персональная скидка на заказ
     *
     * @var double $personalDiscountPercent
     *
     * @Mapping\Type("double")
     * @Mapping\SerializedName("personalDiscountPercent")
     */
    private $personalDiscountPercent;
    
    /**
     * @var \Intaro\RetailCrm\Model\Api\LoyaltyAccount
     *
     * @Mapping\Type("\Intaro\RetailCrm\Model\Api\LoyaltyAccount")
     * @Mapping\SerializedName("loyaltyAccount")
     */
    private $loyaltyAccount;
    
    /**
     * @var \Intaro\RetailCrm\Model\Api\LoyaltyLevel
     *
     * @Mapping\Type("\Intaro\RetailCrm\Model\Api\LoyaltyLevel")
     * @Mapping\SerializedName("loyaltyLevel")
     */
    private $loyaltyLevel;
    
    /**
     * @var \Intaro\RetailCrm\Model\Api\AbstractLoyaltyEvent
     *
     * @Mapping\Type("\Intaro\RetailCrm\Model\Api\AbstractLoyaltyEvent")
     * @Mapping\SerializedName("loyaltyEvent")
     */
    private $loyaltyEvent;
    
    /**
     * @var \Intaro\RetailCrm\Model\Api\Customer
     *
     * @Mapping\Type("\Intaro\RetailCrm\Model\Api\Customer")
     * @Mapping\SerializedName("customer")
     */
    private $customer;
    
    /**
     * @var \Intaro\RetailCrm\Model\Api\SerializedOrderDelivery
     *
     * @Mapping\Type("\Intaro\RetailCrm\Model\Api\SerializedOrderDelivery")
     * @Mapping\SerializedName("delivery")
     */
    private $delivery;
    
    /**
     * Магазин
     *
     * @var string $site
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("site")
     */
    private $site;
    
    /**
     * Позиция в заказе
     *
     * @var array $items
     *
     * @Mapping\Type("array<Intaro\RetailCrm\Model\Api\OrderProduct>")
     * @Mapping\SerializedName("items")
     */
    private $items;
    
    /**
     * @return float
     */
    public function getBonusesCreditTotal(): float
    {
        return $this->bonusesCreditTotal;
    }
    
    /**
     * @param float $bonusesCreditTotal
     */
    public function setBonusesCreditTotal(float $bonusesCreditTotal): void
    {
        $this->bonusesCreditTotal = $bonusesCreditTotal;
    }
    
    /**
     * @return float
     */
    public function getBonusesChargeTotal(): float
    {
        return $this->bonusesChargeTotal;
    }
    
    /**
     * @param float $bonusesChargeTotal
     */
    public function setBonusesChargeTotal(float $bonusesChargeTotal): void
    {
        $this->bonusesChargeTotal = $bonusesChargeTotal;
    }
    
    /**
     * @return float
     */
    public function getTotalSumm(): float
    {
        return $this->totalSumm;
    }
    
    /**
     * @param float $totalSumm
     */
    public function setTotalSumm(float $totalSumm): void
    {
        $this->totalSumm = $totalSumm;
    }
    
    /**
     * @return float
     */
    public function getPersonalDiscountPercent(): float
    {
        return $this->personalDiscountPercent;
    }
    
    /**
     * @param float $personalDiscountPercent
     */
    public function setPersonalDiscountPercent(float $personalDiscountPercent): void
    {
        $this->personalDiscountPercent = $personalDiscountPercent;
    }
    
    /**
     * @return \Intaro\RetailCrm\Model\Api\LoyaltyAccount
     */
    public function getLoyaltyAccount(): \Intaro\RetailCrm\Model\Api\LoyaltyAccount
    {
        return $this->loyaltyAccount;
    }
    
    /**
     * @param \Intaro\RetailCrm\Model\Api\LoyaltyAccount $loyaltyAccount
     */
    public function setLoyaltyAccount(\Intaro\RetailCrm\Model\Api\LoyaltyAccount $loyaltyAccount): void
    {
        $this->loyaltyAccount = $loyaltyAccount;
    }
    
    /**
     * @return \Intaro\RetailCrm\Model\Api\LoyaltyLevel
     */
    public function getLoyaltyLevel(): \Intaro\RetailCrm\Model\Api\LoyaltyLevel
    {
        return $this->loyaltyLevel;
    }
    
    /**
     * @param \Intaro\RetailCrm\Model\Api\LoyaltyLevel $loyaltyLevel
     */
    public function setLoyaltyLevel(\Intaro\RetailCrm\Model\Api\LoyaltyLevel $loyaltyLevel): void
    {
        $this->loyaltyLevel = $loyaltyLevel;
    }
    
    /**
     * @return \Intaro\RetailCrm\Model\Api\AbstractLoyaltyEvent
     */
    public function getLoyaltyEvent(): \Intaro\RetailCrm\Model\Api\AbstractLoyaltyEvent
    {
        return $this->loyaltyEvent;
    }
    
    /**
     * @param \Intaro\RetailCrm\Model\Api\AbstractLoyaltyEvent $loyaltyEvent
     */
    public function setLoyaltyEvent(\Intaro\RetailCrm\Model\Api\AbstractLoyaltyEvent $loyaltyEvent): void
    {
        $this->loyaltyEvent = $loyaltyEvent;
    }
    
    /**
     * @return \Intaro\RetailCrm\Model\Api\Customer
     */
    public function getCustomer(): \Intaro\RetailCrm\Model\Api\Customer
    {
        return $this->customer;
    }
    
    /**
     * @param \Intaro\RetailCrm\Model\Api\Customer $customer
     */
    public function setCustomer(\Intaro\RetailCrm\Model\Api\Customer $customer): void
    {
        $this->customer = $customer;
    }
    
    /**
     * @return \Intaro\RetailCrm\Model\Api\SerializedOrderDelivery
     */
    public function getDelivery(): \Intaro\RetailCrm\Model\Api\SerializedOrderDelivery
    {
        return $this->delivery;
    }
    
    /**
     * @param \Intaro\RetailCrm\Model\Api\SerializedOrderDelivery $delivery
     */
    public function setDelivery(\Intaro\RetailCrm\Model\Api\SerializedOrderDelivery $delivery): void
    {
        $this->delivery = $delivery;
    }
    
    /**
     * @return array
     */
    public function getItems(): array
    {
        return $this->items;
    }
    
    /**
     * @param array $items
     */
    public function setItems(array $items): void
    {
        $this->items = $items;
    }
    
    /**
     * @return string
     */
    public function getSite(): string
    {
        return $this->site;
    }
    
    /**
     * @param string $site
     */
    public function setSite(string $site): void
    {
        $this->site = $site;
    }
}
