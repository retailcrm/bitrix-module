<?php
/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Api\Response\Order\Loyalty
 * @author   retailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Model\Api\Response\Order\Loyalty;

use Intaro\RetailCrm\Component\Json\Mapping;
use Intaro\RetailCrm\Model\Api\Response\AbstractApiResponseModel;

/**
 * Class OrderLoyaltyApplyResponse
 *
 * @package Intaro\RetailCrm\Model\Api\Response\SmsVerification
 */
class OrderLoyaltyApplyResponse extends AbstractApiResponseModel
{
    /**
     * Результат запроса (успешный/неуспешный)
     *
     * @var boolean $success
     *
     * @Mapping\Type("boolean")
     * @Mapping\SerializedName("success")
     */
    public $success;
    
    /**
     * @var \Intaro\RetailCrm\Model\Api\SerializedLoyaltyOrder
     *
     * @Mapping\Type("\Intaro\RetailCrm\Model\Api\SerializedLoyaltyOrder")
     * @Mapping\SerializedName("order")
     */
    public $order;
    
    /**
     * @var \Intaro\RetailCrm\Model\Api\SmsVerification
     *
     * @Mapping\Type("\Intaro\RetailCrm\Model\Api\SerializedLoyaltyOrder")
     * @Mapping\SerializedName("verification")
     */
    public $verification;
}
