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
$loader->articleProperties = $IBLOCK_PROPERTY_ARTICLE;
$loader->filename = $SETUP_FILE_NAME;
$loader->application = $APPLICATION;
$loader->Load();