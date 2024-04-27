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
 * Class CreateResponse
 *
 * @package Intaro\RetailCrm\Model\Api
 */
class CreateResponse extends OperationResponse
{
    /**
     * @var int
     *
     * @Mapping\Type("int")
     * @Mapping\SerializedName("id")
     */
    public $id;
}
