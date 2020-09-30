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
 * Class AbstractLoyaltyEvent
 *
 * @package Intaro\RetailCrm\Model\Api
 */
class AbstractLoyaltyEvent
{
    /**
     * ID события
     *
     * @var integer $id
     *
     * @Mapping\Type("integer")
     * @Mapping\SerializedName("id")
     */
    public $id;
}
