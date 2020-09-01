<?php

namespace Intaro\RetailCrm\Model\Api\Request\SmsVerification;

use Intaro\RetailCrm\Component\Json\Mapping;
use Intaro\RetailCrm\Model\Api\AbstractApiModel;

/**
 * Class SmsVerificationConfirmRequest
 *
 * @package Intaro\RetailCrm\Model\Api\Response\SmsVerification
 */
class SmsVerificationConfirmRequest extends AbstractApiModel
{
    /**
     * @var \Intaro\RetailCrm\Model\Api\SmsVerificationConfirm
     *
     * @Mapping\Type("\Intaro\RetailCrm\Model\Api\SmsVerificationConfirm")
     * @Mapping\SerializedName("verification")
     */
    public $verification;
}
