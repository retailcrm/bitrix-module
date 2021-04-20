<?php

namespace Intaro\RetailCrm\Icml;

use Bitrix\Main\SystemException;
use COption;
use Intaro\RetailCrm\Icml\Utils\IcmlUtils;
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
     */
    public function __construct(XmlSetup $setup)
    {
        $this->setup               = $setup;
        $this->shopName            = RetailcrmConfigProvider::getSiteName();
        $this->catalogRepository   = new CatalogRepository();
        $this->icmlWriter          = new IcmlWriter($this->setup->filePath);
        $this->xmlOfferDirector    = new XmlOfferDirector($this->setup);
        $this->xmlCategoryDirector = new XmlCategoryDirector($this->setup->iblocksForExport);
        $this->queryBuilder        = new QueryParamsMolder();
        $this->xmlData             = new XmlData();
        $this->logger              = Logger::getInstance('/bitrix/catalog_export/');
    }
    
    /**
     * Основной метод. Генерирует icml файл католога товаров Битрикс
     */
    public function generateXml(): void
    {
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
        $paramsForProduct->nPageSize = ceil(self::OFFERS_PART / $this->setup->maxOffersValue);
        
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
        $paramsForOffer->nPageSize
            = $this->setup->maxOffersValue < self::OFFERS_PART ? $this->setup->maxOffersValue : self::OFFERS_PART;
        
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
        $writingOffers              = 0;
        $paramsForOffer->parentId   = $product->id;
        
        do {
            $xmlOffers
                = $this->xmlOfferDirector->getXmlOffersBySingleProduct($paramsForOffer, $catalogIblockInfo, $product);

            // если это "простой товар", у которого нет ТП, то просто записываем его
            if ($paramsForOffer->pageNumber === 1 && count($xmlOffers) === 0) {
                $this->icmlWriter->writeOffers([$product]);
                break;
            }
            
            if (!empty($xmlOffers)) {
                $xmlOffers
                    = $this->trimOffersList($writingOffers, $xmlOffers, $this->setup->maxOffersValue);
    
                $this->icmlWriter->writeOffers($xmlOffers);
    
                $writingOffers += count($xmlOffers);
                $paramsForOffer->pageNumber++;
            }
        } while (!empty($xmlOffers) && $writingOffers < $this->setup->maxOffersValue);
    }

    /**
     * Проверяет,не достигнул ли лимит по записываемым оферам maxOffersValue
     * и обрезает массив до лимита, если он достигнут
     *
     * @param int        $writingOffers
     * @param XmlOffer[] $xmlOffers
     * @param int        $maxOffersValue
     * @return XmlOffer[]
     */
    private function trimOffersList(int $writingOffers, array $xmlOffers, int $maxOffersValue): array
    {
        if (($writingOffers + count($xmlOffers)) > $maxOffersValue) {
            $sliceIndex
                = count($xmlOffers) - ($writingOffers + count($xmlOffers) - $maxOffersValue);
            return array_slice($xmlOffers, 0, $sliceIndex);
        }
        
        return $xmlOffers;
    }
}
