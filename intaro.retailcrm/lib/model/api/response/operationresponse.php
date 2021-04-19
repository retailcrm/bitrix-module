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
 * Class OperationResponse
 *
 * @package Intaro\RetailCrm\Model\Api
 */
class OperationResponse extends AbstractApiResponseModel
{
    /**
     * @var bool
     *
     * @Mapping\Type("bool")
     * @Mapping\SerializedName("success")
     */
    public $success;

    /**
     * @var array
     *
     * @Mapping\Type("array")
     * @Mapping\SerializedName("errors")
     */
    public $errors;

    /**
     * @var \Intaro\RetailCrm\Model\Api\Response\PaginationResponse
     *
     * @Mapping\Type("Intaro\RetailCrm\Model\Api\Response\PaginationResponse")
     * @Mapping\SerializedName("pagination")
     */
    public $pagination;
}
