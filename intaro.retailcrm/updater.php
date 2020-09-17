<?php

if(!CModule::IncludeModule('intaro.retailcrm') || !CModule::IncludeModule('sale') || !CModule::IncludeModule('iblock') || !CModule::IncludeModule('catalog'))
    return;

include_once __DIR__ . '/install/version.php';

$rcrmCurrentUpdateFile = __DIR__ . '/update/' . sprintf('updater-%s.php', $arModuleVersion['VERSION']);

if (file_exists($rcrmCurrentUpdateFile)) {
    include_once $rcrmCurrentUpdateFile;
}

