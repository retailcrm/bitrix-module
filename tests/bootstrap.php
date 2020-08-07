<?php
/**
 * PHP version 7.0
 *
 * @category Integration
 */

if (file_exists(__DIR__ . '/../vendor/RetailcrmClasspathBuilder.php')) {
    require_once __DIR__ . '/../vendor/RetailcrmClasspathBuilder.php';
}

if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::create(__DIR__ . '/../');
    $dotenv->load();
}

$_SERVER['DOCUMENT_ROOT'] = getenv('BITRIX_PATH') ? getenv('BITRIX_PATH') : '/var/www/html';

define('NOT_CHECK_PERMISSIONS', true);
define('NO_AGENT_CHECK', true);

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

global $USER;
$USER->Authorize(1);

if (!IsModuleInstalled('intaro.retailcrm')) {
    RegisterModule('intaro.retailcrm');
}

COption::SetOptionString('intaro.retailcrm', 'api_version', 'v5');
CModule::IncludeModule('intaro.retailcrm');

CModule::IncludeModule("main");
global $DB;
$strSql = "INSERT INTO b_file (TIMESTAMP_X, MODULE_ID, HEIGHT, WIDTH, FILE_SIZE, CONTENT_TYPE, SUBDIR, FILE_NAME, ORIGINAL_NAME, DESCRIPTION, HANDLER_ID, EXTERNAL_ID)
VALUES ('2020-05-08 19:04:03', 'iblock', '500', '500', '23791', 'image/jpeg', 'iblock/c44', 'test.jpg', '788c4cf58bd93a5f75f2e3f2034023db.jpg', '', '', 'c570f175b3f74ccfa62c4a10d8e44b5c');";
$DB->Query($strSql);

require_once __DIR__ . 'BitrixTestCase.php';
require_once __DIR__ . '/helpers/Helpers.php';
