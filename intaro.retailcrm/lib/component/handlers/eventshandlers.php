<?php

/**
 * @category Integration
 * @package  Intaro\RetailCrm\Component\Loyalty
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

namespace Intaro\RetailCrm\Component\Handlers;

IncludeModuleLangFile(__FILE__);

use Bitrix\Main\Event;
use Bitrix\Main\HttpRequest;
use Bitrix\Sale\Order;
use Bitrix\Sale\PriceMaths;
use Intaro\RetailCrm\Component\Builder\Bitrix\LoyaltyDataBuilder;
use Intaro\RetailCrm\Component\ConfigProvider;
use Intaro\RetailCrm\Component\Constants;
use Intaro\RetailCrm\Component\ServiceLocator;
use Intaro\RetailCrm\Model\Api\Response\Loyalty\LoyaltyCalculateResponse;
use Intaro\RetailCrm\Model\Api\Response\Order\Loyalty\OrderLoyaltyApplyResponse;
use Intaro\RetailCrm\Repository\UserRepository;
use Intaro\RetailCrm\Service\GiftResolver;
use Intaro\RetailCrm\Service\GiftRuleCalculator;
use Intaro\RetailCrm\Service\LoyaltyBasketPriceApplier;
use Intaro\RetailCrm\Service\LoyaltyService;
use Intaro\RetailCrm\Service\LoyaltyAccountService;
use Intaro\RetailCrm\Service\CustomerService;
use Intaro\RetailCrm\Service\OrderLoyaltyDataService;
use Intaro\RetailCrm\Service\Utils;
use Logger;
use RetailCrmEvent;
use Throwable;

/**
 * Class EventsHandlers
 *
 * @package Intaro\RetailCrm\Component\Loyalty
 */
class EventsHandlers
{
    public static $disableSaleHandler = false;

    /**
     * EventsHandlers constructor.
     */
    public function __construct()
    {
        IncludeModuleLangFile(__FILE__);
    }

    /**
     * Обработчик события, вызываемого при обновлении еще не сохраненного заказа
     *
     * Модифицирует данные $arResult с учетом привилегий покупателя по Программе лояльности
     *
     * @param \Bitrix\Sale\Order       $order
     * @param array                    $arUserResult
     * @param \Bitrix\Main\HttpRequest $request
     * @param array                    $arParams
     * @param array                    $arResult
     */
    public static function OnSaleComponentOrderResultPreparedHandler(
        Order $order,
        array $arUserResult,
        HttpRequest $request,
        array $arParams,
        array &$arResult
    ): void {
        if (ConfigProvider::getLoyaltyProgramStatus() === 'Y') {
            $bonusInput           = (float) $request->get('bonus-input');
            $availableBonuses     = LoyaltyAccountService::getAvailableBonusesForUser();
            $chargeRate           = (float) $request->get('charge-rate');
            $loyaltyDiscountInput = (float) $request->get('loyalty-discount-input');
            $calculateItemsInput  = $request->get('calculate-items-input');
            $bonusDiscount        = round($bonusInput * $chargeRate, 2);

            if ($bonusInput > $availableBonuses) {
                $arResult['LOYALTY']['ERROR'] = GetMessage('BONUS_ERROR_MSG');

                return;
            }

            $jsDataTotal = &$arResult['JS_DATA']['TOTAL'];

            $isWriteOffAvailable = $bonusInput > 0
                && $availableBonuses > 0
                && $jsDataTotal['ORDER_TOTAL_PRICE'] >= $bonusDiscount + $loyaltyDiscountInput
            ;

            $useBonusesForBasketRules = self::shouldUseBonusesForBasketRules($bonusInput);

            if ($useBonusesForBasketRules && $isWriteOffAvailable) {
                /** @var LoyaltyService $service */
                $service = ServiceLocator::get(LoyaltyService::class);
                $calculate = self::recalculateOrderForBasketRules($order, $arResult['BASKET_ITEMS'], $service, $bonusInput);

                if ($calculate instanceof LoyaltyCalculateResponse) {
                    $arResult['JS_DATA']['TOTAL']['WILL_BE_CREDITED'] = $calculate->order->bonusesCreditTotal;
                    self::refreshCheckoutData($order, $arResult);
                }

                return;
            }

            if ($isWriteOffAvailable || $loyaltyDiscountInput > 0) {
                $jsDataTotal['ORDER_TOTAL_PRICE']
                                                        -= round($bonusDiscount + $loyaltyDiscountInput, 2);
                $jsDataTotal['ORDER_TOTAL_PRICE_FORMATED']
                                                        = number_format($jsDataTotal['ORDER_TOTAL_PRICE'], 0, ',', ' ')
                    . ' ' . GetMessage('RUB');
                $jsDataTotal['BONUS_PAYMENT']           = $bonusDiscount;
                $jsDataTotal['DISCOUNT_PRICE']          += $bonusDiscount + $loyaltyDiscountInput;
                $jsDataTotal['DISCOUNT_PRICE_FORMATED'] = $jsDataTotal['DISCOUNT_PRICE'] . ' ' . GetMessage('RUB');
                $jsDataTotal['ORDER_PRICE_FORMATED']
                                                        = $jsDataTotal['ORDER_PRICE'] - $loyaltyDiscountInput . ' ' . GetMessage('RUB');
                $oldItems                               = json_decode(htmlspecialchars_decode($calculateItemsInput), true);

                /** @var LoyaltyService $service */
                $service = ServiceLocator::get(LoyaltyService::class);
                $calculate = $service->getLoyaltyCalculate($arResult['BASKET_ITEMS'], $bonusInput);

                if ($calculate->success) {
                    $jsDataTotal['WILL_BE_CREDITED'] = $calculate->order->bonusesCreditTotal;
                }

                if ($calculateItemsInput !== null) {
                    foreach ($arResult['JS_DATA']['GRID']['ROWS'] as $key => &$item) {
                        $item['data']['SUM_NUM'] = $oldItems[$key]['SUM_NUM'];
                        $item['data']['SUM']     = $item['data']['SUM_NUM'] . GetMessage('RUB');
                    }
                }

                unset($item);
            }
        }
    }

