<?php

/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Bitrix
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

namespace Intaro\RetailCrm\Model\Bitrix;

use Intaro\RetailCrm\Component\Json\Mapping;

/**
 * Class OrderLoyaltyData
 *
 * описывает HL-блок loyalty_program
 *
 * @package Intaro\RetailCrm\Model\Bitrix
 */
class OrderLoyaltyData
{
    /**
     * ID
     *
     * @var integer
     *
     * @Mapping\Type("integer")
     * @Mapping\SerializedName("ID")
     */
    public $id;

    /**
     * ID заказа
     *
     * @var integer
     *
     * @Mapping\Type("integer")
     * @Mapping\SerializedName("UF_ORDER_ID")
     */
    public $orderId;

    /**
     * ID товара
     *
     * @var integer
     *
     * @Mapping\Type("integer")
     * @Mapping\SerializedName("UF_ITEM_ID")
     */
    public $itemId;

    /**
     * ID проверочного кода
     *
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("UF_CHECK_ID")
     */
    public $checkId;

    /**
     * Списаны ли бонусы
     *
     * @var bool
     *
     * @Mapping\Type("bool")
     * @Mapping\SerializedName("UF_IS_DEBITED")
     */
    public $isDebited;

    /**
     * Количество в корзине
     *
     * @var float
     *
     * @Mapping\Type("integer")
     * @Mapping\SerializedName("UF_QUANTITY")
     */
    public $quantity;

    /**
     * ID позиции товара в корзине
     *
     * @var int $basketItemPositionId
     *
     * @Mapping\Type("integer")
     * @Mapping\SerializedName("UF_ITEM_POS_ID")
     */
    public $basketItemPositionId;

    /**
     * Размер обычной скидки на единицу товара в позиции
     *
     * Эта скидка определяется в правилах работы с корзиной
     *
     * @var float $defaultDiscount
     *
     * @Mapping\Type("float")
     * @Mapping\SerializedName("UF_DEF_DISCOUNT")
     */
    public $defaultDiscount;

    /**
     * Название товара
     *
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("UF_NAME")
     */
    public $name;

    /**
     * Количество списываемых бонусов
     *
     * @var integer
     *
     * @Mapping\Type("integer")
     * @Mapping\SerializedName("UF_BONUS_COUNT")
     */
    public $bonusCount;
}





