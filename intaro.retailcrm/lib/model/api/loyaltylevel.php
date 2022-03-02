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

/**
 * Class LoyaltyLevel
 *
 * @package Intaro\RetailCrm\Model\Api
 */
class LoyaltyLevel
{
    /**
     * ID участия
     *
     * @var int $id
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("id")
     */
    public $id;
    
    /**
     * Название уровня
     *
     * @var string $name
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("name")
     */
    public $name;
    
    /**
     * Тип уровня.
     *
     * Возможные значения:
     * bonus_percent - кешбек от стоимости
     * bonus_converting - Пример:  начисление 1 бонус за каждые 10 рублей покупки
     * discount - скидочный уровень
     *
     * @var string $type
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("type")
     */
    public $type;
    
    /**
     * Размер скидки, процент или курс начисления бонусов для товаров по обычной цене
     *
     * @var float $privilegeSize
     *
     * @Mapping\Type("float")
     * @Mapping\SerializedName("privilegeSize")
     */
    public $privilegeSize;
    
    /**
     * Размер скидки, процент или курс начисления бонусов для акционных товаров
     *
     * @var float $privilegeSizePromo
     *
     * @Mapping\Type("float")
     * @Mapping\SerializedName("privilegeSizePromo")
     */
    public $privilegeSizePromo;
}
