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

    public function setUp()
    {
        parent::setUp();

    }

    public function testAddressBuild()
    {
        $this->addressBuilder = new AdressBuilder();
        $this->addressBuilder->setDataCrm($this->getDataBuilder())->build();

        $this->assertNotEmpty($this->addressBuilder);
    }

    private function getDataBuilder()
    {
        return array(
            'address' => array(
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
            ),
        );
    }
}
