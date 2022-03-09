<?php

namespace Intaro\RetailCrm\Icml;

use Bitrix\Catalog\ProductTable;
use Intaro\RetailCrm\Model\Bitrix\Orm\CatalogIblockInfo;
use Intaro\RetailCrm\Model\Bitrix\Xml\SelectParams;
use Intaro\RetailCrm\Model\Bitrix\Xml\XmlData;
use Intaro\RetailCrm\Model\Bitrix\Xml\XmlOffer;
use Intaro\RetailCrm\Model\Bitrix\Xml\XmlSetup;
use Intaro\RetailCrm\Repository\CatalogRepository;
use Logger;
use RetailcrmConfigProvider;

/**
 * Class IcmlDirector
 * @package Intaro\RetailCrm\Icml
 */
class IcmlDirector
{
    public const INFO = 'INFO';
    public const OFFERS_PART = 500;
    public const FILE_LOG_NAME = 'i_crm_load_log';
    public const DEFAULT_PRODUCT_PAGE_SIZE = 1;

    /**
     * @var IcmlWriter
     */
    private $icmlWriter;

    /**
     * @var \Intaro\RetailCrm\Icml\XmlOfferDirector
     */
    private $xmlOfferDirector;

    /**
     * @var \Intaro\RetailCrm\Model\Bitrix\Xml\XmlSetup
     */
    private $setup;

    /**
     * @var \Intaro\RetailCrm\Repository\CatalogRepository
     */
    private $catalogRepository;

    /**
     * @var string
     */
    private $shopName;

    /**
     * @var \Intaro\RetailCrm\Icml\XmlCategoryDirector
     */
    private $xmlCategoryDirector;

    /**
     * @var \Intaro\RetailCrm\Icml\QueryParamsMolder
     */
    private $queryBuilder;

    /**
     * @var \Intaro\RetailCrm\Model\Bitrix\Xml\XmlData
     */
    private $xmlData;

    /**
     * @var \Logger
     */
    private $logger;

    /**
     * RetailCrmlXml constructor.
     *
     * @param \Intaro\RetailCrm\Model\Bitrix\Xml\XmlSetup $setup
     * @param \Logger                                     $logger
     */
    public function __construct(XmlSetup $setup, Logger $logger)
    {
        $this->setup = $setup;
        $this->shopName = RetailcrmConfigProvider::getSiteName();
        $this->catalogRepository = new CatalogRepository();
        $this->icmlWriter = new IcmlWriter($this->setup->filePath);
        $this->xmlOfferDirector = new XmlOfferDirector($this->setup);
        $this->xmlCategoryDirector = new XmlCategoryDirector($this->setup->iblocksForExport);
        $this->queryBuilder = new QueryParamsMolder();
        $this->xmlData = new XmlData();
        $this->logger = $logger;
    }

    /**
     * Основной метод. Генерирует icml файл католога товаров Битрикс
     */
    public function generateXml(): void
    {
        unlink($this->setup->filePath);
        $this->setXmlData();
        $this->icmlWriter->writeToXmlTop($this->xmlData);
        $this->logger->write(
            self::INFO . ': Start writing categories and header',
            self::FILE_LOG_NAME
        );
        $this->icmlWriter->writeToXmlHeaderAndCategories($this->xmlData);
        $this->logger->write(
            self::INFO . ': End writing categories in XML and Start writing offers',
            self::FILE_LOG_NAME
        );
        $this->writeOffers();
        $this->logger->write(
            self::INFO . ': End writing offers in XML',
            self::FILE_LOG_NAME
        );
        $this->icmlWriter->writeToXmlBottom();
        $this->logger->write(
            self::INFO . ': Loading complete (peak memory usage: ' . memory_get_peak_usage() . ')',
            self::FILE_LOG_NAME
        );
    }

    /**
     * @return void
     */
    private function setXmlData(): void
    {
        $this->xmlData->shopName   = $this->shopName;
        $this->xmlData->company    = $this->shopName;
        $this->xmlData->filePath   = $this->setup->filePath;
        $this->xmlData->categories = $this->xmlCategoryDirector->getXmlCategories();
    }

    /**
     * записывает оферы всех торговых каталогов в xml файл
     */
    private function writeOffers(): void
    {
        $this->icmlWriter->startOffersBlock();

        foreach ($this->setup->iblocksForExport as $iblockId) {
            $this->writeIblockOffers($iblockId);
        }

        $this->icmlWriter->endBlock();
    }

