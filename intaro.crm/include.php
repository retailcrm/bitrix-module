<?php
CModule::AddAutoloadClasses(
    'intaro.crm', // module name
    array (
        'IntaroCrm\RestApi'      => 'classes/general/RestApi.php',
        'ICrmOrderActions' => 'classes/general/ICrmOrderActions.php'
    )
);
?>
