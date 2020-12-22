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
 * Trait PaginatedTrait
 *
 * @package Intaro\RetailCrm\Model\Api\Request
 */
trait PaginatedTrait
{
    /**
     * @var int
     *
     * @Mapping\Type("int")
     * @Mapping\SerializedName("page")
     */
    public $page;

    /**
     * @var int
     *
     * @Mapping\Type("int")
     * @Mapping\SerializedName("limit")
     */
    public $limit;
}
