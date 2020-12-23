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
use Intaro\RetailCrm\Model\Api\Request\ByTrait;
use Intaro\RetailCrm\Model\Api\Request\PaginatedTrait;
use Intaro\RetailCrm\Model\Api\Request\SiteScopedTrait;

/**
 * Class CustomersCorporateContactsRequest
 *
 * @package Intaro\RetailCrm\Model\Api\Request\Customers
 */
class CustomersCorporateContactsRequest extends AbstractApiModel
{
    use ByTrait;
    use PaginatedTrait;
    use SiteScopedTrait;

    /**
     * @var string
     */
    public $externalId;

    /**
     * @var \Intaro\RetailCrm\Model\Api\Request\Filter\CustomerContactFilter
     *
     * @Mapping\Type("Intaro\RetailCrm\Model\Api\Request\Filter\CustomerContactFilter")
     * @Mapping\SerializedName("filter")
     */
    public $filter;
}
