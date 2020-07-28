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
 * Class Serializer
 *
 * @package Intaro\RetailCrm\Component\Json
 */
class Serializer
{
    /**
     * @param object $object
     *
     * @return string
     */
    public static function serialize($object): string
    {
        $result = json_encode(static::serializeArray($object));

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidJsonException(json_last_error_msg(), json_last_error());
        }

        return (string) $result;
    }

    /**
     * @param object $object
     *
     * @return array
     */
    public static function serializeArray($object): array
    {
        return (array) StrategyFactory::serializeStrategyByType(gettype($object))->serialize($object);
    }
}
