<?php

use Bitrix\Currency\CurrencyLangTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Intaro\RetailCrm\Component\ConfigProvider;
use Intaro\RetailCrm\Service\LoyaltyService;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * @var array         $arParams
 * @var array         $arResult
 * @var SaleOrderAjax $component
 */


try {
    Loader::includeModule('intaro.retailcrm');
} catch (LoaderException $exception) {
    AddMessage2Log($exception->getMessage());
}

$arResult['LOYALTY_STATUS']          = ConfigProvider::getLoyaltyProgramStatus();
$arResult['PERSONAL_LOYALTY_STATUS'] = LoyaltyService::getLoyaltyPersonalStatus();

if ($arResult['LOYALTY_STATUS'] === 'Y' && $arResult['PERSONAL_LOYALTY_STATUS'] === true) {
    $service   = new LoyaltyService();
    $calculate = $service->calculateBonus($arResult['BASKET_ITEMS'], $arResult['DISCOUNT_PRICE'], $arResult['DISCOUNT_PERCENT']);

    if ($calculate->success) {
        $arResult['AVAILABLE_BONUSES']    = $calculate->order->bonusesChargeTotal;
        $arResult['TOTAL_BONUSES_COUNT']  = $calculate->order->loyaltyAccount->amount;
        $arResult['LP_CALCULATE_SUCCESS'] = $calculate->success;
        $arResult['WILL_BE_CREDITED']     = $calculate->order->bonusesCreditTotal;
    }

    $component = $this->__component;
    
    $component::scaleImages($arResult['JS_DATA'], $arParams['SERVICES_IMAGES_SCALING']);
    
    try {
        $currency = CurrencyLangTable::query()
            ->setSelect(['FORMAT_STRING'])
            ->where([
                ['CURRENCY', '=', RetailcrmConfigProvider::getCurrencyOrDefault()],
                ['LID', '=', 'LANGUAGE_ID'],
            ])
            ->fetch();
    } catch (ObjectPropertyException | ArgumentException | SystemException $exception) {
        AddMessage2Log($exception->getMessage());
    }
    
    $arResult['BONUS_CURRENCY'] = $currency['FORMAT_STRING'];
}
