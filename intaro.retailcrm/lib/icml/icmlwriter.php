<?php

namespace Intaro\RetailCrm\Icml;

use Intaro\RetailCrm\Model\Bitrix\Xml\OfferParam;
use Intaro\RetailCrm\Model\Bitrix\Xml\XmlCategory;
use Intaro\RetailCrm\Model\Bitrix\Xml\XmlData;
use Intaro\RetailCrm\Model\Bitrix\Xml\XmlOffer;
use XMLWriter;
use Bitrix\Catalog\ProductTable;

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
     * @var string
     */
    private $filePath;

    /** @var bool */
    private $loadServiceNonAvailable;

    /**
     * IcmlWriter constructor.
     *
     * @param string $filePath
     */
    public function __construct(string $filePath, bool $loadServiceNonAvailable)
    {
        $this->filePath = $filePath;
        $this->loadServiceNonAvailable = $loadServiceNonAvailable;

        $this->writer = new XMLWriter();
        $this->writer->openMemory();
        $this->writer->setIndent(false);
        $this->writer->startDocument('1.0', LANG_CHARSET);
    }

    /**
     * @param \Intaro\RetailCrm\Model\Bitrix\Xml\XmlData $data
     */
    public function writeToXmlTop(XmlData $data): void
    {
        $this->writer->startElement('yml_catalog');
        $this->writeSimpleAttribute('date', Date('Y-m-d H:i:s'));

        $this->writer->startElement('shop');
        $this->writeSimpleElement('name', $data->shopName);
        $this->writeSimpleElement('company', $data->company);
    }

    public function writeToXmlBottom(): void
    {
        $this->writer->endElement();
        $this->writer->endElement();
        file_put_contents($this->filePath, $this->writer->flush(true), FILE_APPEND);
        $this->writer->endDocument();
    }

    /**
     * @param \Intaro\RetailCrm\Model\Bitrix\Xml\XmlData $data
     */
    public function writeToXmlHeaderAndCategories(XmlData $data): void
    {
        $this->writer->startElement('categories');

        foreach ($data->categories as $key => $category) {
            $this->writeCategory($category);

            if (
                count($data->categories) === $key + 1
                || is_int(count($data->categories) / self::CATEGORY_PART)
            ) {
                file_put_contents($this->filePath, $this->writer->flush(true), FILE_APPEND);
            }
        }

        $this->writer->endElement();
        file_put_contents($this->filePath, $this->writer->flush(true), FILE_APPEND);
    }

    public function startOffersBlock(): void
    {
        $this->writer->startElement('offers');
    }

    public function endBlock(): void
    {
        $this->writer->endElement();
    }

    /**
     * @param XmlOffer[] $offers
     * @param bool $isNotActiveProduct
     */
    public function writeOffers(array $offers): void
    {
        foreach ($offers as $offer) {
            $this->writeOffer($offer);
        }

        file_put_contents($this->filePath, $this->writer->flush(true), FILE_APPEND);
    }

    /**
     * @param \Intaro\RetailCrm\Model\Bitrix\Xml\XmlOffer $offer
     * @param bool $isNotActiveProduct
     */
    private function writeOffer(XmlOffer $offer): void
    {
        $productType = $offer->productType === ProductTable::TYPE_SERVICE ? 'service' : 'product';

        if ($productType === 'service' && $offer->quantity === 0 && !$this->loadServiceNonAvailable) {
            return;
        }

        $activity = $offer->activity;

        $this->writer->startElement('offer');
        $this->writeSimpleAttribute('id', $offer->id);
        $this->writeSimpleAttribute('type', $productType);
        $this->writeSimpleAttribute('productId', $offer->productId);
        $this->writeSimpleAttribute('quantity', $offer->quantity);
        $this->writeSimpleElement('activity', $activity);

        if ($activity === 'N') {
            $this->writeSimpleElement('productActivity', 'N');
        }

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

        foreach ($offer->params as $param) {
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
     *
     * @return string
     */
    protected function prepareValue($text): string
    {
        global $APPLICATION;

        return strip_tags($APPLICATION->ConvertCharset($text, 'utf-8', 'utf-8'));
    }

    /**
     * @param \Intaro\RetailCrm\Model\Bitrix\Xml\XmlCategory $category
     */
    private function writeCategory(XmlCategory $category): void
    {
        $this->writer->startElement('category');
        $this->writeSimpleAttribute('id', $category->id);
        $this->writeParentId($category->parentId);
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

    /**
     * @param string $parentId
     */
    private function writeParentId(string $parentId)
    {
        if ($parentId > 0) {
            $this->writeSimpleAttribute('parentId', $parentId);
        }
    }
}
