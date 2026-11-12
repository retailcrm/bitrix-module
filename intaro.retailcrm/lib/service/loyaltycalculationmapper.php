<?php

namespace Intaro\RetailCrm\Service;

use Intaro\RetailCrm\Model\Api\CodeValueModel;

/** Resolves calculation rows without relying on their order in the CRM response. */
final class LoyaltyCalculationMapper
{
    public function map(array $items, array $basketItems): bool
    {
        if (count($items) !== count($basketItems)) {
            return false;
        }

        $byId = [];
        foreach ($basketItems as $row) {
            $byId[(string) $row['ID']] = $row;
        }
        $matched = [];
        $assignments = [];
        foreach ($items as $item) {
            $basketId = null;
            foreach ($item->externalIds ?? [] as $id) {
                if ($id->code === 'bitrixBasketId') {
                    $basketId = explode('#', (string) $id->value, 2)[0];
                    break;
                }
            }

            if ($basketId === null) {
                $candidates = [];
                foreach ($byId as $id => $row) {
                    $offerId = (string) ($item->offer->externalId ?? '');
                    $xmlId = (string) ($item->offer->xmlId ?? '');
                    $sameOffer = $xmlId !== '' && $xmlId === (string) ($row['XML_ID'] ?? '')
                        || isset($row['PRODUCT_ID']) && $offerId === (string) $row['PRODUCT_ID']
                        || !isset($row['PRODUCT_ID']) && $offerId === (string) $id;
                    if (!$sameOffer
                        || isset($item->quantity) && abs((float) $item->quantity - (float) $row['QUANTITY']) > 0.00001
                        || isset($item->initialPrice) && abs((float) $item->initialPrice - (float) ($row['BASE_PRICE'] ?? $row['FULL_PRICE'])) > 0.00001
                    ) {
                        continue;
                    }
                    $candidates[] = (string) $id;
                }
                if (count($candidates) !== 1) {
                    return false;
                }
                $basketId = $candidates[0];
            }

            if (!isset($byId[$basketId]) || isset($matched[$basketId])) {
                return false;
            }
            $matched[$basketId] = true;
            $assignments[] = [$item, $basketId];
        }

        foreach ($assignments as [$item, $basketId]) {
            $ids = array_values(array_filter($item->externalIds ?? [], static function ($id): bool {
                return $id->code !== 'bitrixBasketId';
            }));
            $id = new CodeValueModel();
            $id->code = 'bitrixBasketId';
            $id->value = $basketId;
            $ids[] = $id;
            $item->externalIds = $ids;
        }
        return true;
    }
}
