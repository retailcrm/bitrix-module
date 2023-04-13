<?php
/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Bitrix
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
namespace Intaro\RetailCrm\Model\Bitrix\Xml;

/**
 * Class XmlOffer
 * @package Intaro\RetailCrm\Model\Bitrix\Xml
 */
class XmlOffer
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var int
     */
    public $productId;

    /**
     * @var int
     */
    public $quantity;

    /**
     * @var string
     */
    public $picture;

    /**
     * @var string
     */
    public $url;

    /**
     * @var float
     */
    public $price;

    /**
     * Категории, к которым относится товар
     *
     * @var array
     */
    public $categoryIds;

    /**
     * @var string
     */
    public $name;

    /**
     * @var int
     */
    public $xmlId;

    /**
     * @var string
     */
    public $productName;

    /**
     * @var OfferParam[]
     */
    public $params;

    /**
     * @var string
     */
    public $vendor;

    /**
     * @var Unit
     */
    public $unitCode;

    /**
     * ставка налога (НДС)
     *
     * @var string
     */
    public $vatRate;

    /**
     * штрих-код
     *
     * @var string
     */
    public $barcode;

    /**
     * Закупочная цена
     *
     * @var mixed|null
     */
    public $purchasePrice;

    /**
     * Вес товара
     *
     * @var int
     */
    public $weight;

    /**
     * Габариты товара
     *
     * @var string
     */
    public $dimensions;

    /**
     * Тип каталога
     * \Bitrix\Catalog\ProductTable::TYPE_PRODUCT - простой товар
     * \Bitrix\Catalog\ProductTable::TYPE_SKU – товар с торговыми предложениями
     * \Bitrix\Catalog\ProductTable::TYPE_OFFER – торговое предложение
     *
     * @var int
     */
    public $productType;

    /**
     * Активность товара/торгового предложения (N|Y)
     *
     * @var string
     */
    public $activity;

    /**
     * @param $productValue
     * @param $offerValue
     * @return mixed
     */
    public function mergeValues($productValue, $offerValue)
    {
        return empty($offerValue) ? $productValue : $offerValue;
    }
}
