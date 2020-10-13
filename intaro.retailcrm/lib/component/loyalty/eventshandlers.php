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

IncludeModuleLangFile(__FILE__);

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Event;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Sale\Order;
use Bitrix\Sale\PaySystem\Manager;
use Exception;
use Intaro\RetailCrm\Component\ConfigProvider;
use Intaro\RetailCrm\Component\Constants;
use Intaro\RetailCrm\Repository\PaySystemActionRepository;
use Intaro\RetailCrm\Service\LoyaltyService;

/**
 * Class EventsHandlers
 *
 * @package Intaro\RetailCrm\Component\Loyalty
 */
class EventsHandlers
{
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
     * Обработчик события, вызываемого при обновлении заказа
     *
     * @param \Bitrix\Sale\Order       $order
     * @param array                    $arUserResult
     * @param \Bitrix\Main\HttpRequest $request
     * @param array                    $arParams
     * @param array                    $arResult
     */
    public function OnSaleComponentOrderResultPreparedHandler(Order $order, array $arUserResult, HttpRequest $request, array $arParams, array &$arResult): void
    {
        if (ConfigProvider::getLoyaltyProgramStatus() === 'Y') {
            $bonusInput       = (int)$request->get('bonus-input');
            $availableBonuses = (int)$request->get('available-bonuses');
    
            if ($bonusInput > $availableBonuses) {
                $arResult['LOYALTY']['ERROR'] = GetMessage('BONUS_ERROR_MSG');
                return;
            }
            
            if ($bonusInput > 0
                && $availableBonuses > 0
                && $arResult['JS_DATA']['TOTAL']['ORDER_TOTAL_PRICE'] >= $bonusInput
            ) {
                $arResult['JS_DATA']['TOTAL']['ORDER_TOTAL_PRICE']          -= $bonusInput;
                $arResult['JS_DATA']['TOTAL']['ORDER_TOTAL_PRICE_FORMATED'] = number_format($arResult['JS_DATA']['TOTAL']['ORDER_TOTAL_PRICE'], 0, ',', ' ');
                $arResult['JS_DATA']['TOTAL']['BONUS_PAYMENT']              = $bonusInput;
            }
        }
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
            && (int) $_POST['available-bonuses'] >= (int) $_POST['bonus-input']
        ) {
            $orderId    = $order->getId();
            $bonusCount = $_POST['bonus-input'];
            $service    = new LoyaltyService();
            $response   = $service->sendBonusPayment($orderId, $bonusCount);
            
            //TODO - заглушка до появления api на стороне CRM. После появления реального апи - убрать следующую строку
            $response->success=true;
            
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
}
