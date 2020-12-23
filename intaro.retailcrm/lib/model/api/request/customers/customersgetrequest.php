<?php
/**
 * PHP version 7.1
 *
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
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("id")
     */
    public $id;
}
