<?php

/**
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Api\Request
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

namespace Intaro\RetailCrm\Model\Api\Request\Customers;

use Intaro\RetailCrm\Component\Json\Mapping;
use Intaro\RetailCrm\Model\Api\AbstractApiModel;
use Intaro\RetailCrm\Model\Api\Request\ByTrait;
use Intaro\RetailCrm\Model\Api\Request\SiteScopedTrait;

/**
 * Class CustomersGetRequest
 *
 * @package Intaro\RetailCrm\Model\Api\Request\Customers
 */
class CustomersGetRequest extends AbstractApiModel
{
    use ByTrait;
    use SiteScopedTrait;

    /**
     * @var int
     *
     * @Mapping\Type("int")
     * @var int
     *
     * @Mapping\Type("int")
     * @Mapping\SerializedName("id")
     */
    public $id;
}
