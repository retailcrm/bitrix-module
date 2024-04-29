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
 * Class CodeValueModel
 *
 * @package Intaro\RetailCrm\Model\Api
 */
class CodeValueModel extends AbstractApiModel
{
    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("code")
     */
    public $code;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("value")
     */
    public $value;
}
