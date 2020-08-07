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
 * Interface SerializeStrategyInterface
 *
 * @package Intaro\RetailCrm\Component\Json\Strategy\Serialize
 */
interface SerializeStrategyInterface
{
    /**
     * Serialize value
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public function serialize($value);

    /**
     * Sets inner type for types like array<key, value> and \DateTime<format>
     *
     * @param string $type
     *
     * @return \Intaro\RetailCrm\Component\Json\Strategy\Serialize\SerializeStrategyInterface
     */
    public function setInnerType(string $type): SerializeStrategyInterface;
}