    /**
     * Calculates loyalty prices, removes invalidated gifts and recalculates the order after each change.
     *
     * @param \Bitrix\Sale\Order                        $order
     * @param array                                       $basketItems
     * @param \Intaro\RetailCrm\Service\LoyaltyService $loyaltyService
     * @param float                                       $bonusInput
     *
     * @return \Intaro\RetailCrm\Model\Api\Response\Loyalty\LoyaltyCalculateResponse|null
     */
    private static function recalculateOrderForBasketRules(
        Order $order,
        array $basketItems,
        LoyaltyService $loyaltyService,
        float $bonusInput
    ): ?LoyaltyCalculateResponse {
        try {
            $giftResolver = new GiftResolver();
            $giftsBeforeBonuses = $giftResolver->resolve($order);
            $calculate = $loyaltyService->getLoyaltyCalculate($basketItems, $bonusInput);

            if (!$calculate instanceof LoyaltyCalculateResponse || !$calculate->success) {
                return null;
            }

            $priceApplyResult = (new LoyaltyBasketPriceApplier())->apply($order, $calculate, false);

            if (!$priceApplyResult->isSuccess()) {
                Logger::getInstance()->write($priceApplyResult->getErrorMessages(), Constants::LOYALTY_ERROR);

                return null;
            }

            foreach ($giftsBeforeBonuses as $gift) {
                $giftItem = $order->getBasket()->getItemByBasketCode($gift['basketCode']);

                if ($giftItem !== null) {
                    $giftResetResult = $giftItem->setFields([
                        'CUSTOM_PRICE' => 'N',
                        'PRICE' => $giftItem->getBasePrice(),
                        'DISCOUNT_PRICE' => 0,
                    ]);

                    if (!$giftResetResult->isSuccess()) {
                        Logger::getInstance()->write($giftResetResult->getErrorMessages(), Constants::LOYALTY_ERROR);

                        return null;
                    }
                }
            }

            $firstFinalActionResult = $order->doFinalAction(true);

            if (!$firstFinalActionResult->isSuccess()) {
                Logger::getInstance()->write($firstFinalActionResult->getErrorMessages(), Constants::LOYALTY_ERROR);

                return null;
            }

            $giftCalculation = GiftRuleCalculator::buildForGifts($order, $giftsBeforeBonuses);
            $giftCalculationResult = $giftCalculation->calculate();

            if (!$giftCalculationResult->isSuccess()) {
                Logger::getInstance()->write($giftCalculationResult->getErrorMessages(), Constants::LOYALTY_ERROR);

                return null;
            }

            $giftsAfterBonuses = $giftResolver->resolveApplyResult($giftCalculation->getApplyResult(true));
            $removedGifts = $giftResolver->getRemovedGifts($giftsBeforeBonuses, $giftsAfterBonuses);

            foreach ($removedGifts as $gift) {
                $basketItem = $order->getBasket()->getItemByBasketCode($gift['basketCode']);

                if ($basketItem === null) {
                    continue;
                }

                $deleteResult = $basketItem->delete();

                if (!$deleteResult->isSuccess()) {
                    Logger::getInstance()->write($deleteResult->getErrorMessages(), Constants::LOYALTY_ERROR);

                    return null;
                }
            }

            $secondFinalActionResult = $order->doFinalAction(true);

            if (!$secondFinalActionResult->isSuccess()) {
                Logger::getInstance()->write($secondFinalActionResult->getErrorMessages(), Constants::LOYALTY_ERROR);

                return null;
            }

            return $calculate;
        } catch (Throwable $exception) {
            Logger::getInstance()->write($exception->getMessage(), Constants::LOYALTY_ERROR);

            return null;
        }
    }

