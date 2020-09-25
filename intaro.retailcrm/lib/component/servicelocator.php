<?php
/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Component
 * @author   retailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Component;

/**
 * Class ServiceLocator
 *
 * @package Intaro\RetailCrm\Component
 */
class ServiceLocator
{
    /**
     * @var array<string, object>
     */
    private static $services = [];

    /**
     * Register passed services
     *
     * @param array $services
     */
    public static function registerServices(array $services = []): void
    {
        foreach ($services as $name => $service) {
            static::registerService($name, $service);
        }
    }

    /**
     * Register passed service
     *
     * @param string $name
     * @param mixed  $service
     */
    public static function registerService(string $name, $service): void
    {
        if (is_string($service)) {
            if (class_exists($service)) {
                if (is_numeric($name)) {
                    $name = $service;
                }

                static::set($name, new $service());
            } else {
                throw new \RuntimeException(
                    sprintf('Cannot find class "%s" for service "%s"', $service, $name)
                );
            }
        } elseif (is_object($service)) {
            static::set($name, $service);
        } elseif (is_callable($service)) {
            $instance = $service();

            if (empty($instance)) {
                throw new \RuntimeException(
                    sprintf('Cannot register service "%s": callable didn\'t return anything.', $name)
                );
            }

            static::set($name, $instance);
        } else {
            throw new \RuntimeException(
                sprintf('Cannot register service "%s": use class FQN, instance, or factory method.', $name)
            );
        }
    }

    /**
     * Get service (returns null if service doesn't registered)
     *
     * @param string $name
     *
     * @return mixed
     */
    public static function get(string $name)
    {
        return static::$services[$name] ?? null;
    }
    
    /**
     * Get or create service (instantiates service if it wasn't created earlier; $name must be FQN).
     *
     * @param string $name
     *
     * @return mixed
     */
    public static function getOrCreate(string $name)
    {
        $service = static::$services[$name];

        if (null === $service) {
            static::$services[$name] = new $name();
            return static::$services[$name];
        }

        return $service;
    }

    /**
     * Sets service into ServiceContainer.
     *
     * @param string $name
     * @param mixed  $instance
     */
    public static function set(string $name, $instance): void
    {
        static::$services[$name] = $instance;
    }
}
