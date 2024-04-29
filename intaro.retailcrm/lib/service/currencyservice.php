<?php

/**
 * @category Integration
 * @package  Intaro\RetailCrm\Service
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

namespace Intaro\RetailCrm\Service;

/**
 * Class CurrencyService
 *
 * @package Intaro\RetailCrm\Service
 */
class CurrencyService
{
    public static function validateCurrency($cmsCurrency, $crmCurrency): string
    {
        $errorCode = '';

        if ($cmsCurrency === null) {
            $errorCode = 'ERR_CMS_CURRENCY';
        } elseif ($crmCurrency === null) {
            $errorCode = 'ERR_CRM_CURRENCY';
        } elseif ($cmsCurrency !== $crmCurrency) {
            $errorCode = 'ERR_CURRENCY_SITES';
        }

        return $errorCode;
    }
}
