<?php
/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Api\Request\Loyalty
 * @author   retailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Model\Api\Request\Loyalty;

use Intaro\RetailCrm\Component\Json\Mapping;
use Intaro\RetailCrm\Model\Api\AbstractApiModel;
use Intaro\RetailCrm\Model\Api\SerializedOrder;

/**
 * Class LoyaltyCalculateRequest
 *
 * @package Intaro\RetailCrm\Model\Api\Request\Loyalty
 */
class LoyaltyCalculateRequest extends AbstractApiModel
{
    /**
     * @var string $site
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("site")
     */
    private $site;
    
    /**
     * @var \Intaro\RetailCrm\Model\Api\SerializedOrder
     *
     * @Mapping\Type("\Intaro\RetailCrm\Model\Api\SerializedOrder")
     * @Mapping\SerializedName("order")
     */
    private $order;
    
    /**
     * @return string
     */
    public function getSite(): string
    {
        return $this->site;
    }
    
    /**
     * @param string $site
     */
    public function setSite(string $site): void
    {
        $this->site = $site;
    }
    
    /**
     * @return \Intaro\RetailCrm\Model\Api\SerializedOrder
     */
    public function getOrder(): SerializedOrder
    {
        return $this->order;
    }
    
    /**
     * @param \Intaro\RetailCrm\Model\Api\SerializedOrder $order
     */
    public function setOrder(SerializedOrder $order): void
    {
        $this->order = $order;
    }
}

