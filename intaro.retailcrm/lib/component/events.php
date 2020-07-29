<?php
/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Component
 * @author   retailCRM <integration@retailcrm.ru>
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
    public const CUSTOMER_BUILDER_GET_RESULT = 'OnRetailcrmApiCustomerBuilderGetResult';

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
