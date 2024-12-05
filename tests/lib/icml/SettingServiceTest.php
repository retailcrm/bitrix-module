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
