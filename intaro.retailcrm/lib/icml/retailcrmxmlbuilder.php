<?php

namespace Intaro\RetailCrm\Icml;

use Intaro\RetailCrm\Icml\Utils\BasePrice;
use Intaro\RetailCrm\Icml\Utils\IblockUtils;
use Intaro\RetailCrm\Icml\Utils\IcmlLogger;
use Intaro\RetailCrm\Model\Bitrix\Orm\CatalogIblockInfo;
use Intaro\RetailCrm\Model\Bitrix\Xml\SelectParams;
use Intaro\RetailCrm\Model\Bitrix\Xml\XmlOffer;
use Intaro\RetailCrm\Model\Bitrix\Xml\XmlSetup;

/**
 * Class RetailCrmXmlBuilder
 * @package Intaro\RetailCrm\Icml
 */
class RetailCrmXmlBuilder
{
    public const INFO        = 'INFO';
    public const OFFERS_PART = 500;
    
    /**
     * @var IcmlWriter
     */
    private $icmlWriter;
    
    /** @var icmlDataManager */
    private $icmlDataManager;
    
    /**
     * @var \Intaro\RetailCrm\Model\Bitrix\Xml\XmlSetup
     */
    private $setup;
    
    /**
     * RetailCrmlXml constructor.
     * @param \Intaro\RetailCrm\Model\Bitrix\Xml\XmlSetup $setup
     */
    public function __construct(XmlSetup $setup)
    {
        $this->setup              = $setup;
        $this->setup->basePriceId = BasePrice::getBasePriceId($this->setup->profileID);
        $this->icmlWriter         = new IcmlWriter($this->setup->filePath);
        $this->icmlDataManager    = new IcmlDataManager($this->setup);
    }
    
    /**
     * Основной метод. Генерирует icml файл католога товаров Битрикс
     */
    public function generateXml(): void
    {
        IcmlLogger::writeToToLog(Date('Y:m:d H:i:s')
            . ': Start getting data for XML', self::INFO);
        $data = $this->icmlDataManager->getXmlData();
        IcmlLogger::writeToToLog(Date('Y:m:d H:i:s')
            . ': End getting data for XML and Start writing categories and header', self::INFO);
        $this->icmlWriter->writeToXmlHeaderAndCategories($data);
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
        $catalogIblockInfo = IblockUtils::getCatalogIblockInfo($productIblockId);
        
        if ($catalogIblockInfo->skuIblockId === null) {
            $selectParams
                = $this->getSelectParams($this->setup->properties->products->names[$productIblockId]);
            
            $selectParams->pageNumber = 1;
            $selectParams->nPageSize  = self::OFFERS_PART;
            $selectParams->parentId   = null;
            
            while ($xmlOffers = $this->icmlDataManager->getXmlOffersPart( $selectParams, $catalogIblockInfo)) {
                $this->icmlWriter->writeOffers($xmlOffers);
                
                $selectParams->pageNumber++;
            }
        } else {
            $paramsForProduct = $this->getSelectParams($this->setup->properties->products->names[$productIblockId]);
            $paramsForOffer   = $this->getSelectParams($this->setup->properties->sku->names[$productIblockId]);
            
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
            $productsPart = $this->icmlDataManager->getXmlOffersPart($paramsForProduct, $catalogIblockInfo);
            
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
        
        $params->configurable = $userProps ?? [];
        $params->main         = [
            'IBLOCK_ID',
            'IBLOCK_SECTION_ID',
            'NAME',
            'DETAIL_PICTURE',
            'PREVIEW_PICTURE',
            'DETAIL_PAGE_URL',
            'CATALOG_QUANTITY',
            'CATALOG_PRICE_' . $this->setup->basePriceId,
            'CATALOG_PURCHASING_PRICE',
            'EXTERNAL_ID',
            'CATALOG_GROUP_' . $this->setup->basePriceId,
            'ID',
            'LID',
        ];
        
        return $params;
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
                = $this->icmlDataManager->getXmlOffersBySingleProduct($paramsForOffer, $catalogIblockInfo, $product);

            // если это "простой товар", у которого нет ТП, то просто записываем его
            if ($paramsForOffer->pageNumber === 1 && count($xmlOffers) === 0) {
                $this->icmlWriter->writeOffers([$product]);
                break;
            }
            
            if (!empty($xmlOffers)) {
                $xmlOffers = $this->checkMaxOffersLimit($writingOffers, $xmlOffers);
    
                $this->icmlWriter->writeOffers($xmlOffers);
    
                $writingOffers += count($xmlOffers);
    
                $paramsForOffer->pageNumber++;
            }
        } while (!empty($xmlOffers) && $writingOffers < $this->setup->maxOffersValue);
    }
    
    /**
     * Проверяет,не достигнул ли лимит по записываемым оффреам maxOffersValue
     *
     * @param int        $writingOffers
     * @param XmlOffer[] $xmlOffers
     * @return XmlOffer[]
     */
    private function checkMaxOffersLimit(int $writingOffers, array $xmlOffers): array
    {
        if ($writingOffers + count($xmlOffers) > $this->setup->maxOffersValue) {
            $sliceIndex
                = count($xmlOffers) - ($writingOffers + count($xmlOffers) - $this->setup->maxOffersValue);
            return array_slice($xmlOffers, 0, $sliceIndex);
        }
        
        return $xmlOffers;
    }
}
