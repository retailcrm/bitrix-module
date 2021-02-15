<?php

namespace Intaro\RetailCrm\Icml;

use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\SectionTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use CCatalogGroup;
use CCatalogSku;
use CCatalogStoreBarCode;
use CFile;
use CIBlockElement;
use COption;
use IcmlWriter;
use Intaro\RetailCrm\Icml\Utils\IcmlLogger;
use Intaro\RetailCrm\Model\Bitrix\Xml\OfferParam;
use Intaro\RetailCrm\Model\Bitrix\Xml\SelectParams;
use Intaro\RetailCrm\Model\Bitrix\Xml\XmlCategory;
use Intaro\RetailCrm\Model\Bitrix\Xml\XmlData;
use Intaro\RetailCrm\Model\Bitrix\Xml\XmlOffer;
use Intaro\RetailCrm\Model\Bitrix\Xml\XmlSetup;
use Intaro\RetailCrm\Service\Hl;
use RetailcrmConstants;

/**
 * Class IcmlDataManager
 * @package Intaro\RetailCrm\Icml
 */
class IcmlDataManager
{
    private const INFO          = 'INFO';
    private const OFFERS_PART   = 500;
    private const MILLION       = 1000000;
    
    /**
     * @var \Intaro\RetailCrm\Model\Bitrix\Xml\XmlSetup
     */
    private $setup;
    
    /**
     * @var false|string|null
     */
    private $shopName;
    
    /**
     * @var \IcmlWriter
     */
    private $icmlWriter;
    
    /**
     * @var string
     */
    private $basePriceId;
    
    /**
     * @var false|string|null
     */
    private $purchasePriceNull;
    
    /**
     * IcmlDataManager constructor.
     * @param \Intaro\RetailCrm\Model\Bitrix\Xml\XmlSetup $setup
     * @param \IcmlWriter                                 $icmlWriter
     */
    public function __construct(XmlSetup $setup, IcmlWriter $icmlWriter)
    {
        $this->basePriceId = $this->getBasePriceId();
        $this->icmlWriter = &$icmlWriter;
        $this->setup       = $setup;
        $this->shopName    = COption::GetOptionString("main", "site_name");
        $this->purchasePriceNull = COption::GetOptionString(RetailcrmConstants::MODULE_ID,
            RetailcrmConstants::CRM_PURCHASE_PRICE_NULL
        );
    }
   
    /**
     * @return \Intaro\RetailCrm\Model\Bitrix\Xml\XmlData
     */
    public function getXmlData(): XmlData
    {
        $xmlData             = new XmlData();
        $xmlData->shopName   = $xmlData->company = $this->shopName;
        $xmlData->filePath   = $this->setup->filePath;
        $xmlData->categories = $this->getCategories();
        
        return $xmlData;
    }
    
    /**
     * @return XmlCategory[]| null
     */
    private function getCategories(): ?array
    {
        $xmlCategories = [];
        
        foreach ($this->setup->iblocksForExport as $iblockKey => $iblockId){
            try {
                $categories = SectionTable::query()
                    ->addSelect('*')
                    ->where('IBLOCK_ID', $iblockId)
                    ->fetchCollection();
                
                if ($categories === null) {
                    
                    $iblock = IblockTable::query()
                        ->addSelect('NAME')
                        ->where('ID', $iblockId)
                        ->fetchObject();
                    
                    if ($iblock === null) {
                        return null;
                    }
                    
                    $xmlCategory           = new XmlCategory();
                    $xmlCategory->id       = self::MILLION + $iblock->get('ID');
                    $xmlCategory->name     = $iblock->get('NAME');
                    $xmlCategory->parentId = 0;
                    
                    if ($iblock->get('PICTURE') !== null) {
                        $xmlCategory->picture  = $this->setup->defaultServerName
                            . CFile::GetPath($iblock->get('PICTURE'));
                    }
                    
                    $xmlCategories[self::MILLION + $iblock->get('ID')]   = $xmlCategory;
                }
            } catch (ObjectPropertyException | ArgumentException | SystemException $exception) {
                return null;
            }
            
            foreach ($categories as $categoryKey => $category) {
                $xmlCategory           = new XmlCategory();
                $xmlCategory->id       = $category->get('ID');
                $xmlCategory->name     = $category->get('NAME');
                $xmlCategory->parentId = $category->get('IBLOCK_SECTION_ID');
                
                if ($category->get('PICTURE') !== null) {
                    $xmlCategory->picture = $this->setup->defaultServerName. CFile::GetPath($category->get('PICTURE'));
                }
                
                $xmlCategories[$categoryKey]   = $xmlCategory;
            }
        }
        
        return $xmlCategories;
    }
    
