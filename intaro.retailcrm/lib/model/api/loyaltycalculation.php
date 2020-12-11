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

use Intaro\RetailCrm\Component\Json\Mapping;

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
     * Денежная скидка на заказ с учетом списанных бонусов по курсу, заданному в настройках
     *
     * @var float $discount
     *
     * @Mapping\Type("float")
     * @Mapping\SerializedName("discount")
     */
    public $discount;
   
    /**
     * Бонусы к начислению
     *
     * @var float $creditBonuses
     *
     * @Mapping\Type("float")
     * @Mapping\SerializedName("creditBonuses")
     */
    public $creditBonuses;
    
    /**
     * Бонусы, доступные для списания
     *
     * @var float $maxChargeBonuses
     *
     * @Mapping\Type("float")
     * @Mapping\SerializedName("maxChargeBonuses")
     */
    public $maxChargeBonuses;
    
    /**
     * Привилегия с максимальной выгодой
     *
     * @var boolean $maximum
     *
     * @Mapping\Type("boolean")
     * @Mapping\SerializedName("maximum")
     */
    public $maximum;
}
