<?php

namespace Intaro\RetailCrm\Icml;

/**
 * Отвечает за управление настройками выгрузки
 *
 * Class SettingsService
 *
 * @package Intaro\RetailCrm\Icml
 */
class SettingsService
{
    /**
     * @var array
     */
    private $arOldSetupVars;

    /**
     * SettingsService constructor.
     *
     * @param array $arOldSetupVars
     */
    public function __construct(array $arOldSetupVars)
    {
        $this->arOldSetupVars = $arOldSetupVars;
    }

    /**
     * @param array  $properties
     * @param string $propName
     */
    public function setProperties(array &$properties, string $propName)
    {
        foreach ($this->arOldSetupVars['IBLOCK_PROPERTY_SKU' . '_' . $propName] as $iblock => $val) {
            $properties[$iblock][$propName] = $val;
        }
    }

    /**
     * @return mixed|string
     */
    public function getSingleSetting(string $settingName)
    {
        if (isset($this->arOldSetupVars[$settingName])) {
            return $this->arOldSetupVars[$settingName];
        }

        return null;
    }

    /**
     * @return string[]
     */
    public function getIblockPropsPreset(): array
    {
        return [
            "article" => "article",
            "manufacturer" => "manufacturer",
            "color" => "color",
            "size" => "size",
            "weight" => "weight",
            "length" => "length",
            "width" => "width",
            "height" => "height",
            "picture" => "picture",
        ];
    }

    /**
     * @return array
     */
    public function getHintProps(): array
    {
        return [
            'article' => ['ARTICLE', 'ART', 'ARTNUMBER', 'ARTICUL', 'ARTIKUL'],
            'manufacturer' => ['MANUFACTURER', 'PROISVODITEL', 'PROISVOD', 'PROISV'],
            'color' => ['COLOR', 'CVET'],
            'size' => ['SIZE', 'RAZMER'],
            'weight' => ['WEIGHT', 'VES', 'VEC'],
            'length' => ["LENGTH", 'DLINA'],
            'width' => ['WIDTH', 'SHIRINA'],
            'height' => ['HEIGHT', 'VISOTA'],
            'picture' => ['PICTURE', 'PICTURE'],
        ];
    }

    /**
     * @return array
     */
    public function getIblockPropsNames(): array
    {
        return  [
            "article" => GetMessage("PROPERTY_ARTICLE_HEADER_NAME"),
            "manufacturer" => GetMessage("PROPERTY_MANUFACTURER_HEADER_NAME"),
            "color" => GetMessage("PROPERTY_COLOR_HEADER_NAME"),
            "size" => GetMessage("PROPERTY_SIZE_HEADER_NAME"),
            "weight" => GetMessage("PROPERTY_WEIGHT_HEADER_NAME"),
            "length" => GetMessage("PROPERTY_LENGTH_HEADER_NAME"),
            "width" => GetMessage("PROPERTY_WIDTH_HEADER_NAME"),
            "height" => GetMessage("PROPERTY_HEIGHT_HEADER_NAME"),
            "picture" => GetMessage("PROPERTY_PICTURE_HEADER_NAME"),
        ];
    }

    /**
     * @return array[]
     */
    public function getIblockFieldsNames(): array
    {
        return [
            "weight" => [
                "code" => "catalog_weight",
                "name" => GetMessage("SELECT_WEIGHT_PROPERTY_NAME"),
                'unit' => 'mass',
            ],
            "length" => [
                "code" => "catalog_length",
                "name" => GetMessage("SELECT_LENGTH_PROPERTY_NAME"),
                'unit' => 'length',
            ],
            "width" => [
                "code" => "catalog_width",
                "name" => GetMessage("SELECT_WIDTH_PROPERTY_NAME"),
                'unit' => 'length',
            ],
            "height" => [
                "code" => "catalog_height",
                "name" => GetMessage("SELECT_HEIGHT_PROPERTY_NAME"),
                'unit' => 'length',
            ],
        ];
    }

    /**
     * @return array[]
     */
    public function getUnitsNames(): array
    {
        return [
            'length' => [
                'mm' => GetMessage("UNIT_MEASUREMENT_MM"),
                'cm' => GetMessage("UNIT_MEASUREMENT_CM"),
                'm' => GetMessage("UNIT_MEASUREMENT_M"),
            ],
            'mass' => [
                'mg' => GetMessage("UNIT_MEASUREMENT_MG"),
                'g' => GetMessage("UNIT_MEASUREMENT_G"),
                'kg' => GetMessage("UNIT_MEASUREMENT_KG"),
            ],
        ];
    }

    /**
     * @return string[]
     */
    public function getHintUnit(): array
    {
        return [
            'length' => 'mm',
            'mass' => 'g',
        ];
    }
}