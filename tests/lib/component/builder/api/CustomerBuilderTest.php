<?php

namespace Tests\Intaro\RetailCrm\Component\Builder\Api;

use Bitrix\Main\Event;
use Bitrix\Main\EventManager;
use Intaro\RetailCrm\Component\Builder\Api\CustomerBuilder;
use Intaro\RetailCrm\Service\CollectorCookieExtractor;
use Intaro\RetailCrm\Component\Constants;
use Intaro\RetailCrm\Component\Events;
use Intaro\RetailCrm\Component\ServiceLocator;
use Intaro\RetailCrm\Model\Api\Address;
use Intaro\RetailCrm\Model\Api\Customer;
use Intaro\RetailCrm\Model\Bitrix\User;
use PHPUnit\Framework\TestCase;
use Tests\Intaro\RetailCrm\Helpers;

class CustomerBuilderTest extends TestCase
{
    public function setUp()
    {
        Helpers::setConfigProperty('contragentTypes', [
            'individual' => 'individual'
        ]);
    }

    /**
     * @throws \Intaro\RetailCrm\Component\Builder\Exception\BuilderException
     * @var User $entity
     * @dataProvider userData
     */
    public function testBuild($entity): void
    {
        self::assertTrue($entity instanceof User);

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

        $builder = new CustomerBuilder();
        $result = $builder
            ->setAttachDaemonCollectorId(true)
            ->setPersonTypeId('individual')
            ->setUser($entity)
            ->build()
            ->getResult();

        ServiceLocator::set(CollectorCookieExtractor::class, $originalCookieCollector);

        self::assertTrue($result instanceof Customer);
        self::assertEquals($entity->getId(), $result->externalId);
        self::assertEquals($entity->getEmail(), $result->email);
        self::assertEquals($entity->getDateRegister(), $result->createdAt);
        self::assertFalse($result->subscribed);
        self::assertEquals($entity->getName(), $result->firstName);
        self::assertEquals($entity->getLastName(), $result->lastName);
        self::assertEquals($entity->getSecondName(), $result->patronymic);
        self::assertCount(2, $result->phones);
        self::assertEquals($entity->getPersonalPhone(), $result->phones[0]->number);
        self::assertEquals($entity->getWorkPhone(), $result->phones[1]->number);
        self::assertTrue($result->address instanceof Address);
        self::assertEquals($entity->getPersonalCity(), $result->address->city);
        self::assertEquals($entity->getPersonalStreet(), $result->address->text);
        self::assertEquals($entity->getPersonalZip(), $result->address->index);
        self::assertEquals($cookieData, $result->browserId);

    }

    /**
     * @throws \Intaro\RetailCrm\Component\Builder\Exception\BuilderException
     * @var User $entity
     * @dataProvider userData
     */
    public function testCustomizedBuild($entity): void
    {
        self::assertTrue($entity instanceof User);

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

        self::assertTrue($result instanceof Customer);
        self::assertEquals('replaced', $result->externalId);
    }

    /**
     * @return \Intaro\RetailCrm\Model\Bitrix\User[][]
     */
    public function userData()
    {
        $entity = new User();
        $entity->setId(21);
        $entity->setEmail('vovka@narod.ru');
        $entity->setDateRegister(new \DateTime());
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
