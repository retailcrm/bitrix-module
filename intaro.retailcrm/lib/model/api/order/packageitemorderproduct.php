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
 * Class PackageItemOrderProduct
 *
 * @package Intaro\RetailCrm\Model\Api\Order
 */
class PackageItemOrderProduct extends AbstractApiModel
{
    /**
     * @var int
     *
     * @Mapping\Type("int")
     * @Mapping\SerializedName("id")
     */
    public $id;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("externalId")
     */
    public $externalId;

    /**
     * @var \Intaro\RetailCrm\Model\Api\CodeValueModel
     *
     * @Mapping\Type("Intaro\RetailCrm\Model\Api\CodeValueModel")
     * @Mapping\SerializedName("externalIds")
     */
    public $externalIds;
}
