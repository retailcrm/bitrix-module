<?php
/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Api\Response\Loyalty
 * @author   retailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Model\Api;

/**
 * Class LoyaltyCalculation
 *
 * @package Intaro\RetailCrm\Model\Api
 */
class LoyaltyCalculation
{
    /**
     * Тип привилегии
     *
     * @var string $privilegeType
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("privilegeType")
     */
    public $privilegeType;
    
    /**
     * Суммарная скидка на заказ
     *
     * @var float $amount
     *
     * @Mapping\Type("float")
     * @Mapping\SerializedName("amount")
     */
    public $amount;
   
    /**
     * Будет начислено бонусов
     *
     * @var float $bonuses
     *
     * @Mapping\Type("float")
     * @Mapping\SerializedName("bonuses")
     */
    public $bonuses;
    
    /**
     * Итоговая сумма выгоды
     *
     * @var float $total
     *
     * @Mapping\Type("float")
     * @Mapping\SerializedName("total")
     */
    public $total;
}
