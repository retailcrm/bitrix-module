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
 * Class SelectParams
 * @package Intaro\RetailCrm\Model\Bitrix\Xml
 */
class SelectParams
{
    /**
     * конфигурируемые свойства
     *
     * @var array
     */
    public $configurable;
    
    /**
     * обязательные свойства
     *
     * @var array
     */
    public $main;
    
    /**
     * номер запрашиваемой страницы
     *
     * @var int
     */
    public $pageNumber;

    /**
     * количество товаров на странице
     *
     * @var int
     */
    public $nPageSize;
    
    /**
     * id товара у торогового предложения, если запрашивается SKU
     *
     * @var int
     */
    public $parentId;
}
