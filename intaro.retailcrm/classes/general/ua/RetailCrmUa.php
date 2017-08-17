<?php
class RetailCrmUa
{
    public static $MODULE_ID = 'intaro.retailcrm';
    public static $CRM_UA = 'ua';
    public static $CRM_UA_INDEX = 'ua_index';
    public static $CRM_UA_ID = 'ua_id';
    
    public static function add()
    {
        $ua = COption::GetOptionString(self::$MODULE_ID, self::$CRM_UA, 0);
        $uaIndex = COption::GetOptionString(self::$MODULE_ID, self::$CRM_UA_INDEX, 0);
        $uaId = COption::GetOptionString(self::$MODULE_ID, self::$CRM_UA_ID, 0);
        if ($ua === 'Y' && $uaIndex && $uaId && ADMIN_SECTION !== true) {
            global $APPLICATION;

            $ua = "
            <script>
                (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
                (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
                m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
                })(window,document,'script','//www.google-analytics.com/analytics.js','ga');
                ga('create', '" . $uaId . "', 'auto');
                function getRetailCrmCookie(name) {
                      var matches = document.cookie.match(new RegExp(
                          '(?:^|; )' + name + '=([^;]*)'
                      ));
                      return matches ? decodeURIComponent(matches[1]) : '';
                }
                ga('set', 'dimension" . $uaIndex . "', getRetailCrmCookie('_ga'));
                ga('send', 'pageview');
            ";
            if (isset($_GET['ORDER_ID'])) {
                CModule::IncludeModule("sale");
                $arOrder = CSaleOrder::GetByID($_GET['ORDER_ID']);
                $ua .= "
                    ga('require', 'ecommerce', 'ecommerce.js');
                    ga('ecommerce:addTransaction', {
                      'id': $arOrder[ID],
                      'affiliation': $_SERVER[SERVER_NAME], 
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
            }
            $ua .= "</script>";

            $APPLICATION->AddHeadString($ua);
        }
    }
}