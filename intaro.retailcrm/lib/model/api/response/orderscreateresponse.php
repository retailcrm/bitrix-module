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
 * Class OrdersCreateResponse
 *
 * @package Intaro\RetailCrm\Model\Api
 */
class OrdersCreateResponse extends AbstractApiResponseModel
{
    /**
     * @var bool
     *
     * @Mapping\Type("bool")
     * @Mapping\SerializedName("success")
     */
    public $success;
    
    /**
     * @var int
     *
     * @Mapping\Type("int")
     * @Mapping\SerializedName("id")
     */
    public $id;
    
    /**
     * @var \Intaro\RetailCrm\Model\Api\Order\CreateOrder
     *
     * @Mapping\Type("\Intaro\RetailCrm\Model\Api\Order\CreateOrder")
     * @Mapping\SerializedName("order")
     */
    public $order;
}
