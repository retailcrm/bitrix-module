<?php

namespace Intaro\RetailCrm\Repository;

use CCatalogMeasure;

/**
 * Class MeasureRepository
 * @package Intaro\RetailCrm\Repository
 */
class MeasureRepository
{
    /**
     * Получает доступные в Битриксе единицы измерения для товаров
     *
     * @return array
     */
    public static function getMeasures(): array
    {
        $measures    = [];
        $resMeasure = CCatalogMeasure::getList();
        
        while ($measure = $resMeasure->Fetch()) {
            $measures[$measure['ID']] = $measure;
        }
        
        return $measures;
    }
}