    /**
     * Replaces checkout prices with the values calculated on the updated order.
     *
     * @param \Bitrix\Sale\Order $order
     * @param array                $arResult
     *
     * @return void
     */
    private static function refreshCheckoutData(Order $order, array &$arResult): void
    {
        $currency = $order->getCurrency();
        $basket = $order->getBasket();
        $basketItemData = [];

        foreach ($basket as $basketItem) {
            $price = $basketItem->getPrice();
            $basePrice = $basketItem->getBasePrice();
            $discountPrice = $basketItem->getDiscountPrice();
            $quantity = $basketItem->getQuantity();
            $discountPercent = $basePrice > 0 ? round($discountPrice / $basePrice * 100) : 0;
            $itemData = [
                'CUSTOM_PRICE' => $basketItem->getField('CUSTOM_PRICE'),
                'PRICE' => $price,
                'PRICE_FORMATED' => SaleFormatCurrency($price, $currency),
                'BASE_PRICE' => $basePrice,
                'BASE_PRICE_FORMATED' => SaleFormatCurrency($basePrice, $currency),
                'DISCOUNT_PRICE' => $discountPrice,
                'DISCOUNT_PRICE_PERCENT' => $discountPercent,
                'DISCOUNT_PRICE_PERCENT_FORMATED' => $discountPercent . '%',
                'SUM_NUM' => $price * $quantity,
                'SUM' => SaleFormatCurrency($price * $quantity, $currency),
                'SUM_BASE' => $basePrice * $quantity,
                'SUM_BASE_FORMATED' => SaleFormatCurrency($basePrice * $quantity, $currency),
            ];
            $basketItemData[(string) $basketItem->getBasketCode()] = $itemData;

            if ($basketItem->getId() > 0) {
                $basketItemData[(string) $basketItem->getId()] = $itemData;
            }
        }

        foreach ($arResult['BASKET_ITEMS'] as $key => $item) {
            $basketCode = (string) ($item['ID'] ?? '');

            if (!isset($basketItemData[$basketCode])) {
                unset($arResult['BASKET_ITEMS'][$key]);
                continue;
            }

            $arResult['BASKET_ITEMS'][$key] = array_replace($item, $basketItemData[$basketCode]);
        }

        $arResult['BASKET_ITEMS'] = array_values($arResult['BASKET_ITEMS']);

        foreach ($arResult['GRID']['ROWS'] as $basketCode => &$row) {
            $basketCode = (string) $basketCode;

            if (!isset($basketItemData[$basketCode])) {
                unset($arResult['GRID']['ROWS'][$basketCode]);
                continue;
            }

            $row['data'] = array_replace($row['data'], $basketItemData[$basketCode]);
        }

        unset($row);

        $orderPrice = PriceMaths::roundPrecision($basket->getPrice());
        $priceWithoutDiscount = $basket->getBasePrice();
        $discountPrice = PriceMaths::roundPrecision(
            $order->getDiscountPrice() + ($priceWithoutDiscount - $orderPrice)
        );
        $orderTotalPrice = PriceMaths::roundPrecision($order->getPrice());
        $deliveryPrice = PriceMaths::roundPrecision($order->getDeliveryPrice());
        $innerPayment = $order->getPaymentCollection()->getInnerPayment();
        $paidFromAccount = $innerPayment ? $innerPayment->getSum() : 0;

        $arResult['PRICE_WITHOUT_DISCOUNT_VALUE'] = $priceWithoutDiscount;
        $arResult['PRICE_WITHOUT_DISCOUNT'] = SaleFormatCurrency($priceWithoutDiscount, $currency);
        $arResult['ORDER_PRICE'] = $orderPrice;
        $arResult['ORDER_PRICE_FORMATED'] = SaleFormatCurrency($orderPrice, $currency);
        $arResult['DISCOUNT_PRICE'] = $discountPrice;
        $arResult['DISCOUNT_PRICE_FORMATED'] = SaleFormatCurrency($discountPrice, $currency);
        $arResult['ORDER_TOTAL_PRICE'] = $orderTotalPrice;
        $arResult['ORDER_TOTAL_PRICE_FORMATED'] = SaleFormatCurrency($orderTotalPrice, $currency);
        $arResult['DELIVERY_PRICE'] = $deliveryPrice;
        $arResult['DELIVERY_PRICE_FORMATED'] = SaleFormatCurrency($deliveryPrice, $currency);
        $arResult['ORDER_TOTAL_LEFT_TO_PAY'] = $orderTotalPrice - $paidFromAccount;
        $arResult['ORDER_TOTAL_LEFT_TO_PAY_FORMATED'] = SaleFormatCurrency(
            $arResult['ORDER_TOTAL_LEFT_TO_PAY'],
            $currency
        );
        $arResult['TAX_PRICE'] = $order->getTaxPrice();

        foreach ([
            'PRICE_WITHOUT_DISCOUNT_VALUE',
            'PRICE_WITHOUT_DISCOUNT',
            'ORDER_PRICE',
            'ORDER_PRICE_FORMATED',
            'DISCOUNT_PRICE',
            'DISCOUNT_PRICE_FORMATED',
            'ORDER_TOTAL_PRICE',
            'ORDER_TOTAL_PRICE_FORMATED',
            'DELIVERY_PRICE',
            'DELIVERY_PRICE_FORMATED',
            'ORDER_TOTAL_LEFT_TO_PAY',
            'ORDER_TOTAL_LEFT_TO_PAY_FORMATED',
            'TAX_PRICE',
        ] as $key) {
            $arResult['JS_DATA']['TOTAL'][$key] = $arResult[$key];
        }

        $arResult['JS_DATA']['GRID'] = $arResult['GRID'];
    }

