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
use Intaro\RetailCrm\Model\Bitrix\Orm\CatalogIblockInfo;
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
    private const INFO        = 'INFO';
    private const OFFERS_PART = 500;
    private const MILLION     = 1000000;
    
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
     *
     * @param \Intaro\RetailCrm\Model\Bitrix\Xml\XmlSetup $setup
     * @param \Intaro\RetailCrm\Icml\IcmlWriter           $icmlWriter
     */
    public function __construct(XmlSetup $setup, IcmlWriter $icmlWriter)
    {
        $this->basePriceId       = $this->getBasePriceId();
        $this->icmlWriter        = &$icmlWriter;
        $this->setup             = $setup;
        $this->shopName          = COption::GetOptionString('main', 'site_name');
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
     * запрашивает свойства оферов из БД и записывает их в xml
     */
    public function writeOffersHandler(): void
    {
        foreach ($this->setup->iblocksForExport as $iblockId) {
            $catalogIblockInfo = $this->getCatalogIblockInfo($iblockId);
            
            //null - значит нет торговых предложений - работаем только с товарами
            if ($catalogIblockInfo->skuIblockId === null) {
                $arSelectForProduct = $this->getSelectParams($this->setup->properties->products->names[$iblockId]);
                
                $this->writeProductsAsOffersInXml($catalogIblockInfo, $arSelectForProduct);
            } else {
                $paramsForProduct = $this->getSelectParams($this->setup->properties->products->names[$iblockId]);
                $paramsForOffer   = $this->getSelectParams($this->setup->properties->sku->names[$iblockId]);
                
                $this->writeOffersAsOffersInXml($paramsForProduct, $paramsForOffer, $catalogIblockInfo);
            }
        }
    }
    
    /**
     * Добавляет в XmlOffer значения настраиваемых параметров, производителя, вес и габариты
     *
     * @param \Intaro\RetailCrm\Model\Bitrix\Xml\XmlOffer          $xmlOffer
     * @param array                                                $productProps
     * @param array                                                $configurableParams
     * @param \Intaro\RetailCrm\Model\Bitrix\Orm\CatalogIblockInfo $iblockInfo
     * @return \Intaro\RetailCrm\Model\Bitrix\Xml\XmlOffer
     */
    private function addDataFromParams(
        XmlOffer $xmlOffer,
        array $productProps,
        array $configurableParams,
        CatalogIblockInfo $iblockInfo
    ): XmlOffer {
        //достаем значения из HL блоков товаров
        $resultParams = $this->getHlParams($iblockInfo->productIblockId,
            $productProps,
            $configurableParams,
            $this->setup->properties->highloadblockProduct
        );
        
        //достаем значения из HL блоков торговых предложений
        $resultParams = array_merge($resultParams, $this->getHlParams($iblockInfo->productIblockId,
            $productProps,
            $configurableParams,
            $this->setup->properties->highloadblockSku
        ));
        
        //достаем значения из обычных свойств
        $resultParams = array_merge($resultParams, $this->getSimpleParams($resultParams,
            $configurableParams,
            $productProps
        ));
        
        [$resultParams, $dimensions] = $this->extractDimensionsFromParams($resultParams, $iblockInfo->productIblockId);
        [$resultParams, $weight] = $this->extractWeightFromParams($resultParams, $iblockInfo->productIblockId);
        [$resultParams, $vendor] = $this->extractVendorFromParams($resultParams);
        
        $resultParams = $this->dropEmptyParams($resultParams);
        
        $xmlOffer->params     = $this->createParamObject($resultParams);
        $xmlOffer->vendor     = $vendor;
        $xmlOffer->weight     = $weight;
        $xmlOffer->dimensions = $dimensions;
        
        return $xmlOffer;
    }
    
    /**
     * Получение категорий каталога
     *
     * @return XmlCategory[]| null
     */
    private function getCategories(): ?array
    {
        $xmlCategories = [];
        
        foreach ($this->setup->iblocksForExport as $iblockKey => $iblockId) {
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
                        $xmlCategory->picture = $this->setup->defaultServerName
                            . CFile::GetPath($iblock->get('PICTURE'));
                    }
                    
                    $xmlCategories[self::MILLION + $iblock->get('ID')] = $xmlCategory;
                }
            } catch (ObjectPropertyException | ArgumentException | SystemException $exception) {
                return null;
            }
            
            $xmlCategories =array_merge($xmlCategories, $this->getXmlCategories($categories));
        }
        
        return $xmlCategories;
    }
    
    /**
     * Собираем свойства, указанные в настройках
     *
     * @param array|null $userProps
     * @return \Intaro\RetailCrm\Model\Bitrix\Xml\SelectParams
     */
    private function getSelectParams(?array $userProps): SelectParams
    {
        $catalogFields = ['catalog_length', 'catalog_width', 'catalog_height', 'catalog_weight'];
        
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
        $params->main         = [
            'IBLOCK_ID',
            'IBLOCK_SECTION_ID',
            'NAME',
            'DETAIL_PICTURE',
            'PREVIEW_PICTURE',
            'DETAIL_PAGE_URL',
            'CATALOG_QUANTITY',
            'CATALOG_PRICE_' . $this->basePriceId,
            'CATALOG_PURCHASING_PRICE',
            'EXTERNAL_ID',
            'CATALOG_GROUP_' . $this->basePriceId,
            'ID',
            'LID',
        ];
        
        return $params;
    }
    
    /**
     * Эта стратегия записи используется,
     * когда в каталоге нет торговых предложений
     * только товары
     *
     * @param \Intaro\RetailCrm\Model\Bitrix\Orm\CatalogIblockInfo $catalogIblockInfo
     * @param \Intaro\RetailCrm\Model\Bitrix\Xml\SelectParams      $selectParams
     */
    private function writeProductsAsOffersInXml(CatalogIblockInfo $catalogIblockInfo, SelectParams $selectParams): void
    {
        $selectParams->pageNumber = 1;
        $selectParams->nPageSize  = self::OFFERS_PART;
        $selectParams->parentId   = null;
        
        do {
            $xmlOffers = $this->getXmlOffersPart($selectParams, $catalogIblockInfo);
            
            $selectParams->pageNumber++;
            $this->icmlWriter->writeOffers($xmlOffers);
            
            IcmlLogger::writeToToLog(count($xmlOffers)
                . ' product(s) has been loaded from '
                . $catalogIblockInfo->productIblockId
                . ' IB (memory usage: '
                . memory_get_usage()
                . ')',
                self::INFO
            );
        } while (!empty($xmlOffers));
    }
    
    /**
     * Эта стратегия записи используется,
     * когда в каталоге есть торговые предложения
     *
     * @param \Intaro\RetailCrm\Model\Bitrix\Xml\SelectParams      $paramsForProduct
     * @param \Intaro\RetailCrm\Model\Bitrix\Xml\SelectParams      $paramsForOffer
     * @param \Intaro\RetailCrm\Model\Bitrix\Orm\CatalogIblockInfo $catalogIblockInfo
     */
    private function writeOffersAsOffersInXml(
        SelectParams $paramsForProduct,
        SelectParams $paramsForOffer,
        CatalogIblockInfo $catalogIblockInfo
    ): void {
        $paramsForProduct->pageNumber = 1;
        $paramsForProduct->nPageSize  = ceil(self::OFFERS_PART / $this->setup->maxOffersValue);
        
        do {
            $products = $this->getXmlOffersPart($paramsForProduct, $catalogIblockInfo);
            
            $paramsForProduct->pageNumber++;
            $this->writeProductsOffers($products, $paramsForOffer, $catalogIblockInfo);
        } while (!empty($products));
    }
    
    /**
     * Возвращает страницу (массив) с товарами или торговыми предложениями (в зависимости от $param)
     *
     * @param \Intaro\RetailCrm\Model\Bitrix\Xml\SelectParams      $param
     * @param \Intaro\RetailCrm\Model\Bitrix\Orm\CatalogIblockInfo $catalogIblockInfo
     * @return XmlOffer[]
     */
    private function getXmlOffersPart(SelectParams $param, CatalogIblockInfo $catalogIblockInfo): array
    {
        if ($param->parentId === null) {
            $where = [
                'IBLOCK_ID' => $catalogIblockInfo->productIblockId,
                'ACTIVE'    => 'Y',
            ];
        } else {
            $where = [
                'IBLOCK_ID'                                     => $catalogIblockInfo->skuIblockId,
                'ACTIVE'                                        => 'Y',
                'PROPERTY_' . $catalogIblockInfo->skuPropertyId => $param->parentId,
            ];
        }
        
        $ciBlockResult = CIBlockElement::GetList([],
            $where,
            false,
            ['nPageSize' => $param->nPageSize, 'iNumPage' => $param->pageNumber, 'checkOutOfRange' => true],
            array_merge($param->configurable, $param->main)
        );
        $products      = [];
        $barcodes      = $this->getProductBarcodesByIblock($catalogIblockInfo->skuIblockId);
        
        while ($product = $ciBlockResult->GetNext()) {
            $xmlOffer          = new XmlOffer();
            $xmlOffer->barcode = $barcodes[$product['ID']];
            
            if ($param->parentId === null) {
                $pictureProperty = $this->setup->properties->products->pictures[$catalogIblockInfo->productIblockId];
            } else {
                $pictureProperty = $this->setup->properties->sku->pictures[$catalogIblockInfo->productIblockId];
            }
            
            $xmlOffer->picture = $this->getProductPicture($product, $pictureProperty ?? '');
            $xmlOffer          = $this->addDataFromParams(
                $xmlOffer,
                $product,
                $param->configurable,
                $catalogIblockInfo
            );
            $products[]        = $this->addDataFromItem($product, $xmlOffer);
        }
        
        return $products;
    }
    
    /**
     * @param array  $product
     * @param string $pictureProp
     * @return string
     */
    private function getProductPicture(array $product, string $pictureProp = ''): string
    {
        $picture   = '';
        $pictureId = $product['PROPERTY_' . $pictureProp . '_VALUE'] ?? null;
        
        if (isset($product['DETAIL_PICTURE'])) {
            $picture = $this->getImageUrl($product['DETAIL_PICTURE']);
        } elseif (isset($product['PREVIEW_PICTURE'])) {
            $picture = $this->getImageUrl($product['PREVIEW_PICTURE']);
        } elseif ($pictureId !== null) {
            $picture = $this->getImageUrl($pictureId);
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
        $validation = '/^(http|https):\/\/([A-Z0-9][A-Z0-9_-]*(?:\.[A-Z0-9][A-Z0-9_-]*)+):?(\d+)?\/?/i';
        
        if ((bool)preg_match($validation, $pathImage) === false) {
            return $this->setup->defaultServerName . $pathImage;
        }
        
        return $pathImage;
    }
    
    /**
     * Получение категорий, к которым относится товар
     *
     * @param $offerId
     * @return array
     */
    private function getProductCategoriesIds(int $offerId): array
    {
        $query = CIBlockElement::GetElementGroups($offerId, false, ['ID']);
        $ids   = [];
        
        while ($category = $query->GetNext()) {
            $ids[] = $category['ID'];
        }
        
        return $ids;
    }
    
    /**
     * Добавляет в объект XmlOffer информацию из GetList
     *
     * @param array                                       $item
     * @param \Intaro\RetailCrm\Model\Bitrix\Xml\XmlOffer $xmlOffer
     * @return \Intaro\RetailCrm\Model\Bitrix\Xml\XmlOffer
     */
    private function addDataFromItem(array $item, XmlOffer $xmlOffer): XmlOffer
    {
        $xmlOffer->id            = $item['ID'];
        $xmlOffer->productId     = $item['ID'];
        $xmlOffer->quantity      = $item['CATALOG_QUANTITY'] ?? '';
        $xmlOffer->url           = $item['DETAIL_PAGE_URL']
            ? $this->setup->defaultServerName . $item['DETAIL_PAGE_URL']
            : '';
        $xmlOffer->price         = $item['CATALOG_PRICE_' . $this->basePriceId];
        $xmlOffer->purchasePrice = $this->getPurchasePrice($item);
        $xmlOffer->categoryIds   = $this->getProductCategoriesIds($item['ID']);
        $xmlOffer->name          = $item['NAME'];
        $xmlOffer->xmlId         = $item['EXTERNAL_ID'] ?? '';
        $xmlOffer->productName   = $item['NAME'];
        $xmlOffer->vatRate       = $item['CATALOG_VAT'] ?? 'none';
        
        if (isset($item['CATALOG_MEASURE'])) {
            $xmlOffer->unitCode = $this->createUnit($item['CATALOG_MEASURE']);
        }
        
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
            ['IBLOCK_ID' => $iblockId],
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
     * Получение закупочной цены
     *
     * @param array $product //результат GetList
     * @return int|null
     */
    private function getPurchasePrice(array $product): ?int
    {
        if ($this->setup->loadPurchasePrice) {
            if ($product['CATALOG_PURCHASING_PRICE']) {
                return $product['CATALOG_PURCHASING_PRICE'];
            }
            
            if ($this->purchasePriceNull === 'Y') {
                return 0;
            }
        }
        
        return null;
    }
    
    /**
     * Получение настраиваемых параметров, если они лежат в HL-блоке
     *
     * @param int   $iblockId //ID инфоблока товаров, даже если данные нужны по SKU
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
            } elseif (isset($productProps[$params])) {
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
    private function extractVendorFromParams(array $resultParams): array
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
        $offerParams = [];
        
        foreach ($params as $code => $value) {
            $offerParam        = new OfferParam();
            $offerParam->name  = GetMessage("PARAM_NAME_$code");
            $offerParam->code  = $code;
            $offerParam->value = $value;
            $offerParams[]     = $offerParam;
        }
        
        return $offerParams;
    }
    
    /**
     * Удаляет параметры с пустыми и нулевыми значениями
     *
     * @param array $params
     * @return array
     */
    private function dropEmptyParams(array $params): array
    {
        return array_diff($params, ['', 0, '0']);
    }
    
    /**
     * Получает доступные в Битриксе единицы измерения для товаров
     *
     * @return array
     */
    private function getMeasures(): array
    {
        $measures    = [];
        $res_measure = CCatalogMeasure::getList();
        
        while ($measure = $res_measure->Fetch()) {
            $measures[$measure['ID']] = $measure;
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
    
    /**
     * Получает оферы  по товару и записывает их в файл
     *
     * @param XmlOffer[]                                           $products
     * @param \Intaro\RetailCrm\Model\Bitrix\Xml\SelectParams      $paramsForOffer
     * @param \Intaro\RetailCrm\Model\Bitrix\Orm\CatalogIblockInfo $catalogIblockInfo
     */
    private function writeProductsOffers(
        array $products,
        SelectParams $paramsForOffer,
        CatalogIblockInfo $catalogIblockInfo
    ): void {
        $paramsForOffer->nPageSize
            = $this->setup->maxOffersValue < self::OFFERS_PART ? $this->setup->maxOffersValue : self::OFFERS_PART;
        
        foreach ($products as $product) {
            $this->writeOffersBySingleProduct($paramsForOffer, $catalogIblockInfo, $product);
        }
    }
    
    /**
     * записывает оферы одного товара
     *
     * @param \Intaro\RetailCrm\Model\Bitrix\Xml\SelectParams      $paramsForOffer
     * @param \Intaro\RetailCrm\Model\Bitrix\Orm\CatalogIblockInfo $catalogIblockInfo
     * @param \Intaro\RetailCrm\Model\Bitrix\Xml\XmlOffer          $product
     */
    private function writeOffersBySingleProduct(
        SelectParams $paramsForOffer,
        CatalogIblockInfo $catalogIblockInfo,
        XmlOffer $product
    ): void {
        $paramsForOffer->pageNumber = 1;
        $paramsForOffer->parentId   = $product->id;
        $writingOffers              = 0; //счетчик уже записанных оферов товара
        
        do {
            $xmlOffers = $this->getXmlOffersPart($paramsForOffer, $catalogIblockInfo);
            
            if ($paramsForOffer->pageNumber === 1 && count($xmlOffers) === 0) {
                $this->icmlWriter->writeOffers([$product]);
                break;
            }
            
            if ($writingOffers + count($xmlOffers) > $this->setup->maxOffersValue) {
                $sliceIndex
                           = count($xmlOffers) - ($writingOffers + count($xmlOffers) - $this->setup->maxOffersValue);
                $xmlOffers = array_slice($xmlOffers, 0, $sliceIndex);
            }
            
            $paramsForOffer->pageNumber++;
            
            $xmlOffers = $this->addProductInfo($xmlOffers, $product);
            
            $this->icmlWriter->writeOffers($xmlOffers);
            
            $writingOffers += count($xmlOffers);
        } while (!empty($xmlOffers) || $writingOffers < $this->setup->maxOffersValue);
    }
    
    /**
     * Возвращает информацию об инфоблоке торговых предложений по ID инфоблока товаров
     *
     * @param int $productIblockId
     * @return \Intaro\RetailCrm\Model\Bitrix\Orm\CatalogIblockInfo
     */
    private function getCatalogIblockInfo(int $productIblockId): CatalogIblockInfo
    {
        $catalogIblockInfo = new CatalogIblockInfo();
        $info              = CCatalogSKU::GetInfoByProductIBlock($productIblockId);
        
        if ($info === false) {
            $catalogIblockInfo->productIblockId = $productIblockId;
            
            return $catalogIblockInfo;
        }
        
        $catalogIblockInfo->skuIblockId     = $info['IBLOCK_ID'];
        $catalogIblockInfo->productIblockId = $info['PRODUCT_IBLOCK_ID'];
        $catalogIblockInfo->skuPropertyId   = $info['SKU_PROPERTY_ID'];
        
        return $catalogIblockInfo;
    }
    
    /**
     * Декорирует оферы информацией из товаров
     *
     * @param XmlOffer[]                                  $xmlOffers
     * @param \Intaro\RetailCrm\Model\Bitrix\Xml\XmlOffer $product
     * @return \Intaro\RetailCrm\Model\Bitrix\Xml\XmlOffer[]
     */
    private function addProductInfo(array $xmlOffers, XmlOffer $product): array
    {
        foreach ($xmlOffers as $offer) {
            $offer->productId   = $product->id;
            $offer->params      = array_merge($offer->params, $product->params);
            $offer->unitCode    = $offer->unitCode->mergeWithOtherUnit($product->unitCode);
            $offer->vatRate     = $offer->vatRate === 'none' ? $product->vatRate : $offer->vatRate;
            $offer->vendor      = $offer->mergeValues($product->vendor, $offer->vendor);
            $offer->picture     = $offer->mergeValues($product->picture, $offer->picture);
            $offer->weight      = $offer->mergeValues($product->weight, $offer->weight);
            $offer->dimensions  = $offer->mergeValues($product->dimensions, $offer->dimensions);
            $offer->categoryIds = $product->categoryIds;
            $offer->productName = $product->productName;
        }
        
        return $xmlOffers;
    }
    
    /**
     * Получение данных для ноды dimensions
     *
     * Данные должны быть переведены в сантиметры
     * и представлены в формате Длина/Ширина/Высота
     *
     * @param array $resultParams
     * @param int   $iblockId //ID инфоблока товаров (даже если расчет ведется для торговых предложений)
     * @return array
     */
    private function extractDimensionsFromParams(array $resultParams, int $iblockId): array
    {
        $dimensionsParams = ['length', 'width', 'height'];
        $dimensions       = '';
        $factors          = [
            'mm' => 0.1,
            'cm' => 1,
            'm'  => 100,
        ];
        
        foreach ($dimensionsParams as $key => $param) {
            $unit = '';
            
            if (!empty($this->setup->properties->products->names[$iblockId][$param])) {
                $unit = $this->setup->properties->products->units[$iblockId][$param];
            } elseif (!empty($this->setup->properties->sku->names[$iblockId][$param])) {
                $unit = $this->setup->properties->sku->units[$iblockId][$param];
            }
            
            if (isset($factors[$unit], $resultParams[$param])) {
                $dimensions .= $resultParams[$param] * $factors[$unit];
            } else {
                $dimensions .= '0';
            }
            
            if (count($dimensionsParams) > $key + 1) {
                $dimensions .= '/';
            }
            
            if (isset($resultParams[$param])) {
                unset($resultParams[$param]);
            }
        }
        
        return [$resultParams, $dimensions === '0/0/0' ? '' : $dimensions];
    }
    
    /**
     * Преобразует вес товара в килограммы для ноды weight
     *
     * @param array $resultParams
     * @param int   $iblockId //ID инфоблока товаров (даже если расчет ведется для торговых предложений)
     * @return array
     */
    private function extractWeightFromParams(array $resultParams, int $iblockId): array
    {
        $factors = [
            'mg' => 0.000001,
            'g'  => 0.001,
            'kg' => 1,
        ];
        
        $unit = '';
        
        if (!empty($this->setup->properties->products->names[$iblockId]['weight'])) {
            $unit = $this->setup->properties->products->units[$iblockId]['weight'];
        } elseif (!empty($this->setup->properties->sku->names[$iblockId]['weight'])) {
            $unit = $this->setup->properties->sku->units[$iblockId]['weight'];
        }
        
        if (isset($resultParams['weight'], $factors[$unit])) {
            $weight = $resultParams['weight'] * $factors[$unit];
        } else {
            $weight = '';
        }
        
        if (isset($resultParams['weight'])) {
            unset($resultParams['weight']);
        }
        
        return [$resultParams, $weight];
    }
    
    /**
     * Возвращает коллекцию категорий
     *
     * @param $categories
     * @return XmlCategory[]
     */
    private function getXmlCategories($categories): array
    {
        $xmlCategories = [];
        
        foreach ($categories as $categoryKey => $category) {
            $xmlCategory           = new XmlCategory();
            $xmlCategory->id       = $category->get('ID');
            $xmlCategory->name     = $category->get('NAME');
            $xmlCategory->parentId = $category->get('IBLOCK_SECTION_ID');
            
            if ($category->get('PICTURE') !== null) {
                $xmlCategory->picture = $this->setup->defaultServerName . CFile::GetPath($category->get('PICTURE'));
            }
            
            $xmlCategories[$categoryKey] = $xmlCategory;
        }
        
        return $xmlCategories;
    }
}
