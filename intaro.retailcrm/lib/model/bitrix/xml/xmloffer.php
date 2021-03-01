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
     * @var int
     */
    public $categoryId;
    
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
     * @var string
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
}
