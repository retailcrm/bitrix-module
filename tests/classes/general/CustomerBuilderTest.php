<?php

/**
 * Class CustomerBuilderTest
 */
class CustomerBuilderTest extends \PHPUnit\Framework\TestCase
{
    /**@var object $customer */
    public $customer;

    /**@var object $addressBuilder */
    public $addressBuilder;

    /**@var array $dataCrm */
    protected $dataCrm;

    public function setUp()
    {
        parent::setUp();

    }

    public function testCustomerBuild()
    {
        $this->customer = new CustomerBuilder();
        $this->customer->setDataCrm($this->getDataBuilder())->build();

        $this->assertNotEmpty($this->customer);
    }

    private function getDataBuilder()
    {
        return array(
            "type"=>"customer",
            "id"=> 20250,
            "createdAt"=> "2020-05-13 16:34:54",
            "site"=> "bitrix-local",
            "marginSumm"=> 0,
            "totalSumm"=> 0,
            "averageSumm"=> 0,
            "ordersCount"=> 0,
            "customFields"=> array(
                "faxcliente"=> "11",
                "tipodecliente"=> "11",
            ),
            "personalDiscount"=> 0,
            "cumulativeDiscount"=> 0,
            "address"=> array(
                "id"=> 13748,
                "index"=> "346000",
                "countryIso"=>"RU",
                "region"=>"Ростовская область",
                "regionId"=> 73,
                "city"=> "Ростов-на-Дону",
                "cityId"=> 4298,
                "cityType"=> "г.",
                "street"=> "Большая Садовая",
                "streetId"=> 1583457,
                "streetType"=>"ул.",
                "building"=>"1",
                "flat"=> "1",
                "floor"=> "1",
                "block"=> "1",
                "house"=> "1",
                "housing"=> "1",
                "notes"=> "111",
                "text"=>"ул. Большая Садовая, д. 1, стр. 1, корп. 1, кв./офис 1, под. 1, эт. 1, 111",
            ),
            "firstName"=> "mmm",
            "lastName"=> "mmm",
            "patronymic"=> "mmm",
            "sex"=> "female",
            "email"=> "mm@mm.mmm",
            "phones"=> array(
                "0" => array(
                    "number"=> "474747856878",
                )
            ),
            "birthday"=> "2020-05-13",
            "create"=> 1
        );
    }
}
