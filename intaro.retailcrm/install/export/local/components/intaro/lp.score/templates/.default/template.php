<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
?>

<style>
    .loyalty-wrapper {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        font-size: 15px;
        color: #000;
        line-height: 1.5;
        max-width: 600px;
    }

    .loyalty-block {
        margin-bottom: 20px;
    }

    .loyalty-title {
        font-weight: bold;
        font-size: 17px;
        margin-bottom: 5px;
    }

    .loyalty-subinfo {
        color: #777;
        font-size: 13px;
    }

    .loyalty-history {
        margin-top: 20px;
    }

    .loyalty-history table {
        width: 100%;
        border-collapse: collapse;
    }

    .loyalty-history td {
        padding: 6px 4px;
        font-size: 14px;
        vertical-align: middle;
    }

    .amount-positive {
        color: green;
        font-weight: bold;
    }

    .amount-negative {
        color: red;
        font-weight: bold;
    }

    .loyalty-history .bonus-description {
        color: #555;
    }

    .loyalty-link {
        color: #0077cc;
        text-decoration: none;
        font-weight: 500;
    }

    .loyalty-link:hover {
        text-decoration: underline;
    }
</style>

<div class='loyalty-wrapper'>

    <?php if (isset($arResult['ERRORS'])): ?>
        <div class='loyalty-block'>
            <div class='loyalty-title'><?= GetMessage('ERRORS') ?></div>
            <?= $arResult['ERRORS'] ?>
        </div>
    <?php endif; ?>

    <?php if (isset($arResult['BONUS_COUNT'])): ?>
        <div class='loyalty-block'>
            <div class='loyalty-title'><?= sprintf(GetMessage('BONUS_COUNT'), $arResult['BONUS_COUNT']) ?></div>
            <div class='loyalty-subinfo'>
                <?= $arResult['BONUS_WILL_EXPIRE'] ?> • <?= $arResult['BONUS_PENDING'] ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (isset($arResult['LOYALTY_LEVEL_NAME'])): ?>
        <div class='loyalty-block'>
            <div class='loyalty-title'><?= $arResult['LOYALTY_LEVEL_NAME'] ?></div>
            <div class='loyalty-subinfo'>
                <?= sprintf(
                    GetMessage('LOYALTY_BONUS_PERCENT_INFO'),
                    $arResult['LL_PRIVILEGE_SIZE'],
                    $arResult['LL_PRIVILEGE_SIZE_PROMO']
                ) ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (isset($arResult['ORDERS_SUM']) || isset($arResult['REMAINING_SUM'])): ?>
        <div class='loyalty-block'>
            <div class='loyalty-title'><?= GetMessage('ORDERS_SUM') ?> <?= $arResult['ORDERS_SUM'] ?> ₽</div>
            <div class='loyalty-subinfo'>
                <?= GetMessage('REMAINING_SUM') ?> <?= $arResult['REMAINING_SUM'] ?> ₽
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($arResult['LOYALTY_ACCOUNT_OPERATIONS'])): ?>
        <div class='loyalty-history'>
            <div class='loyalty-title'><?= GetMessage('LOYALTY_HISTORY_TITLE')?></div>
            <table>
                <tbody>
                <?php foreach ($arResult['LOYALTY_ACCOUNT_OPERATIONS'] as $operation):
                    $amount = $operation->amount;
                    $isAccrual = $amount >= 0;
                    $formattedAmount = ($isAccrual ? '+' : '') . number_format($amount, 0, '.', ' ');
                    $createdAt = $operation->createdAt instanceof \DateTime
                        ? $operation->createdAt->format('Y-m-d')
                        : $operation->createdAt;
                    $description = '';

                    switch ($operation->type) {
                        case 'credit_for_order':
                            $orderId = $operation->order->externalId;
                            $description = GetMessage('LOYALTY_ORDER_BONUS_ACCRUAL') . ' <a class="loyalty-link" href="/personal/orders/' . $orderId . '">' . $orderId . '</a>';

                            break;
                        case 'burn':
                            $description = GetMessage('LOYALTY_BONUS_EXPIRED');

                            break;
                        case 'credit_for_event':
                            $description = GetMessage('LOYALTY_EVENT_BONUS_ACCRUAL');

                            break;
                        case 'charge_for_order':
                            $orderId = $operation->order->externalId ?? null;
                            $description = GetMessage('LOYALTY_ORDER_BONUS_DEBIT') . ' <a class="loyalty-link" href="/personal/orders/' . $orderId . '">' . $orderId . '</a>';

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
                    <tr>
                        <td class='<?= $isAccrual ? 'amount-positive' : 'amount-negative' ?>'><?= $formattedAmount ?></td>
                        <td class='bonus-description'><?= htmlspecialchars($createdAt) ?></td>
                        <td><?= $description ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
