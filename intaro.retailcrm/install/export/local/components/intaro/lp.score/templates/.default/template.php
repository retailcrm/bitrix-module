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
    <?php if (isset($arResult['ACTIVE_STATUS'])) { ?>
        <b><?=GetMessage('STATUS')?>: </b>
        <?php if ($arResult['ACTIVE_STATUS'] === 'not_confirmed') { ?>
            <?= GetMessage('STATUS_NOT_CONFIRMED')?>
            <a href="/lp-register?activate=Y"> <?= GetMessage('ACTIVATE') ?></a>
        <?php } ?>
        <?php if ($arResult['ACTIVE_STATUS'] === 'deactivated') { ?>
            <?=GetMessage('STATUS_DEACTIVATED')?>
        <?php } ?>
        <?php if ($arResult['ACTIVE_STATUS'] === 'activated') { ?>
            <?= GetMessage('STATUS_ACTIVE')?>
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

<?php if (!empty($arResult['LOYALTY_ACCOUNT_OPERATIONS'])): ?>
    <div class="loyalty-history">
        <h3>История операций</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <tbody>
            <?php foreach ($arResult['LOYALTY_ACCOUNT_OPERATIONS'] as $operation): ?>
                <?php
                $amount = $operation->amount;
                $isAccrual = $amount >= 0;
                $formattedAmount = ($amount >= 0 ? '+' : '') . number_format($amount, 0, '.', ' ');
                $createdAt = $operation->createdAt instanceof \DateTime
                    ? $operation->createdAt->format('Y-m-d H:i:s')
                    : $operation->createdAt;

                $description = '';

                switch ($operation->type) {
                    case 'credit_for_order':
                        $orderId = $operation->order->externalId;
                        $description = 'Начисление бонусов за заказ ' . '<a href="/personal/orders/' . $orderId . '">' . $orderId . '</a>';

                        break;
                    case 'burn':
                        $description = 'Бонусы сгорели';

                        break;
                    case 'credit_for_event':
                        $description = 'Начисление бонусов за событие';

                        break;
                    case 'charge_for_order':
                        $orderId = $operation->order->externalId ?? null;
                        $description = 'Списание бонусов за заказ ' . '<a href="/personal/orders/' . $orderId . '">' . $orderId . '</a>';

                        break;
                    case 'charge_manual':
                        $description = 'Списание бонусов менеджером';

                        break;

                    case 'credit_manual':
                        $description = 'Начислено бонусов менеджером';

                        break;
                    case 'cancel_of_charge':
                        $description = 'Отмена списания бонусов';

                        break;

                    case 'cancel_of_credit':
                        $description = 'Отмена начисления бонусов';

                        break;
                }

                ?>
                <tr style="height: 30px;">
                    <td style="color: <?= $isAccrual ? 'green' : 'red' ?>; font-weight: bold; width: 100px;">
                        <?= htmlspecialchars($formattedAmount) ?>
                    </td>
                    <td style="width: 180px; color: #555;">
                        <?= htmlspecialchars($createdAt) ?>
                    </td>
                    <td>
                        <?= $description ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <style>
        .loyalty-history table {
            width: 100%;
            border-collapse: collapse;
        }

        .loyalty-history table td {
            padding: 8px 4px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            font-size: 16px;
            vertical-align: middle;
        }

        .loyalty-history a {
            color: #0077cc;
            text-decoration: none;
            font-weight: 500;
        }

        .loyalty-history a:hover {
            text-decoration: underline;
        }

        .loyalty-history .amount-positive {
            color: #008000;
            font-weight: bold;
        }

        .loyalty-history .amount-negative {
            color: #cc0000;
            font-weight: bold;
        }

        .loyalty-history .operation-row {
            border-bottom: 1px solid #e0e0e0;
        }

        .loyalty-history .operation-row:last-child {
            border-bottom: none;
        }
    </style>

<?php endif; ?>
