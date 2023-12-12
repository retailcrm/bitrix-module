<?php

namespace Intaro\RetailCrm\Service;


class CurrencyService
{
    public static function validateCurrency($cmsCurrency, $crmCurrency, $cmsSite = null, $crmSite = null)
    {
        $errorMessage = '';

        if ($cmsCurrency === null) {
            $errorMessage = GetMessage('ERR_CMS_CURRENCY') . ' (' . $cmsSite . ')';
        } elseif ($crmCurrency === null) {
            $errorMessage = GetMessage('ERR_CRM_CURRENCY') . ' (' . $crmSite . ')';
        } elseif ($cmsCurrency !== $crmCurrency) {
            $errorMessage = GetMessage('ERR_CURRENCY_SITES') . ' (' . $crmSite . ')';
        }

        return $errorMessage;
    }
}
