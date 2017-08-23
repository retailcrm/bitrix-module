<?php
if (!CModule::IncludeModule("main")) return;

$mid = 'intaro.retailcrm';
$CRM_INVENTORIES_UPLOAD = 'inventories_upload';
$CRM_PRICES_UPLOAD = 'prices_upload';
$CRM_COLLECTOR = 'collector';
$CRM_UA = 'ua';
$CRM_API_VERSION = 'api_version';

COption::SetOptionString($mid, $CRM_INVENTORIES_UPLOAD, 'N');
COption::SetOptionString($mid, $CRM_PRICES_UPLOAD, 'N');
COption::SetOptionString($mid, $CRM_COLLECTOR, 'N');
COption::SetOptionString($mid, $CRM_UA, 'N');
COption::SetOptionString($mid, $CRM_API_VERSION, 'v4');

COption::RemoveOption($mid, 'catalog_base_iblocks');