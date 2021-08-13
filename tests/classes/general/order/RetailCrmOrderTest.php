<?php

/**
 * Class RetailCrmOrderTest
 */
use \Bitrix\Main\Loader;
use \Bitrix\Main\Application;

class RetailCrmOrderTest extends BitrixTestCase
{
    use TestHelper;

    protected $retailCrmOrder;
    protected $test;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $cache = Application::getInstance()->getManagedCache();
        $cache->clean('b_option:intaro.retailcrm');

        $this->retailCrmOrder = \Mockery::mock('RetailCrmOrder');
        COption::SetOptionString('intaro.retailcrm', 'api_version', 'v5');
        CModule::IncludeModule('intaro.retailcrm');

        RetailcrmConfigProvider::setPaymentTypes(['bitrixPayment' => 'crmPayment']);
        RetailcrmConfigProvider::setIntegrationPaymentTypes(['crmPayment']);
    }

    public function testModuleInstalled()
    {
        $this->assertTrue(Loader::includeModule("intaro.retailcrm"));
    }

    /**
     * @param $pSize
     * @param $failed
     * @param $orderList
     *
     * @dataProvider getData
     */
    public function testUploadOrders($pSize, $failed, $orderList)
    {
        $this->assertEquals(50, $pSize);
        $this->assertFalse($failed);

        if ($orderList) {
            $this->assertEquals(3, sizeof($orderList));

            $this->retailCrmOrder->shouldReceive('uploadOrders')
                ->andReturn(
                    array(
                        array('id' => 001, 'externalId' => 2),
                        array('id' => 002, 'externalId' => 3),
                        array('id' => 003, 'externalId' => 4)
                    )
                );
            $result = $this->retailCrmOrder->uploadOrders();

            foreach ($result as $key => $order) {
                $this->assertEquals($order["externalId"], $orderList[$key]);
            }
        } else {
            $this->assertFalse($orderList);
        }
    }

    /**
     * @return array
     */
    public function getData()
    {
        return [
            [
                'pSize' => 50,
                'failed' => false,
                'orderList' => false
            ],
            [
                'pSize' => 50,
                'failed' => false,
                'orderList' => array(2,3,4)
            ]
        ];
    }

    /**
     * @param $pack
     * @param $method
     * @param $keyResponse
     * @param $apiMock
     * @param $optionsSitesList
     *
     * @dataProvider getDataUpload
     */
    public function testUploadItems($pack, $method, $keyResponse, $apiMock, $optionsSitesList)
    {
        $responseBody1 = '';
        for ($i = 1; $i < 51; $i++) {
            $responseBody1 .= "{\"id\":".$i.",\"externalId\":".$i."},";
        }

        $responseBody1 = substr($responseBody1,0,-1);
        $responseBody1 ='{
        "success":true,
            "'.$keyResponse.'":['.$responseBody1.']
        }';

        $responseBody2 ='{
        "success":true,
            "uploadedCustomers":[
                {"id":51,"externalId":"51"}
            ]
        }';

        $apiMock->shouldReceive($method)
            ->andReturn(
                new \RetailCrm\Response\ApiResponse(
                    200,
                    $responseBody1
                ),
                new \RetailCrm\Response\ApiResponse(
                    200,
                    $responseBody2
                )
            );

        $test = new RetailCrmOrder();
        $result = $test::uploadItems($pack, $method, $keyResponse, $apiMock, $optionsSitesList);

        $this->assertEquals(sizeof($pack['s1']), sizeof($result));
    }

    /**
     * @return mixed
     */
    public function getCustomerList()
    {
        $faker = Faker\Factory::create();
        $customerList = [];

        for ($i = 1; $i < 52; $i++) {
            $customerList['s1'][$i]['externalId'] = $i;
            $customerList['s1'][$i]['email'] = $faker->email;
            $customerList['s1'][$i]['createdAt'] = $faker->date('Y-m-d H:i:s');
            $customerList['s1'][$i]['subscribed'] = '';
            $customerList['s1'][$i]['contragent'] = ['contragentType' => 'individual'];
            $customerList['s1'][$i]['firstName'] = $faker->firstName;
            $customerList['s1'][$i]['lastName'] = $faker->lastName;
        }

        return $customerList;
    }

    /**
     * @return array
     */
    public function getDataUpload()
    {
        return [
            [
                'pack' => $this->getCustomerList(),
                'customersUpload',
                'uploadedCustomers',
                'api'=>\Mockery::mock('RetailCrm\ApiClient'),
                'optionsSitesList'=>[]
            ],
            [
                'pack'=> [],
                'ordersUpload',
                'uploadedOrders',
                'api'=>\Mockery::mock('RetailCrm\ApiClient'),
                'optionsSitesList'=>[]
            ]
        ];
    }

    /**
     * @param array  $arFields
     * @param array  $arParams
     * @param string $methodApi
     * @param array  $expected
     *
     * @dataProvider orderSendProvider
     */
    public function testOrderSendWitIntegrationPayment($arFields, $arParams, $methodApi, $expected)
    {
        $orderSend = RetailCrmOrder::orderSend(
            $arFields,
            new stdClass(),
            $arParams,
            false,
            null,
            $methodApi
        );

        static::assertEquals($expected['payments'][0], $orderSend['payments'][0]);
    }

    /**
     * @return array[]
     */
    public function orderSendProvider()
    {
        $arFields = $this->getArFields();
        $arParams = [
            'optionsPayTypes' => ['bitrixPayment' => 'crmPayment'],
            'optionsPayment' => ['Y' => 'paid']
        ];

        return [
            [
                'arFields' => $arFields,
                'arParams' => $arParams,
                'methodApi' => 'ordersCreate',
                'expected' => [
                    'number'          => (string) $arFields['NUMBER'],
                    'externalId'      => (string) $arFields['ID'],
                    'payments' => [[
                        'type' => $arParams['optionsPayTypes'][$arFields['PAYMENTS'][0]['PAY_SYSTEM_ID']],
                        'externalId' => RCrmActions::generatePaymentExternalId($arFields['PAYMENTS'][0]['ID']),
                    ]]
                ],
            ]
        ];
    }
}
