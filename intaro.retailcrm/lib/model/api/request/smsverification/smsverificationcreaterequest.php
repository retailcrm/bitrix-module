<?php

namespace Intaro\RetailCrm\Model\Api\Request\SmsVerification;

use Intaro\RetailCrm\Model\Api\AbstractApiModel;

/**
 * Class SmsVerificationCreateRequest
 *
 * @package Intaro\RetailCrm\Model\Api\Response\SmsVerification
 */
class SmsVerificationCreateRequest extends AbstractApiModel
{
    /**
     * @var \Intaro\RetailCrm\Model\Api\SmsVerificationCreate
     *
     * @Mapping\Type("\Intaro\RetailCrm\Model\Api\SmsVerificationCreate")
     * @Mapping\SerializedName("verification")
     */
    public $verification;
}
