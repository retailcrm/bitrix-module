<?php
/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Component\Json\Strategy\Serialize
 * @author   retailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Component\Json\Strategy\Serialize;

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
    public function serialize($value)
    {
        if ($value instanceof \DateTime) {
            return $value->format($this->innerType);
        }

        return null;
    }
}
