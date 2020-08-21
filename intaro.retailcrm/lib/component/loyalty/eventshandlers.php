<?php
/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Component
 * @author   retailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

namespace Intaro\RetailCrm\Component\Loyalty;

use Bitrix\Main\Event;

/**
 * Class EventsHandlers
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
        AddMessage2Log('OnSaleOrderPaidHandler work! '. $event->getDebugInfo());
    }
    
    /**
     * @param \Bitrix\Main\Event $event
     */
    public function OnSaleStatusOrderChangeHandler(Event $event): void
    {
        AddMessage2Log('OnSaleStatusOrderChangeHandler work! '. $event->getDebugInfo());
    }
    
    /**
     * @param \Bitrix\Main\Event $event
     */
    public function OnSaleOrderSavedHandler(Event $event): void
    {
        AddMessage2Log('OnSaleOrderSavedHandler work! '. $event->getDebugInfo());
    }
    
    /**
     * @param \Bitrix\Main\Event $event
     */
    public function OnSaleOrderCanceledHandler(Event $event): void
    {
        AddMessage2Log('OnSaleOrderCanceledHandler work! '. $event->getDebugInfo());
    }
    
    /**
     * @param \Bitrix\Main\Event $event
     */
    public function OnSaleOrderDeletedHandler(Event $event): void
    {
        AddMessage2Log('OnSaleOrderDeletedHandler work! '. $event->getDebugInfo());
    }
    
    /**
     * @param $arResult
     * @param $arUserResult
     * @param $arParams
     * @return mixed
     */
    public function OnSaleComponentOrderOneStepProcessHandler($arResult, $arUserResult, $arParams)
    {
        AddMessage2Log('OnSaleComponentOrderOneStepProcessHandler work! '. $arUserResult. $arParams);
        return  $arResult;
    }
}
