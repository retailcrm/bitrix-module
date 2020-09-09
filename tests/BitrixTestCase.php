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
}
