<?php

/**
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Api\Response\Loyalty\Account
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

namespace Intaro\RetailCrm\Model\Api\Response\Loyalty;

use Intaro\RetailCrm\Model\Api\Response\AbstractApiResponseModel;
use Intaro\RetailCrm\Component\Json\Mapping;

/**
 * Class LoyaltyLoyaltyResponse
 *
 * @package Intaro\RetailCrm\Model\Api\Response\Loyalty
 */
class LoyaltyLoyaltyResponse extends AbstractApiResponseModel
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
     * Программа лояльности
     *
     * @var \Intaro\RetailCrm\Model\Api\Loyalty $loyalty
     *
     * @Mapping\Type("\Intaro\RetailCrm\Model\Api\Loyalty")
     * @Mapping\SerializedName("loyalty")
     */
    public $loyalty;

    /**
     * Требуемые при регистрации поля
     *
     * @var \Intaro\RetailCrm\Model\Api\RequiredFields[] $requiredFields
     *
     * @Mapping\Type("array<Intaro\RetailCrm\Model\Api\RequiredFields>")
     * @Mapping\SerializedName("requiredFields")
     */
    public $requiredFields;
}
