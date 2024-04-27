<?php

/**
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

/**
 * Class CustomersFixExternalIdsRequest
 *
 * @package Intaro\RetailCrm\Model\Api\Request\Customers
 */
class CustomersFixExternalIdsRequest extends AbstractApiModel
{
    /**
     * @var \Intaro\RetailCrm\Model\Api\IdentifiersPair[]
     *
     * @Mapping\Type("Intaro\RetailCrm\Model\Api\IdentifiersPair[]")
     * @Mapping\SerializedName("customers")
     */
    public $customers;
}
