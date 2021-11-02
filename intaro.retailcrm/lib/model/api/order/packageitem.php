<?php
/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Api\Order
 * @author   retailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Model\Api\Order;

use Intaro\RetailCrm\Component\Json\Mapping;

/**
 * Class PackageItem
 *
 * @package Intaro\RetailCrm\Model\Api\Order
 */
class PackageItem extends AbstractApiModel
{
    /**
     * @var \Intaro\RetailCrm\Model\Api\Order\PackageItemOrderProduct
     *
     * @Mapping\Type("Intaro\RetailCrm\Model\Api\Order\PackageItemOrderProduct")
     * @Mapping\SerializedName("orderProduct")
     */
    public $orderProduct;

    /**
     * @var double
     *
     * @Mapping\Type("double")
     * @Mapping\SerializedName("quantity")
     */
    public $quantity;
}
