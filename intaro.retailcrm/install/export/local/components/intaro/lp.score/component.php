<?php

use Bitrix\Main\Localization\Loc;
use Intaro\RetailCrm\Service\Exception\LpAccountsUnavailableException;
use Intaro\RetailCrm\Component\ConfigProvider;
use Intaro\RetailCrm\Component\ServiceLocator;
use Intaro\RetailCrm\Repository\UserRepository;
use Intaro\RetailCrm\Service\LoyaltyService;
use Bitrix\Main\Loader;
use Intaro\RetailCrm\Service\LoyaltyAccountService;

global $USER;

Loc::loadMessages(__FILE__);

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true || !$USER->IsAuthorized()) {
    die(GetMessage('NOT_AUTHORIZED'));
}

try {
    if (!Loader::includeModule('intaro.retailcrm')) {
        die(GetMessage('MODULE_NOT_INSTALL'));
    }
} catch (Throwable $exception) {
    die(GetMessage('MODULE_NOT_INSTALL') . ': ' . $exception->getMessage());
}

require_once $_SERVER['DOCUMENT_ROOT']
    . '/bitrix/modules/intaro.retailcrm/lib/service/exception/lpaccountsavailableexception.php';

try {
    $arResult['LOYALTY_STATUS']          = ConfigProvider::getLoyaltyProgramStatus();
    $arResult['PERSONAL_LOYALTY_STATUS'] = LoyaltyAccountService::getLoyaltyPersonalStatus();

    $customer = UserRepository::getById($USER->GetID());

    if (
        $arResult['LOYALTY_STATUS'] === 'Y'
        && null !== $customer->getLoyalty()
        && $customer->getLoyalty()->getIdInLoyalty() > 0
    ) {
        /* @var LoyaltyService $service */
        $service = ServiceLocator::get(LoyaltyService::class);
        $response = $service->getLoyaltyAccounts($customer->getLoyalty()->getIdInLoyalty());

        if ($response !== null) {
            $arResult['BONUS_COUNT'] = $response->amount;
            $arResult['ACTIVE_STATUS'] = $response->status;
            $arResult['CARD'] = $response->cardNumber !== ''
                ? $response->cardNumber
                : GetMessage('CARD_NOT_LINKED');
            $arResult['PHONE'] = $response->phoneNumber;
            $arResult['REGISTER_DATE'] = $response->createdAt->format('Y-m-d');
            $arResult['LOYALTY_LEVEL_NAME'] = $response->loyaltyLevel->name;
            $arResult['LOYALTY_LEVEL_ID'] = $response->id;
            $arResult['LL_PRIVILEGE_SIZE'] = $response->loyaltyLevel->privilegeSize;
            $arResult['LL_PRIVILEGE_SIZE_PROMO'] = $response->loyaltyLevel->privilegeSizePromo;
            $arResult['LOYALTY_LEVEL_TYPE'] = $response->loyaltyLevel->type;
            $arResult['NEXT_LEVEL_SUM'] = (int)$response->nextLevelSum === 0
                ? GetMessage('TOP_LEVEL')
                : (int) $response->nextLevelSum;
            $arResult['ORDERS_SUM'] = (int)$response->ordersSum;

            if (is_int($arResult['NEXT_LEVEL_SUM']) && is_int($arResult['ORDERS_SUM'])) {
                $arResult['REMAINING_SUM'] = $arResult['NEXT_LEVEL_SUM'] - $arResult['ORDERS_SUM'];
            }
        }

        $this->IncludeComponentTemplate();
    } else {
        require_once __DIR__ . '/register.php';
    }
} catch (LpAccountsUnavailableException $exception) {
    $arResult['ERRORS'] = GetMessage('LP_NOT_ACTUAL');

    $this->IncludeComponentTemplate();
} catch (Throwable $exception) {
    die(GetMessage('MODULE_NOT_INSTALL') . ': ' . $exception->getMessage());
}
