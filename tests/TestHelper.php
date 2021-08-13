<?php

trait TestHelper
{
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

    private function getDateTime(): \Bitrix\Main\Type\DateTime
    {
        return \Bitrix\Main\Type\DateTime::createFromPhp(new DateTime('2000-01-01'));
    }
}