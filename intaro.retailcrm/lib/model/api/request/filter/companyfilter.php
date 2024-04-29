<?php

/**
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Api\Request\Filter
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

namespace Intaro\RetailCrm\Model\Api\Request\Filter;

use Intaro\RetailCrm\Component\Json\Mapping;
use Intaro\RetailCrm\Model\Api\AbstractApiModel;

/**
 * Class CompanyFilter
 *
 * @package Intaro\RetailCrm\Model\Api\Request\Filter
 */
class CompanyFilter extends AbstractApiModel
{
    /**
     * @var int[]
     *
     * @Mapping\Type("int[]")
     * @Mapping\SerializedName("ids")
     */
    public $ids;

    /**
     * @var string[]
     *
     * @Mapping\Type("string[]")
     * @Mapping\SerializedName("externalIds")
     */
    public $externalIds;
}
