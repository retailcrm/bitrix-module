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

use Intaro\RetailCrm\Component\Json\Strategy\StrategyFactory;

/**
 * Class SimpleTypeStrategy
 *
 * @package Intaro\RetailCrm\Component\Json\Strategy\Serialize
 */
class SimpleTypeStrategy implements SerializeStrategyInterface
{
    use InnerTypeTrait;

    /**
     * @inheritDoc
     */
    public function serialize($value)
    {
        switch (gettype($value)) {
            case 'bool':
            case 'boolean':
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
                    $result = [];

                    foreach ($value as $key => $item) {
                        $result[$key] = StrategyFactory::serializeStrategyByType(gettype($item))->serialize($item);
                    }

                    return $result;
                }

                return null;
        }
    }
}
