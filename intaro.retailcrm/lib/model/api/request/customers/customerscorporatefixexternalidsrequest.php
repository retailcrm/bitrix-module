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

/**
 * Class CustomersCorporateFixExternalIdsRequest
 *
 * @package Intaro\RetailCrm\Model\Api\Request\Customers
 */
class CustomersCorporateFixExternalIdsRequest extends AbstractApiModel
{
    /**
     * @var \Intaro\RetailCrm\Model\Api\IdentifiersPair[]
     *
     * @Mapping\Type("Intaro\RetailCrm\Model\Api\IdentifiersPair[]")
     * @Mapping\SerializedName("customers")
     */
    public $customers;
}
