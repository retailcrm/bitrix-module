<?php
IncludeModuleLangFile(__FILE__);
class RetailCrmPrices
{
    public static $MODULE_ID = 'intaro.retailcrm';
    public static $CRM_API_HOST_OPTION = 'api_host';
    public static $CRM_API_KEY_OPTION = 'api_key';
    public static $CRM_PRICES_UPLOAD = 'prices_upload';
    public static $CRM_PRICES = 'prices';
    public static $CRM_PRICE_SHOPS = 'price_shops';
    public static $CRM_IBLOCKS_PRICES = 'iblock_prices';
    public static $pageSize = 500;
    
    public static function pricesUpload()
    { 
        if (!CModule::IncludeModule('iblock')) {
            RCrmActions::eventLog('RetailCrmHistory::orderHistory', 'iblock', 'module not found');

            return false;
        }
        if (!CModule::IncludeModule('sale')) {
            RCrmActions::eventLog('RetailCrmHistory::orderHistory', 'sale', 'module not found');

            return false;
        }
        if (!CModule::IncludeModule('catalog')) {
            RCrmActions::eventLog('RetailCrmHistory::orderHistory', 'catalog', 'module not found');

            return false;
        }

	$api_host = COption::GetOptionString(self::$MODULE_ID, self::$CRM_API_HOST_OPTION, 0);
        $api_key = COption::GetOptionString(self::$MODULE_ID, self::$CRM_API_KEY_OPTION, 0);
	$api = new RetailCrm\ApiClient($api_host, $api_key);

        $infoBlocks = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_IBLOCKS_PRICES, 0));
	$prices = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_PRICES, 0));
	$shops = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_PRICE_SHOPS, 0));

        if (count($shops) == 0) {
            RCrmActions::eventLog('RetailCrmPrices::pricesUpload()', '$shops', 'No stores selected for download');
            
            return false;
        }

        if (count($prices) == 0) {
            RCrmActions::eventLog('RetailCrmPrices::pricesUpload()', '$prices', 'No prices selected for download');
            
            return false;
        }
        
        if (count($infoBlocks) > 0) {
            $log = new Logger();
            
            foreach ($infoBlocks as $id) {
                $iblockOffer = CCatalogSKU::GetInfoByProductIBlock($id);

                $arNavStatParams = array(
                    'iNumPage' => 1,
                    'nPageSize' => self::$pageSize,
                );

                do {         
                    $dbResProductsIds = CIBlockElement::GetList(array('ID'), array('IBLOCK_ID' => $id), false, $arNavStatParams, array('ID'));
                    $products = array();
                    while ($product = $dbResProductsIds->fetch()) {
                        $products[$product['ID']] = $product;
                        $products[$product['ID']]['offers'] = array();
                    }

                    if (!empty($iblockOffer['IBLOCK_ID'])) {
                        $arFilterOffer = array(
                            'IBLOCK_ID' => $iblockOffer['IBLOCK_ID'],
                            'PROPERTY_' . $iblockOffer['SKU_PROPERTY_ID'] => array_keys($products),
                        );

                        $dbResOffers = CIBlockElement::GetList(array('ID'), $arFilterOffer, false, false, array('ID', 'PROPERTY_' . $iblockOffer['SKU_PROPERTY_ID']));
                        while ($offer = $dbResOffers->fetch()) { 
                            $products[$offer['PROPERTY_' . $iblockOffer['SKU_PROPERTY_ID'] . '_VALUE']]['offers'][] = $offer['ID'];
                        }
                    }

                    $elems = array();
                    foreach ($products as $product) {
                        if (count($product['offers']) > 0) {
                            $elems = array_merge($elems, $product['offers']);
                        } else {
                            $elems[] = $product['ID'];
                        }
                    }

                    $pricesUpload = array();
                    $dbPricesProduct = CPrice::GetList(
                        array(),
                        array('PRODUCT_ID' => $elems, 'CATALOG_GROUP_ID' => array_keys($prices)),
                        false,
                        false,
                        array('PRODUCT_ID', 'CATALOG_GROUP_ID', 'PRICE')
                    );
                    while ($arPricesProduct = $dbPricesProduct->Fetch()) {
                        foreach ($shops as $shop) {
                            if (!isset($pricesUpload[$arPricesProduct['PRODUCT_ID'] . '-' . $shop])) {
                                $pricesUpload[$arPricesProduct['PRODUCT_ID'] . '-' . $shop] = array(
                                    'externalId' => $arPricesProduct['PRODUCT_ID'],
                                    'site' => $shop
                                );
                            }
                            $pricesUpload[$arPricesProduct['PRODUCT_ID'] . '-' . $shop]['prices'][] = array(
                                'code' => $prices[$arPricesProduct['CATALOG_GROUP_ID']],
                                'price' => $arPricesProduct['PRICE'],
                            );
                        }
                    }  

                    //for log
                    $splitedItems = array_chunk($pricesUpload, 200);
                    foreach ($splitedItems as $chunk) {
                        $log->write($chunk, 'storePricesUpload');
                        
                        foreach ($shops as $shop) {
                            RCrmActions::apiMethod($api, 'storePricesUpload', __METHOD__, $chunk, $shop);
                            time_nanosleep(0, 250000000);
                        }
                    }

                    $arNavStatParams['iNumPage'] = $dbResProductsIds->NavPageNomer + 1;
                } while($dbResProductsIds->NavPageNomer < $dbResProductsIds->NavPageCount);
            }
        } else {
            RCrmActions::eventLog('RetailCrmPrices::pricesUpload()', '$infoBlocks', 'No iblocks selected');
            
            return false;
        }
        
        return 'RetailCrmPrices::pricesUpload();';
    }
}