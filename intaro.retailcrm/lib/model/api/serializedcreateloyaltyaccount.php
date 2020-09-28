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
 * Class SerializedCreateLoyaltyAccount
 *
 * @package Intaro\RetailCrm\Model\Api
 */
class SerializedCreateLoyaltyAccount
{
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
     * ID участия
     *
     * @var integer $loyaltyId
     *
     * @Mapping\Type("integer")
     * @Mapping\SerializedName("loyaltyId")
     */
    public $loyaltyId;
    
    /**
     * 	ID клиента
     *
     * @var integer $customerId
     *
     * @Mapping\Type("integer")
     * @Mapping\SerializedName("customerId")
     */
    public $customerId;
}