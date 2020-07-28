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
use Intaro\RetailCrm\Model\Customer;
use RetailCrm\Exception\InvalidJsonException;

/**
 * Class Serializer
 *
 * @package Intaro\RetailCrm\Component\Json
 */
class Serializer
{
    use AnnotationReaderTrait;

    /**
     * @param object $object
     *
     * @return string
     * @throws \ReflectionException
     */
    public static function serialize($object): string
    {
        $result = json_encode(static::serializeArray($object));

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidJsonException(json_last_error_msg(), json_last_error());
        }

        return (string) $result;
    }

    /**
     * @param object $object
     *
     * @return array
     * @throws \ReflectionException
     */
    public static function serializeArray($object): array
    {
        $result = [];
        $ref = new \ReflectionClass(get_class($object));

        foreach ($ref->getProperties() as $property) {
            static::serializeProperty($object, $property, $result);
        }

        return $result;
    }

    /**
     * @param object              $object
     * @param \ReflectionProperty $property
     * @param array               $result
     *
     * @throws \ReflectionException
     */
    protected static function serializeProperty($object, \ReflectionProperty $property, array &$result)
    {
        $property->setAccessible(true);

        $name = $property->getName();
        $value = $property->getValue($object);
        $annotation = static::annotationReader()->getPropertyAnnotation($property, Name::class);

        if ($annotation instanceof Name) {
            $name = !empty($annotation->name) ? $annotation->name : $name;
        }

        if (is_object($value)) {
            $result[$name] = static::serializeArray($value);
        } else {
            $result[$name] = $value;
        }
    }
}
