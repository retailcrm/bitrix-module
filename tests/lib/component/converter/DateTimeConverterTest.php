<?php

namespace Tests\Intaro\RetailCrm\Component\Converter;

use Bitrix\Main\Type\DateTime;
use PHPUnit\Framework\TestCase;
use Intaro\RetailCrm\Component\Converter\DateTimeConverter;

class DateTimeConverterTest extends TestCase
{
    public function testPhpToBitrix(): void
    {
        $dateTime = \DateTime::createFromFormat(\DateTime::RFC3339, '1970-01-01T00:01:02');
        $bitrixDateTime = DateTimeConverter::phpToBitrix($dateTime);

        self::assertEquals(
            $dateTime->format(\DateTime::RFC3339),
            $bitrixDateTime->format(\DateTime::RFC3339)
        );
    }

    public function testBitrixToPhp(): void
    {
        $dateTime = \DateTime::createFromFormat(\DateTime::RFC3339, '1970-01-01T00:01:02');
        $bitrixDateTime = DateTime::createFromPhp($dateTime);
        $dateTime = DateTimeConverter::bitrixToPhp($bitrixDateTime);

        self::assertEquals(
            $bitrixDateTime->format(\DateTime::RFC3339),
            $dateTime->format(\DateTime::RFC3339)
        );
    }
}
