<?php

use Bitrix\Sale\Order;
use Bitrix\Main\Context;
use Bitrix\Catalog\StoreTable;

class BitrixOrderService
{
    public static function getCountryList()
    {
        $server = Context::getCurrent()->getServer()->getDocumentRoot();
        $countryList = [];

        if (file_exists($server . '/bitrix/modules/intaro.retailcrm/classes/general/config/country.xml')) {
            $countryFile = simplexml_load_file($server . '/bitrix/modules/intaro.retailcrm/classes/general/config/country.xml');

            foreach ($countryFile->country as $country) {
                $countryList[RCrmActions::fromJSON((string) $country->name)] = (string) $country->alpha;
            }
        }

        return $countryList;
    }

    public static function getPickupPointAddress($arOrder)
    {
        $address = '';
        $orderInfo = Order::load($arOrder['ID']);

        foreach ($orderInfo->getShipmentCollection() as $store) {
            $storeId = $store->getStoreId();

            if ($storeId) {
                $arStore = StoreTable::getRow([
                    'filter' => [
                        'ID' => $storeId,
                    ]
                ]);

                if (!empty($arStore['ADDRESS'])) {
                    $address = 'Пункт самовывоза: ' . $arStore['ADDRESS'];

                    break;
                }
            }
        }

        return $address;
    }
}
