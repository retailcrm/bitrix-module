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
namespace Intaro\RetailCrm\Model\Api;

use Intaro\RetailCrm\Component\Json\Mapping;

/**
 * Class SerializedOrder
 *
 * @package Intaro\RetailCrm\Model\Api
 */
class SerializedOrder
{
    /**
     * Денежная скидка на весь заказ
     *
     * @var double $discountManualAmount
     *
     * @Mapping\Type("double")
     * @Mapping\SerializedName("discountManualAmount")
     */
    private $discountManualAmount;
    
    /**
     * Процентная скидка на весь заказ. Система округляет это значение до 2 знаков после запятой
     *
     * @var double $discountManualPercent
     *
     * @Mapping\Type("double")
     * @Mapping\SerializedName("discountManualPercent")
     */
    private $discountManualPercent;
    
    /**
     * Клиент
     *
     * @var \Intaro\RetailCrm\Model\Api\SerializedRelationCustomer
     *
     * @Mapping\Type("\Intaro\RetailCrm\Model\Api\SerializedRelationCustomer")
     * @Mapping\SerializedName("customer")
     */
    private $customer;
    
    /**
     * @var array $items
     *
     * @Mapping\Type("array<Intaro\RetailCrm\Model\Api\SerializedOrderProduct>")
     * @Mapping\SerializedName("items")
     */
    private $items;
    
    /**
     * @var \Intaro\RetailCrm\Model\Api\SerializedOrderDelivery
     *
     * @Mapping\Type("\Intaro\RetailCrm\Model\Api\SerializedOrderDelivery")
     * @Mapping\SerializedName("delivery")
     */
    private $delivery;
    
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
     * @return \Intaro\RetailCrm\Model\Api\SerializedRelationCustomer
     */
    public function getCustomer(): \Intaro\RetailCrm\Model\Api\SerializedRelationCustomer
    {
        return $this->customer;
    }
    
    /**
     * @param \Intaro\RetailCrm\Model\Api\SerializedRelationCustomer $customer
     */
    public function setCustomer(\Intaro\RetailCrm\Model\Api\SerializedRelationCustomer $customer): void
    {
        $this->customer = $customer;
    }
    
    /**
     * @return float
     */
    public function getDiscountManualPercent(): float
    {
        return $this->discountManualPercent;
    }
    
    /**
     * @param float $discountManualPercent
     */
    public function setDiscountManualPercent(float $discountManualPercent): void
    {
        $this->discountManualPercent = $discountManualPercent;
    }
    
    /**
     * @return float
     */
    public function getDiscountManualAmount(): float
    {
        return $this->discountManualAmount;
    }
    
    /**
     * @param float $discountManualAmount
     */
    public function setDiscountManualAmount(float $discountManualAmount): void
    {
        $this->discountManualAmount = $discountManualAmount;
    }
}
