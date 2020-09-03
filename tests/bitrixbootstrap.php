<?php
// Подключение ядра 1С-Битрикс
define ('NOT_CHECK_PERMISSIONS', true);
define ('NO_AGENT_CHECK', true);
$GLOBALS['DBType'] = 'mysql';
$_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/../..' );

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

// Искуственная авторизация в роли админа
$_SESSION['SESS_AUTH']['USER_ID'] = 1;
// Подключение автозаргрузки Composer
require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

\Bitrix\Main\Loader::includeModule('intaro.retailcrm');
\Bitrix\Main\Loader::includeModule('sale');
require_once $_SERVER['DOCUMENT_ROOT'] . '/local/tests/helpers/Helpers.php';