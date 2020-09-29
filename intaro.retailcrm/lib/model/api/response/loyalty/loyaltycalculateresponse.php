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
use Intaro\RetailCrm\Model\Api\CalculateMaximum;
use Intaro\RetailCrm\Model\Api\SerializedLoyaltyOrder;

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
    private $success;
    
    /**
     * @var \Intaro\RetailCrm\Model\Api\SerializedLoyaltyOrder
     *
     * @Mapping\Type("\Intaro\RetailCrm\Model\Api\SerializedLoyaltyOrder")
     * @Mapping\SerializedName("order")
     */
    private $order;
    
    /**
     * @var \Intaro\RetailCrm\Model\Api\CalculateMaximum
     *
     * @Mapping\Type("\Intaro\RetailCrm\Model\Api\CalculateMaximum")
     * @Mapping\SerializedName("maximum")
     */
    private $maximum;
    
    /**
     * Позиция в заказе
     *
     * @var array $calculations
     *
     * @Mapping\Type("array<Intaro\RetailCrm\Model\Api\LoyaltyCalculation>")
     * @Mapping\SerializedName("calculations")
     */
    private $calculations;
    
    /**
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }
    
    /**
     * @param bool $success
     */
    public function setSuccess(bool $success): void
    {
        $this->success = $success;
    }
    
    /**
     * @return \Intaro\RetailCrm\Model\Api\SerializedLoyaltyOrder
     */
    public function getOrder(): SerializedLoyaltyOrder
    {
        return $this->order;
    }
    
    /**
     * @param \Intaro\RetailCrm\Model\Api\SerializedLoyaltyOrder $order
     */
    public function setOrder(SerializedLoyaltyOrder $order): void
    {
        $this->order = $order;
    }
    
    /**
     * @return \Intaro\RetailCrm\Model\Api\CalculateMaximum
     */
    public function getMaximum(): CalculateMaximum
    {
        return $this->maximum;
    }
    
    /**
     * @param \Intaro\RetailCrm\Model\Api\CalculateMaximum $maximum
     */
    public function setMaximum(CalculateMaximum $maximum): void
    {
        $this->maximum = $maximum;
    }
    
    /**
     * @return array
     */
    public function getCalculations(): array
    {
        return $this->calculations;
    }
    
    /**
     * @param array $calculations
     */
    public function setCalculations(array $calculations): void
    {
        $this->calculations = $calculations;
    }
}
