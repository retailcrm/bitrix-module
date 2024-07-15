<?php

namespace classes\general\cart;

use DateTime;
use Mockery;
use PHPUnit\Framework\TestCase;
use RCrmActions;
use RetailCrmCart;

class RetailCrmCartTest extends TestCase
{
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

        $result = RetailCrmCart::handlerCart($arBasket);

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

        $result = RetailCrmCart::handlerCart($arBasket);

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

        $result = RetailCrmCart::handlerCart($arBasket);

        self::assertNull($result);
    }

    public function testGenerateCartLink()
    {
        var_dump(RetailCrmCart::generateCartLink());
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
