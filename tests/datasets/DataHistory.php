<?php

namespace Tests\Intaro\RetailCrm;

/**
 * Class DataHistory
 */
class DataHistory
{
    public static function get_history_data_new_customer()
    {
        return [
            'success' => true,
            'history' => [
                [
                    'id' => 18009,
                    'createdAt' => '2021-12-03 13:22:45',
                    'created' => true,
                    'source' => 'user',
                    'user' => ['id' => 11],
                    'field' => 'id',
                    'oldValue' => null,
                    'newValue' => 3758,
                    'customer' => [
                        'type' => 'customer',
                        'id' => 3758,
                        'isContact' => false,
                        'createdAt' => '2021-12-03 13:22:45',
                        'vip' => false,
                        'bad' => false,
                        'site' => 'bitrix',
                        'marginSumm' => 0,
                        'totalSumm' => 0,
                        'averageSumm' => 0,
                        'ordersCount' => 0,
                        'personalDiscount' => 0,
                        'cumulativeDiscount' => 0,
                        'address' => [
                            'id' => 3503,
                            'index' => 123456,
                            'countryIso' => 'ES',
                            'region' => 'Region',
                            'city' => 'City',
                            'text' => 'Street',
                        ],
                        'customFields' => ['crm_customer' => 'test_customer'],
                        'segments' => [],
                        'firstName' => 'Test_Name',
                        'lastName' => 'Test_LastName',
                        'email' => 'testbitrixreg@gmail.com',
                        'phones' => ['0' => ['number' => '+79184563200']],
                        'birthday' => '2021-10-01'
                    ]
                ]
            ],
            'pagination' => [
                'limit' => 100,
                'totalCount' => 1,
                'currentPage' => 1,
                'totalPageCount' => 1
            ]
        ];
    }
}