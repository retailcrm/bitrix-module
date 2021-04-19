<?php

/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Component\Json\Strategy\Deserialize
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

namespace Intaro\RetailCrm\Component\Json\Strategy\Deserialize;

/**
 * Class DateTimeStrategy
 *
 * @package Intaro\RetailCrm\Component\Json\Strategy\Deserialize
 */
class DateTimeStrategy implements DeserializeStrategyInterface
{
    use InnerTypeTrait;

    /**
     * @inheritDoc
     */
    public function deserialize(string $type, $value, $annotations = null)
    {
        if (!empty($value)) {
            $result = \DateTime::createFromFormat($this->innerType, $value);

            if (!$result) {
                return null;
            }

            return $result;
        }

        return null;
    }
}
