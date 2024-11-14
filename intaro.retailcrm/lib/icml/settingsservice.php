<?php

namespace Intaro\RetailCrm\Icml;

use Bitrix\Highloadblock\HighloadBlockTable;
use CCatalog;
use CCatalogGroup;
use CCatalogSku;
use CCatalogVat;
use CIBlock;
use COption;
use Bitrix\Main\Config\Option;
use Intaro\RetailCrm\Service\Hl;
use RetailcrmConfigProvider;
use Bitrix\Main\Application;
use Bitrix\Main\Entity\Query;

/**
 * Отвечает за управление настройками выгрузки icml каталога
 * @var $PROFILE_ID
 * Class SettingsService
 *
 *
 * @package Intaro\RetailCrm\Icml
 */
class SettingsService
{
    /**
     * инфоблок товаров, имеющих торговые предложения,
     * при это сам инфоблок тоже является торговым каталогом
     */
    public const CATALOG_WITH_SKU = 'X';

    /*
     * инфоблок товаров, имеющих торговые предложения,
     * но сам торговым каталогом не является
     */
    public const INFOBLOCK_WITH_SKU = 'P';

    private const MODULE_ID = 'intaro.retailcrm';

    private string $catalogCustomPropsOptionName;

    private string $profileCatalogsOptionName;

    private string $exportProfileId;

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
    public $setupProfileName = '';

    /**
     * @var array
     */
    public $priceTypes = [];

    /**
     * @var array
     */
    public $vatRates = [];

    /**
     * @var mixed|string|null
     */
    public $loadNonActivity;

    /** @var array */
    public $actualPropList = [];

    /** @var array */
    public $customPropList = [];

    /** @var array */
    public $defaultPropList = [];

    /**
     * @var \Intaro\RetailCrm\Icml\SettingsService|null
     */
    private static $instance = null;


    /**
     * SettingsService constructor.
     *
     * @param array       $arOldSetupVars
     * @param string|null $action
     */
    private function __construct(array $arOldSetupVars, ?string $action)
    {
        $this->arOldSetupVars = $arOldSetupVars;
        $this->action = $action;
        $this->iblockExport = $this->getSingleSetting('iblockExport');
        $this->loadPurchasePrice = $this->getSingleSetting('loadPurchasePrice');
        $this->loadNonActivity = $this->getSingleSetting('loadNonActivity');
        $oldSetup = $this->getSingleSetting('SETUP_FILE_NAME');
        $defaultFilePath = RetailcrmConfigProvider::getDefaultIcmlPath();
        $this->setupFileName = htmlspecialcharsbx($oldSetup ?? $defaultFilePath);
        $this->setupProfileName
            = $this->getSingleSetting('SETUP_PROFILE_NAME') ?? GetMessage('PROFILE_NAME_EXAMPLE');

        $this->getPriceTypes();
        $this->getVatRates();

        $this->exportProfileId = $this->getExportProfileId();
        $this->profileCatalogsOptionName = $this->getProfileCatalogsOptionName();

        $this->customPropList = $this->getNewProps();
        $this->defaultPropList = $this->getIblockPropsPreset();
        $this->actualPropList = $this->getActualPropList();
    }

    /**
     * @param array       $arOldSetupVars
     * @param string|null $action
     *
     * @return \Intaro\RetailCrm\Icml\SettingsService|null
     */
    public static function getInstance(array $arOldSetupVars, ?string $action): ?SettingsService
    {
        if (is_null(self::$instance)) {
            self::$instance = new self($arOldSetupVars, $action);
        }

        return self::$instance;
    }

    public function getActualPropList(): array
    {
        $customProps = [];

        foreach ($this->customPropList as $propsByCatalog) {
            $customProps = array_merge($customProps, $propsByCatalog);
        }

        return [
            ...$this->defaultPropList,
            ...$customProps
        ];
    }

    public function getPriceTypes()
    {
        $dbPriceType = CCatalogGroup::GetList(['SORT' => 'ASC'], [], [], [], ['ID', 'NAME', 'BASE']);

        while ($arPriceType = $dbPriceType->Fetch()) {
            $this->priceTypes[$arPriceType['ID']] = $arPriceType;
        }
    }

