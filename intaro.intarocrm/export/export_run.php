<?php

set_time_limit(0);

global $APPLICATION;
if (!CModule::IncludeModule("iblock"))
    return;
if (!CModule::IncludeModule("catalog"))
    return;
if (!CModule::IncludeModule("intaro.intarocrm"))
    return;

$iblockProperties = Array(
        "article" => "article",
        "manufacturer" => "manufacturer",
        "color" =>"color",
        "weight" => "weight",
        "size" => "size",
    );
$IBLOCK_PROPERTY_SKU = array();
foreach ($iblockProperties as $prop) {
    $skuProps = ('IBLOCK_PROPERTY_SKU' . "_" . $prop);
    $skuProps = $$skuProps;
    foreach ($skuProps as $iblock => $val) {
        $IBLOCK_PROPERTY_SKU[$iblock][$prop] = $val;
    }
}
$IBLOCK_PROPERTY_PRODUCT = array();
foreach ($iblockProperties as $prop) {
    $skuProps = "IBLOCK_PROPERTY_PRODUCT" . "_" . $prop;
    $skuProps = $$skuProps;
    foreach ($skuProps as $iblock => $val) {
        $IBLOCK_PROPERTY_PRODUCT[$iblock][$prop] = $val;
    }
}


$loader = new ICMLLoader();
$loader->iblocks = $IBLOCK_EXPORT;
$loader->propertiesSKU = $IBLOCK_PROPERTY_SKU;
$loader->propertiesProduct = $IBLOCK_PROPERTY_PRODUCT;
$loader->filename = $SETUP_FILE_NAME;
$loader->application = $APPLICATION;
$loader->Load();