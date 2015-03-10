<?php
if (!CModule::IncludeModule("intaro.intarocrm")) return;

if (!CModule::IncludeModule("sale")) return;

class RestApiSite extends \IntaroCrm\RestApi
{
    public function sitesList()
    {
        $url = $this->apiUrl.'reference/sites';
        $result = $this->curlRequest($url);
        return $result;
    }
}
$mid = 'intaro.intarocrm';
$CRM_API_HOST_OPTION = 'api_host';
$CRM_API_KEY_OPTION = 'api_key';
$CRM_CONTRAGENT_TYPE = 'contragent_type';
$CRM_SITES_LIST= 'sites_list';

$api_host = COption::GetOptionString($mid, $CRM_API_HOST_OPTION, 0);
$api_key = COption::GetOptionString($mid, $CRM_API_KEY_OPTION, 0);
$api = new RestApiSite($api_host, $api_key);

if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/intaro.intarocrm/classes/general/agent.php')) {
    unlink($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/intaro.intarocrm/classes/general/agent.php');
}
if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/intaro.intarocrm/classes/general/Exception/ApiException.php')) {
    unlink($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/intaro.intarocrm/classes/general/Exception/ApiException.php');
}
if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/retailcrm')) {
    removeDirectory($_SERVER['DOCUMENT_ROOT'] . '/retailcrm');
}

//sites
$rsSites = CSite::GetList($by, $sort, array('ACTIVE' => 'Y'));
while ($ar = $rsSites->Fetch()){
    $arSites[] = $ar;
}
if(count($arSites)>1){
    try {
        $sitesList = $api->sitesList(); 
    } catch (\IntaroCrm\Exception\CurlException $e) {
        ICrmOrderActions::eventLog(
            'intaro.crm/updater.php', 'RetailCrm\RestApi::sitesList::CurlException',
            $e->getCode() . ': ' . $e->getMessage()
        );
        return;
    }
    foreach ($arResult['arSites'] as $arSites) {
        $siteListArr[$arSites['LID']] = $sitesList[0]['code'];
    }
    COption::SetOptionString($mid, $CRM_SITES_LIST, serialize(ICrmOrderActions::clearArr($siteListArr)));
}

//contragents type list
$dbOrderTypesList = CSalePersonType::GetList(
    array(
        "SORT" => "ASC",
        "NAME" => "ASC"
    ),
    array(
         "ACTIVE" => "Y",
    ),
    false,
    false,
    array()
);

$orderTypesList = array();
while ($arOrderTypesList = $dbOrderTypesList->Fetch()){
    $orderTypesList[] = $arOrderTypesList;
}
$contragentTypeArr = array();
foreach ($orderTypesList as $orderType) {
    $contragentTypeArr[$orderType['ID']] = 'individual';
}
COption::SetOptionString($mid, $CRM_CONTRAGENT_TYPE, serialize(ICrmOrderActions::clearArr($contragentTypeArr))); 

function removeDirectory($dir) {
    if ($objs = glob($dir."/*")) {
        foreach($objs as $obj) {
            is_dir($obj) ? removeDirectory($obj) : unlink($obj);
        }
    }
    rmdir($dir);
}
