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

/**
 * Class PriceType
 *
 * @package Intaro\RetailCrm\Model\Api
 */
class PriceType
{
    /**
     * Код типа цены
     *
     * @var string $code
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("code")
     */
    public $code;
}
