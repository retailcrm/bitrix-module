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
 * Class XmlSetup
 * @package Intaro\RetailCrm\Model\Bitrix\Xml
 */
class XmlSetup
{
    /**
     *
     * @var int
     */
    public $profileID;
    
    /**
     * id инфоблоков, которые подлежат экспорту - IBLOCK_EXPORT
     *
     * @var array
     */
    public $iblocksForExport;
    
    
    /**
     * Путь, по которому сохраняется xml - SETUP_FILE_NAME
     *
     * @var string
     */
    public $filePath;
    
    /**
     * синхронизируемые свойства
     *
     * @var \Intaro\RetailCrm\Model\Bitrix\Xml\XmlSetupPropsCategories
     */
    public $properties;
    
    /**
     * максимальное количество торговых предложений у товара - MAX_OFFERS_VALUE
     *
     * @var int
     */
    public $maxOffersValue;
    
    /**
     * адрес сайта
     *
     * @var string
     */
    public $defaultServerName;
    
    /**
     * выгружать ли закупочную цену
     *
     * @var bool
     */
    public $loadPurchasePrice;
    
    /**
     * @var int|null
     */
    public $basePriceId;
    
}