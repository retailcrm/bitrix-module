<?php
IncludeModuleLangFile(__FILE__);
class RetailCrmInventories
{
    public static $MODULE_ID = 'intaro.retailcrm';
    public static $CRM_API_HOST_OPTION = 'api_host';
    public static $CRM_API_KEY_OPTION = 'api_key';
    public static $CRM_INVENTORIES_UPLOAD = 'inventories_upload';
    public static $CRM_STORES = 'stores';
    public static $CRM_SHOPS = 'shops';
    public static $CRM_IBLOCKS_INVENTORIES = 'iblocks_inventories';   
    public static $pageSize = 500;

    public static function inventoriesUpload()
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
	
	$infoBlocks = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_IBLOCKS_INVENTORIES, 0));
	$stores = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_STORES, 0));
	$shops = unserialize(COption::GetOptionString(self::$MODULE_ID, self::$CRM_SHOPS, 0));

        try {
            $inventoriesList = $api->storesList()->stores;
        } catch (\RetailCrm\Exception\CurlException $e) {
            RCrmActions::eventLog(
                'RetailCrmInventories::inventoriesUpload()', 'RetailCrm\ApiClient::storesList::CurlException',
                $e->getCode() . ': ' . $e->getMessage()
            );
            
            return false;
        }
        
        $inventoriesType = array();
        if (count($inventoriesList) > 0) {
            foreach ($inventoriesList as $inventory) {
                $inventoriesType[$inventory['code']] = $inventory['inventoryType'];
            }        
        } else {
            RCrmActions::eventLog('RetailCrmInventories::inventoriesUpload()', '$inventoriesList', 'Stores in crm not found');

            return false;
        }
        if (count($shops) == 0) {
            RCrmActions::eventLog('RetailCrmInventories::inventoriesUpload()', '$shops', 'No stores selected for download');

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
                    $chunkStores = array_chunk($stores, 50, true);
                    foreach ($chunkStores as $stores) { 
                        foreach ($products as $product) {
                            if (count($product['offers']) > 0) {
                                $elems = array_merge($elems, $product['offers']);
                            } else {
                                $elems[] = $product['ID'];
                            }
                        }

                        $invUpload = array();
                        $dbStoreProduct = CCatalogStoreProduct::GetList(
                            array(),
                            array('PRODUCT_ID' => $elems, 'STORE_ID' => array_keys($stores)),
                            false,
                            false,
                            array('PRODUCT_ID', 'STORE_ID', 'AMOUNT')
                        );
                        while ($arStoreProduct = $dbStoreProduct->Fetch()) {
                            if (!isset($invUpload[$arStoreProduct['PRODUCT_ID']])) {
                                $invUpload[$arStoreProduct['PRODUCT_ID']] = array(
                                    'externalId' => $arStoreProduct['PRODUCT_ID']
                                );
                            }
                            $invUpload[$arStoreProduct['PRODUCT_ID']]['stores'][] = array(
                                'code' => $stores[$arStoreProduct['STORE_ID']],
                                'available' => self::switchCount($arStoreProduct['AMOUNT'], $inventoriesType[$stores[$arStoreProduct['STORE_ID']]]),
                            );
                        }    
                        //for log                  
                        $splitedItems = array_chunk($invUpload, 200);
                        foreach ($splitedItems as $chunk) {
                            $log->write($chunk, 'inventoriesUpload');

                            foreach ($shops as $shop) {
                                RCrmActions::apiMethod($api, 'storeInventoriesUpload', __METHOD__, $chunk, $shop);
                                time_nanosleep(0, 250000000);
                            }
                        }

                        $arNavStatParams['iNumPage'] = $dbResProductsIds->NavPageNomer + 1;
                    }
                } while($dbResProductsIds->NavPageNomer < $dbResProductsIds->NavPageCount);
            }
        } else {
            RCrmActions::eventLog('RetailCrmInventories::inventoriesUpload()', '$infoBlocks', 'No iblocks selected');
            
            return false;
        }

        return 'RetailCrmInventories::inventoriesUpload();';
    }
    
    public static function switchCount($val, $type)
    {
        if ($val < 0) {
            $val = 0;
        } elseif ($val > 999999) {
            $val = 999999;
        }

        if ($type == 'available' && $val > 0) {
            $val = 1;
        }

        return $val;
    }
}