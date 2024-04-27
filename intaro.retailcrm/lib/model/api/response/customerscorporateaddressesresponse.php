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
 * Class CustomersNotesResponse
 *
 * @package Intaro\RetailCrm\Model\Api
 */
class CustomersCorporateAddressesResponse extends OperationResponse
{
    /**
     * @var \Intaro\RetailCrm\Model\Api\Address[]
     *
     * @Mapping\Type("Intaro\RetailCrm\Model\Api\Address[]")
     * @Mapping\SerializedName("addresses")
     */
    public $addresses;
}
