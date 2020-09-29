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
 * Class LoyaltyLevel
 *
 * @package Intaro\RetailCrm\Model\Api
 */
class LoyaltyLevel
{
    /**
     * ID уровня
     *
     * @var integer $id
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("id")
     */
    private $id;
    
    /**
     * Название уровня
     *
     * @var string $name
     *
     * @Mapping\Type("string")
     * @Mapping\SerializedName("name")
     */
    private $name;
    
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
    
    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
    
    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
