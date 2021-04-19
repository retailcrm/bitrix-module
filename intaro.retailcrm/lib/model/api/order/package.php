<?php

/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Api\Order
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

namespace Intaro\RetailCrm\Model\Api\Order;

use Intaro\RetailCrm\Component\Json\Mapping;

/**
 * Class Package
 *
 * @package Intaro\RetailCrm\Model\Api\Order
 */
class Package extends AbstractApiModel
{
    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("packageId")
     */
    public $packageId;

    /**
     * @var double
     *
     * @Mapping\Type("double")
     * @Mapping\SerializedName("weight")
     */
    public $weight;

    /**
     * @var int
     *
     * @Mapping\Type("int")
     * @Mapping\SerializedName("length")
     */
    public $length;

    /**
     * @var int
     *
     * @Mapping\Type("int")
     * @Mapping\SerializedName("width")
     */
    public $width;

    /**
     * @var int
     *
     * @Mapping\Type("int")
     * @Mapping\SerializedName("height")
     */
    public $height;

    /**
     * @var \Intaro\RetailCrm\Model\Api\Order\PackageItem[]
     *
     * @Mapping\Type("Intaro\RetailCrm\Model\Api\Order\PackageItem[]")
     * @Mapping\SerializedName("items")
     */
    public $items;
}
