<?php

/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Component\Loyalty
 * @author   retailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Component\Loyalty;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Event;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Sale\PaySystem\Manager;
use Exception;
use Intaro\RetailCrm\Component\ConfigProvider;
use Intaro\RetailCrm\Repository\PaySystemActionRepository;
use Intaro\RetailCrm\Service\LoyaltyService;

/**
 * Class EventsHandlers
 *
 * @package Intaro\RetailCrm\Component\Loyalty
 */
class EventsHandlers
{
    const BONUS_ERROR_MSG = 'Нельзя потратить такое количество бонусов';

    /**
     * @param \Bitrix\Main\Event $event
     */

    public function OnBeforeSalePaymentSetFieldHandler(Event $event): void
    {
        AddMessage2Log('OnBeforeSalePaymentSetFieldHandler work! ' . $event->getDebugInfo());
    }

    /**
     * @return mixed
     */
    public function OnBeforeEndBufferContentHandler()
    {
        AddMessage2Log('OnBeforeEndBufferContentHandler work! ');
    }

    /**
     * @param \Bitrix\Main\Event $event
     */
    public function OnSaleOrderBeforeSavedHandler(Event $event): void
    {
        AddMessage2Log('OnSaleOrderBeforeSavedHandler work! ' . $event->getDebugInfo());
    }

    /**
     * @param \Bitrix\Main\Event $event
     */
    public function OnSaleOrderPaidHandler(Event $event): void
    {
        AddMessage2Log('OnSaleOrderPaidHandler work! ' . $event->getDebugInfo());
    }

    /**
     * @param \Bitrix\Main\Event $event
     */
    public function OnSaleStatusOrderChangeHandler(Event $event): void
    {
        AddMessage2Log('OnSaleStatusOrderChangeHandler work! ' . $event->getDebugInfo());
    }

    /**
     * @param \Bitrix\Main\Event $event
     */
    public function OnSaleOrderCanceledHandler(Event $event): void
    {
        AddMessage2Log('OnSaleOrderCanceledHandler work! ' . $event->getDebugInfo());
    }

    /**
     * @param \Bitrix\Main\Event $event
     */
    public function OnSaleOrderDeletedHandler(Event $event): void
    {
        AddMessage2Log('OnSaleOrderDeletedHandler work! ' . $event->getDebugInfo());
    }

    /**
     * @param $arResult
     * @param $arUserResult
     * @param $arParams
     *
     * @return mixed
     */
    public function OnSaleComponentOrderOneStepProcessHandler($arResult, $arUserResult, $arParams)
    {
        AddMessage2Log('OnSaleComponentOrderOneStepProcessHandler work! ' . $arUserResult . $arParams);
        return $arResult;
    }

    /**
     * Обработчик события, вызываемого ПОСЛЕ сохранения заказа
     *
     * @param \Bitrix\Main\Event $event
     */
    public function OnSaleOrderSavedHandler(Event $event): void
    {
        /**@var \Bitrix\Sale\Order $order */
        $order = $event->getParameter("ENTITY");
        $isNew = $event->getParameter("IS_NEW");

        if (isset($_POST['bonus-input'], $_POST['available-bonuses'])
            && $isNew
            && (int)$_POST['available-bonuses'] >= (int)$_POST['bonus-input']) {
            $orderId    = $order->getId();
            $bonusCount = $_POST['bonus-input'];
            $service    = new LoyaltyService();
            $response   = $service->sendBonusPayment($orderId, $bonusCount);

            //TODO - заглушка до появления api на стороне CRM. После появления реального апи - убрать
            $response->success = true;

            if ($response->success) {
                try {
                    $bonusPaySystem    = PaySystemActionRepository::getFirstByWhere(['ID'], [['ACTION_FILE', '=', 'retailcrmbonus']]);
                    $paymentCollection = $order->getPaymentCollection();

                    if ($bonusPaySystem !== null) {
                        $service    = Manager::getObjectById($bonusPaySystem->getId());
                        $newPayment = $paymentCollection->createItem($service);

                        $newPayment->setField('SUM', $bonusCount);
                        $newPayment->setPaid('Y');
                        $order->save();
                    }
                } catch (ObjectPropertyException | ArgumentException | SystemException | Exception $e) {
                    AddMessage2Log('ERROR PaySystemActionRepository: ' . $e->getMessage());
                }
            }
        }
    }

    /**
     * @param                          $order
     * @param                          $arUserResult
     * @param \Bitrix\Main\HttpRequest $request
     * @param                          $arParams
     * @param                          $arResult
     */
    public function OnSaleComponentOrderResultPreparedHandler($order, $arUserResult, HttpRequest $request, $arParams, &$arResult): void
    {
        if (ConfigProvider::getLoyaltyProgramStatus() === 'Y') {
            $isBonusError     = false;
            $bonusInput       = (int)$request->get('bonus-input');
            $availableBonuses = (int)$request->get('available-bonuses');

            if ($bonusInput > $availableBonuses) {
                $arResult['LOYALTY']['ERROR'] = self::BONUS_ERROR_MSG;
                $isBonusError                 = true;
            }

            if (
                $bonusInput > 0
                && $availableBonuses > 0
                && $isBonusError === false
                && $arResult['JS_DATA']['TOTAL']['ORDER_TOTAL_PRICE'] > $bonusInput
            ) {
                $arResult['JS_DATA']['TOTAL']['ORDER_TOTAL_PRICE']          -= $bonusInput;
                $arResult['JS_DATA']['TOTAL']['ORDER_TOTAL_PRICE_FORMATED'] = number_format($arResult['JS_DATA']['TOTAL']['ORDER_TOTAL_PRICE'], 0, ',', ' ');
                $arResult['JS_DATA']['TOTAL']['BONUS_PAYMENT']              = $bonusInput;
            }
        }
    }
}
