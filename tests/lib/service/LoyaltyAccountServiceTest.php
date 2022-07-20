<?php

use Intaro\RetailCrm\Model\Api\Response\Loyalty\Account\LoyaltyAccountCreateResponse;
use Intaro\RetailCrm\Service\LoyaltyAccountService;

/**
 * Class LoyaltyAccountService
 */
class LoyaltyAccountServiceTest extends BitrixTestCase
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

    /**
     * @param LoyaltyAccountCreateResponse $createResponse
     * @param bool $expected
     *
     * @dataProvider proveUserInLpExistsProvider
     */
    public function testProveUserInLpExists(LoyaltyAccountCreateResponse $createResponse, $expected)
    {
        self::assertEquals($expected,LoyaltyAccountService::proveUserInLpExists($createResponse));
    }

    /**
     * @param LoyaltyAccountCreateResponse $createResponse
     * @param bool $expected
     *
     * @dataProvider proveNotUserInLpExistsProvider
     */
    public function testNotProveUserInLpExists(LoyaltyAccountCreateResponse $createResponse, $expected)
    {
        self::assertEquals($expected,LoyaltyAccountService::proveUserInLpExists($createResponse));
    }

    /**
     * @return array[]
     */
    public function proveUserInLpExistsProvider()
    {
        $createResponse = new LoyaltyAccountCreateResponse();
        $createResponse->success = false;
        $createResponse->errors = [
            'loyalty' => 'The customer is in this loyalty program already'
        ];

        return [[
            'createResponse' => $createResponse,
            'expected' => true
        ]];
    }

    /**
     * @return array[]
     */
    public function proveNotUserInLpExistsProvider()
    {
        $createResponse = new LoyaltyAccountCreateResponse();
        $createResponse->success = false;
        $createResponse->errors = [
            'loyalty' => 'Some other failure'
        ];

        return [[
            'createResponse' => $createResponse,
            'expected' => false
        ]];
    }
}