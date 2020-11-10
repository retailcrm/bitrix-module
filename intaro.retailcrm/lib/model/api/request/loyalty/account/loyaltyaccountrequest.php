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
use Intaro\RetailCrm\Model\Api\LoyaltyAccount;

/**
 * Class LoyaltyAccountRequest
 *
 * @package Intaro\RetailCrm\Model\Api\Request\Loyalty\Account
 */
class LoyaltyAccountRequest extends AbstractApiModel
{
    /**
     * Количество элементов в ответе (по умолчанию равно 20)
     *
     * @var integer $limit
     *
     * @Mapping\Type("integer")
     * @Mapping\SerializedName("limit")
     */
    public $limit;
    
    /**
     * Номер страницы с результатами (по умолчанию равно 1)
     *
     * @var integer $page
     *
     * @Mapping\Type("integer")
     * @Mapping\SerializedName("page")
     */
    public $page;
    
    /**
     * @var \Intaro\RetailCrm\Model\Api\LoyaltyAccountApiFilterType
     *
     * @Mapping\Type("\Intaro\RetailCrm\Model\Api\LoyaltyAccountApiFilterType")
     * @Mapping\SerializedName("filter")
     */
    public $filter;
}
