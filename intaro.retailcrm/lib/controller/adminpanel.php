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
     * @param array  $templates
     * @param string $donor
     * @param string $replaceDefaultTemplate
     * @return array
     */
    public function createTemplateAction(array $templates, string $donor ,$replaceDefaultTemplate = 'N'): array
    {
        $templateName = $replaceDefaultTemplate === 'Y' ? '.default' : Constants::MODULE_ID;
    
        $donor = str_replace(['../', './'], '', $donor);
    
        foreach ($templates as $template) {
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
}
