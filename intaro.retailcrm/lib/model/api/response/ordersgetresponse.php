<?php

/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Api\Response
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

namespace Intaro\RetailCrm\Model\Api\Response;

use Intaro\RetailCrm\Component\Json\Mapping;

/**
 * Class ordersgetresponse
 * @package Intaro\RetailCrm\Model\Api\Response
 */
class OrdersGetResponse extends AbstractApiResponseModel
{
    /**
     * @var bool
     *
     * @Mapping\Type("bool")
     * @Mapping\SerializedName("success")
     */
    public $success;
    
    /**
     * @var \Intaro\RetailCrm\Model\Api\Order\Order
     *
     * @Mapping\Type("\Intaro\RetailCrm\Model\Api\Order\Order")
     * @Mapping\SerializedName("order")
     */
    public $order;
}