<?php

namespace Intaro\RetailCrm\Icml;

use Intaro\RetailCrm\Icml\Utils\IcmlUtils;
use Intaro\RetailCrm\Model\Bitrix\Orm\CatalogIblockInfo;
use Intaro\RetailCrm\Model\Bitrix\Xml\OfferParam;
use Intaro\RetailCrm\Model\Bitrix\Xml\SelectParams;
use Intaro\RetailCrm\Model\Bitrix\Xml\Unit;
use Intaro\RetailCrm\Model\Bitrix\Xml\XmlOffer;
use Intaro\RetailCrm\Model\Bitrix\Xml\XmlSetup;
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
     * @var bool|string|null
     */
    private $purchasePriceNull;
    
    /**
     * @var array
     */
    private $measures;
    
    /**
     * @var string|null
     */
    private $defaultServerName;
    
    /**
     * @var array
     */
    private $skuHlParams;
    
    /**
     * @var array
     */
    private $productHlParams;
    
    /**
     * @var string
     */
    private $productPicture;
    
    /**
     * @var \Intaro\RetailCrm\Model\Bitrix\Orm\CatalogIblockInfo
     */
    private $catalogIblockInfo;
    
    /**
     * @var array
     */
    private $productProps;
    
    /**
     * @var string
     */
    private $barcode;
    
    /**
     * @var \Intaro\RetailCrm\Model\Bitrix\Xml\SelectParams
     */
    private $selectParams;
    
    /**
     * @var \Intaro\RetailCrm\Model\Bitrix\Xml\XmlOffer
     */
    private $xmlOffer;
    
    /**
     * IcmlDataManager constructor.
     *
     * XmlOfferBuilder constructor.
     * @param \Intaro\RetailCrm\Model\Bitrix\Xml\XmlSetup $setup
     * @param array                                       $measure
     */
    public function __construct(XmlSetup $setup, array $measure, ?string $defaultServerName)
    {
        $this->setup             = $setup;
        $this->purchasePriceNull = RetailcrmConfigProvider::getCrmPurchasePrice();
        $this->measures          = $measure;
        $this->defaultServerName = $defaultServerName;
     }
    
    /**
     * @return \Intaro\RetailCrm\Model\Bitrix\Xml\XmlOffer
     */
    public function createXmlOffer(): XmlOffer
    {
        $this->xmlOffer          = new XmlOffer();
        $this->xmlOffer->barcode = $this->barcode;
        $this->xmlOffer->picture = $this->productPicture;
        
        $this->addDataFromParams();
        
        return $this->xmlOffer;
    }
    
    /**
     * Добавляет в XmlOffer значения настраиваемых параметров, производителя, вес и габариты
     */
    public function addDataFromParams(): void
    {
        $resultParams = array_merge($this->productHlParams, $this->skuHlParams);
    
        //достаем значения из обычных свойств
        $resultParams = array_merge($resultParams, IcmlUtils::getSimpleParams(
            $resultParams,
            $this->selectParams->configurable,
            $this->productProps
        ));
    
        [$resultParams, $this->xmlOffer->dimensions]
            = IcmlUtils::extractDimensionsFromParams(
            $this->setup->properties,
            $resultParams,
            $this->catalogIblockInfo->productIblockId
        );
        [$resultParams, $this->xmlOffer->weight]
            = IcmlUtils::extractWeightFromParams(
            $this->setup->properties,
            $resultParams,
            $this->catalogIblockInfo->productIblockId
        );
        [$resultParams, $this->xmlOffer->vendor] = IcmlUtils::extractVendorFromParams($resultParams);
        $resultParams           = IcmlUtils::dropEmptyParams($resultParams);
        $this->xmlOffer->params = $this->createParamObject($resultParams);
    }
    
    /**
     * Добавляет в объект XmlOffer информацию из GetList
     *
     * @param array $item
     * @param array $categoryIds
     */
    public function addDataFromItem(array $item, array $categoryIds): void
    {
        $this->xmlOffer->id            = $item['ID'];
        $this->xmlOffer->productId     = $item['ID'];
        $this->xmlOffer->quantity      = $item['CATALOG_QUANTITY'] ?? '';
        $this->xmlOffer->url           = $item['DETAIL_PAGE_URL']
            ? $this->defaultServerName . $item['DETAIL_PAGE_URL']
            : '';
        $this->xmlOffer->price         = $item['CATALOG_PRICE_' . $this->setup->basePriceId];
        $this->xmlOffer->purchasePrice = IcmlUtils::getPurchasePrice(
            $item,
            $this->setup->loadPurchasePrice,
            $this->purchasePriceNull
        );
        $this->xmlOffer->categoryIds   = $categoryIds;
        $this->xmlOffer->name          = $item['NAME'];
        $this->xmlOffer->xmlId         = $item['EXTERNAL_ID'] ?? '';
        $this->xmlOffer->productName   = $item['NAME'];
        $this->xmlOffer->vatRate       = $item['CATALOG_VAT'] ?? 'none';
        
        if (isset($item['CATALOG_MEASURE'])) {
            $this->xmlOffer->unitCode = $this->createUnit($item['CATALOG_MEASURE']);
        }
    }

    /**
     * Декорирует оферы информацией из товаров
     *
     * @param XmlOffer[]                                  $xmlOffers
     * @param \Intaro\RetailCrm\Model\Bitrix\Xml\XmlOffer $product
     * @return \Intaro\RetailCrm\Model\Bitrix\Xml\XmlOffer[]
     */
    public function addProductInfo(array $xmlOffers, XmlOffer $product): array
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
    
    /**
     * @param mixed $skuHlParams
     */
    public function setSkuHlParams($skuHlParams): void
    {
        $this->skuHlParams = $skuHlParams;
    }
    
    /**
     * @param mixed $productHlParams
     */
    public function setProductHlParams($productHlParams): void
    {
        $this->productHlParams = $productHlParams;
    }
    
    /**
     * @param \Intaro\RetailCrm\Model\Bitrix\Xml\SelectParams $selectParams
     */
    public function setSelectParams(SelectParams $selectParams): void
    {
        $this->selectParams = $selectParams;
    }
    
    /**
     * @param array $productProps
     */
    public function setOfferProps(array $productProps): void
    {
        $this->productProps = $productProps;
        
    }
    
    /**
     * @param string $barcode
     */
    public function setBarcode(string $barcode): void
    {
        $this->barcode = $barcode;
    }
    
    /**
     * @param \Intaro\RetailCrm\Model\Bitrix\Orm\CatalogIblockInfo $catalogIblockInfo
     */
    public function setCatalogIblockInfo(CatalogIblockInfo $catalogIblockInfo): void
    {
        $this->catalogIblockInfo = $catalogIblockInfo;
    }
    
    /**
     * @param string $getProductPicture
     */
    public function setPicturesPath(string $getProductPicture): void
    {
        $this->productPicture = $getProductPicture;
    }
    
    /**
     * @return \Intaro\RetailCrm\Model\Bitrix\Xml\XmlOffer
     */
    public function getXmlOffer(): XmlOffer
    {
        return $this->xmlOffer;
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
}
