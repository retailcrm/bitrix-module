<?php

/**
 * Class AddressBuilderTest
 */

class AddressBuilderTest extends \PHPUnit\Framework\TestCase
{
    /**@var object $addressBuilder */
    public $addressBuilder;

    /**@var array $dataCrm */
    protected $dataCrm;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testAddressBuild()
    {
        $this->addressBuilder = new AddressBuilder();
        $this->addressBuilder->setDataCrm($this->getDataBuilder())->build();
        $this->assertNotEmpty($this->addressBuilder);
        $addressResult = $this->addressBuilder->getCustomerAddress()->getObjectToArray();

        $this->assertEquals("346000", $addressResult["index"]);
        $this->assertEquals("RU", $addressResult["country"]);
        $this->assertEquals("Ростовская область", $addressResult["region"]);
        $this->assertEquals("Ростов-на-Дону", $addressResult["city"]);
        $this->assertEquals("Большая Садовая", $addressResult["street"]);
        $this->assertEquals("3", $addressResult["building"]);
        $this->assertEquals("3", $addressResult["flat"]);
        $this->assertEquals("3", $addressResult["floor"]);
        $this->assertEquals("3", $addressResult["block"]);
        $this->assertEquals("3", $addressResult["house"]);
        $this->assertEquals("Дополнительная информация", $addressResult["notes"]);
        $this->assertEquals(
            "ул. Большая Садовая, д. 3, стр. 3, корп. 3, кв./офис 3, под. 3, эт. 3, Дополнительная информация",
            $addressResult["text"]
        );
    }

    private function getDataBuilder()
    {
        return array(
            "id" => "13743",
            "index" => "346000",
            "countryIso" => "RU",
            "region" => "Ростовская область",
            "regionId" => "73",
            "city" => "Ростов-на-Дону",
            "cityId" => "4298",
            "cityType" => "г.",
            "street" => "Большая Садовая",
            "streetId" => "1583457",
            "streetType" => "ул.",
            "building" => "3",
            "flat" => "3",
            "floor" => "3",
            "block" =>"3",
            "house" => "3",
            "housing" => "3",
            "notes" => "Дополнительная информация",
            "text" => "ул. Большая Садовая, д. 3, стр. 3, корп. 3, кв./офис 3, под. 3, эт. 3, Дополнительная информация",
        );
    }
}