    public function getVatRates()
    {
        $dbVatRate = CCatalogVat::GetListEx(['SORT' => 'ASC'], ['ACTIVE' => 'Y'], false, false, ['ID', 'NAME', 'RATE']);

        while ($arVatRate = $dbVatRate->Fetch()) {
            $this->vatRates[$arVatRate['ID']] = $arVatRate;
        }
    }

    /**
     * @param string $selected
     * @param string $key
     * @param int    $iblockId
     * @param string $field
     * @param string $fieldGroup
     *
     * @return string
     */
    public function getHlOptionStatus(string $selected, string $key, int $iblockId, string $field, string $fieldGroup): string
    {
        if ($this->arOldSetupVars[$fieldGroup . $selected . '_' . $key][$iblockId] === $field) {
            return ' selected';
        }

        return '';
    }

    /**
     * @param string $key
     * @param int    $iblockId
     * @param string $tableName
     * @param string $catalogType
     *
     * @return bool
     */
    public function isHlSelected(
        string $key,
        int $iblockId,
        string $tableName = '',
        string $catalogType = ''
    ): bool {
        return isset(
            $tableName,
            $this->arOldSetupVars['highloadblock' . $catalogType . $tableName . '_' .$key][$iblockId]
        );
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function isOptionHasPreset(string $key): bool
    {
        return version_compare(SM_VERSION, '14.0.0', '>=')
        && array_key_exists($key, $this->getIblockFieldsNames());
    }

    /**
     * @return bool
     */
    public function isSetupModulePage(): bool
    {
        global $APPLICATION;

        return $APPLICATION->GetCurPage() === '/bitrix/admin/partner_modules.php';
    }

    /**
     * @param array  $properties
     * @param string $propName
     */
    private function setProperties(array &$properties, string $propName): void
    {
        foreach ($this->arOldSetupVars[$propName] as $iblock => $val) {
            if (!empty($val)) {
                $properties[$iblock][$propName] = $val;
            }
        }
    }

    /**
     * @return mixed|string
     */
    public function getSingleSetting(string $settingName)
    {
        return $this->arOldSetupVars[$settingName] ?? null;
    }

    /**
     * @return string[]
     */
    private function getIblockPropsPreset(): array
    {
        return [
            'article'      => GetMessage('PROPERTY_ARTICLE_HEADER_NAME'),
            'manufacturer' => GetMessage('PROPERTY_MANUFACTURER_HEADER_NAME'),
            'color'        => GetMessage('PROPERTY_COLOR_HEADER_NAME'),
            'size'         => GetMessage('PROPERTY_SIZE_HEADER_NAME'),
            'weight'       => GetMessage('PROPERTY_WEIGHT_HEADER_NAME'),
            'length'       => GetMessage('PROPERTY_LENGTH_HEADER_NAME'),
            'width'        => GetMessage('PROPERTY_WIDTH_HEADER_NAME'),
            'height'       => GetMessage('PROPERTY_HEIGHT_HEADER_NAME'),
            'picture'      => GetMessage('PROPERTY_PICTURE_HEADER_NAME'),
        ];
    }

    private function getNewProps(): array
    {
        $result = [];
        $currentProfileCatalogIds = $this->getProfileCatalogs();

        if (!is_null($currentProfileCatalogIds)) {
            foreach ($currentProfileCatalogIds as $catalogId) {
                $catalogCustomProps = $this
                    ->setCatalogCustomPropsOptionName($catalogId)
                    ->getCustomProps();

                foreach ($catalogCustomProps as $prop) {
                    $result[$catalogId][$prop['code']] = $prop['title'];
                }
            }
        }

        return $result;
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
            'length' => ['LENGTH', 'DLINA'],
            'width' => ['WIDTH', 'SHIRINA'],
            'height' => ['HEIGHT', 'VISOTA'],
            'picture' => ['PICTURE', 'PICTURE'],
        ];
    }

