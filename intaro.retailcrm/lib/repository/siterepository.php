<?php

namespace Intaro\RetailCrm\Repository;

use CSite;
use RetailcrmConfigProvider;
use Bitrix\Iblock\IblockTable;

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
        $result = [];
        $resultBlock = [];
        $resultSites = [];

        try {
            $iBlockResult = IblockTable::GetList(['select' => ['ID', 'LID']], $sort, ['ACTIVE' => 'Y']);

            while ($ar = $iBlockResult->Fetch()) {
                $resultBlock[] = $ar;
            }

            $resultBlock = array_column($resultBlock, 'LID', 'ID');

            $rsSites = CSite::GetList($by, $sort, ['ACTIVE' => 'Y']);

            while ($ar = $rsSites->Fetch()) {
                $resultSites[] = $ar;
            }

            $resultSites = array_column($resultSites, 'SERVER_NAME', 'LID');

            foreach ($resultBlock as $id => $lid) {
                if (isset($resultSites[$lid])) {
                    $result[$id] = $resultSites[$lid];
                }
            }
        } catch (\Throwable $exception) {
            RCrmActions::eventLog(
                'SiteRepository:getDomainList',
                'domain',
                'Error when obtaining domains: ' . $exception->getMessage()
            );
        }

        return $result;
    }
}
