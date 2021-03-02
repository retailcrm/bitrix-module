<?php

namespace Intaro\RetailCrm\Icml;

use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\SectionTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use CCatalogGroup;
use CCatalogMeasure;
use CCatalogSku;
use CCatalogStoreBarCode;
use CFile;
use CIBlockElement;
use COption;
use Intaro\RetailCrm\Icml\Utils\IcmlLogger;
use Intaro\RetailCrm\Model\Bitrix\Xml\OfferParam;
use Intaro\RetailCrm\Model\Bitrix\Xml\SelectParams;
use Intaro\RetailCrm\Model\Bitrix\Xml\Unit;
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
     * @var  \Intaro\RetailCrm\Icml\IcmlWriter
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
     * доступные единицы измерений в битриксе
     *
     * @var array
     */
    private $measures;
    
    /**
     * IcmlDataManager constructor.
     * @param \Intaro\RetailCrm\Model\Bitrix\Xml\XmlSetup $setup
     * @param \Intaro\RetailCrm\Icml\IcmlWriter           $icmlWriter
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
        
        $this->measures = $this->getMeasures();
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
     * Собирает значения настраиваемых параметров
     *
     * @param array $productProps
     * @param array $configurableParams
     * @param int   $iblockId
     * @return OfferParam[]
     */
    private function getOfferParamsAndVendor(array $productProps, array $configurableParams, int $iblockId): array
    {
        //достаем значения из HL блоков товаров
        $resultParams = $this->getHlParams($iblockId,
            $productProps,
            $configurableParams,
            $this->setup->properties->highloadblockProduct
        );
    
        //достаем значения из HL блоков торговых предложений
        $resultParams = array_merge($resultParams, $this->getHlParams($iblockId,
            $productProps,
            $configurableParams,
            $this->setup->properties->highloadblockSku
        ));
    
        //достаем значения из обычных свойств
        $resultParams = array_merge($resultParams, $this->getSimpleParams($resultParams,
            $configurableParams,
            $productProps
        ));
    
        $resultParams = $this->dropEmptyParams($resultParams);
        [$cleanParams, $vendor] = $this->separateVendorAndParams($resultParams);
        
        return [$this->createParamObject($cleanParams), $vendor];
    }
    
    /**
     * запрашивает свойства оферов из БД и записывает их в xml
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
     * Собираем свойства, указанные в настройках
     *
     * @param array $userProps
     * @param bool  $fullStack
     * @return \Intaro\RetailCrm\Model\Bitrix\Xml\SelectParams
     */
    private function getSelectParams(array $userProps, bool $fullStack = false): SelectParams
    {
        $catalogFields = ['catalog_length', 'catalog_length', 'catalog_width', 'catalog_height', 'catalog_weight'];
        
        $params = new SelectParams();
        
        foreach ($userProps as $key => $name) {
            if ($name === '') {
                unset($userProps[$key]);
                continue;
            }
           
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
        
        if (isset($product['CATALOG_MEASURE'])) {
            $xmlOffer->unitCode      = $this->createUnit($product['CATALOG_MEASURE']);
        }
        
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
    
    /**
     * @param int   $iblockId
     * @param       $productProps
     * @param       $configurableParams
     * @param array $hls
     * @return array
     */
    private function getHlParams(int $iblockId, $productProps, $configurableParams, array $hls): array
    {
        $params = [];
        
        foreach ($hls as $hlName => $hlBlockProduct) {
            if (isset($hlBlockProduct[$iblockId])) {
            
                reset($hlBlockProduct[$iblockId]);
                $firstKey = key($hlBlockProduct[$iblockId]);
            
                $hl = Hl::getHlClassByTableName($hlName);
            
                if (!$hl) {
                    continue;
                }
    
                try {
                    $result = $hl::query()
                        ->setSelect(['*'])
                        ->where('UF_XML_ID', '=', $productProps[$configurableParams[$firstKey] . '_VALUE'])
                        ->fetch();
        
        
                    foreach ($hlBlockProduct[$iblockId] as $hlPropCodeKey => $hlPropCode) {
                        $params[$hlPropCodeKey] = $result[$hlPropCode];
                    }
                } catch (ObjectPropertyException | ArgumentException | SystemException $exception) {
                    AddMessage2Log($exception->getMessage());
                }
            }
        }
        
        return $params;
    }
    
    /**
     * Получение обычных свойств
     *
     * @param array $resultParams
     * @param array $configurableParams
     * @param array $productProps
     * @return array
     */
    private function getSimpleParams(array $resultParams, array $configurableParams, array $productProps): array
    {
        foreach ($configurableParams as $key => $params) {
            if (isset($resultParams[$key])) {
                continue;
            }
            
            $codeWithValue = $params . '_VALUE';
            
            if (isset($productProps[$codeWithValue])) {
                $resultParams[$key] = $productProps[$codeWithValue];
            }
            
            if (isset($productProps[$params])) {
                $resultParams[$key] = $productProps[$params];
            }
        }
        
        return $resultParams;
    }
    
    /**
     * Разделяем вендора и остальные параметры
     *
     * @param array $resultParams
     * @return array
     */
    private function separateVendorAndParams(array $resultParams): array
    {
        $vendor = null;
        
        if (isset($resultParams['manufacturer'])) {
            $vendor = $resultParams['manufacturer'];
            unset($resultParams['manufacturer']);
        }
        
        return [$resultParams, $vendor];
    }
    
    /**
     * Собираем объект параметре заказа
     *
     * @param $params
     * @return OfferParam[]
     */
    private function createParamObject($params): array
    {
        /** @var OfferParam[] $offerParams */
        $offerParams = [];
        
        foreach ($params as $code => $value) {
            $offerParam        = new OfferParam();
            $offerParam->name  = GetMessage("PARAM_NAME_$code");
            $offerParam->code  = $code;
            $offerParam->value = $value;
    
            $offerParams[] = $offerParam;
        }
        
        return $offerParams;
    }
    
    /**
     * удаляет параметры с пустыми и нулевыми значениями
     *
     * @param array $params
     * @return array
     */
    private function dropEmptyParams(array $params): array
    {
        return array_diff($params, ['', 0, '0']);
    }
    
    /**
     * получает доступные в Битриксе единицы измерения для товаров
     */
    private function getMeasures(): array
    {
        $measures = [];
        
        $res_measure = CCatalogMeasure::getList();
        
        while ($measure = $res_measure->Fetch()) {
            $measures[] = $measure;
        }
        
        return $measures;
    }
    
    /**
     * Собираем объект единицы измерения для товара
     *
     * @param int $measureIndex
     * @return \Intaro\RetailCrm\Model\Bitrix\Xml\Unit
     */
    private function createUnit(int $measureIndex): Unit
    {
        $unit       = new Unit();
        $unit->name = $this->measures[$measureIndex]['MEASURE_TITLE'];
        $unit->code = $this->measures[$measureIndex]['SYMBOL_INTL'];
        $unit->sym  = $this->measures[$measureIndex]['SYMBOL_RUS'];
        
        return $unit;
    }
}
