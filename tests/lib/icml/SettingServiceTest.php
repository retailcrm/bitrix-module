<?php

namespace lib\icml;

use Intaro\RetailCrm\Icml\SettingsService;

class SettingServiceTest extends \BitrixTestCase
{
    private $mockSettingService;
    public function setUp(): void
    {
        parent::setUp();

        $this->mockSettingService = $this->getMockBuilder(SettingsService::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
    }

    public function testConstruct(): SettingsService
    {
        $path = $_SERVER['DOCUMENT_ROOT'] . '/local/';

        CheckDirPath($path);

        $file = new \Bitrix\Main\IO\File($path . '/icml_property_retailcrm.txt', $siteId = null);

        $file->putContents("property1 = test prop \n property2 = test prop 2");

        $settingService = SettingsService::getInstance($this->getSetupVars(), "");

        $this->assertInstanceOf(SettingsService::class, $settingService);
        $this->assertArrayHasKey('property1', $settingService->actualPropList);
        $this->assertArrayHasKey('property2', $settingService->actualPropList);

        return $settingService;
    }

    private function getSetupVars()
    {
        return [
            'iblockExport' => 2,
            'loadPurchasePrice' => "",
            'loadNonActivity' => "",
            'SETUP_FILE_NAME' => "/bitrix/catalog_export/retailcrm.xml",
            'SETUP_PROFILE_NAME' => "Выгрузка каталога RetailCRM"
        ];
    }
}
