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

/**
 * Class CollectorCookieExtractor
 *
 * @package Intaro\RetailCrm\Component
 */
class CollectorCookieExtractor
{
    /**
     * Extracts daemon collector cookie if it's present.
     *
     * @return string|null
     */
    public function extractCookie(): ?string
    {
        global $_COOKIE;

        return (isset($_COOKIE['_rc']) && $_COOKIE['_rc'] != '') ? $_COOKIE['_rc'] : null;
    }
}
