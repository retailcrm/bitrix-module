<?php

/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Api
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

namespace Intaro\RetailCrm\Model\Api;

use Intaro\RetailCrm\Component\Json\Mapping;

class RequiredFields
{
    /**
     * Название поля
     *
     * @var string $name
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("name")
     */
    public $name;

    /**
     * Код поля
     *
     * @var string $code
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("code")
     */
    public $code;

    /**
     * Связанная сущность
     *
     * @var string $entity
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("entity")
     */
    public $entity;

    /**
     * Тип поля
     *
     * @var string $type
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("type")
     */
    public $type;

    /**
     * Флаг: кастомное поле
     *
     * @var bool $custom
     *
     * @Mapping\Type("boolean")
     * @Mapping\SerializedName("custom")
     */
    public $custom;

    /**
     * Возможные значения для списочных полей
     *
     * @var \Intaro\RetailCrm\Model\Api\DictionaryElements[] $dictionaryElements
     *
     * @Mapping\Type("array<Intaro\RetailCrm\Model\Api\DictionaryElements>")
     * @Mapping\SerializedName("dictionaryElements")
     */
    public $dictionaryElements;
}