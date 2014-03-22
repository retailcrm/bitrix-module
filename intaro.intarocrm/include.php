<?php
CModule::AddAutoloadClasses(
    'intaro.intarocrm', // module name
    array (
        'IntaroCrm\RestApi'                 => 'classes/general/RestApi.php',
        'ICrmOrderActions'                  => 'classes/general/ICrmOrderActions.php',
        'ICMLLoader'                        => 'classes/general/ICMLLoader.php',
        'ICrmOrderEvent'                    => 'classes/general/events/ICrmOrderEvent.php',
        'IntaroCrm\Exception\ApiException'  => 'classes/general/Exception/ApiException.php',
        'IntaroCrm\Exception\CurlException' => 'classes/general/Exception/CurlException.php'
    )
);