    /**
     * @param array $productProps
     * @param array $configurableParams
     * @param int   $iblockId
     * @return OfferParam[]
     */
    private function getOfferParamsAndVendor(array $productProps, array $configurableParams, int $iblockId): array
    {
        $offerParams = [];
        $vendor=[];
        
        $hlSku = $this->setup->properties->highloadblockSku[$iblockId];
        
        
        if (isset($hlSku) && ! empty($hlSku)) {
            foreach ($this->setup->properties->highloadblockSku[$iblockId] as $hlName => $hlBlockSku){
                $hl = Hl::getHlClassByName($hlName);
                $hl::query()
                    ->where()
            }
        }
        
        
    
        foreach ($this->setup->properties->highloadblockProduct[$iblockId] as $key => $hlBlockProduct){
        
        }
        
        foreach ($configurableParams as $key => $paramCode) {
            
            if ($key === 'manufacturer' && isset($productProps[$paramCode])) {
                $vendor = $productProps[$paramCode];
                continue;
            }
            
            
            $offerParam        = new OfferParam();
            $offerParam->name  = GetMessage('PARAM_NAME_'.$key);
            $offerParam->code  = 12;
            $offerParam->value = "188-16-xx";
            $offerParams[$key] = $offerParam;
        }
        
        return [$offerParams, $vendor];
    }
    
    /**
     * запрашивает свойства офферов из БД и записывает их в xml
     */
    public function writeOffersHandler(): void
    {
        foreach ($this->setup->iblocksForExport as $iblockId) {
    
            $iblockOfferInfo = CCatalogSKU::GetInfoByProductIBlock($iblockId);
    
            //false - значит нет торговых предложений - работаем только с товарами
            if ($iblockOfferInfo === false) {
                $arSelectForProduct = $this->getSelectParams(
                    $this->setup->properties->products->names[$iblockId],
                    true
                );
        
                $this->writeProductsAsOffersInXml($iblockId, $arSelectForProduct);
            } else {
                $arSelectForProduct = $this->getSelectParams($this->setup->properties->products->names[$iblockId]);
                $arSelectForOffer   = $this->getSelectParams($this->setup->properties->sku->names[$iblockId]
                    , true
                );
        
                $this->writeOffersAsOffersInXml($iblockId, $arSelectForProduct, $arSelectForOffer);
            }
        }
    }
    
    /**
     * @param array $userProps
     * @param bool  $fullStack
     * @return \Intaro\RetailCrm\Model\Bitrix\Xml\SelectParams
     */
    private function getSelectParams(array $userProps, bool $fullStack = false): SelectParams
    {
        $catalogFields = ['catalog_length', 'catalog_length', 'catalog_width', 'catalog_height', 'catalog_weight'];
        
        $params = new SelectParams();
        
        foreach ($userProps as $key => $name) {
            if (in_array($name, $catalogFields, true)) {
                $userProps[$key] = strtoupper($userProps[$key]);
            } else {
                $userProps[$key] = 'PROPERTY_' . $userProps[$key];
            }
        }
    
        $params->configurable = $userProps;
    
    
        if ($fullStack) {
            $params->main = [
                'IBLOCK_ID',
                'IBLOCK_SECTION_ID',
                'NAME',
                'DETAIL_PICTURE',
                'PREVIEW_PICTURE',
                'DETAIL_PAGE_URL',
                'CATALOG_QUANTITY',
                'EXTERNAL_ID',
                "CATALOG_GROUP_" . $this->basePriceId,
            ];
        } else {
            $params->main = [];
        }
    
        $params->default = [
            "ID",
            "LID"
        ];
        
        return $params;
    }
    
