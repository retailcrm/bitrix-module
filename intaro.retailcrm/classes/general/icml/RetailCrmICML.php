<?php

use Bitrix\Highloadblock as HL;
use Bitrix\Main\Entity;

IncludeModuleLangFile(__FILE__);
class RetailCrmICML
{
    public $profileID;
    public $iblocks;
    public $filename;
    public $serverName;
    public $defaultServerName;
    public $propertiesSKU;
    public $propertiesUnitSKU;
    public $propertiesProduct;
    public $propertiesUnitProduct;
    public $highloadblockSkuProperties;
    public $highloadblockProductProperties;
    public $application;
    public $encoding = 'utf-8';
    public $encodingDefault = 'utf-8';
    public $loadPurchasePrice = false;
    public $productPictures;
    public $skuPictures;
    public $offerPageSize = 50;

    protected $fp;
    protected $mainSection = 1000000;
    protected $pageSize = 500;
    protected $protocol;

    protected $isLogged = false;
    protected $logFile = '/bitrix/catalog_export/i_crm_load_log.txt';
    protected $fpLog;

    protected $MODULE_ID = 'intaro.retailcrm';
    protected $CRM_CATALOG_BASE_PRICE = 'catalog_base_price';
    protected $PROTOCOL_OPTION = 'protocol';

    protected $measurement = array (
        'mm' => 1,          // 1 mm = 1 mm
        'cm' => 10,         // 1 cm = 10 mm
        'm' => 1000,
        'mg' => 0.001,      // 0.001 g = 1 mg
        'g' => 1,
        'kg' => 1000,
    );

    protected $measurementLink = array (
        'mm' => 'mm',
        'cm' => 'mm',
        'm' => 'mm',
        'mg' => 'g',
        'g' => 'g',
        'kg' => 'g',
    );

    protected $measure = array (
        'pc. 1' => 'pc',
        'm' => 'm',
        'l' => 'l',
        'kg' => 'kg',
    );

    public function Load()
    {
        global $USER;
        if (!isset($_SESSION["SESS_AUTH"]["USER_ID"]) || !$_SESSION["SESS_AUTH"]["USER_ID"]) {
            $USER = new CUser;
        }

        $this->isLogged = true;

        $defaultSite = CSite::GetList($by = "def", $order = "desc", array('DEF' => 'Y'))->Fetch();
        $this->encodingDefault = $defaultSite["CHARSET"];

        $this->protocol = COption::GetOptionString($this->MODULE_ID, $this->PROTOCOL_OPTION);

        $this->PrepareSettings();

        $this->fp = $this->PrepareFile($this->filename. '.tmp');

        if ($this->isLogged) {
            $this->fpLog = $this->PrepareFile($this->logFile);
            $this->WriteLog("Start Loading");
        }

        $this->PreWriteCatalog();

        $categories = $this->GetCategories();

        $this->WriteCategories($categories);

        $this->PreWriteOffers();
        $this->BuildOffers($categories);
        $this->PostWriteOffers();

        $this->PostWriteCatalog();

        if ($this->isLogged) {
            $this->WriteLog("Loading was ended successfully (peek memory usage: " . memory_get_peak_usage() . ")");
        }

        $this->CloseFile($this->fp);
        $this->CloseFile($this->fpLog);
        unlink($defaultSite['ABS_DOC_ROOT'] . $this->filename);
        rename($defaultSite['ABS_DOC_ROOT'] . $this->filename. '.tmp', $defaultSite['ABS_DOC_ROOT'] . $this->filename);

        return true;

    }

    private function setSiteAddress($block_id)
    {
        $site = CAllIBlock::GetSite($block_id)->Fetch();

        if ($site['SERVER_NAME']) {
            $this->serverName = $site['SERVER_NAME'];
        } else {
            $this->serverName = $this->defaultServerName;
        }
    }

    protected function PrepareSettings()
    {
        foreach ($this->propertiesSKU as $iblock => $arr) {
            foreach ($arr as $id => $sku) {
                $this->propertiesSKU[$iblock][$id] = strtoupper($sku);
            }
        }

        foreach ($this->propertiesProduct as $iblock => $arr) {
            foreach ($arr as $id => $prod) {
                $this->propertiesProduct[$iblock][$id] = strtoupper($prod);
            }
        }
    }

