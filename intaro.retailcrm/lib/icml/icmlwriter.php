<?php

namespace Intaro\RetailCrm\Icml;

use Bitrix\Main\Diag\Debug;
use Intaro\RetailCrm\Icml\Utils\IcmlLogger;
use Intaro\RetailCrm\Model\Bitrix\Xml\OfferParam;
use Intaro\RetailCrm\Model\Bitrix\Xml\XmlCategory;
use Intaro\RetailCrm\Model\Bitrix\Xml\XmlData;
use Intaro\RetailCrm\Model\Bitrix\Xml\XmlOffer;
use Intaro\RetailCrm\Model\Bitrix\Xml\XmlSetup;
use XMLWriter;

class IcmlWriter
{
    public const INFO          = 'INFO';
    public const CATEGORY_PART = 1000;
    
    /**
     * @var \XMLWriter
     */
    private $writer;
    
    /**
     * @var \CAllMain|\CMain
     */
    private $application;
    
    public function __construct()
    {
        global $APPLICATION;
        $this->application = $APPLICATION;
    }
    
    /**
     * @param \Intaro\RetailCrm\Model\Bitrix\Xml\XmlData $data
     */
    public function writeToXmlHeaderAndCategories(XmlData $data): void
    {
        $this->writer = $writer = new XMLWriter();
        $writer->openURI($_SERVER['DOCUMENT_ROOT'] . $data->filePath);
        $writer->setIndent(true);
        
        $writer->startElement('yml_catalog');
        $this->writeSimpleAttribute('date', Date('Y-m-d H:i:s'));
        $writer->startElement('shop');
        $this->writeSimpleElement('name', $data->shopName);
        $this->writeSimpleElement('company', $data->company);
        
        $writer->startElement('categories');
        
        foreach ($data->categories as $key => $category) {
            $this->writeCategory($category);
            
            if (
                count($data->categories) === $key+1
                || is_int(count($data->categories)/self::CATEGORY_PART)
            ) {
                $writer->flush();
            }
        }
        
        $writer->endElement();
        $writer->flush();
        
        $writer->startElement('offers');
    }
    
    /**
     * @param XmlOffer[] $offers
     */
    public function writeOffers(array $offers): void
    {
        foreach ($offers as $offer) {
            $this->writeOffer($offer);
        }
        
        $this->writer->flush();
    }
    
    public function writeToXmlBottom(): void
    {
        $this->writer->endElement();
        $this->writer->endElement();
        $this->writer->endElement();
        $this->writer->flush();
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
        
        if (!empty($offer->barcode)) {
            $this->writeSimpleElement('barcode', $offer->barcode);
        }
    
        if (!empty($offer->vatRate)) {
            $this->writeSimpleElement('vatRate', $offer->vatRate);
        }
    
        if ($offer->purchasePrice !== null) {
            $this->writeSimpleElement('purchasePrice', $offer->vatRate);
        }
        
        $this->writer->startElement('unit');
        $this->writeSimpleAttribute('code', $offer->unitCode);
        $this->writer->endElement();
    
        foreach ($offer->params as $key => $param) {
            $this->writeParam($param);
        }
        
        $this->writer->endElement();
    }
    
    /**
     * @param string     $name
     * @param            $value
     */
    private function writeSimpleElement(string $name, $value): void
    {
        $this->writer->startElement($name);
        $this->writer->text($this->PrepareValue($value));
        $this->writer->endElement();
    }
    
    /**
     * @param string     $name
     * @param            $value
     */
    private function writeSimpleAttribute(string $name, $value): void
    {
        $this->writer->startAttribute($name);
        $this->writer->text($this->PrepareValue($value));
        $this->writer->endAttribute();
    }
    
    /**
     * @param $text
     * @return string|string[]
     */
    protected function PrepareValue($text)
    {
        $newText = $this->application->ConvertCharset($text, 'utf-8', 'utf-8');
        $newText = strip_tags($newText);
        $newText = str_replace("&", "&#x26;", $newText);
        
        return $newText;
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
        $this->writer->text($this->PrepareValue($param->value));
        $this->writer->endElement();
    }
}