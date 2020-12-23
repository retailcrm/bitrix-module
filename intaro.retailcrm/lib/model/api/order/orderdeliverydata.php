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
 * Class OrderDeliveryData
 *
 * @package Intaro\RetailCrm\Model\Api\Order
 */
class OrderDeliveryData extends AbstractApiModel
{
    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("externalId")
     */
    public $externalId;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("trackNumber")
     */
    public $trackNumber;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("tariff")
     */
    public $tariff;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("pickuppointId")
     */
    public $pickuppointId;

    /**
     * @var array
     *
     * @Mapping\Type("array")
     * @Mapping\SerializedName("extraData")
     */
    public $extraData;

    /**
     * @var \Intaro\RetailCrm\Model\Api\Order\Package[]
     *
     * @Mapping\Type("Intaro\RetailCrm\Model\Api\Order\Package[]")
     * @Mapping\SerializedName("packages")
     */
    public $packages;
}
