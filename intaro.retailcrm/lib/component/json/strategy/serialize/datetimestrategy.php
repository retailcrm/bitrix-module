<?php

/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Component\Json\Strategy\Serialize
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

namespace Intaro\RetailCrm\Component\Json\Strategy\Serialize;

use Intaro\RetailCrm\Component\Json\PropertyAnnotations;

/**
 * Class DateTimeStrategy
 *
 * @package Intaro\RetailCrm\Component\Json\Strategy\Serialize
 */
class DateTimeStrategy implements SerializeStrategyInterface
{
    use InnerTypeTrait;

    /**
     * @inheritDoc
     */
    public function serialize($value, $annotations)
    {
        if ($value instanceof \DateTime) {
            return $value->format($this->innerType);
        }

        return null;
    }
}
