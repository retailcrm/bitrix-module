<?php

use RetailCrm\ApiClient;
use RetailCrm\Response\ApiResponse;

/**
 * Class RetailCrmCorporateClientTest
 */
class RetailCrmCorporateClientTest extends \BitrixTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        COption::SetOptionString('intaro.retailcrm', 'api_version', 'v5');
        CModule::IncludeModule('intaro.retailcrm');
    }

    public function testCorpTookAndSetPrefix(): void
    {
        $apiClientMock = $this->getMockBuilder(ApiClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['customersCorporateGet', 'customersCorporateEdit'])
            ->getMock()
        ;

        $apiResponse = new ApiResponse('200', json_encode(['customerCorporate' => 'test']));
        $apiClientMock
            ->method('customersCorporateGet')
            ->withAnyParameters()
            ->willReturn($apiResponse)
        ;

        $response = \RetailCrmCorporateClient::isCorpTookExternalId('15', $apiClientMock, 'testSite');

        $this->assertTrue($response);

        $apiClientMock
            ->method('customersCorporateEdit')
            ->withAnyParameters()
            ->willReturn($apiResponse)
        ;

        RetailCrmCorporateClient::setPrefixForExternalId(15, $apiClientMock, 'testSite');
    }
}