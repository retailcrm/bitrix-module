<?php

/**
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Api
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

namespace Intaro\RetailCrm\Model\Api;

use Intaro\RetailCrm\Component\Json\Mapping;


/**
 * Class TimeInterval
 *
 * @package Intaro\RetailCrm\Model\Api
 */
class TimeInterval extends AbstractApiModel
{
    /**
     * @var \DateTime
     *
     * @Mapping\Type("DateTime<'Y-m-d H:i:s'>")
     * @Mapping\SerializedName("from")
     */
    public $from;

    /**
     * @var \DateTime
     *
     * @Mapping\Type("DateTime<'Y-m-d H:i:s'>")
     * @Mapping\SerializedName("to")
     */
    public $to;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("to")
     */
    public $custom;
}
