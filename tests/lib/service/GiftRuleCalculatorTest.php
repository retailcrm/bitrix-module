<?php

use Bitrix\Sale\Order;
use Intaro\RetailCrm\Service\GiftRuleCalculator;

class GiftRuleCalculatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider thresholdCases
     */
    public function testGiftThreshold(float $bonuses, bool $expected): void
    {
        $calculator = GiftRuleCalculator::buildForGifts(Order::create('s1', 1, 'RUB'), [
            ['basketCode' => 'gift'],
        ]);
        $orderData = [
            'BASKET_ITEMS' => [
                'product' => ['PRICE' => 2699 - $bonuses, 'QUANTITY' => 1],
                'gift' => ['PRICE' => 200, 'QUANTITY' => 1],
            ],
        ];
        $fields = [
            'orderData' => $orderData,
            'currentStep' => ['cacheIndex' => 0],
            'saleDiscountCacheKey' => 'test',
            'saleDiscountCache' => [
                'test' => [[
                    'UNPACK' => 'condition',
                    'EXECUTE_UNPACK' => static function ($order): bool {
                        return CSaleBasketFilter::AmountFilter($order, '') >= 2500;
                    },
                ]],
            ],
        ];

        foreach ($fields as $name => $value) {
            $property = new ReflectionProperty($calculator, $name);
            $property->setAccessible(true);
            $property->setValue($calculator, $value);
        }

        $method = new ReflectionMethod($calculator, 'checkDiscountConditions');
        $method->setAccessible(true);

        self::assertSame($expected, $method->invoke($calculator));
        $property = new ReflectionProperty($calculator, 'orderData');
        $property->setAccessible(true);
        self::assertSame($orderData, $property->getValue($calculator));
    }

    public function thresholdCases(): array
    {
        return [
            [0.0, true],
            [100.0, true],
            [198.99, true],
            [199.0, true],
            [199.01, false],
            [200.0, false],
            [300.0, false],
            [400.0, false],
            [500.0, false],
        ];
    }
}
