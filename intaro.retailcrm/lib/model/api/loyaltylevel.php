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
 * Class LoyaltyLevel
 *
 * @package Intaro\RetailCrm\Model\Api
 */
class LoyaltyLevel
{
    /**
     * ID уровня
     *
     * @var integer $id
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("id")
     */
    public $id;
    
    /**
     * Название уровня
     *
     * @var string $name
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("name")
     */
    public $name;
}
