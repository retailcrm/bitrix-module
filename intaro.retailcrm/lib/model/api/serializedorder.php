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
    public $discountManualAmount;
    
    /**
     * Процентная скидка на весь заказ. Система округляет это значение до 2 знаков после запятой
     *
     * @var double $discountManualPercent
     *
     * @Mapping\Type("double")
     * @Mapping\SerializedName("discountManualPercent")
     */
    public $discountManualPercent;
    
    /**
     * Клиент
     *
     * @var \Intaro\RetailCrm\Model\Api\SerializedRelationCustomer
     *
     * @Mapping\Type("\Intaro\RetailCrm\Model\Api\SerializedRelationCustomer")
     * @Mapping\SerializedName("customer")
     */
    public $customer;
    
    /**
     * @var array $items
     *
     * @Mapping\Type("array<Intaro\RetailCrm\Model\Api\SerializedOrderProduct>")
     * @Mapping\SerializedName("items")
     */
    public $items;
    
    /**
     * @var \Intaro\RetailCrm\Model\Api\SerializedOrderDelivery
     *
     * @Mapping\Type("\Intaro\RetailCrm\Model\Api\SerializedOrderDelivery")
     * @Mapping\SerializedName("delivery")
     */
    public $delivery;
}
