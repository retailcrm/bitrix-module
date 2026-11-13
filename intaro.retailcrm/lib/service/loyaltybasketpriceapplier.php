<?php

/**
 * @category Integration
 * @package  Intaro\RetailCrm\Service
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

namespace Intaro\RetailCrm\Service;

use Bitrix\Main\Error;
use Bitrix\Sale\Order;
use Bitrix\Sale\Result;
use Intaro\RetailCrm\Model\Api\Response\Loyalty\LoyaltyCalculateResponse;
use Intaro\RetailCrm\Model\Api\Response\Order\Loyalty\OrderLoyaltyApplyResponse;

/**
 * Applies loyalty-program prices to Bitrix basket items.
 */
final class LoyaltyBasketPriceApplier
{
    /**
     * @param \Bitrix\Sale\Order                                                                                         $order
     * @param \Intaro\RetailCrm\Model\Api\Response\Loyalty\LoyaltyCalculateResponse|\Intaro\RetailCrm\Model\Api\Response\Order\Loyalty\OrderLoyaltyApplyResponse $response
     * @param bool                                                                                                         $runFinalAction
     *
     * @return \Bitrix\Sale\Result
     */
    public function apply(Order $order, $response, bool $runFinalAction = true): Result
    {
        $result = new Result();

        if (!$response instanceof LoyaltyCalculateResponse && !$response instanceof OrderLoyaltyApplyResponse) {
            return $result->addError(new Error('Unsupported loyalty response type.'));
        }

        if (!isset($response->order) || !is_array($response->order->items)) {
            return $result->addError(new Error('Loyalty response does not contain order items.'));
        }

        try {
            $basket = $order->getBasket();
            $itemsToUpdate = [];

            foreach ($response->order->items as $calculatedItem) {
                $basketItemId = $this->getBasketItemId($calculatedItem);

                if ($basketItemId === null) {
                    $result->addError(new Error('Loyalty response item does not contain bitrixBasketId.'));
                    continue;
                }

                if (isset($itemsToUpdate[$basketItemId])) {
                    $result->addError(new Error(sprintf('Loyalty response contains duplicate basket item %s.', $basketItemId)));
                    continue;
                }

                $basketItem = $basket->getItemById($basketItemId);

                if ($basketItem === null) {
                    $result->addError(new Error(sprintf('Basket item %s was not found.', $basketItemId)));
                    continue;
                }

                if (!isset($calculatedItem->initialPrice, $calculatedItem->discountTotal)
                    || !is_numeric($calculatedItem->initialPrice)
                    || !is_numeric($calculatedItem->discountTotal)
                ) {
                    $result->addError(new Error(sprintf('Loyalty response item %s has invalid price data.', $basketItemId)));
                    continue;
                }

                $itemsToUpdate[$basketItemId] = [
                    'basketItem' => $basketItem,
                    'discount' => (float) $calculatedItem->discountTotal,
                    'price' => max(0, (float) $calculatedItem->initialPrice - (float) $calculatedItem->discountTotal),
                ];
            }

            if (empty($itemsToUpdate)) {
                $result->addError(new Error('Loyalty response has no basket items to update.'));
            }

            if (!$result->isSuccess()) {
                return $result;
            }

            foreach ($itemsToUpdate as $item) {
                $setResult = $item['basketItem']->setFields([
                    'CUSTOM_PRICE' => 'Y',
                    'PRICE' => $item['price'],
                    'DISCOUNT_PRICE' => $item['discount'],
                ]);

                if (!$setResult->isSuccess()) {
                    $result->addErrors($setResult->getErrors());
                }
            }

            if (!$result->isSuccess()) {
                return $result;
            }

            if ($runFinalAction) {
                $finalActionResult = $order->doFinalAction(true);

                if (!$finalActionResult->isSuccess()) {
                    $result->addErrors($finalActionResult->getErrors());
                }
            }
        } catch (\Throwable $exception) {
            $result->addError(new Error($exception->getMessage()));
        }

        return $result;
    }

    public function applySavedDiscounts(Order $order, array $calculateItemsInput): Result
    {
        $result = new Result();

        try {
            foreach ($order->getBasket() as $basketItem) {
                $basketItemId = (string) $basketItem->getId();
                $calculateItem = $calculateItemsInput[$basketItemId] ?? null;

                if (!is_array($calculateItem)
                    || !isset($calculateItem['SUM_NUM'], $calculateItem['QUANTITY'])
                    || !is_numeric($calculateItem['SUM_NUM'])
                    || !is_numeric($calculateItem['QUANTITY'])
                    || (float) $calculateItem['QUANTITY'] <= 0
                ) {
                    $result->addError(new Error(sprintf('Saved loyalty discount for basket item %s is invalid.', $basketItemId)));
                    continue;
                }

                $price = (float) $calculateItem['SUM_NUM'] / (float) $calculateItem['QUANTITY'];
                $setResult = $basketItem->setFields([
                    'CUSTOM_PRICE' => 'Y',
                    'DISCOUNT_PRICE' => $basketItem->getBasePrice() - $price,
                    'PRICE' => $price,
                ]);

                if (!$setResult->isSuccess()) {
                    $result->addErrors($setResult->getErrors());
                }
            }
        } catch (\Throwable $exception) {
            $result->addError(new Error($exception->getMessage()));
        }

        return $result;
    }

    /**
     * @param mixed $calculatedItem
     *
     * @return string|null
     */
    private function getBasketItemId($calculatedItem): ?string
    {
        if (!isset($calculatedItem->externalIds) || !is_array($calculatedItem->externalIds)) {
            return null;
        }

        foreach ($calculatedItem->externalIds as $externalId) {
            if (!is_object($externalId)
                || !isset($externalId->code, $externalId->value)
                || $externalId->code !== 'bitrixBasketId'
            ) {
                continue;
            }

            $basketItemId = explode('#', (string) $externalId->value, 2)[0];

            return $basketItemId === '' ? null : $basketItemId;
        }

        return null;
    }
}
