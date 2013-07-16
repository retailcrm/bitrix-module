<?php
CModule::AddAutoloadClasses(
    'intaro.intarocrm', // module name
    array (
        'IntaroCrm\RestApi'      => 'classes/general/RestApi.php',
        'ICrmOrderActions' => 'classes/general/ICrmOrderActions.php'
    )
);
?>
