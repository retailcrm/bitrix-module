<?php

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Diag\Debug;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Intaro\RetailCrm\Model\Bitrix\Orm\IblockCatalogTable;
use Intaro\RetailCrm\Model\Bitrix\Xml\OfferParam;
use Intaro\RetailCrm\Model\Bitrix\Xml\XmlCategory;
use Intaro\RetailCrm\Model\Bitrix\Xml\XmlData;
use Intaro\RetailCrm\Model\Bitrix\Xml\XmlOffer;

/**
 * Class RetailCrmXml
 */
class RetailCrmlXml
{
    private const INFO     = 'INFO';
    private const XML_PATH    = '/bitrix/catalog_export/retailcrm.xml';
    private const OFFERS_PART = 50;
    /**
     * @var \XMLWriter
     */
    private $writer;
    
    public function generateXml(): void
    {
        $data = $this->getXmlData();
        
        $this->writeToXml($data);
    }
    
    /**
     * @return \Intaro\RetailCrm\Model\Bitrix\Xml\XmlData
     */
    private function getXmlData(): XmlData
    {
        $this->writeToToLog(Date("Y:m:d H:i:s") . ': Start loading data for XML', self::INFO);
        
        $xmlData             = new XmlData();
        $xmlData->shopName   = 'Современная';
        $xmlData->company    = 'Современная';
        $xmlData->categories = $this->getCategories();
        $xmlData->offers     = $this->getOffers();
        
        $this->writeToToLog(Date("Y:m:d H:i:s") . ': End loading data for XML', self::INFO);
        
        return $xmlData;
    }
    
    /**
     * @param \Intaro\RetailCrm\Model\Bitrix\Xml\XmlData $data
     */
    private function writeToXml(XmlData $data): void
    {
        $this->writeToToLog(Date("Y:m:d H:i:s") . ': Start generate XML body', self::INFO);
        
        $this->writer = $writer = new XMLWriter();
        $writer->openURI($_SERVER["DOCUMENT_ROOT"] . self::XML_PATH);
        $writer->setIndent(true);
        
        $writer->startElement('yml_catalog');
        $this->writeSimpleAttribute('date', Date("Y-m-d H:i:s"));
            $writer->startElement('shop');
                $this->writeSimpleElement('name', $data->shopName);
                $this->writeSimpleElement('company', $data->company);
                
                $writer->startElement('categories');
                
                /** @var XmlCategory $category */
                foreach ($data->categories as $key => $category) {
                    $this->writeCategory($category);
                }
                
                $writer->endElement();
                $writer->flush();
                
                $writer->startElement('offers');
                
                /** @var XmlOffer $offer */
                foreach ($data->offers as $key => $offer) {
                    $this->writeOffer($offer);
                    
                    if (
                        count($data->offers) === $key+1
                        || is_int(count($data->offers)/self::OFFERS_PART)
                    ) {
                        $writer->flush();
                    }
                }
                
                $writer->endElement();
            $writer->endElement();
        $writer->endElement();
        $writer->flush(); //TODO будут проблемы с записью - можно попробовать заменить на file_put_contents($fileXml, $xml->flush(true), FILE_APPEND);
        $this->writeToToLog(Date("Y:m:d H:i:s") . ': End generate XML body', self::INFO);
    }
    
    /**
     * @param string $msg
     * @param string $level
     */
    private function writeToToLog(string $msg, string $level): void
    {
        Debug::writeToFile($msg, $level, '/bitrix/catalog_export/i_crm_load_log.txt');
    }
    
    /**
     * @return XmlCategory[]| null
     */
    private function getCategories(): ?array
    {
        try {
            $categories = IblockCatalogTable::query()
                ->addSelect('SECTION')
                ->where('PRODUCT_IBLOCK_ID', 0)
                ->fetchCollection();
        } catch (ObjectPropertyException | ArgumentException | SystemException $exception) {
            return null;
        }
        
        $xmlCategories = [];
        
        foreach ($categories as $key => $category) {
            $xmlCategory           = new XmlCategory();
            $xmlCategory->id       = 1;
            $xmlCategory->name     = $category->get('SECTION.NAME');
            $xmlCategory->parentId = $category->get('SECTION.IBLOCK_SECTION_ID');
            $xmlCategory->picture  = "sdfsdf";
            $xmlCategories[$key]   = $xmlCategory;
        }
        
        return $xmlCategories;
    }
    
    /**
     * @return XmlOffer[]
     */
    private function getOffers(): array
    {
        $xmlOffers = [];
        
        foreach ($offers as $key => $offer) {
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
            $xmlOffers[$key]       = $xmlOffer;
        }
        
        return $xmlOffers;
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
     * @param \Intaro\RetailCrm\Model\Bitrix\Xml\XmlOffer $offer
     */
    private function writeOffer(XmlOffer $offer): void
    {
        $this->writer->startElement('offer');
            $this->writeSimpleAttribute('id', $offer->id);
            $this->writeSimpleAttribute('productId', $offer->productId);
            $this->writeSimpleAttribute('quantity', $offer->quantity);
            
            $this->writeSimpleElement('picture', $offer->picture);
            $this->writeSimpleElement('url', $offer->url);
            $this->writeSimpleElement('price', $offer->price);
            $this->writeSimpleElement('categoryId', $offer->categoryId);
            $this->writeSimpleElement('name', $offer->name);
            $this->writeSimpleElement('xmlId', $offer->xmlId);
            $this->writeSimpleElement('productName', $offer->productName);
            $this->writeSimpleElement('vendor', $offer->vendor);
            $this->writeSimpleElement('vatRate', $offer->vatRate);
            $this->writer->startElement('unit');
            $this->writeSimpleAttribute('code', $offer->unitCode);
            $this->writer->endElement();
            
            /** @var OfferParam $param */
            foreach ($offer->params as $key => $param) {
                $this->writeParam($param);
            }
    
        $this->writer->endElement();
    }
    
    /**
     * @param \Intaro\RetailCrm\Model\Bitrix\Xml\XmlCategory $category
     */
    private function writeCategory(XmlCategory $category): void
    {
        $this->writer->startElement('category');
        $this->writeSimpleAttribute('id', $category->id);
        $this->writeSimpleElement('name', $category->name);
        $this->writeSimpleElement('picture', $category->picture);
        $this->writer->endElement();
    }
    
    /**
     * @param \Intaro\RetailCrm\Model\Bitrix\Xml\OfferParam $param
     */
    private function writeParam(OfferParam $param): void
    {
        $this->writer->startElement('param');
        $this->writeSimpleAttribute('name', $param->name);
        $this->writeSimpleAttribute('code', $param->code);
        $this->writer->text($param->value);
        $this->writer->endElement();
    }
    
    /**
     * @param string     $name
     * @param            $value
     */
    private function writeSimpleElement(string $name, $value): void
    {
        $this->writer->startElement($name);
        $this->writer->text($value);
        $this->writer->endElement();
    }
    
    /**
     * @param string     $name
     * @param            $value
     */
    private function writeSimpleAttribute(string $name, $value): void
    {
        $this->writer->startAttribute($name);
        $this->writer->text($value);
        $this->writer->endAttribute();
    }
}
