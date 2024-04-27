<?php

/**
 * @category Integration
 * @package  Intaro\RetailCrm\Component\ApiClient
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

namespace Intaro\RetailCrm\Component\ApiClient\Traits;

use RetailCrm\ApiClient;

/**
 * Trait BaseClientTrait
 *
 * @package Intaro\RetailCrm\Component\ApiClient\Traits
 */
trait BaseClientTrait
{
    /** @var \RetailCrm\ApiClient */
    private $client;

    /**
     * ClientFacade constructor.
     *
     * @param string      $url
     * @param string      $apiKey
     * @param string|null $site
     */
    public function __construct(string $url, string $apiKey, $site = null)
    {
        $this->client = new ApiClient($url, $apiKey, $site);
    }
}
