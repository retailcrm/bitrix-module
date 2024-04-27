<?php

/**
 * @category Integration
 * @package  Intaro\RetailCrm\Component\Json\Strategy\Serialize
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

namespace Intaro\RetailCrm\Component\Json\Strategy\Serialize;

/**
 * Trait InnerTypeTrait
 *
 * @package Intaro\RetailCrm\Component\Json\Strategy\Serialize
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
     * @return \Intaro\RetailCrm\Component\Json\Strategy\Serialize\SerializeStrategyInterface
     */
    public function setInnerType(string $type): SerializeStrategyInterface
    {
        $this->innerType = $type;
        return $this;
    }
}
