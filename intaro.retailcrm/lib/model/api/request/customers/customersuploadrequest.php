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
use Intaro\RetailCrm\Model\Api\Request\SiteScopedTrait;

/**
 * Class CustomersUploadRequest
 *
 * @package Intaro\RetailCrm\Model\Api\Request\Customers
 */
class CustomersUploadRequest extends AbstractApiModel
{
    use SiteScopedTrait;

    /**
     * @var \Intaro\RetailCrm\Model\Api\Customer[]
     *
     * @Mapping\Type("Intaro\RetailCrm\Model\Api\Customer[]")
     * @Mapping\SerializedName("customers")
     */
    public $customers;
}
