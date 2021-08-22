<?php
/**
* Class BitrixTestCase
*/
class BitrixTestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @var bool
     */
    protected $backupGlobals = false;
    
    /**
     * @var \Generator
     */
    protected $faker;
    
    /**
     * этот метод phpUnit вызывает перед запуском текущего теста
     * @inheritdoc
     */
    public function setUp()
    {
        // создание экземпляра Faker, который будет создавать рандомные данные
        $this->faker = \Faker\Factory::create();
    }
    
    /**
     * этот метод phpUnit вызывает после исполнения текущего теста
     * @inheritdoc
     */
    public function tearDown()
    {
        // без этого вызова Mockery не будет работать
        \Mockery::close();
    }

    public function getArFields(): array
    {
        return [
            'ID' => 1,
            'NUMBER' => "1",
            'USER_ID' => "1",
            'STATUS_ID' => "1",
            'PERSON_TYPE_ID' => 'bitrixType',
            'DATE_INSERT' => '2015-02-22 00:00:00',
            'USER_DESCRIPTION' => 'userComment',
            'COMMENTS' => 'managerComment',
            'PRICE_DELIVERY' => '100',
            'PROPS' => ['properties' => []],
            'DELIVERYS' => [[
                'id' => 'test',
                'service' => 'service'
            ]],
            'BASKET' => [],
            'PAYMENTS' => [[
                'ID' => 1,
                'PAY_SYSTEM_ID' => 'bitrixPayment',
                'SUM' => 1000,
                'DATE_PAID' => $this->getDateTime(),
                'PAID' => 'Y'
            ]]
        ];
    }

    public function getDateTime(): \Bitrix\Main\Type\DateTime
    {
        return \Bitrix\Main\Type\DateTime::createFromPhp(new DateTime('2000-01-01'));
    }
}
