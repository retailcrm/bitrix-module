<?php


namespace Intaro\RetailCrm\Model\Bitrix\Xml;

/**
 * Class XmlSetupProps
 * @package Intaro\RetailCrm\Model\Bitrix\Xml
 */
class XmlSetupPropsCategories
{
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