    /**
     * Обработчик события, вызываемого ПОСЛЕ сохранения заказа (OnSaleOrderSaved)
     *
     * @param \Bitrix\Main\Event $event
     */
    public static function OnSaleOrderSavedHandler(Event $event): void
    {
        if (self::$disableSaleHandler === true) {
            return;
        }

        try {
            /** @var Order $order */
            $order = $event->getParameter('ENTITY');

            $isBonusInput = (
                !empty($_POST['bonus-input'])
            );

            $isDataForLoyaltyDiscount = isset($_POST['calculate-items-input'], $_POST['loyalty-discount-input']);

            if (!($isDataForLoyaltyDiscount || $isBonusInput) ) {
                return;
            }

            /* @var LoyaltyService $loyaltyService */
            $loyaltyService = ServiceLocator::get(LoyaltyService::class);

            /* @var OrderLoyaltyDataService $orderLoyaltyDataService */
            $orderLoyaltyDataService = ServiceLocator::get(OrderLoyaltyDataService::class);

            $bonusFloat = (float) $_POST['bonus-input'];
            $availableBonuses = LoyaltyAccountService::getAvailableBonusesForUser((int) $order->getUserId());

            /** @var bool $isNewOrder */
            $isNewOrder                 = $event->getParameter('IS_NEW');
            $isLoyaltyOn                = ConfigProvider::getLoyaltyProgramStatus() === 'Y';
            $isBonusesIssetAndAvailable = $isBonusInput && $availableBonuses >= $bonusFloat;

            /** @var array $calculateItemsInput */
            $calculateItemsInput = $isDataForLoyaltyDiscount
                ? json_decode(htmlspecialchars_decode($_POST['calculate-items-input']), true)
                : [];

            if ($isNewOrder && $isLoyaltyOn && ($isDataForLoyaltyDiscount || $isBonusInput)) {
                self::$disableSaleHandler = true;

                try {
                    $hlInfoBuilder = new LoyaltyDataBuilder();
                    $hlInfoBuilder->setOrder($order);

                    $discountInput = isset($_POST['loyalty-discount-input'])
                        ? (float) $_POST['loyalty-discount-input']
                        : 0;

                    $loyaltyBonusMsg = 0;

                    $preliminaryPrices = ConfigProvider::getLoyaltyBonusesForBasketRules() === 'Y'
                        ? self::getBasketPrices($order)
                        : [];

                    //Если есть бонусы
                    if ($isBonusesIssetAndAvailable) {
                        $applyBonusResponse = $loyaltyService->applyBonusesInOrder($order, $bonusFloat);

                        if ($applyBonusResponse instanceof OrderLoyaltyApplyResponse) {
                            $hlInfoBuilder->setApplyResponse($applyBonusResponse);
                            self::logActualLoyaltyPriceDifference($order, $preliminaryPrices);
                        }

                        $loyaltyBonusMsg = $bonusFloat;
                        $hlInfoBuilder->setBonusCountTotal($bonusFloat);
                    }

                    //Если бонусов нет, но скидка по ПЛ есть
                    if ($isDataForLoyaltyDiscount && !$isBonusInput) {
                        $loyaltyService->saveDiscounts($order, $calculateItemsInput);
                    }

                    $orderLoyaltyDataService->saveBonusAndDiscToOrderProps(
                        $order->getPropertyCollection(),
                        $discountInput,
                        $loyaltyBonusMsg
                    );

                    $hlInfoBuilder->setCalculateItemsInput($calculateItemsInput);
                    $orderLoyaltyDataService->saveLoyaltyInfoToHl($hlInfoBuilder->build()->getResult());
                    $order->save();
                } finally {
                    self::$disableSaleHandler = false;
                }
            }
        } catch (Throwable $exception) {
            Logger::getInstance()->write(GetMessage('CAN_NOT_SAVE_ORDER') . $exception->getMessage(), 'uploadApiErrors');
        }
    }

