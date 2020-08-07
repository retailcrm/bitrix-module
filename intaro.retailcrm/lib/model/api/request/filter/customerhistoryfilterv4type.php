<?php
/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Api\Request\Filter
 * @author   retailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Model\Api\Request\Filter;

use Intaro\RetailCrm\Component\Json\Mapping;
use Intaro\RetailCrm\Model\Api\AbstractApiModel;

/**
 * Class CustomerHistoryFilterV4Type
 *
 * @package Intaro\RetailCrm\Model\Api\Request\Filter
 */
class CustomerHistoryFilterV4Type extends AbstractApiModel
{
    /**
     * @var int
     *
     * @Mapping\Type("int")
     * @Mapping\SerializedName("customerId")
     */
    public $customerId;

    /**
     * @var int
     *
     * @Mapping\Type("int")
     * @Mapping\SerializedName("sinceId")
     */
    public $sinceId;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("customerExternalId")
     */
    public $customerExternalId;

    /**
     * @var \DateTime
     *
     * @Mapping\Type("DateTime<'Y-m-d H:i:s'>")
     * @Mapping\SerializedName("startDate")
     */
    public $startDate;

    /**
     * @var \DateTime
     *
     * @Mapping\Type("DateTime<'Y-m-d H:i:s'>")
     * @Mapping\SerializedName("endDate")
     */
    public $endDate;
}
