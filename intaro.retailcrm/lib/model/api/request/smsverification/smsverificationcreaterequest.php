<?php
/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Api\Request\SmsVerification
 * @author   retailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Model\Api\Request\SmsVerification;

use Intaro\RetailCrm\Model\Api\AbstractApiModel;
use Intaro\RetailCrm\Component\Json\Mapping;

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
