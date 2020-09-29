<?php
/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Api\Request\Order\Loyalty
 * @author   retailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Model\Api\Request\Order\Loyalty;

use Intaro\RetailCrm\Component\Json\Mapping;
use Intaro\RetailCrm\Model\Api\AbstractApiModel;
use Intaro\RetailCrm\Model\Api\SerializedOrderReference;

/**
 * Class OrderLoyaltyApplyRequest
 *
 * @package Intaro\RetailCrm\Model\Api\Request\Order\Loyalty
 */
class OrderLoyaltyApplyRequest extends AbstractApiModel
{
    /**
     * @var \Intaro\RetailCrm\Model\Api\SerializedOrderReference
     *
     * @Mapping\Type("\Intaro\RetailCrm\Model\Api\SerializedOrderReference")
     * @Mapping\SerializedName("order")
     */
    private $order;
    
    /**
     * @return \Intaro\RetailCrm\Model\Api\SerializedOrderReference
     */
    public function getOrder(): SerializedOrderReference
    {
        return $this->order;
    }
    
    /**
     * @param \Intaro\RetailCrm\Model\Api\SerializedOrderReference $order
     */
    public function setOrder(SerializedOrderReference $order): void
    {
        $this->order = $order;
    }
}
