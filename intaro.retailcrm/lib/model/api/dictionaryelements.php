<?php

namespace Intaro\RetailCrm\Model\Api;

use Intaro\RetailCrm\Component\Json\Mapping;

class DictionaryElements
{
    /**
     * Название элемента
     *
     * @var string $name
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("name")
     */
    public $name;

    /**
     * Код элемента
     *
     * @var string $code
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("code")
     */
    public $code;

    /**
     * Код элемента
     *
     * @var string $ordering
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("ordering")
     */
    public $ordering;
}