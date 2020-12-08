<?php
/**
 * PHP version 7.0
 *
 * @category Integration
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  https://retailcrm.ru Proprietary
 * @link     https://retailcrm.ru
 * @see      https://docs.retailcrm.ru
 */

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
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

require_once 'BitrixTestCase.php';
