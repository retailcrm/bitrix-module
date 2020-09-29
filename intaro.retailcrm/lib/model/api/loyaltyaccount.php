<?php
/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Api\Request\Loyalty\Account
 * @author   retailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Model\Api;

/**
 * Class LoyaltyAccount
 *
 * @package Intaro\RetailCrm\Model\Api
 */
class LoyaltyAccount
{
    /**
     * Номер телефона
     *
     * @var boolean $active
     *
     * @Mapping\Type("boolean")
     * @Mapping\SerializedName("active")
     */
    public $active;
    
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
     * Id участия в программе лояльности
     *
     * @var integer $id
     *
     * @Mapping\Type("integer")
     * @Mapping\SerializedName("id")
     */
    public $id;
    
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
}