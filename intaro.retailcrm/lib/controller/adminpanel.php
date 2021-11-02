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
     * @return string[]
     */
    public function createSaleTemplateAction($templates): array
    {
        foreach ($templates as $template){
            $pathFrom = $_SERVER['DOCUMENT_ROOT']
                . '/bitrix/modules/'
                . Constants::MODULE_ID
                . '/install/export/local/components/intaro/sale.order.ajax/templates/.default';
            
            $pathTo = $_SERVER['DOCUMENT_ROOT']
                . '/local/templates/'
                . $template
                . '/components/bitrix/sale.order.ajax/intaro.retailCRM';

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
    
    /**
     * @return string[]
     */
    public function ReplaceDefSaleTemplateAction(): array
    {
        return ['status' => 'ok'];
    }
}
