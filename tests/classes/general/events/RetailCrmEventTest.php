<?php

use Intaro\RetailCrm\Component\Constants;

/**
 * Class RetailCrmEventTest
 */
class RetailCrmEventTest extends PHPUnit\Framework\TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        COption::SetOptionString('intaro.retailcrm', 'api_version', 'v5');
        CModule::IncludeModule('intaro.retailcrm');

        $this->retailcrmEvent = new RetailCrmEvent();
    }

    /**
     * @param $history
     * @param $emptyData
     *
     * @throws InvalidArgumentException
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @dataProvider userUpdateDataProvider
     */
    public function testOnAfterUserUpdate($history, $emptyData)
    {
        $arFields = [
            'ID' => 1
        ];

        if ($history === true) {
            $GLOBALS['RETAIL_CRM_HISTORY'] = $history;
        }

        if ($emptyData === true) {
            $arFields['RESULT'] = [];
        }

        $result = RetailCrmEvent::OnAfterUserUpdate($arFields);

        $this->assertEquals(false, $result);
    }

    /**
     * @param $history
     * @param $new
     *
     * @throws InvalidArgumentException
     * @throws \PHPUnit\Framework\Exception
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \PHPUnit\Framework\MockObject\RuntimeException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     *
     */
    public function testIntegrationPaymentSave()
    {
        RetailcrmConfigProvider::setSyncIntegrationPayment('N');
        RetailcrmConfigProvider::setIntegrationPaymentTypes(['testPayment']);

        $event = $this->createMock(\Bitrix\Sale\Payment::class);
        $date = \Bitrix\Main\Type\DateTime::createFromPhp(new DateTime('2000-01-01'))->format('Y-m-d H:i:s');
        $order = $this->createMock(\Bitrix\Sale\Order::class);

        $order->expects($this->any())
            ->method('isNew')
            ->willReturn(false);

        $paymentCollection = $this->createMock(\Bitrix\Sale\PaymentCollection::class);
        $paymentCollection->expects($this->any())
            ->method('getOrder')
            ->willReturn($order);

        $event->method('getCollection')->willReturn($paymentCollection);
        $event->method('getId')->willReturn(11);
        $event->method('getField')->willReturnCallback(function ($field) use ($date){
            switch ($field) {
                case 'ORDER_ID': return 11;
                case 'PAID': return 'paid';
                case 'PAY_SYSTEM_ID': return 1;
                case 'SUM': return '500';
                case 'DATE_PAID': return $date;
                default: return null;
            }
        });

        $api = new RetailCrm\ApiClient(RetailcrmConfigProvider::getApiUrl(), RetailcrmConfigProvider::getApiKey());
        $spy = \Mockery::spy('overload:' . RCrmActions::class); //Добавление слежки за классом

        $GLOBALS['RETAIL_CRM_HISTORY'] = false;

        $result = RetailCrmEvent::paymentSave($event);

        //Проверка вызова класса и передачи определенных параметров
        $spy->shouldReceive('apiMethod')->with(
            $api,
            'ordersPaymentCreate',
            'RetailCrmEvent::paymentSave',
            [
                'externalId' => null,
                'order' => ['externalId' => 11],
                'type' => 'testPayment'
            ],
            null
        )->once();

        $this->assertEquals(true, $result);
    }

    /**
     * @dataProvider paymentSaveDataProvider
     */
    public function testSavePaymentWithHistoryAndCreateOrder($history, $new)
    {
        $event = $this->createMock(\Bitrix\Sale\Payment::class);

        $order = $this->createMock(\Bitrix\Sale\Order::class);
        $order->expects($this->any())
            ->method('isNew')
            ->willReturn($new);

        $paymentCollection = $this->createMock(\Bitrix\Sale\PaymentCollection::class);
        $paymentCollection->expects($this->any())
            ->method('getOrder')
            ->willReturn($order);

        $event->method('getCollection')->willReturn($paymentCollection);

        if ($history === true) {
            $GLOBALS['RETAIL_CRM_HISTORY'] = true;
        }

        $result = RetailCrmEvent::paymentSave($event);

        $this->assertEquals(false, $result);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testPaymentSaveWithSyncIntegrationPayment()
    {
        RetailcrmConfigProvider::setSyncIntegrationPayment('Y');
        RetailcrmConfigProvider::setIntegrationPaymentTypes(['testPayment']);

        $event = $this->createMock(\Bitrix\Sale\Payment::class);
        $date = \Bitrix\Main\Type\DateTime::createFromPhp(new DateTime('2000-01-01'))->format('Y-m-d H:i:s');
        $order = $this->createMock(\Bitrix\Sale\Order::class);

        $order->expects($this->any())
            ->method('isNew')
            ->willReturn(false);

        $paymentCollection = $this->createMock(\Bitrix\Sale\PaymentCollection::class);
        $paymentCollection->expects($this->any())
            ->method('getOrder')
            ->willReturn($order);

        $event->method('getCollection')->willReturn($paymentCollection);
        $event->method('getId')->willReturn(11);
        $event->method('getField')->willReturnCallback(function ($field) use ($date){
            switch ($field) {
                case 'ORDER_ID': return 11;
                case 'PAID': return 'paid';
                case 'PAY_SYSTEM_ID': return 1;
                case 'SUM': return '500';
                case 'DATE_PAID': return $date;
                default: return null;
            }
        });

        $api = new RetailCrm\ApiClient(RetailcrmConfigProvider::getApiUrl(), RetailcrmConfigProvider::getApiKey());
        $spy = \Mockery::spy('overload:' . RCrmActions::class);

        $GLOBALS['RETAIL_CRM_HISTORY'] = false;

        $result = RetailCrmEvent::paymentSave($event);

        $spy->shouldReceive('apiMethod')->with(
            $api,
            'ordersPaymentCreate',
            'RetailCrmEvent::paymentSave',
            [
                'externalId' => null,
                'order' => ['externalId' => 11],
                'type' => 'testPayment' . Constants::CRM_PART_SUBSTITUTED_PAYMENT_CODE,
                'status' => 'paid',
                'paidAt' => $date
            ],
            null
        )->once();

        $this->assertEquals(true, $result);
    }

    /**
     * @param $history
     *
     * @throws InvalidArgumentException
     * @throws \PHPUnit\Framework\Exception
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @dataProvider paymentDeleteDataProvider
     */
    public function testPaymentDelete($history)
    {
        $event = $this->createMock(\Bitrix\Sale\Payment::class);

        if ($history === true) {
            $GLOBALS['RETAIL_CRM_HISTORY'] = true;
        }

        $result = RetailCrmEvent::paymentDelete($event);

        $this->assertEquals(false, $result);
    }

    /**
     *
     * @throws InvalidArgumentException
     * @throws \PHPUnit\Framework\Exception
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testOrderSave()
    {
        $GLOBALS['RETAIL_CRM_HISTORY'] = true;
        $GLOBALS['RETAILCRM_ORDER_OLD_EVENT'] = false;
        $GLOBALS['RETAILCRM_ORDER_DELETE'] = true;

        $event = $this->createMock(\Bitrix\Main\Event::class);

        $result = RetailCrmEvent::orderSave($event);

        $this->assertEquals(false, $result);
    }

    /**
     * @throws InvalidArgumentException
     * @throws \PHPUnit\Framework\Exception
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testOrderDelete()
    {
        $event = $this->createMock(\Bitrix\Main\Event::class);

        $result = RetailCrmEvent::OrderDelete($event);

        $this->assertEquals(true, $GLOBALS['RETAILCRM_ORDER_DELETE']);
    }

    /**
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testOnUpdateOrder()
    {
        $arFields = [
            'CANCELED' => 'Y',
            'BASKET_ITEMS' => [],
            'ORDER_PROP' => []
        ];

        RetailCrmEvent::OnUpdateOrder(1, $arFields);

        $this->assertEquals(true, $GLOBALS['RETAILCRM_ORDER_OLD_EVENT']);
        $this->assertEquals(true, $GLOBALS['ORDER_DELETE_USER_ADMIN']);
    }

    /**
     * @return array
     */
    public function userUpdateDataProvider()
    {
        return [
            [
                'history' => true,
                'emptyData' => false
            ],
            [
                'history' => false,
                'emptyData' => true
            ]
        ];
    }

    /**
     * @return array
     */
    public function paymentSaveDataProvider()
    {
        return [
            [
                'history' => true,
                'new' => false
            ],
            [
                'history' => false,
                'new' => true
            ]
        ];
    }

    /**
     * @return array
     */
    public function paymentDeleteDataProvider()
    {
        return [
            [
                'history' => true,
            ],
            [
                'history' => false,
            ],
            [
                'history' => false,
            ]
        ];
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $GLOBALS['RETAIL_CRM_HISTORY'] = false;
        $GLOBALS['RETAILCRM_ORDER_OLD_EVENT'] = true;
        $GLOBALS['RETAILCRM_ORDER_DELETE'] = false;
    }
}
