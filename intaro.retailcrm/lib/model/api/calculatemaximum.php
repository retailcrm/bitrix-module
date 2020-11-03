<?php
/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Api\Response\Loyalty
 * @author   retailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Model\Api;

use Intaro\RetailCrm\Component\Json\Mapping;

/**
 * Class CalculateMaximum
 *
 * @package Intaro\RetailCrm\Model\Api
 */
class CalculateMaximum
{
    /**
     * Привилегия с максимальной выгодой
     *
     * @var string $privilegeType
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("privilegeType")
     */
    public $privilegeType;
    
    /**
     * @var \Intaro\RetailCrm\Model\Api\AbstractLoyaltyEvent
     *
     * @Mapping\Type("\Intaro\RetailCrm\Model\Api\AbstractLoyaltyEvent")
     * @Mapping\SerializedName("loyaltyEvent")
     */
    public $loyaltyEvent;
}
