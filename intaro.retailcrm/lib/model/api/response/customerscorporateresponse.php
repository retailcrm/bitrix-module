<?php
/**
 * PHP version 7.1
 *
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
 * Class CustomersCorporateResponse
 *
 * @package Intaro\RetailCrm\Model\Api
 */
class CustomersCorporateResponse extends OperationResponse
{
    /**
     * @var \Intaro\RetailCrm\Model\Api\Customer[]
     *
     * @Mapping\Type("Intaro\RetailCrm\Model\Api\Customer[]")
     * @Mapping\SerializedName("customersCorporate")
     */
    public $customersCorporate;
}
