<?php

use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Intaro\RetailCrm\Component\ServiceLocator;
use Intaro\RetailCrm\Service\CollectorCookieExtractor;
use Intaro\RetailCrm\Service\LoyaltyService;
use Intaro\RetailCrm\Service\LpUserAccountService;
use Intaro\RetailCrm\Service\CustomerService;
use Intaro\RetailCrm\Vendor\Doctrine\Common\Annotations\AnnotationReader;
use Intaro\RetailCrm\Vendor\Doctrine\Common\Annotations\AnnotationRegistry;

require_once __DIR__ . '/RetailcrmClasspathBuilder.php';

$retailcrmModuleId = 'intaro.retailcrm';
$server = Context::getCurrent()->getServer()->getDocumentRoot();
$version = COption::GetOptionString('intaro.retailcrm', 'api_version');

$builder = new RetailcrmClasspathBuilder();
$builder->setDisableNamespaces(true)
    ->setDocumentRoot($server)
    ->setModuleId($retailcrmModuleId)
    ->setPath('classes')
    ->setVersion($version)
    ->build();

Loader::switchAutoLoad(true);
Loader::registerAutoLoadClasses('intaro.retailcrm', $builder->getResult());
AnnotationRegistry::registerLoader('class_exists');

ServiceLocator::registerServices([
    \Intaro\RetailCrm\Service\Utils::class,
    Logger::class,
    AnnotationReader::class,
    CollectorCookieExtractor::class,
    LpUserAccountService::class,
    LoyaltyService::class,
    CustomerService::class
]);

$arJsConfig = [
    'intaro_countdown' => [
        'js'  => '/bitrix/js/intaro/sms.js',
        'rel' => [],
    ],
];

foreach ($arJsConfig as $ext => $arExt) {
    CJSCore::RegisterExt($ext, $arExt);
}
