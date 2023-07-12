<?php

namespace Intaro\RetailCrm\Component\Installer;

use Intaro\RetailCrm\Component\Constants;

IncludeModuleLangFile(__FILE__);

trait SubscriberInstallerTrait
{
    public function CopyFilesSubscribe(): void
    {
        $pathFrom = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . Constants::MODULE_ID . '/install';

        CopyDirFiles(
            $pathFrom . '/export',
            $_SERVER['DOCUMENT_ROOT'],
            true,
            true,
            false
        );

        $lpTemplateNames = [
            'sale.personal.section'
        ];

        foreach ($lpTemplateNames as $lpTemplateName){
            $lpTemplatePath = $_SERVER['DOCUMENT_ROOT']
                . '/local/templates/.default/components/bitrix/' . $lpTemplateName . '/default_subscribe10';

            if (!file_exists($lpTemplatePath)) {
                $pathFrom = $_SERVER['DOCUMENT_ROOT']
                    . '/bitrix/modules/intaro.retailcrm/install/export/local/components/intaro/'
                    . $lpTemplateName;

                CopyDirFiles(
                    $pathFrom,
                    $lpTemplatePath,
                    true,
                    true,
                    false
                );
            }
        }
    }
}