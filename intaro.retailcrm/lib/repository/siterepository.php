<?php

namespace Intaro\RetailCrm\Repository;

use CSite;
use RetailcrmConfigProvider;

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
}
