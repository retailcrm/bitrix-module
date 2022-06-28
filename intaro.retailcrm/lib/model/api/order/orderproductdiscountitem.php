<?php

/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Api
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

namespace Intaro\RetailCrm\Model\Api\Order;

use Intaro\RetailCrm\Model\Api\AbstractApiModel;
use Intaro\RetailCrm\Component\Json\Mapping;

/**
 * Class OrderProductPriceItem
 * @package Intaro\RetailCrm\Model\Api\Order
 */
class OrderProductDiscountItem extends AbstractApiModel
{
    /**
     * Тип скидки
     *
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("type")
     */
    public $type;
    
    /**
     * Денежная величина скидки на товарную позицию
     *
     * @var float $amount
     *
     * @Mapping\Type("float")
     * @Mapping\SerializedName("amount")
     */
    public $amount;
}
