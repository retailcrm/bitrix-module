<?php

/**
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Api\Order
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

namespace Intaro\RetailCrm\Model\Api\Order;

use Intaro\RetailCrm\Component\Json\Mapping;
use Intaro\RetailCrm\Model\Api\AbstractApiModel;

/**
 * Class SerializedOrderDelivery
 *
 * @package Intaro\RetailCrm\Model\Api\Order
 */
class SerializedOrderDelivery extends AbstractApiModel
{
    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("code")
     */
    public $code;

    /**
     * @var \Intaro\RetailCrm\Model\Api\Order\OrderDeliveryData
     *
     * @Mapping\Type("Intaro\RetailCrm\Model\Api\Order\OrderDeliveryData")
     * @Mapping\SerializedName("data")
     */
    public $data;

    /**
     * @var \Intaro\RetailCrm\Model\Api\Order\SerializedDeliveryService
     *
     * @Mapping\Type("Intaro\RetailCrm\Model\Api\Order\SerializedDeliveryService")
     * @Mapping\SerializedName("service")
     */
    public $service;

    /**
     * @var double
     *
     * @Mapping\Type("double")
     * @Mapping\SerializedName("cost")
     */
    public $cost;

    /**
     * @var double
     *
     * @Mapping\Type("double")
     * @Mapping\SerializedName("netCost")
     */
    public $netCost;

    /**
     * @var \DateTime
     *
     * @Mapping\Type("DateTime<'Y-m-d H:i:s'>")
     * @Mapping\SerializedName("date")
     */
    public $date;

    /**
     * @var \Intaro\RetailCrm\Model\Api\TimeInterval
     *
     * @Mapping\Type("Intaro\RetailCrm\Model\Api\TimeInterval")
     * @Mapping\SerializedName("time")
     */
    public $time;

    //TODO:
    // order[delivery][address] model
}
