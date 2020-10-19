<?php

use Bitrix\Currency\CurrencyLangTable;
use Bitrix\Main\Loader;
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

Loader::includeModule('intaro.retailcrm');

$arResult['LOYALTY_STATUS'] = ConfigProvider::getLoyaltyProgramStatus();

//TODO Закомментированно до появления реального апи
//TODO добавить проверку на участие покупателя в программе лояльности (таска 68813)
/*$service = new LoyaltyService();
$calculate = $service->calculateBonus($arResult['BASKET_ITEMS'], $arResult['DISCOUNT_PRICE'], $arResult['DISCOUNT_PERCENT']);

if ($calculate->success) {
    $arResult['AVAILABLE_BONUSES'] = $calculate->order->bonusesChargeTotal;
    $arResult['TOTAL_BONUSES_COUNT'] = $calculate->order->loyaltyAccount->amount;
    $arResult['LP_CALCULATE_SUCCESS'] = $calculate->success;
    $arResult['WILL_BE_CREDITED'] = $calculate->order->bonusesCreditTotal;
}*/
//TODO убрать заглушку после появления реальных методов
$arResult['LP_CALCULATE_SUCCESS'] = true;
$arResult['AVAILABLE_BONUSES']    = 300; //доступно
$arResult['TOTAL_BONUSES_COUNT']  = 600; //всего на щету
$arResult['WILL_BE_CREDITED']     = 245; //будет начислено
$component                        = $this->__component;
$component::scaleImages($arResult['JS_DATA'], $arParams['SERVICES_IMAGES_SCALING']);

$currency = CurrencyLangTable::query()
    ->setSelect(['FORMAT_STRING'])
    ->where([
        ['CURRENCY', '=', RetailcrmConfigProvider::getCurrencyOrDefault()],
        ['LID', '=', 'LANGUAGE_ID'],
    ])
    ->fetch();

$arResult['BONUS_CURRENCY'] = $currency['FORMAT_STRING'];
