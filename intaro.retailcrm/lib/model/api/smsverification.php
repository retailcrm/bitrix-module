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

use DateTime;

/**
 * Class SmsVerification
 *
 * @package Intaro\RetailCrm\Model\Api
 */
class SmsVerification extends AbstractApiModel
{
    /**
     * Дата создания. (Y-m-d H:i:s)
     *
     * @var \DateTime
     *
     * @Mapping\Type("DateTime<'Y-m-d H:i:s'>")
     * @Mapping\SerializedName("createdAt")
     */
    private $createdAt;
    
    /**
     * Дата окончания срока жизни. (Y-m-d H:i:s)
     *
     * @var \DateTime
     *
     * @Mapping\Type("DateTime<'Y-m-d H:i:s'>")
     * @Mapping\SerializedName("expiredAt")
     */
    private $expiredAt;
    
    /**
     * Дата успешной верификации. (Y-m-d H:i:s)
     *
     * @var \DateTime
     *
     * @Mapping\Type("DateTime<'Y-m-d H:i:s'>")
     * @Mapping\SerializedName("verifiedAt")
     */
    private $verifiedAt;
    
    /**
     * Идентификатор для проверки кода
     *
     * @var string $checkId
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("checkId")
     */
    private $checkId;
    
    /**
     * Тип действия
     *
     * @var string $actionType
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("actionType")
     */
    private $actionType;
    
    /**
     * @param \DateTime $createdAt
     */
    public function setCreatedAt(DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }
    
    /**
     * @param string $actionType
     */
    public function setActionType(string $actionType): void
    {
        $this->actionType = $actionType;
    }
    
    /**
     * @param string $checkId
     */
    public function setCheckId(string $checkId): void
    {
        $this->checkId = $checkId;
    }
    
    /**
     * @param \DateTime $verifiedAt
     */
    public function setVerifiedAt(DateTime $verifiedAt): void
    {
        $this->verifiedAt = $verifiedAt;
    }
    
    /**
     * @param \DateTime $expiredAt
     */
    public function setExpiredAt(DateTime $expiredAt): void
    {
        $this->expiredAt = $expiredAt;
    }
}
