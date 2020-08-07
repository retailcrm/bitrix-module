<?php

namespace Tests\Intaro\RetailCrm\Component\Builder\Api;

use Bitrix\Main\Type\DateTime;
use Bitrix\Sale\Order;
use Intaro\RetailCrm\Component\Builder\Api\CorporateCustomerBuilder;
use Intaro\RetailCrm\Component\Builder\Exception\BuilderException;
use Intaro\RetailCrm\Component\ConfigProvider;
use Intaro\RetailCrm\Component\ServiceLocator;
use Intaro\RetailCrm\Model\Bitrix\User;
use Intaro\RetailCrm\Service\CollectorCookieExtractor;
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

    public function testBuild()
    {
        $cookieData = 'rcCookie';
        $originalCookieCollector = ServiceLocator::get(CollectorCookieExtractor::class);

        $cookieExtractorMock = $this->getMockBuilder(CollectorCookieExtractor::class)
            ->setMethods(['extractCookie'])
            ->getMock();

        $cookieExtractorMock
            ->method('extractCookie')
            ->withAnyParameters()
            ->willReturn($cookieData);

        ServiceLocator::set(CollectorCookieExtractor::class, $cookieExtractorMock);

        $userLogin = uniqid('testuser_', false);
        $user = new User();
        $user->setLogin($userLogin);
        $user->setName($userLogin);
        $user->setPassword($userLogin);
        $user->setEmail($userLogin . '@example.com');
        $user->setWorkCompany('WorkCompany');
        $saveResult = $user->save();
        self::assertTrue($saveResult->isSuccess(), implode(', ', $saveResult->getErrorMessages()));
        self::assertNotNull($user->getId(), implode(', ', $saveResult->getErrorMessages()));

        $order = Order::create('s1', $user->getId());
        $order->setField('DATE_INSERT', new DateTime());
        $order->setPersonTypeId(array_flip(ConfigProvider::getContragentTypes())['legal-entity']);
        $saveResult = $order->save();
        self::assertTrue($saveResult->isSuccess(), implode(', ', $saveResult->getErrorMessages()));
        self::assertNotNull($order->getId(), implode(', ', $saveResult->getErrorMessages()));

        $customer = (new CorporateCustomerBuilder())
            ->reset()
            ->setMainCompany(true)
            ->setMainContact(true)
            ->setAttachDaemonCollectorId(true)
            ->setBuildChildEntities(true)
            ->setOrder($order)
            ->build()
            ->getResult();

        ServiceLocator::set(CollectorCookieExtractor::class, $originalCookieCollector);

        self::assertNotEmpty($customer);
    }
}
