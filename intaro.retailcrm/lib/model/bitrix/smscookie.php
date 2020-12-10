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
     * @var \Bitrix\Main\Type\DateTime
     */
    public $createdAt;
    
    /**
     * Дата устаревания. (Y-m-d H:i:s)
     *
     * @var \Bitrix\Main\Type\DateTime
     */
    public $expiredAt;
    
    /**
     * Проверочный код.
     *
     * @var string $checkId
     */
    public $checkId;
    
    /**
     * Проверочный код.
     *
     * @var boolean $isVerified
     */
    public $isVerified;
}
