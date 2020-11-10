<?php

use Bitrix\Main\LoaderException;
use Intaro\RetailCrm\Component\ConfigProvider;
use Intaro\RetailCrm\Component\ServiceLocator;
use Intaro\RetailCrm\Repository\UserRepository;
use Intaro\RetailCrm\Service\LoyaltyService;
use Bitrix\Main\Loader;

global $USER;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true || !$USER->IsAuthorized()) {
    die();
}

try {
    Loader::includeModule('intaro.retailcrm');
} catch (LoaderException $e) {
    die();
}

$arResult['LOYALTY_STATUS']          = ConfigProvider::getLoyaltyProgramStatus();
$arResult['PERSONAL_LOYALTY_STATUS'] = LoyaltyService::getLoyaltyPersonalStatus();

$customer = UserRepository::getById($USER->GetID());

if ($arResult['LOYALTY_STATUS'] === 'Y'
    && $arResult['PERSONAL_LOYALTY_STATUS'] === true
    && $customer->getLoyalty()->getIdInLoyalty() > 0
) {
    $customer = UserRepository::getById($USER->GetID());
    
    
    if ($arResult['LOYALTY_STATUS'] === 'Y' && $USER->IsAuthorized()) {
        /* @var LoyaltyService $service*/
        $service    = ServiceLocator::get(LoyaltyService::class);
        $service->getLoyaltyAccounts($customer->getLoyalty()->getIdInLoyalty());
    }
    
    $this->IncludeComponentTemplate();
} else {
    include_once __DIR__ . 'register.php';
}

