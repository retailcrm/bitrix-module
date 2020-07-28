<?php

/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Component\Json
 * @author   retailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Component\Json;

use Intaro\RetailCrm\Component\Json\Mapping\JsonProperty;
use Intaro\RetailCrm\Component\Json\Mapping\Name;
use Intaro\RetailCrm\Component\Json\Mapping\Type;
use RetailCrm\Exception\InvalidJsonException;

/**
 * Class Deserializer
 *
 * @package Intaro\RetailCrm\Component\Json
 */
class Deserializer
{
    use AnnotationReaderTrait;

    /**
     * @param string $json
     * @param string $className
     *
     * @return mixed
     * @throws \ReflectionException
     */
    public static function deserialize(string $json, string $className)
    {
        return static::deserializeArray(json_decode($json, true), $className);
    }

    /**
     * @param array  $data
     * @param string $className
     *
     * @return mixed
     * @throws \ReflectionException
     */
    public static function deserializeArray(array $data, string $className)
    {
        $reflection = new \ReflectionClass($className);
        $instance = new $className();

        foreach ($reflection->getProperties() as $property) {
            static::deserializeProperty($instance, $property, $data);
        }

        return $instance;
    }

    /**
     * @param object              $object
     * @param \ReflectionProperty $property
     * @param array               $data
     *
     * @throws \ReflectionException
     */
    protected static function deserializeProperty($object, \ReflectionProperty $property, array $data)
    {
        $property->setAccessible(true);

        $name = $property->getName();
        $fqcn = '';

        $nameData = static::annotationReader()->getPropertyAnnotation($property, Name::class);
        $fqcnData = static::annotationReader()->getPropertyAnnotation($property, Type::class);

        if ($nameData instanceof Name) {
            $name = !empty($nameData->name) ? $nameData->name : $name;
        }

        if ($fqcnData instanceof Type) {
            $fqcn = $fqcnData->type;
        }

        if (empty($fqcn)) {
            $property->setValue($object, $data[$name]);
        } else {
            $property->setValue($object, static::deserializeArray($data[$name], $fqcn));
        }
    }
}
