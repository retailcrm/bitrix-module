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

    public static function availableSitesAndTypesData()
    {
        return [
            'sites' => [
                'bitrix' => 's1',
                'bitrix2' => 's2'
            ],
            'types' => [
                'test1' => [
                    'code' => 'test1',
                    'active' => true,
                    'sites' => []
                ],
                'test2' => [
                    'code' => 'test2',
                    'active' => false,
                    'sites' => []
                ],
                'test3' => [
                    'code' => 'test3',
                    'active' => true,
                    'sites' => ['crm', 'crm1']
                ],
                'test4' => [
                    'code' => 'test4',
                    'active' => true,
                    'sites' => ['bitrix', 'crm']
                ],
                'test5' => [
                    'code' => 'test5',
                    'active' => false,
                    'sites' => ['bitrix', 'bitrix2']
                ],
                'test6' => [
                    'code' => 'test6',
                    'active' => true,
                    'sites' => ['bitrix', 'bitrix2']
                ]
            ]
        ];
    }
}
