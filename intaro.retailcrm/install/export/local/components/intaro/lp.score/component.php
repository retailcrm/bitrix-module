<?php

use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Intaro\RetailCrm\Component\ConfigProvider;
use Intaro\RetailCrm\Component\ServiceLocator;
use Intaro\RetailCrm\Repository\UserRepository;
use Intaro\RetailCrm\Service\LoyaltyService;
use Bitrix\Main\Loader;

global $USER;

Loc::loadMessages(__FILE__);

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true || !$USER->IsAuthorized()) {
    die(GetMessage('NOT_AUTHORIZED'));
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
    && $customer->getLoyalty()->getIdInLoyalty() > 0
) {
    /* @var LoyaltyService $service */
    $service  = ServiceLocator::get(LoyaltyService::class);
    $response = $service->getLoyaltyAccounts($customer->getLoyalty()->getIdInLoyalty());
    
    if ($response !== null) {
        $arResult['BONUS_COUNT']   = $response->amount;
        $arResult['ACTIVE']        = $response->active ? GetMessage('YES') : GetMessage('NO');
        $arResult['CARD']          = $response->cardNumber !== '' ? $response->cardNumber : GetMessage('CARD_NOT_LINKED');
        $arResult['PHONE']         = $response->phoneNumber;
        $arResult['REGISTER_DATE'] = $response->createdAt->format('Y-m-d');
    }
    
    $this->IncludeComponentTemplate();
} else {
    require_once __DIR__ . '/register.php';
}
