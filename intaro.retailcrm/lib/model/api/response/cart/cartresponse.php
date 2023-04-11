<?php

/**
 * PHP version 8.0
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Api\Response
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

namespace Intaro\RetailCrm\Model\Api\Response\Cart;

use Intaro\RetailCrm\Component\Json\Mapping;
use Intaro\RetailCrm\Model\Api\Response\AbstractApiResponseModel;

/**
 * Class CartResponse
 * @package Intaro\RetailCrm\Model\Api\Response
 */
class CartResponse extends AbstractApiResponseModel
{
    /**
     * @var bool
     *
     * @Mapping\Type("bool")
     * @Mapping\SerializedName("success")
     */
    public $success;
}
