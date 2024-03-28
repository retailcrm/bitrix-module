<?php

namespace Intaro\RetailCrm\Repository;

use CSite;
use RetailcrmConfigProvider;
use IblockTable;

/**
 * Class SiteRepository
 * @package Intaro\RetailCrm\Repository
 */
class SiteRepository
{
    /**
     * @return string
     */
    public static function getDefaultServerName(): ?string
    {
        $rsSites = CSite::GetList($by, $sort, ['ACTIVE' => 'Y']);
    
        while ($ar = $rsSites->Fetch()) {
            if ($ar['DEF'] === 'Y') {
                return RetailcrmConfigProvider::getProtocol() . $ar['SERVER_NAME'];
            }
        }
        
        return null;
    }

    public static function getDomainList(): ?array
    {
        $iBlockResult= IblockTable::GetList();
        $resultBlock = [];

        while ($ar = $iBlockResult->Fetch()) {
            $resultBlock[] = $ar;
        }

        $rsSites = CSite::GetList($by, $sort, ['ACTIVE' => 'Y']);
        $resultSites = [];

        while ($ar = $rsSites = $rsSites->Fetch()) {
            $resultSites[] = $ar;
        }


        return [];
    }
}
