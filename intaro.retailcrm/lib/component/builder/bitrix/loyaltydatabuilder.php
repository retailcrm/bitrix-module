<?php

/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Component\Builder\Bitrix
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

namespace Intaro\RetailCrm\Component\Builder\Bitrix;

use Bitrix\Main\ArgumentNullException;
use Bitrix\Sale\BasketItemBase;
use Bitrix\Sale\Order;
use Exception;
use Intaro\RetailCrm\Component\Builder\BuilderInterface;
use Intaro\RetailCrm\Component\Constants;
use Intaro\RetailCrm\Component\ServiceLocator;
use Intaro\RetailCrm\Model\Api\OrderProduct;
use Intaro\RetailCrm\Model\Api\Response\Order\Loyalty\OrderLoyaltyApplyResponse;
use Intaro\RetailCrm\Model\Bitrix\OrderLoyaltyData;
use Intaro\RetailCrm\Service\CookieService;
use Logger;

/**
 * Class LoyaltyDataBuilder
 *
 * @package Intaro\RetailCrm\Component\Builder\Bitrix
 */
class LoyaltyDataBuilder implements BuilderInterface
{
    /** @var Order $order */
    private $order;

    /** @var OrderLoyaltyApplyResponse $applyResponse */
    private $applyResponse;

    /** @var array */
    private $calculateItemsInput;

    /** @var Logger $logger */
    private $logger;

    /** @var OrderLoyaltyData[] $data */
    private $data;

    /** @var float|null */
    private $bonusCountTotal;

    /**
     * LoyaltyDataBuilder constructor.
     */
    public function __construct()
    {
        $this->logger = Logger::getInstance();
    }

    /**
     * @param int[] $basketItemIds
     *
     * @return \Intaro\RetailCrm\Component\Builder\BuilderInterface
     */
    public function build(array $basketItemIds = []): BuilderInterface
    {
        try {
            /** @var BasketItemBase $basketItem */
            foreach ($this->order->getBasket() as $key => $basketItem) {
                if (!empty($basketItemIds) && !in_array($basketItem->getId(), $basketItemIds)) {
                    continue;
                }

                $loyaltyHl = new OrderLoyaltyData();
                $loyaltyHl->orderId = $this->order->getId();
                $loyaltyHl->itemId= $basketItem->getProductId();
                $loyaltyHl->basketItemPositionId = $basketItem->getId();
                $loyaltyHl->quantity = $basketItem->getQuantity();
                $loyaltyHl->name = $basketItem->getField('NAME');
                $loyaltyHl->bonusCountTotal = $this->bonusCountTotal ?? null;

                $loyaltyHl->defaultDiscount
                    = $this->calculateItemsInput[$loyaltyHl->basketItemPositionId]['SHOP_ITEM_DISCOUNT'] ?? null;

                $loyaltyHl->bonusCount
                    = $this->calculateItemsInput[$loyaltyHl->basketItemPositionId]['BONUSES_CHARGE'] ?? null;

                $this->addBonusInfo($loyaltyHl, $key);

                $this->data[] = $loyaltyHl;
            }
        } catch (ArgumentNullException | Exception $exception) {
            $this->logger->write($exception->getMessage(), Constants::LOYALTY_ERROR);
        }

        return $this;
    }

    /**
     * @return \Intaro\RetailCrm\Component\Builder\BuilderInterface
     */
    public function reset(): BuilderInterface
    {
        $this->data = null;
        $this->order = null;
        $this->applyResponse = null;

        return $this;
    }

    /**
     * @return \Intaro\RetailCrm\Model\Bitrix\OrderLoyaltyData[]
     */
    public function getResult(): array
    {
        return $this->data;
    }

    /**
     * @param \Bitrix\Sale\Order $order
     *
     * @return \Intaro\RetailCrm\Component\Builder\Bitrix\LoyaltyDataBuilder
     */
    public function setOrder(Order $order): LoyaltyDataBuilder
    {
        $this->order = $order;

        return $this;
    }

    /**
     * @param \Intaro\RetailCrm\Model\Api\Response\Order\Loyalty\OrderLoyaltyApplyResponse $applyResponse
     *
     * @return \Intaro\RetailCrm\Component\Builder\Bitrix\LoyaltyDataBuilder
     */
    public function setApplyResponse(OrderLoyaltyApplyResponse $applyResponse): LoyaltyDataBuilder
    {
        $this->applyResponse = $applyResponse;

        return $this;
    }

    /**
     * @param array $calculateItemsInput
     *
     * @return \Intaro\RetailCrm\Component\Builder\Bitrix\LoyaltyDataBuilder
     */
    public function setCalculateItemsInput(array $calculateItemsInput): LoyaltyDataBuilder
    {
        $this->calculateItemsInput = $calculateItemsInput;

        return $this;
    }

    /**
     * @param float|null $bonusCountTotal
     */
    public function setBonusCountTotal(?float $bonusCountTotal): void
    {
        $this->bonusCountTotal = $bonusCountTotal;
    }

    /**
     * @param \Intaro\RetailCrm\Model\Bitrix\OrderLoyaltyData $loyaltyHl
     * @param int                                             $key
     */
    private function addBonusInfo(OrderLoyaltyData $loyaltyHl, int $key): void
    {
        if (null === $this->applyResponse) {
            return;
        }

        /** @var \Intaro\RetailCrm\Service\CookieService $service */
        $service   = ServiceLocator::get(CookieService::class);
        $isDebited = false;
        $checkId   = '';

        //если верификация необходима, но не пройдена
        if (
            isset($this->applyResponse->verification, $this->applyResponse->verification->checkId)
            && !isset($response->verification->verifiedAt)
        ) {
            $isDebited = false;
            $service->setSmsCookie('lpOrderBonusConfirm', $this->applyResponse->verification);
            $checkId = $this->applyResponse->verification->checkId;
        }

        //если верификация не нужна
        if (!isset($this->applyResponse->verification)) {
            $isDebited = true;
        }

        /** @var OrderProduct $item */
        $item = $this->applyResponse->order->items[$key];

        $loyaltyHl->checkId    = $checkId;
        $loyaltyHl->isDebited  = $isDebited;
        $loyaltyHl->bonusCount = $item->bonusesChargeTotal;
    }
}
