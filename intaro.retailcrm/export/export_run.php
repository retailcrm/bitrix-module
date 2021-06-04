<?php

use Bitrix\Highloadblock\HighloadBlockTable;
use Intaro\RetailCrm\Icml\IcmlDirector;
use Intaro\RetailCrm\Model\Bitrix\Xml\XmlSetup;
use Intaro\RetailCrm\Model\Bitrix\Xml\XmlSetupProps;
use Intaro\RetailCrm\Model\Bitrix\Xml\XmlSetupPropsCategories;
use Intaro\RetailCrm\Repository\CatalogRepository;

if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/bitrix/php_interface/retailcrm/export_run.php')) {
    require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/php_interface/retailcrm/export_run.php');
} else {
    ignore_user_abort(true);
    set_time_limit(0);

    global $APPLICATION;

    if (
        !CModule::IncludeModule('iblock')
        || !CModule::IncludeModule('catalog')
        || !CModule::IncludeModule('intaro.retailcrm')
    ) {
        return;
    }

    $hlblockModule = false;

    if (CModule::IncludeModule('highloadblock')) {
        $hlblockModule = true;
        $hlblockList   = [];
        $hlblockListDb = HighloadBlockTable::getList();

        while ($hlblockArr = $hlblockListDb->Fetch()) {
            $hlblockList[$hlblockArr["TABLE_NAME"]] = $hlblockArr;
        }
    }

    $iblockProperties = [
        'article'      => 'article',
        'manufacturer' => 'manufacturer',
        'color'        => 'color',
        'weight'       => 'weight',
        'size'         => 'size',
        'length'       => 'length',
        'width'        => 'width',
        'height'       => 'height',
    ];

    $iblockPropertySku = [];
    $iblockPropertySkuHl = [];
    $iblockPropertyUnitSku = [];
    $iblockPropertyProduct = [];
    $iblockPropertyProductHl = [];
    $iblockPropertyUnitProduct = [];

    foreach ($iblockProperties as $prop) {
        $skuUnitProps = ('IBLOCK_PROPERTY_UNIT_SKU' . "_" . $prop);
        $skuUnitProps = $$skuUnitProps;

        if (is_array($skuUnitProps)) {
            foreach ($skuUnitProps as $iblock => $val) {
                $iblockPropertyUnitSku[$iblock][$prop] = $val;
            }
        }

        $skuProps = ('IBLOCK_PROPERTY_SKU' . "_" . $prop);
        $skuProps = $$skuProps;
        if (is_array($skuProps)) {
            foreach ($skuProps as $iblock => $val) {
                $iblockPropertySku[$iblock][$prop] = $val;
            }
        }

        if ($hlblockModule === true) {
            foreach ($hlblockList as $hlblockTable => $hlblock) {
                $hbProps = ('highloadblock' . $hlblockTable . '_' . $prop);
                $hbProps = $$hbProps;

                if (is_array($hbProps)) {
                    foreach ($hbProps as $iblock => $val) {
                        $iblockPropertySkuHl[$hlblockTable][$iblock][$prop] = $val;
                    }
                }
            }
        }

        $productUnitProps = "IBLOCK_PROPERTY_UNIT_PRODUCT" . "_" . $prop;
        $productUnitProps = $$productUnitProps;
        if (is_array($productUnitProps)) {
            foreach ($productUnitProps as $iblock => $val) {
                $iblockPropertyUnitProduct[$iblock][$prop] = $val;
            }
        }

        $productProps = "IBLOCK_PROPERTY_PRODUCT" . "_" . $prop;
        $productProps = $$productProps;
        if (is_array($productProps)) {
            foreach ($productProps as $iblock => $val) {
                $iblockPropertyProduct[$iblock][$prop] = $val;
            }
        }

        if ($hlblockModule === true) {
            foreach ($hlblockList as $hlblockTable => $hlblock) {
                $hbProps = ('highloadblock_product' . $hlblockTable . '_' . $prop);
                $hbProps = $$hbProps;

                if (is_array($hbProps)) {
                    foreach ($hbProps as $iblock => $val) {
                        $iblockPropertyProductHl[$hlblockTable][$iblock][$prop] = $val;
                    }
                }
            }
        }
    }

    $productPictures = [];

    if (is_array($IBLOCK_PROPERTY_PRODUCT_picture)) {
        foreach ($IBLOCK_PROPERTY_PRODUCT_picture as $key => $value) {
            $productPictures[$key] = $value;
        }
    }

    $skuPictures = [];

    if (is_array($IBLOCK_PROPERTY_SKU_picture)) {
        foreach ($IBLOCK_PROPERTY_SKU_picture as $key => $value) {
            $skuPictures[$key] = $value;
        }
    }

    $xmlProps = new XmlSetupPropsCategories(
        new XmlSetupProps($iblockPropertyProduct, $iblockPropertyUnitProduct, $productPictures),
        new XmlSetupProps($iblockPropertySku, $iblockPropertyUnitSku, $skuPictures)
    );

    if ($hlblockModule === true) {
        $xmlProps->highloadblockSku    = $iblockPropertySkuHl;
        $xmlProps->highloadblockProduct = $iblockPropertyProductHl;
    }

    $fileSetup = new XmlSetup($xmlProps);
    $fileSetup->profileId = $profile_id;
    $fileSetup->iblocksForExport = $iblockExport;
    $fileSetup->maxOffersValue = $maxOffersValue ?? null;
    $fileSetup->filePath = $setupFileName;
    $fileSetup->loadPurchasePrice = $loadPurchasePrice === 'Y';
    $fileSetup->basePriceId = CatalogRepository::getBasePriceId($fileSetup->profileId);
    $logger = Logger::getInstance('/bitrix/catalog_export/');

    if (!is_array($fileSetup->iblocksForExport) || count($fileSetup->iblocksForExport) === 0) {
        $logger->write(GetMessage("IBLOCK_NOT_SELECTED"), 'i_crm_load_log');
    } else {
        $loader = new IcmlDirector($fileSetup, $logger);
        $loader->generateXml();
    }
}