    /**
     * Эта стратегия записи используется,
     * когда в каталоге нет торговых предложений
     * только товары
     *
     * @param                                                 $iblockId
     * @param \Intaro\RetailCrm\Model\Bitrix\Xml\SelectParams $arSelect
     */
    private function writeProductsAsOffersInXml($iblockId, SelectParams $arSelect): void
    {
        $pageNumber = 1;
        do {
            [$xmlOffers, $pageNumber] = $this->getXmlOffersFromProduct($arSelect, $iblockId, $pageNumber);
            $pageNumber++;
            $this->icmlWriter->writeOffers($xmlOffers);
            
            IcmlLogger::writeToToLog(count($xmlOffers)
                . " product(s) has been loaded from " . $iblockId
                . " IB (memory usage: " . memory_get_usage() . ")",
                self::INFO
            );
        } while (!empty($xmlOffers));
    }
    
    /**
     * Эта стратегия записи используется,
     * когда в каталоге есть торговые предложения
     *
     * @param       $iblockId
     * @param       $arSelectForProduct
     * @param       $arSelectForOffer
     */
    private function writeOffersAsOffersInXml($iblockId, $arSelectForProduct, $arSelectForOffer): void
    {
        do {
            $productsPropsPage = $this->getProductsPropsPage($arSelectForProduct, $iblockId);
            $xmlOffers = $this->getXmlOffersFromOffers($productsPropsPage);
            $this->icmlWriter->writeOffers($xmlOffers);
        } while ($productsPropsPage);
    }
    
    /**
     * @param \Intaro\RetailCrm\Model\Bitrix\Xml\SelectParams $arSelect
     * @param                                                 $iblockId
     * @param                                                 $pageNumber
     * @return array
     */
    private function getXmlOffersFromProduct(SelectParams $arSelect, $iblockId, $pageNumber): array
    {
        $ciBlockResult = CIBlockElement::GetList([],
            ['IBLOCK_ID' => $iblockId , "ACTIVE" => 'Y'],
            false,
            ['nPageSize' => self::OFFERS_PART, 'iNumPage'=>$pageNumber, 'checkOutOfRange' => true],
            array_merge($arSelect->default, $arSelect->configurable, $arSelect->main)
        );
        
        $products = [];
        
        $barcodes = $this->getProductBarcodesByIblock($iblockId);//получение штрих-кодов товаров
    
        while ($product = $ciBlockResult->GetNext()) {
            $product['BARCODE'] = $barcodes[$product['ID']];
            [$product['PARAMS'], $product['VENDOR']]
                = $this->getOfferParamsAndVendor($product, $arSelect->configurable, $iblockId);
            $products[]         = $this->buildXmlOffer($product);
        }
        
        return [$products, $pageNumber];
    }
    
    /**
     * @param array  $product
     * @param string $pictureProp
     * @return string
     */
    private function getProductPicture(array $product, string $pictureProp = ''): string
    {
        $picture = '';
        $propPicture = $product["PROPERTY_" . $pictureProp . "_VALUE"] ?? null;
    
        if (isset($product["DETAIL_PICTURE"])) {
            $picture = $this->getImageUrl($product["DETAIL_PICTURE"]);
        } elseif (isset($product["PREVIEW_PICTURE"])) {
            $picture = $this->getImageUrl($product["PREVIEW_PICTURE"]);
        } elseif ($propPicture !== null) {
            $picture = $this->getImageUrl($propPicture);
        }
        
        return $picture ?? '';
    }
    
