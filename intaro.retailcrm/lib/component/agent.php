<?php

namespace Intaro\RetailCrm\Component;

use CCatalogExport;
use CModule;
use Dotenv\Loader;
use Intaro\RetailCrm\Component\Factory\ClientFactory;
use Intaro\RetailCrm\Component\Json\Serializer;

/**
 * Class Agent
 *
 * @package Intaro\RetailCrm\Component
 */
class Agent
{
    /**
     * @param int $profileId
     *
     * @return string
     */
    public function preGenerateExport(int $profileId): string
    {
        CModule::IncludeModule('catalog');
        CCatalogExport::PreGenerateExport($profileId);

        return '';
    }
}
