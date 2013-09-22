<?php

global $MESS;
IncludeModuleLangFile(__FILE__);

class ICMLLoader {

    public $iblocks;
    public $filename;
        public $articleProperties;
    public $application;
    public $encoding = 'utf-8';

    protected $fp;
        protected $mainSection = 1000000;

    public function Load()
    {
            global $USER;
            if(!isset($USER))
                $USER = new CUser;

            if (count($this->iblocks) < count($this->articleProperties))
                return false;

            $this->PrepareFile();

            $this->PreWriteCatalog();

            $categories = $this->GetCategories();

            $this->WriteCategories($categories);

            $this->PreWriteOffers();
            $this->BuildOffers($categories);
            $this->PostWriteOffers();

            $this->PostWriteCatalog();

            $this->CloseFile();
            return true;

    }

    protected function PrepareValue($text)
        {
            $newText = $this->application->ConvertCharset($text, LANG_CHARSET, $this->encoding);
            $newText = strip_tags($newText);
            $newText = str_replace("&", "&#x26;", $newText);
            return $newText;
        }

    protected function PrepareFile()
    {
            $fullFilename = $_SERVER["DOCUMENT_ROOT"] . $this->filename;
            CheckDirPath($fullFilename);

            if (!$this->fp = @fopen($fullFilename, "w"))
                    return false;
            else
                return true;
    }

    protected function PreWriteCatalog()
    {
            @fwrite($this->fp, "<yml_catalog date=\"" . $this->PrepareValue(Date("Y-m-d H:i:s")) . "\">\n");
            @fwrite($this->fp, "<shop>\n");

            @fwrite($this->fp, "<name>". $this->PrepareValue(COption::GetOptionString("main", "site_name", ""))."</name>\n");

            @fwrite($this->fp, "<company>".$this->PrepareValue(COption::GetOptionString("main", "site_name", ""))."</company>\n");

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

    protected function PostWriteCatalog()
    {
            @fwrite($this->fp, "</shop>\n");
            @fwrite($this->fp, "</yml_catalog>\n");
    }

    protected function CloseFile()
    {
            @fclose($this->fp);
    }


    protected function GetCategories()
    {
            $categories = array();
            foreach ($this->iblocks as $id)
            {
                $filter = Array(
                                "IBLOCK_ID" => $id,
                                "ACTIVE" => "Y",
                                "IBLOCK_ACTIVE" => "Y",
                                "GLOBAL_ACTIVE" => "Y"
                                );

                $dbRes = CIBlockSection::GetList(array("left_margin" => "asc"), $filter);
                $hasCategories = false;
                while ($arRes = $dbRes->Fetch())
                {
                        $categories[$arRes['ID']] = $arRes;
                        $hasCategories = true;
                }
                if (!$hasCategories)
                {
                    $iblock = CIBlock::GetByID($id)->Fetch();

                    $arRes = Array();
                    $arRes['ID'] = $this->mainSection + $id;
                    $arRes['IBLOCK_SECTION_ID'] = 0;
                    $arRes['NAME'] = sprintf(GetMessage('ROOT_CATEGORY_FOR_CATALOG'), $iblock['NAME']);
                    $categories[$arRes['ID']] = $arRes;
                }
            }
            return $categories;

    }

    protected function BuildCategory($arCategory)
    {
            return "
                    <category id=\"" . $this->PrepareValue($arCategory["ID"]) . "\""
                    . ( intval($arCategory["IBLOCK_SECTION_ID"] ) > 0 ?
                            " parentId=\"" . $this->PrepareValue($arCategory["IBLOCK_SECTION_ID"]) . "\""
                            :"")
                    . ">"
                    . $this->PrepareValue($arCategory["NAME"])
                    . "</category>\n";

    }

    protected function BuildOffers(&$allCategories)
    {
            foreach ($this->iblocks as $key => $id)
            {

                    $iblock['IBLOCK_DB'] = CIBlock::GetByID($id)->Fetch();
                    $iblockOffer = CCatalogSKU::GetInfoByProductIBlock($id);


                    $arSelect = Array (
                                    "ID",
                                    "LID",
                                    "IBLOCK_ID",
                                    "IBLOCK_SECTION_ID",
                                    "ACTIVE",
                                    "ACTIVE_FROM",
                                    "ACTIVE_TO",
                                    "NAME",
                                    "DETAIL_PICTURE",
                                    "DETAIL_TEXT",
                                    "DETAIL_PICTURE",
                                    "LANG_DIR",
                                    "DETAIL_PAGE_URL"
                            );

                    if (isset($this->articleProperties[$id]))
                        $arSelect[] =  "PROPERTY_" . $this->articleProperties[$id];


                    $filter = Array (
                                    "IBLOCK_ID" => $id,
                                    "ACTIVE_DATE" => "Y",
                                    "ACTIVE" => "Y",
                                    "INCLUDE_SUBSECTIONS" => "Y"
                            );
                    $count = 0;
                    $dbResProducts = CIBlockElement::GetList(array(), $filter, false, false, $arSelect);
                    $stringOffers = "";
                    while ($product = $dbResProducts->GetNextElement()) {

                            $product = $product->GetFields();

                            // Get categories in InfoBlock
                            $categories = Array();
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


                                $arFilterOffer = Array (
                                                'IBLOCK_ID' => $iblockOffer['IBLOCK_ID'],
                                                'PROPERTY_' . $iblockOffer['SKU_PROPERTY_ID'] => $product["ID"]
                                        );
                                $arSelectOffer = Array (
                                                'ID',
                                                "NAME",
                                                "DETAIL_TEXT",
                                                "DETAIL_PAGE_URL",
                                                "DETAIL_PICTURE"
                                        );
                                if (isset($this->articleProperties[$id]))
                                    $arSelectOffer[] =  "PROPERTY_" . $this->articleProperties[$id];


                                $rsOffers = CIBlockElement::GetList(array(), $arFilterOffer, false, false, $arSelectOffer);
                                while ($arOffer = $rsOffers->GetNext()) {

                                    $offer = CCatalogProduct::GetByID($arOffer['ID']);
                                    $arOffer['QUANTITY'] = $offer["QUANTITY"];

                                    $arOffer['PRODUCT_ID'] = $product["ID"];
                                    $arOffer['DETAIL_PAGE_URL'] = $product["DETAIL_PAGE_URL"];
                                    $arOffer['DETAIL_PICTURE'] = $product["DETAIL_PICTURE"];
                                    $arOffer['PREVIEW_PICTURE'] = $product["PREVIEW_PICTURE"];
                                    $arOffer['PRODUCT_NAME'] = $product["NAME"];
                                    if (isset($this->articleProperties[$id]))
                                        $arOffer['ARTICLE'] = $product["PROPERTY_" . $this->articleProperties[$id] . "_VALUE"];

                                    $dbPrice = GetCatalogProductPrice($arOffer["ID"],1);
                                    $arOffer['PRICE'] = $dbPrice['PRICE'];

                                    $stringOffers .= $this->BuildOffer($arOffer, $categories, $iblock);
                                    $existOffer = true;
                                }
                            }
                            if (!$existOffer) {

                                $offer = CCatalogProduct::GetByID($product['ID']);
                                $product['QUANTITY'] = $offer["QUANTITY"];

                                $product['PRODUCT_ID'] = $product["ID"];
                                $product['PRODUCT_NAME'] = $product["NAME"];
                                if (isset($this->articleProperties[$id]))
                                    $product['ARTICLE'] = $product["PROPERTY_" . $this->articleProperties[$id] . "_VALUE"];

                                $dbPrice = GetCatalogProductPrice($product["ID"],1);
                                $product['PRICE'] = $dbPrice['PRICE'];

                                $stringOffers .= $this->BuildOffer($product, $categories, $iblock);
                            }

                            $count++;
                            if ($count == 1000) {
                                $this->WriteOffers($stringOffers);
                                $stringOffers = "";
                            }

                    }

                    if ($stringOffers != "") {
                        $this->WriteOffers($stringOffers);
                        $stringOffers = "";
                    }

            }
    }


