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

        if (static::isDateTime($dataType)) {
            return (new Serialize\DateTimeStrategy())->setInnerType(\DateTime::RFC3339);
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
        if (in_array($dataType, static::$simpleTypes)) {
            return new Deserialize\SimpleTypeStrategy();
        }

        if (static::isDateTime($dataType)) {
            return (new Deserialize\DateTimeStrategy())->setInnerType(\DateTime::RFC3339);
        }

        // TODO: DateTime<format> strategy and array<valueType>, array<keyType, valueType> strategies

        return new Deserialize\EntityStrategy();
    }

    /**
     * Returns true if provided type is DateTime
     *
     * @param string $dataType
     *
     * @return bool
     */
    private static function isDateTime(string $dataType): bool
    {
        return strlen($dataType) > 1
            && (\DateTime::class === $dataType
                || ('\\' === $dataType[0] && \DateTime::class === substr($dataType, 1)));
    }
}
