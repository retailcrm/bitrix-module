<?php
/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Service
 * @author   retailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

namespace Intaro\RetailCrm\Service;

use Intaro\RetailCrm\Component\Factory\ClientFactory;
use Intaro\RetailCrm\Component\Json\Deserializer;
use Intaro\RetailCrm\Model\Api\Response\Settings\CredentialsResponse;

/**
 * Class SettingsService
 *
 * @package Intaro\RetailCrm\Service
 */
class SettingsService
{
    /**
     * @var \Intaro\RetailCrm\Component\ApiClient\ClientAdapter
     */
    private $client;
    
    /**
     * LoyaltyService constructor.
     */
    public function __construct()
    {
        $this->client = ClientFactory::createClientAdapter();
    }
    
    /**
     * @return \Intaro\RetailCrm\Model\Api\Response\Settings\CredentialsResponse
     */
    public function getCredentials(): CredentialsResponse
    {
        $response = $this->client->getCredentials();
    
        return Deserializer::deserializeArray($response->getResponseBody(), CredentialsResponse::class);
    }
}
