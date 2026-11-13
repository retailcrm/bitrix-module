<?php

use Intaro\RetailCrm\Component\Handlers\EventsHandlers;
use Intaro\RetailCrm\Component\ConfigProvider;
use Intaro\RetailCrm\Model\Api\Response\Loyalty\LoyaltyCalculateResponse;
use Intaro\RetailCrm\Service\LoyaltyService;
use Bitrix\Main\EventManager;
use Bitrix\Sale\Order;

class EventsHandlersTest extends \BitrixTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        COption::SetOptionString('intaro.retailcrm', 'api_version', 'v5');
        CModule::IncludeModule('intaro.retailcrm');
        ConfigProvider::setLoyaltyBonusesForBasketRules('N');
    }

    public function tearDown(): void
    {
        ConfigProvider::setLoyaltyBonusesForBasketRules('N');

        parent::tearDown();
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testSavedHandlerStops()
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

    public function testDisabledKeepsGift()
    {
        ConfigProvider::setLoyaltyBonusesForBasketRules('N');

        self::assertFalse($this->callHandlerMethod('shouldUseBonusesForBasketRules', [100.0]));
    }

    public function testErrorKeepsBasket()
    {
        $discount = new class {
            public function getApplyResult(bool $extended): array
            {
                return [];
            }
        };
        $order = $this->createMock(Order::class);
        $order->method('getDiscount')->willReturn($discount);
        $order->expects(self::never())->method('getBasket');

        $response = new LoyaltyCalculateResponse();
        $response->success = false;
        $service = $this->getMockBuilder(LoyaltyService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getLoyaltyCalculate'])
            ->getMock();
        $service->method('getLoyaltyCalculate')->willReturn($response);

        self::assertNull($this->callHandlerMethod('recalculateOrderForBasketRules', [$order, [], $service, 100.0]));
    }

    public function testApplyDiffers()
    {
        $differences = $this->callHandlerMethod('findLoyaltyPriceDifferences', [
            ['12' => ['price' => 900.0, 'discountPrice' => 100.0]],
            ['12' => ['price' => 850.0, 'discountPrice' => 150.0]],
        ]);

        self::assertSame([
            [
                'bitrixBasketId' => 12,
                'preliminary' => ['price' => 900.0, 'discountPrice' => 100.0],
                'actual' => ['price' => 850.0, 'discountPrice' => 150.0],
            ],
        ], $differences);
    }

    private function callHandlerMethod(string $method, array $arguments)
    {
        $reflectionMethod = new \ReflectionMethod(EventsHandlers::class, $method);
        $reflectionMethod->setAccessible(true);

        return $reflectionMethod->invokeArgs(null, $arguments);
    }
}
