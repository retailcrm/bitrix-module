<?php

namespace Intaro\RetailCrm\Model\Api\Response\SmsVerification;

use Intaro\RetailCrm\Model\Api\AbstractApiModel;

/**
 * Class SmsVerificationStatusRequest
 *
 * @package Intaro\RetailCrm\Model\Api\Response\SmsVerification
 */
class SmsVerificationStatusRequest extends AbstractApiModel
{
    /**
     * Номер телефона для отправки сообщения
     *
     * @var string $phone
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("checkId")
     */
    public $checkId;
}
