<?php
/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Bitrix
 * @author   retailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Model\Bitrix;

use Intaro\RetailCrm\Component\Json\Mapping;

/**
 * Class SmsCookie
 * @package Intaro\RetailCrm\Model\Bitrix
 */
class SmsCookie
{
    /**
     * Дата создания кода верификации. (Y-m-d H:i:s)
     *
     * @var \DateTime $createdAt
     *
     * @Mapping\Type("DateTime<'Y-m-d H:i:s'>")
     * @Mapping\SerializedName("createdAt")
     */
    public $createdAt;
    
    /**
     * Дата устаревания.
     *
     * @var \DateTime $expiredAt
     *
     * @Mapping\Type("DateTime<'Y-m-d H:i:s'>")
     * @Mapping\SerializedName("expiredAt")
     */
    public $expiredAt;
    
    /**
     * Проверочный код.
     *
     * @var string $checkId
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("checkId")
     */
    public $checkId;
    
    /**
     * Код подтвержден
     *
     * @var boolean $isVerified
     *
     * @Mapping\Type("boolean")
     * @Mapping\SerializedName("isVerified")
     */
    public $isVerified;
    
    /**
     * Повторная отправка доступна (Y-m-d H:i:s)
     *
     * @var \DateTime $resendAvailabl
     *
     * @Mapping\Type("DateTime<'Y-m-d H:i:s'>")
     * @Mapping\SerializedName("resendAvailable")
     */
    public $resendAvailable;
}
