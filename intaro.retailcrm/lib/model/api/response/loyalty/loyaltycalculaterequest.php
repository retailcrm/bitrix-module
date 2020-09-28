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

/**
 * Class LoyaltyCalculateRequest
 *
 * @package Intaro\RetailCrm\Model\Api\Response\SmsVerification
 */
class LoyaltyCalculateRequest extends AbstractApiModel
{
    /**
     * @var string $site
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("site")
     */
    public $site;
    
    /**
     * @var \Intaro\RetailCrm\Model\Api\SerializedOrder
     *
     * @Mapping\Type("\Intaro\RetailCrm\Model\Api\SerializedOrder")
     * @Mapping\SerializedName("order")
     */
    public $order;
}

