<?php
/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Component\Converter
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Component\Converter;

use Bitrix\Main\Type\DateTime as BitrixDateTime;

/**
 * Class DateTimeConverter
 *
 * @package Intaro\RetailCrm\Component\Converter
 */
class DateTimeConverter
{
    /**
     * Intermediate format for converting Bitrix DateTime to PHP DateTime
     */
    const INTERMEDIATE_FORMAT = 'U';

    /**
     * Converts Bitrix DateTime to php version
     *
     * @param \Bitrix\Main\Type\DateTime $dateTime
     *
     * @return \DateTime
     */
    public static function bitrixToPhp(BitrixDateTime $dateTime): \DateTime
    {
        return \DateTime::createFromFormat(
            static::INTERMEDIATE_FORMAT,
            $dateTime->format(static::INTERMEDIATE_FORMAT)
        );
    }

    /**
     * Converts PHP DateTime to Bitrix version
     *
     * @param \DateTime $dateTime
     *
     * @return \Bitrix\Main\Type\DateTime
     */
    public static function phpToBitrix(\DateTime $dateTime): BitrixDateTime
    {
        return BitrixDateTime::createFromPhp($dateTime);
    }
}
