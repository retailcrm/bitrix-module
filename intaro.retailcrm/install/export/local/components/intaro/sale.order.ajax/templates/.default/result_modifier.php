<? use Intaro\RetailCrm\Component\ConfigProvider;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

/**
 * @var array $arParams
 * @var array $arResult
 * @var SaleOrderAjax $component
 */

$arResult['LOYALTY_STATUS'] = ConfigProvider::getLoyaltyProgramStatus();
$arResult['AVAILABLE_BONUSES'] = 300;
$component = $this->__component;
$component::scaleImages($arResult['JS_DATA'], $arParams['SERVICES_IMAGES_SCALING']);