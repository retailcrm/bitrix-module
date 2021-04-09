<?php

namespace Intaro\RetailCrm\Icml;

use Intaro\RetailCrm\Icml\Utils\IcmlUtils;
use Intaro\RetailCrm\Model\Bitrix\Orm\CatalogIblockInfo;
use Intaro\RetailCrm\Model\Bitrix\Xml\OfferParam;
use Intaro\RetailCrm\Model\Bitrix\Xml\SelectParams;
use Intaro\RetailCrm\Model\Bitrix\Xml\Unit;
use Intaro\RetailCrm\Model\Bitrix\Xml\XmlOffer;
use Intaro\RetailCrm\Model\Bitrix\Xml\XmlSetup;
use Intaro\RetailCrm\Repository\CatalogRepository;
use Intaro\RetailCrm\Repository\FileRepository;
use Intaro\RetailCrm\Repository\HlRepository;
use Intaro\RetailCrm\Repository\MeasureRepository;
use Intaro\RetailCrm\Repository\SiteRepository;
use RetailcrmConfigProvider;

/**
 * Отвечает за создание XMLOffer
 *
 * Class XmlOfferBuilder
 * @package Intaro\RetailCrm\Icml
 */
class XmlOfferBuilder
{
    /**
     * @var \Intaro\RetailCrm\Model\Bitrix\Xml\XmlSetup
     */
    private $setup;
    
    /**
     * @var \Intaro\RetailCrm\Repository\FileRepository
     */
    private $fileRepository;
    
    /**
     * @var \Intaro\RetailCrm\Repository\CatalogRepository
     */
    private $catalogRepository;
    
    /**
     * @var bool|string|null
     */
    private $purchasePriceNull;
    
    /**
     * @var array
     */
    private $measures;
    
    /**
     * @var \Intaro\RetailCrm\Icml\QueryParamsMolder
     */
    private $builder;
    
    /**
     * @var string|null
     */
    private $defaultServerName;
    
    /**
     * IcmlDataManager constructor.
     *
     * @param \Intaro\RetailCrm\Model\Bitrix\Xml\XmlSetup $setup
     */
    public function __construct(XmlSetup $setup)
    {
        $this->setup             = $setup;
        $this->purchasePriceNull = RetailcrmConfigProvider::getCrmPurchasePrice();
        $this->measures          = MeasureRepository::getMeasures();
        $this->defaultServerName = SiteRepository::getDefaultServerName();
        $this->fileRepository    = new FileRepository($this->defaultServerName);
        $this->catalogRepository = new CatalogRepository();
        $this->builder           = new QueryParamsMolder();
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
     * Возвращает страницу (массив) с товарами или торговыми предложениями (в зависимости от $param)
     *
     * @param \Intaro\RetailCrm\Model\Bitrix\Xml\SelectParams      $param
     * @param \Intaro\RetailCrm\Model\Bitrix\Orm\CatalogIblockInfo $catalogIblockInfo
     * @return XmlOffer[]
     */
    public function getXmlOffersPart(SelectParams $param, CatalogIblockInfo $catalogIblockInfo): array
    {
        $where         = $this->builder->getWhereForOfferPart($param->parentId, $catalogIblockInfo);
        $ciBlockResult = $this->catalogRepository->getProductPage(
            $where,
            array_merge($param->configurable, $param->main),
            $param->nPageSize,
            $param->pageNumber
        );

        $barcodes =  $this->catalogRepository->getProductBarcodesByIblockId($catalogIblockInfo->productIblockId);
        $products = [];
        
        while ($product = $ciBlockResult->GetNext()) {
            $xmlOffer          = new XmlOffer();
            $xmlOffer->barcode = $barcodes[$product['ID']];
            
            if ($param->parentId === null) {
                $pictureProperty = $this->setup->properties->products->pictures[$catalogIblockInfo->productIblockId];
            } else {
                $pictureProperty = $this->setup->properties->sku->pictures[$catalogIblockInfo->productIblockId];
            }
    
            $xmlOffer->picture = $this->fileRepository->getProductPicture($product, $pictureProperty ?? '');
            
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
        $resultParams = array_merge($resultParams, IcmlUtils::getSimpleParams(
            $resultParams,
            $configurableParams,
            $productProps
        ));
    
        [$resultParams, $xmlOffer->dimensions]
            = IcmlUtils::extractDimensionsFromParams(
                $this->setup->properties,
                $resultParams,
                $iblockInfo->productIblockId
        );
        [$resultParams, $xmlOffer->weight]
            = IcmlUtils::extractWeightFromParams($this->setup->properties, $resultParams, $iblockInfo->productIblockId);
        [$resultParams, $xmlOffer->vendor] = IcmlUtils::extractVendorFromParams($resultParams);
        $resultParams     = IcmlUtils::dropEmptyParams($resultParams);
        $xmlOffer->params = $this->createParamObject($resultParams);
        
        return $xmlOffer;
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
            ? $this->defaultServerName . $item['DETAIL_PAGE_URL']
            : '';
        $xmlOffer->price         = $item['CATALOG_PRICE_' . $this->setup->basePriceId];
        $xmlOffer->purchasePrice = IcmlUtils::getPurchasePrice(
            $item,
            $this->setup->loadPurchasePrice,
            $this->purchasePriceNull
        );
        $xmlOffer->categoryIds   = $this->catalogRepository->getProductCategoriesIds($item['ID']);
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
     * Получение настраиваемых параметров, если они лежат в HL-блоке
     *
     * @param int   $iblockId //ID инфоблока товаров, даже если данные нужны по SKU
     * @param array $productProps
     * @param array $configurableParams
     * @param array $hls
     * @return array
     */
    private function getHlParams(int $iblockId, array $productProps, array $configurableParams, array $hls): array
    {
        $params = [];
        
        foreach ($hls as $hlName => $hlBlockProduct) {
            if (isset($hlBlockProduct[$iblockId])) {
                reset($hlBlockProduct[$iblockId]);
                $firstKey     = key($hlBlockProduct[$iblockId]);
                $hlRepository = new HlRepository($hlName);
                
                if ($hlRepository->getHl() === null) {
                    continue;
                }
                
                $result = $hlRepository->getDataByXmlId($productProps[$configurableParams[$firstKey] . '_VALUE']);
                
                if ($result === null) {
                    continue;
                }
                
                foreach ($hlBlockProduct[$iblockId] as $hlPropCodeKey => $hlPropCode) {
                    if (isset($result[$hlPropCode])) {
                        $params[$hlPropCodeKey] = $result[$hlPropCode];
                    }
                }
            }
        }
        
        return $params;
    }
    
    /**
     * Собираем объект параметре заказа
     *
     * @param array $params
     * @return OfferParam[]
     */
    private function createParamObject(array $params): array
    {
        $offerParams = [];
        
        foreach ($params as $code => $value) {
            $paramName = GetMessage('PARAM_NAME_' . $code);
            
            if (empty($paramName)) {
                continue;
            }
            
            $offerParam        = new OfferParam();
            $offerParam->name  = $paramName;
            $offerParam->code  = $code;
            $offerParam->value = $value;
            $offerParams[]     = $offerParam;
        }
        
        return $offerParams;
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
            $offer->unitCode    = $offer->unitCode->merge($product->unitCode);
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
}
