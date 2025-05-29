<?php

namespace Intaro\RetailCrm\Model\Api\Loyalty;

use Intaro\RetailCrm\Component\Json\Mapping;

/**
 * Class OperationOrder
 */
class OperationOrder
{
    /**
     * ID заказа
     *
     * @var int $id
     *
     * @Mapping\Type("integer")
     * @Mapping\SerializedName("id")
     */
    public $id;

    /**
     * Внешний ID заказа
     *
     * @var string $externalId
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("externalId")
     */
    public $externalId;
}
