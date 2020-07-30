<?php

use Intaro\RetailCrm\Component\ServiceLocator;
use Intaro\RetailCrm\Component\CollectorCookieExtractor;
use Intaro\RetailCrm\Vendor\Doctrine\Common\Annotations\AnnotationReader;
use Intaro\RetailCrm\Vendor\Doctrine\Common\Annotations\AnnotationRegistry;

require_once __DIR__ . '/RetailcrmClasspathBuilder.php';

$retailcrmModuleId = 'intaro.retailcrm';
$server = \Bitrix\Main\Context::getCurrent()->getServer()->getDocumentRoot();
$version = COption::GetOptionString('intaro.retailcrm', 'api_version');

$builder = new RetailcrmClasspathBuilder();
$builder->setDisableNamespaces(true)
    ->setDocumentRoot($server)
    ->setModuleId($retailcrmModuleId)
    ->setPath('classes')
    ->setVersion($version)
    ->build();

\Bitrix\Main\Loader::switchAutoLoad(true);
\Bitrix\Main\Loader::registerAutoLoadClasses('intaro.retailcrm', $builder->getResult());
AnnotationRegistry::registerLoader('class_exists');

ServiceLocator::registerServices([
    Logger::class,
    AnnotationReader::class,
    CollectorCookieExtractor::class
]);
