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
            'code' => 'boxberry',
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

    public function testOnUnsetIntegrationDeliveryFields()
    {
        $newParams = RetailCrmService::unsetIntegrationDeliveryFields($this->paramsExample);
        $expectedArray = $this->paramsExample;
        unset($expectedArray['firstName']);
        unset($expectedArray['lastName']);
        unset($expectedArray['delivery']['address']);
        unset($expectedArray['weight']);
        unset($expectedArray['phone']);
        unset($expectedArray['deliveryCost']);
        unset($expectedArray['paymentType']);
        unset($expectedArray['shipmentStore']);
        unset($expectedArray['delivery']['data']);

        $this->assertEquals($newParams, $expectedArray);
    }
}