<?php
/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Api
 * @author   retailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Model\Api;

/**
 * Class LoyaltyCalculateRequest
 *
 * @package Intaro\RetailCrm\Model\Api\Response\SmsVerification
 */
class SerializedOrderReference
{
    /**
     * @var integer $id
     *
     * @Mapping\Type("integer")
     * @Mapping\SerializedName("id")
     */
    private $id;
    
    /**
     * @var float $bonuses
     *
     * @Mapping\Type("float")
     * @Mapping\SerializedName("bonuses")
     */
    private $bonuses;
    
    /**
     * @return float
     */
    public function getBonuses(): float
    {
        return $this->bonuses;
    }
    
    /**
     * @param float $bonuses
     */
    public function setBonuses(float $bonuses): void
    {
        $this->bonuses = $bonuses;
    }
    
    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }
    
    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }
}