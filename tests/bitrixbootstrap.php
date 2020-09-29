<?php
use Bitrix\Main\Loader;

define ('NOT_CHECK_PERMISSIONS', true);
define ('NO_AGENT_CHECK', true);
$GLOBALS['DBType'] = 'mysql';
$_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/../..' );

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

$_SESSION['SESS_AUTH']['USER_ID'] = 1;
require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

Loader::includeModule('intaro.retailcrm');
Loader::includeModule('sale');
require_once $_SERVER['DOCUMENT_ROOT'] . '/local/tests/helpers/Helpers.php';