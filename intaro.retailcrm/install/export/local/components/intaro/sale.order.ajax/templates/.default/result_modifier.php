<?php

use Bitrix\Main\Loader;
use Intaro\RetailCrm\Component\ConfigProvider;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

/**
 * @var array $arParams
 * @var array $arResult
 * @var SaleOrderAjax $component
 */

Loader::includeModule('intaro.retailcrm');

$arResult['LOYALTY_STATUS'] = ConfigProvider::getLoyaltyProgramStatus();
$arResult['AVAILABLE_BONUSES'] = 300;
$arResult['TOTAL_BONUSES_COUNT'] = 600;
$component = $this->__component;
$component::scaleImages($arResult['JS_DATA'], $arParams['SERVICES_IMAGES_SCALING']);
