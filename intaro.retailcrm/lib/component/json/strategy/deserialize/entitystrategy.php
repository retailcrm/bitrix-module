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

use Intaro\RetailCrm\Component\Json\Mapping\Accessor;
use Intaro\RetailCrm\Component\Json\Mapping\SerializedName;
use Intaro\RetailCrm\Component\Json\Mapping\Type;
use Intaro\RetailCrm\Component\Json\PropertyAnnotations;
use Intaro\RetailCrm\Component\Json\Strategy\IsNoTransformTrait;
use Intaro\RetailCrm\Component\Json\Strategy\StrategyFactory;

/**
 * Class EntityStrategy
 *
 * @package Intaro\RetailCrm\Component\Json\Strategy\Serialize
 */
class EntityStrategy implements DeserializeStrategyInterface
{
    use InnerTypeTrait;
    use IsNoTransformTrait;

    /**
     * @param string $type
     * @param mixed  $value
     * @param null   $annotations
     *
     * @return mixed
     * @throws \ReflectionException
     */
    public function deserialize(string $type, $value, $annotations = null)
    {
        if (empty($value)) {
            return null;
        }

        $reflection = new \ReflectionClass($type);
        $instance = new $type();

        if (!$reflection->isUserDefined()) {
            if (is_iterable($value)) {
                foreach ($value as $field => $content) {
                    $instance->$field = $content;
                }
            }

            return $instance;
        }

        foreach ($reflection->getProperties() as $property) {
            static::deserializeProperty($instance, $property, $value);
        }

        return $instance;
    }

    /**
     * @param object              $object
     * @param \ReflectionProperty $property
     * @param array               $data
     */
    protected static function deserializeProperty($object, \ReflectionProperty $property, array $data): void
    {
        $annotations = new PropertyAnnotations(static::annotationReader()->getPropertyAnnotations($property));

        if (!($annotations->serializedName instanceof SerializedName)) {
            return;
        }

        if ($annotations->type instanceof Type) {
            $type = $annotations->type->type;
        } else {
            $type = gettype($data[$annotations->serializedName->name]);
        }

        if (static::isNoTransform($property)) {
            $value = $data[$annotations->serializedName->name];
        } else {
            $value = StrategyFactory::deserializeStrategyByType($type)
                ->deserialize($type, $data[$annotations->serializedName->name], $annotations);
        }

        if ($annotations->accessor instanceof Accessor && !empty($annotations->accessor->setter)) {
            $object->{$annotations->accessor->setter}($value);
        } else {
            $property->setAccessible(true);
            $property->setValue($object, $value);
        }
    }
}
