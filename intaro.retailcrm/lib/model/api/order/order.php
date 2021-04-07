<?php

namespace Intaro\RetailCrm\Model\Api\Order;

use Intaro\RetailCrm\Model\Api\AbstractApiModel;
use Intaro\RetailCrm\Component\Json\Mapping;

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
     * Денежная скидка на весь заказ
     *
     * @var double $discountManualAmount
     *
     * @Mapping\Type("double")
     * @Mapping\SerializedName("discountManualAmount")
     */
    public $discountManualAmount;
    
    /**
     * Процентная скидка на весь заказ
     *
     * @var double $discountManualPercent
     *
     * @Mapping\Type("double")
     * @Mapping\SerializedName("discountManualPercent")
     */
    public $discountManualPercent;
    
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
     * Позиции в заказе
     *
     * @var array $items
     *
     * @Mapping\Type("array<Intaro\RetailCrm\Model\Api\Order\OrderProduct>")
     * @Mapping\SerializedName("items")
     */
    public $items;
    
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
