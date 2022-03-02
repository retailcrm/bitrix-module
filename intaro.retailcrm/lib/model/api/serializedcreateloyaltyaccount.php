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
     * ID клиента
     *
     * @var \Intaro\RetailCrm\Model\Api\IdentifiersPair
     *
     * @Mapping\Type("\Intaro\RetailCrm\Model\Api\IdentifiersPair")
     * @Mapping\SerializedName("customer")
     */
    public $customer;
    
    /**
     * Ассоциативный массив пользовательских полей
     *
     * @var array $customFields
     *
     * @Mapping\Type("array")
     * @Mapping\SerializedName("customFields")
     */
    public $customFields;
}
