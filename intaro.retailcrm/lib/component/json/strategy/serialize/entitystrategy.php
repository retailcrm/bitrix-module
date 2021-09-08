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
    public function serialize($value)
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
        $nameData = static::annotationReader()->getPropertyAnnotation($property, SerializedName::class);

        if (!($nameData instanceof SerializedName)) {
            return;
        }

        $accessorData = static::annotationReader()->getPropertyAnnotation($property, Accessor::class);

        if ($accessorData instanceof Accessor && !empty($accessorData->getter)) {
            $value = $object->{$accessorData->getter}();
        } else {
            $property->setAccessible(true);
            $value = $property->getValue($object);
        }

        if (static::isNoTransform($property)) {
            $result[$nameData->name] = $value;
        } else {
            $typeData = static::annotationReader()->getPropertyAnnotation($property, Type::class);

            if ($typeData instanceof Type) {
                $result[$nameData->name] = StrategyFactory::serializeStrategyByType($typeData->type)->serialize($value);
            } else {
                $result[$nameData->name] = StrategyFactory::serializeStrategyByType(gettype($value))->serialize($value);
            }
        }
    }
}
