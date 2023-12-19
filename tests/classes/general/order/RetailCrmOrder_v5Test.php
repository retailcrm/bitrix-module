<?php

/**
 * Class RetailCrmOrder_v5Test
 */
class RetailCrmOrder_v5Test extends BitrixTestCase {

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
     * @param array  $arFields
     * @param array  $arParams
     * @param string $methodApi
     * @param array  $expected
     *
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     * @dataProvider orderSendProvider
     */
    public function testOrderSend($arFields, $arParams, $methodApi, $expected)
    {
        RetailcrmConfigProvider::setCustomFieldsStatus('Y');

        self::assertEquals($expected, RetailCrmOrder::orderSend(
            $arFields,
            new stdClass(),
            $arParams,
            false,
            null,
            $methodApi
        ));
    }

    /**
     * @dataProvider orderSendProvider
     */
    public function testOrderSendWitIntegrationPayment(
        array $arFields,
        array $arParams,
        string $methodApi,
        array $expected
    ): void {
        RetailcrmConfigProvider::setIntegrationPaymentTypes(['testPayment']);

        $orderSend = RetailCrmOrder::orderSend(
            $arFields,
            new stdClass(),
            $arParams,
            false,
            null,
            $methodApi
        );

        unset($expected['payments'][0]['paidAt'], $expected['payments'][0]['status']);
        static::assertEquals($expected['payments'][0], $orderSend['payments'][0]);
    }

    public function testFieldExists(): void
    {
        $order = \Bitrix\Sale\Order::create('s1', 1, 'RUB');
        $order->setPersonTypeId('bitrixType');
        $flag = true;

        try {
            $order->setField('REASON_CANCELED', 'тестовый заказ');
        } catch(\Exception $e) {
            $flag = false;
        }

        $this->assertTrue($flag, 'Переменной не существует');
    }

    public function initSystemData(): void
    {
        RetailcrmConfigProvider::setOrderTypes(['bitrixType' => 'crmType']);
        RetailcrmConfigProvider::setContragentTypes(['bitrixType' => 'individual']);
        RetailcrmConfigProvider::setPaymentStatuses([1 => 'paymentStatus']);
        RetailcrmConfigProvider::setPaymentTypes([1 => 'testPayment']);
        RetailcrmConfigProvider::setDeliveryTypes(['test' => 'test']);
        RetailcrmConfigProvider::setSendPaymentAmount('N');
    }

    /**
     * @return array[]
     */
    public function orderSendProvider()
    {
        $arFields = $this->getArFields();
        $this->initSystemData();

        $arParams = [
            'optionsOrderTypes' => RetailcrmConfigProvider::getOrderTypes(),
            'optionsPayStatuses' => RetailcrmConfigProvider::getPaymentStatuses(),
            'optionsContragentType' => RetailcrmConfigProvider::getContragentTypes(),
            'optionsDelivTypes' => RetailcrmConfigProvider::getDeliveryTypes(),
            'optionsPayTypes' => RetailcrmConfigProvider::getPaymentTypes(),
            'optionsOrderProps' => ['bitrixType' => ['fio' => 'FIO']],
            'optionsPayment' => ['Y' => 'paid'],
            'customOrderProps' => ['1#TEST_PROP_1' => 'custom_first', '2#TEST_PROP_2' => 'custom_second']
        ];

        return [[
            'arFields' => $arFields,
            'arParams' => $arParams,
            'methodApi' => 'ordersCreate',
            'expected' => [
                'number'          => $arFields['NUMBER'],
                'externalId'      => (string) $arFields['ID'],
                'createdAt'       => $arFields['DATE_INSERT'],
                'customer'        => ['externalId' => $arFields['USER_ID']],
                'orderType'       => $arParams['optionsOrderTypes'][$arFields['PERSON_TYPE_ID']],
                'status'          => $arParams['optionsPayStatuses'][$arFields['STATUS_ID']],
                'customerComment' => $arFields['USER_DESCRIPTION'],
                'managerComment'  => $arFields['COMMENTS'],
                'delivery' => [
                    'cost' => $arFields['PRICE_DELIVERY'],
                    'code' => $arFields['DELIVERYS'][0]['id'],
                    'service' => ['code' => $arFields['DELIVERYS'][0]['service']]
                ],
                'contragent' => [
                    'contragentType' => $arParams['optionsContragentType'][$arFields['PERSON_TYPE_ID']]
                ],
                'payments' => [[
                    'type' => $arParams['optionsPayTypes'][$arFields['PAYMENTS'][0]['PAY_SYSTEM_ID']],
                    'externalId' => RCrmActions::generatePaymentExternalId($arFields['PAYMENTS'][0]['ID']),
                    'status' => 'paid',
                    'paidAt' => $this->getDateTime()->format('Y-m-d H:i:s')
                ]],
                'privilegeType' => 'none',
                'statusComment' => $arFields['REASON_CANCELED'],
                'firstName' => 'FirstName',
                'lastName' => 'LastName',
                'customFields' => ['custom_first' => 'test 1', 'custom_second' => 'test 2']
            ],
        ]];
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testUploadOrders(): void
    {
        $dependencyLoader = Mockery::mock('alias:' . RetailcrmDependencyLoader::class);
        $dependencyLoader->shouldReceive('loadDependencies')->withAnyArgs()->andReturn(true);

        COption::SetOptionString('intaro.retailcrm', \Intaro\RetailCrm\Component\Constants::CRM_SITES_LIST, serialize([]));

        $rcrmActions = Mockery::mock('alias:' . RCrmActions::class);
        $rcrmActions->shouldReceive('apiMethod')->withAnyArgs()->andReturn(true);
        $rcrmActions->shouldReceive('fromJSON')->withAnyArgs()->andReturn('');

        $result = RetailCrmOrder::uploadOrders();

        self::assertTrue($result);
    }
}
