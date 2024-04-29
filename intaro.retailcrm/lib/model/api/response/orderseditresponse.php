<?php

/**
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
 * Class OrdersEditResponse
 * @package Intaro\RetailCrm\Model\Api\Response
 */
class OrdersEditResponse extends AbstractApiResponseModel
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
