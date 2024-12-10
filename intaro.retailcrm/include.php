<?php

use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Intaro\RetailCrm\Component\ConfigProvider;
use Intaro\RetailCrm\Component\Factory\ClientFactory;
use Intaro\RetailCrm\Component\ServiceLocator;
use Intaro\RetailCrm\Service\CookieService;
use Intaro\RetailCrm\Service\OrderLoyaltyDataService;
use Intaro\RetailCrm\Service\LoyaltyService;
use Intaro\RetailCrm\Service\LoyaltyAccountService;
use Intaro\RetailCrm\Service\CustomerService;
use Intaro\RetailCrm\Vendor\Doctrine\Common\Annotations\AnnotationReader;
use Intaro\RetailCrm\Vendor\Doctrine\Common\Annotations\AnnotationRegistry;
use \Intaro\RetailCrm\Component\Builder\Api\CustomerBuilder;
use RetailCrm\Exception\CurlException;

require_once __DIR__ . '/RetailcrmClasspathBuilder.php';

$retailcrmModuleId = 'intaro.retailcrm';
$server = Context::getCurrent()->getServer()->getDocumentRoot();
$version = COption::GetOptionString('intaro.retailcrm', 'api_version');

$builder = new RetailcrmClasspathBuilder();
$builder->setDisableNamespaces(true)
    ->setDocumentRoot($server)
    ->setModuleId($retailcrmModuleId)
    ->setDirectories(['classes', 'lib/icml'])
    ->setVersion($version)
    ->build();

Loader::registerAutoLoadClasses('intaro.retailcrm', $builder->getResult());
AnnotationRegistry::registerLoader('class_exists');

ServiceLocator::registerServices([
    \Intaro\RetailCrm\Service\Utils::class,
    Logger::class,
    AnnotationReader::class,
    CookieService::class,
    LoyaltyAccountService::class,
    LoyaltyService::class,
    CustomerService::class,
    OrderLoyaltyDataService::class,
    CustomerBuilder::class
]);

$arJsConfig = [
    'intaro_countdown' => [
        'js'  => '/bitrix/js/intaro/sms.js',
        'rel' => [],
    ],
    'intaro_custom_props' => [
        'js'  => '/bitrix/js/intaro/export/custom-props-export.js',
        'rel' => [],
    ],
];

foreach ($arJsConfig as $ext => $arExt) {
    CJSCore::RegisterExt($ext, $arExt);
}

if (empty(ConfigProvider::getSitesAvailable())) {
    $client = ClientFactory::createClientAdapter();
    try {
        $credentials = $client->getCredentials();

        ConfigProvider::setSitesAvailable($credentials->sitesAvailable[0] ?? '');
    } catch (ArgumentOutOfRangeException | CurlException $exception) {
        Logger::getInstance()->write($exception->getMessage());
    }
}
