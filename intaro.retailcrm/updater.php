<?php

$rcrmVersionFile = __DIR__ . '/install/version.php';

if(!CModule::IncludeModule('intaro.retailcrm')
    || !CModule::IncludeModule('sale')
    || !CModule::IncludeModule('iblock')
    || !CModule::IncludeModule('catalog')
    || !file_exists($rcrmVersionFile)
) {
    return;
}

include_once $rcrmVersionFile;

if (!isset($arModuleVersion['VERSION'])) {
    return;
}

$rcrmCurrentUpdateFile = __DIR__ . '/update/' . sprintf('updater-%s.php', $arModuleVersion['VERSION']);

if (file_exists($rcrmCurrentUpdateFile)) {
    include_once $rcrmCurrentUpdateFile;
    $functionName = 'update_' . str_replace('.', '_', $arModuleVersion['VERSION']);

    if (function_exists($functionName)) {
        $functionName();
    }
}
