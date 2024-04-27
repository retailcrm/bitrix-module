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
 * единица измерения для товара, элемент не является обязательным в icml
 *
 * Class Unit
 * @package Intaro\RetailCrm\Model\Bitrix\Xml
 */
class Unit
{
    /**
     * @var string
     */
    public $name;
    
    /**
     * @var string
     */
    public $code;
    
    /**
     * единица измерения товара
     *
     * @var string
     */
    public $sym;
    
    /**
     * @param \Intaro\RetailCrm\Model\Bitrix\Xml\Unit|null $unitCode
     * @return \Intaro\RetailCrm\Model\Bitrix\Xml\Unit
     */
    public function merge(?Unit $unitCode): Unit
    {
        if ($this->code === null && $unitCode !== null) {
            return $unitCode;
        }
        
        return $this;
    }
}
