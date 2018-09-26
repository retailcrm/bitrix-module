<?php
class RetailCrmUa
{
    public static $MODULE_ID = 'intaro.retailcrm';
    public static $CRM_UA = 'ua';
    public static $CRM_UA_KEYS = 'ua_keys';
    
    public static function add()
    {
        $ua = COption::GetOptionString(self::$MODULE_ID, self::$CRM_UA, 0);
        $uaKeys = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_UA_KEYS, 0));

        if ($ua === 'Y' && !empty($uaKeys[SITE_ID]['ID']) && !empty($uaKeys[SITE_ID]['INDEX']) && ADMIN_SECTION !== true) {
            global $APPLICATION;

            $ua = "
            <script type=\"text/javascript\">
                (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
                (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
                m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
                })(window,document,'script','//www.google-analytics.com/analytics.js','ga');
                ga('create', '" . $uaKeys[SITE_ID]['ID'] . "', 'auto');
                function getRetailCrmCookie(name) {
                      var matches = document.cookie.match(new RegExp(
                          '(?:^|; )' + name + '=([^;]*)'
                      ));
                      return matches ? decodeURIComponent(matches[1]) : '';
                }
                ga('set', 'dimension" . $uaKeys[SITE_ID]['INDEX'] . "', getRetailCrmCookie('_ga'));
                ga('send', 'pageview');
            </script>";
            if (isset($_GET['ORDER_ID'])) {
                CModule::IncludeModule("sale");
                $order = \Bitrix\Sale\Order::loadByAccountNumber($_GET['ORDER_ID']);

                if ($order !== null) {
                    $arOrder = array(
                        'ID' => $order->getId(),
                        'PRICE' => $order->getPrice(),
                        'DISCOUNT_VALUE' => $order->getField('DISCOUNT_VALUE')
                    );

                    $ua .= "<script type=\"text/javascript\">
                    ga('require', 'ecommerce', 'ecommerce.js');
                    ga('ecommerce:addTransaction', {
                      'id': $arOrder[ID],
                      'affiliation': '$_SERVER[SERVER_NAME]', 
                      'revenue': $arOrder[PRICE],
                      'tax': $arOrder[DISCOUNT_VALUE]
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
                        $ua .= "
                        ga('ecommerce:addItem', {
                            'id': $arOrder[ID],
                            'sku': '$arItem[PRODUCT_ID]',
                            'name': '$arItem[NAME]',
                            'price': $arItem[PRICE],
                            'quantity': $arItem[QUANTITY]
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