    protected function PrepareValue($text)
    {
        $newText = $this->application->ConvertCharset($text, $this->encodingDefault, $this->encoding);
        $newText = strip_tags($newText);
        $newText = str_replace("&", "&#x26;", $newText);

        return $newText;
    }

    protected function PrepareFile($filename)
    {
        $fullFilename = $_SERVER["DOCUMENT_ROOT"] . $filename;
        CheckDirPath($fullFilename);

        if ($fp = @fopen($fullFilename, "w")){
            return $fp;
        } else {
            return false;
        }
    }

    protected function PreWriteCatalog()
    {
        @fwrite($this->fp, "<yml_catalog date=\"" . $this->PrepareValue(Date("Y-m-d H:i:s")) . "\">\n
            <shop>\n
            <name>" . $this->PrepareValue(COption::GetOptionString("main", "site_name", ""))."</name>\n
            <company>" . $this->PrepareValue(COption::GetOptionString("main", "site_name", ""))."</company>\n"
        );
    }

    protected function WriteCategories($categories)
    {
        $stringCategories = "";
        @fwrite($this->fp, "<categories>\n");
        foreach ($categories as $category) {
            $stringCategories .= $this->BuildCategory($category);
        }
        @fwrite($this->fp, $stringCategories);
        @fwrite($this->fp, "</categories>\n");
    }
    protected function PreWriteOffers()
    {
        @fwrite($this->fp, "<offers>\n");
    }

    protected function PostWriteOffers()
    {
        @fwrite($this->fp, "</offers>\n");
    }

    protected function WriteOffers($offers)
    {
        @fwrite($this->fp, $offers);
    }

    protected function WriteLog($text)
    {
        if ($this->isLogged) {
            @fwrite($this->fpLog, Date("Y:m:d H:i:s") . ": " . $text . "\n");
        }
    }

