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
    
    /**
     * @return string
     */
    public function getPhoneNumber(): string
    {
        return $this->phoneNumber;
    }
    
    /**
     * @param string $phoneNumber
     */
    public function setPhoneNumber(string $phoneNumber): void
    {
        $this->phoneNumber = $phoneNumber;
    }
    
    /**
     * @return string
     */
    public function getCardNumber(): string
    {
        return $this->cardNumber;
    }
    
    /**
     * @param string $cardNumber
     */
    public function setCardNumber(string $cardNumber): void
    {
        $this->cardNumber = $cardNumber;
    }
    
    /**
     * @return int
     */
    public function getLoyaltyId(): int
    {
        return $this->loyaltyId;
    }
    
    /**
     * @param int $loyaltyId
     */
    public function setLoyaltyId(int $loyaltyId): void
    {
        $this->loyaltyId = $loyaltyId;
    }
    
    /**
     * @return int
     */
    public function getCustomerId(): int
    {
        return $this->customerId;
    }
    
    /**
     * @param int $customerId
     */
    public function setCustomerId(int $customerId): void
    {
        $this->customerId = $customerId;
    }
}