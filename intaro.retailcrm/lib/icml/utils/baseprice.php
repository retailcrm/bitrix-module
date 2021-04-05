<?php

namespace Intaro\RetailCrm\Icml\Utils;

use CCatalogGroup;
use RetailcrmConfigProvider;

/**
 * Class BasePrice
 * @package Intaro\RetailCrm\Icml\Utils
 */
class BasePrice
{
    /**
     * @param $profileID
     * @return int|null
     */
    public static function getBasePriceId($profileID): ?int
    {
        $basePriceId = RetailcrmConfigProvider::getCatalogBasePriceByProfile($profileID);
        
        if (!$basePriceId) {
            $dbPriceType = CCatalogGroup::GetList(
                [],
                ['BASE' => 'Y'],
                false,
                false,
                ['ID']
            );
            
            $result      = $dbPriceType->GetNext();
            $basePriceId = $result['ID'];
        }
        
        return $basePriceId;
    }
}
