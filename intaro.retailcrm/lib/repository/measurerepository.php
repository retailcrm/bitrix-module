<?php

/**
 * @category Integration
 * @package  Intaro\RetailCrm\Repository
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

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
            return [];
        }

        foreach ($resMeasures as $resMeasure) {
            $measures[$resMeasure['ID']] = $resMeasure;
        }

        return $measures;
    }
}
