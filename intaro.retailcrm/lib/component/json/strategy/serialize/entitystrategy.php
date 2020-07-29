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

use Intaro\RetailCrm\Component\Doctrine\Common\Annotations\AnnotationReader;
use Intaro\RetailCrm\Component\Json\Mapping\Accessor;
use Intaro\RetailCrm\Component\Json\Mapping\SerializedName;
use Intaro\RetailCrm\Component\Json\Mapping\Type;
use Intaro\RetailCrm\Component\Json\Strategy\AnnotationReaderTrait;
use Intaro\RetailCrm\Component\Json\Strategy\StrategyFactory;

/**
 * Class EntityStrategy
 *
 * @package Intaro\RetailCrm\Component\Json\Strategy\Serialize
 */
class EntityStrategy implements SerializeStrategyInterface
{
    use InnerTypeTrait;
    use AnnotationReaderTrait;

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
        $accessorData = static::annotationReader()->getPropertyAnnotation($property, Accessor::class);
        $name = $property->getName();

        if ($accessorData instanceof Accessor && !empty($accessorData->getter)) {
            $value = $object->{$accessorData->getter}();
        } else {
            $property->setAccessible(true);
            $value = $property->getValue($object);
        }

        $nameData = static::annotationReader()->getPropertyAnnotation($property, SerializedName::class);
        $typeData = static::annotationReader()->getPropertyAnnotation($property, Type::class);

        if ($nameData instanceof SerializedName) {
            $name = !empty($nameData->name) ? $nameData->name : $name;
        }

        if ($typeData instanceof Type) {
            $result[$name] = StrategyFactory::serializeStrategyByType($typeData->type)->serialize($value);
        } else {
            $result[$name] = StrategyFactory::serializeStrategyByType(gettype($value))->serialize($value);
        }
    }
}
