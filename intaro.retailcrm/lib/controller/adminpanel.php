<?php

namespace Intaro\RetailCrm\Controller;

use Bitrix\Main\Engine\ActionFilter\Authentication;
use Bitrix\Main\Engine\Controller;
use Intaro\RetailCrm\Component\Constants;

class AdminPanel extends Controller
{
    public function configureActions(): array
    {
        return [
            'createTemplate' => [
                '-prefilters' => [
                    Authentication::class,
                ],
            ],
        ];
    }

    /**
     * @param array  $templates
     * @param string $donor
     * @param string $replaceDefaultTemplate
     *
     * @return array
     */
    public function createTemplateAction(array $templates, string $donor, string $replaceDefaultTemplate = 'N'): array
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
            'status' => $status ?? false,
        ];
    }

    /**
     * @return string[]
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     */
    public function LoyaltyProgramToggleAction(): array
    {
        $status    = ConfigProvider::getLoyaltyProgramStatus();
        $newStatus = $status !== 'Y' ? 'Y' : 'N';
        ConfigProvider::setLoyaltyProgramStatus($newStatus);

        return ['newStatus' => $newStatus];
    }

    /**
     * @param       $templates
     * @param string $defreplace
     * @return array
     */
    public function createSaleTemplateAction($templates, $defreplace = 'N'): array
    {
        $templateName = $defreplace === 'Y' ? '.default' : Constants::MODULE_ID;

        foreach ($templates as $template) {
            $pathFrom = $_SERVER['DOCUMENT_ROOT']
                . '/bitrix/modules/'
                . Constants::MODULE_ID
                . '/install/export/local/components/intaro/sale.order.ajax/templates/.default';

            $pathTo = $_SERVER['DOCUMENT_ROOT']
                . $template['location']
                . $template['name']
                . '/components/bitrix/sale.order.ajax/'
                . $templateName;

            if ($defreplace === 'Y' && file_exists($pathTo)) {
                $backPath = $_SERVER['DOCUMENT_ROOT']
                    . $template['location']
                    . $template['name']
                    . '/components/bitrix/sale.order.ajax/'
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
            'status' => $status,
        ];
    }
}
