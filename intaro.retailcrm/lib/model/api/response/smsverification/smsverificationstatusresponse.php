<?php

namespace Intaro\RetailCrm\Model\Api\Response\SmsVerification;

use Intaro\RetailCrm\Model\Api\AbstractApiModel;

/**
 * Class SmsVerificationStatusResponse
 *
 * @package Intaro\RetailCrm\Model\Api\Response\SmsVerification
 */
class SmsVerificationStatusResponse extends AbstractApiModel
{
    /**
     * @var bool
     *
     * @Mapping\Type("bool")
     * @Mapping\SerializedName("success")
     */
    public $success;
    
    /**
     * @var \Intaro\RetailCrm\Model\Api\SmsVerification
     *
     * @Mapping\Type("\Intaro\RetailCrm\Model\Api\SmsVerification")
     * @Mapping\SerializedName("verification")
     */
    public $verification;
}
