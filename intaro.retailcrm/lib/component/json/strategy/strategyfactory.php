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
    /** @var string */
    private const TYPED_MATCHER = '/^\\\\?([a-zA-Z0-9_]+)\s*\<(.+)\>$/m';

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

        $arrSubType = static::getArrayInnerTypes($dataType);

        if (!empty($arrSubType)) {
            return (new Serialize\TypedArrayStrategy())->setInnerType($arrSubType);
        }

        $dateTimeFormat = static::getDateTimeFormat($dataType);

        if (!empty($dateTimeFormat)) {
            return (new Serialize\DateTimeStrategy())->setInnerType($dateTimeFormat);
        }

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

        $arrSubType = static::getArrayInnerTypes($dataType);

        if (!empty($arrSubType)) {
            return (new Deserialize\TypedArrayStrategy())->setInnerType($arrSubType);
        }

        $dateTimeFormat = static::getDateTimeFormat($dataType);

        if (!empty($dateTimeFormat)) {
            return (new Deserialize\DateTimeStrategy())->setInnerType($dateTimeFormat);
        }

        return new Deserialize\EntityStrategy();
    }

    /**
     * Returns array inner type for arrays like array<int, \DateTime<Y-m-d\TH:i:sP>>
     * For this example, "int, \DateTime<Y-m-d\TH:i:sP>" will be returned.
     *
     * Also works for arrays like int[].
     *
     * @param string $dataType
     *
     * @return string
     */
    private static function getArrayInnerTypes(string $dataType): string
    {
        $matches = [];

        preg_match_all(static::TYPED_MATCHER, $dataType, $matches, PREG_SET_ORDER, 0);

        if (empty($matches)) {
            if (strlen($dataType) > 2 && substr($dataType, -2) === '[]') {
                return substr($dataType, 0, -2);
            }

            return '';
        }

        if ($matches[0][1] === 'array') {
            return $matches[0][2];
        }

        return '';
    }

    /**
     * Returns DateTime format. Example: \DateTime<Y-m-d\TH:i:sP>>
     *
     * @param string $dataType
     *
     * @return string
     */
    private static function getDateTimeFormat(string $dataType): string
    {
        $matches = [];

        preg_match_all(static::TYPED_MATCHER, $dataType, $matches, PREG_SET_ORDER, 0);

        if (empty($matches)) {
            return '';
        }

        if ($matches[0][1] === 'DateTime') {
            $format = $matches[0][2];

            if (strlen($format) > 2 && $format[0] === "'" && substr($format, -1) === "'") {
                return substr($format, 1, -1);
            }

            return $format;
        }

        return '';
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
