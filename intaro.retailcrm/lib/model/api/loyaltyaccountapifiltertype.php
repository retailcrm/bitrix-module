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
 * Class LoyaltyAccountApiFilterType
 * @package Intaro\RetailCrm\Model\Api
 */
class LoyaltyAccountApiFilterType extends AbstractApiModel
{
    /**
     * Массив ID участий в программе лояльности
     *
     * @var array $ids
     *
     * @Mapping\Type("array")
     * @Mapping\SerializedName("ids")
     */
    public $ids;
    
    /**
     * Статус [activated|deactivated|not_confirmed]
     *
     * @var string $status
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("status")
     */
    public $status;
    
    /**
     * Баланс бонусов (от)
     *
     * @var string $minAmount
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("minAmount")
     */
    public $minAmount;
    
    /**
     * Баланс бонусов (до)
     *
     * @var string $maxAmount
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("maxAmount")
     */
    public $maxAmount;
    
    /**
     * Сумма покупок (от)
     *
     * @var string $minOrdersSum
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("minOrdersSum")
     */
    public $minOrdersSum;
    
    /**
     * Сумма покупок (до)
     *
     * @var string $maxOrdersSum
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("maxOrdersSum")
     */
    public $maxOrdersSum;
    
    /**
     * Дата регистрации (от)
     *
     * @var string $createdAtFrom
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("createdAtFrom")
     */
    public $createdAtFrom;
    
    /**
     * Дата регистрации (до)
     *
     * @var string $createdAtTo
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("createdAtTo")
     */
    public $createdAtTo;
    
    /**
     * Клиент
     *
     * @var string $nickName
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("nickName")
     */
    public $nickName;
    
    /**
     * Номер телефона
     *
     * @var string $phoneNumber
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("phoneNumber")
     */
    public $phoneNumber;
    
    /**
     * Номер карты
     *
     * @var string $cardNumber
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("cardNumber")
     */
    public $cardNumber;
    
    /**
     * Внутренний ID клиента
     *
     * @var string $customerId
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("customerId")
     */
    public $customerId;
    
    /**
     * Внешний ID клиента
     *
     * @var string $customerExternalId
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("customerExternalId")
     */
    public $customerExternalId;
    
    /**
     * Массив ID Программ лояльности
     *
     * @var array $loyalties
     *
     * @Mapping\Type("array")
     * @Mapping\SerializedName("loyalties")
     */
    public $loyalties;
}
