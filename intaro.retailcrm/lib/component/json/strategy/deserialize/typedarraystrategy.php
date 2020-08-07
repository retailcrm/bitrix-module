<?php
/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Component\Json\Strategy\Deserialize
 * @author   retailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Component\Json\Strategy\Deserialize;

use Intaro\RetailCrm\Component\Json\Exception\InvalidAnnotationException;
use Intaro\RetailCrm\Component\Json\Strategy\StrategyFactory;
use Intaro\RetailCrm\Component\Json\Strategy\TypedArrayTrait;

/**
 * Class TypedArrayStrategy
 *
 * @package Intaro\RetailCrm\Component\Json\Strategy\Deserialize
 */
class TypedArrayStrategy implements DeserializeStrategyInterface
{
    use InnerTypeTrait;
    use TypedArrayTrait;

    /**
     * @inheritDoc
     */
    public function deserialize(string $type, $value)
    {
        $keyType = '';
        $valueType = '';
        $result = [];

        if (strpos($this->innerType, ',') !== false) {
            [$keyType, $valueType] = static::getInnerTypes($this->innerType);

            if ('' === $keyType && '' === $valueType) {
                $valueType = $this->innerType;
            }
        } else {
            $valueType = $this->innerType;
        }

        $simpleStrategy = new SimpleTypeStrategy();

        foreach (array_keys($value) as $key) {
            $deserializedKey = $key;

            if ('' !== $keyType) {
                $deserializedKey = $simpleStrategy->deserialize($keyType, $key);
            }

            $result[$deserializedKey]
                = StrategyFactory::deserializeStrategyByType($valueType)->deserialize($valueType, $value[$key]);
        }

        return $result;
    }
}
