<?php

namespace Tests\Intaro\RetailCrm;

/**
 * Class DataService
 */
class DataService
{
    public static function deliveryDataForValidation()
    {
        return [
            'delivery' => [
                'code' => 'boxberry',
                'cost' => 'test',
                'address' => 'test',
                'data' => 'test',
            ],
            'weight' => 'test',
            'firstName' => 'test',
            'lastName' => 'test',
            'phone' => 'test',
            'paymentType' => 'test',
            'shipmentStore' => 'test',
        ];
    }

    public static function deliveryDataCourier()
    {
        return [
            'delivery' => [
                'code' => 'test',
                'cost' => 500,
                'address' => 'test address',
                'data' => 'test data'
            ],
            'weight' => '3',
            'firstName' => 'TestName',
            'lastName' => 'TestLastName',
            'phone' => '89998887766',
            'paymentType' => 'test',
            'shipmentStore' => 'test',
        ];
    }
}
