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
namespace Intaro\RetailCrm\Model\Api\Request\Loyalty\Account;

use Intaro\RetailCrm\Component\Json\Mapping;
use Intaro\RetailCrm\Model\Api\AbstractApiModel;
use Intaro\RetailCrm\Model\Api\SerializedCreateLoyaltyAccount;

/**
 * Class LoyaltyAccountCreateRequest
 *
 * @package Intaro\RetailCrm\Model\Api\Request\Loyalty\Account
 */
class LoyaltyAccountCreateRequest extends AbstractApiModel
{
    /**
     * @var \Intaro\RetailCrm\Model\Api\SerializedCreateLoyaltyAccount
     *
     * @Mapping\Type("\Intaro\RetailCrm\Model\Api\SerializedCreateLoyaltyAccount")
     * @Mapping\SerializedName("loyalty_account")
     */
    public $loyaltyAccount;
}
