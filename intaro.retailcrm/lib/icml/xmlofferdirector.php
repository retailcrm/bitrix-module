<?php

namespace Intaro\RetailCrm\Icml;

use Intaro\RetailCrm\Model\Bitrix\Orm\CatalogIblockInfo;
use Intaro\RetailCrm\Model\Bitrix\Xml\SelectParams;
use Intaro\RetailCrm\Model\Bitrix\Xml\XmlOffer;
use Intaro\RetailCrm\Model\Bitrix\Xml\XmlSetup;
use Intaro\RetailCrm\Repository\CatalogRepository;
use Intaro\RetailCrm\Repository\FileRepository;
use Intaro\RetailCrm\Repository\HlRepository;
use Intaro\RetailCrm\Repository\MeasureRepository;
use Intaro\RetailCrm\Repository\SiteRepository;

/**
 * Class XmlOfferDirector
 * @package Intaro\RetailCrm\Icml
 */
class XmlOfferDirector
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
     * @var \Intaro\RetailCrm\Icml\XmlOfferBuilder
     */
    private $xmlOfferBuilder;
    
    /**
     * XmlOfferFactory constructor.
     * @param \Intaro\RetailCrm\Model\Bitrix\Xml\XmlSetup $setup
     */
    public function __construct(XmlSetup $setup)
    {
        $this->setup = $setup;
        $this->fileRepository = new FileRepository(SiteRepository::getDefaultServerName());
        $this->catalogRepository = new CatalogRepository();
        $this->xmlOfferBuilder = new XmlOfferBuilder(
            $setup,
            MeasureRepository::getMeasures(),
            SiteRepository::getDefaultServerName()
        );
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
        $ciBlockResult = $this->catalogRepository->getProductPage($param, $catalogIblockInfo);
        $barcodes =  $this->catalogRepository->getProductBarcodesByIblockId($catalogIblockInfo->productIblockId);
        $offers = [];
        
        while ($offer = $ciBlockResult->GetNext()) {
            $this->setXmlOfferParams($param, $offer, $catalogIblockInfo, $barcodes);
            $this->xmlOfferBuilder->setCategories($this->catalogRepository->getProductCategoriesIds($offer['ID']));
           
            $offers[] = $this->xmlOfferBuilder->build();
        }
        
        return $offers;
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
            $offer->params      = $this->mergeParams($offer->params, $product->params);
            $offer->unitCode    = $offer->unitCode === null ? null : $offer->unitCode->merge($product->unitCode);
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
     * @param \Intaro\RetailCrm\Model\Bitrix\Xml\SelectParams      $param
     * @param array                                                $product
     * @param \Intaro\RetailCrm\Model\Bitrix\Orm\CatalogIblockInfo $catalogIblockInfo
     * @param array                                                $barcodes
     */
    private function setXmlOfferParams(
        SelectParams $param,
        array $product,
        CatalogIblockInfo $catalogIblockInfo,
        array $barcodes
    ): void {
        if ($param->parentId === null) {
            $pictureProperty = $this->setup->properties->products->pictures[$catalogIblockInfo->productIblockId];
        } else {
            $pictureProperty = $this->setup->properties->sku->pictures[$catalogIblockInfo->productIblockId];
        }
    
        //достаем значения из HL блоков товаров
        $this->xmlOfferBuilder->setProductHlParams($this->getHlParams(
            $catalogIblockInfo->productIblockId,
            $product,
            $param->configurable,
            $this->setup->properties->highloadblockProduct
        ));
    
        //достаем значения из HL блоков торговых предложений
        $this->xmlOfferBuilder->setSkuHlParams($this->getHlParams(
            $catalogIblockInfo->productIblockId,
            $product,
            $param->configurable,
            $this->setup->properties->highloadblockSku
        ));
        $this->xmlOfferBuilder->setSelectParams($param);
        $this->xmlOfferBuilder->setOfferProps($product);
        $this->xmlOfferBuilder->setBarcode($barcodes[$product['ID']] ?? '');
        $this->xmlOfferBuilder->setCatalogIblockInfo($catalogIblockInfo);
        $this->xmlOfferBuilder->setPicturesPath(
            $this
                ->fileRepository
                ->getProductPicture($product, $pictureProperty ?? '')
        );
    }
    
    /**
     * @param array $offerParams
     * @param array $productParams
     */
    private function mergeParams(array $offerParams, array $productParams): array
    {
        $offerCodes = [];
        
        /** @var \Intaro\RetailCrm\Model\Bitrix\Xml\OfferParam $offerParam */
        foreach ($offerParams as $offerParam) {
            $offerCodes[] = $offerParam->code;
        }

        /** @var \Intaro\RetailCrm\Model\Bitrix\Xml\OfferParam $productParam */
        foreach ($productParams as $productParam) {
            if (in_array($productParam->code, $offerCodes, true)) {
                continue;
            }
        
            $offerParams[] = $productParam;
        }
        
        return $offerParams;
    }
}
