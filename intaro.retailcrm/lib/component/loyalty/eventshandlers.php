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
 * Class ServiceLocator
 *
 * @package Intaro\RetailCrm\Component\EventsHandlers
 */
class EventsHandlers
{
    /**
     * @param \Bitrix\Main\Event $event
     */
    public function OnBeforeSalePaymentSetFieldHandler(Event $event)
    {
        AddMessage2Log('OnBeforeSalePaymentSetFieldHandler work! ' . $event->getDebugInfo());
    }
    
    /**
     * @param &$content
     * @return mixed
     */
    public function OnBeforeEndBufferContentHandler($content)
    {
        AddMessage2Log('OnBeforeEndBufferContentHandler work! ');
        return $content;
    }
    
    /**
     * @param \Bitrix\Main\Event $event
     */
    public function OnSaleOrderBeforeSavedHandler(Event $event)
    {
        AddMessage2Log('OnSaleOrderBeforeSavedHandler work! ' . $event->getDebugInfo());
    }
    
    /**
     * @param \Bitrix\Main\Event $event
     */
    public function OnSaleOrderPaidHandler(Event $event)
    {
        AddMessage2Log('OnSaleOrderPaidHandler work! '. $event->getDebugInfo());
    }
    
    /**
     * @param \Bitrix\Main\Event $event
     */
    public function OnSaleStatusOrderChangeHandler(Event $event)
    {
        AddMessage2Log('OnSaleStatusOrderChangeHandler work! '. $event->getDebugInfo());
    }
    
    /**
     * @param \Bitrix\Main\Event $event
     */
    public function OnSaleOrderSavedHandler(Event $event)
    {
        AddMessage2Log('OnSaleOrderSavedHandler work! '. $event->getDebugInfo());
    }
    
    /**
     * @param \Bitrix\Main\Event $event
     */
    public function OnSaleOrderCanceledHandler(Event $event)
    {
        AddMessage2Log('OnSaleOrderCanceledHandler work! '. $event->getDebugInfo());
    }
    
    /**
     * @param \Bitrix\Main\Event $event
     */
    public function OnSaleOrderDeletedHandler(Event $event)
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
