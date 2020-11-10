<?php

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Intaro\RetailCrm\Component\ConfigProvider;
use Intaro\RetailCrm\Repository\UserRepository;
use Intaro\RetailCrm\Service\LoyaltyService;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

try {
    Loader::includeModule('intaro.retailcrm');
} catch (LoaderException $e) { ?>
    Модуль intaro.retailcrm  не установлен
    <?php
    die();
}

$APPLICATION->SetTitle("Бонусный счет");
?>

<?php
$arResult['LOYALTY_STATUS']          = ConfigProvider::getLoyaltyProgramStatus();
$arResult['PERSONAL_LOYALTY_STATUS'] = LoyaltyService::getLoyaltyPersonalStatus();

global $USER;
$customer = UserRepository::getById($USER->GetID());

if ($arResult['LOYALTY_STATUS'] === 'Y'
    && $arResult['PERSONAL_LOYALTY_STATUS'] === true
    && $customer->getLoyalty()->getIdInLoyalty() > 0
) { ?>
    
    <?php $APPLICATION->IncludeComponent(
        "intaro:lp.score",
        "",
        []
    ); ?>

<?php } else {
    ?>
    <?php $APPLICATION->IncludeComponent(
        "intaro:lp.register",
        "",
        []
    ); ?>
<?php } ?>

<?php require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>