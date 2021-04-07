<?php

namespace Intaro\RetailCrm\Repository;

use CCatalogStoreBarCode;
use CIBlockElement;

class CatalogRepository
{
    /**
     * Получение категорий, к которым относится товар
     *
     * @param $offerId
     * @return array
     */
    public function getProductCategoriesIds(int $offerId): array
    {
        $query = CIBlockElement::GetElementGroups($offerId, false, ['ID']);
        $ids   = [];
        
        while ($category = $query->GetNext()) {
            $ids[] = $category['ID'];
        }
        
        return $ids;
    }
    
    /**
     * Returns products IDs with barcodes by infoblock id
     *
     * @param int $iblockId
     *
     * @return array
     */
    public function getProductBarcodesByIblock(int $iblockId): array
    {
        $barcodes  = [];
        $dbBarCode = CCatalogStoreBarCode::getList(
            [],
            ['IBLOCK_ID' => $iblockId],
            false,
            false,
            ['PRODUCT_ID', 'BARCODE']
        );
        
        while ($arBarCode = $dbBarCode->GetNext()) {
            if (!empty($arBarCode)) {
                $barcodes[$arBarCode['PRODUCT_ID']] = $arBarCode['BARCODE'];
            }
        }
        
        return $barcodes;
    }
}
