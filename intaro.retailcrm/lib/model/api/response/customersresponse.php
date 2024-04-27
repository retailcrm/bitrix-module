<?php

/**
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Api\Response
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

namespace Intaro\RetailCrm\Model\Api\Response;

use Intaro\RetailCrm\Component\Json\Mapping;

/**
 * Class CustomersResponse
 *
 * @package Intaro\RetailCrm\Model\Api
 */
class CustomersResponse extends OperationResponse
{
    /**
     * @var \Intaro\RetailCrm\Model\Api\Customer[]
     *
     * @Mapping\Type("Intaro\RetailCrm\Model\Api\Customer[]")
     * @Mapping\SerializedName("customers")
     */
    public $customers;
}
