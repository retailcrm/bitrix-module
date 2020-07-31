<?php

namespace Tests\Intaro\RetailCrm\Component\Converter;

use Bitrix\Main\Type\DateTime;
use PHPUnit\Framework\TestCase;
use Intaro\RetailCrm\Component\Converter\DateTimeConverter;

class DateTimeConverterTest extends TestCase
{
    /**
     * Better rely on preconfigured format & data
     */
    const FORMAT = 'Y-m-d\TH:i:s';

    /** @var string */
    const FORMAT_DATE = '1970-01-01T12:30:45';

    public function testPhpToBitrix(): void
    {
        $dateTime = \DateTime::createFromFormat(self::FORMAT, self::FORMAT_DATE);
        $bitrixDateTime = DateTimeConverter::phpToBitrix($dateTime);

        self::assertEquals(
            $dateTime->format(\DateTime::RFC3339),
            $bitrixDateTime->format(\DateTime::RFC3339)
        );
    }

    public function testBitrixToPhp(): void
    {
        $dateTime = \DateTime::createFromFormat(self::FORMAT, self::FORMAT_DATE);
        $bitrixDateTime = DateTime::createFromPhp($dateTime);
        $dateTime = DateTimeConverter::bitrixToPhp($bitrixDateTime);

        self::assertEquals(
            $bitrixDateTime->format(\DateTime::RFC3339),
            $dateTime->format(\DateTime::RFC3339)
        );
    }
}
