<?php
define("NO_KEEP_STATISTIC", true);
require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');
$GLOBALS['APPLICATION']->RestartBuffer();
$moduleId = 'intaro.retailcrm';
$historyTime = 'history_time';
$idOrderCRM = (int)$_REQUEST['idOrderCRM'];

if (CModule::IncludeModule($moduleId) && $idOrderCRM && $idOrderCRM > 0) {
    $timeBd = COption::GetOptionString($moduleId, $historyTime, 0);
    $nowDate = date('Y-m-d H:i:s');
    if (!empty($timeBd)) {
        $timeBdObj = new \DateTime($timeBd);
        $newTimeBdObj = $timeBdObj->modify('+5 min');
        $nowDateObj = new \DateTime($nowDate);
        //If there is a record, but it is older than 5 minutes, overwrite
        if ($newTimeBdObj < $nowDateObj) {
            COption::SetOptionString($moduleId, $historyTime, $nowDate);
            //call history
            RCrmActions::orderAgent();
            
            COption::RemoveOption($moduleId, $historyTime);
        }       
    } else {
        COption::SetOptionString($moduleId, $historyTime, $nowDate);
        //call history
        RCrmActions::orderAgent();
        
        COption::RemoveOption($moduleId, $historyTime);
    }
}