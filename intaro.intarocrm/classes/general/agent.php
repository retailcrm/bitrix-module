<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');
if (!CModule::IncludeModule('intaro.intarocrm')) die('retailCRM not installed.');

ICrmOrderActions::orderAgent();
