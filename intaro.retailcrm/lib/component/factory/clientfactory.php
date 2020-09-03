<?php
/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Component\Factory
 * @author   retailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Component\Factory;

use Intaro\RetailCrm\Component\ApiClient\ClientAdapter;
use Intaro\RetailCrm\Component\ConfigProvider;

/**
 * Class ClientFactory
 * @package Intaro\RetailCrm\Component\Factory
 */
class ClientFactory
{
    /**
     * Create ClientAdapter with current data for access to CRM
     *
     * @return \Intaro\RetailCrm\Component\ApiClient\ClientAdapter|null
     */
    public static function creacteClientAdapter(): ?ClientAdapter
    {
        $apiHost = ConfigProvider::getApiUrl();
        $apiKey  = ConfigProvider::getApiKey();

        if (empty($apiHost) || empty($apiKey)) {
            return null;
        }
    
        return new ClientAdapter($apiHost, $apiKey);
    }
}
