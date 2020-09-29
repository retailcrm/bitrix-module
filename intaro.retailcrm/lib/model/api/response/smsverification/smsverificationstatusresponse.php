<?php
/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Api\Response\SmsVerification
 * @author   retailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Model\Api\Response\SmsVerification;

use Intaro\RetailCrm\Model\Api\AbstractApiModel;
use Intaro\RetailCrm\Component\Json\Mapping;
use Intaro\RetailCrm\Model\Api\SmsVerification;

/**
 * Class SmsVerificationStatusResponse
 *
 * @package Intaro\RetailCrm\Model\Api\Response\SmsVerification
 */
class SmsVerificationStatusResponse extends SmsVerificationConfirmResponse
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
     * @var \Intaro\RetailCrm\Model\Api\SmsVerification
     *
     * @Mapping\Type("\Intaro\RetailCrm\Model\Api\SmsVerification")
     * @Mapping\SerializedName("verification")
     */
    private $verification;
    
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
}
