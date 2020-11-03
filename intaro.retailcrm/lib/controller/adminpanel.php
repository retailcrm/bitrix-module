<?php

namespace Intaro\RetailCrm\Controller;

use Bitrix\Main\Engine\ActionFilter\Authentication;
use Bitrix\Main\Engine\Controller;
use Intaro\RetailCrm\Component\ConfigProvider;
use Intaro\RetailCrm\Component\Constants;

class AdminPanel extends Controller
{
    public function configureActions(): array
    {
        return [
            'loyaltyProgramToggle' => [
                '-prefilters' => [
                    Authentication::class,
                ],
            ],
            'createTemplate' => [
                '-prefilters' => [
                    Authentication::class,
                ],
            ],
        ];
    }

    /**
     * @return string[]
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     */
    public function LoyaltyProgramToggleAction(): array
    {
        $status    = ConfigProvider::getLoyaltyProgramStatus();

        if ($status !== 'Y') {
            $newStatus = 'Y';
        } else {
            $newStatus = 'N';
        }

        ConfigProvider::setLoyaltyProgramStatus($newStatus);

        return ['newStatus' => $newStatus];
    }

    /**
     * @param array  $templates
     * @param string $donor
     * @param string $replaceDefaultTemplate
     *
     * @return array
     */
    public function createTemplateAction(array $templates, string $donor ,$replaceDefaultTemplate = 'N'): array
    {
        $templateName = $replaceDefaultTemplate === 'Y' ? '.default' : Constants::MODULE_ID;
        $donor = str_replace(['../', './'], '', $donor);

        foreach ($templates as $template) {
            $template['location'] = str_replace(['../', './'], '', $template['location']);
            $template['name'] = str_replace(['../', './'], '', $template['name']);

            $pathFrom = $_SERVER['DOCUMENT_ROOT']
                . '/bitrix/modules/'
                . Constants::MODULE_ID
                . '/install/export/local/components/intaro/' . $donor . '/templates/.default';
            $pathTo = $_SERVER['DOCUMENT_ROOT']
                . $template['location']
                . $template['name']
                . '/components/bitrix/'
                . $donor
                . '/'
                . $templateName;

            if ($replaceDefaultTemplate === 'Y' && file_exists($pathTo)) {
                $backPath = $_SERVER['DOCUMENT_ROOT']
                    . $template['location']
                    . $template['name']
                    . '/components/bitrix/'
                    . $donor
                    . '/'
                    . $templateName.'_backup';

                 CopyDirFiles(
                    $pathTo,
                    $backPath,
                    true,
                    true,
                    false
                );
            }

            $status = CopyDirFiles(
                $pathFrom,
                $pathTo,
                true,
                true,
                false
            );
        }

        return [
            'status' => isset($status) ? $status : false,
        ];
    }

    /**
     * @return string[]
     */
    public function ReplaceDefSaleTemplateAction(): array
    {
        return ['status' => 'ok'];
    }
}
