<?php

/**
 * @category RetailCRM
 * @package  RetailCRM\Cart
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

use Bitrix\Main\Context\Culture;
use Bitrix\Sale\Basket;

IncludeModuleLangFile(__FILE__);


/**
 * Class RetailCrmCart
 *
 * @category RetailCRM
 * @package RetailCRM\Cart
 */
class RetailCrmCart
{
    private static string $dateFormat = "Y-m-d H:i:sP";

    /**
     * @param array $arBasket
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     *
     * @return array|null
     */
    public static function handlerCart(array $arBasket)
    {
        $api = new RetailCrm\ApiClient(RetailcrmConfigProvider::getApiUrl(), RetailcrmConfigProvider::getApiKey());
        $optionsSitesList = RetailcrmConfigProvider::getSitesList();

        if ($optionsSitesList) {
            if (array_key_exists($arBasket['LID'], $optionsSitesList) && $optionsSitesList[$arBasket['LID']] !== null) {
                $site = $optionsSitesList[$arBasket['LID']];

                $api->setSite($site);
            } else {
                RCrmActions::eventLog(
                    'RetailCrmCart::handlerCart',
                    'RetailcrmConfigProvider::getSitesList',
                    'Error set site'
                );

                return null;
            }
        } else {
            $site = RetailcrmConfigProvider::getSitesAvailable();
        }

        $crmBasket = RCrmActions::apiMethod($api, 'cartGet', __METHOD__, $arBasket['USER_ID'], $site);

        if (empty($arBasket['BASKET'])) {
            if (!empty($crmBasket['cart']['items'])) {
                return RCrmActions::apiMethod(
                    $api,
                    'cartClear',
                    __METHOD__,
                    [
                        'clearedAt' => date(self::$dateFormat),
                        'customer' => [
                            'externalId' => $arBasket['USER_ID']
                        ]
                    ],
                    $site
                );
            }

            return null;
        }

        $date = 'createdAt';
        $items = [];

        foreach ($arBasket['BASKET'] as $itemBitrix) {
            $item['quantity'] = $itemBitrix['QUANTITY'];
            $item['price'] =  $itemBitrix['PRICE'];
            $item['createdAt'] = $itemBitrix['DATE_INSERT']->format(self::$dateFormat);
            $item['updateAt'] = $itemBitrix['DATE_UPDATE']->format(self::$dateFormat);
            $item['offer']['externalId'] = $itemBitrix['PRODUCT_ID'];
            $items[] = $item;
        }

        if (!empty($crmBasket['cart']['items'])) {
            $date = 'updatedAt';
        }

        return RCrmActions::apiMethod(
            $api,
            'cartSet',
            __METHOD__,
            [
                'customer' => [
                    'externalId' => $arBasket['USER_ID'],
                    'site' => $site,
                ],
                $date => date(self::$dateFormat),
                'droppedAt' => date(self::$dateFormat),
                'items' => $items,
                'link' => static::generateCartLink(),
            ],
            $site
        );
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
            RCrmActions::eventLog('RetailCrmEvent::onChangeBasket', 'getBasketArray', 'event error');

            return null;
        }

        $arBasket = [
            'LID' => $obBasket->getSiteId(),
        ];

        $items = $obBasket->getBasket();

        foreach ($items as $item) {
            $arBasket['BASKET'][] = $item->getFields();
        }

        return $arBasket;
    }

    public static function generateCartLink()
    {
        return sprintf(
            '%s://%s/personal/cart',
            !empty($_SERVER['HTTPS']) ? 'https' : 'http',
            $_SERVER['HTTP_HOST']
        );
    }
}
