<?php

/**
 * Class RetailCrmServiceTest
 */
class RetailCrmServiceTest extends PHPUnit\Framework\TestCase
{
    private $paramsExample = [
        'delivery'      => [
            'code'    => 'boxberry',
            'cost'    => 'test',
            'address' => 'test',
            'data'    => 'test',
        ],
        'weight'        => 'test',
        'firstName'     => 'test',
        'lastName'      => 'test',
        'phone'         => 'test',
        'paymentType'   => 'test',
        'shipmentStore' => 'test',
    ];
    
    public function testOnUnsetIntegrationDeliveryFields()
    {
        $value = serialize(['boxberry' => 'test']);
        COption::SetOptionString(RetailcrmConstants::MODULE_ID, RetailcrmConstants::CRM_INTEGRATION_DELIVERY, $value);
        $newParams     = RetailCrmService::unsetIntegrationDeliveryFields($this->paramsExample);
        $expectedArray = [
            'delivery' => [
                'code' => 'boxberry',
            ],
        ];
        
        $this->assertEquals($newParams, $expectedArray);
    }

    /**
     * @param array $data
     * @param array $expected
     * @dataProvider selectIntegrationDeliveriesProvider
     */
    public function testSelectIntegrationDeliveries(array $data, array $expected)
    {
        $this->assertEquals($expected, RetailCrmService::selectIntegrationDeliveries($data));
    }

    /**
     * @param array $data
     * @param array $expected
     * @dataProvider selectIntegrationPaymentsProvider
     */
    public function testSelectIntegrationPayments(array $data, array $expected)
    {
        $this->assertEquals($expected, RetailCrmService::selectIntegrationPayments($data));
    }

    public function selectIntegrationDeliveriesProvider()
    {
        return [[
           'data' => [
               [
                   'code' => 'test1',
                   'integrationCode' => 'test2'
               ]
           ],
            'expected' => ['test1' => 'test2']
        ]];
    }

    public function selectIntegrationPaymentsProvider()
    {
        return [[
            'data' => [
                [
                    'code' => 'test1',
                    'integrationModule' => 'test2'
                ]
            ],
            'expected' => ['test1']
        ]];
    }
}
