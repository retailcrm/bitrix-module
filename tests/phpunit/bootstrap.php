<?php
/**
 * PHP version 7.0
 *
 * @category Integration
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  http://retailcrm.ru Proprietary
 * @link     http://retailcrm.ru
 * @see      http://help.retailcrm.ru
 */

if (file_exists(__DIR__ . '/../../.env')) {
    $dotenv = Dotenv\Dotenv::create(__DIR__ . '/../../');
    $dotenv->load();
}

if (getenv('TRAVIS_BUILD_DIR')) {
    $_SERVER['DOCUMENT_ROOT'] = getenv('TRAVIS_BUILD_DIR') . '/bitrix';
} else {
    $_SERVER['DOCUMENT_ROOT'] = getenv('BITRIX_PATH');
}

define('NOT_CHECK_PERMISSIONS', true);
define('NO_AGENT_CHECK', true);

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

global $USER;
$USER->Authorize(1);

if (!CModule::IncludeModule('intaro.retailcrm')) {
    RegisterModule('intaro.retailcrm');
}
