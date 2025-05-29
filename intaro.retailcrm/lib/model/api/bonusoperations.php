<?php

namespace Intaro\RetailCrm\Model\Api\Loyalty;

use Intaro\RetailCrm\Component\Json\Mapping;

/**
 * Class Operation
 */
class BonusOperations
{
    /**
     * Тип действия
     *
     * @var string $type
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("type")
     */
    public $type;

    /**
     * Дата действия
     *
     * @var \DateTime $createdAt
     *
     * @Mapping\Type("DateTime<'Y-m-d H:i:s'>")
     * @Mapping\SerializedName("createdAt")
     */
    public $createdAt;

    /**
     * Количество бонусов
     *
     * @var float $amount
     *
     * @Mapping\Type("float")
     * @Mapping\SerializedName("amount")
     */
    public $amount;

    /**
     * Связанный заказ
     *
     * @var \Intaro\RetailCrm\Model\Api\Loyalty\OperationOrder $order
     *
     * @Mapping\Type("\Intaro\RetailCrm\Model\Api\Loyalty\OperationOrder")
     * @Mapping\SerializedName("order")
     */
    public $order;

    /**
     * Начисленные бонусы
     *
     * @var \Intaro\RetailCrm\Model\Api\Loyalty\OperationBonus $bonus
     *
     * @Mapping\Type("\Intaro\RetailCrm\Model\Api\Loyalty\OperationBonus")
     * @Mapping\SerializedName("bonus")
     */
    public $bonus;

    /**
     * Событие программы лояльности
     *
     * @var \Intaro\RetailCrm\Model\Api\Loyalty\OperationEvent $event
     *
     * @Mapping\Type("\Intaro\RetailCrm\Model\Api\Loyalty\OperationEvent")
     * @Mapping\SerializedName("event")
     */
    public $event;

    /**
     * Комментарий
     *
     * @var string $comment
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("comment")
     */
    public $comment;
}
