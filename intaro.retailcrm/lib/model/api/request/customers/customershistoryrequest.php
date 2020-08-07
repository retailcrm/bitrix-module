<?php
/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Api\Request\Customers
 * @author   retailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Model\Api\Request\Customers;

use Intaro\RetailCrm\Component\Json\Mapping;
use Intaro\RetailCrm\Model\Api\AbstractApiModel;
use Intaro\RetailCrm\Model\Api\Request\Filter\CustomerHistoryFilterV4Type;
use Intaro\RetailCrm\Model\Api\Request\PaginatedTrait;

/**
 * Class CustomersHistoryRequest
 *
 * @package Intaro\RetailCrm\Model\Api\Request\Customers
 */
class CustomersHistoryRequest extends AbstractApiModel
{
    use PaginatedTrait;

    /**
     * @var CustomerHistoryFilterV4Type
     *
     * @Mapping\Type("Intaro\RetailCrm\Model\Api\Request\Filter\CustomerHistoryFilterV4Type")
     * @Mapping\SerializedName("filter")
     */
    public $filter;
}
