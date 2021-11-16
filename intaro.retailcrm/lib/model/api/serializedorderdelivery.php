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

namespace Intaro\RetailCrm\Model\Api;

use Intaro\RetailCrm\Component\Json\Mapping;

/**
 * Class SerializedOrderDelivery
 *
 * @package Intaro\RetailCrm\Model\Api
 */
class SerializedOrderDelivery
{
    /**
     * Цена товара/SKU/Стоимость доставки
     *
     * @var double $cost
     *
     * @Mapping\Type("double")
     * @Mapping\SerializedName("cost")
     */
    public $cost;
}
