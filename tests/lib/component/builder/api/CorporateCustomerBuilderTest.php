<?php

namespace Tests\Intaro\RetailCrm\Component\Builder\Api;

use Bitrix\Sale\Order;
use Intaro\RetailCrm\Component\Builder\Api\CorporateCustomerBuilder;
use Intaro\RetailCrm\Component\Builder\Exception\BuilderException;
use PHPUnit\Framework\TestCase;
use Tests\Intaro\RetailCrm\Helpers;

class CorporateCustomerBuilderTest extends TestCase
{
    protected function setUp(): void
    {
        Helpers::setConfigProperty('sitesList', ['s1' => 's1']);
        Helpers::setConfigProperty('corporateClientName', 'COMPANY');
        Helpers::setConfigProperty('corporateClientAddress', 'COMPANY_ADR');
        Helpers::setConfigProperty('contragentTypes', [
            'individual',
            'legal-entity'
        ]);
        Helpers::setConfigProperty('legalDetails', [
            'individual',
            'legal-entity'
        ]);
    }

    public function testBuildNoOrder(): void
    {
        $this->expectException(BuilderException::class);
        (new CorporateCustomerBuilder())->build();
    }

    public function testNoCorrespondingContragentType()
    {
        $this->expectException(BuilderException::class);
        $order = Order::create('s1');
        $order->setField('LID', 'unknown_site');
        (new CorporateCustomerBuilder())
            ->setOrder($order)
            ->build();
    }
}
