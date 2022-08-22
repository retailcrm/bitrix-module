<?php

namespace Intaro\RetailCrm\Icml;

use Intaro\RetailCrm\Model\Bitrix\Orm\CatalogIblockInfo;
use Intaro\RetailCrm\Model\Bitrix\Xml\SelectParams;

/**
 * Class QueryParamsMolder
 * @package Intaro\RetailCrm\Icml
 */
class QueryParamsMolder
{
    /**
     * Формирует параметры товаров или офферов для запросов к БД на основе настроек экспорта
     *
     * @param array|null $userProps
     * @param int        $basePriceId
     * @return \Intaro\RetailCrm\Model\Bitrix\Xml\SelectParams
     */
    public function getSelectParams(?array $userProps, int $basePriceId): SelectParams
    {
        $catalogFields = ['catalog_length', 'catalog_width', 'catalog_height', 'catalog_weight'];

        $params = new SelectParams();

        foreach ($userProps as $key => $name) {
            if ($name === '') {
                unset($userProps[$key]);
                continue;
            }

            if (in_array($name, $catalogFields, true)) {
                $userProps[$key] = strtoupper($userProps[$key]);
            } else {
                $userProps[$key] = 'PROPERTY_' . $userProps[$key];
            }
        }

        $params->configurable = $userProps ?? [];
        $params->main         = [
            'LANG_DIR',
            'CODE',
            'IBLOCK_ID',
            'IBLOCK_CODE',
            'IBLOCK_SECTION_ID',
            'IBLOCK_EXTERNAL_ID',
            'NAME',
            'DETAIL_PICTURE',
            'PREVIEW_PICTURE',
            'DETAIL_PAGE_URL',
            'CATALOG_QUANTITY',
            'CATALOG_TYPE',
            'CATALOG_PRICE_' . $basePriceId,
            'CATALOG_PURCHASING_PRICE',
            'EXTERNAL_ID',
            'CATALOG_GROUP_' . $basePriceId,
            'ID',
            'LID',
            'VAT_ID',
        ];

        return $params;
    }

    /**
     * @param int|null                                             $parentId
     * @param \Intaro\RetailCrm\Model\Bitrix\Orm\CatalogIblockInfo $info
     * @return array
     */
    public function getWhereForOfferPart(?int $parentId, CatalogIblockInfo $info): array
    {
        if ($parentId === null) {
            return [
                'IBLOCK_ID' => $info->productIblockId,
                'ACTIVE'    => 'Y',
            ];
        }

        return [
            'IBLOCK_ID'                        => $info->skuIblockId,
            'ACTIVE'                           => 'Y',
            'PROPERTY_' . $info->skuPropertyId => $parentId,
        ];
    }
}
