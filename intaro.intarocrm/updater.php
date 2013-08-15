<?
if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/bitrix/php_interface/catalog_export/intarocrm_run.php')) {
    unlink($_SERVER['DOCUMENT_ROOT'] . '/bitrix/php_interface/catalog_export/intarocrm_run.php');
}
$updater->CopyFiles("install/export/intarocrm_run.php", "php_interface/catalog_export/intarocrm_run.php");

if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/bitrix/php_interface/catalog_export/intarocrm_setup.php')) {
    unlink($_SERVER['DOCUMENT_ROOT'] . '/bitrix/php_interface/catalog_export/intarocrm_setup.php');
}
$updater->CopyFiles("install/export/intarocrm_setup.php", "php_interface/catalog_export/intarocrm_setup.php");


$PROFILE_ID = CCatalogExport::Add(array(
    "LAST_USE"		=> false,
    "FILE_NAME"		=> 'intarocrm',
    "NAME"		=> 'intarocrmprofile',
    "DEFAULT_PROFILE"   => "N",
    "IN_MENU"		=> "N",
    "IN_AGENT"		=> "Y",
    "IN_CRON"		=> "N",
    "NEED_EDIT"		=> "N",
    "SETUP_VARS"	=> $ar
    ));
if (intval($PROFILE_ID) <= 0) {
    $arResult['errCode'] = 'ERR_IBLOCK';
    return;
}
$dateAgent = new DateTime();
$intAgent = new DateInterval('PT60S'); // PT60S - 60 sec;
$dateAgent->add($intAgent);
CAgent::AddAgent(
        "CCatalogExport::PreGenerateExport(" . $PROFILE_ID . ");", 
        "catalog", 
        "N", 
        86400, 
        $dateAgent->format('d.m.Y H:i:s'), // date of first check
        "Y", // агент активен
        $dateAgent->format('d.m.Y H:i:s'), // date of first start
        30
        );




