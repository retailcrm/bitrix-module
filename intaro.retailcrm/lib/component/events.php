<?php

/**
 * @category Integration
 * @package  Intaro\RetailCrm\Component
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

namespace Intaro\RetailCrm\Component;

use Bitrix\Main\Event;

/**
 * Class Events
 *
 * @package Intaro\RetailCrm\Component
 */
class Events
{
    /**
     * Fired before returning result from RetailCRM customer builder (customer for RetailCRM API)
     */
    public const API_CUSTOMER_BUILDER_GET_RESULT = 'OnRetailcrmApiCustomerBuilderGetResult';

    /**
     * Fired before returning result from RetailCRM corporate customer builder (customer for RetailCRM API)
     */
    public const API_CORPORATE_CUSTOMER_BUILDER_GET_RESULT = 'OnRetailcrmApiCorporateCustomerBuilderGetResult';

    /**
     * Fired before returning result from RetailCRM customer builder (builds user data for Bitrix)
     */
    public const BITRIX_CUSTOMER_BUILDER_GET_RESULT = 'OnRetailcrmBitrixCustomerBuilderGetResult';

    /**
     * Push event
     *
     * @param string $eventType
     * @param array  $eventParams
     */
    public static function push(string $eventType, array $eventParams): void
    {
        $event = new Event(Constants::MODULE_ID, $eventType, $eventParams);
        $event->send();
    }
}
