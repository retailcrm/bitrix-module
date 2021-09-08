<h3><?=GetMessage('EXPORT_CATALOGS_INFO')?></h3>

<?php
global $oldValues;

$STEP = 1;
$ACTION = 'EXPORT_SETUP';
$arOldSetupVars = $oldValues ?? [];
$SETUP_PROFILE_NAME = $oldValues['SETUP_PROFILE_NAME'] ?? GetMessage('PROFILE_NAME_EXAMPLE');

require_once __DIR__ . '/../export/export_setup.php';
?>
