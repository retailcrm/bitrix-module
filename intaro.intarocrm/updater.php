<?php
$mid = 'intaro.intarocrm';
$CRM_API_HOST_OPTION = 'api_host';

$api_host = COption::GetOptionString($mid, $CRM_API_HOST_OPTION, 0);
$api_host = parse_url($api_host);
if ($api_host['scheme'] != 'https') $api_host['scheme'] = 'https';
$api_host = $api_host['scheme'] . '://' . $api_host['host'];
COption::SetOptionString($mid, $CRM_API_HOST_OPTION, $api_host);