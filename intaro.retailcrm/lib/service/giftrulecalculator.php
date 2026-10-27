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

use Bitrix\Sale\Discount;
use Bitrix\Sale\Discount\Context\User;
use Bitrix\Sale\Order;

final class GiftRuleCalculator extends Discount
{
    private $giftBasketCodes = [];

    public static function buildForGifts(Order $order, array $gifts): self
    {
        $calculator = new self();
        $calculator->order = $order;
        $calculator->context = new User($order->getUserId());
        $calculator->initInstanceData();

        foreach ($gifts as $gift) {
            $calculator->giftBasketCodes[$gift['basketCode']] = true;
        }

        return $calculator;
    }

    protected function checkDiscountConditions()
    {
        $prices = [];

        foreach ($this->giftBasketCodes as $basketCode => $unused) {
            if (isset($this->orderData['BASKET_ITEMS'][$basketCode])) {
                $prices[$basketCode] = $this->orderData['BASKET_ITEMS'][$basketCode]['PRICE'];
                $this->orderData['BASKET_ITEMS'][$basketCode]['PRICE'] = 0;
            }
        }

        try {
            return parent::checkDiscountConditions();
        } finally {
            foreach ($prices as $basketCode => $price) {
                $this->orderData['BASKET_ITEMS'][$basketCode]['PRICE'] = $price;
            }
        }
    }
}
