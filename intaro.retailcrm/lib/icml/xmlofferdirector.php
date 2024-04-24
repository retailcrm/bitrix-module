<?php

namespace Intaro\RetailCrm\Icml;

use CIBlock;
use CIBlockSection;
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
    private       $xmlOfferBuilder;

    /**
     * @var array
     */
    private $barcodes;

    /**
     * XmlOfferFactory constructor.
     * @param \Intaro\RetailCrm\Model\Bitrix\Xml\XmlSetup $setup
     */
    public function __construct(XmlSetup $setup)
    {
        $this->setup = $setup;
        $this->fileRepository = new FileRepository(SiteRepository::getDefaultServerName());
        $this->xmlOfferBuilder = new XmlOfferBuilder(
            $setup,
            MeasureRepository::getMeasures(),
            SiteRepository::getDefaultServerName()
        );
        $this->catalogRepository = new CatalogRepository();

        $this->catalogRepository->setLoadNotActive($this->setup->loadNonActivity);
        $this->barcodes = $this->catalogRepository->getBarcodes();
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
        $offers = [];

        $this->xmlOfferBuilder->setServerName($this->fileRepository->getServerName($catalogIblockInfo->productIblockId));

        while ($offer = $ciBlockResult->Fetch()) {
            $categories = $this->catalogRepository->getProductCategories($offer['ID']);
            $offer['DETAIL_PAGE_URL'] = $this->replaceUrlTemplate($offer, $categories);

            $this->setXmlOfferParams($param, $offer, $catalogIblockInfo);
            $this->xmlOfferBuilder
                ->setCategories(array_column($categories, 'IBLOCK_SECTION_ELEMENT_IBLOCK_SECTION_ID'));

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
            $offer->barcode     = $offer->mergeValues($product->barcode, $offer->barcode);
            $offer->categoryIds = $product->categoryIds;
            $offer->productName = $product->productName;
            $offer->url = $this->mergeUrls($product->url, $offer->url);
            $offer->activityProduct = $product->activity;
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
     */
    private function setXmlOfferParams(
        SelectParams $param,
        array $product,
        CatalogIblockInfo $catalogIblockInfo
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
        $this->xmlOfferBuilder->setBarcode($this->barcodes[$product['ID']] ?? '');
        $this->xmlOfferBuilder->setCatalogIblockInfo($catalogIblockInfo);
        $this->xmlOfferBuilder->setPicturesPath(
            $this
                ->fileRepository
                ->getProductPicture($product, $pictureProperty ?? '', $catalogIblockInfo->productIblockId)
        );
    }

    /**
     * @param array $offer
     * @param array $categories
     *
     * @return string
     */
    private function replaceUrlTemplate(array $offer, array $categories): string
    {
        if (strpos($offer['DETAIL_PAGE_URL'], '#PRODUCT_URL#')) {
            return $offer['DETAIL_PAGE_URL'];
        }

        $replaceableUrlParts = [
            '#SITE_DIR#'=> 'LANG_DIR',
            '#ID#' => 'ID',
            '#CODE#'  => 'CODE',
            '#EXTERNAL_ID#' => 'EXTERNAL_ID',
            '#IBLOCK_TYPE_ID#' => 'IBLOCK_TYPE_ID',
            '#IBLOCK_ID#' => 'IBLOCK_ID',
            '#IBLOCK_CODE#' => 'IBLOCK_CODE',
            '#IBLOCK_EXTERNAL_ID#' => 'IBLOCK_EXTERNAL_ID',
            '#ELEMENT_ID#' => 'ID',
            '#ELEMENT_CODE#' => 'CODE',
        ];

        $resultUrl = $offer['DETAIL_PAGE_URL'];

        foreach ($replaceableUrlParts as $key => $replaceableUrlPart) {
            if (isset($offer[$replaceableUrlPart])) {
                $resultUrl = str_replace($key, $offer[$replaceableUrlPart], $resultUrl);
            }
        }

        if (
            isset($categories[0]['IBLOCK_SECTION_ELEMENT_IBLOCK_SECTION_ID'])
            && strpos($offer['DETAIL_PAGE_URL'], '#SECTION_ID#') !== false
        ) {
            $resultUrl = str_replace(
                '#SECTION_ID#',
                $categories[0]['IBLOCK_SECTION_ELEMENT_IBLOCK_SECTION_ID'],
                $resultUrl
            );
        }

        if (
            isset($categories[0]['IBLOCK_SECTION_ELEMENT_IBLOCK_SECTION_CODE'])
            && strpos($offer['DETAIL_PAGE_URL'], '#SECTION_CODE#') !== false
        ) {
            $resultUrl = str_replace(
                '#SECTION_CODE#',
                $categories[0]['IBLOCK_SECTION_ELEMENT_IBLOCK_SECTION_CODE'],
                $resultUrl
            );
        }

        if (
            isset(
                $categories[0]['IBLOCK_SECTION_ELEMENT_IBLOCK_SECTION_CODE'],
                $categories[0]['IBLOCK_SECTION_ELEMENT_IBLOCK_SECTION_ID']
            )
            && strpos($offer['DETAIL_PAGE_URL'], '#SECTION_CODE_PATH#') !== false
        ) {
            $resultUrl = str_replace(
                '#SECTION_CODE_PATH#',
                CIBlockSection::getSectionCodePath($categories[0]['IBLOCK_SECTION_ELEMENT_IBLOCK_SECTION_ID']),
                $resultUrl
            );
        }

        return str_replace('//', '/', $resultUrl);
    }

    /**
     * @param string $productUrl
     * @param string $offerUrl
     *
     * @return string
     */
    private function mergeUrls(string $productUrl, string $offerUrl): string
    {
        if (strpos($offerUrl, '#PRODUCT_URL#') !== false) {
            return $productUrl;
        }

        return $offerUrl;
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
