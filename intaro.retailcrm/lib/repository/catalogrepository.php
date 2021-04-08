<?php

namespace Intaro\RetailCrm\Repository;

use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\SectionTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM\Objectify\Collection;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\SystemException;
use CCatalogGroup;
use CCatalogSku;
use CCatalogStoreBarCode;
use CIBlockElement;
use Intaro\RetailCrm\Model\Bitrix\Orm\CatalogIblockInfo;
use RetailcrmConfigProvider;

/**
 * Class CatalogRepository
 * @package Intaro\RetailCrm\Repository
 */
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
    public function getProductBarcodesByIblockId(int $iblockId): array
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
    
    /**
     * @param array $where
     * @param array $selectFields
     * @param int   $nPageSize
     * @param int   $pageNumber
     * @return \CIBlockResult|int
     */
    public function getProductPage(array $where, array $selectFields, int $nPageSize, int $pageNumber)
    {
        return CIBlockElement::GetList(
            [],
            $where,
            false,
            ['nPageSize' => $nPageSize, 'iNumPage' => $pageNumber, 'checkOutOfRange' => true],
            $selectFields
        );
    }
    
    /**
     * @param int $iblockId
     * @return  \Bitrix\Main\ORM\Objectify\Collection|null
     */
    public function getCategoriesByIblockId(int $iblockId): ?Collection
    {
        try {
            return SectionTable::query()
                ->addSelect('*')
                ->where('IBLOCK_ID', $iblockId)
                ->fetchCollection();
        } catch (ObjectPropertyException | ArgumentException | SystemException $exception) {
            return null;
        }
    }
    
    /**
     * @param $iblockId
     * @return EntityObject|null
     */
    public function getIblockById($iblockId): ?EntityObject
    {
        try {
            return IblockTable::query()
                ->where('ID', $iblockId)
                ->fetchObject();
        } catch (ObjectPropertyException | ArgumentException | SystemException $exception) {
            return null;
        }
    }
    
    /**
     * Возвращает информацию об инфоблоке торговых предложений по ID инфоблока товаров
     *
     * @param int $productIblockId
     * @return \Intaro\RetailCrm\Model\Bitrix\Orm\CatalogIblockInfo
     */
    public function getCatalogIblockInfo(int $productIblockId): CatalogIblockInfo
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
    
    /**
     * @param int|null $profileID
     * @return int
     */
    public function getBasePriceId(?int $profileID): int
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
    
            $result = $dbPriceType->GetNext();
            return $result['ID'];
        }
        
        return $basePriceId;
    }
}
