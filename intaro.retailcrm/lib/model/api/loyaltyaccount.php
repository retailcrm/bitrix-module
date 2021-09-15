<?php

/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Api\Request\Loyalty\Account
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

namespace Intaro\RetailCrm\Model\Api;

use Intaro\RetailCrm\Component\Json\Mapping;

/**
 * Class LoyaltyAccount
 *
 * @package Intaro\RetailCrm\Model\Api
 */
class LoyaltyAccount
{
    /**
     * Активность аккаунта
     *
     * @var bool $active
     *
     * @Mapping\Type("boolean")
     * @Mapping\SerializedName("active")
     */
    public $active;

    /**
     * Id участия в программе лояльности
     *
     * @var int $id
     *
     * @Mapping\Type("integer")
     * @Mapping\SerializedName("id")
     */
    public $id;

    /**
     * Программа лояльности
     *
     * @var \Intaro\RetailCrm\Model\Api\Loyalty
     *
     * @Mapping\Type("\Intaro\RetailCrm\Model\Api\Loyalty")
     * @Mapping\SerializedName("$loyalty")
     */
    public $loyalty;

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
     * Количество бонусов
     *
     * @var float $amount
     *
     * @Mapping\Type("float")
     * @Mapping\SerializedName("amount")
     */
    public $amount;

    /**
     * Дата создания
     *
     * @var \DateTime $createdAt
     *
     * @Mapping\Type("DateTime<'Y-m-d H:i:s'>")
     * @Mapping\SerializedName("createdAt")
     */
    public $createdAt;

    /**
     * @var \DateTime $activatedAt
     *
     * @Mapping\Type("DateTime<'Y-m-d H:i:s'>")
     * @Mapping\SerializedName("activatedAt")
     */
    public $activatedAt;

    /**
     * Идентификатор последней смс-верификации
     *
     * @var string $lastCheckId
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("lastCheckId")
     */
    public $lastCheckId;

    /**
     * Сумма покупок
     *
     * @var float $ordersSum
     *
     * @Mapping\Type("float")
     * @Mapping\SerializedName("ordersSum")
     */
    public $ordersSum;

    /**
     * Необходимая сумма покупок для перехода на след уровень
     *
     * @var float $nextLevelSum
     *
     * @Mapping\Type("float")
     * @Mapping\SerializedName("nextLevelSum")
     */
    public $nextLevelSum;

    /**
     * Дата верификации номера телефона
     *
     * @var \DateTime $confirmedPhoneAt
     *
     * @Mapping\Type("DateTime<'Y-m-d H:i:s'>")
     * @Mapping\SerializedName("confirmedPhoneAt")
     */
    public $confirmedPhoneAt;

    /**
     * @var \Intaro\RetailCrm\Model\Api\LoyaltyLevel
     *
     * @Mapping\Type("\Intaro\RetailCrm\Model\Api\LoyaltyLevel")
     * @Mapping\SerializedName("level")
     */
    public $loyaltyLevel;

    /**
     * @var \Intaro\RetailCrm\Model\Api\Customer
     *
     * @Mapping\Type("\Intaro\RetailCrm\Model\Api\Customer")
     * @Mapping\SerializedName("customer")
     */
    public $customer;

    /**
     * @var array $customFields
     *
     * @Mapping\Type("array")
     * @Mapping\SerializedName("customFields")
     */
    public $customFields;
}
