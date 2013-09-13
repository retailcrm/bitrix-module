<?php

set_time_limit(0);

global $APPLICATION;
if (!CModule::IncludeModule("iblock"))
    return;
if (!CModule::IncludeModule("catalog"))
    return;
if (!CModule::IncludeModule("intaro.intarocrm"))
    return;

$loader = new ICMLLoader();
$loader->iblocks = $IBLOCK_EXPORT;
$loader->propertiesSKU = $IBLOCK_PROPERTY_SKU;
$loader->propertiesProduct = $IBLOCK_PROPERTY_PRODUCT;
$loader->filename = $SETUP_FILE_NAME;
$loader->application = $APPLICATION;
$loader->Load();