<?php

/**
 * @category RetailCRM
 * @package  RetailCRM\Ua
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

/**
 * Class RetailCrmUa
 *
 * @category RetailCRM
 * @package RetailCRM\Ua
 */
class RetailCrmUa
{
    public static $MODULE_ID = 'intaro.retailcrm';
    public static $CRM_UA = 'ua';
    public static $CRM_UA_KEYS = 'ua_keys';
    private const JSON_FLAGS = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
    
    public static function add()
    {
        $ua = COption::GetOptionString(self::$MODULE_ID, self::$CRM_UA, 0);
        $uaKeys = unserialize(
            COption::GetOptionString(self::$MODULE_ID, self::$CRM_UA_KEYS, 0),
            ['allowed_classes' => false]
        );
        $request = \Bitrix\Main\Context::getCurrent()->getRequest();

        if ($ua === 'Y' && !empty($uaKeys[SITE_ID]['ID']) && !empty($uaKeys[SITE_ID]['INDEX']) && $request->isAdminSection() !== true) {
            global $APPLICATION;
            $trackerId = json_encode((string) $uaKeys[SITE_ID]['ID'], self::JSON_FLAGS);
            $dimensionIndex = preg_replace('/\D+/', '', (string) $uaKeys[SITE_ID]['INDEX']);

            if ($dimensionIndex === '') {
                return;
            }

            $ua = "
            <script type=\"text/javascript\">
                (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
                (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
                m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
                })(window,document,'script','//www.google-analytics.com/analytics.js','ga');
                ga('create', " . $trackerId . ", 'auto');
                function getRetailCRMCookie(name) {
                      var matches = document.cookie.match(new RegExp(
                          '(?:^|; )' + name + '=([^;]*)'
                      ));
                      return matches ? decodeURIComponent(matches[1]) : '';
                }
                ga('set', 'dimension" . $dimensionIndex . "', getRetailCRMCookie('_ga'));
                ga('send', 'pageview');
            </script>";

            /**
             * В $_GET['ORDER_ID'] содержится номер заказа, а не его ID.
             * Номер может совпадать с ID заказа, но это необязательное условие,
             * то есть они могут отличаться.
             */
            if (isset($_GET['ORDER_ID'])) {
                CModule::IncludeModule("sale");
                $order = \Bitrix\Sale\Order::loadByAccountNumber($_GET['ORDER_ID']);

                if ($order instanceof \Bitrix\Sale\Order) {
                    $arOrder = array(
                        'ID' => $order->getId(),
                        'PRICE' => $order->getPrice(),
                        'DISCOUNT_VALUE' => $order->getField('DISCOUNT_VALUE')
                    );
                    $safeOrderId = json_encode((string) $arOrder['ID'], self::JSON_FLAGS);
                    $safeServerName = json_encode((string) ($_SERVER['SERVER_NAME'] ?? ''), self::JSON_FLAGS);
                    $safePrice = json_encode((float) $arOrder['PRICE'], self::JSON_FLAGS);
                    $safeDiscountValue = json_encode((float) $arOrder['DISCOUNT_VALUE'], self::JSON_FLAGS);

                    $ua .= "<script type=\"text/javascript\">
                    ga('require', 'ecommerce', 'ecommerce.js');
                    ga('ecommerce:addTransaction', {
                      'id': " . $safeOrderId . ",
                      'affiliation': " . $safeServerName . ", 
                      'revenue': " . $safePrice . ",
                      'tax': " . $safeDiscountValue . "
                    });
                    ";
                    $arBasketItems = CsaleBasket::GetList(
                        array('id' => 'asc'),
                        array('ORDER_ID' => $_GET['ORDER_ID']),
                        false,
                        false,
                        array('PRODUCT_ID', 'NAME', 'PRICE', 'QUANTITY', 'ORDER_ID', 'ID')
                    );  
                    while ($arItem = $arBasketItems->fetch()) {
                        $safeProductId = json_encode((string) $arItem['PRODUCT_ID'], self::JSON_FLAGS);
                        $safeProductName = json_encode((string) $arItem['NAME'], self::JSON_FLAGS);
                        $safeItemPrice = json_encode((float) $arItem['PRICE'], self::JSON_FLAGS);
                        $safeQuantity = json_encode((float) $arItem['QUANTITY'], self::JSON_FLAGS);

                        $ua .= "
                        ga('ecommerce:addItem', {
                            'id': " . $safeOrderId . ",
                            'sku': " . $safeProductId . ",
                            'name': " . $safeProductName . ",
                            'price': " . $safeItemPrice . ",
                            'quantity': " . $safeQuantity . "
                        });
                        ";
                    }
                    $ua .= "ga('ecommerce:send');";
                    $ua .= "</script>";
                }
            }

            $APPLICATION->AddHeadString($ua);
        }
    }
}
