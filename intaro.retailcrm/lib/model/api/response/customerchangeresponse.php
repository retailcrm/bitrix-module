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
 * Class CustomerChangeResponse
 *
 * @package Intaro\RetailCrm\Model\Api
 */
class CustomerChangeResponse extends CreateResponse
{
    /**
     * @var string
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("state")
     */
    public $state;
}
