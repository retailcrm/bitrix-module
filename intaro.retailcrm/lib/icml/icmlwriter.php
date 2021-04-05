<?php

namespace Intaro\RetailCrm\Icml;

use Intaro\RetailCrm\Model\Bitrix\Xml\OfferParam;
use Intaro\RetailCrm\Model\Bitrix\Xml\XmlCategory;
use Intaro\RetailCrm\Model\Bitrix\Xml\XmlData;
use Intaro\RetailCrm\Model\Bitrix\Xml\XmlOffer;
use XMLWriter;

/**
 * Отвечает за запись данных каталога в файл
 *
 * Class IcmlWriter
 * @package Intaro\RetailCrm\Icml
 */
class IcmlWriter
{
    public const INFO          = 'INFO';
    public const CATEGORY_PART = 1000;
    
    /**
     * @var \XMLWriter
     */
    private $writer;
    
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
                count($data->categories) === $key + 1
                || is_int(count($data->categories) / self::CATEGORY_PART)
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
        
        foreach ($offer->categoryIds as $categoryId) {
            $this->writeSimpleElement('categoryId', $categoryId);
        }
        
        if (!empty($offer->picture)) {
            $this->writeSimpleElement('picture', $offer->picture);
        }
        
        if (!empty($offer->unitCode->code)) {
            $this->writer->startElement('unit');
            $this->writeSimpleAttribute('code', $offer->unitCode->code);
            $this->writeSimpleAttribute('name', $offer->unitCode->name);
            $this->writeSimpleAttribute('sym', $offer->unitCode->sym);
            $this->writer->endElement();
        }
        
        foreach ($offer->params as $key => $param) {
            $this->writeParam($param);
        }
        
        $this->writeSimpleElement('url', $offer->url);
        $this->writeSimpleElement('price', $offer->price);
        $this->writeSimpleElement('name', $offer->name);
        $this->writeSimpleElement('productName', $offer->productName);
        $this->writeSimpleElement('xmlId', $offer->xmlId);
        $this->writeOptionalSimpleElement('vendor', $offer->vendor);
        $this->writeOptionalSimpleElement('barcode', $offer->barcode);
        $this->writeOptionalSimpleElement('vatRate', $offer->vatRate);
        $this->writeOptionalSimpleElement('weight', $offer->weight);
        $this->writeOptionalSimpleElement('dimensions', $offer->dimensions);
        $this->writeOptionalSimpleElement('purchasePrice', $offer->purchasePrice);
        $this->writer->endElement();
    }
    
    /**
     * Создает ноду, если значение не пустое
     *
     * @param string $name
     * @param        $value
     */
    private function writeOptionalSimpleElement(string $name, $value): void
    {
        if (!empty($value)) {
            $this->writeSimpleElement($name, $value);
        }
    }
    
    /**
     * @param string     $name
     * @param            $value
     */
    private function writeSimpleElement(string $name, $value): void
    {
        $this->writer->startElement($name);
        $this->writer->text($this->prepareValue($value));
        $this->writer->endElement();
    }
    
    /**
     * @param string     $name
     * @param            $value
     */
    private function writeSimpleAttribute(string $name, $value): void
    {
        $this->writer->startAttribute($name);
        $this->writer->text($this->prepareValue($value));
        $this->writer->endAttribute();
    }
    
    /**
     * @param $text
     * @return string|string[]
     */
    protected function prepareValue($text)
    {
        return str_replace('&', '&#x26;', strip_tags($text));
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
        $this->writer->text($this->prepareValue($param->value));
        $this->writer->endElement();
    }
}
