<?php

/**
 * PHP version 5.3
 *
 * RetailcrmDependencyLoader class
 *
 * @category RetailCrm
 * @package  RetailCrm
 * @author   RetailCrm <integration@retailcrm.ru>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     http://www.retailcrm.ru/docs/Developers/ApiVersion4
 */

IncludeModuleLangFile(__FILE__);

/**
 * PHP version 5.3
 *
 * RetailcrmDependencyLoader class
 *
 * @category RetailCrm
 * @package  RetailCrm
 * @author   RetailCrm <integration@retailcrm.ru>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     http://www.retailcrm.ru/docs/Developers/ApiVersion4
 */
class RetailcrmDependencyLoader
{
    /** @var int */
    const LEGACY_LOADER = 0;

    /** @var int */
    const D7_LOADER = 1;

    /** @var int $loader */
    private static $loader = self::D7_LOADER;

    /**
     * Loads dependencies
     *
     * @return bool
     */
    public static function loadDependencies()
    {
        foreach (self::getDependencies() as $dependency) {
            if (self::LEGACY_LOADER == self::$loader) {
                if (!CModule::IncludeModule($dependency)) {
                    RCrmActions::eventLog(
                        __CLASS__ . '::' . __METHOD__,
                        $dependency,
                        'module not found'
                    );

                    return false;
                }
            } else {
                try {
                    if (!\Bitrix\Main\Loader::includeModule($dependency)) {
                        RCrmActions::eventLog(
                            __CLASS__ . '::' . __METHOD__,
                            $dependency,
                            'module not found'
                        );

                        return false;
                    }
                } catch (\Bitrix\Main\LoaderException $exception) {
                    RCrmActions::eventLog(
                        __CLASS__ . '::' . __METHOD__,
                        $dependency,
                        sprintf('error while trying to load module: %s', $exception->getMessage())
                    );

                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Set loader mode. Use RetailcrmDependencyLoader::LEGACY_LOADER or RetailcrmDependencyLoader::D7_LOADER
     *
     * @param $loader
     */
    public static function setLoader($loader)
    {
        if (in_array($loader, array(self::LEGACY_LOADER, self::D7_LOADER))) {
            self::$loader = $loader;
        }
    }

    /**
     * Returns array of required modules names
     *
     * @return array<string>
     */
    public static function getDependencies()
    {
        return array("iblock", "sale", "catalog");
    }
}
