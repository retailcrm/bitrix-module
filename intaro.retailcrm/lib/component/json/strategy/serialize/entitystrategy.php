<?php
/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Component\Json\Strategy\Serialize
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Component\Json\Strategy\Serialize;

use Intaro\RetailCrm\Component\Json\PropertyAnnotations;
use Intaro\RetailCrm\Component\Json\Strategy\IsNoTransformTrait;
use Intaro\RetailCrm\Component\Json\Mapping\Accessor;
use Intaro\RetailCrm\Component\Json\Mapping\SerializedName;
use Intaro\RetailCrm\Component\Json\Mapping\Type;
use Intaro\RetailCrm\Component\Json\Strategy\StrategyFactory;

/**
 * Class EntityStrategy
 *
 * @package Intaro\RetailCrm\Component\Json\Strategy\Serialize
 */
class EntityStrategy implements SerializeStrategyInterface
{
    use InnerTypeTrait;
    use IsNoTransformTrait;

    /**
     * @inheritDoc
     * @throws \ReflectionException
     */
    public function serialize($value, $annotations = null)
    {
        if (empty($value)) {
            return null;
        }

        $result = [];
        $reflection = new \ReflectionClass(get_class($value));

        if (!$reflection->isUserDefined()) {
            return (array) $value;
        }

        foreach ($reflection->getProperties() as $property) {
            static::serializeProperty($value, $property, $result);
        }

        return $result;
    }

    /**
     * @param object              $object
     * @param \ReflectionProperty $property
     * @param array               $result
     */
    protected static function serializeProperty($object, \ReflectionProperty $property, array &$result): void
    {
        $annotations = new PropertyAnnotations(static::annotationReader()->getPropertyAnnotations($property));

        if (!($annotations->serializedName instanceof SerializedName)) {
            return;
        }

        if ($annotations->accessor instanceof Accessor && !empty($annotations->accessor->getter)) {
            $value = $object->{$annotations->accessor->getter}();
        } else {
            $property->setAccessible(true);
            $value = $property->getValue($object);
        }

        if (static::isNoTransform($property)) {
            $result[$annotations->serializedName->name] = $value;
        } else {
            if ($annotations->type instanceof Type) {
                $result[$annotations->serializedName->name] =
                    StrategyFactory::serializeStrategyByType($annotations->type->type)->serialize($value, $annotations);
            } else {
                $result[$annotations->serializedName->name] =
                    StrategyFactory::serializeStrategyByType(gettype($value))->serialize($value, $annotations);
            }
        }
    }
}
