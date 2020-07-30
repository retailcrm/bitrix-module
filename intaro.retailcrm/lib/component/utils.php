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

use Bitrix\Main\Text\Encoding;
use Intaro\RetailCrm\Model\Api\Order\Order;

/**
 * Class Utils
 *
 * @package Intaro\RetailCrm\Component
 */
class Utils
{
    /**
     * Removes all empty fields from arrays, works for nested arrays
     *
     * @param array $arr
     * @return array
     */
    public static function clearArray($arr): array
    {
        if (is_array($arr) === false) {
            return $arr;
        }

        $result = array();

        foreach ($arr as $index => $node) {
            $result[$index] = is_array($node) === true ? self::clearArray($node) : trim($node);

            if ($result[$index] == '' || $result[$index] === null || count($result[$index]) < 1) {
                unset($result[$index]);
            }
        }

        return $result;
    }

    /**
     *
     * @param array|bool|\SplFixedArray|string $string in SITE_CHARSET
     *
     * @return array|bool|\SplFixedArray|string $str in utf-8
     */
    public static function toUTF8($string)
    {
        if (!defined('SITE_CHARSET')) {
            throw new \RuntimeException('SITE_CHARSET must be defined.');
        }

        return static::convertCharset($string, SITE_CHARSET, 'utf-8');
    }

    /**
     *
     * @param string|array|\SplFixedArray $string in utf-8
     *
     * @return array|bool|\SplFixedArray|string $str in SITE_CHARSET
     */
    public static function fromUTF8($string)
    {
        if (!defined('SITE_CHARSET')) {
            throw new \RuntimeException('SITE_CHARSET must be defined.');
        }

        return static::convertCharset($string, 'utf-8', SITE_CHARSET);
    }

    /**
     * Returns true if provided PERSON_TYPE_ID is corporate customer
     *
     * @param string $personTypeId
     *
     * @return bool
     */
    public static function isPersonCorporate(string $personTypeId): bool
    {
        return ConfigProvider::getContragentTypeForPersonType($personTypeId) === Constants::CORPORATE_CONTRAGENT_TYPE;
    }

    /**
     * @return string
     */
    public static function createPlaceholderEmail(): string
    {
        return uniqid('user_' . time(), false) . '@example.com';
    }

    /**
     * @return string
     */
    public static function createPlaceholderPassword(): string
    {
        return uniqid("R", false);
    }

    /**
     * @param string $string
     * @param string $inCharset
     * @param string $outCharset
     *
     * @return array|bool|\SplFixedArray|string
     */
    protected static function convertCharset(string $string, string $inCharset, string $outCharset)
    {
        $error = '';
        $result = Encoding::convertEncoding($string,  $inCharset, $outCharset, $error);

        if (!$result && !empty($error)) {
            throw new \RuntimeException($error);
        }

        return $result;
    }
}
