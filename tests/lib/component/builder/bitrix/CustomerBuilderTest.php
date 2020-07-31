<?php

namespace Tests\Intaro\RetailCrm\Component\Builder\Bitrix;

use Intaro\RetailCrm\Component\Builder\Bitrix\CustomerBuilder;
use Intaro\RetailCrm\Component\Json\Deserializer;
use Intaro\RetailCrm\Model\Api\Customer;
use PHPUnit\Framework\TestCase;

/**
 * Class CustomerBuilderTest
 */
class CustomerBuilderTest extends TestCase
{
    /**@var CustomerBuilder $customer */
    public $customer;

    /**@var array $dataCrm */
    protected $dataCrm;

    public function testCustomerBuild()
    {
        $this->customer = new CustomerBuilder();
        $user = $this->customer->setCustomer($this->getDataBuilder())->build()->getResult();

        self::assertEquals("mm@mm.mmm", $user->getEmail());
        self::assertEquals("mmm", $user->getName());
        self::assertEquals("mmm", $user->getLastName());
        self::assertEquals("mmm", $user->getSecondName());
        self::assertEquals("474747856878", $user->getPersonalPhone());
        self::assertEquals("346000", $user->getPersonalZip());
        self::assertEquals("Ростов-на-Дону", $user->getPersonalCity());
        self::assertEquals("13.05.2020", $user->getPersonalBirthday()->format('d.m.Y'));
        self::assertEquals("female", $user->getPersonalGender());
    }

    /**
     * @return Customer
     */
    private function getDataBuilder(): Customer
    {
        $customerArray = [
            "type"=>"customer",
            "id"=> 20250,
            "createdAt"=> "2020-05-13 16:34:54",
            "site"=> "bitrix-local",
            "marginSumm"=> 0,
            "totalSumm"=> 0,
            "averageSumm"=> 0,
            "ordersCount"=> 0,
            "customFields"=> [
                "faxcliente"=> "11",
                "tipodecliente"=> "11",
            ],
            "personalDiscount"=> 0,
            "cumulativeDiscount"=> 0,
            "address"=> [
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
            ],
            "firstName"=> "mmm",
            "lastName"=> "mmm",
            "patronymic"=> "mmm",
            "sex"=> "female",
            "email"=> "mm@mm.mmm",
            "phones"=> [
                "0" => [
                    "number"=> "474747856878",
                ]
            ],
            "birthday"=> "2020-05-13",
            "create"=> 1
        ];

        return Deserializer::deserializeArray($customerArray, Customer::class);
    }
}
