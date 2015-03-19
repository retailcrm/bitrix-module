<?php
if (!CModule::IncludeModule("main")) return;
DeleteDirFilesEx('/retailcrm');
DeleteDirFilesEx('/bitrix/modules/intaro.intarocrm/classes/general/agent.php');
DeleteDirFilesEx('/bitrix/modules/intaro.intarocrm/classes/general/Exception/ApiException.php');