    /**
     * @return array[]
     */
    public function getIblockFieldsNames(): array
    {
        return [
            'weight' => [
                'CODE' => 'catalog_weight',
                'name' => GetMessage('SELECT_WEIGHT_PROPERTY_NAME'),
                'unit' => 'mass',
            ],
            'length' => [
                'CODE' => 'catalog_length',
                'name' => GetMessage('SELECT_LENGTH_PROPERTY_NAME'),
                'unit' => 'length',
            ],
            'width' => [
                'CODE' => 'catalog_width',
                'name' => GetMessage('SELECT_WIDTH_PROPERTY_NAME'),
                'unit' => 'length',
            ],
            'height' => [
                'CODE' => 'catalog_height',
                'name' => GetMessage('SELECT_HEIGHT_PROPERTY_NAME'),
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
                'mm' => GetMessage('UNIT_MEASUREMENT_MM'),
                'cm' => GetMessage('UNIT_MEASUREMENT_CM'),
                'm' => GetMessage('UNIT_MEASUREMENT_M'),
            ],
            'mass' => [
                'mg' => GetMessage('UNIT_MEASUREMENT_MG'),
                'g' => GetMessage('UNIT_MEASUREMENT_G'),
                'kg' => GetMessage('UNIT_MEASUREMENT_KG'),
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
        foreach (array_keys($this->actualPropList) as $prop) {
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
            $arSetupErrors[] = str_replace('#FILE#', $setupFileName,
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
            $entity = Hl::getBaseEntityByHlId($hlblockArr['ID']);
            $hbFields = $entity->getFields();
            $hlBlockList[$hlblockArr['TABLE_NAME']]['LABEL'] = $hlblockArr['NAME'];

            foreach ($hbFields as $hbFieldCode => $hbField) {
                $hlBlockList[$hlblockArr['TABLE_NAME']]['FIELDS'][] = $hbFieldCode;
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
        $values = 'loadPurchasePrice,SETUP_FILE_NAME,iblockExport,maxOffersValue,loadNonActivity';

        foreach ($iblockProperties as $val) {
            $values .= ',iblockPropertySku_' . $val
                . ',iblockPropertyUnitSku_' . $val
                . ',iblockPropertyProduct_' . $val
                . ',iblockPropertyUnitProduct_' . $val;

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
     * @param array $prop
     *
     * @return string|null
     */
    public function getHlTableName(array $prop): ?string
    {
        if ($prop['USER_TYPE'] === 'directory') {
            return $prop['USER_TYPE_SETTINGS']['TABLE_NAME'];
        }

        return null;
    }


    /**
     * @param array       $prop
     * @param array|null  $oldSelect
     * @param string      $key
     *
     * @return bool
     */
    public function isOptionSelected(array $prop, array $oldSelect, string $key): bool
    {
        if ($key === 'manufacturer') {
            $a = 'yes';
        }
        if (count($oldSelect) > 0) {
            if ($prop['CODE'] === $oldSelect[$key]) {
                return true;
            }
        } else {
            $iblockPropertiesHint = $this->getHintProps();

            foreach ($iblockPropertiesHint[$key] as $hint) {
                if ($prop['CODE'] === $hint) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param array $prop
     * @param bool  $isProduct
     *
     * @return string
     */
    public function getOptionClass(array $prop, bool $isProduct): string
    {
        $productMarker = $isProduct ? '-product' : '';

        if ($prop['USER_TYPE'] === 'directory') {
            return 'class="highloadblock' . $productMarker .'" id="'
                . $prop['USER_TYPE_SETTINGS']['TABLE_NAME']
                . '"';
        }

        return 'class="not-highloadblock"';
    }

    /**
     * @param array $unitSelect
     * @param string|null $keyUnit
     * @param string|null $key
     * @param string|null $unitTypeName
     *
     * @return string
     */
    public function getUnitOptionStatus(array $unitSelect, ?string $keyUnit, ?string $key, ?string $unitTypeName): string
    {
        if (count($unitSelect) > 0) {
            if ($keyUnit === $unitSelect[$key]) {
                return ' selected';
            }
        } else {
            $hintUnit = $this->getHintUnit();

            if ($keyUnit === $hintUnit[$unitTypeName]) {
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
            $siteList[] = $arSite['SITE_ID'];
        }

        return $siteList;
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

        if ($iblockOffer !== false) {
            $dbSkuProperties = CIBlock::GetProperties($iblockOffer['IBLOCK_ID'], [], ['MULTIPLE' => 'N']);

            while ($prop = $dbSkuProperties->Fetch()) {
                if ($prop['CODE'] !== '') {
                    $propertiesSKU[] = $prop;
                }
            }
        }

        return $propertiesSKU;
    }

    /**
     * Возвращает уже выбранные свойства
     *
     * @param array|null $oldValues
     * @param int        $iblockId
     * @param string     $keyGroup
     *
     * @return array
     */
    private function getOldProps(?array $oldValues, int $iblockId, string $keyGroup = ''): array
    {
        $props = [];

        if (isset($oldValues[$iblockId])) {
            foreach (array_keys($this->actualPropList) as $prop) {
                $fullKey = $keyGroup . '_' . $prop;
                $props[$prop] = $oldValues[$iblockId][$fullKey];
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
        return $arCatalog['CATALOG_TYPE'] === 'D'
            || $arCatalog['CATALOG_TYPE'] === 'X'
            || $arCatalog['CATALOG_TYPE'] === 'P';
    }

    /**
     * @param int $iblockId
     *
     * @return array|null
     */
    public function getProductProps(int $iblockId): ?array
    {
        $propertiesProduct = null;

        $iblockResult = CIBlock::GetProperties($iblockId, [], ['MULTIPLE' => 'N']);

        while ($prop = $iblockResult->Fetch()) {
            if ($prop['CODE'] !== '') {
                $propertiesProduct[] = $prop;
            }
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
        if (is_array($iblockExport) && count($iblockExport) !== 0) {
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
        $arIBlockList = [];
        $intCountChecked = 0;
        $intCountAvailIBlock = 0;

        $dbRes = CIBlock::GetList(
            ['IBLOCK_TYPE' => 'ASC', 'NAME' => 'ASC'],
            ['CHECK_PERMISSIONS' => 'Y', 'MIN_PERMISSION' => 'W']
        );

        while ($iblock = $dbRes->Fetch()) {
            $arCatalog = CCatalog::GetByIDExt($iblock['ID']);

            if (!$arCatalog || !$this->isCorrectCatalogType($arCatalog)) {
                continue;
            }

            if (
                $arCatalog['CATALOG_TYPE'] === self::CATALOG_WITH_SKU
                || $arCatalog['CATALOG_TYPE'] === self::INFOBLOCK_WITH_SKU
            ) {
                $propertiesSKU = $this->getSkuProps($iblock['ID']);
                $oldPropertySKU = $this->getOldProps(
                    $this->iblockPropertySku,
                    $iblock['ID'],
                    'iblockPropertySku'
                );
                $oldPropertyUnitSKU = $this->getOldProps(
                    $this->iblockPropertyUnitSku,
                    $iblock['ID'],
                    'iblockPropertyUnitSku'
                );
            }

            $arIBlockList[] = [
                'ID' => $iblock['ID'],
                'NAME' => $iblock['NAME'],
                'IBLOCK_TYPE_ID' => $iblock['IBLOCK_TYPE_ID'],
                'iblockExport' => $this->isExport($iblock['ID'], $this->iblockExport),
                'PROPERTIES_SKU' => $propertiesSKU ?? null,
                'OLD_PROPERTY_SKU_SELECT' => $oldPropertySKU ?? [],
                'OLD_PROPERTY_UNIT_SKU_SELECT' => $oldPropertyUnitSKU ?? [],
                'PROPERTIES_PRODUCT' => $this->getProductProps($iblock['ID']),
                'OLD_PROPERTY_PRODUCT_SELECT' => $this->getOldProps(
                    $this->iblockPropertyProduct,
                    $iblock['ID'],
                    'iblockPropertyProduct'
                ),
                'OLD_PROPERTY_UNIT_PRODUCT_SELECT' => $this->getOldProps(
                    $this->iblockPropertyUnitProduct,
                    $iblock['ID'],
                    'iblockPropertyUnitProduct'
                ),
                'SITE_LIST' => '(' . implode(' ', $this->getSiteList($iblock['ID'])) . ')',
            ];

            if ($arIBlockList['iblockExport']) {
                $intCountChecked++;
            }

            $intCountAvailIBlock++;

            unset($propertiesSKU, $oldPropertySKU, $oldPropertyUnitSKU);
        }

        return [$arIBlockList, $intCountChecked, $intCountAvailIBlock, $arIBlockList['iblockExport'] ?? false];
    }

    public function setCatalogCustomPropsOptionName(string $catalogId): self
    {
        $this->catalogCustomPropsOptionName = sprintf(
            'customProps_exportProfileId_%s_catalogId_%s',
            $this->exportProfileId,
            $catalogId
        );

        return $this;
    }

    private function getExportProfileId(): string
    {
        global $PROFILE_ID;

        return $PROFILE_ID;
    }

    private function getProfileCatalogsOptionName(): string
    {
        return sprintf('exportProfile_%s_catalogs', $this->exportProfileId);
    }

    public function getCustomProps(): ?array
    {
        $props = unserialize(COption::GetOptionString(self::MODULE_ID, $this->catalogCustomPropsOptionName));

        if (!$props) {
            return null;
        }

        return $props;
    }

    public function removeCustomProps(array $propsToDelete, string $catalogId): void
    {
        $currentCatalogProps = $this->getCustomProps();
        $updatedCatalogProps = array_values(array_filter(
            $currentCatalogProps,
            fn ($currentProp) => !in_array($currentProp, $propsToDelete)
        ));

        if (empty($updatedCatalogProps)) {
            COption::RemoveOption(self::MODULE_ID, $this->catalogCustomPropsOptionName);
            COption::RemoveOption(self::MODULE_ID, $this->profileCatalogsOptionName);
        } else {
            $this->updateProfileCatalogs($catalogId);
            $this->updateCustomPropsOptionEntry($updatedCatalogProps);
        }
    }

    public function updateCustomPropsOptionEntry(array $updatedProps)
    {
        COption::SetOptionString(self::MODULE_ID, $this->catalogCustomPropsOptionName, '');
        $this->setCustomPropsOptionEntry($updatedProps);
    }

    private function setCustomPropsOptionEntry(array $props)
    {
        $propsString = serialize($props);
        COption::SetOptionString(self::MODULE_ID, $this->catalogCustomPropsOptionName, $propsString);
    }

    public function saveCustomProps(string $catalogId, array $newProps): void
    {
        $currentProps = $this->getCustomProps();

        if (is_null($currentProps)) {
            $this->setCustomPropsOptionEntry($newProps);
            $this->setProfileCatalogs($catalogId);
        } else {
            $updatedProps = array_merge($currentProps, $newProps);
            $this->updateCustomPropsOptionEntry($updatedProps);
            $this->updateProfileCatalogs($catalogId);
        }
    }

    private function getProfileCatalogs(): ?array
    {
        $catalogs = unserialize(COption::GetOptionString(self::MODULE_ID, $this->profileCatalogsOptionName));

        if (!$catalogs) {
            return null;
        }

        return $catalogs;
    }

    private function setProfileCatalogs(string $catalogId): void
    {
        $catalogs = serialize([$catalogId]);
        COption::SetOptionString(self::MODULE_ID, $this->profileCatalogsOptionName, $catalogs);
    }

    private function updateProfileCatalogs(string $catalogId): void
    {
        $currentCatalogs = unserialize(COption::GetOptionString(self::MODULE_ID, $this->profileCatalogsOptionName));
        $updatedCatalogs = serialize([...$currentCatalogs, $catalogId]);

        COption::SetOptionString(self::MODULE_ID, $this->profileCatalogsOptionName, '');
        COption::SetOptionString(self::MODULE_ID, $this->profileCatalogsOptionName, $updatedCatalogs);
    }
}
