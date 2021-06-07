<?php

namespace Intaro\RetailCrm\Icml;

use Bitrix\Highloadblock\HighloadBlockTable;
use CCatalog;
use CCatalogGroup;
use CCatalogSku;
use CIBlock;
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
     * @var mixed|string|null
     */
    public $iblockExport;

    /**
     * @var mixed|string|null
     */
    public $loadPurchasePrice;

    /**
     * @var array
     */
    public $iblockPropertySku = [];

    /**
     * @var array
     */
    public $iblockPropertyUnitSku = [];

    /**
     * @var array
     */
    public $iblockPropertyProduct = [];

    /**
     * @var array
     */
    public $iblockPropertyUnitProduct = [];

    /**
     * @var string
     */
    public $setupFileName = '';

    /**
     * @var string
     */
    public  $setupProfileName = '';

    /**
     * @var array
     */
    public $priceTypes = [];

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
        $this->iblockExport = $this->getSingleSetting('iblockExport');
        $this->loadPurchasePrice = $this->getSingleSetting('loadPurchasePrice');
        $this->setupFileName = $this->getSingleSetting('SETUP_FILE_NAME');
        $this->setupProfileName = $this->getSingleSetting('SETUP_PROFILE_NAME');

        $this->getPriceTypes();
    }

    public function getPriceTypes()
    {
        $dbPriceType = CCatalogGroup::GetList(["SORT" => "ASC"], [], [], [], ['ID', 'NAME', 'BASE']);

        while ($arPriceType = $dbPriceType->Fetch()) {
            $this->priceTypes[$arPriceType['ID']] = $arPriceType;
        }
    }

    /**
     * @param array  $properties
     * @param string $propName
     */
    private function setProperties(array &$properties, string $propName)
    {
        foreach ($this->arOldSetupVars[$propName] as $iblock => $val) {
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

    public function setProps(): void
    {
        $iblockProperties = $this->getIblockPropsPreset();

        foreach ($iblockProperties as $prop) {
           $this->setProperties($this->iblockPropertySku, 'iblockPropertySku_' . $prop);
           $this->setProperties($this->iblockPropertyUnitSku, 'iblockPropertyUnitSku_' . $prop);
           $this->setProperties($this->iblockPropertyProduct, 'iblockPropertyProduct_' . $prop);
           $this->setProperties($this->iblockPropertyUnitProduct, 'iblockPropertyUnitProduct_' . $prop);
        }
    }

    /**
     * @param string|null $setupFileName
     * @param string      $setupProfileName
     *
     * @return array
     */
    private function checkFileAndProfile(?string $setupFileName, string $setupProfileName): array
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

    /**
     * @param array      $prop
     * @param array|null $oldSelect
     * @param string     $key
     * @param string     $selected
     *
     * @return bool
     */
    public function isOptionSelected(array $prop, ?array $oldSelect, string $key, string &$selected): bool
    {
        if ($oldSelect != null) {
            if ($prop["CODE"] === $oldSelect[$key]) {
                if ($prop['USER_TYPE'] === 'directory') {
                    $selected = $prop['USER_TYPE_SETTINGS']['TABLE_NAME'];
                }

                return true;
            }
        } else {
            $iblockPropertiesHint = $this->getHintProps();

            foreach ($iblockPropertiesHint[$key] as $hint) {
                if ($prop["CODE"] == $hint) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param array $prop
     *
     * @return string
     */
    public function getOptionClass(array $prop): string
    {
        if ($prop['USER_TYPE'] === 'directory') {
            return 'class="highloadblock-product" id="'
                . $prop['USER_TYPE_SETTINGS']['TABLE_NAME']
                . '"';
        } else {
            return 'class="not-highloadblock"';
        }
    }

    /**
     * @param array $arIBlock
     * @param       $keyUnit
     * @param       $key
     * @param       $unitTypeName
     *
     * @return string
     */
    public function getUnitOptionStatus(array $arIBlock, $keyUnit, $key, $unitTypeName): string
    {
        if ($arIBlock['OLD_PROPERTY_UNIT_SKU_SELECT'] != null) {
            if ($keyUnit == $arIBlock['OLD_PROPERTY_UNIT_SKU_SELECT'][$key]) {
                return ' selected';
            }
        } else {
            $hintUnit = $this->getHintUnit();

            if ($keyUnit == $hintUnit[$unitTypeName]) {
               return ' selected';
            }
        }

        return '';
    }

    /**
     * @param int $iblockId
     *
     * @return array
     */
    public function getSiteList(int $iblockId): array
    {
        $siteList = [];

        $rsSites = CIBlock::GetSite($iblockId);

        while ($arSite = $rsSites->Fetch()) {
            $siteList[] = $arSite["SITE_ID"];
        }

        return $siteList;
    }

    /**
     * @param int   $iblockId
     * @param array $iblockPropertyUnitProduct
     *
     * @return array|null
     */
    public function getOldPropsUnitProduct(int $iblockId, array $iblockPropertyUnitProduct): ?array
    {
        $oldPropertyUnitProduct = null;

        if (isset($iblockPropertyUnitProduct[$iblockId])) {
            $iblockPropertiesName = $this->getIblockPropsNames();

            foreach ($iblockPropertiesName as $key => $prop) {
                $oldPropertyUnitProduct[$key] = $iblockPropertyUnitProduct[$iblockId][$key];
            }
        }

        return $oldPropertyUnitProduct;
    }

    /**
     * @param int $iblockId
     *
     * @return array|null
     */
    private function getSkuProps(int $iblockId): ?array
    {
        $propertiesSKU = null;

        $iblockOffer = CCatalogSKU::GetInfoByProductIBlock($iblockId);
        $dbSkuProperties = CIBlock::GetProperties($iblockOffer['IBLOCK_ID'], []);

        while ($prop = $dbSkuProperties->Fetch()) {
            $propertiesSKU[] = $prop;
        }

        return $propertiesSKU;
    }

    /**
     * @param array|null $allProps
     * @param array|null $propsNames
     * @param int        $iblockId
     *
     * @return array|null
     */
    private function getOldProps(?array $allProps, ?array $propsNames, int $iblockId): ?array
    {
        $props = null;

        if (isset($allProps[$iblockId])) {
            foreach ($propsNames as $key => $prop) {
                $props[$key] = $allProps[$iblockId][$key];
            }
        }

        return $props;
    }

    /**
     * @param array $arCatalog
     *
     * @return bool
     */
    private function isCorrectCatalogType(array $arCatalog): bool
    {
        if ($arCatalog['CATALOG_TYPE'] === 'D'
            || $arCatalog['CATALOG_TYPE'] === 'X'
            || $arCatalog['CATALOG_TYPE'] === 'P') {
            return true;
        }

        return false;
    }

    /**
     * @param int $iblockId
     *
     * @return array
     */
    public function getProductProps(int $iblockId): array
    {
        $propertiesProduct = null;

        $iblockResult = CIBlock::GetProperties($iblockId, []);

        while ($prop = $iblockResult->Fetch()) {
            $propertiesProduct[] = $prop;
        }

        return $propertiesProduct;
    }

    /**
     * @param $iblockId
     * @param $iblockExport
     *
     * @return bool
     */
    public function isExport($iblockId, $iblockExport): bool
    {
        if (count($iblockExport) != 0) {
            return (in_array($iblockId, $iblockExport));
        }

        return true;
    }

    /**
     * @param int         $step
     * @param string|null $fileName
     * @param string|null $profileName
     *
     * @return int
     */
    public function returnIfErrors(int $step, ?string $fileName, ?string $profileName): int
    {
        if ($step === 2) {
            $arSetupErrors = $this->checkFileAndProfile(
                $fileName ?? null,
                $profileName ?? null
            );

            if (count($arSetupErrors) > 0) {
                ShowError(implode('<br />', $arSetupErrors));

                return 1;
            }
        }

        return $step;
    }

    /**
     * @return array
     */
    public function getSettingsForIblocks(): array
    {
        $iblockPropertiesName = $this->getIblockPropsNames();

        $arIBlockList = [];

        $dbRes = CIBlock::GetList(
            ['IBLOCK_TYPE' => 'ASC', 'NAME' => 'ASC'],
            ['CHECK_PERMISSIONS' => 'Y', 'MIN_PERMISSION' => 'W']
        );

        while ($iblock = $dbRes->Fetch()) {
            $arCatalog = CCatalog::GetByIDExt($iblock['ID']);

            if (!$arCatalog || !$this->isCorrectCatalogType($arCatalog)) {
                continue;
            }

            $propertiesSKU = null;
            $propertiesSKU = null;
            $oldPropertySKU = null;

            if ($arCatalog['CATALOG_TYPE'] === 'X' || $arCatalog['CATALOG_TYPE'] === 'P') {
                $propertiesSKU = $this->getSkuProps($iblock['ID']);
                $oldPropertySKU = $this->getOldProps(
                    $this->iblockPropertySku,
                    $iblockPropertiesName,
                    $iblock['ID']
                );
                $oldPropertyUnitSKU = $this->getOldProps(
                    $this->iblockPropertyUnitSku,
                    $iblockPropertiesName,
                    $iblock['ID']
                );
            }

            $isExportIblock = $this->isExport($iblock['ID'], $this->iblockExport);

            $arIBlockList[] = [
                'ID' => $iblock['ID'],
                'NAME' => $iblock['NAME'],
                'IBLOCK_TYPE_ID' => $iblock['IBLOCK_TYPE_ID'],
                'iblockExport' => $isExportIblock,
                'PROPERTIES_SKU' => $propertiesSKU,
                'PROPERTIES_PRODUCT' => $this->getProductProps($iblock['ID']),
                'OLD_PROPERTY_SKU_SELECT' => $oldPropertySKU,
                'OLD_PROPERTY_UNIT_SKU_SELECT' => $oldPropertyUnitSKU ?? null,
                'OLD_PROPERTY_PRODUCT_SELECT' => $this->getOldProps(
                    $this->iblockPropertyProduct,
                    $iblockPropertiesName,
                    $iblock['ID']
                ),
                'OLD_PROPERTY_UNIT_PRODUCT_SELECT' => $this->getOldPropsUnitProduct(
                    $iblock['ID'],
                    $this->iblockPropertyUnitProduct
                ),
                'SITE_LIST' => '(' . implode(' ', $this->getSiteList($iblock['ID'])) . ')',
            ];

            if ($isExportIblock) {
                $intCountChecked++;
            }

            $intCountAvailIBlock++;
        }

        return [$arIBlockList, $intCountChecked, $intCountAvailIBlock, $isExportIblock];
    }
}
