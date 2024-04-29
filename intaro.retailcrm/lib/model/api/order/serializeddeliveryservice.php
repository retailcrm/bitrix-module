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

use Intaro\RetailCrm\Model\Api\AbstractApiModel;
use Intaro\RetailCrm\Component\Json\Mapping;

/**
 * Class SerializedDeliveryService
 *
 * @package Intaro\RetailCrm\Model\Api\Order
 */
class SerializedDeliveryService extends AbstractApiModel
{
    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("name")
     */
    public $name;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("code")
     */
    public $code;

    /**
     * @var bool
     *
     * @Mapping\Type("bool")
     * @Mapping\SerializedName("active")
     */
    public $active;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("deliveryType")
     */
    public $deliveryType;
}
