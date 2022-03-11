<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */

/** @var array $arResult */

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Intaro\RetailCrm\Component\ConfigProvider;
use Intaro\RetailCrm\Component\Constants;
use Intaro\RetailCrm\Component\ServiceLocator;
use Intaro\RetailCrm\Model\Api\Response\Loyalty\LoyaltyCalculateResponse;
use Intaro\RetailCrm\Service\LoyaltyService;
use Intaro\RetailCrm\Service\LoyaltyAccountService;
use RetailCrm\Exception\CurlException;

/** RetailCRM loyalty program  start*/
function checkLoadIntaro(): bool
{
    try {
        return Loader::includeModule('intaro.retailcrm');
    } catch (LoaderException $e) {
        return false;
    }
}

if (checkLoadIntaro()) {
    $arResult['LOYALTY_STATUS'] = ConfigProvider::getLoyaltyProgramStatus();
    $arResult['PERSONAL_LOYALTY_STATUS'] = LoyaltyAccountService::getLoyaltyPersonalStatus();

    /** @var LoyaltyService $service */
    $service = ServiceLocator::get(LoyaltyService::class);

    try {
        if ($arResult['LOYALTY_STATUS'] === 'Y' && $arResult['PERSONAL_LOYALTY_STATUS'] === true) {
            $calculate = $service->getLoyaltyCalculate($arResult['BASKET_ITEM_RENDER_DATA']);

            if (
                $calculate instanceof LoyaltyCalculateResponse
                && $calculate->success
                && null !== $calculate->order->loyaltyAccount
            ) {
                $arResult = $service->addLoyaltyToBasket($arResult, $calculate);
            }
        }
    } catch (CurlException $exception) {
        Logger::getInstance()->write($exception->getMessage(), Constants::TEMPLATES_ERROR);

        $arResult['LOYALTY_ERROR_MESSAGE'] = GetMessage('LOYALTY_CONNECTION_ERROR');
    }
} else {
    AddMessage2Log(GetMessage('INTARO_NOT_INSTALLED'));
}
/** RetailCRM loyalty program end */

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
