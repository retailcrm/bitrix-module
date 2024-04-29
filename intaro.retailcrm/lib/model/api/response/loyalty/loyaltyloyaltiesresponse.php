<?php

/**
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Api\Response\Loyalty
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

namespace Intaro\RetailCrm\Model\Api\Response\Loyalty;

use Intaro\RetailCrm\Model\Api\Response\AbstractApiResponseModel;
use Intaro\RetailCrm\Component\Json\Mapping;

/**
 * Class LoyaltyLoyaltiesResponse
 *
 * @package Intaro\RetailCrm\Model\Api\Response\Loyalty
 */
class LoyaltyLoyaltiesResponse extends AbstractApiResponseModel
{
    /**
     * Результат запроса (успешный/неуспешный)
     *
     * @var bool $success
     *
     * @Mapping\Type("boolean")
     * @Mapping\SerializedName("success")
     */
    public $success;

    /**
     * Постраничная разбивка
     *
     * @var \Intaro\RetailCrm\Model\Api\Response\PaginationResponse $pagination
     *
     * @Mapping\Type("\Intaro\RetailCrm\Model\Api\Response\PaginationResponse")
     * @Mapping\SerializedName("pagination")
     */
    public $pagination;

    /**
     * Программы лояльности
     *
     * @var \Intaro\RetailCrm\Model\Api\Loyalty[] $loyalties
     *
     * @Mapping\Type("array<Intaro\RetailCrm\Model\Api\Loyalty>")
     * @Mapping\SerializedName("loyalties")
     */
    public $loyalties;
}
