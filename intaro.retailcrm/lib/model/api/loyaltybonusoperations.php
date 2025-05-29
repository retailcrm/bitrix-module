<?php

namespace Intaro\RetailCrm\Model\Api;

use Intaro\RetailCrm\Component\Json\Mapping;

/**
 * Class Operation
 */
class LoyaltyBonusOperations
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
     * @var \Intaro\RetailCrm\Model\Api\Operation\OperationOrder $order
     *
     * @Mapping\Type("\Intaro\RetailCrm\Model\Api\Operation\OperationOrder")
     * @Mapping\SerializedName("order")
     */
    public $order;

    /**
     * Начисленные бонусы
     *
     * @var \Intaro\RetailCrm\Model\Api\Operation\OperationBonus $bonus
     *
     * @Mapping\Type("\Intaro\RetailCrm\Model\Api\Operation\OperationBonus")
     * @Mapping\SerializedName("bonus")
     */
    public $bonus;

    /**
     * Событие программы лояльности
     *
     * @var \Intaro\RetailCrm\Model\Api\Operation\OperationEvent $event
     *
     * @Mapping\Type("\Intaro\RetailCrm\Model\Api\Operation\OperationEvent")
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
