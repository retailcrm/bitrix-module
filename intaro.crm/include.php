<?php
/*
 * 
 * Add autoload classes here
 * 
 */
CModule::AddAutoloadClasses(
    'intaro.crm',	// module name
    array (
        'IntaroCrmRestApi' => 'classes/general/IntaroCrmRestApi.php',
        'ICrmApi' => 'classes/general/ICrmApi.php'
    )
);

?>
