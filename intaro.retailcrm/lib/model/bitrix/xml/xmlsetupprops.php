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
 * Class XmlSetupProps
 *
 * @package Intaro\RetailCrm\Model\Bitrix\Xml
 */
class XmlSetupProps
{
    /**
     * XmlSetupProps constructor.
     * @param array      $names
     * @param array      $units
     * @param array|null $pictures
     */
    public function __construct(array $names, array $units, ?array $pictures)
    {
        $this->names = $names;
        $this->units = $units;
        $this->pictures = $pictures;
    }
    
    /**
     * названия свойств
     *
     * @var array
     */
    public $names;
    
    /**
     * меры измерения
     *
     * @var array
     */
    public $units;
    
    /**
     * свойства, из которых нужно брать картинки
     *
     * @var array
     */
    public $pictures;
}
