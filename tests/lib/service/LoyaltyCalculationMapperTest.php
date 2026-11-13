<?php

use Intaro\RetailCrm\Model\Api\CodeValueModel;
use Intaro\RetailCrm\Model\Api\OrderProduct;
use Intaro\RetailCrm\Model\Api\SerializedOrderProductOffer;
use Intaro\RetailCrm\Service\LoyaltyCalculationMapper;

class LoyaltyCalculationMapperTest extends \PHPUnit\Framework\TestCase
{
    public function testMapsResolvedOffers(): void
    {
        $items = [$this->item('314'), $this->item('299')];
        self::assertTrue((new LoyaltyCalculationMapper())->map($items, [
            $this->row(27, 299), $this->row(28, 314),
        ]));
        self::assertSame('28', $items[0]->externalIds[0]->value);
        self::assertSame('27', $items[1]->externalIds[0]->value);
    }

    public function testKeepsBasketIds(): void
    {
        $items = [$this->item('299'), $this->item('299')];
        foreach (['28', '27'] as $key => $basketId) {
            $id = new CodeValueModel();
            $id->code = 'bitrixBasketId';
            $id->value = $basketId;
            $items[$key]->externalIds = [$id];
        }
        self::assertTrue((new LoyaltyCalculationMapper())->map($items, [
            $this->row(27, 299), $this->row(28, 299),
        ]));
        self::assertSame('28', $items[0]->externalIds[0]->value);
    }

    public function testRejectsAmbiguousRows(): void
    {
        $items = [$this->item('299'), $this->item('299')];
        self::assertFalse((new LoyaltyCalculationMapper())->map($items, [
            $this->row(27, 299), $this->row(28, 299),
        ]));
        self::assertEmpty($items[0]->externalIds);
        self::assertFalse((new LoyaltyCalculationMapper())->map([], [$this->row(27, 299)]));
    }

    private function item(string $productId): OrderProduct
    {
        $item = new OrderProduct();
        $item->offer = new SerializedOrderProductOffer();
        $item->offer->externalId = $productId;
        $item->externalIds = [];
        return $item;
    }

    private function row(int $id, int $productId): array
    {
        return ['ID' => $id, 'PRODUCT_ID' => $productId, 'QUANTITY' => 1, 'BASE_PRICE' => 200];
    }
}
