<?php

use Bitrix\Main\Context\Culture;
use Bitrix\Sale\Basket;

IncludeModuleLangFile(__FILE__);


/**
 * Class RetailCrmCart
 */
class RetailCrmCart
{
    public static function prepareCart(array $arBasket)
    {
        $api = new RetailCrm\ApiClient(RetailcrmConfigProvider::getApiUrl(), RetailcrmConfigProvider::getApiKey());
        $optionsSitesList = RetailcrmConfigProvider::getSitesList();

        if ($optionsSitesList) {
            if (array_key_exists($arBasket['LID'], $optionsSitesList) && $optionsSitesList[$arBasket['LID']] !== null) {
                $site = $optionsSitesList[$arBasket['LID']];
            } else {
                RCrmActions::eventLog(
                    'RetailCrmCart::prepareCart',
                    'RetailcrmConfigProvider::getSitesList',
                    'Error set site'
                );

                return null;
            }
        } else {
            $site = RetailcrmConfigProvider::getSitesAvailable();
        }
        //метод апи работает
        //if (!empty($arBasket['BASKET'])) {
            $crmBasket = RCrmActions::apiMethod($api, 'cartGet', __METHOD__, $arBasket['USER_ID'], $site);
            RCrmActions::eventLog(
                'RetailCrmCart::prepareCart',
                'RetailcrmConfigProvider::getSitesList',
                print_r($crmBasket, true)
            );
      //  }
        //$crmBasket конверт в массив нужен или юзать объект
       /* if (empty($arBasket['BASKET']) && $crmBasket) если не пустой, очищать.*/
        //$api->setSite($site);
    }

    /**
     * @throws \Bitrix\Main\SystemException
     *
     * @return array|null
     */
    public static function getBasketArray($event): ?array
    {
        if ($event instanceof Basket) {
            $obBasket = $event;
        } elseif ($event instanceof Event) {
            $obBasket = $event->getParameter('ENTITY');
        } else {
            RCrmActions::eventLog('RetailCrmEvent::onChangeBasket', 'events', 'event error');

            return null;
        }

        $culture = new Culture(['FORMAT_DATETIME' => 'Y-m-d HH:i:s']);
        $arBasket = [
            'LID' => $obBasket->getSiteId(),
        ];

        $items = $obBasket->getBasket();

        foreach ($items as $item) {
            $arBasket['BASKET'][] = $item->getFields();
        }

        return $arBasket;
    }
}
