<?
if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/bitrix/php_interface/include/catalog_export/intarocrm_run.php')) {
    unlink($_SERVER['DOCUMENT_ROOT'] . '/bitrix/php_interface/include/catalog_export/intarocrm_run.php');
}
$updater->CopyFiles("install/export/intarocrm_run.php", "php_interface/include/catalog_export/intarocrm_run.php");

if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/bitrix/php_interface/include/catalog_export/intarocrm_setup.php')) {
    unlink($_SERVER['DOCUMENT_ROOT'] . '/bitrix/php_interface/include/catalog_export/intarocrm_setup.php');
}
$updater->CopyFiles("install/export/intarocrm_setup.php", "php_interface/include/catalog_export/intarocrm_setup.php");
