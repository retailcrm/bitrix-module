<?php

use Intaro\RetailCrm\Service\CurrencyService;

class CurrencyServiceTest extends BitrixTestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }
    public function testValidateCurrency()
    {
        self::assertNotEquals('', CurrencyService::validateCurrency('RUB', 'USD'));
        self::assertNotEquals('', CurrencyService::validateCurrency('USD', 'RUB'));
        self::assertEquals('', CurrencyService::validateCurrency('RUB', 'RUB'));
    }
}
