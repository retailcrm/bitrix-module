<?php

/**
 * Class RetailCrmOrder_v5Test
 */
class RetailCrmOrder_v5Test extends BitrixTestCase {

    /**
     * setUp method
     */
    public function setUp()
    {
        parent::setUp();

        COption::SetOptionString('intaro.retailcrm', 'api_version', 'v5');
        CModule::IncludeModule('intaro.retailcrm');
        RetailcrmConfigProvider::setOrderTypes(['bitrixType' => 'crmType']);
        RetailcrmConfigProvider::setContragentTypes(['bitrixType' => 'individual']);
        RetailcrmConfigProvider::setPaymentStatuses([1 => 'paymentStatus']);
        RetailcrmConfigProvider::setPaymentTypes(['bitrixPayment' => 'testPayment']);
        RetailcrmConfigProvider::setDeliveryTypes(['test' => 'test']);
        RetailcrmConfigProvider::setSendPaymentAmount('N');
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
     * @return array[]
     */
    public function orderSendProvider()
    {
        $arFields = $this->getArFields();
        $arParams = [
            'optionsOrderTypes' => RetailcrmConfigProvider::getOrderTypes(),
            'optionsPayStatuses' => RetailcrmConfigProvider::getPaymentStatuses(),
            'optionsContragentType' => RetailcrmConfigProvider::getContragentTypes(),
            'optionsDelivTypes' => RetailcrmConfigProvider::getDeliveryTypes(),
            'optionsPayTypes' => RetailcrmConfigProvider::getPaymentTypes(),
            'optionsPayment' => ['Y' => 'paid']
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
                ]]
            ],
        ]];
    }
}