    protected function PostWriteCatalog()
    {
        @fwrite($this->fp, "</shop>\n
            </yml_catalog>\n");
    }

    protected function CloseFile($fp)
    {
        @fclose($fp);
    }

    protected function GetCategories()
    {
        $categories = array();
        foreach ($this->iblocks as $id) {
            $this->setSiteAddress($id);
            $filter = array("IBLOCK_ID" => $id);

            $dbRes = CIBlockSection::GetList(array("left_margin" => "asc"), $filter);
            $hasCategories = false;

            while ($arRes = $dbRes->Fetch()) {
                $categories[$arRes['ID']] = $arRes;
                $categories[$arRes['ID']]['SITE'] = $this->protocol . $this->serverName;
                $hasCategories = true;
            }

            if (!$hasCategories) {
                $iblock = CIBlock::GetByID($id)->Fetch();

                $arRes = array();
                $arRes['ID'] = $this->mainSection + $id;
                $arRes['IBLOCK_SECTION_ID'] = 0;
                $arRes['NAME'] = sprintf(GetMessage('ROOT_CATEGORY_FOR_CATALOG'), $iblock['NAME']);
                $categories[$arRes['ID']] = $arRes;
                $categories[$arRes['ID']]['SITE'] = $this->protocol . $this->serverName;
            }
        }

        return $categories;
    }

    protected function BuildCategory($arCategory)
    {
        $category =
            "<category id=\"" . $this->PrepareValue($arCategory["ID"]) . "\""
            . (intval($arCategory["IBLOCK_SECTION_ID"]) > 0 ?
                " parentId=\"" . $this->PrepareValue($arCategory["IBLOCK_SECTION_ID"]) . "\""
                :"")
            . ">\n\t"
            . "<name>" . $this->PrepareValue($arCategory["NAME"]) . "</name>\n";

        if (CFile::GetPath($arCategory["DETAIL_PICTURE"])) {
            $category .= "\t<picture>" . $arCategory['SITE'] . CFile::GetPath($arCategory["DETAIL_PICTURE"]) . "</picture>\n";
        }

        if (CFile::GetPath($arCategory["PICTURE"])) {
            $category .= "\t<picture>" . $arCategory['SITE'] . CFile::GetPath($arCategory["PICTURE"]) . "</picture>\n";
        }

        $category .= "</category>\n";

        return $category;
    }

    protected function BuildOffers(&$allCategories)
    {
        $basePriceId = COption::GetOptionString(
            $this->MODULE_ID,
            $this->CRM_CATALOG_BASE_PRICE . '_' . $this->profileID,
            0
        );

        if (!$basePriceId) {
            $dbPriceType = CCatalogGroup::GetList(
                array(),
                array('BASE' => 'Y'),
                false,
                false,
                array('ID')
            );

            $result = $dbPriceType->GetNext();
            $basePriceId = $result['ID'];
        }

        foreach ($this->iblocks as $key => $id) {
            $this->setSiteAddress($id);
            $barcodes = array();

            $dbBarCode = CCatalogStoreBarCode::getList(
                array(),
                array("IBLOCK_ID" => $id),
                false,
                false,
                array('PRODUCT_ID', 'BARCODE')
            );

            while ($arBarCode = $dbBarCode->GetNext()) {
                if (!empty($arBarCode)) {
                    $barcodes[$arBarCode['PRODUCT_ID']] = $arBarCode['BARCODE'];
                }
            }

            $highloadblockSkuProps = array();
            $highloadblockProductProps = array();
            $productProps = CIBlockproperty::GetList(array(), array("IBLOCK_ID" => $id));

            while ($arrProductProps = $productProps->Fetch()) {

                if ($arrProductProps["USER_TYPE"] == 'directory') {
                    $highloadblockProductProps[$arrProductProps['CODE']] = $arrProductProps;
                }
            }

            // Get Info by infoblocks
            $iblock['IBLOCK_DB'] = CIBlock::GetByID($id)->Fetch();
            $iblockOffer = CCatalogSKU::GetInfoByProductIBlock($id);
            $skuProps = CIBlockproperty::GetList(array(), array("IBLOCK_ID" => $iblockOffer['IBLOCK_ID']));

            while ($arrSkuProps = $skuProps->Fetch()) {
                if ($arrSkuProps["USER_TYPE"] == 'directory') {
                    $highloadblockSkuProps[$arrSkuProps['CODE']] = $arrSkuProps;
                }
            }

            $arSelect = array(
                "ID",
                "LID",
                "IBLOCK_ID",
                "IBLOCK_SECTION_ID",
                "ACTIVE",
                "NAME",
                "DETAIL_PICTURE",
                "PREVIEW_PICTURE",
                "DETAIL_PAGE_URL",
                "CATALOG_GROUP_" . $basePriceId
            );
            // Set selected properties
            foreach ($this->propertiesProduct[$id] as $key => $propProduct) {
                if ($this->propertiesProduct[$id][$key] != "") {
                    $arSelect[] = "PROPERTY_" . $propProduct;
                    $arSelect[] = "PROPERTY_" . $propProduct . ".NAME";
                }
            }

            if ($this->productPictures && isset($this->productPictures[$id])) {
                $arSelect[] = "PROPERTY_" . $this->productPictures[$id]['picture'];
                $arSelect[] = "PROPERTY_" . $this->productPictures[$id]['picture'] . ".NAME";
            }

            $arSelectOffer = array(
                'ID',
                "NAME",
                "DETAIL_PAGE_URL",
                "DETAIL_PICTURE",
                "PREVIEW_PICTURE",
                'PROPERTY_' . $iblockOffer['SKU_PROPERTY_ID'],
                "CATALOG_GROUP_" . $basePriceId
            );

            // Set selected properties
            foreach ($this->propertiesSKU[$id] as $key => $propSKU) {
                if ($this->propertiesSKU[$id][$key] != "") {
                    $arSelectOffer[] =  "PROPERTY_" . $propSKU;
                    $arSelectOffer[] =  "PROPERTY_" . $propSKU . ".NAME";
                }
            }

            if ($this->skuPictures && isset($this->skuPictures[$id])) {
                $arSelectOffer[] = "PROPERTY_" . $this->skuPictures[$id]['picture'];
                $arSelectOffer[] = "PROPERTY_" . $this->skuPictures[$id]['picture'] . ".NAME";
            }

            // Set filter
            $filter = array(
                "IBLOCK_ID" => $id
            );
            $order = array("id");
            $arNavStatParams = array(
                "iNumPage" => 1,
                "nPageSize" => $this->pageSize,
            );

            // Cycle page to page
            do {
                // Get products on this page
                $elems = array();
                $dbResProductsIds = CIBlockElement::GetList($order, $filter, false, $arNavStatParams, array('ID'));
                while ($obIds = $dbResProductsIds->Fetch()) {
                    $elems[] = $obIds['ID'];
                }
                $arfilter = array(
                    "IBLOCK_ID" => $id,
                    "ID" => $elems
                );

                $dbResProducts = CIBlockElement::GetList($order, $arfilter, false, false, $arSelect);

                $products = array();

                while ($product = $dbResProducts->GetNext()) {
                    // Compile products to array
                    $products[$product['ID']] = $product;
                    $products[$product['ID']]['offers'] = array();

                }

                unset($product);

                if (!empty($iblockOffer['IBLOCK_ID'])) {
                    $arFilterOffer = array(
                        'IBLOCK_ID' => $iblockOffer['IBLOCK_ID'],
                        'PROPERTY_' . $iblockOffer['SKU_PROPERTY_ID'] => array_keys($products),
                    );

                    // Get all offers for products on this page
                    $dbResOffers = CIBlockElement::GetList(
                        array(),
                        $arFilterOffer,
                        false,
                        array('nTopCount' => $this->pageSize * $this->offerPageSize),
                        $arSelectOffer
                    );

                    while ($offer = $dbResOffers->GetNext()) {
                        // Link offers to products
                        $products[$offer['PROPERTY_' . $iblockOffer['SKU_PROPERTY_ID'] . '_VALUE']]['offers'][$offer['ID']] = $offer;
                    }
                    unset($offer, $dbResOffers);
                }

                $stringOffers = "";
                foreach ($products as $product) {
                    if (CFile::GetPath($product["DETAIL_PICTURE"])) {
                        $product['PICTURE'] = $this->protocol . $this->serverName . CFile::GetPath($product["DETAIL_PICTURE"]);
                    } elseif (CFile::GetPath($product["PREVIEW_PICTURE"])){
                        $product['PICTURE'] = $this->protocol . $this->serverName . CFile::GetPath($product["PREVIEW_PICTURE"]);
                    } elseif (
                        $this->productPictures
                        && isset($this->productPictures[$id])
                        && CFile::GetPath($product["PROPERTY_" . $this->productPictures[$id]['picture'] . "_VALUE"])
                    ) {
                        $picture = CFile::GetPath($product["PROPERTY_" . $this->productPictures[$id]['picture'] . "_VALUE"]);
                        $product['PICTURE'] = $this->protocol . $this->serverName . $picture;
                    }

                    // Get properties of product
                    $resPropertiesProduct = Array();
                    foreach ($this->propertiesProduct[$id] as $key => $propProduct) {
                        $resPropertiesProduct[$key] = "";

                        if ($propProduct != "") {
                            if (isset($product["PROPERTY_" . $propProduct . "_NAME"])) {
                                $resPropertiesProduct[$key] =  $product["PROPERTY_" . $propProduct . "_NAME"];
                            } elseif (isset($product["PROPERTY_" . $propProduct . "_VALUE"])) {
                                $resPropertiesProduct[$key] =  $product["PROPERTY_" . $propProduct . "_VALUE"];
                            } elseif (isset($product[$propProduct])) {
                                $resPropertiesProduct[$key] =  $product[$propProduct];
                            }

                            if (array_key_exists($key, $this->propertiesUnitProduct[$id])) {
                                $resPropertiesProduct[$key] *= $this->measurement[$this->propertiesUnitProduct[$id][$key]];
                                $resPropertiesProduct[$key . "_UNIT"] = $this->measurementLink[$this->propertiesUnitProduct[$id][$key]];
                            }

                            if (isset($highloadblockProductProps[$propProduct])) {
                                $propVal = $this->getHBprop($highloadblockProductProps[$propProduct], $product["PROPERTY_" . $propProduct . "_VALUE"]);
                                $tableName = $highloadblockProductProps[$propProduct]['USER_TYPE_SETTINGS']['TABLE_NAME'];
                                $field = $this->highloadblockProductProperties[$tableName][$id][$key];

                                $resPropertiesProduct[$key] =  $propVal[$field];
                            }
                        }
                    }

                    // Get categories of product
                    $categories = array();
                    $dbResCategories = CIBlockElement::GetElementGroups($product['ID'], true);
                    while ($arResCategory = $dbResCategories->Fetch()) {
                        $categories[$arResCategory["ID"]] = array(
                            'ID' => $arResCategory["ID"],
                            'NAME' => $arResCategory["NAME"],
                        );
                    }
                    if (count($categories) == 0) {
                        $catId = $this->mainSection + $id;
                        $categories[$catId] = $allCategories[$catId];
                    }

                    $existOffer = false;
                    if (!empty($iblockOffer['IBLOCK_ID'])) {
                        foreach ($product['offers'] as $offer) {
                            $offer['BARCODE'] = isset($barcodes[$offer['ID']]) ? $barcodes[$offer['ID']] : '';
                            $offer['PRODUCT_ID'] = $product["ID"];
                            $offer['DETAIL_PAGE_URL'] = $product["DETAIL_PAGE_URL"];

                            if (CFile::GetPath($offer["DETAIL_PICTURE"])) {
                                $offer['PICTURE'] = $this->protocol . $this->serverName . CFile::GetPath($offer["DETAIL_PICTURE"]);
                            } elseif (CFile::GetPath($offer["PREVIEW_PICTURE"])){
                                $offer['PICTURE'] = $this->protocol . $this->serverName . CFile::GetPath($offer["PREVIEW_PICTURE"]);
                            } elseif (
                                $this->skuPictures
                                && isset($this->skuPictures[$id])
                                && CFile::GetPath($offer["PROPERTY_" . $this->skuPictures[$id]['picture'] . "_VALUE"])
                            ) {
                                $picture = CFile::GetPath($offer["PROPERTY_" . $this->skuPictures[$id]['picture'] . "_VALUE"]);
                                $offer['PICTURE'] = $this->protocol . $this->serverName . $picture;
                            } else {
                                $offer['PICTURE'] = $product['PICTURE'];
                            }

                            $offer['PRODUCT_NAME'] = $product["NAME"];
                            $offer['PRODUCT_ACTIVE'] = $product["ACTIVE"];
                            $offer['PRICE'] = $offer['CATALOG_PRICE_' . $basePriceId];
                            $offer['PURCHASE_PRICE'] = $offer['CATALOG_PURCHASING_PRICE'];
                            $offer['QUANTITY'] = $offer["CATALOG_QUANTITY"];

                            // Get properties of product
                            foreach ($this->propertiesSKU[$id] as $key => $propSKU) {
                                if ($propSKU != "") {
                                    if (isset ($offer["PROPERTY_" . $propSKU . "_NAME"])) {
                                        $offer['_PROP_' . $key] =  $offer["PROPERTY_" . $propSKU . "_NAME"];
                                    } elseif (isset($offer["PROPERTY_" . $propSKU . "_VALUE"])) {
                                        $offer['_PROP_' . $key] =  $offer["PROPERTY_" . $propSKU . "_VALUE"];
                                    } elseif (isset($offer[$propSKU])) {
                                        $offer['_PROP_' . $key] = $offer[$propSKU];
                                    }
                                    if (array_key_exists($key, $this->propertiesUnitSKU[$id])) {
                                        $offer['_PROP_' . $key] *= $this->measurement[$this->propertiesUnitSKU[$id][$key]];
                                        $offer['_PROP_' . $key . "_UNIT"] = $this->measurementLink[$this->propertiesUnitSKU[$id][$key]];
                                    }
                                    if (isset($highloadblockSkuProps[$propSKU])) {
                                        $propVal = $this->getHBprop($highloadblockSkuProps[$propSKU], $offer["PROPERTY_" . $propSKU . "_VALUE"]);
                                        $tableName = $highloadblockSkuProps[$propSKU]['USER_TYPE_SETTINGS']['TABLE_NAME'];
                                        $field = $this->highloadblockSkuProperties[$tableName][$id][$key];
                                        $offer['_PROP_' . $key] = $propVal[$field];
                                    }
                                }
                            }

                            foreach ($resPropertiesProduct as $key => $propProduct) {
                                if ($this->propertiesProduct[$id][$key] != "" && !isset($offer[$key])) {
                                    $offer['_PROP_' . $key] =  $propProduct;
                                }
                            }

                            $stringOffers .= $this->BuildOffer($offer, $categories, $iblock, $allCategories);
                            $existOffer = true;
                        }
                    }
                    if (!$existOffer) {
                        $offer['BARCODE'] = isset($barcodes[$product["ID"]]) ? $barcodes[$product["ID"]] : '';
                        $product['PRODUCT_ID'] = $product["ID"];
                        $product['PRODUCT_NAME'] = $product["NAME"];
                        $product['PRODUCT_ACTIVE'] = $product["ACTIVE"];
                        $product['PRICE'] = $product['CATALOG_PRICE_' . $basePriceId];
                        $product['PURCHASE_PRICE'] = $product['CATALOG_PURCHASING_PRICE'];
                        $product['QUANTITY'] = $product["CATALOG_QUANTITY"];

                        foreach ($resPropertiesProduct as $key => $propProduct) {
                            if ($this->propertiesProduct[$id][$key] != "" || $this->propertiesProduct[$id][str_replace("_UNIT", "", $key)] != "") {
                                $product['_PROP_' . $key] =  $propProduct;
                            }
                        }

                        $stringOffers .= $this->BuildOffer($product, $categories, $iblock, $allCategories);
                    }
                }
                unset($products);

                if ($this->isLogged) {
                    $this->WriteLog(($this->pageSize * $arNavStatParams['iNumPage']) . " product(s) has been loaded from " . $id . " IB (memory usage: " . memory_get_usage() . ")");
                }
                if ($stringOffers != "") {
                    $this->WriteOffers($stringOffers);
                    $stringOffers = "";
                }

                $arNavStatParams['iNumPage'] = $dbResProductsIds->NavPageNomer + 1;
            } while ($dbResProductsIds->NavPageNomer < $dbResProductsIds->NavPageCount);
        }
    }

    protected function BuildOffer($arOffer, $categories, $iblock, &$allCategories)
    {
        $offer = "";
        $offer .= "<offer id=\"" .$this->PrepareValue($arOffer["ID"]) . "\" ".
                "productId=\"" . $this->PrepareValue($arOffer["PRODUCT_ID"]) . "\" ".
                "quantity=\"" . $this->PrepareValue(DoubleVal($arOffer['QUANTITY'])) . "\">\n";

        if ($arOffer['PRODUCT_ACTIVE'] == "N") {
            $offer .= "<productActivity>" .  $this->PrepareValue($arOffer['PRODUCT_ACTIVE']) . "</productActivity>\n";
        }

        $keys = array_keys($categories);
        if (strpos($arOffer['DETAIL_PAGE_URL'], "#SECTION_PATH#") !== false) {
            if (count($categories) != 0) {
                $category = $allCategories[$keys[0]];
                $path = $category['CODE'];

                if (intval($category["IBLOCK_SECTION_ID"] ) != 0) {
                    while (true) {
                        $category = $allCategories[$category['IBLOCK_SECTION_ID']];
                        $path = $category['CODE'] . '/' . $path;
                        if(intval($category["IBLOCK_SECTION_ID"]) == 0){
                            break;
                        }
                    }
                }

            }
            $arOffer['DETAIL_PAGE_URL'] = str_replace("#SECTION_PATH#", $path, $arOffer['DETAIL_PAGE_URL']);
        }

        if (isset($arOffer["PICTURE"]) && $arOffer["PICTURE"]) {
            $offer .= "<picture>" . $this->PrepareValue($arOffer["PICTURE"]) . "</picture>\n";
        }

        $offer .= "<url>" . $this->protocol . $this->serverName . $this->PrepareValue($arOffer['DETAIL_PAGE_URL']) . "</url>\n";

        $offer .= "<price>" . $this->PrepareValue($arOffer['PRICE']) . "</price>\n";
        if ($arOffer['PURCHASE_PRICE'] && $this->loadPurchasePrice) {
            $offer .= "<purchasePrice>" . $this->PrepareValue($arOffer['PURCHASE_PRICE']) . "</purchasePrice>\n";
        }
        foreach ($categories as $category) {
            $offer .= "<categoryId>" . $category['ID'] . "</categoryId>\n";
        }

        $offer .= "<name>" . $this->PrepareValue($arOffer["NAME"]) . "</name>\n";

        $offer .= "<xmlId>" . $this->PrepareValue($arOffer["EXTERNAL_ID"]) . "</xmlId>\n";
        $offer .= "<productName>" . $this->PrepareValue($arOffer["PRODUCT_NAME"]) . "</productName>\n";

        foreach ($this->propertiesProduct[$iblock['IBLOCK_DB']['ID']] as $key => $propProduct) {
            if ($propProduct != "" && $arOffer['_PROP_' . $key] != null) {
                if ($key === "manufacturer") {
                    $offer .= "<vendor>" . $this->PrepareValue($arOffer['_PROP_' . $key]) . "</vendor>\n";
                } else {
                    $offer .= '<param name="' . $key . '"' . (isset($arOffer['_PROP_' . $key . "_UNIT"]) ? ' unit="' . $arOffer['_PROP_' . $key . "_UNIT"] . '"' : "") . ">" . $this->PrepareValue($arOffer['_PROP_' . $key]) . "</param>\n";
                }
            }
        }
        foreach ($this->propertiesSKU[$iblock['IBLOCK_DB']['ID']] as $key => $propProduct) {
            if ($propProduct != "" && $arOffer['_PROP_' . $key] != null) {
                if ($key === "manufacturer") {
                    $offer .= "<vendor>" . $this->PrepareValue($arOffer['_PROP_' . $key]) . "</vendor>\n";
                } else {
                    $offer .= '<param name="' . $key . '"' . (isset($arOffer['_PROP_' . $key . "_UNIT"]) ? ' unit="' . $arOffer['_PROP_' . $key . "_UNIT"] . '"' : "") . ">" . $this->PrepareValue($arOffer['_PROP_' . $key]) . "</param>\n";
                }
            }
        }
        if (isset($arOffer["MEASURE"]['SYMBOL_INTL'])) {
            if ($this->measure[$arOffer["MEASURE"]['SYMBOL_INTL']]) {
                $offer .= '<unit code="' . $this->measure[$this->PrepareValue($arOffer["MEASURE"]['SYMBOL_INTL'])] . '" />' . "\n";
            } else {
                $offer .= '<unit code="' . $this->PrepareValue($arOffer["MEASURE"]['SYMBOL_INTL']) . '" name="' . $this->PrepareValue($arOffer["MEASURE"]['MEASURE_TITLE']) . '" sym="' . $this->PrepareValue($arOffer["MEASURE"]['SYMBOL_RUS']) . '" />' . "\n";
            }

        }
        if ($arOffer["BARCODE"]) {
            $offer.= "<barcode>" . $this->PrepareValue($arOffer["BARCODE"]) . "</barcode>\n";
        }

        if ($arOffer["CATALOG_VAT"]) {
            $vatRate = $arOffer["CATALOG_VAT"];
        } else {
            $vatRate = 'none';
        }

        $offer.= "<vatRate>" . $this->PrepareValue($vatRate) . "</vatRate>\n";
        $offer.= "</offer>\n";

        return $offer;
    }

    private function getHBprop($hbProp, $xml_id)
    {
        if (CModule::IncludeModule('highloadblock')) {
            $hlblockArr = \Bitrix\Highloadblock\HighloadBlockTable::getList(array(
                'filter' => array('=TABLE_NAME' => $hbProp['USER_TYPE_SETTINGS']['TABLE_NAME'])
            ))->fetch();

            $hlblock = HL\HighloadBlockTable::getById($hlblockArr["ID"])->fetch();
            $entity = HL\HighloadBlockTable::compileEntity($hlblock);
            $entityClass = $entity->getDataClass();

            $result = $entityClass::getList(array(
                'select' => array('*'),
                'filter' => array('UF_XML_ID' => $xml_id)
            ));

            return $result->fetch();
        }

        return array();
    }
}
