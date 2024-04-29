<?php

/**
 * @category Integration
 * @package  Intaro\RetailCrm\Model\Bitrix\Xml
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
     * XmlSetup constructor.
     * @param \Intaro\RetailCrm\Model\Bitrix\Xml\XmlSetupPropsCategories $xmlProps
     */
    public function __construct(XmlSetupPropsCategories $xmlProps)
    {
        $this->properties = $xmlProps;
    }
    
    /**
     * @var int
     */
    public $profileId;
    
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
     * @var null|int
     */
    public $maxOffersValue;
    
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

    /**
     *
     * @var bool
     */
    public $loadNonActivity;
}
