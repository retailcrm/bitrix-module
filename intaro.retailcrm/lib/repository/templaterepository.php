<?php

/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Repository
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

namespace Intaro\RetailCrm\Repository;

/**
 * Class TemplateRepository
 *
 * @package Intaro\RetailCrm\Repository
 */
class TemplateRepository extends AbstractRepository
{

    public const BITRIX_TEMPLATE_DIR = '/bitrix/templates/';
    public const LOCAL_TEMPLATE_DIR  = '/local/templates/';

    /**
     * @return array|false
     */
    public static function getAllIds()
    {
        $scanDirs = [
            self::BITRIX_TEMPLATE_DIR,
            self::LOCAL_TEMPLATE_DIR,
        ];
        $result   = [];

        foreach ($scanDirs as $scanDir) {
            $handle = opendir($_SERVER['DOCUMENT_ROOT'] . '/' . $scanDir);

            if ($handle) {
                while (($file = readdir($handle)) !== false) {
                    if ($file === "." || $file === "..") {
                        continue;
                    }

                    if (is_dir($_SERVER['DOCUMENT_ROOT'] . '/' . $scanDir . '/' . $file)) {
                        $result[] = [
                            'name' => $file,
                            'folder'   => $scanDir,
                        ];
                    }
                }

                closedir($handle);
            }
        }

        return $result;
    }
}
