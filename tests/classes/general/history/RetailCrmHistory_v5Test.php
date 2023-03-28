<?php

use Bitrix\Sale\Order;
use Bitrix\Currency\CurrencyManager;
/**
 * Class RetailCrmHistory_v5Test
 */
class RetailCrmHistory_v5Test extends \BitrixTestCase
{
    /**
     * setUp method
     */
    public function setUp(): void
    {
        parent::setUp();

        COption::SetOptionString('intaro.retailcrm', 'api_version', 'v5');
        CModule::IncludeModule('intaro.retailcrm');
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testRegisterUser(): void
    {
        $actionsMock = Mockery::mock('alias:' . RCrmActions::class);
        $actionsMock->shouldReceive('apiMethod')->withAnyArgs()->andReturn($this->getCustomerHistory());

        $this->deleteTestingUser();
        RetailCrmHistory::customerHistory();

        $dbUser = CUser::GetList(($by = 'ID'), ($sort = 'DESC'), array('=EMAIL' => 'testbitrixreg@gmail.com'));

        $this->assertEquals(1, $dbUser->SelectedRowsCount());
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testUnregisterDoubleUser(): void
    {
        $this->deleteTestingUser();

        $user = new CUser;
        $arFields = [
            'NAME' => 'test',
            'LAST_NAME' => 'test',
            'LOGIN' => 'test',
            'EMAIL' => 'testbitrixreg@gmail.com',
            'LID' => 'ru',
            'ACTIVE' => 'Y',
            'GROUP_ID' => [10, 11],
            'PASSWORD' => '123456',
            'CONFIRM_PASSWORD' => '123456',
        ];

        $ID = $user->Add($arFields);

        $this->assertTrue((int)$ID > 0);

        $actionsMock = Mockery::mock('alias:' . RCrmActions::class);
        $actionsMock->shouldReceive('apiMethod')->withAnyArgs()->andReturn($this->getCustomerHistory());

        RetailCrmHistory::customerHistory();

        $dbUser = CUser::GetList(($by = 'ID'), ($sort = 'DESC'), array('=EMAIL' => 'testbitrixreg@gmail.com'));

        $this->assertEquals(1, $dbUser->SelectedRowsCount());

        $user = new CUser;
        $arFields = [
            'NAME' => 'test2',
            'LAST_NAME' => 'test2',
            'LOGIN' => 'test2',
            'EMAIL' => 'testbitrixreg@gmail.com',
            'LID' => 'ru',
            'ACTIVE' => 'Y',
            'GROUP_ID' => [10, 11],
            'PASSWORD' => '123456',
            'CONFIRM_PASSWORD' => '123456',
        ];

        $ID = $user->Add($arFields);

        $this->assertTrue((int)$ID > 0);

        RetailCrmHistory::customerHistory();

        $dbUser = CUser::GetList(($by = 'ID'), ($sort = 'DESC'), array('=EMAIL' => 'testbitrixreg@gmail.com'));

        $this->assertEquals(2, $dbUser->SelectedRowsCount());
    }

    public function testSetPasswordUser(): void
    {
        $customers = $this->getCustomers();

        foreach ($customers as $customer) {
            $customerBuilder = new CustomerBuilder();
            $dbUser = $customer['countRows'];
            $registerNewUser = true;

            if (!empty($customer['email'])) {
                switch ($dbUser) {
                    case 0:
                        $login = $customer['email'];
                        $customerBuilder->setLogin($login);
                        break;
                    case 1:
                        $this->assertContains($customer['id'], [1]);
                        $registerNewUser = false;
                        break;
                    default:
                        $login = uniqid('user_' . time()) . '@example.com';
                        $customerBuilder->setLogin($login);
                        break;
                }
            }

            if ($registerNewUser === true) {
                $customerBuilder->buildPassword();
                $array = $customerBuilder->getCustomer()->getObjectToArray();
                $this->assertNotEmpty($array["PASSWORD"]);
            }
        }
    }

    public function testShipmentItemReset(): void
    {
        $shipmentCollection = $this->createMock(\Bitrix\Sale\ShipmentCollection::class);
        $shipmentCollection->method('resetCollection')
            ->willReturn(true);
        $shipmentCollection->method('tryUnreserve')
            ->willReturn(true);
        $shipmentCollection->method('tryReserve')
            ->willReturn(true);

        $shipment = $this->createMock(\Bitrix\Sale\Shipment::class);
        $shipment->method('getShipmentItemCollection')
            ->willReturn($shipmentCollection);
        $shipment->method('needReservation')
            ->willReturn(true);
        $shipment->method('isShipped')
            ->willReturn(true);
        $shipment->method('isSystem')
            ->willReturn(false);

        $shipmentCollection->method('getIterator')
            ->willReturn(new \ArrayObject([$shipment]));

        $order = $this->createMock(\Bitrix\Sale\Order::class);
        $order->method('getShipmentCollection')
            ->willReturn($shipmentCollection);
        $order->method('getBasket')
            ->willReturn(true);

        $this->assertEquals(null, RetailCrmHistory::shipmentItemReset($order));

        $shipment->method('isShipped')
            ->willReturn(false);

        $this->assertEquals(null, RetailCrmHistory::shipmentItemReset($order));
    }

    public function testSetManager()
    {
        $currency = CurrencyManager::getBaseCurrency();
        $cmsOrder = Order::create('bitrix', 1, $currency);
        $crmManagerId = 123;

        RetailcrmConfigProvider::setUsersMap(['bitrixUserId-1515' => $crmManagerId]);
        RetailCrmHistory::setManager($cmsOrder, ['externalId' => 1, 'managerId' => $crmManagerId]);

        $this->assertEquals(1515, $cmsOrder->getField('RESPONSIBLE_ID'));
    }

    private function deleteTestingUser()
    {
        $dbUser = CUser::GetList(($by = 'ID'), ($sort = 'DESC'), array('=EMAIL' => 'testbitrixreg@gmail.com'));

        if ($dbUser->SelectedRowsCount() > 0) {
            while ($user = $dbUser->Fetch()) {
                CUser::Delete((int)$user['ID']);
            }
        }
    }

    private function getCustomers(): array
    {
        return [
            [
                'email' => 'test@test.ru',
                'id' => 1,
                'countRows' => 1
            ],
            [
                'email' => null,
                'id' => 2,
                'countRows' => 1
            ],
            [
                'email' => 'test@test.ru',
                'id' => 3,
                'countRows' => 2
            ],
            [
                'email' => 'test@test.ru',
                'id' => 4,
                'countRows' => 0
            ],
        ];
    }

    /**
     * @throws JsonException
     */
    private function getCustomerHistory()
    {
        $jsonText = '{"success":true,"generatedAt":"2023-03-27 16:46:46","history":[{"id":6808,"createdAt":"2023-03-27 16:44:46","created":true,"source":"user","user":{"id":13},"field":"id","oldValue":null,"newValue":1821,"customer":{"type":"customer","id":1821,"isContact":false,"createdAt":"2023-03-27 16:44:46","vip":false,"bad":false,"site":"bitrix","customFields":{"reg_api":true},"marginSumm":0,"totalSumm":0,"averageSumm":0,"ordersCount":0,"personalDiscount":0,"cumulativeDiscount":0,"address":{"id":84,"countryIso":"RU"},"segments":[],"firstName":"TestBitrixMan","lastName":"TestBitrixMan","patronymic":"TestBitrixMan","sex":"male","email":"testbitrixreg@gmail.com","phones":[{"number":"89486541252"}]}},{"id":6809,"createdAt":"2023-03-27 16:44:46","source":"code","field":"loyalty_accounts","oldValue":null,"newValue":{"id":312},"customer":{"id":1821,"site":"bitrix"}}],"pagination":{"limit":100,"totalCount":2,"currentPage":1,"totalPageCount":1}}';
       // $jsonText = json_encode($jsonText);
        return json_decode($jsonText, true, 512, JSON_THROW_ON_ERROR);
    }
}