    /**
     * @param $fileId
     * @return string
     */
    private function getImageUrl($fileId): string
    {
        $pathImage  = CFile::GetPath($fileId);
        $validation = "/^(http|https):\/\/([A-Z0-9][A-Z0-9_-]*(?:\.[A-Z0-9][A-Z0-9_-]*)+):?(\d+)?\/?/i";
        
        if ((bool)preg_match($validation, $pathImage) === false) {
            return $this->setup->defaultServerName . $pathImage;
        }
        
        return $pathImage;
    }
    
    /**
     * @param $offerId
     * @return array
     */
    private function getProductCategoriesIds(int $offerId): array
    {
        $query = CIBlockElement::GetElementGroups($offerId, false, ['ID']);
        $ids = [];
    
        while ($category = $query->GetNext()){
            $ids[] = $category['ID'];
        }
        
        return $ids;
    }
    
    /**
     * @param array $product
     * @return \Intaro\RetailCrm\Model\Bitrix\Xml\XmlOffer
     */
    private function buildXmlOffer(array $product): XmlOffer
    {
        $xmlOffer                = new XmlOffer();
        $xmlOffer->id            = $product['ID'];
        $xmlOffer->productId     = $product['ID'];
        $xmlOffer->quantity      = $product['CATALOG_QUANTITY'] ?? '';
        $xmlOffer->picture       = $this->getProductPicture($product, $arSelect['picture'] ?? '');
        $xmlOffer->url           = $product['DETAIL_PAGE_URL']
            ? $this->setup->defaultServerName . $product['DETAIL_PAGE_URL']
            : '';
        $xmlOffer->price         = $product['CATALOG_GROUP_' . $this->basePriceId];
        $xmlOffer->purchasePrice = $this->getPurchasePrice($product);
        $xmlOffer->categoryId    = $this->getProductCategoriesIds($product['ID']);
        $xmlOffer->name          = $product['NAME'];
        $xmlOffer->xmlId         = $product['EXTERNAL_ID'] ?? '';
        $xmlOffer->productName   = $product['NAME'];
        $xmlOffer->params        = $product['PARAMS'];
        $xmlOffer->vendor        = $product['VENDOR'];
        $xmlOffer->barcode       = $product['BARCODE'];
        $xmlOffer->unitCode      = "12.00";
        $xmlOffer->vatRate       = $product['CATALOG_VAT'] ?? 'none'; //Получение НДС
        
        return $xmlOffer;
    }
    
    /**
     * Returns products IDs with barcodes by infoblock id
     *
     * @param int $iblockId
     *
     * @return array
     */
    private function getProductBarcodesByIblock($iblockId): array
    {
        $barcodes  = [];
        $dbBarCode = CCatalogStoreBarCode::getList(
            [],
            ["IBLOCK_ID" => $iblockId],
            false,
            false,
            ['PRODUCT_ID', 'BARCODE']
        );
        
        while ($arBarCode = $dbBarCode->GetNext()) {
            if (!empty($arBarCode)) {
                $barcodes[$arBarCode['PRODUCT_ID']] = $arBarCode['BARCODE'];
            }
        }
        
        return $barcodes;
    }
    
    /**
     * Returns base price id
     *
     * @return string
     */
    private function getBasePriceId(): string
    {
        $basePriceId = COption::GetOptionString(
            RetailcrmConstants::MODULE_ID,
            RetailcrmConstants::CRM_CATALOG_BASE_PRICE . '_' . $this->setup->profileID,
            0
        );
        
        if (!$basePriceId) {
            $dbPriceType = CCatalogGroup::GetList(
                [],
                ['BASE' => 'Y'],
                false,
                false,
                ['ID']
            );
            
            $result      = $dbPriceType->GetNext();
            $basePriceId = $result['ID'];
        }
        
        return $basePriceId;
    }
    
    /**
     * @param array $product
     * @return int|null
     */
    private function getPurchasePrice(array $product): ?int
    {
        if ($this->setup->loadPurchasePrice) {
            if ($product['PURCHASE_PRICE']) {
                return $product['PURCHASE_PRICE'];
            }
    
            if ("Y" === $this->purchasePriceNull) {
                return 0;
            }
        }else{
            return null;
        }
    }
}
