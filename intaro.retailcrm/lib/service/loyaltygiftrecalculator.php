<?php

namespace Intaro\RetailCrm\Service;

use Bitrix\Main\Error;
use Bitrix\Sale\Order;
use Bitrix\Sale\Result;
use Intaro\RetailCrm\Component\Constants;
use Intaro\RetailCrm\Component\ServiceLocator;
use Intaro\RetailCrm\Model\Api\Response\Loyalty\LoyaltyCalculateResponse;
use Logger;
use SplObjectStorage;
use Throwable;

/** Request-local state shared by checkout, saving and the CRM export. */
final class LoyaltyGiftRecalculator
{
    private $preparedOrders;
    private $originalDiscounts = [];
    private $discardedCartItems;

    public function __construct()
    {
        $this->preparedOrders = new SplObjectStorage();
        $this->discardedCartItems = new SplObjectStorage();
    }

    public static function getInstance(): self
    {
        $service = ServiceLocator::get(self::class);
        if ($service === null) {
            $service = new self();
            ServiceLocator::set(self::class, $service);
        }
        return $service;
    }

    public function prepare(Order $order, array $basketItems, LoyaltyService $service, float $bonuses): ?LoyaltyCalculateResponse
    {
        if (isset($this->preparedOrders[$order])) {
            return $this->preparedOrders[$order]['calculation'];
        }

        try {
            $initialRules = $order->getDiscount()->getApplyResult(true);
            $gifts = (new GiftResolver())->resolveApplyResult($initialRules);
            $ruleDefinitions = $initialRules['FULL_DISCOUNT_LIST'] ?? [];
            $calculation = $service->getLoyaltyCalculate($basketItems, $bonuses);
            if (!$calculation instanceof LoyaltyCalculateResponse || !$calculation->success) {
                return null;
            }

            $discounts = [];
            foreach ($order->getBasket() as $item) {
                $discounts[(string) $item->getBasketCode()] = max(0, $item->getBasePrice() - $item->getPrice());
            }
            $result = $this->applyAndRecalculate($order, $calculation, $gifts, $ruleDefinitions);
            if (!$result->isSuccess()) {
                Logger::getInstance()->write($result->getErrorMessages(), Constants::LOYALTY_ERROR);
                return null;
            }

            $this->preparedOrders[$order] = ['calculation' => $calculation, 'gifts' => $gifts, 'rules' => $ruleDefinitions];
            foreach ($order->getBasket() as $item) {
                $this->originalDiscounts[(string) $item->getId()] = $discounts[(string) $item->getBasketCode()] ?? 0;
            }

            return $calculation;
        } catch (Throwable $exception) {
            Logger::getInstance()->write($exception->getMessage(), Constants::LOYALTY_ERROR);
            return null;
        }
    }

    public function applyActual(Order $order, $response): Result
    {
        $gifts = isset($this->preparedOrders[$order])
            ? $this->preparedOrders[$order]['gifts']
            : (new GiftResolver())->resolve($order);

        $rules = isset($this->preparedOrders[$order]) ? $this->preparedOrders[$order]['rules'] : [];
        return $this->applyAndRecalculate($order, $response, $gifts, $rules);
    }

    public function getOriginalDiscount(int $basketId): ?float
    {
        return $this->originalDiscounts[(string) $basketId] ?? null;
    }

    /** A new order can leave deleted, previously saved cart rows unattached to it. */
    public function removeDiscardedCartItems(Order $order): void
    {
        if ($order->getId() <= 0 || !isset($this->discardedCartItems[$order])) {
            return;
        }
        foreach ($this->discardedCartItems[$order] as $id => $expected) {
            $row = \Bitrix\Sale\Internals\BasketTable::getById($id)->fetch();
            if ($row && empty($row['ORDER_ID'])
                && (int) $row['FUSER_ID'] === $expected['fuserId']
                && (int) $row['PRODUCT_ID'] === $expected['productId']
                && $row['LID'] === $order->getSiteId()
            ) {
                if (!\CSaleBasket::Delete($id)) {
                    Logger::getInstance()->write('Cannot delete discarded gift basket row ' . $id, Constants::LOYALTY_ERROR);
                }
            }
        }
        unset($this->discardedCartItems[$order]);
    }

    private function applyAndRecalculate(Order $order, $response, array $gifts, array $ruleDefinitions): Result
    {
        $result = (new LoyaltyBasketPriceApplier())->apply($order, $response, false);
        if (!$result->isSuccess()) {
            return $result;
        }

        try {
            // Restore only proven Bitrix gifts, so their rules can be evaluated again.
            foreach ($gifts as $gift) {
                $item = $order->getBasket()->getItemByBasketCode($gift['basketCode']);
                if ($item === null) {
                    continue;
                }
                $reset = $item->setFields([
                    'CUSTOM_PRICE' => 'N',
                    'PRICE' => $item->getBasePrice(),
                    'DISCOUNT_PRICE' => 0,
                ]);
                if (!$reset->isSuccess()) {
                    return $reset;
                }
            }

            $result = $order->doFinalAction(true);
            if (!$result->isSuccess()) {
                return $result;
            }

            $calculator = GiftRuleCalculator::buildForGifts($order, $gifts);
            $result = $calculator->calculate();
            if (!$result->isSuccess()) {
                return $result;
            }

            $resolver = new GiftResolver();
            $applyResult = $calculator->getApplyResult(true);
            // Bitrix may omit full rule definitions when recalculating a saved order.
            // Reuse the definitions of the gifts confirmed earlier in this request.
            $applyResult['FULL_DISCOUNT_LIST'] = ($applyResult['FULL_DISCOUNT_LIST'] ?? []) + $ruleDefinitions;
            $remaining = $resolver->resolveApplyResult($applyResult);
            foreach ($resolver->getRemovedGifts($gifts, $remaining) as $gift) {
                $item = $order->getBasket()->getItemByBasketCode($gift['basketCode']);
                if ($item !== null) {
                    $id = $item->getId();
                    $cartItem = $id > 0 && !$item->getField('ORDER_ID')
                        ? ['fuserId' => (int) $item->getField('FUSER_ID'), 'productId' => $item->getProductId()]
                        : null;
                    $result = $item->delete();
                    if (!$result->isSuccess()) {
                        return $result;
                    }
                    if ($cartItem !== null) {
                        $discarded = $this->discardedCartItems[$order] ?? [];
                        $discarded[$id] = $cartItem;
                        $this->discardedCartItems[$order] = $discarded;
                    }
                }
            }

            $result = $order->doFinalAction(true);
            if ($result->isSuccess()) {
                $this->updatePayment($order);
            }
            return $result;
        } catch (Throwable $exception) {
            return (new Result())->addError(new Error($exception->getMessage()));
        }
    }

    private function updatePayment(Order $order): void
    {
        $payments = $order->getPaymentCollection();
        // The checkout has one external payment, possibly combined with an inner payment.
        $external = [];
        $innerSum = 0;
        foreach ($payments as $payment) {
            if ($payment->isInner()) {
                $innerSum += $payment->getSum();
            } else {
                $external[] = $payment;
            }
        }
        if (count($external) === 1 && !$external[0]->isPaid()) {
            $external[0]->setField('SUM', max(0, $order->getPrice() - $innerSum));
        }
    }
}
