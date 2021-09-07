<?php

use Bitrix\Main\ORM\Objectify\EntityObject;
use Intaro\RetailCrm\Model\Bitrix\Orm\ExportTable;

/**
 * @throws \Bitrix\Main\ArgumentException
 * @throws \Bitrix\Main\ObjectPropertyException
 * @throws \Bitrix\Main\SystemException
 */
function update_5_9_0()
{
        /** @var EntityObject $exportSystem */
    $exportSystem = ExportTable::query()
            ->addSelect('*')
            ->where('FILE_NAME', 'retailcrm')
            ->fetchObject();
    
    replaceExportVars($exportSystem);
}

/**
 * @throws \Bitrix\Main\SystemException
 * @throws \Bitrix\Main\ArgumentException
 */
function replaceExportVars(EntityObject $exportSystem)
{
    $replaceableVars = [
        ['search' => 'IBLOCK_EXPORT', 'replace' => 'iblockExport'],
        ['search' => 'IBLOCK_PROPERTY_SKU', 'replace' => 'iblockPropertySku'],
        ['search' => 'IBLOCK_PROPERTY_UNIT_SKU', 'replace' => 'iblockPropertyUnitSku'],
        ['search' => 'IBLOCK_PROPERTY_PRODUCT', 'replace' => 'iblockPropertyProduct'],
        ['search' => 'IBLOCK_PROPERTY_UNIT_PRODUCT', 'replace' => 'iblockPropertyUnitProduct'],
        ['search' => 'MAX_OFFERS_VALUE', 'replace' => 'maxOffersValue'],
    ];
    $setupVars = $exportSystem->get('SETUP_VARS');
    $newSetupVars = str_replace(
        array_column($replaceableVars,'search'),
        array_column($replaceableVars, 'replace'),
        $setupVars
    );
    
    $exportSystem->set('SETUP_VARS', $newSetupVars);
    $exportSystem->save();
}
