<?php

namespace Intaro\RetailCrm\Repository;

use CIBlockElement;

class CategoryRepository
{
    /**
     * Получение категорий, к которым относится товар
     *
     * @param $offerId
     * @return array
     */
    public function getProductCategoriesIds(int $offerId): array
    {
        $query = CIBlockElement::GetElementGroups($offerId, false, ['ID']);
        $ids   = [];
        
        while ($category = $query->GetNext()) {
            $ids[] = $category['ID'];
        }
        
        return $ids;
    }
}
