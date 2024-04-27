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
 * Class XmlData
 * @package Intaro\RetailCrm\Model\Bitrix
 */
class XmlData
{
    /**
     * @var string
     */
    public $shopName;
    
    /**
     * @var string
     */
    public $company;
    
    /**
     * @var \Intaro\RetailCrm\Model\Bitrix\Xml\XmlCategory[]
     */
    public  $categories;
    
    /**
     * @var string
     */
    public $filePath;
}
