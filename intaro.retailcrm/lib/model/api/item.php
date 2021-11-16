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
 * Class Item
 *
 * @package Intaro\RetailCrm\Model\Api
 */
class Item extends AbstractApiModel
{
    /**
     * ID позиции в заказе
     *
     * @var integer $id
     *
     * @Mapping\Type("integer")
     * @Mapping\SerializedName("id")
     */
    public $id;

    /**
     * Внешние идентификаторы позиции в заказе
     *
     * @var array $externalIds
     *
     * @Mapping\Type("array")
     * @Mapping\SerializedName("externalIds")
     */
    public $externalIds;

    /**
     * Торговое предложение
     *
     * @var array $offer
     *
     * @Mapping\Type("array")
     * @Mapping\SerializedName("offer")
     */
    public $offer;

    /**
     * [массив] Дополнительные свойства позиции в заказе
     *
     * @var array $properties
     *
     * @Mapping\Type("array")
     * @Mapping\SerializedName("properties")
     */
    public $properties;
}
