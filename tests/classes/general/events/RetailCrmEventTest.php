<?php

/**
 * Class RetailCrmEventTest
 */
class RetailCrmEventTest extends PHPUnit\Framework\TestCase
{
    /**
     * @var RetailCrmEvent
     */
    private $retailcrmEvent;

    public function setUp()
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

        $result = $this->retailcrmEvent->OnAfterUserUpdate($arFields);

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

        $result = $this->retailcrmEvent->paymentSave($event);

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

        $result = $this->retailcrmEvent->paymentDelete($event);

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

        $result = $this->retailcrmEvent->OrderDelete($event);

        $this->assertEquals(null, $result);
    }

    /**
     * @throws \PHPUnit\Framework\ExpectationFailedException
     * @throws \SebastianBergmann\RecursionContext\InvalidArgumentException
     */
    public function testOnUpdateOrder()
    {
        $arFields = [];

        $result = $this->retailcrmEvent->OnUpdateOrder(1, $arFields);

        $this->assertEquals(null, $result);
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

    public function tearDown()
    {
        parent::tearDown();

        $GLOBALS['RETAIL_CRM_HISTORY'] = false;
        $GLOBALS['RETAILCRM_ORDER_OLD_EVENT'] = true;
        $GLOBALS['RETAILCRM_ORDER_DELETE'] = false;
    }
}
