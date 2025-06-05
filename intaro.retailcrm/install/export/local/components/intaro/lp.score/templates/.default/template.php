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
            <?php if ($arResult['ACTIVE_STATUS'] === 'not_confirmed') { ?>
                <?= GetMessage('STATUS_NOT_CONFIRMED')?>
                <a href="/lp-register?activate=Y"> <?= GetMessage('ACTIVATE') ?></a>
            <?php } ?>
            <?php if ($arResult['ACTIVE_STATUS'] === 'deactivated') { ?>
                <?=GetMessage('STATUS_DEACTIVATED')?>
            <?php } ?>
        <?php if (isset($arResult['CARD'])) { ?>
            <b><?=GetMessage('CARD')?></b> <?=$arResult['CARD']?><br>
        <?php } ?>


    <?php if (isset($arResult['BONUS_COUNT'])) { ?>
        <?=sprintf(GetMessage('BONUS_COUNT'), $arResult['BONUS_COUNT']) ?><br>
    <?php } ?>
        <?php if (isset($arResult['BONUS_COUNT'])) { ?>
            <?=sprintf(GetMessage('BONUS_COUNT'), $arResult['BONUS_COUNT']) ?><br>
        <?php } ?>




        <?php if (isset($arResult['LOYALTY_LEVEL_NAME'])) { ?>
            <?=$arResult['LOYALTY_LEVEL_NAME']?><br>
        <?php } ?>
        <?php if (isset($arResult['LL_PRIVILEGE_SIZE']) && isset($arResult['LL_PRIVILEGE_SIZE_PROMO'])) { ?>
            <?=sprintf(GetMessage('LOYALTY_BONUS_PERCENT_INFO'), $arResult['LL_PRIVILEGE_SIZE'], $arResult['LL_PRIVILEGE_SIZE_PROMO'])?><br>
        <?php } ?>




        <?php if (isset($arResult['ORDERS_SUM'])) { ?>
            <?=GetMessage('ORDERS_SUM')?> <?=$arResult['ORDERS_SUM']?><br>
        <?php } ?>
    <?php if (isset($arResult['REMAINING_SUM'])) { ?>
            <?=GetMessage('REMAINING_SUM')?> <?=$arResult['REMAINING_SUM']?><br>
        <?php } ?>



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
                        $description = GetMessage('LOYALTY_ORDER_BONUS_ACCRUAL') . ' <a href="/personal/orders/' . $orderId . '">' . $orderId . '</a>';

                        break;
                    case 'burn':
                        $description = GetMessage('LOYALTY_BONUS_EXPIRED');

                        break;
                    case 'credit_for_event':
                        $description = GetMessage('LOYALTY_EVENT_BONUS_ACCRUAL');

                        break;
                    case 'charge_for_order':
                        $orderId = $operation->order->externalId ?? null;
                        $description = GetMessage('LOYALTY_ORDER_BONUS_DEBIT') . ' <a href="/personal/orders/' . $orderId . '">' . $orderId . '</a>';

                        break;
                    case 'charge_manual':
                        $description = GetMessage('LOYALTY_MANAGER_BONUS_DEBIT');

                        break;

                    case 'credit_manual':
                        $description = GetMessage('LOYALTY_MANAGER_BONUS_ACCRUAL');

                        break;
                    case 'cancel_of_charge':
                        $description = GetMessage('LOYALTY_BONUS_DEBIT_CANCELLED');

                        break;

                    case 'cancel_of_credit':
                        $description = GetMessage('LOYALTY_BONUS_ACCRUAL_CANCELLED');

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
