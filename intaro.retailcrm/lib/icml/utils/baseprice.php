<?php

namespace Intaro\RetailCrm\Icml\Utils;

use CCatalogGroup;
use COption;
use RetailcrmConstants;

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
        $basePriceId = COption::GetOptionString(
            RetailcrmConstants::MODULE_ID,
            RetailcrmConstants::CRM_CATALOG_BASE_PRICE . '_' . $profileID,
            0
        );
        
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