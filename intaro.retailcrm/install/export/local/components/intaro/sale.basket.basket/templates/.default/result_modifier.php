<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */

/** @var array $arResult */

use Bitrix\Main;
use Bitrix\Main\Config\Option;
use Intaro\RetailCrm\Component\ConfigProvider;
use Intaro\RetailCrm\Component\ServiceLocator;
use Intaro\RetailCrm\Service\LoyaltyService;

try {
    Main\Loader::includeModule('intaro.retailcrm');
} catch (Main\LoaderException $exception) {
    AddMessage2Log($exception->getMessage());
}

$contragentsTypes = ConfigProvider::getContragentTypes();
$key              = array_search('individual', $contragentsTypes, true);

$defaultParams = [
    'TEMPLATE_THEME' => 'blue',
];
$arParams      = array_merge($defaultParams, $arParams);
unset($defaultParams);

$arParams['TEMPLATE_THEME'] = (string)($arParams['TEMPLATE_THEME']);

if ('' !== $arParams['TEMPLATE_THEME']) {
    $arParams['TEMPLATE_THEME'] = preg_replace('/[^a-zA-Z0-9_\-\(\)\!]/', '', $arParams['TEMPLATE_THEME']);
    
    if ('site' === $arParams['TEMPLATE_THEME']) {
        $templateId                 = (string)Option::get('main', 'wizard_template_id', 'eshop_bootstrap', SITE_ID);
        $templateId                 = (0 === strpos($templateId, "eshop_adapt")) ? 'eshop_adapt' : $templateId;
        $arParams['TEMPLATE_THEME'] = (string)Option::get('main', 'wizard_' . $templateId . '_theme_id', 'blue', SITE_ID);
    }
    
    $cssFile = $_SERVER['DOCUMENT_ROOT'] . $this->GetFolder() . '/themes/' . $arParams['TEMPLATE_THEME'] . '/style.css';
    
    if (('' !== $arParams['TEMPLATE_THEME'])
        && !is_file($cssFile)
    ) {
        $arParams['TEMPLATE_THEME'] = '';
    }
}

if ('' === $arParams['TEMPLATE_THEME']) {
    $arParams['TEMPLATE_THEME'] = 'blue';
}


$arResult['LOYALTY_STATUS']          = ConfigProvider::getLoyaltyProgramStatus();
$arResult['PERSONAL_LOYALTY_STATUS'] = LoyaltyService::getLoyaltyPersonalStatus();

/** @var LoyaltyService $service */
$service = ServiceLocator::get(LoyaltyService::class);

if ($arResult['LOYALTY_STATUS'] === 'Y'
    && $arResult['PERSONAL_LOYALTY_STATUS'] === true
) {
    //TODO есть проблемы с округлением. нужно изучить, можно ли не передавать $discountPercent
    $discountPercent = round($arResult['DISCOUNT_PRICE_ALL'] / ($arResult['allSum'] / 100), 0);
    $calculate       = $service->calculateBonus( $arResult['BASKET_ITEM_RENDER_DATA']);
    
    if ($calculate->success) {
        $arResult['LP_CALCULATE_SUCCESS']                  = $calculate->success;
        $arResult['TOTAL_RENDER_DATA']['WILL_BE_CREDITED'] = $calculate->order->bonusesCreditTotal;
    }
    
    foreach ($arResult['BASKET_ITEM_RENDER_DATA'] as $key => &$item) {
        $item['WILL_BE_CREDITED_BONUS'] = $calculate->order->items[$key]->bonusesCreditTotal;
    }
}
