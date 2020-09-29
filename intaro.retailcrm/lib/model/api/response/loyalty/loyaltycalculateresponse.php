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
namespace Intaro\RetailCrm\Model\Api\Response\Loyalty;

use Intaro\RetailCrm\Model\Api\AbstractApiModel;

/**
 * Class LoyaltyCalculateResponse
 *
 * @package Intaro\RetailCrm\Model\Api\Response\Loyalty
 */
class LoyaltyCalculateResponse extends AbstractApiModel
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
     * @var \Intaro\RetailCrm\Model\Api\SerializedLoyaltyOrder
     *
     * @Mapping\Type("\Intaro\RetailCrm\Model\Api\SerializedLoyaltyOrder")
     * @Mapping\SerializedName("order")
     */
    public $order;
    
    /**
     * @var \Intaro\RetailCrm\Model\Api\CalculateMaximum
     *
     * @Mapping\Type("\Intaro\RetailCrm\Model\Api\CalculateMaximum")
     * @Mapping\SerializedName("maximum")
     */
    public $maximum;
    
    /**
     * Позиция в заказе
     *
     * @var array $calculations
     *
     * @Mapping\Type("array<Intaro\RetailCrm\Model\Api\LoyaltyCalculation>")
     * @Mapping\SerializedName("calculations")
     */
    public $calculations;
}
