<?php

/**
 * Bitrix vars
 *
 * @var  array   $arResult
 * @global CUser $USER
 * @global CMain $APPLICATION
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
?>
<p>
    <?php if (isset($arResult['ERRORS'])) { ?>
        <b><?=GetMessage('ERRORS')?></b> <?=$arResult['ERRORS']?><br>
    <?php } ?>
    <?php if (isset($arResult['LOYALTY_LEVEL_ID'])) { ?>
        <b><?=GetMessage('LOYALTY_LEVEL_ID')?></b> <?=$arResult['LOYALTY_LEVEL_ID']?><br>
    <?php } ?>
    <?php if (isset($arResult['ACTIVE'])) { ?>
        <b><?=GetMessage('ACTIVE')?></b> <?=$arResult['ACTIVE'] ? GetMessage('YES') : GetMessage('NO')?>
        <?php if ($arResult['ACTIVE'] === false) { ?>
            <a href="/lp-register"> Активировать</a>
        <?php } ?>
        <br>
    <?php } ?>
    <?php if (isset($arResult['LOYALTY_LEVEL_NAME'])) { ?>
        <b><?=GetMessage('LOYALTY_LEVEL_NAME')?></b> <?=$arResult['LOYALTY_LEVEL_NAME']?><br>
    <?php } ?>
    <?php if (isset($arResult['ORDERS_SUM'])) { ?>
        <b><?=GetMessage('ORDERS_SUM')?></b> <?=$arResult['ORDERS_SUM']?><br>
    <?php } ?>
    <?php if (isset($arResult['REMAINING_SUM'])) { ?>
        <b><?=GetMessage('REMAINING_SUM')?></b> <?=$arResult['REMAINING_SUM']?><br>
    <?php } ?>
    <?php if (isset($arResult['BONUS_COUNT'])) { ?>
        <b><?=GetMessage('BONUS_COUNT')?></b> <?=$arResult['BONUS_COUNT']?><br>
    <?php } ?>
    <?php if (isset($arResult['CARD'])) { ?>
        <b><?=GetMessage('CARD')?></b> <?=$arResult['CARD']?><br>
    <?php } ?>
    <?php if (isset($arResult['PHONE'])) { ?>
        <b><?=GetMessage('PHONE')?></b> <?=$arResult['PHONE']?><br>
    <?php } ?>
    <?php if (isset($arResult['REGISTER_DATE'])) { ?>
        <b><?=GetMessage('REGISTER_DATE')?></b> <?=$arResult['REGISTER_DATE']?><br>
    <?php } ?>

    <?php if (isset($arResult['LOYALTY_LEVEL_TYPE'])) { ?>
        <br><br><b><?=GetMessage('LOYALTY_LEVEL_TYPE')?></b><br>

        <?php if (isset($arResult['NEXT_LEVEL_SUM'])) { ?>
            <b><?=GetMessage('NEXT_LEVEL_SUM')?></b> <?=$arResult['NEXT_LEVEL_SUM']?><br>
        <?php } ?>
        <?php
        switch ($arResult['LOYALTY_LEVEL_TYPE']) {
            case 'bonus_percent':
                ?>
                <b><?=GetMessage('SIMPLE_PRODUCTS')?></b>
                <?=GetMessage('BONUS_PERCENT')?> <?=$arResult['LL_PRIVILEGE_SIZE']?>%<br>
                <b><?=GetMessage('SALE_PRODUCTS')?></b>
                <?=GetMessage('BONUS_PERCENT')?> <?=$arResult['LL_PRIVILEGE_SIZE_PROMO']?>%<br>
                <?php
                break;
            case 'bonus_converting':
                ?>
                <b><?=GetMessage('SIMPLE_PRODUCTS')?></b>
                <?=GetMessage('BONUS_CONVERTING')?> <?=$arResult['LL_PRIVILEGE_SIZE']?> <?=GetMessage('EACH_RUB')?><br>
                <b><?=GetMessage('SALE_PRODUCTS')?></b>
                <?=GetMessage('BONUS_CONVERTING')?> <?=$arResult['LL_PRIVILEGE_SIZE_PROMO']?>
                <?=GetMessage('EACH_RUB')?><br>
                <?php
                break;
            case 'discount':
                ?>
                <b><?=GetMessage('SIMPLE_PRODUCTS')?></b>
                <?=GetMessage('PERSONAL_DISCOUNT')?> <?=$arResult['LL_PRIVILEGE_SIZE']?>%<br>
                <b><?=GetMessage('SALE_PRODUCTS')?></b>
                <?=GetMessage('PERSONAL_DISCOUNT')?> <?=$arResult['LL_PRIVILEGE_SIZE_PROMO']?>%<br>
                <?php
                break;
            default: ?> - <?php
                break;
        }
        ?>
    <?php } ?>
</p>
