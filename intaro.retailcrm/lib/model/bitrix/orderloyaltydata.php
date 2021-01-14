<?php

/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Bitrix
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Model\Bitrix;

use Intaro\RetailCrm\Component\Json\Mapping;

/**
 * Class OrderLoyaltyData
 *
 * описывает HL-блок loyalty_program
 *
 * @package Intaro\RetailCrm\Model\Bitrix
 */
class OrderLoyaltyData
{
    /**
     * ID заказа
     *
     * @var integer
     *
     * @Mapping\Type("integer")
     * @Mapping\SerializedName("UF_ORDER_ID")
     */
    public $orderId;
    
    /**
     * Скидка в денежном выражении
     *
     * @var integer
     *
     * @Mapping\Type("integer")
     * @Mapping\SerializedName("UF_CASH_DISCOUNT")
     */
    public $cashDiscount;
    
    /**
     * Курс бонуса
     *
     * @var integer
     *
     * @Mapping\Type("integer")
     * @Mapping\SerializedName("UF_BONUS_RATE")
     */
    public $bonusRate;
    
    /**
     * Количество списываемых бонусов
     *
     * @var integer
     *
     * @Mapping\Type("integer")
     * @Mapping\SerializedName("UF_BONUS_COUNT")
     */
    public $bonusCount;
    
    /**
     * ID проверочного кода
     *
     * @var string
     *
     * @Mapping\Type("integer")
     * @Mapping\SerializedName("UF_CHECK_ID")
     */
    public $checkId;
    
    /**
     * Списаны ли бонусы
     *
     * @var bool
     *
     * @Mapping\Type("bool")
     * @Mapping\SerializedName("UF_IS_DEBITED")
     * @Mapping\BitrixBoolean
     */
    public $isDebited;
}
