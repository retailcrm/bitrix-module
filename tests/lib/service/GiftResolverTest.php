<?php

use Intaro\RetailCrm\Service\GiftResolver;
use Intaro\RetailCrm\Component\ConfigProvider;
use Intaro\RetailCrm\Component\Handlers\EventsHandlers;

class GiftResolverTest extends BitrixTestCase
{
    public function tearDown(): void
    {
        ConfigProvider::setLoyaltyBonusesForBasketRules('N');

        parent::tearDown();
    }

    public function testResolveGifts()
    {
        $resolver = new GiftResolver();

        $gifts = $resolver->resolveApplyResult([
            'PRICES' => [
                'BASKET' => [
                    'gift-item' => [
                        'BASE_PRICE' => 200,
                        'PRICE' => 0,
                        'DISCOUNT' => 200,
                    ],
                    'free-item' => [
                        'BASE_PRICE' => 0,
                        'PRICE' => 0,
                        'DISCOUNT' => 0,
                    ],
                    'partial-discount-item' => [
                        'BASE_PRICE' => 200,
                        'PRICE' => 0,
                        'DISCOUNT' => 100,
                    ],
                    'unrelated-rule-item' => [
                        'BASE_PRICE' => 200,
                        'PRICE' => 0,
                        'DISCOUNT' => 200,
                    ],
                    'ordinary-discount-item' => [
                        'BASE_PRICE' => 200,
                        'PRICE' => 0,
                        'DISCOUNT' => 200,
                    ],
                ],
            ],
            'RESULT' => [
                'BASKET' => [
                    'gift-item' => [
                        ['DISCOUNT_ID' => 'discount-1'],
                    ],
                    'free-item' => [
                        ['DISCOUNT_ID' => 'discount-1'],
                    ],
                    'partial-discount-item' => [
                        ['DISCOUNT_ID' => 'discount-1'],
                    ],
                    'unrelated-rule-item' => [
                        ['DISCOUNT_ID' => 'discount-2'],
                    ],
                    'ordinary-discount-item' => [
                        ['DISCOUNT_ID' => 'discount-3'],
                    ],
                ],
            ],
            'DISCOUNT_LIST' => [
                [
                    'ID' => 'discount-1',
                    'REAL_DISCOUNT_ID' => 42,
                ],
                [
                    'ID' => 'discount-3',
                    'REAL_DISCOUNT_ID' => 43,
                ],
            ],
            'FULL_DISCOUNT_LIST' => [
                42 => [
                    'ACTIONS_LIST' => [
                        'CLASS_ID' => 'CondGroup',
                        'CHILDREN' => [
                            ['CLASS_ID' => 'GiftCondGroup'],
                        ],
                    ],
                ],
                43 => [
                    'ACTIONS_LIST' => [
                        'CLASS_ID' => 'CondGroup',
                        'CHILDREN' => [
                            ['CLASS_ID' => 'ActSaleBasket'],
                        ],
                    ],
                ],
            ],
        ]);

        self::assertSame([
            'gift-item' => [
                'basketCode' => 'gift-item',
                'discountId' => 42,
                'discountPercent' => 100.0,
            ],
        ], $gifts);
    }

    public function testActionsArrayGift()
    {
        $result = $this->getApplyResult([
            'BASE_PRICE' => 200,
            'PRICE' => 0,
            'DISCOUNT' => 200,
        ]);
        $result['FULL_DISCOUNT_LIST'][42]['ACTIONS'] = $result['FULL_DISCOUNT_LIST'][42]['ACTIONS_LIST'];
        unset($result['FULL_DISCOUNT_LIST'][42]['ACTIONS_LIST']);

        self::assertSame('gift', (new GiftResolver())->resolveApplyResult($result)['gift']['basketCode']);
    }

    public function testFindRemovedGifts()
    {
        $resolver = new GiftResolver();

        $removedGifts = $resolver->getRemovedGifts([
            'gift-that-stayed' => ['discountId' => 42],
            'gift-that-lost-rule' => ['discountId' => 42],
            'gift-with-replaced-rule' => ['discountId' => 42],
        ], [
            'gift-that-stayed' => ['discountId' => 42],
            'gift-with-replaced-rule' => ['discountId' => 43],
        ]);

        self::assertSame([
            'gift-that-lost-rule' => ['discountId' => 42],
            'gift-with-replaced-rule' => ['discountId' => 42],
        ], $removedGifts);
    }

    public function testBonusesRemoveGift()
    {
        ConfigProvider::setLoyaltyBonusesForBasketRules('Y');
        $resolver = new GiftResolver();

        self::assertTrue($this->isBasketRuleMode());
        self::assertSame([
            'gift' => ['discountId' => 42],
        ], $resolver->getRemovedGifts([
            'gift' => ['discountId' => 42],
        ], []));
    }

    public function testThresholdKeepsGift()
    {
        ConfigProvider::setLoyaltyBonusesForBasketRules('Y');
        $resolver = new GiftResolver();

        self::assertTrue($this->isBasketRuleMode());
        self::assertSame([], $resolver->getRemovedGifts([
            'gift' => ['discountId' => 42],
        ], [
            'gift' => ['discountId' => 42],
        ]));
    }

    public function testFreeItemStays()
    {
        self::assertSame([], (new GiftResolver())->resolveApplyResult($this->getApplyResult([
            'BASE_PRICE' => 0,
            'PRICE' => 0,
            'DISCOUNT' => 0,
        ])));
    }

    public function testDiscountItemStays()
    {
        $result = $this->getApplyResult([
            'BASE_PRICE' => 200,
            'PRICE' => 0,
            'DISCOUNT' => 200,
        ]);
        $result['FULL_DISCOUNT_LIST'][42]['ACTIONS_LIST']['CHILDREN'][0]['CLASS_ID'] = 'ActSaleBasket';

        self::assertSame([], (new GiftResolver())->resolveApplyResult($result));
    }

    public function testExternalGiftStays()
    {
        $result = $this->getApplyResult([
            'BASE_PRICE' => 200,
            'PRICE' => 0,
            'DISCOUNT' => 200,
        ]);
        $result['RESULT']['BASKET']['gift'] = [];

        self::assertSame([], (new GiftResolver())->resolveApplyResult($result));
    }

    private function getApplyResult(array $priceData): array
    {
        return [
            'PRICES' => [
                'BASKET' => ['gift' => $priceData],
            ],
            'RESULT' => [
                'BASKET' => [
                    'gift' => [
                        ['DISCOUNT_ID' => 'discount-1'],
                    ],
                ],
            ],
            'DISCOUNT_LIST' => [
                [
                    'ID' => 'discount-1',
                    'REAL_DISCOUNT_ID' => 42,
                ],
            ],
            'FULL_DISCOUNT_LIST' => [
                42 => [
                    'ACTIONS_LIST' => [
                        'CLASS_ID' => 'CondGroup',
                        'CHILDREN' => [
                            ['CLASS_ID' => 'GiftCondGroup'],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function isBasketRuleMode(): bool
    {
        $reflectionMethod = new \ReflectionMethod(EventsHandlers::class, 'shouldUseBonusesForBasketRules');
        $reflectionMethod->setAccessible(true);

        return $reflectionMethod->invoke(null, 100.0);
    }
}
