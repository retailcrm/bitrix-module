<?php

namespace Intaro\RetailCrm\Icml\Utils;

use CCatalogSku;
use Intaro\RetailCrm\Model\Bitrix\Orm\CatalogIblockInfo;

/**
 * Class IblockUtils
 * @package Intaro\RetailCrm\Icml\Utils
 */
class IblockUtils
{
    /**
     * Возвращает информацию об инфоблоке торговых предложений по ID инфоблока товаров
     *
     * @param int $productIblockId
     * @return \Intaro\RetailCrm\Model\Bitrix\Orm\CatalogIblockInfo
     */
    public static function getCatalogIblockInfo(int $productIblockId): CatalogIblockInfo
    {
        $catalogIblockInfo = new CatalogIblockInfo();
        $info              = CCatalogSKU::GetInfoByProductIBlock($productIblockId);
        
        if ($info === false) {
            $catalogIblockInfo->productIblockId = $productIblockId;
            
            return $catalogIblockInfo;
        }
        
        $catalogIblockInfo->skuIblockId     = $info['IBLOCK_ID'];
        $catalogIblockInfo->productIblockId = $info['PRODUCT_IBLOCK_ID'];
        $catalogIblockInfo->skuPropertyId   = $info['SKU_PROPERTY_ID'];
        
        return $catalogIblockInfo;
    }
}