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
 *
 * @package Intaro\RetailCrm\Model\Bitrix\Xml
 */
class XmlSetupProps
{
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
