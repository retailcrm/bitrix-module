<?php

namespace Tests\Intaro\RetailCrm;

use Intaro\RetailCrm\Component\ConfigProvider;

class Helpers
{
    /** @var \ReflectionClass */
    private static $configReflection;

    /**
     * Sets property into config provider
     *
     * @param string $propertyName
     * @param mixed  $value
     *
     * @throws \ReflectionException
     */
    public static function setConfigProperty(string $propertyName, $value)
    {
        static::regenerateConfigReflection();
        $property = static::$configReflection->getProperty($propertyName);
        $property->setAccessible(true);
        $property->setValue($value);
    }

    /**
     * Sets property into config provider
     *
     * @param string $propertyName
     *
     * @return mixed
     * @throws \ReflectionException
     */
    public static function getConfigProperty(string $propertyName): mixed
    {
        static::regenerateConfigReflection();
        $property = static::$configReflection->getProperty($propertyName);
        $property->setAccessible(true);
        return $property->getValue();
    }

    /**
     * Regenerates config reflection
     */
    protected static function regenerateConfigReflection(): void
    {
        if (null === static::$configReflection) {
            static::$configReflection = new \ReflectionClass(ConfigProvider::class);
        }
    }
}