    private static function getBasketPrices(Order $order): array
    {
        $prices = [];

        foreach ($order->getBasket() as $basketItem) {
            $basketItemId = (string) $basketItem->getId();

            if ($basketItemId === '0') {
                continue;
            }

            $prices[$basketItemId] = [
                'price' => (float) $basketItem->getPrice(),
                'discountPrice' => (float) $basketItem->getDiscountPrice(),
            ];
        }

        return $prices;
    }

    private static function logActualLoyaltyPriceDifference(Order $order, array $preliminaryPrices): void
    {
        if (empty($preliminaryPrices)) {
            return;
        }

        $differences = self::findLoyaltyPriceDifferences($preliminaryPrices, self::getBasketPrices($order));

        if (!empty($differences)) {
            Logger::getInstance()->write([
                'message' => 'Loyalty/apply prices differ from the preliminary loyalty/calculate prices.',
                'orderId' => $order->getId(),
                'differences' => $differences,
            ], Constants::LOYALTY_ERROR);
        }
    }

    private static function shouldUseBonusesForBasketRules(float $bonusInput): bool
    {
        return ConfigProvider::getLoyaltyBonusesForBasketRules() === 'Y' && $bonusInput > 0;
    }

    private static function findLoyaltyPriceDifferences(array $preliminaryPrices, array $actualPrices): array
    {
        $differences = [];

        foreach ($actualPrices as $basketItemId => $actualPrice) {
            if (!isset($preliminaryPrices[$basketItemId])) {
                continue;
            }

            $preliminaryPrice = $preliminaryPrices[$basketItemId];

            if (
                abs($preliminaryPrice['price'] - $actualPrice['price']) < 0.00001
                && abs($preliminaryPrice['discountPrice'] - $actualPrice['discountPrice']) < 0.00001
            ) {
                continue;
            }

            $differences[] = [
                'bitrixBasketId' => $basketItemId,
                'preliminary' => $preliminaryPrice,
                'actual' => $actualPrice,
            ];
        }

        return $differences;
    }
}
