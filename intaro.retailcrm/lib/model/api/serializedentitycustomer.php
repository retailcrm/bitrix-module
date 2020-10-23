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
 * Class SerializedEntityCustomer
 * @package Intaro\RetailCrm\Model\Api
 */
class SerializedEntityCustomer
{
    /**
     * Внутренний ID клиента
     *
     * @var integer $id
     *
     * @Mapping\Type("integer")
     * @Mapping\SerializedName("id")
     */
    public $id;
    
    /**
     * Внешний ID клиента
     *
     * @var string $externalId
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("id")
     */
    public $externalId;
}
