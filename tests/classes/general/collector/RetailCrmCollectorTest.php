<?php

class RetailCrmCollectorTest extends \PHPUnit\Framework\TestCase
{
    const TEST_KEY = 'RC-XXXXXXXXXX-X';

    public function setUp(): void
    {
        parent::setUp();

        COption::SetOptionString(
            RetailCrmCollector::$MODULE_ID,
            RetailCrmCollector::$CRM_COLL_KEY,
            serialize([SITE_ID => self::TEST_KEY])
        );

        COption::SetOptionString(
            RetailCrmCollector::$MODULE_ID,
            RetailCrmCollector::$CRM_COLL,
            'Y'
        );
    }

    public function testAdd()
    {
        RetailCrmCollector::add();
        $strings = \Bitrix\Main\Page\Asset::getInstance()->getStrings();

        $this->assertStringContainsString(self::TEST_KEY, $strings);
        $this->assertStringContainsString('customerId', $strings);
    }
}
