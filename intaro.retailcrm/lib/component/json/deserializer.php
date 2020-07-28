<?php

/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Component\Json
 * @author   retailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Component\Json;

use Intaro\RetailCrm\Component\Json\Strategy\StrategyFactory;
use RetailCrm\Exception\InvalidJsonException;

/**
 * Class Deserializer
 *
 * @package Intaro\RetailCrm\Component\Json
 */
class Deserializer
{
    /**
     * @param string $type
     * @param string $json
     *
     * @return mixed
     */
    public static function deserialize(string $type, $json)
    {
        $result = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidJsonException(json_last_error_msg(), json_last_error());
        }

        return static::deserializeArray($type, $result);
    }

    /**
     * @param string $type
     * @param array  $value
     *
     * @return mixed
     */
    public static function deserializeArray(string $type, array $value)
    {
        return StrategyFactory::deserializeStrategyByType($type)->deserialize($type, $value);
    }
}
