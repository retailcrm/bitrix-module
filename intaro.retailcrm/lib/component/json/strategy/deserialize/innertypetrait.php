<?php

/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Component\Json\Strategy\Deserialize
 * @author   retailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Component\Json\Strategy\Deserialize;

/**
 * Trait InnerTypeTrait
 *
 * @package Intaro\RetailCrm\Component\Json\Strategy\Deserialize
 */
trait InnerTypeTrait
{
    /** @var string $innerType */
    private $innerType;

    /**
     * Sets inner type for types like array<key, value> and \DateTime<format>
     *
     * @param string $type
     *
     * @return \Intaro\RetailCrm\Component\Json\Strategy\Serialize\DeserializeStrategyInterface
     */
    public function setInnerType(string $type): DeserializeStrategyInterface
    {
        $this->innerType = $type;
        return $this;
    }
}
