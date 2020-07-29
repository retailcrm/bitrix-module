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

use Intaro\RetailCrm\Component\Doctrine\Common\Annotations\AnnotationReader;
use Intaro\RetailCrm\Component\Json\Mapping\Accessor;
use Intaro\RetailCrm\Component\Json\Mapping\Name;
use Intaro\RetailCrm\Component\Json\Mapping\Type;
use Intaro\RetailCrm\Component\Json\Strategy\AnnotationReaderTrait;
use Intaro\RetailCrm\Component\Json\Strategy\StrategyFactory;

/**
 * Class EntityStrategy
 *
 * @package Intaro\RetailCrm\Component\Json\Strategy\Serialize
 */
class EntityStrategy implements DeserializeStrategyInterface
{
    use InnerTypeTrait;
    use AnnotationReaderTrait;

    /**
     * @param string $type
     * @param mixed  $value
     *
     * @return mixed
     * @throws \ReflectionException
     */
    public function deserialize(string $type, $value)
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
        $type = '';
        $name = $property->getName();
        $accessorData = static::annotationReader()->getPropertyAnnotation($property, Accessor::class);
        $nameData = static::annotationReader()->getPropertyAnnotation($property, Name::class);
        $typeData = static::annotationReader()->getPropertyAnnotation($property, Type::class);

        if ($nameData instanceof Name) {
            $name = !empty($nameData->name) ? $nameData->name : $name;
        }

        if ($typeData instanceof Type) {
            $type = $typeData->type;
        } else {
            $type = gettype($data[$name]);
        }

        $value = StrategyFactory::deserializeStrategyByType($type)->deserialize($type, $data[$name]);

        if ($accessorData instanceof Accessor && !empty($accessorData->setter)) {
            $object->{$accessorData->setter}($value);
        } else {
            $property->setAccessible(true);
            $property->setValue($object, $value);
        }
    }
}
