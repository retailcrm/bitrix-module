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
use Intaro\RetailCrm\Model\Api\Request\PaginatedTrait;

/**
 * Class CustomersCorporateListRequest
 *
 * @package Intaro\RetailCrm\Model\Api\Request\Customers
 */
class CustomersCorporateListRequest extends AbstractApiModel
{
    use PaginatedTrait;

    /**
     * @var \Intaro\RetailCrm\Model\Api\Request\Filter\CustomersCorporateFilter
     *
     * @Mapping\Type("Intaro\RetailCrm\Model\Api\Request\Filter\CustomersCorporateFilter")
     * @Mapping\SerializedName("filter")
     */
    public $filter;
}
