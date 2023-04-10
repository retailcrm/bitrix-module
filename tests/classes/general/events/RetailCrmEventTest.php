<?php

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
     * @param $v5
     * @param $new
     *
     * @throws InvalidArgumentException
     * @throws \PHPUnit\Framework\Exception
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \PHPUnit\Framework\MockObject\RuntimeException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @dataProvider paymentSaveDataProvider
     */
    public function testPaymentSave($history, $v5, $new)
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

        if ($v5 === false) {
            COption::SetOptionString('intaro.retailcrm', 'api_version', 'v4');
        }

        $result = RetailCrmEvent::paymentSave($event);

        $this->assertEquals(false, $result);
    }

    /**
     * @param $history
     * @param $v5
     *
     * @throws InvalidArgumentException
     * @throws \PHPUnit\Framework\Exception
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     *
     * @dataProvider paymentDeleteDataProvider
     */
    public function testPaymentDelete($history, $v5)
    {
        $event = $this->createMock(\Bitrix\Sale\Payment::class);

        if ($history === true) {
            $GLOBALS['RETAIL_CRM_HISTORY'] = true;
        }

        if ($v5 === false) {
            COption::SetOptionString('intaro.retailcrm', 'api_version', 'v4');
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

        $result = RetailCrmEvent::OnUpdateOrder(1, $arFields);

        $this->assertEquals(true, $GLOBALS['RETAILCRM_ORDER_OLD_EVENT']);

        $this->assertEquals(true, $GLOBALS['ORDER_DELETE_USER_ADMIN']);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testSetBasket(): void
    {
        $arBasket = $this->getBasket();
        $crmBasket = $this->getCrmCart();

        $actionsMock = Mockery::mock('alias:' . RCrmActions::class);

        $actionsMock->shouldReceive('apiMethod')->withAnyArgs()->andReturn($crmBasket, ['success' => true]);

        $result = RetailCrmCart::interactionCart($arBasket);

        self::assertTrue($result['success']);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testClearBasket(): void
    {
        $arBasket = ['LID' => 's1', 'USER_ID' => '1'];
        $crmBasket = $this->getCrmCart();

        $actionsMock = Mockery::mock('alias:' . RCrmActions::class);

        $actionsMock->shouldReceive('apiMethod')->withAnyArgs()->andReturn($crmBasket, ['success' => true]);

        $result = RetailCrmCart::interactionCart($arBasket);

        self::assertTrue($result['success']);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testIgnoreChangeBasket()
    {
        $arBasket = ['LID' => 's1', 'USER_ID' => '1'];
        $crmBasket = [];

        $actionsMock = Mockery::mock('alias:' . RCrmActions::class);

        $actionsMock->shouldReceive('apiMethod')->withAnyArgs()->andReturn($crmBasket);

        $result = RetailCrmCart::interactionCart($arBasket);

        self::assertNull($result);
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
                'v5' => true,
                'new' => false
            ],
            [
                'history' => false,
                'v5' => false,
                'new' => false
            ],
            [
                'history' => false,
                'v5' => true,
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
                'v5' => true
            ],
            [
                'history' => false,
                'v5' => false
            ],
            [
                'history' => false,
                'v5' => true
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

    /**
     * @return array
     */
    public function getBasket(): array
    {
        return [
            'LID' => 's1',
            'USER_ID' => '1',
            'BASKET' => [
                [
                    'QUANTITY' => 2,
                    'PRICE' => 100,
                    'DATE_INSERT' => new DateTime('now'),
                    'DATE_UPDATE' => new DateTime('now'),
                    'PRODUCT_ID' => '10'
                ],
                [
                    'QUANTITY' => 1,
                    'PRICE' => 300,
                    'DATE_INSERT' => new DateTime('now'),
                    'DATE_UPDATE' => new DateTime('now'),
                    'PRODUCT_ID' => '2'
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function getCrmCart(): array
    {
        return [
            'cart' => [
                'items' => 'items'
            ]
        ];
    }
}
