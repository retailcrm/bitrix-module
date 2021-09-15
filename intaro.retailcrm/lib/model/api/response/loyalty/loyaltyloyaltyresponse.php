<?php

namespace Intaro\RetailCrm\Model\Api\Response\Loyalty;

use Intaro\RetailCrm\Model\Api\Response\AbstractApiResponseModel;
use Intaro\RetailCrm\Component\Json\Mapping;

class LoyaltyLoyaltyResponse extends AbstractApiResponseModel
{
    /**
     * Результат запроса (успешный/неуспешный)
     *
     * @var boolean $success
     *
     * @Mapping\Type("boolean")
     * @Mapping\SerializedName("success")
     */
    public $success;

    /**
     * Программа лояльности
     *
     * @var \Intaro\RetailCrm\Model\Api\Loyalty
     *
     * @Mapping\Type("\Intaro\RetailCrm\Model\Api\Loyalty")
     * @Mapping\SerializedName("$loyalty")
     */
    public $loyalty;
}
