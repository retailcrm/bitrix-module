<?php

/**
 * PHP version 7.1
 *
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
 * Class IdentifiersPair
 *
 * @package Intaro\RetailCrm\Model\Api
 */
class IdentifiersPair extends AbstractApiModel
{
    /**
     * @var int
     *
     * @Mapping\Type("int")
     * @Mapping\SerializedName("id")
     */
    public $id;

    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("externalId")
     */
    public $externalId;
}
