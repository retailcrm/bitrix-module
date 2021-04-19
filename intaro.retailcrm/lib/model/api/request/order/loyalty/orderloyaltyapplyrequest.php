<?php

/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Api\Request\Order\Loyalty
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

namespace Intaro\RetailCrm\Model\Api\Request\Order\Loyalty;

use Intaro\RetailCrm\Component\Json\Mapping;
use Intaro\RetailCrm\Model\Api\AbstractApiModel;

/**
 * Class OrderLoyaltyApplyRequest
 *
 * @package Intaro\RetailCrm\Model\Api\Request\Order\Loyalty
 */
class OrderLoyaltyApplyRequest extends AbstractApiModel
{
    /**
     * Символьный код магазина
     *
     * @var string $site
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("site")
     */
    public $site;

    /**
     * @var \Intaro\RetailCrm\Model\Api\SerializedOrderReference
     *
     * @Mapping\Type("\Intaro\RetailCrm\Model\Api\SerializedOrderReference")
     * @Mapping\SerializedName("order")
     */
    public $order;

    /**
     * Количество бонусов для списания
     *
     * @var float $bonuses
     *
     * @Mapping\Type("float")
     * @Mapping\SerializedName("bonuses")
     */
    public $bonuses;
}
