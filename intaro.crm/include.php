<?php
CModule::AddAutoloadClasses(
    'intaro.crm',	// module name
    array (
        'IntaroCrmRestApi' => 'classes/general/IntaroCrmRestApi.php',
        'ICrmApi' => 'classes/general/ICrmApi.php'
    )
);
?>
