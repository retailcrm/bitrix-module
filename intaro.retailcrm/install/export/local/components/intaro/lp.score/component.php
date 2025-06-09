<?php

use Bitrix\Main\Localization\Loc;
use Intaro\RetailCrm\Model\Api\LoyaltyBonusOperations;
use Intaro\RetailCrm\Service\Exception\LpAccountsUnavailableException;
use Intaro\RetailCrm\Component\ConfigProvider;
use Intaro\RetailCrm\Component\ServiceLocator;
use Intaro\RetailCrm\Repository\UserRepository;
use Intaro\RetailCrm\Service\LoyaltyService;
use Bitrix\Main\Loader;
use Intaro\RetailCrm\Service\LoyaltyAccountService;
use RetailCrm\Exception\CurlException;

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
    $arResult['LOYALTY_STATUS'] = ConfigProvider::getLoyaltyProgramStatus();
    $arResult['PERSONAL_LOYALTY_STATUS'] = LoyaltyAccountService::getLoyaltyPersonalStatus();

    $customer = UserRepository::getById($USER->GetID());
    $loyaltyAccountId = $customer->getLoyalty()->getIdInLoyalty();

    if (
        $arResult['LOYALTY_STATUS'] === 'Y'
        && null !== $customer->getLoyalty()
        && $customer->getLoyalty()->getIdInLoyalty() > 0
    ) {
        /* @var LoyaltyService $service */
        $service = ServiceLocator::get(LoyaltyService::class);
        $loyaltyAccount = $service->getLoyaltyAccounts($loyaltyAccountId);
        $loyaltyBonusesDetails = $service->getLoyaltyBonusesDetails($loyaltyAccountId);
        $loyaltyAccountOperations = $service->getLoyaltyAccountOperations($loyaltyAccountId);

        if ($loyaltyAccount !== null) {
            $arResult['BONUS_COUNT'] = $loyaltyAccount->amount;
            $arResult['LOYALTY_LEVEL_NAME'] = $loyaltyAccount->loyaltyLevel->name;
            $arResult['LL_PRIVILEGE_SIZE'] = $loyaltyAccount->loyaltyLevel->privilegeSize;
            $arResult['LL_PRIVILEGE_SIZE_PROMO'] = $loyaltyAccount->loyaltyLevel->privilegeSizePromo;
            $arResult['LOYALTY_LEVEL_TYPE'] = $loyaltyAccount->loyaltyLevel->type;
            $arResult['NEXT_LEVEL_SUM'] = (int) $loyaltyAccount->nextLevelSum === 0
                ? GetMessage('TOP_LEVEL')
                : (int) $loyaltyAccount->nextLevelSum;
            $arResult['ORDERS_SUM'] = (int)$loyaltyAccount->ordersSum;

            if (is_int($arResult['NEXT_LEVEL_SUM']) && is_int($arResult['ORDERS_SUM'])) {
                $arResult['REMAINING_SUM'] = $arResult['NEXT_LEVEL_SUM'] - $arResult['ORDERS_SUM'];
            }

            $arResult['LOYALTY_ACCOUNT_OPERATIONS'] = $loyaltyAccountOperations;
            $arResult['BONUS_PENDING'] = $loyaltyBonusesDetails['waiting_activation'] ?: [];
            $arResult['BONUS_WILL_EXPIRE'] = $loyaltyBonusesDetails['burn_soon'] ?: [];
        }

        $this->IncludeComponentTemplate();
    } else {
        require_once __DIR__ . '/register.php';
    }
} catch (LpAccountsUnavailableException $exception) {
    $arResult['ERRORS'] = GetMessage('LP_NOT_ACTUAL');

    $this->IncludeComponentTemplate();
} catch (CurlException $exception) {
    $arResult['ERRORS'] = GetMessage('LOYALTY_CONNECTION_ERROR');

    $this->IncludeComponentTemplate();
} catch (Throwable $exception) {
    die(GetMessage('MODULE_NOT_INSTALL') . ': ' . $exception->getMessage());
}
