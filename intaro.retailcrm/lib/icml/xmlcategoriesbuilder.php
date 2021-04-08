<?php

namespace Intaro\RetailCrm\Icml;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ORM\Objectify\Collection;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\SystemException;
use Intaro\RetailCrm\Icml\Utils\IcmlUtils;
use Intaro\RetailCrm\Model\Bitrix\Xml\XmlCategory;
use Intaro\RetailCrm\Model\Bitrix\Xml\XmlSetup;
use Intaro\RetailCrm\Repository\CatalogRepository;
use Intaro\RetailCrm\Repository\FileRepository;

/**
 * Отвечает за создание XmlCategory
 *
 * Class CategoriesBuilder
 * @package Intaro\RetailCrm\Icml
 */
class XmlCategoriesBuilder
{
    private const MILLION     = 1000000;
    
    /**
     * @var \Intaro\RetailCrm\Repository\CatalogRepository
     */
    private $catalogRepository;
    
    /**
     * @var \Intaro\RetailCrm\Repository\FileRepository
     */
    private $fileRepository;
    /**
     * @var \Intaro\RetailCrm\Model\Bitrix\Xml\XmlSetup
     */
    private $setup;
    
    /**
     * CategoriesBuilder constructor.
     * @param \Intaro\RetailCrm\Model\Bitrix\Xml\XmlSetup $setup
     */
    public function __construct(XmlSetup $setup)
    {
        $this->setup             = $setup;
        $this->catalogRepository = new CatalogRepository();
        $this->fileRepository    = new FileRepository($this->setup->defaultServerName);
    }
    
    /**
     * Получение категорий каталога
     *
     * @return XmlCategory[]|null
     */
    public function getCategories(): ?array
    {
        $xmlCategories = [];
        
        foreach ($this->setup->iblocksForExport as $iblockKey => $iblockId) {
            $categories = $this->catalogRepository->getCategoriesByIblockId($iblockId);
    
            if ($categories === null) {
                $categoryId  = self::MILLION + $iblockId;
                $xmlCategory = $this->makeXmlCategoryFromIblock($iblockId, $categoryId);
                
                if (!$xmlCategory) {
                    continue;
                }
                
                $xmlCategories[$categoryId] = $xmlCategory;
            }
    
            $xmlCategories = array_merge($xmlCategories, $this->getXmlCategories($categories));
        }
        
        return $xmlCategories;
    }
    
    /**
     * Возвращает коллекцию категорий
     *
     * @param \Bitrix\Main\ORM\Objectify\Collection $categories
     * @return XmlCategory[]
     */
    private function getXmlCategories(Collection $categories): array
    {
        $xmlCategories = [];
        
        foreach ($categories as $categoryKey => $category) {
            $xmlCategory = $this->getXmlCategory($category);
            
            if (!$xmlCategory) {
                continue;
            }
            
            $xmlCategories[$categoryKey] = $this->getXmlCategory($category);
        }
        
        return $xmlCategories;
    }
    
    /**
     * @param \Bitrix\Main\ORM\Objectify\EntityObject $category
     * @param int|null                                $categoryId
     * @return \Intaro\RetailCrm\Model\Bitrix\Xml\XmlCategory|null
     */
    private function getXmlCategory(EntityObject $category, int $categoryId = null): ?XmlCategory
    {
        try {
            $xmlCategory           = new XmlCategory();
            $xmlCategory->id       = $categoryId ?? $category->get('ID');
            $xmlCategory->name     = $category->get('NAME');
            $xmlCategory->parentId = $categoryId ? 0 : $category->get('IBLOCK_SECTION_ID');
            $xmlCategory->picture  = $this->fileRepository->getImageUrl($category->get('PICTURE'));
        } catch (ArgumentException | SystemException $exception){
            return null;
        }
        
        return $xmlCategory;
    }
    
    /**
     * Создает XmlCategory из инфоблока
     *
     * @param int $iblockId
     * @param int $categoryId
     * @return \Intaro\RetailCrm\Model\Bitrix\Xml\XmlCategory|null
     */
    private function makeXmlCategoryFromIblock(int $iblockId, int $categoryId): ?XmlCategory
    {
        $iblock = $this->catalogRepository->getIblockById($iblockId);
    
        if ($iblock === null) {
            return null;
        }
    
        return $this->getXmlCategory($iblock, $categoryId);
    }
}
