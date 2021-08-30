<?php

namespace Intaro\RetailCrm\Repository;

use Bitrix\Catalog\MeasureTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;

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
    
        try {
            $resMeasures = MeasureTable::query()
                ->addSelect('ID')
                ->addSelect('MEASURE_TITLE')
                ->addSelect('SYMBOL_INTL')
                ->addSelect('SYMBOL')
                ->fetchAll();
        } catch (ObjectPropertyException | ArgumentException | SystemException $exception) {
            die('hui');
            return [];
        }
    
        foreach ($resMeasures as $resMeasure) {
            $measures[$resMeasure['ID']] = $resMeasure;
        }

        return $measures;
    }
}
