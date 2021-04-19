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

use Intaro\RetailCrm\Component\Json\Mapping\BitrixBoolean;

/**
 * Class SimpleTypeStrategy
 *
 * @package Intaro\RetailCrm\Component\Json\Strategy\Serialize
 */
class SimpleTypeStrategy implements DeserializeStrategyInterface
{
    use InnerTypeTrait;

    /**
     * @inheritDoc
     */
    public function deserialize(string $type, $value, $annotations)
    {
        switch ($type) {
            case 'bool':
            case 'boolean':
                if ($annotations->bitrixBoolean instanceof BitrixBoolean) {
                    return 'Y' === $value;
                }

                return (bool) $value;
            case 'int':
            case 'integer':
                return (int) $value;
            case 'float':
                return (float) $value;
            case 'double':
                return (double) $value;
            case 'string':
                return (string) $value;
            default:
                if (is_iterable($value)) {
                    return (array) $value;
                }

                return null;
        }
    }
}
