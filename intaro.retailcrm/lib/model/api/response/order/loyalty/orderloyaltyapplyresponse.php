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
use Intaro\RetailCrm\Model\Api\AbstractApiModel;
use Intaro\RetailCrm\Model\Api\SerializedLoyaltyOrder;
use Intaro\RetailCrm\Model\Api\SmsVerification;

/**
 * Class OrderLoyaltyApplyResponse
 *
 * @package Intaro\RetailCrm\Model\Api\Response\SmsVerification
 */
class OrderLoyaltyApplyResponse extends AbstractApiModel
{
    /**
     * Результат запроса (успешный/неуспешный)
     *
     * @var boolean $success
     *
     * @Mapping\Type("boolean")
     * @Mapping\SerializedName("success")
     */
    private $success;
    
    /**
     * @var \Intaro\RetailCrm\Model\Api\SerializedLoyaltyOrder
     *
     * @Mapping\Type("\Intaro\RetailCrm\Model\Api\SerializedLoyaltyOrder")
     * @Mapping\SerializedName("order")
     */
    private $order;
    
    /**
     * @var \Intaro\RetailCrm\Model\Api\SmsVerification
     *
     * @Mapping\Type("\Intaro\RetailCrm\Model\Api\SerializedLoyaltyOrder")
     * @Mapping\SerializedName("verification")
     */
    private $verification;
    
    /**
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }
    
    /**
     * @param bool $success
     */
    public function setSuccess(bool $success): void
    {
        $this->success = $success;
    }
    
    /**
     * @return \Intaro\RetailCrm\Model\Api\SerializedLoyaltyOrder
     */
    public function getOrder(): SerializedLoyaltyOrder
    {
        return $this->order;
    }
    
    /**
     * @param \Intaro\RetailCrm\Model\Api\SerializedLoyaltyOrder $order
     */
    public function setOrder(SerializedLoyaltyOrder $order): void
    {
        $this->order = $order;
    }
    
    /**
     * @return \Intaro\RetailCrm\Model\Api\SmsVerification
     */
    public function getVerification(): SmsVerification
    {
        return $this->verification;
    }
    
    /**
     * @param \Intaro\RetailCrm\Model\Api\SmsVerification $verification
     */
    public function setVerification(SmsVerification $verification): void
    {
        $this->verification = $verification;
    }
}
