<?php

/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Component\Json\Strategy
 * @author   retailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Component\Json\Strategy;

use Intaro\RetailCrm\Component\Json\Strategy\Deserialize\DeserializeStrategyInterface;
use Intaro\RetailCrm\Component\Json\Strategy\Serialize;
use Intaro\RetailCrm\Component\Json\Strategy\Serialize\SerializeStrategyInterface;

/**
 * Class StrategyFactory
 *
 * @package Intaro\RetailCrm\Component\Json\Strategy
 */
class StrategyFactory
{
    /** @var string[] $simpleTypes */
    private static $simpleTypes = [
        'bool',
        'boolean',
        'int',
        'integer',
        'float',
        'double',
        'string',
        'array'
    ];

    /**
     * @param string $dataType
     *
     * @return \Intaro\RetailCrm\Component\Json\Strategy\Serialize\SerializeStrategyInterface
     */
    public static function serializeStrategyByType(string $dataType): SerializeStrategyInterface
    {
        if (in_array($dataType, static::$simpleTypes)) {
            return new Serialize\SimpleTypeStrategy();
        }

        // TODO: DateTime<format> strategy and array<valueType>, array<keyType, valueType> strategies

        return new Serialize\EntityStrategy();
    }

    /**
     * @param string $dataType
     *
     * @return \Intaro\RetailCrm\Component\Json\Strategy\Deserialize\DeserializeStrategyInterface
     */
    public static function deserializeStrategyByType(string $dataType): DeserializeStrategyInterface
    {
        print_r($dataType);

        if (in_array($dataType, static::$simpleTypes)) {
            return new Deserialize\SimpleTypeStrategy();
        }

        // TODO: DateTime<format> strategy and array<valueType>, array<keyType, valueType> strategies
        
        return new Deserialize\EntityStrategy();
    }
}
