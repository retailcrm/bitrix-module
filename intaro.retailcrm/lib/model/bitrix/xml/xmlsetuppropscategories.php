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
 * Class XmlSetupProps
 * @package Intaro\RetailCrm\Model\Bitrix\Xml
 */
class XmlSetupPropsCategories
{
    /**
     * XmlSetupPropsCategories constructor.
     * @param \Intaro\RetailCrm\Model\Bitrix\Xml\XmlSetupProps $products
     * @param \Intaro\RetailCrm\Model\Bitrix\Xml\XmlSetupProps $sku
     */
    public function __construct(XmlSetupProps $products, XmlSetupProps $sku)
    {
        $this->products = $products;
        $this->sku      = $sku;
    }
    
    /**
     * Синхронизируемые свойства товаров
     *
     * @var XmlSetupProps
     */
    public $products;
    
    /**
     * Синхронизируемые свойства торговых предложений
     *
     * @var XmlSetupProps
     */
    public $sku;
    
    /**
     * Синхронизируемые свойства торговых предложений, находящиеся в HL блоках
     *
     * массив с названиями HL блоков, элементы которого содержат,
     * синхронизируемые св-ва
     *
     * @var array[][]
     */
    public $highloadblockSku;
    
    /**
     * Синхрозируемые свойства товаров, находящиеся в HL блоках
     *
     * массив с названиями HL блоков, элементы которого содержат,
     * синхронизируемые св-ва
     *
     * @var array[][]
     */
    public $highloadblockProduct;
}
