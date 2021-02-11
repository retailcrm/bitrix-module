<?php

namespace Intaro\RetailCrm\Icml;

use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\SectionTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use CCatalogSku;
use CFile;
use CIBlockElement;
use COption;
use IcmlWriter;
use Intaro\RetailCrm\Icml\Utils\IcmlLogger;
use Intaro\RetailCrm\Model\Bitrix\Xml\OfferParam;
use Intaro\RetailCrm\Model\Bitrix\Xml\XmlCategory;
use Intaro\RetailCrm\Model\Bitrix\Xml\XmlData;
use Intaro\RetailCrm\Model\Bitrix\Xml\XmlOffer;
use Intaro\RetailCrm\Model\Bitrix\Xml\XmlSetup;

/**
 * Class IcmlDataManager
 * @package Intaro\RetailCrm\Icml
 */
class IcmlDataManager
{
    private const INFO          = 'INFO';
    private const OFFERS_PART   = 500;
    private const MILLION       = 1000000;
    
    /**
     * @var \Intaro\RetailCrm\Model\Bitrix\Xml\XmlSetup
     */
    private $setup;
    
    /**
     * @var false|string|null
     */
    private $shopName;
    
    /**
     * @var \IcmlWriter
     */
    private $icmlWriter;
    
    public function __construct(XmlSetup $setup, IcmlWriter $icmlWriter)
    {
        $this->icmlWriter = &$icmlWriter;
        $this->setup       = $setup;
        $this->shopName    = COption::GetOptionString("main", "site_name");
    }
    
    /**
     * @param $iblockId
     * @return array
     */
    public function getOfferList(int $iblockId): array
    {
        $iblockOfferInfo = CCatalogSKU::GetInfoByProductIBlock($iblockId);
        
        //false - значит нет торговых предложений
        if ($iblockOfferInfo === false) {
            $names         = $this->setup->properties->products->names[$iblockId];
            $offerIblockId = $iblockId;
        } else {
            $names         = $this->setup->properties->sku->names[$iblockId];
            $offerIblockId = $iblockOfferInfo['IBLOCK_ID']];
        }
        
        $query         = $this->prepareQuery($names);
        $cIblockResult = CIBlockElement::GetList([],
            ['IBLOCK_ID' => $offerIblockId],
            false, [],
            $query
        );
        
        return [$names, $cIblockResult];
    }
    
    /**
     * @return \Intaro\RetailCrm\Model\Bitrix\Xml\XmlData
     */
    public function getXmlData(): XmlData
    {
        $xmlData             = new XmlData();
        $xmlData->shopName   = $xmlData->company = $this->shopName;
        $xmlData->categories = $this->getCategories();
        
        return $xmlData;
    }
    
    /**
     * @return XmlCategory[]| null
     */
    private function getCategories(): ?array
    {
        $xmlCategories = [];
        
        foreach ($this->setup->iblocksForExport as $iblockKey => $iblockId){
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
                        $xmlCategory->picture  = $this->setup->defaultServerName
                            . CFile::GetPath($iblock->get('PICTURE'));
                    }
                    
                    $xmlCategories[self::MILLION + $iblock->get('ID')]   = $xmlCategory;
                }
            } catch (ObjectPropertyException | ArgumentException | SystemException $exception) {
                return null;
            }
            
            foreach ($categories as $categoryKey => $category) {
                $xmlCategory           = new XmlCategory();
                $xmlCategory->id       = $category->get('ID');
                $xmlCategory->name     = $category->get('NAME');
                $xmlCategory->parentId = $category->get('IBLOCK_SECTION_ID');
                
                if ($category->get('PICTURE') !== null) {
                    $xmlCategory->picture = $this->setup->defaultServerName. CFile::GetPath($category->get('PICTURE'));
                }
                
                $xmlCategories[$categoryKey]   = $xmlCategory;
            }
        }
        
        return $xmlCategories;
    }
    
    /**
     * @return OfferParam[]
     */
    private function getOfferParams(): array
    {
        $offerParams = [];
        
        foreach ($offerParams as $key => $offerParam) {
            $offerParam        = new OfferParam();
            $offerParam->name  = 1;
            $offerParam->code  = 12;
            $offerParam->value = "188-16-xx";
            $offerParams[$key] = $offerParam;
        }
        
        return $offerParams;
    }
    
    /**
     * запрашивает свойства офферов из БД и записывает их в xml
     */
    public function writeOffersHandler(): void
    {
        foreach ($this->setup->iblocksForExport as $iblockId) {
            [$names, $cIblockResult] = $this->getOfferList($iblockId);
            $count = 0;
            
            while ($result = $cIblockResult->Fetch()) {
                $offers[] = $this->getOffer($result, $names);
                
                if ($count === self::OFFERS_PART) {
                    $this->icmlWriter->writeOffers($offers);
                    
                    $offers = [];
                    $count = 0;
    
                    IcmlLogger::writeToToLog(
                        $count
                        . " product(s) has been loaded from " . $iblockId . " IB (memory usage: " . memory_get_usage() . ")",
                        self::INFO
                    );
                    
                    continue;
                }
                
                $count++;
            }
            
            if (count($offers)>0) {
                $this->icmlWriter->writeOffers($offers);
            }
        }
    }
    
    /**
     * @param int   $result
     * @param array $names
     * @return \Intaro\RetailCrm\Model\Bitrix\Xml\XmlOffer
     */
    private function getOffer(int $result, array $names): XmlOffer
    {
        
        $xmlOffer              = new XmlOffer();
        $xmlOffer->id          = 1;
        $xmlOffer->productId   = 12;
        $xmlOffer->quantity    = 80;
        $xmlOffer->picture     = "sdfsdf";
        $xmlOffer->url         = "sdfsdf";
        $xmlOffer->price       = 12.00;
        $xmlOffer->categoryId  = 12;
        $xmlOffer->name        = "";
        $xmlOffer->xmlId       = 345;
        $xmlOffer->productName = "12.00";
        $xmlOffer->params      = $this->getOfferParams();
        $xmlOffer->vendor      = "12.00";
        $xmlOffer->unitCode    = "12.00";
        $xmlOffer->vatRate     = "12.00";
        
        return $xmlOffer;
    }
    
    /**
     * @param array $userProps
     * @return mixed
     */
    private function prepareQuery(array  $userProps)
    {
        $catalogFields = ['catalog_length', 'catalog_length', 'catalog_width', 'catalog_height', 'catalog_weight'];
        
        foreach ($userProps as $key => $name) {
            if (in_array($name, $catalogFields, true)) {
                $userProps[$key] = strtoupper($userProps[$key]);
            } else {
                $userProps[$key] = 'PROPERTY_' . $userProps[$key];
            }
        }
        
        $mainProps = ['ID', 'CATALOG_QUANTITY', 'NAME'];
        
        $allFields = array_merge($userProps, $mainProps);
        
        return $allFields;
    }
}
