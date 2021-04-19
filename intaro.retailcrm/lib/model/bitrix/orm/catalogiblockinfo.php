<?php


namespace Intaro\RetailCrm\Model\Bitrix\Orm;

/**
 * Class CatalogIblockInfo
 * @package Intaro\RetailCrm\Model\Bitrix\Orm
 */
class CatalogIblockInfo
{
    /**
     * ID инфоблока торговых предложений
     *
     * @var int
     */
    public $skuIblockId;
    
    /**
     * ID инфоблока товаров
     *
     * @var int
     */
    public $productIblockId;
    
    /**
     * ID свойства привязки торговых предложений к товарам
     *
     * @var int
     */
    public $skuPropertyId;
}
