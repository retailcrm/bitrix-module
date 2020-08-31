<?php

namespace Intaro\RetailCrm\Component\Factory;

use Bitrix\Main\Config\Option;
use Intaro\RetailCrm\Component\ApiClient\ClientAdapter;

class ClientFactory
{
    
    /**
     * create ClientAdapter with current data for access to CRM
     * @return \Intaro\RetailCrm\Component\ApiClient\ClientAdapter|null
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     */
    public static function creacteClientAdapter(): ?ClientAdapter
    {
        $apiHost = Option::get('intaro.retailcrm', 'api_host');
        $apiKey  = Option::get('intaro.retailcrm', 'api_key');

        if (empty($apiHost) || empty($apiKey)) {
            return null;
        } else {
            return new ClientAdapter($apiHost, $apiKey);
        }
    }
}