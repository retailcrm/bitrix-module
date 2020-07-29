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

use Intaro\RetailCrm\Component\Json\Mapping\PostSerialize;
use Intaro\RetailCrm\Component\Json\Strategy\AnnotationReaderTrait;
use Intaro\RetailCrm\Component\Json\Strategy\StrategyFactory;
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
     */
    public static function serializeArray($object): array
    {
        $result = (array) StrategyFactory::serializeStrategyByType(gettype($object))->serialize($object);

        return static::processPostSerialize($object, $result);
    }

    /**
     * Process post deserialize callback
     *
     * @param object $object
     * @param array  $result
     *
     * @return array
     */
    private static function processPostSerialize($object, array $result): array
    {
        $class = get_class($object);

        if ($object) {
            try {
                $reflection = new \ReflectionClass($class);
            } catch (\ReflectionException $e) {
                return $result;
            }

            foreach ($reflection->getMethods() as $method) {
                $postDeserialize = static::annotationReader()
                    ->getMethodAnnotation($method, PostSerialize::class);

                if ($postDeserialize instanceof PostSerialize) {
                    return $method->invokeArgs($object, [$result]);
                }
            }
        }

        return $result;
    }
}
