<?php

namespace Intaro\RetailCrm\Component\Advanced;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\EventManager;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use CUserTypeEntity;
use Intaro\RetailCrm\Component\Constants;

trait InstallerTrait
{
    public function installExport()
    {
        $pathFrom = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . Constants::MODULE_ID . '/install';

        CopyDirFiles(
            $pathFrom . '/export/bitrix/php_interface/include/catalog_export',
            $_SERVER['DOCUMENT_ROOT'] . '/bitrix/php_interface/include',
            true,
            true,
            false
        );

        $path = $_SERVER['DOCUMENT_ROOT'] . '/local/';

        CheckDirPath($path);

        $file = new \Bitrix\Main\IO\File($path . 'icml_property_retailcrm.txt', $siteId = null);

        if (!$file->isExists()) {
            $file->putContents("");
        }
    }

    public function subscriptionSetup()
    {
        $pathFrom = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . Constants::MODULE_ID . '/install';

        CopyDirFiles(
            $pathFrom . '/export/sub-register',
            $_SERVER['DOCUMENT_ROOT'] . '/sub-register',
            true,
            true,
            false
        );

        $templateNames = [
            'default_subscribe' => [
                0 => [
                    'name' => 'sale.personal.section',
                    'templateDirectory' => '.default'
                ],
                1 => [
                    'name' => 'main.register',
                    'templateDirectory' => '.default_subscribe'
                ]
            ]
        ];

        foreach ($templateNames as $directory => $templates) {
            foreach ($templates as $template) {
                $this->copy($directory, $template);
            }
        }

        $property = [
            'ENTITY_ID'       => 'USER',
            'FIELD_NAME'      => 'UF_SUBSCRIBE_USER_EMAIL',
            'USER_TYPE_ID'    => 'boolean',
            'MULTIPLE'        => 'N',
            'MANDATORY'       => 'N',
            'EDIT_FORM_LABEL' => ['ru' => GetMessage('UF_SUBSCRIBE_USER_EMAIL_TITLE')],

        ];

        $obUserField = new CUserTypeEntity();
        $dbRes = CUserTypeEntity::GetList([], ['FIELD_NAME' => 'UF_SUBSCRIBE_USER_EMAIL'])->fetch();

        if (!$dbRes['ID']) {
            $obUserField->Add($property);
        }
    }

    private function copy($directory, $template): void
    {
        $templatePath = $_SERVER['DOCUMENT_ROOT']
            . '/local/templates/.default/components/bitrix/' . $template['name'] . '/'. $directory
        ;

        if (!file_exists($templatePath)) {
            $pathFrom = $_SERVER['DOCUMENT_ROOT']
                . '/bitrix/modules/intaro.retailcrm/install/export/local/components/intaro/'
                . $template['name']
                . '/templates/' . $template['templateDirectory']
            ;

            CopyDirFiles(
                $pathFrom,
                $templatePath,
                true,
                true,
                false
            );
        }
    }
}
