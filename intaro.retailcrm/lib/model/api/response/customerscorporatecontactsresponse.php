<?php
/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Api\Response
 * @author   retailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Model\Api\Response;

use Intaro\RetailCrm\Component\Json\Mapping;

/**
 * Class CustomersCorporateContactsResponse
 *
 * @package Intaro\RetailCrm\Model\Api
 */
class CustomersCorporateContactsResponse extends OperationResponse
{
    /**
     * @var \Intaro\RetailCrm\Model\Api\CustomerContact[]
     *
     * @Mapping\Type("Intaro\RetailCrm\Model\Api\CustomerContact[]")
     * @Mapping\SerializedName("contacts")
     */
    public $contacts;
}
