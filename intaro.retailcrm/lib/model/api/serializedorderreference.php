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
 * Class LoyaltyCalculateRequest
 *
 * @package Intaro\RetailCrm\Model\Api\Response\SmsVerification
 */
class SerializedOrderReference
{
    /**
     * @var integer $id
     *
     * @Mapping\Type("integer")
     * @Mapping\SerializedName("id")
     */
    public $id;
    
    /**
     * @var float $bonuses
     *
     * @Mapping\Type("float")
     * @Mapping\SerializedName("bonuses")
     */
    public $bonuses;
}
