<?php
$server = \Bitrix\Main\Context::getCurrent()->getServer()->getDocumentRoot();

CModule::AddAutoloadClasses(
    'intaro.retailcrm', // module name
    array (
        'RestNormalizer'                            => file_exists($server . '/bitrix/php_interface/retailcrm/RestNormalizer.php') ? '../../php_interface/retailcrm/RestNormalizer.php' : 'classes/general/RestNormalizer.php',
        'Logger'                                    => file_exists($server . '/bitrix/php_interface/retailcrm/Logger.php') ? '../../php_interface/retailcrm/Logger.php' : 'classes/general/Logger.php',
        'RetailCrm\ApiClient'                       => file_exists($server . '/bitrix/php_interface/retailcrm/ApiClient.php') ? '../../php_interface/retailcrm/ApiClient.php' : 'classes/general/ApiClient.php',
        'RetailCrm\Http\Client'                     => file_exists($server . '/bitrix/php_interface/retailcrm/Client.php') ? '../../php_interface/retailcrm/Client.php' : 'classes/general/Http/Client.php',
        'RCrmActions'                               => file_exists($server . '/bitrix/php_interface/retailcrm/RCrmActions.php') ? '../../php_interface/retailcrm/RCrmActions.php' : 'classes/general/RCrmActions.php',
        'RetailCrmUser'                             => file_exists($server . '/bitrix/php_interface/retailcrm/RetailCrmUser.php') ? '../../php_interface/retailcrm/RetailCrmUser.php' : 'classes/general/user/RetailCrmUser.php',
        'RetailCrmOrder'                            => file_exists($server . '/bitrix/php_interface/retailcrm/RetailCrmOrder.php') ? '../../php_interface/retailcrm/RetailCrmOrder.php' : 'classes/general/order/RetailCrmOrder.php',
        'RetailCrmHistory'                          => file_exists($server . '/bitrix/php_interface/retailcrm/RetailCrmHistory.php') ? '../../php_interface/retailcrm/RetailCrmHistory.php' : 'classes/general/history/RetailCrmHistory.php',
        'RetailCrmICML'                             => file_exists($server . '/bitrix/php_interface/retailcrm/RetailCrmICML.php') ? '../../php_interface/retailcrm/RetailCrmICML.php' : 'classes/general/icml/RetailCrmICML.php',
        'RetailCrmEvent'                            => file_exists($server . '/bitrix/php_interface/retailcrm/RetailCrmEvent.php') ? '../../php_interface/retailcrm/RetailCrmEvent.php' : 'classes/general/events/RetailCrmEvent.php',
        'RetailCrm\Response\ApiResponse'            => 'classes/general/Response/ApiResponse.php',
        'RetailCrm\Exception\InvalidJsonException'  => 'classes/general/Exception/InvalidJsonException.php',
        'RetailCrm\Exception\CurlException'         => 'classes/general/Exception/CurlException.php',
    )
);