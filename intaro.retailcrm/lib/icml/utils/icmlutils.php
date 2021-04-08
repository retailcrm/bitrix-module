<?php

namespace Intaro\RetailCrm\Icml\Utils;

use Intaro\RetailCrm\Model\Bitrix\Xml\XmlSetupPropsCategories;

class IcmlUtils
{
    /**
     * Удаляет параметры с пустыми и нулевыми значениями
     *
     * @param array $params
     * @return array
     */
    public static function dropEmptyParams(array $params): array
    {
        return array_diff($params, ['', 0, '0']);
    }
    
    /**
     * Возвращает массив обычных свойств
     *
     * @param array $resultParams
     * @param array $configurableParams
     * @param array $productProps
     * @return array
     */
    public static function getSimpleParams(array $resultParams, array $configurableParams, array $productProps): array
    {
        foreach ($configurableParams as $key => $params) {
            if (isset($resultParams[$key])) {
                continue;
            }
            
            $codeWithValue = $params . '_VALUE';
            
            if (isset($productProps[$codeWithValue])) {
                $resultParams[$key] = $productProps[$codeWithValue];
            } elseif (isset($productProps[$params])) {
                $resultParams[$key] = $productProps[$params];
            }
        }
        
        return $resultParams;
    }
    
    /**
     * Возвращает закупочную цену, если она требуется настройками
     *
     * @param array  $product //результат GetList
     * @param bool   $isLoadPrice
     * @param string $purchasePriceNull
     * @return int|null
     */
    public static function getPurchasePrice(array $product, bool $isLoadPrice, string $purchasePriceNull): ?int
    {
        if ($isLoadPrice) {
            if ($product['CATALOG_PURCHASING_PRICE']) {
                return $product['CATALOG_PURCHASING_PRICE'];
            }
            
            if ($purchasePriceNull === 'Y') {
                return 0;
            }
        }

        return null;
    }
    
    /**
     * Разделяем вендора и остальные параметры
     *
     * @param array $resultParams
     * @return array
     */
    public static function extractVendorFromParams(array $resultParams): array
    {
        $vendor = null;
        
        if (isset($resultParams['manufacturer'])) {
            $vendor = $resultParams['manufacturer'];
            
            unset($resultParams['manufacturer']);
        }
        
        return [$resultParams, $vendor];
    }
    
    /**
     * Преобразует вес товара в килограммы для ноды weight
     *
     * @param \Intaro\RetailCrm\Model\Bitrix\Xml\XmlSetupPropsCategories $xmlSetupPropsCategories
     * @param array                                                      $resultParams
     * @param int                                                        $iblockId
     * @return array
     */
    public static function extractWeightFromParams(
        XmlSetupPropsCategories $xmlSetupPropsCategories,
        array $resultParams,
        int $iblockId
    ): array {
        $factors = [
            'mg' => 0.000001,
            'g'  => 0.001,
            'kg' => 1,
        ];
        $unit = '';
        
        if (!empty($xmlSetupPropsCategories->products->names[$iblockId]['weight'])) {
            $unit = $xmlSetupPropsCategories->products->units[$iblockId]['weight'];
        } elseif (!empty($xmlSetupPropsCategories->sku->names[$iblockId]['weight'])) {
            $unit = $xmlSetupPropsCategories->sku->units[$iblockId]['weight'];
        }
        
        if (isset($resultParams['weight'], $factors[$unit])) {
            $weight = $resultParams['weight'] * $factors[$unit];
        } else {
            $weight = '';
        }
        
        if (isset($resultParams['weight'])) {
            unset($resultParams['weight']);
        }
        
        return [$resultParams, $weight];
    }
    
    /**
     * @param array $arrayOne
     * @param array $arrayTwo
     * @return array
     */
    public static function arrayMerge(array $arrayOne, array $arrayTwo): array
    {
        return array_merge($arrayOne, $arrayTwo);
    }
    
    /**
     * Получение данных для ноды dimensions
     *
     * Данные должны быть переведены в сантиметры
     * и представлены в формате Длина/Ширина/Высота
     *
     * @param \Intaro\RetailCrm\Model\Bitrix\Xml\XmlSetupPropsCategories $xmlSetupPropsCategories
     * @param array                                                      $resultParams
     * @param int                                                        $iblockId
     * @return array
     */
    public static function extractDimensionsFromParams(
        XmlSetupPropsCategories $xmlSetupPropsCategories,
        array $resultParams,
        int $iblockId
    ): array {
        $dimensionsParams = ['length', 'width', 'height'];
        $dimensions       = '';
        $factors          = [
            'mm' => 0.1,
            'cm' => 1,
            'm'  => 100,
        ];
        
        foreach ($dimensionsParams as $key => $param) {
            $unit = '';
            
            if (!empty($xmlSetupPropsCategories->products->names[$iblockId][$param])) {
                $unit = $xmlSetupPropsCategories->products->units[$iblockId][$param];
            } elseif (!empty($xmlSetupPropsCategories->sku->names[$iblockId][$param])) {
                $unit = $xmlSetupPropsCategories->sku->units[$iblockId][$param];
            }
            
            if (isset($factors[$unit], $resultParams[$param])) {
                $dimensions .= $resultParams[$param] * $factors[$unit];
            } else {
                $dimensions .= '0';
            }
            
            if (count($dimensionsParams) > $key + 1) {
                $dimensions .= '/';
            }
            
            if (isset($resultParams[$param])) {
                unset($resultParams[$param]);
            }
        }
        
        return [$resultParams, $dimensions === '0/0/0' ? '' : $dimensions];
    }
}
