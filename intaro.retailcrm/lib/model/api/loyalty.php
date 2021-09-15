<?php

namespace Intaro\RetailCrm\Model\Api;

/**
 * Class Loyalty
 *
 * @package Intaro\RetailCrm\Model\Api
 */
class Loyalty
{
    /**
     * Компании
     *
     * @var array $levels
     *
     * @Mapping\Type("array<Intaro\RetailCrm\Model\Api\LoyaltyLevel>")
     * @Mapping\SerializedName("levels")
     */
    public $levels;

    /**
     * Активна
     *
     * @var bool $active
     *
     * @Mapping\Type("boolean")
     * @Mapping\SerializedName("active")
     */
    public $active;

    /**
     * Заблокирована
     *
     * @var bool $blocked
     *
     * @Mapping\Type("boolean")
     * @Mapping\SerializedName("blocked")
     */
    public $blocked;

    /**
     * Id программы лояльности
     *
     * @var int $id
     *
     * @Mapping\Type("integer")
     * @Mapping\SerializedName("id")
     */
    public $id;

    /**
     * Заблокирована
     *
     * @var string $name
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("name")
     */
    public $name;

    /**
     * Заблокирована
     *
     * @var bool $confirmSmsCharge
     *
     * @Mapping\Type("boolean")
     * @Mapping\SerializedName("confirmSmsCharge")
     */
    public $confirmSmsCharge;

    /**
     * Заблокирована
     *
     * @var bool $confirmSmsRegistration
     *
     * @Mapping\Type("boolean")
     * @Mapping\SerializedName("confirmSmsRegistration")
     */
    public $confirmSmsRegistration;

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
     * Дата запуска
     *
     * @var \DateTime $activatedAt
     *
     * @Mapping\Type("DateTime<'Y-m-d H:i:s'>")
     * @Mapping\SerializedName("activatedAt")
     */
    public $activatedAt;

    /**
     * Дата остановки
     *
     * @var \DateTime $deactivatedAt
     *
     * @Mapping\Type("DateTime<'Y-m-d H:i:s'>")
     * @Mapping\SerializedName("deactivatedAt")
     */
    public $deactivatedAt;

    /**
     * Дата блокировки
     *
     * @var \DateTime $blockedAt
     *
     * @Mapping\Type("DateTime<'Y-m-d H:i:s'>")
     * @Mapping\SerializedName("blockedAt")
     */
    public $blockedAt;
}

