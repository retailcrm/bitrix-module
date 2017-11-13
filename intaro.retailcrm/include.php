<?php
$server = \Bitrix\Main\Context::getCurrent()->getServer()->getDocumentRoot();
$version = COption::GetOptionString('intaro.retailcrm', 'api_version');

CModule::AddAutoloadClasses(
    'intaro.retailcrm', // module name
    array (
        'RestNormalizer'                            => file_exists($server . '/bitrix/php_interface/retailcrm/RestNormalizer.php') ? '../../php_interface/retailcrm/RestNormalizer.php' : 'classes/general/RestNormalizer.php',
        'Logger'                                    => file_exists($server . '/bitrix/php_interface/retailcrm/Logger.php') ? '../../php_interface/retailcrm/Logger.php' : 'classes/general/Logger.php',
        'RetailCrm\ApiClient'                       => file_exists($server . '/bitrix/php_interface/retailcrm/ApiClient.php') ? '../../php_interface/retailcrm/ApiClient.php' : 'classes/general/ApiClient_' . $version . '.php',
        'RetailCrm\Http\Client'                     => file_exists($server . '/bitrix/php_interface/retailcrm/Client.php') ? '../../php_interface/retailcrm/Client.php' : 'classes/general/Http/Client.php',
        'RCrmActions'                               => file_exists($server . '/bitrix/php_interface/retailcrm/RCrmActions.php') ? '../../php_interface/retailcrm/RCrmActions.php' : 'classes/general/RCrmActions.php',
        'RetailCrmUser'                             => file_exists($server . '/bitrix/php_interface/retailcrm/RetailCrmUser.php') ? '../../php_interface/retailcrm/RetailCrmUser.php' : 'classes/general/user/RetailCrmUser.php',
        'RetailCrmOrder'                            => file_exists($server . '/bitrix/php_interface/retailcrm/RetailCrmOrder.php') ? '../../php_interface/retailcrm/RetailCrmOrder.php' : 'classes/general/order/RetailCrmOrder_' . $version . '.php',
        'RetailCrmHistory'                          => file_exists($server . '/bitrix/php_interface/retailcrm/RetailCrmHistory.php') ? '../../php_interface/retailcrm/RetailCrmHistory.php' : 'classes/general/history/RetailCrmHistory_' . $version . '.php',
        'RetailCrmICML'                             => file_exists($server . '/bitrix/php_interface/retailcrm/RetailCrmICML.php') ? '../../php_interface/retailcrm/RetailCrmICML.php' : 'classes/general/icml/RetailCrmICML.php',
        'RetailCrmInventories'                      => file_exists($server . '/bitrix/php_interface/retailcrm/RetailCrmInventories.php') ? '../../php_interface/retailcrm/RetailCrmInventories.php' : 'classes/general/inventories/RetailCrmInventories.php',
        'RetailCrmPrices'                           => file_exists($server . '/bitrix/php_interface/retailcrm/RetailCrmPrices.php') ? '../../php_interface/retailcrm/RetailCrmPrices.php' : 'classes/general/prices/RetailCrmPrices.php',
        'RetailCrmCollector'                        => file_exists($server . '/bitrix/php_interface/retailcrm/RetailCrmCollector.php') ? '../../php_interface/retailcrm/RetailCrmCollector.php' : 'classes/general/collector/RetailCrmCollector.php',
        'RetailCrmUa'                               => file_exists($server . '/bitrix/php_interface/retailcrm/RetailCrmUa.php') ? '../../php_interface/retailcrm/RetailCrmUa.php' : 'classes/general/ua/RetailCrmUa.php',
        'RetailCrmEvent'                            => file_exists($server . '/bitrix/php_interface/retailcrm/RetailCrmEvent.php') ? '../../php_interface/retailcrm/RetailCrmEvent.php' : 'classes/general/events/RetailCrmEvent.php',
        'RetailCrm\Response\ApiResponse'            => 'classes/general/Response/ApiResponse.php',
        'RetailCrm\Exception\InvalidJsonException'  => 'classes/general/Exception/InvalidJsonException.php',
        'RetailCrm\Exception\CurlException'         => 'classes/general/Exception/CurlException.php',
    )
);