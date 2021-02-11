<?php

namespace Intaro\RetailCrm\Icml\Utils;

use Bitrix\Main\Diag\Debug;

class IcmlLogger
{
    /**
     * @param string $msg
     * @param string $level
     */
    public static function writeToToLog(string $msg, string $level): void
    {
        Debug::writeToFile($msg, $level, '/bitrix/catalog_export/i_crm_load_log.txt');
    }
}