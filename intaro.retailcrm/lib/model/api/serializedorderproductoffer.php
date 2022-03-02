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
 * Class SerializedOrderProductOffer
 *
 * @package Intaro\RetailCrm\Model\Api
 */
class SerializedOrderProductOffer
{
    /**
     * ID торгового предложения
     *
     * @var integer $initialPrice
     *
     * @Mapping\Type("integer")
     * @Mapping\SerializedName("id")
     */
    public $id;

    /**
     * Внешний ID торгового предложения
     *
     * @var string $externalId
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("externalId")
     */
    public $externalId;

    /**
     * ID торгового предложения в складской системе
     *
     * @var string $xmlId
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("xmlId")
     */
    public $xmlId;
}
