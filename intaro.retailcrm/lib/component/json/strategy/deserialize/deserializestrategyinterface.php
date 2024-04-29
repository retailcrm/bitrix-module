<?php

/**
 * @category Integration
 * @package  Intaro\RetailCrm\Component\Json\Strategy\Deserialize
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

namespace Intaro\RetailCrm\Component\Json\Strategy\Deserialize;

/**
 * Interface DeserializeStrategyInterface
 *
 * @package Intaro\RetailCrm\Component\Json\Strategy\Serialize
 */
interface DeserializeStrategyInterface
{
    /**
     * Deserialize value
     *
     * @param string                                               $type
     * @param mixed                                                $value
     * @param \Intaro\RetailCrm\Component\Json\PropertyAnnotations $annotations
     *
     * @return mixed
     */
    public function deserialize(string $type, $value, $annotations);

    /**
     * Sets inner type for types like array<key, value> and \DateTime<format>
     *
     * @param string $type
     *
     * @return \Intaro\RetailCrm\Component\Json\Strategy\Deserialize\DeserializeStrategyInterface
     */
    public function setInnerType(string $type): DeserializeStrategyInterface;
}
