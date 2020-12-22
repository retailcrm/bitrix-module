<?php
/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Api\Request\Filter
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Model\Api\Request\Filter;

use Intaro\RetailCrm\Component\Json\Mapping;
use Intaro\RetailCrm\Model\Api\AbstractApiModel;

/**
 * Class CustomersNotesFilter
 *
 * @package Intaro\RetailCrm\Model\Api\Request\Filter
 */
class CustomersNotesFilter extends AbstractApiModel
{
    /**
     * @var int[]
     *
     * @Mapping\Type("int[]")
     * @Mapping\SerializedName("ids")
     */
    public $ids;

    /**
     * @var int[]
     *
     * @Mapping\Type("int[]")
     * @Mapping\SerializedName("customerIds")
     */
    public $customerIds;

    /**
     * @var string[]
     *
     * @Mapping\Type("string[]")
     * @Mapping\SerializedName("customerExternalIds")
     */
    public $customerExternalIds;

    /**
     * @var int[]
     *
     * @Mapping\Type("int[]")
     * @Mapping\SerializedName("managerIds")
     */
    public $managerIds;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("text")
     */
    public $text;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("createdAtFrom")
     */
    public $createdAtFrom;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("createdAtTo")
     */
    public $createdAtTo;
}
