<?php

use Intaro\RetailCrm\Component\Json\Deserializer;
use Intaro\RetailCrm\Component\Json\Serializer;
use Intaro\RetailCrm\Model\Api\OrderProduct;
use Intaro\RetailCrm\Model\Api\Response\Loyalty\LoyaltyCalculateResponse;
use Intaro\RetailCrm\Model\Api\SerializedLoyaltyOrder;
use Intaro\RetailCrm\Model\Api\SerializedOrderProductOffer;
use Intaro\RetailCrm\Service\LoyaltyService;

class LoyaltyCalculationTest extends \PHPUnit\Framework\TestCase
{
    public function testMapsReorderedItems(): void
    {
        $response = new LoyaltyCalculateResponse();
        $response->success = true;
        $response->order = new SerializedLoyaltyOrder();
        $response->order->items = [];

        foreach (['17', '16'] as $basketId) {
            $item = new OrderProduct();
            $item->externalIds = [];
            $item->offer = new SerializedOrderProductOffer();
            $item->offer->externalId = $basketId;
            $response->order->items[] = $item;
        }

        $client = new class($response) {
            private $response;

            public function __construct($response)
            {
                $this->response = $response;
            }

            public function loyaltyCalculate($request)
            {
                return $this->response;
            }
        };
        $service = new LoyaltyService();
        $property = new ReflectionProperty(LoyaltyService::class, 'client');
        $property->setAccessible(true);
        $property->setValue($service, $client);
        $items = [];

        foreach ([16, 17] as $basketId) {
            $items[] = [
                'ID' => $basketId,
                'XML_ID' => 'test-' . $basketId,
                'BASE_PRICE' => 200,
                'PRICE' => 200,
                'QUANTITY' => 1,
                'PRICE_TYPE_ID' => 1,
            ];
        }

        $calculated = $service->getLoyaltyCalculate($items, 100);

        self::assertSame('17', $calculated->order->items[0]->externalIds[0]->value);
        self::assertSame('16', $calculated->order->items[1]->externalIds[0]->value);
        self::assertSame('bitrixBasketId', $calculated->order->items[0]->externalIds[0]->code);
    }

    public function testCheckoutWithoutAccount(): void
    {
        $order = \Bitrix\Sale\Order::create('s1', 1, 'RUB');
        $basket = \Bitrix\Sale\Basket::create('s1');
        $item = $basket->createItem('', 1);
        $item->setFields([
            'NAME' => 'Test product',
            'QUANTITY' => 1,
            'CURRENCY' => 'RUB',
            'LID' => 's1',
            'BASE_PRICE' => 2699,
            'PRICE' => 2199,
            'DISCOUNT_PRICE' => 500,
            'CUSTOM_PRICE' => 'Y',
        ]);
        $order->setBasket($basket);
        $result = [
            'BASKET_ITEMS' => [['ID' => $item->getBasketCode()]],
            'GRID' => ['ROWS' => [$item->getBasketCode() => ['data' => []]]],
            'JS_DATA' => ['TOTAL' => []],
        ];
        $method = new ReflectionMethod(
            \Intaro\RetailCrm\Component\Handlers\EventsHandlers::class,
            'refreshCheckoutData'
        );
        $method->setAccessible(true);
        $method->invokeArgs(null, [$order, &$result]);

        self::assertEquals(2199, $result['JS_DATA']['TOTAL']['ORDER_PRICE']);
        self::assertEquals(500, $result['JS_DATA']['TOTAL']['DISCOUNT_PRICE']);
        self::assertCount(1, $result['JS_DATA']['GRID']['ROWS']);
    }

    public function testReadsOfferId(): void
    {
        $item = new OrderProduct();
        $item->offer = new SerializedOrderProductOffer();
        $item->offer->externalId = '16';
        $rawItem = Serializer::serializeArray($item);
        unset($rawItem['externalIds']);
        $rawItem['externalIds'] = [];
        $parsed = Deserializer::deserializeArray($rawItem, OrderProduct::class);

        self::assertSame('16', $parsed->offer->externalId);
    }
}
