<?php

/**
 * Class RetailCrmServiceTest
 */
class RetailCrmServiceTest extends PHPUnit\Framework\TestCase
{
    private $paramsExample = array (
        'number' => '5958C',
        'externalId' => '8',
        'createdAt' => '2020-06-22 16:47:49',
        'customer' => array (
            'externalId' => '3',
        ),
        'orderType' => 'eshop-individual',
        'status' => 'prepayed',
        'delivery' => array (
            'cost' => '0',
            'address' => array (
                'text' => 'ул. Первомайская 41',
            ),
        ),
        'contragent' => array (
            'contragentType' => 'individual',
        ),
        'discountManualAmount' => '0',
        'discountManualPercent' => '0',
        'items' => array (
            array (
                'externalIds' => array (
                    array (
                        'code' => 'bitrix',
                        'value' => '0_88',
                    ),
                ),
                'quantity' => '1',
                'offer' => array (
                    'externalId' => '88',
                    'xmlId' => '248',
                ),
                'productName' => 'Agustí Torelló Mata GR Barrica 2011',
                'id' => '9072',
                'discountManualPercent' => '0',
                'discountManualAmount' => '0',
                'initialPrice' => '21.25',
            ),
        ),
    );

    private $errorsExample = array (
        'order[firstName]: This value is used in integration delivery and can`t be changed through API.',
        'order[lastName]: This value is used in integration delivery and can`t be changed through API.',
        'order[delivery][address]: This value is used in integration delivery and can`t be changed through API.',
    );

    public function testOnUnsetIntegrationDeliveryFields()
    {
        $newParams = RetailCrmService::unsetIntegrationDeliveryFields($this->paramsExample, $this->errorsExample);
        $expectedArray = $this->paramsExample;
        unset($expectedArray['firstName']);
        unset($expectedArray['lastName']);
        unset($expectedArray['delivery']['address']);

        $this->assertEquals($newParams, $expectedArray);
    }
}
