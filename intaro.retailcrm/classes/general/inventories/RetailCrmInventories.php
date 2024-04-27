<?php
IncludeModuleLangFile(__FILE__);

/**
 * @category RetailCRM
 * @package  RetailCRM\Inventories
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

/**
 * Class RetailCrmInventories
 *
 * @category RetailCRM
 * @package RetailCRM\Inventories
 */
class RetailCrmInventories
{
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

	$api = new RetailCrm\ApiClient(RetailcrmConfigProvider::getApiUrl(), RetailcrmConfigProvider::getApiKey());
	$infoBlocks = RetailcrmConfigProvider::getInfoblocksInventories();
	$stores = RetailcrmConfigProvider::getStores();
	$shops = RetailcrmConfigProvider::getShops();

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
                    $storesChunks = array_chunk($stores, 50, true);
                    foreach ($storesChunks as $storesChunk) {
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
                            array('PRODUCT_ID' => $elems, 'STORE_ID' => array_keys($storesChunk)),
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
                                'code' => $storesChunk[$arStoreProduct['STORE_ID']],
                                'available' => self::switchCount($arStoreProduct['AMOUNT'], $inventoriesType[$storesChunk[$arStoreProduct['STORE_ID']]]),
                            );
                        }    
                        //for log                  
                        $splitedItems = array_chunk($invUpload, 200);
                        foreach ($splitedItems as $chunk) {
                            Logger::getInstance()->write($chunk, 'inventoriesUpload');

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
