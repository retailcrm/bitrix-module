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
    /**
     * Loads dependencies
     *
     * @return bool
     */
    public static function loadDependencies()
    {
        foreach (self::getDependencies() as $dependency) {
            if (!CModule::IncludeModule($dependency)) {
                RCrmActions::eventLog(
                    __CLASS__ . '::' . __METHOD__,
                    $dependency,
                    'module not found'
                );

                return false;
            }
        }

        return true;
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
