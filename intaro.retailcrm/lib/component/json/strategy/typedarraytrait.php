<?php

/**
 * @category Integration
 * @package  Intaro\RetailCrm\Component\Json\Strategy
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

namespace Intaro\RetailCrm\Component\Json\Strategy;

/**
 * Class TypedArrayTrait
 *
 * @package Intaro\RetailCrm\Component\Json\Strategy
 */
trait TypedArrayTrait
{
    private static $innerTypesMatcher = '/^([a-z]+)\s*\,?\s*(.+?\>)/m';

    /**
     * Returns inner types for array with typed key (example: array<string, DateTime<Y m d H i s>>).
     *
     * @param string $innerType
     *
     * @return string[]
     */
    private static function getInnerTypes(string $innerType)
    {
        $matches = [];

        preg_match_all(static::$innerTypesMatcher, $innerType, $matches, PREG_SET_ORDER, 0);

        if (empty($matches)) {
            return ['', ''];
        }

        $matches = $matches[0];

        return [trim($matches[1]), trim($matches[2])];
    }
}
