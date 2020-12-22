<?php
/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Api\Response\Loyalty\Account
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Model\Api\Response\Loyalty\Account;

use Intaro\RetailCrm\Model\Api\Response\AbstractApiResponseModel;
use Intaro\RetailCrm\Component\Json\Mapping;

/**
 * Class LoyaltyAccountCreateResponse
 *
 * @package Intaro\RetailCrm\Model\Api\Response\Loyalty\Account
 */
class LoyaltyAccountCreateResponse extends AbstractApiResponseModel
{
    /**
     * Результат запроса (успешный/неуспешный)
     *
     * @var boolean $success
     *
     * @Mapping\Type("boolean")
     * @Mapping\SerializedName("success")
     */
    public $success;
    
    /**
     * @var \Intaro\RetailCrm\Model\Api\LoyaltyAccount
     *
     * @Mapping\Type("\Intaro\RetailCrm\Model\Api\LoyaltyAccount")
     * @Mapping\SerializedName("loyaltyAccount")
     */
    public $loyaltyAccount;
    
    /**
     * @var array $warnings
     *
     * @Mapping\Type("array")
     * @Mapping\SerializedName("warnings")
     */
    public $warnings;
}
