<?php

/**
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Api\Response\Loyalty\Account
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

namespace Intaro\RetailCrm\Model\Api\Response\Loyalty\Account;

use Intaro\RetailCrm\Component\Json\Mapping;
use Intaro\RetailCrm\Model\Api\PaginationResponse;
use Intaro\RetailCrm\Model\Api\Response\AbstractApiResponseModel;

/**
 * Class LoyaltyAccountsResponse
 *
 * @package Intaro\RetailCrm\Model\Api\Request\Loyalty\Account
 */
class LoyaltyAccountsResponse extends AbstractApiResponseModel
{
    /**
     * Результат запроса (успешный/неуспешный)
     *
     * @var bool $success
     *
     * @Mapping\Type("boolean")
     * @Mapping\SerializedName("success")
     */
    public $success;

    /**
     * @var PaginationResponse
     *
     * @Mapping\Type("\Intaro\RetailCrm\Model\Api\PaginationResponse")
     * @Mapping\SerializedName("pagination")
     */
    public $pagination;

    /**
     * @var array $loyaltyAccounts
     *
     * @Mapping\Type("array<Intaro\RetailCrm\Model\Api\LoyaltyAccount>")
     * @Mapping\SerializedName("loyaltyAccounts")
     */
    public $loyaltyAccounts;
}
