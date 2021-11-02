<?php
/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Api\Request\Customers
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Model\Api\Request\Customers;

use Intaro\RetailCrm\Component\Json\Mapping;
use Intaro\RetailCrm\Model\Api\AbstractApiModel;
use Intaro\RetailCrm\Model\Api\Request\PaginatedTrait;

/**
 * Class CustomersListRequest
 *
 * @package Intaro\RetailCrm\Model\Api\Request\Customers
 */
class CustomersListRequest extends AbstractApiModel
{
    use PaginatedTrait;

    /**
     * @var \Intaro\RetailCrm\Model\Api\Request\Filter\CustomerFilter
     *
     * @Mapping\Type("Intaro\RetailCrm\Model\Api\Request\Filter\CustomerFilter")
     * @Mapping\SerializedName("filter")
     */
    public $filter;
}
