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
namespace Intaro\RetailCrm\Model\Api\Request\Loyalty\Account;

use Intaro\RetailCrm\Component\Json\Mapping;
use Intaro\RetailCrm\Model\Api\AbstractApiModel;
use Intaro\RetailCrm\Model\Api\LoyaltyAccount;

/**
 * Class LoyaltyAccountEditRequest
 *
 * @package Intaro\RetailCrm\Model\Api\Request\Loyalty\Account
 */
class LoyaltyAccountEditRequest extends AbstractApiModel
{
    /**
     * @var \Intaro\RetailCrm\Model\Api\LoyaltyAccount
     *
     * @Mapping\Type("\Intaro\RetailCrm\Model\Api\LoyaltyAccount")
     * @Mapping\SerializedName("loyaltyAccount")
     */
    public $loyaltyAccount;
}
