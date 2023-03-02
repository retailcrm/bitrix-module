<?php

use Intaro\RetailCrm\Component\Handlers\EventsHandlers;
use Bitrix\Main\EventManager;

class EventsHandlersTest extends \BitrixTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        COption::SetOptionString('intaro.retailcrm', 'api_version', 'v5');
        CModule::IncludeModule('intaro.retailcrm');
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testOnSaleOrderSavedHandler()
    {
        $order = \Bitrix\Sale\Order::create('s1', 2, 'RUB');
        $order->setPersonTypeId(2);

        $event = $this->createMock(\Bitrix\Main\Event::class);
        $event->method('getParameter')->willReturn($order);

        $spy = \Mockery::spy('overload:' .RetailCrmEvent::class);

        EventsHandlers::OnSaleOrderSavedHandler($event);

        //Проверяет, был ли вызван метод класса. Если метод не вызывался, выдает ошибку теста
        //Если метод вызывался, ошибку не выдает, но phpunit выдает сообщение об отсутствии тестов
        $spy->shouldHaveReceived('orderSave');
        self::assertTrue(true);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testOnSaleOrderNotSavedHandler()
    {
        $order = \Bitrix\Sale\Order::create('s1', 2, 'RUB');
        $order->setPersonTypeId(2);

        $event = $this->createMock(\Bitrix\Main\Event::class);
        $event->method('getParameter')->willReturn($order);

        $spy = \Mockery::spy('overload:' .RetailCrmEvent::class);


        EventsHandlers::$disableSaleHandler = true;
        EventsHandlers::OnSaleOrderSavedHandler($event);

        $spy->shouldNotHaveReceived('orderSave');
        self::assertTrue(true);
    }
}