    /**
     * записывает оферы конкретного торгового каталога товаров в xml файл
     *
     * @param int $productIblockId  //ID инфоблока товаров в торговом каталоге
     */
    private function writeIblockOffers(int $productIblockId): void
    {
        $catalogIblockInfo = $this->catalogRepository->getCatalogIblockInfo($productIblockId);

        //если нет торговых предложений
        if ($catalogIblockInfo->skuIblockId === null) {
            $selectParams
                = $this->queryBuilder->getSelectParams(
                $this->setup->properties->products->names[$productIblockId],
                $this->setup->basePriceId
            );

            $selectParams->pageNumber = 1;
            $selectParams->nPageSize  = self::OFFERS_PART;
            $selectParams->parentId   = null;
            $selectParams->allParams = array_merge($selectParams->configurable, $selectParams->main);

            while ($xmlOffers = $this->xmlOfferDirector->getXmlOffersPart($selectParams, $catalogIblockInfo)) {
                $this->icmlWriter->writeOffers($xmlOffers);

                $selectParams->pageNumber++;
            }

            return;
        }

        //если есть торговые предложения
        $paramsForProduct
            = $this->queryBuilder->getSelectParams(
            $this->setup->properties->products->names[$productIblockId],
            $this->setup->basePriceId
        );

        $paramsForOffer
            = $this->queryBuilder->getSelectParams(
            $this->setup->properties->sku->names[$productIblockId],
            $this->setup->basePriceId
        );

        $this->writeOffersAsOffersInXml($paramsForProduct, $paramsForOffer, $catalogIblockInfo);
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
        $paramsForProduct->nPageSize = $this->calculateProductPageSize();
        $paramsForProduct->allParams = array_merge($paramsForProduct->configurable, $paramsForProduct->main);

        do {
            $productsPart = $this->xmlOfferDirector->getXmlOffersPart($paramsForProduct, $catalogIblockInfo);
            $paramsForProduct->pageNumber++;

            $this->writeProductsOffers($productsPart, $paramsForOffer, $catalogIblockInfo);
        } while (!empty($productsPart));
    }

    /**
     * Записывает в файл оферы всех товаров из $products
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
        $paramsForOffer->nPageSize = $this->calculateOffersPageSize();

        foreach ($products as $product) {
            $this->writeProductOffers($paramsForOffer, $catalogIblockInfo, $product);
        }
    }

    /**
     * Записывает оферы отдельного продукта в xml файл
     *
     * @param \Intaro\RetailCrm\Model\Bitrix\Xml\SelectParams      $paramsForOffer
     * @param \Intaro\RetailCrm\Model\Bitrix\Orm\CatalogIblockInfo $catalogIblockInfo
     * @param \Intaro\RetailCrm\Model\Bitrix\Xml\XmlOffer          $product
     */
    private function writeProductOffers(
        SelectParams $paramsForOffer,
        CatalogIblockInfo $catalogIblockInfo,
        XmlOffer $product
    ): void {
        $paramsForOffer->pageNumber = 1;
        $writingOffersCount = 0;
        $paramsForOffer->parentId = $product->id;
        $paramsForOffer->allParams = array_merge($paramsForOffer->configurable, $paramsForOffer->main);

        do {
            //Если каталог проиндексирован, у товара есть Тип и это простой товар, то просто записываем его
            if ($product->productType = ProductTable::TYPE_PRODUCT) {
                $this->icmlWriter->writeOffers([$product]);
                break;
            }

            $xmlOffersPart
                = $this->xmlOfferDirector->getXmlOffersBySingleProduct($paramsForOffer, $catalogIblockInfo, $product);

            // если это "простой товар", у которого нет ТП, то просто записываем его
            if ($paramsForOffer->pageNumber === 1 && count($xmlOffersPart) === 0) {
                $this->icmlWriter->writeOffers([$product]);
                break;
            }

            if (!empty($xmlOffersPart)) {
                $xmlOffersPart
                    = $this->trimOffersList($writingOffersCount, $xmlOffersPart);

                $this->icmlWriter->writeOffers($xmlOffersPart);

                $writingOffersCount += count($xmlOffersPart);
                $paramsForOffer->pageNumber++;
            }
        } while ($this->shouldContinueWriting($writingOffersCount, $xmlOffersPart));
    }

    /**
     * Проверяет,не достигнул ли лимит по записываемым оферам maxOffersValue
     * и обрезает массив до лимита, если он достигнут
     *
     * @param int        $writingOffers
     * @param XmlOffer[] $xmlOffers
     *
     * @return XmlOffer[]
     */
    private function trimOffersList(int $writingOffers, array $xmlOffers): array
    {
        if (!empty($this->setup->maxOffersValue) && ($writingOffers + count($xmlOffers)) > $this->setup->maxOffersValue) {
            $sliceIndex
                = count($xmlOffers) - ($writingOffers + count($xmlOffers) - $this->setup->maxOffersValue);
            return array_slice($xmlOffers, 0, $sliceIndex);
        }

        return $xmlOffers;
    }

    /**
     * Возвращает размер страницы для запроса товаров
     *
     * @return int
     */
    private function calculateProductPageSize(): int
    {
        if (empty($this->setup->maxOffersValue)) {
            return self::DEFAULT_PRODUCT_PAGE_SIZE;
        }

        return (int) ceil(self::OFFERS_PART / $this->setup->maxOffersValue);
    }

    /**
     * Возвращает размер страницы для офферов
     *
     * @return int
     */
    private function calculateOffersPageSize(): int
    {
        if (empty($this->setup->maxOffersValue)) {
            return self::OFFERS_PART;
        }

        return $this->setup->maxOffersValue < self::OFFERS_PART ?
            $this->setup->maxOffersValue : self::OFFERS_PART;
    }

    /**
     * Проверяет, нужно ли дальше записывать офферы
     *
     * @param int   $writingOffers
     * @param \Intaro\RetailCrm\Model\Bitrix\Xml\XmlOffer[] $xmlOffers
     *
     * @return bool
     */
    private function shouldContinueWriting(int $writingOffers, array $xmlOffers): bool
    {
        if (empty($this->setup->maxOffersValue)) {
            return !empty($xmlOffers);
        }

        return !empty($xmlOffers) && $writingOffers < $this->setup->maxOffersValue;
    }
}