    protected function BuildOffer($arOffer, $categories, $iblock)
    {
            $offer = "";
            $offer .= "<offer id=\"" .$this->PrepareValue($arOffer["ID"]) . "\" ".
                    "productId=\"" . $this->PrepareValue($arOffer["PRODUCT_ID"]) . "\" ".
                    "quantity=\"" . $this->PrepareValue(DoubleVal($arOffer['QUANTITY'])) . "\">\n";

            $offer .= "<url>http://" . $this->PrepareValue($iblock['IBLOCK_DB']['SERVER_NAME']) . $this->PrepareValue($arOffer['DETAIL_PAGE_URL']) . "</url>\n";

            $offer .= "<price>" . $this->PrepareValue($arOffer['PRICE']) . "</price>\n";
            foreach ($categories as $category)
                $offer .= "<categoryId>" . $category['ID'] . "</categoryId>\n";

            $detailPicture = intval($arOffer["DETAIL_PICTURE"]);
            $previewPicture = intval($arOffer["PREVIEW_PICTURE"]);

            if ($detailPicture > 0 || $previewPicture > 0)
            {
                    $picture = $detailPicture;
                    if ($picture <= 0) {
                            $picture = $previewPicture;
                    }

                    if ($arFile = CFile::GetFileArray($picture))
                    {
                            if(substr($arFile["SRC"], 0, 1) == "/")
                                    $strFile = "http://" . $this->PrepareValue($iblock['IBLOCK_DB']['SERVER_NAME']) . implode("/", array_map("rawurlencode", explode("/", $arFile["SRC"])));
                            elseif(preg_match("/^(http|https):\\/\\/(.*?)\\/(.*)\$/", $arFile["SRC"], $match))
                                    $strFile = "http://" . $this->PrepareValue($match[2]) . '/' . implode("/", array_map("rawurlencode", explode("/", $this->PrepareValue($match[3]))));
                            else
                                    $strFile = $arFile["SRC"];
                            $offer .= "<picture>" . $this->PrepareValue($strFile) . "</picture>\n";
                    }
            }

            $offer .= "<name>" . $this->PrepareValue($arOffer["NAME"]) . "</name>\n";

            $offer .= "<xmlId>" . $this->PrepareValue($arOffer["EXTERNAL_ID"]) . "</xmlId>\n";
            $offer .= "<productName>" . $this->PrepareValue($arOffer["PRODUCT_NAME"]) . "</productName>\n";
            if (isset($arOffer["ARTICLE"]))
                $offer .= "<article>" . $this->PrepareValue($arOffer["ARTICLE"]) . "</article>\n";

            $offer.= "</offer>\n";
            return $offer;
    }

}