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
use Intaro\RetailCrm\Model\Api\Request\SiteScopedTrait;

/**
 * Class CustomersNotesCreateRequest
 *
 * @package Intaro\RetailCrm\Model\Api\Request\Customers
 */
class CustomersNotesCreateRequest extends AbstractApiModel
{
    use SiteScopedTrait;

    /**
     * @var \Intaro\RetailCrm\Model\Api\CustomerNote
     *
     * @Mapping\Type("Intaro\RetailCrm\Model\Api\CustomerNote")
     * @Mapping\SerializedName("filter")
     */
    public $note;
}
