<?php
/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Api\Request
 * @author   retailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Model\Api\Request;

use Intaro\RetailCrm\Component\Json\Mapping;

/**
 * Trait ByTrait
 *
 * @package Intaro\RetailCrm\Model\Api\Request
 */
trait ByTrait
{
    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("by")
     */
    public $by;
}
