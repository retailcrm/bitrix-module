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

use Bitrix\Sale\Order;

/**
 * Finds basket items that were made free by an applied Bitrix basket rule.
 */
final class GiftResolver
{
    private const PRICE_EPSILON = 0.00001;
    private const GIFT_ACTION_IDS = ['GiftCondGroup', 'ActSaleGift'];

    /**
     * Reads the extended discount-calculation result of an order.
     *
     * @param \Bitrix\Sale\Order $order
     *
     * @return array
     */
    public function resolve(Order $order): array
    {
        return $this->resolveApplyResult($order->getDiscount()->getApplyResult(true));
    }

    /**
     * Returns confirmed gifts indexed by their Bitrix basket code.
     *
     * @param array $applyResult Result of \Bitrix\Sale\Discount::getApplyResult(true)
     *
     * @return array
     */
    public function resolveApplyResult(array $applyResult): array
    {
        $priceDataByBasketCode = $applyResult['PRICES']['BASKET'] ?? [];
        $resultDataByBasketCode = $applyResult['RESULT']['BASKET'] ?? [];
        $discountList = $applyResult['DISCOUNT_LIST'] ?? [];
        $fullDiscountList = $applyResult['FULL_DISCOUNT_LIST'] ?? [];
        $gifts = [];

        if (!is_array($priceDataByBasketCode)
            || !is_array($resultDataByBasketCode)
            || !is_array($discountList)
            || !is_array($fullDiscountList)
        ) {
            return $gifts;
        }

        foreach ($priceDataByBasketCode as $basketCode => $priceData) {
            if (!is_array($priceData) || !$this->hasFullDiscount($priceData)) {
                continue;
            }

            $itemDiscountResults = $resultDataByBasketCode[$basketCode] ?? [];

            if (!is_array($itemDiscountResults)) {
                continue;
            }

            foreach ($discountList as $discountData) {
                if (!is_array($discountData) || !isset($discountData['ID'])) {
                    continue;
                }

                $ruleId = $discountData['REAL_DISCOUNT_ID'] ?? $discountData['ID'];
                $discountRule = $fullDiscountList[$ruleId] ?? null;

                if (!is_array($discountRule)
                    || !$this->isGiftRule($discountRule)
                    || !$this->isAppliedToBasketItem($itemDiscountResults, $discountData['ID'])
                ) {
                    continue;
                }

                $gifts[$basketCode] = [
                    'basketCode' => (string) $basketCode,
                    'discountId' => $ruleId,
                    'discountPercent' => 100.0,
                ];

                break;
            }
        }

        return $gifts;
    }

    /**
     * Returns gifts whose original Bitrix gift rule stopped applying after recalculation.
     *
     * @param array $giftsBefore
     * @param array $giftsAfter
     *
     * @return array
     */
    public function getRemovedGifts(array $giftsBefore, array $giftsAfter): array
    {
        $removedGifts = [];

        foreach ($giftsBefore as $basketCode => $giftBefore) {
            if (!is_array($giftBefore) || !array_key_exists('discountId', $giftBefore)) {
                continue;
            }

            $giftAfter = $giftsAfter[$basketCode] ?? null;

            if (!is_array($giftAfter)
                || !array_key_exists('discountId', $giftAfter)
                || (string) $giftAfter['discountId'] !== (string) $giftBefore['discountId']
            ) {
                $removedGifts[$basketCode] = $giftBefore;
            }
        }

        return $removedGifts;
    }

    /**
     * Checks that the position was reduced from a positive price to zero by a 100% discount.
     *
     * @param array $priceData
     *
     * @return bool
     */
    private function hasFullDiscount(array $priceData): bool
    {
        if (!isset($priceData['BASE_PRICE'], $priceData['PRICE'], $priceData['DISCOUNT'])
            || !is_numeric($priceData['BASE_PRICE'])
            || !is_numeric($priceData['PRICE'])
            || !is_numeric($priceData['DISCOUNT'])
        ) {
            return false;
        }

        $basePrice = (float) $priceData['BASE_PRICE'];
        $price = (float) $priceData['PRICE'];
        $discount = (float) $priceData['DISCOUNT'];

        return $basePrice > self::PRICE_EPSILON
            && abs($price) < self::PRICE_EPSILON
            && $discount >= $basePrice - self::PRICE_EPSILON;
    }

    /**
     * Checks that the applied basket rule contains Bitrix's gift action.
     *
     * @param array $discountRule
     *
     * @return bool
     */
    private function isGiftRule(array $discountRule): bool
    {
        if (isset($discountRule['ACTIONS_LIST']) && is_array($discountRule['ACTIONS_LIST'])) {
            return $this->containsGiftAction($discountRule['ACTIONS_LIST']);
        }

        if (isset($discountRule['ACTIONS']) && is_array($discountRule['ACTIONS'])) {
            return $this->containsGiftAction($discountRule['ACTIONS']);
        }

        if (!isset($discountRule['ACTIONS']) || !is_string($discountRule['ACTIONS'])) {
            return false;
        }

        foreach (self::GIFT_ACTION_IDS as $giftActionId) {
            if (mb_strpos($discountRule['ACTIONS'], $giftActionId) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array $action
     *
     * @return bool
     */
    private function containsGiftAction(array $action): bool
    {
        if (isset($action['CLASS_ID']) && in_array($action['CLASS_ID'], self::GIFT_ACTION_IDS, true)) {
            return true;
        }

        if (empty($action['CHILDREN']) || !is_array($action['CHILDREN'])) {
            return false;
        }

        foreach ($action['CHILDREN'] as $childAction) {
            if (is_array($childAction) && $this->containsGiftAction($childAction)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks that the calculation result connects a basket item to a particular basket rule.
     *
     * @param array       $itemDiscountResults
     * @param string|int  $discountId
     *
     * @return bool
     */
    private function isAppliedToBasketItem(array $itemDiscountResults, $discountId): bool
    {
        foreach ($itemDiscountResults as $itemDiscountResult) {
            if (!is_array($itemDiscountResult) || !array_key_exists('DISCOUNT_ID', $itemDiscountResult)) {
                continue;
            }

            if ((string) $itemDiscountResult['DISCOUNT_ID'] === (string) $discountId) {
                return true;
            }
        }

        return false;
    }
}
