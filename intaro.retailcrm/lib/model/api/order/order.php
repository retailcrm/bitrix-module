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
 * Class Order
 *
 * @package Intaro\RetailCrm\Model\Api\Order
 */
class Order extends AbstractApiModel
{
    /**
     * ID заказа
     *
     * @var integer $id
     *
     * @Mapping\Type("integer")
     * @Mapping\SerializedName("id")
     */
    public $id;

    /**
     * Номер заказа
     *
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("number")
     */
    public $number;

    /**
     * Внешний ID корпоративного клиента
     *
     * @var string $externalId
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("externalId")
     */
    public $externalId;

    /**
     * Менеджер, прикрепленный к заказу
     *
     * @var string $managerId
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("managerId")
     */
    public $managerId;

    /**
     * Магазин
     *
     * @var string $site
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("site")
     */
    public $site;

    /**
     * Статус заказа
     *
     * @var string $status
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("status")
     */
    public $status;

    /**
     * @var \Intaro\RetailCrm\Model\Api\Order\SerializedOrderDelivery
     *
     *
     * @Mapping\Type("Intaro\RetailCrm\Model\Api\Order\SerializedOrderDelivery")
     * @Mapping\SerializedName("delivery")
     */
    public $delivery;

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
}
