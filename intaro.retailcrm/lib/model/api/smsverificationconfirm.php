<?php
/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Api
 * @author   retailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Model\Api;

use Intaro\RetailCrm\Component\Json\Mapping;

/**
 * Class SmsVerificationConfirm
 * @package Intaro\RetailCrm\Model\Api
 */
class SmsVerificationConfirm extends AbstractApiModel
{
    /**
     * Проверочный код
     *
     * @var string $code
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("code")
     */
    public $code;
    
    /**
     * Идентификатор проверки кода
     *
     * @var string $checkId
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("checkId")
     */
    public $checkId;
    
    
}
