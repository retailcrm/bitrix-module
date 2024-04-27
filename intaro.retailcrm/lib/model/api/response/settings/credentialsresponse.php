<?php

/**
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Api\Response\Order\Loyalty
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

namespace Intaro\RetailCrm\Model\Api\Response\Settings;

use Intaro\RetailCrm\Model\Api\Response\AbstractApiResponseModel;
use Intaro\RetailCrm\Component\Json\Mapping;

/**
 * Class CredentialsResponse
 *
 * @package Intaro\RetailCrm\Model\Api\Response\Settings
 */
class CredentialsResponse extends AbstractApiResponseModel
{
    /**
     * Результат запроса (успешный/неуспешный)
     *
     * @var bool $success
     *
     * @Mapping\Type("bool")
     * @Mapping\SerializedName("success")
     */
    public $success;
    
    /**
     * @var array $credentials
     *
     * @Mapping\Type("array")
     * @Mapping\SerializedName("credentials")
     */
    public $credentials;
    
    /**
     * @var string $siteAccess
     *
     * @Mapping\Type("array")
     * @Mapping\SerializedName("siteAccess")
     */
    public $siteAccess;
    
    /**
     * @var array $sitesAvailable
     *
     * @Mapping\Type("array")
     * @Mapping\SerializedName("sitesAvailable")
     */
    public $sitesAvailable;
}
