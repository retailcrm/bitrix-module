<?php

/**
 * Class RetailCrmOrderTest
 */
use \Bitrix\Main\Loader;

class RetailCrmICMLTest extends BitrixTestCase
{
    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();
        COption::SetOptionString('intaro.retailcrm', 'api_version', 'v5');
        CModule::IncludeModule('intaro.retailcrm');
    }

    public function testModuleInstalled()
    {
        $this->assertTrue(Loader::includeModule("intaro.retailcrm"));
    }

    public function testGetImageUrl()
    {
        $test = new RetailCrmICML();
        $result = $test->getImageUrl(1);

        if (!empty($result)) {
            $this->assertIsString($result);
            $this->assertEquals("/upload/iblock/c44/test.jpg", $result);
        } else {
            $this->assertEmpty($result);
        }
    }
}
