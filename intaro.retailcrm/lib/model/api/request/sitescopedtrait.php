<?php
/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Api\Request
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Model\Api\Request;

use Intaro\RetailCrm\Component\Json\Mapping;

/**
 * Trait SiteScopedTrait
 *
 * @package Intaro\RetailCrm\Model\Api\Request
 */
trait SiteScopedTrait
{
    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("site")
     */
    public $site;
}
