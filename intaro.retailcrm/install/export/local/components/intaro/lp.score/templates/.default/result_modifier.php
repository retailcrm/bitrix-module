<?php

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Intaro\RetailCrm\Component\ConfigProvider;
use Intaro\RetailCrm\Component\ServiceLocator;
use Intaro\RetailCrm\Repository\AgreementRepository;
use Intaro\RetailCrm\Repository\UserRepository;
use Intaro\RetailCrm\Service\LoyaltyService;

try {
    Loader::includeModule('intaro.retailcrm');
} catch (LoaderException $exception) {
    AddMessage2Log($exception->getMessage());
}

$arResult['LOYALTY_STATUS'] = ConfigProvider::getLoyaltyProgramStatus();

global $USER;

$customer = UserRepository::getById($USER->GetID());


if ($arResult['LOYALTY_STATUS'] === 'Y' && $USER->IsAuthorized()) {
    /* @var LoyaltyService $service*/
    $service    = ServiceLocator::get(LoyaltyService::class);
$service->getLoyaltyAccounts($customer->getLoyalty()->getIdInLoyalty());
}
