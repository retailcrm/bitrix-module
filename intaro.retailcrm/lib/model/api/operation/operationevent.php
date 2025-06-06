<?php

namespace Intaro\RetailCrm\Model\Api\Operation;

use Intaro\RetailCrm\Component\Json\Mapping;

/**
 * Class OperationEvent
 */
class OperationEvent
{
    /**
     * ID события
     *
     * @var int $id
     *
     * @Mapping\Type("integer")
     * @Mapping\SerializedName("id")
     */
    public $id;

    /**
     * Тип события. Возможные значения: birthday, welcome
     *
     * @var string $type
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("type")
     */
    public $type;
}
