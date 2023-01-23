<?php

use Bitrix\Sale\Order;
/**
 * Class RetailCrmHistory_v5Test
 */
class RetailCrmHistory_v5Test extends \BitrixTestCase
{
    /**
     * setUp method
     */
    public function setUp()
    {
        parent::setUp();

        COption::SetOptionString('intaro.retailcrm', 'api_version', 'v5');
        CModule::IncludeModule('intaro.retailcrm');
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
}