<?php

namespace Intaro\RetailCrm\Icml;

use Bitrix\Conversion\Utils;
use COption;
use Intaro\RetailCrm\Icml\Utils\IcmlLogger;
use Intaro\RetailCrm\Icml\Utils\IcmlUtils;
use Intaro\RetailCrm\Model\Bitrix\Orm\CatalogIblockInfo;
use Intaro\RetailCrm\Model\Bitrix\Xml\SelectParams;
use Intaro\RetailCrm\Model\Bitrix\Xml\XmlData;
use Intaro\RetailCrm\Model\Bitrix\Xml\XmlOffer;
use Intaro\RetailCrm\Model\Bitrix\Xml\XmlSetup;
use Intaro\RetailCrm\Repository\CatalogRepository;

/**
 * Class IcmlDirector
 * @package Intaro\RetailCrm\Icml
 */
class IcmlDirector
{
    public const INFO        = 'INFO';
    public const OFFERS_PART = 500;
    
    /**
     * @var IcmlWriter
     */
    private $icmlWriter;
    
    /** @var XmlOfferBuilder */
    private $xmlOfferBuilder;
    
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
     * @var \Intaro\RetailCrm\Icml\XmlCategoriesBuilder
     */
    private $xmlCategoryBuilder;
    
    /**
     * @var \Intaro\RetailCrm\Icml\QueryBuilder
     */
    private $queryBuilder;
    
    /**
     * @var \Intaro\RetailCrm\Model\Bitrix\Xml\XmlData
     */
    private $xmlData;
    
    /**
     * RetailCrmlXml constructor.
     * @param \Intaro\RetailCrm\Model\Bitrix\Xml\XmlSetup $setup
     */
    public function __construct(XmlSetup $setup)
    {
        $this->setup              = $setup;
        $this->shopName           = COption::GetOptionString('main', 'site_name');
        $this->catalogRepository  = new CatalogRepository();
        $this->setup->basePriceId = $this->catalogRepository->getBasePriceId($this->setup->profileID);
        $this->icmlWriter         = new IcmlWriter($this->setup->filePath);
        $this->xmlOfferBuilder    = new XmlOfferBuilder($this->setup);
        $this->xmlCategoryBuilder = new XmlCategoriesBuilder($setup);
        $this->queryBuilder       = new QueryBuilder();
        $this->xmlData            = new XmlData();
        $this->setXmlData();
    }
    
    /**
     * Основной метод. Генерирует icml файл католога товаров Битрикс
     */
    public function generateXml(): void
    {
        IcmlLogger::writeToToLog(Date('Y:m:d H:i:s')
            . ': Start writing categories and header', self::INFO);
        $this->icmlWriter->writeToXmlHeaderAndCategories($this->xmlData);
        IcmlLogger::writeToToLog(Date('Y:m:d H:i:s')
            . ': End writing categories in XML and Start writing offers', self::INFO);
        $this->writeOffers();
        IcmlLogger::writeToToLog(Date('Y:m:d H:i:s')
            . ': End writing offers in XML', self::INFO);
        $this->icmlWriter->writeToXmlBottom();
        IcmlLogger::writeToToLog(Date('Y:m:d H:i:s')
            . ': Loading complete (peek memory usage: ' . memory_get_peak_usage() . ')', self::INFO);
    }
    
    /**
     * @return void
     */
    private function setXmlData(): void
    {
        $this->xmlData->shopName   = $this->shopName;
        $this->xmlData->company    = $this->shopName;
        $this->xmlData->filePath   = $this->setup->filePath;
        $this->xmlData->categories = $this->xmlCategoryBuilder->getCategories();
    }
    
    /**
     * записывает оферы всех торговых каталогов в xml файл
     */
    private function writeOffers(): void
    {
        foreach ($this->setup->iblocksForExport as $iblockId) {
            $this->writeIblockOffers($iblockId);
        }
    }
    
    /**
     * записывает оферы конкретного торгового каталога товаров в xml файл
     *
     * @param int $productIblockId  //ID инфоблока товаров в торговом каталоге
     */
    private function writeIblockOffers(int $productIblockId): void
    {
        $catalogIblockInfo = $this->catalogRepository->getCatalogIblockInfo($productIblockId);
        
        if ($catalogIblockInfo->skuIblockId === null) {
            $selectParams
                = $this->queryBuilder->getSelectParams(
                $this->setup->properties->products->names[$productIblockId],
                $this->setup->basePriceId
            );
            
            $selectParams->pageNumber = 1;
            $selectParams->nPageSize  = self::OFFERS_PART;
            $selectParams->parentId   = null;
            
            while ($xmlOffers = $this->xmlOfferBuilder->getXmlOffersPart($selectParams, $catalogIblockInfo)) {
                $this->icmlWriter->writeOffers($xmlOffers);
                
                $selectParams->pageNumber++;
            }
        } else {
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
            $productsPart = $this->xmlOfferBuilder->getXmlOffersPart($paramsForProduct, $catalogIblockInfo);
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
                = $this->xmlOfferBuilder->getXmlOffersBySingleProduct($paramsForOffer, $catalogIblockInfo, $product);

            // если это "простой товар", у которого нет ТП, то просто записываем его
            if ($paramsForOffer->pageNumber === 1 && count($xmlOffers) === 0) {
                $this->icmlWriter->writeOffers([$product]);
                break;
            }
            
            if (!empty($xmlOffers)) {
                $xmlOffers = IcmlUtils::trimOffersToLimitIfLimit($writingOffers, $xmlOffers, $this->setup->maxOffersValue);
    
                $this->icmlWriter->writeOffers($xmlOffers);
    
                $writingOffers += count($xmlOffers);
                $paramsForOffer->pageNumber++;
            }
        } while (!empty($xmlOffers) && $writingOffers < $this->setup->maxOffersValue);
    }
}
