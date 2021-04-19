<?php

namespace Intaro\RetailCrm\Model\Bitrix\Orm;

use Bitrix\Main,
    Bitrix\Main\Localization\Loc;
use Bitrix\Main\Entity\BooleanField;
use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\Entity\ReferenceField;

Loc::loadMessages(__FILE__);

/**
 * Class IblockCatalogTable
 *
 * Fields:
 * <ul>
 * <li> IBLOCK_ID int mandatory
 * <li> YANDEX_EXPORT bool optional default 'N'
 * <li> SUBSCRIPTION bool optional default 'N'
 * <li> VAT_ID int optional
 * <li> PRODUCT_IBLOCK_ID int mandatory
 * <li> SKU_PROPERTY_ID int mandatory
 * <li> VAT reference to {@link \Bitrix\Catalog\CatalogVatTable}
 * <li> IBLOCK reference to {@link \Bitrix\Iblock\IblockTable}
 * <li> PRODUCT_IBLOCK reference to {@link \Bitrix\Iblock\IblockTable}
 * <li> SKU_PROPERTY reference to {@link \Bitrix\Iblock\IblockPropertyTable}
 * </ul>
 *
 * @package Bitrix\Catalog
 **/
class IblockCatalogTable extends Main\Entity\DataManager
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'b_catalog_iblock';
    }
    
    /**
     * Returns entity map definition.
     *
     * @return array|null
     */
    public static function getMap(): ?array
    {
        try {
            return [
                new IntegerField('IBLOCK_ID'),
                new BooleanField('YANDEX_EXPORT'),
                new BooleanField('SUBSCRIPTION'),
                new IntegerField('VAT_ID'),
                new IntegerField('PRODUCT_IBLOCK_ID'),
                new IntegerField('SKU_PROPERTY_ID'),
                new ReferenceField(
                    'VAT',
                    'Bitrix\Catalog\CatalogVat',
                    ['=this.VAT_ID' => 'ref.ID']
                ),
                new ReferenceField(
                    'IBLOCK',
                    'Bitrix\Iblock\Iblock',
                    ['=this.IBLOCK_ID' => 'ref.ID']
                ),
                new ReferenceField(
                    'PRODUCT_IBLOCK',
                    'Bitrix\Iblock\Iblock',
                    ['=this.PRODUCT_IBLOCK_ID' => 'ref.ID']
                ),
                new ReferenceField(
                    'SKU_PROPERTY',
                    'Bitrix\Iblock\IblockProperty',
                    ['=this.SKU_PROPERTY_ID' => 'ref.ID']
                ),
                new ReferenceField(
                    'SECTION',
                    'Bitrix\Iblock\IblockSection',
                    ['=this.IBLOCK_ID' => 'ref.IBLOCK_ID']
                ),
                new ReferenceField(
                    'OFFERS_IBLOCK',
                    'Bitrix\Iblock\IblockSection',
                    ['=this.IBLOCK_ID' => 'this.ID']
                ),
            ];
        } catch (Main\ArgumentException | Main\SystemException $e) {
            return null;
        }
    }
}
