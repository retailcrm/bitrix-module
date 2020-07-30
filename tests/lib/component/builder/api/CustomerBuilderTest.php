<?php

namespace Tests\Intaro\RetailCrm\Component\Builder\Api;

use Bitrix\Main\Event;
use Bitrix\Main\EventManager;
use Bitrix\Main\Type\DateTime;
use Intaro\RetailCrm\Component\Builder\Api\CustomerBuilder;
use Intaro\RetailCrm\Component\CollectorCookieExtractor;
use Intaro\RetailCrm\Component\ConfigProvider;
use Intaro\RetailCrm\Component\Constants;
use Intaro\RetailCrm\Component\Converter\DateTimeConverter;
use Intaro\RetailCrm\Component\Events;
use Intaro\RetailCrm\Component\ServiceLocator;
use Intaro\RetailCrm\Model\Api\Address;
use Intaro\RetailCrm\Model\Api\Customer;
use Intaro\RetailCrm\Model\Bitrix\User;
use PHPUnit\Framework\Constraint\IsType;
use PHPUnit\Framework\TestCase;
use Tests\Intaro\RetailCrm\Helpers;

class CustomerBuilderTest extends TestCase
{
    private const COOKIE_DATA = 'rcCookie';

    /** @var \Intaro\RetailCrm\Component\CollectorCookieExtractor */
    private $originalCookieCollector;

    protected function setUp(): void
    {
        $this->originalCookieCollector = ServiceLocator::get(CollectorCookieExtractor::class);

        $cookieExtractorMock = $this->getMockBuilder(CollectorCookieExtractor::class)
            ->setMethods(['extractCookie'])
            ->getMock();

        $cookieExtractorMock
            ->method('extractCookie')
            ->withAnyParameters()
            ->willReturn(static::COOKIE_DATA);

        ServiceLocator::set(CollectorCookieExtractor::class, $cookieExtractorMock);

        Helpers::setConfigProperty('contragentTypes', [
            'individual' => 'individual'
        ]);
    }

    protected function tearDown(): void
    {
        ServiceLocator::set(CollectorCookieExtractor::class, $this->originalCookieCollector);
    }

    /**
     * @throws \Intaro\RetailCrm\Component\Builder\Exception\BuilderException
     * @var User $entity
     * @dataProvider userData
     */
    public function testBuild($entity): void
    {
        $this->assertTrue($entity instanceof User);

        $builder = new CustomerBuilder();
        $result = $builder
            ->setAttachDaemonCollectorId(true)
            ->setPersonTypeId('individual')
            ->setUser($entity)
            ->build()
            ->getResult();

        $this->assertTrue($result instanceof Customer);
        $this->assertEquals($entity->getId(), $result->externalId);
        $this->assertEquals($entity->getEmail(), $result->email);
        $this->assertEquals(DateTimeConverter::bitrixToPhp($entity->getDateRegister()), $result->createdAt);
        $this->assertFalse($result->subscribed);
        $this->assertEquals($entity->getName(), $result->firstName);
        $this->assertEquals($entity->getLastName(), $result->lastName);
        $this->assertEquals($entity->getSecondName(), $result->patronymic);
        $this->assertCount(2, $result->phones);
        $this->assertEquals($entity->getPersonalPhone(), $result->phones[0]->number);
        $this->assertEquals($entity->getWorkPhone(), $result->phones[1]->number);
        $this->assertTrue($result->address instanceof Address);
        $this->assertEquals($entity->getPersonalCity(), $result->address->city);
        $this->assertEquals($entity->getPersonalStreet(), $result->address->text);
        $this->assertEquals($entity->getPersonalZip(), $result->address->index);
        $this->assertEquals(static::COOKIE_DATA, $result->browserId);

    }

    /**
     * @throws \Intaro\RetailCrm\Component\Builder\Exception\BuilderException
     * @var User $entity
     * @dataProvider userData
     */
    public function testCustomizedBuild($entity): void
    {
        $this->assertTrue($entity instanceof User);

        EventManager::getInstance()->addEventHandler(
            Constants::MODULE_ID,
            Events::API_CUSTOMER_BUILDER_GET_RESULT,
            static function (Event $event) {
                $event->getParameter('customer')->externalId = 'replaced';
            }
        );

        $builder = new CustomerBuilder();
        $result = $builder
            ->setPersonTypeId('individual')
            ->setUser($entity)
            ->build()
            ->getResult();

        $this->assertTrue($result instanceof Customer);
        $this->assertEquals('replaced', $result->externalId);
        $this->assertEquals(static::COOKIE_DATA, $result->browserId);
    }

    /**
     * @return \Intaro\RetailCrm\Model\Bitrix\User[][]
     */
    public function userData()
    {
        $entity = new User();
        $entity->setId(21);
        $entity->setEmail('vovka@narod.ru');
        $entity->setDateRegister(DateTime::createFromPhp(new \DateTime()));
        $entity->setName('First');
        $entity->setLastName('Last');
        $entity->setSecondName('Second');
        $entity->setPersonalPhone('88005553535');
        $entity->setWorkPhone('88005553536');
        $entity->setPersonalCity('city');
        $entity->setPersonalStreet('street');
        $entity->setPersonalZip('344000');

        return [[$entity]];
    }
}
