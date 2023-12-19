<?php

use Bitrix\Sale\Order;
use Bitrix\Currency\CurrencyManager;
use RetailCrm\Response\ApiResponse;
use Tests\Intaro\RetailCrm\DataHistory;
use CUserTypeEntity;

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
        RetailcrmConfigProvider::setCustomFieldsStatus('Y');
        RetailcrmConfigProvider::setMatchedUserFields(
            ['UF_FIELD_USER_1' => 'custom_1', 'UF_FIELD_USER_2' => 'custom_2']
        );

        $this->registerCustomFields();

        $actionsMock = Mockery::mock('alias:' . RCrmActions::class);
        $apiResponse = new ApiResponse(200, DataHistory::get_history_data_new_customer());

        $actionsMock->shouldReceive('apiMethod')->withAnyArgs()->andReturn($apiResponse);
        $actionsMock->shouldReceive('getTypeUserField')->withAnyArgs()->andReturn([
            'UF_FIELD_USER_1' => 'string', 'UF_FIELD_USER_2' => 'string'
        ]);
        $actionsMock->shouldReceive('convertCrmValueToCmsField')->byDefault();

        $this->deleteTestingUser();
        RetailCrmHistory::customerHistory();

        $dbUser = CUser::GetList(($by = 'ID'), ($sort = 'DESC'), ['=EMAIL' => 'testbitrixreg@gmail.com']);

        $this->assertEquals(1, $dbUser->SelectedRowsCount());

        RetailcrmConfigProvider::setCustomFieldsStatus('N');
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

        $this->assertTrue((int) $ID > 0);

        $actionsMock = Mockery::mock('alias:' . RCrmActions::class);
        $apiResponse = new ApiResponse(200, DataHistory::get_history_data_new_customer());

        $actionsMock->shouldReceive('apiMethod')->withAnyArgs()->andReturn($apiResponse);

        RetailCrmHistory::customerHistory();

        $dbUser = CUser::GetList(($by = 'ID'), ($sort = 'DESC'), ['=EMAIL' => 'testbitrixreg@gmail.com']);

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

        $this->assertTrue((int) $ID > 0);

        RetailCrmHistory::customerHistory();

        $dbUser = CUser::GetList(($by = 'ID'), ($sort = 'DESC'), ['=EMAIL' => 'testbitrixreg@gmail.com']);

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
        $cmsOrder->setPersonTypeId('bitrixType');
        $crmManagerId = 123;

        RetailcrmConfigProvider::setUsersMap(['bitrixUserId-1515' => $crmManagerId]);
        RetailCrmHistory::setManager($cmsOrder, ['externalId' => 1, 'managerId' => $crmManagerId]);

        $this->assertEquals(1515, $cmsOrder->getField('RESPONSIBLE_ID'));
    }

    private function deleteTestingUser(): void
    {
        $dbUser = CUser::GetList(($by = 'ID'), ($sort = 'DESC'), ['=EMAIL' => 'testbitrixreg@gmail.com']);

        if ($dbUser->SelectedRowsCount() > 0) {
            while ($user = $dbUser->Fetch()) {
                CUser::Delete((int) $user['ID']);
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

    private function registerCustomFields()
    {
        $oUserTypeEntity    = new CUserTypeEntity();
        $userField = [
            'ENTITY_ID' => 'USER',
            'FIELD_NAME' => 'UF_FIELD_USER_1',
            'USER_TYPE_ID' => 'string',
            'MULTIPLE' => 'N',
            'MANDATORY' => 'N',
            'EDIT_FROM_LABEL' => ['ru' => 'TEST 1']
        ];

        $dbRes = CUserTypeEntity::GetList([], ['FIELD_NAME' => 'UF_FIELD_USER_1'])->fetch();

        if (!$dbRes['ID']) {
            $oUserTypeEntity->Add($userField);
        }

        $userField = [
            'ENTITY_ID' => 'USER',
            'FIELD_NAME' => 'UF_FIELD_USER_2',
            'USER_TYPE_ID' => 'string',
            'MULTIPLE' => 'N',
            'MANDATORY' => 'N',
            'EDIT_FROM_LABEL' => ['ru' => 'TEST 2']
        ];

        $dbRes = CUserTypeEntity::GetList([], ['FIELD_NAME' => 'UF_FIELD_USER_2'])->fetch();

        if (!$dbRes['ID']) {
            $oUserTypeEntity->Add($userField);
        }
    }
}
