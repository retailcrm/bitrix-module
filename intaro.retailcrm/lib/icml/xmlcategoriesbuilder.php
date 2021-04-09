<?php

namespace Intaro\RetailCrm\Icml;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ORM\Objectify\Collection;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\SystemException;
use Intaro\RetailCrm\Model\Bitrix\Xml\XmlCategory;
use Intaro\RetailCrm\Repository\CatalogRepository;
use Intaro\RetailCrm\Repository\FileRepository;
use Intaro\RetailCrm\Repository\SiteRepository;

/**
 * Отвечает за создание XmlCategory
 *
 * Class CategoriesBuilder
 * @package Intaro\RetailCrm\Icml
 */
class XmlCategoriesBuilder
{
    /**
     * @param \Bitrix\Main\ORM\Objectify\EntityObject $category
     * @param int|null                                $categoryId
     * @return \Intaro\RetailCrm\Model\Bitrix\Xml\XmlCategory|null
     */
    public function getXmlCategory(EntityObject $category, string $picture, int $categoryId = null): ?XmlCategory
    {
        try {
            $xmlCategory           = new XmlCategory();
            $xmlCategory->id       = $categoryId ?? $category->get('ID');
            $xmlCategory->name     = $category->get('NAME');
            $xmlCategory->parentId = $categoryId ? 0 : $category->get('IBLOCK_SECTION_ID');
            $xmlCategory->picture  = $picture;
        } catch (ArgumentException | SystemException $exception) {
            return null;
        }
        
        return $xmlCategory;
    }
}
