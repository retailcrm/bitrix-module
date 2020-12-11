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
     */
    public $createdAt;
    
    /**
     * Дата устаревания.
     *
     * @var \DateTime $expiredAt
     */
    public $expiredAt;
    
    /**
     * Проверочный код.
     *
     * @var string $checkId
     */
    public $checkId;
    
    /**
     * Код подтвержден
     *
     * @var boolean $isVerified
     */
    public $isVerified;
    
    /**
     * Повторная отправка доступна (Y-m-d H:i:s)
     *
     * @var \DateTime $resendAvailabl
     */
    public $resendAvailable;
}
