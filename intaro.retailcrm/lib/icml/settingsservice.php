<?php

namespace Intaro\RetailCrm\Icml;

use Bitrix\Highloadblock\HighloadBlockTable;
use Intaro\RetailCrm\Service\Hl;

/**
 * Отвечает за управление настройками выгрузки icml каталога
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
     * @var string|null
     */
    private $action;

    /**
     * SettingsService constructor.
     *
     * @param array       $arOldSetupVars
     * @param string|null $action
     */
    public function __construct(array $arOldSetupVars, ?string $action)
    {
        $this->arOldSetupVars = $arOldSetupVars;
        $this->action = $action;
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

    /**
     * @param string|null $setupFileName
     * @param string      $setupProfileName
     *
     * @return array
     */
    public function checkFileAndProfile(?string $setupFileName, string $setupProfileName): array
    {
        global $APPLICATION;

        $arSetupErrors = [];

        if (strlen($setupFileName) <= 0) {
            $arSetupErrors[] = GetMessage('ERROR_NO_FILENAME');
        } elseif ($APPLICATION->GetFileAccessPermission($setupFileName) < 'W') {
            $arSetupErrors[] = str_replace("#FILE#", $setupFileName,
                GetMessage('FILE_ACCESS_DENIED'));
        }

        $isValidAction = (
            $this->action === 'EXPORT_SETUP'
            || $this->action === 'EXPORT_EDIT'
            || $this->action === 'EXPORT_COPY'
        );

        if ($isValidAction && strlen($setupProfileName) <= 0) {
            $arSetupErrors[] = GetMessage('ERROR_NO_PROFILE_NAME');
        }

        return $arSetupErrors;
    }

    /**
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getHlBlockList(): array
    {
        $hlBlockList = [];
        $hlblockListDb = HighloadBlockTable::getList();

        while ($hlblockArr = $hlblockListDb->Fetch()) {
            $entity = Hl::getBaseEntityByHlId($hlblockArr["ID"]);
            $hbFields = $entity->getFields();
            $hlBlockList[$hlblockArr["TABLE_NAME"]]['LABEL'] = $hlblockArr["NAME"];

            foreach ($hbFields as $hbFieldCode => $hbField) {
                $hlBlockList[$hlblockArr["TABLE_NAME"]]['FIELDS'][] = $hbFieldCode;
            }
        }

        return $hlBlockList;
    }

    /**
     * @param array $iblockProperties
     * @param bool  $hlblockModule
     * @param array $hlBlockList
     *
     * @return string
     */
    public function getSetupFieldsString(array $iblockProperties, bool $hlblockModule, array $hlBlockList): string
    {
        $values = 'loadPurchasePrice,SETUP_FILE_NAME,iblockExport,maxOffersValue';

        foreach ($iblockProperties as $val) {
            $values .= ",iblockPropertySku_" . $val
                . ",iblockPropertyUnitSku_" . $val
                . ",iblockPropertyProduct_" . $val
                . ",iblockPropertyUnitProduct_" . $val;

            if ($hlblockModule === true && $val !== 'picture') {
                foreach ($hlBlockList as $hlblockTable => $hlblock) {
                    $values .= ',highloadblock' . $hlblockTable . '_' . $val;
                    $values .= ',highloadblock_product' . $hlblockTable . '_' . $val;
                }
            }
        }

        return $values;
    }
}
