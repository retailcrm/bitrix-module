<?php

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Intaro\RetailCrm\Component\ConfigProvider;
use Intaro\RetailCrm\Component\ServiceLocator;
use Intaro\RetailCrm\Model\Api\Response\Loyalty\LoyaltyCalculateResponse;
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
    /* @var LoyaltyService $service */
    $service = ServiceLocator::get(LoyaltyService::class);
    
    /** @var LoyaltyCalculateResponse $calculate */
    $calculate = $service->calculateBonus($arResult['BASKET_ITEMS']);

    if ($calculate instanceof LoyaltyCalculateResponse && $calculate->success) {
        $arResult = $service->calculateOrderBasket($arResult, $calculate);
    }
    
    $arResult['JS_MESS'] = json_encode([
        'COUNT_FOR_WRITE_OFF' => GetMessage('COUNT_FOR_WRITE_OFF'),
        'DATA_PROCESSING'     => GetMessage('DATA_PROCESSING'),
        'YOU_CANT_SPEND_MORE' => GetMessage('YOU_CANT_SPEND_MORE'),
        'BONUSES'             => GetMessage('BONUSES'),
    ]);
}

$component = $this->__component;

$component::scaleImages($arResult['JS_DATA'], $arParams['SERVICES_IMAGES_SCALING']);
