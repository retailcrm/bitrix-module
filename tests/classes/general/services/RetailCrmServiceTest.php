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
}
