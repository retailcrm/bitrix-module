<?php
/**
 * Bitrix vars
 * @var  array                     $arResult
 * @global CUser                   $USER
 * @global CMain                   $APPLICATION
 */

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}
?>

<p>
    <? if (isset($arResult['BONUS_COUNT'])) {?>
        <b>Бонусов на счете:</b> <?= $arResult['BONUS_COUNT']?><br>
    <?php }?>
    
    <? if (isset($arResult['ACTIVE'])) {?>
        <b>Активность аккаунта:</b> <?= $arResult['ACTIVE']?><br>
    <?php }?>
    
    <? if (isset($arResult['CARD'])) {?>
        <b>Номер бонусной карты:</b> <?= $arResult['CARD']?><br>
    <?php }?>
    
    <? if (isset($arResult['PHONE'])) {?>
        <b>Привязанный телефон:</b> <?= $arResult['PHONE']?><br>
    <?php }?>
    
    <? if (isset($arResult['REGISTER_DATE'])) {?>
        <b>Дата регистрации:</b> <?= $arResult['REGISTER_DATE']?><br>
    <?php }?>
</p>