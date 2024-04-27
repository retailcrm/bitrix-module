<?php

/**
 * @category Integration
 * @package  Intaro\RetailCrm\Component\Json
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

namespace Intaro\RetailCrm\Component\Json;

use Intaro\RetailCrm\Component\Json\Mapping\PostDeserialize;
use Intaro\RetailCrm\Component\Json\Strategy\AnnotationReaderTrait;
use Intaro\RetailCrm\Component\Json\Strategy\StrategyFactory;
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
     * @param string $type
     * @param string $json
     *
     * @return mixed
     */
    public static function deserialize(string $json, string $type)
    {
        $result = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidJsonException(json_last_error_msg(), json_last_error());
        }

        return static::deserializeArray($result, $type);
    }

    /**
     * @param array|null $value
     *
     * @param string     $type
     *
     * @return mixed
     */
    public static function deserializeArray(?array $value, string $type)
    {
        $result = StrategyFactory::deserializeStrategyByType($type)->deserialize($type, $value ?? []);

        static::processPostDeserialize($result);

        return $result;
    }

    /**
     * Process post deserialize callback
     *
     * @param object $object
     */
    private static function processPostDeserialize($object): void
    {
        $class = get_class($object);

        if ($object) {
            try {
                $reflection = new \ReflectionClass($class);
            } catch (\ReflectionException $e) {
                return;
            }

            foreach ($reflection->getMethods() as $method) {
                $postDeserialize = static::annotationReader()
                    ->getMethodAnnotation($method, PostDeserialize::class);

                if ($postDeserialize instanceof PostDeserialize) {
                    $method->invoke($object);

                    break;
                }
            }
        }
    }
}
