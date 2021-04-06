<?php

namespace Intaro\RetailCrm\Icml;

use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\SectionTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use CCatalogMeasure;
use CCatalogStoreBarCode;
use CFile;
use CIBlockElement;
use COption;
use Intaro\RetailCrm\Model\Bitrix\Orm\CatalogIblockInfo;
use Intaro\RetailCrm\Model\Bitrix\Xml\OfferParam;
use Intaro\RetailCrm\Model\Bitrix\Xml\SelectParams;
use Intaro\RetailCrm\Model\Bitrix\Xml\Unit;
use Intaro\RetailCrm\Model\Bitrix\Xml\XmlCategory;
use Intaro\RetailCrm\Model\Bitrix\Xml\XmlData;
use Intaro\RetailCrm\Model\Bitrix\Xml\XmlOffer;
use Intaro\RetailCrm\Model\Bitrix\Xml\XmlSetup;
use Intaro\RetailCrm\Service\Hl;
use RetailcrmConfigProvider;

/**
 * Class IcmlDataManager
 * @package Intaro\RetailCrm\Icml
 */
class IcmlDataManager
{
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
     */
    public function __construct(XmlSetup $setup)
    {
        $this->setup             = $setup;
        $this->shopName          = COption::GetOptionString('main', 'site_name');
        $this->purchasePriceNull = RetailcrmConfigProvider::getCrmPurchasePrice();
        $this->measures = $this->getMeasures();
    }
    
    /**
     * возвращает массив XmlOffers для конкретного продукта
     *
     * @param \Intaro\RetailCrm\Model\Bitrix\Xml\SelectParams      $paramsForOffer
     * @param \Intaro\RetailCrm\Model\Bitrix\Orm\CatalogIblockInfo $catalogIblockInfo
     * @param \Intaro\RetailCrm\Model\Bitrix\Xml\XmlOffer          $product
     * @return XmlOffer[]
     */
    public function getXmlOffersBySingleProduct(
        SelectParams $paramsForOffer,
        CatalogIblockInfo $catalogIblockInfo,
        XmlOffer $product
    ): array {
        $xmlOffers = $this->getXmlOffersPart($paramsForOffer, $catalogIblockInfo);
        
        return $this->addProductInfo($xmlOffers, $product);
    }
    
    /**
     * @return \Intaro\RetailCrm\Model\Bitrix\Xml\XmlData
     */
    public function getXmlData(): XmlData
    {
        $xmlData = new XmlData();
        $xmlData->shopName = $this->shopName;
        $xmlData->company = $this->shopName;
        $xmlData->filePath = $this->setup->filePath;
        $xmlData->categories = $this->getCategories();
        
        return $xmlData;
    }
    
    /**
     * Возвращает страницу (массив) с товарами или торговыми предложениями (в зависимости от $param)
     *
     * @param \Intaro\RetailCrm\Model\Bitrix\Xml\SelectParams      $param
     * @param \Intaro\RetailCrm\Model\Bitrix\Orm\CatalogIblockInfo $catalogIblockInfo
     * @return XmlOffer[]
     */
    public function getXmlOffersPart(SelectParams $param, CatalogIblockInfo $catalogIblockInfo): array
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

        $ciBlockResult = CIBlockElement::GetList(
            [],
            $where,
            false,
            ['nPageSize' => $param->nPageSize, 'iNumPage' => $param->pageNumber, 'checkOutOfRange' => true],
            array_merge($param->configurable, $param->main)
        );
        $products      = [];
        $barcodes      = $this->getProductBarcodesByIblock($catalogIblockInfo->productIblockId);
        
        while ($product = $ciBlockResult->GetNext()) {
            $xmlOffer          = new XmlOffer();
            $xmlOffer->barcode = $barcodes[$product['ID']];
            
            if ($param->parentId === null) {
                $pictureProperty = $this->setup->properties->products->pictures[$catalogIblockInfo->productIblockId];
            } else {
                $pictureProperty = $this->setup->properties->sku->pictures[$catalogIblockInfo->productIblockId];
            }
    
            $xmlOffer->picture = $this->getProductPicture($product, $pictureProperty ?? '');
            
            $this->addDataFromParams(
                $xmlOffer,
                $product,
                $param->configurable,
                $catalogIblockInfo
            );
    
            $products[] = $this->addDataFromItem($product, $xmlOffer);
        }
        
        return $products;
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
        $resultParams = $this->getHlParams(
            $iblockInfo->productIblockId,
            $productProps,
            $configurableParams,
            $this->setup->properties->highloadblockProduct
        );
        
        //достаем значения из HL блоков торговых предложений
        $resultParams = array_merge($resultParams, $this->getHlParams(
            $iblockInfo->productIblockId,
            $productProps,
            $configurableParams,
            $this->setup->properties->highloadblockSku
        ));
        
        //достаем значения из обычных свойств
        $resultParams = array_merge($resultParams, $this->getSimpleParams(
            $resultParams,
            $configurableParams,
            $productProps
        ));
    
        [$resultParams, $xmlOffer->dimensions]
            = $this->extractDimensionsFromParams($resultParams, $iblockInfo->productIblockId);
        [$resultParams, $xmlOffer->weight]
            = $this->extractWeightFromParams($resultParams, $iblockInfo->productIblockId);
        [$resultParams, $xmlOffer->vendor] = $this->extractVendorFromParams($resultParams);
        $resultParams     = $this->dropEmptyParams($resultParams);
        $xmlOffer->params = $this->createParamObject($resultParams);
        
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
            
            $xmlCategories = array_merge($xmlCategories, $this->getXmlCategories($categories));
        }
        
        return $xmlCategories;
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
        $xmlOffer->price         = $item['CATALOG_PRICE_' . $this->setup->basePriceId];
        $xmlOffer->purchasePrice = $this->getPurchasePrice($item);
        
        $repository = new \CategoryRepository();
        
        $xmlOffer->categoryIds   = $repository->getProductCategoriesIds($item['ID']);
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
    private function getProductBarcodesByIblock(int $iblockId): array
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
            if (empty(GetMessage("PARAM_NAME_$code"))) {
                continue;
            }
            
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
        $resMeasure = CCatalogMeasure::getList();
        
        while ($measure = $resMeasure->Fetch()) {
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
