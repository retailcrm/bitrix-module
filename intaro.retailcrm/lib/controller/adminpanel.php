<?php

namespace Intaro\RetailCrm\Controller;

use Bitrix\Main\Engine\ActionFilter\Authentication;
use Bitrix\Main\Engine\Controller;
use Intaro\RetailCrm\Component\ConfigProvider;

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
    public function CreateSaleTemplateAction($templates): array
    {
        return [
            'status' => 'ok',
            'templates' => $templates,
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
