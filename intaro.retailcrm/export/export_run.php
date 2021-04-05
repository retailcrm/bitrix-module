<?php

use Bitrix\Highloadblock\HighloadBlockTable;

use Intaro\RetailCrm\Icml\RetailCrmXmlBuilder;
use Intaro\RetailCrm\Model\Bitrix\Xml\XmlSetup;
use Intaro\RetailCrm\Model\Bitrix\Xml\XmlSetupProps;
use Intaro\RetailCrm\Model\Bitrix\Xml\XmlSetupPropsCategories;

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
    
    $rsSites = CSite::GetList($by, $sort, ['ACTIVE' => 'Y']);
    
    while ($ar = $rsSites->Fetch()) {
        if ($ar['DEF'] === 'Y') {
            $SERVER_NAME = $ar['SERVER_NAME'];
        }
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
    
    $iblockProperties                  = [
        'article'      => 'article',
        'manufacturer' => 'manufacturer',
        'color'        => 'color',
        'weight'       => 'weight',
        'size'         => 'size',
        'length'       => 'length',
        'width'        => 'width',
        'height'       => 'height',
    ];
    
    $IblockPropertySku = [];
    $IblockPropertySkuHl = [];
    $IblockPropertyUnitSku = [];
    $IblockPropertyProduct = [];
    $IblockPropertyProductHl = [];
    $IblockPropertyUnitProduct = [];
    
    foreach ($iblockProperties as $prop) {
        $skuUnitProps = ('IBLOCK_PROPERTY_UNIT_SKU' . "_" . $prop);
        $skuUnitProps = $$skuUnitProps;
        
        if (is_array($skuUnitProps)) {
            foreach ($skuUnitProps as $iblock => $val) {
                $IblockPropertyUnitSku[$iblock][$prop] = $val;
            }
        }
        
        $skuProps = ('IBLOCK_PROPERTY_SKU' . "_" . $prop);
        $skuProps = $$skuProps;
        if (is_array($skuProps)) {
            foreach ($skuProps as $iblock => $val) {
                $IblockPropertySku[$iblock][$prop] = $val;
            }
        }
        
        if ($hlblockModule === true) {
            foreach ($hlblockList as $hlblockTable => $hlblock) {
                $hbProps = ('highloadblock' . $hlblockTable . '_' . $prop);
                $hbProps = $$hbProps;
                
                if (is_array($hbProps)) {
                    foreach ($hbProps as $iblock => $val) {
                        $IblockPropertySkuHl[$hlblockTable][$iblock][$prop] = $val;
                    }
                }
            }
        }

        $productUnitProps = "IBLOCK_PROPERTY_UNIT_PRODUCT" . "_" . $prop;
        $productUnitProps = $$productUnitProps;
        if (is_array($productUnitProps)) {
            foreach ($productUnitProps as $iblock => $val) {
                $IblockPropertyUnitProduct[$iblock][$prop] = $val;
            }
        }
        
        $productProps = "IBLOCK_PROPERTY_PRODUCT" . "_" . $prop;
        $productProps = $$productProps;
        if (is_array($productProps)) {
            foreach ($productProps as $iblock => $val) {
                $IblockPropertyProduct[$iblock][$prop] = $val;
            }
        }
        
        if ($hlblockModule === true) {
            foreach ($hlblockList as $hlblockTable => $hlblock) {
                $hbProps = ('highloadblock_product' . $hlblockTable . '_' . $prop);
                $hbProps = $$hbProps;
                
                if (is_array($hbProps)) {
                    foreach ($hbProps as $iblock => $val) {
                        $IblockPropertyProductHl[$hlblockTable][$iblock][$prop] = $val;
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
    
    $fileSetup = new XmlSetup();
    $fileSetup->properties = new XmlSetupPropsCategories();
    $fileSetup->properties->sku = new XmlSetupProps();
    $fileSetup->properties->products = new XmlSetupProps();
    
    $fileSetup->profileID = $profile_id;
    $fileSetup->iblocksForExport = $IBLOCK_EXPORT;

    $fileSetup->properties->sku->names = $IblockPropertySku;
    $fileSetup->properties->sku->units = $IblockPropertyUnitSku;
    $fileSetup->properties->sku->pictures = $skuPictures;

    $fileSetup->properties->products->names = $IblockPropertyProduct;
    $fileSetup->properties->products->units = $IblockPropertyUnitProduct;
    $fileSetup->properties->products->pictures = $productPictures;
    
    if ($hlblockModule === true) {
        $fileSetup->properties->highloadblockSku    = $IblockPropertySkuHl;
        $fileSetup->properties->highloadblockProduct = $IblockPropertyProductHl;
    }
    
    if ($MAX_OFFERS_VALUE) {
        $fileSetup->maxOffersValue = $MAX_OFFERS_VALUE;
    }
    
    $fileSetup->filePath = $SETUP_FILE_NAME;
    $fileSetup->defaultServerName
        = COption::GetOptionString('intaro.retailcrm', 'protocol') . $SERVER_NAME;
    $fileSetup->loadPurchasePrice = $LOAD_PURCHASE_PRICE === 'Y';

    $loader = new RetailCrmXmlBuilder($fileSetup);
    $loader->generateXml();
}