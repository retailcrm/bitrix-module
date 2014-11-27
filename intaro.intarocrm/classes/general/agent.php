<?php
define('NO_AGENT_CHECK', true);
require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');
if ($_SERVER['SERVER_ADDR'] != $_SERVER['REMOTE_ADDR']) die('You are not allowed to access this file.');
if (!CModule::IncludeModule('intaro.intarocrm')) die('retailCRM not installed.');

ICrmOrderActions::notForkedOrderAgent();
