<?php

namespace Intaro\RetailCrm\Icml;

use Bitrix\Catalog\ProductTable;
use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Iblock;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Entity;
use Bitrix\Main\Loader;
use Bitrix\Main\UserField\Internal\UserFieldHelper;
use Intaro\RetailCrm\Icml\Utils\IcmlUtils;
use Intaro\RetailCrm\Model\Bitrix\Orm\CatalogIblockInfo;
use Intaro\RetailCrm\Model\Bitrix\Xml\OfferParam;
use Intaro\RetailCrm\Model\Bitrix\Xml\SelectParams;
use Intaro\RetailCrm\Model\Bitrix\Xml\Unit;
use Intaro\RetailCrm\Model\Bitrix\Xml\XmlOffer;
use Intaro\RetailCrm\Model\Bitrix\Xml\XmlSetup;
use Intaro\RetailCrm\Model\Bitrix\Xml\XmlSetupPropsCategories;
use Logger;
use RetailcrmConfigProvider;

/**
 * Отвечает за создание XMLOffer
 *
 * Class XmlOfferBuilder
 * @package Intaro\RetailCrm\Icml
 */
class XmlOfferBuilder
{
    /**
     * @var \Intaro\RetailCrm\Model\Bitrix\Xml\XmlSetup
     */
    private $setup;

    /**
     * @var bool|string|null
     */
    private $purchasePriceNull;

    /**
     * @var array
     */
    private $measures;

    /**
     * @var string|null
     */
    private $serverName;

    /**
     * @var array
     */
    private $skuHlParams;

    /**
     * @var array
     */
    private $productHlParams;

    /**
     * @var string
     */
    private $productPicture;

    /**
     * @var \Intaro\RetailCrm\Model\Bitrix\Orm\CatalogIblockInfo
     */
    private $catalogIblockInfo;

    /**
     * @var array
     */
    private $productProps;

    /**
     * @var string
     */
    private $barcode;

    /**
     * @var \Intaro\RetailCrm\Model\Bitrix\Xml\SelectParams
     */
    private $selectParams;

    /**
     * @var \Intaro\RetailCrm\Model\Bitrix\Xml\XmlOffer
     */
    private $xmlOffer;

    /**
     * @var array
     */
    private $categories;

    /**
     * @var array
     */
    private $vatRates;

    /**
     * @var SettingsService
     */
    private $settingsService;

    /**
     * IcmlDataManager constructor.
     *
     * XmlOfferBuilder constructor.
     *
     * @param \Intaro\RetailCrm\Model\Bitrix\Xml\XmlSetup $setup
     * @param array                                       $measure
     * @param string|null                                 $serverName
     */
    public function __construct(XmlSetup $setup, array $measure, ?string $serverName)
    {
        $this->setup             = $setup;
        $this->setSettingsService();
        $this->purchasePriceNull = RetailcrmConfigProvider::getCrmPurchasePrice();
        $this->vatRates          = $this->settingsService->vatRates;
        $this->measures          = $this->prepareMeasures($measure);
        $this->serverName = $serverName;

        Loader::includeModule("highloadblock");
        Loader::includeModule('iblock');
        Loader::includeModule('catalog');
     }

    /**
     * @return \Intaro\RetailCrm\Model\Bitrix\Xml\XmlOffer
     */
    public function build(): XmlOffer
    {
        $this->xmlOffer          = new XmlOffer();
        $this->xmlOffer->barcode = $this->barcode;
        $this->xmlOffer->picture = $this->productPicture;

        $this->addDataFromParams();
        $this->addDataFromItem($this->productProps, $this->categories);

        return $this->xmlOffer;
    }

    private function setSettingsService(): void
    {
        global $PROFILE_ID;
        $this->settingsService = SettingsService::getInstance([], '', $PROFILE_ID);
    }

    /**
     * @param array $categories
     */
    public function setCategories(array $categories)
    {
        $this->categories = $categories;
    }

    /**
     * @param mixed $skuHlParams
     */
    public function setSkuHlParams($skuHlParams): void
    {
        $this->skuHlParams = $skuHlParams;
    }

    /**
     * @param mixed $productHlParams
     */
    public function setProductHlParams($productHlParams): void
    {
        $this->productHlParams = $productHlParams;
    }

    /**
     * @param \Intaro\RetailCrm\Model\Bitrix\Xml\SelectParams $selectParams
     */
    public function setSelectParams(SelectParams $selectParams): void
    {
        $this->selectParams = $selectParams;
    }

    /**
     * @param array $productProps
     */
    public function setOfferProps(array $productProps): void
    {
        $this->productProps = $productProps;

    }

    /**
     * @param string $barcode
     */
    public function setBarcode(string $barcode): void
    {
        $this->barcode = $barcode;
    }

    /**
     * @param \Intaro\RetailCrm\Model\Bitrix\Orm\CatalogIblockInfo $catalogIblockInfo
     */
    public function setCatalogIblockInfo(CatalogIblockInfo $catalogIblockInfo): void
    {
        $this->catalogIblockInfo = $catalogIblockInfo;
    }

    /**
     * @param string $getProductPicture
     */
    public function setPicturesPath(string $getProductPicture): void
    {
        $this->productPicture = $getProductPicture;
    }

    public function setServerName(string $serverName): void
    {
        $this->serverName = $serverName;
    }

    /**
     * Добавляет в XmlOffer значения настраиваемых параметров, производителя, вес и габариты
     */
    private function addDataFromParams(): void
    {
        $resultParams = array_merge($this->productHlParams, $this->skuHlParams);

        //достаем значения из обычных свойств
        $resultParams = array_merge($resultParams, $this->getSimpleParams(
            $resultParams,
            $this->selectParams->configurable,
            $this->productProps
        ));

        [$resultParams, $this->xmlOffer->dimensions]
            = $this->extractDimensionsFromParams(
            $this->setup->properties,
            $resultParams,
            $this->catalogIblockInfo->productIblockId
        );
        [$resultParams, $this->xmlOffer->weight]
            = $this->extractWeightFromParams(
            $this->setup->properties,
            $resultParams,
            $this->catalogIblockInfo->productIblockId
        );
        [$resultParams, $this->xmlOffer->vendor] = $this->extractVendorFromParams($resultParams);
        $resultParams           = $this->dropEmptyParams($resultParams);
        $this->xmlOffer->params = $this->createParamObject($resultParams);
    }

    /**
     * Добавляет в объект XmlOffer информацию из GetList
     *
     * @param array $item
     * @param array $categoryIds
     */
    private function addDataFromItem(array $item, array $categoryIds): void
    {
        $this->xmlOffer->id = $item['ID'];
        $this->xmlOffer->productId = $item['ID'];
        $this->xmlOffer->productType = (int) $item['CATALOG_TYPE'];
        $this->xmlOffer->quantity = $item['CATALOG_QUANTITY'] ?? '';
        $this->xmlOffer->url = $item['DETAIL_PAGE_URL']
            ? $this->serverName . $item['DETAIL_PAGE_URL']
            : '';
        $this->xmlOffer->price = $item['CATALOG_PRICE_' . $this->setup->basePriceId];
        $this->xmlOffer->purchasePrice = $this->getPurchasePrice(
            $item,
            $this->setup->loadPurchasePrice,
            $this->purchasePriceNull
        );
        $this->xmlOffer->categoryIds = $categoryIds;
        $this->xmlOffer->name = $item['NAME'];
        $this->xmlOffer->xmlId = $item['EXTERNAL_ID'] ?? '';
        $this->xmlOffer->productName = $item['NAME'];
        $this->xmlOffer->vatRate = $this->getVatRate($item);
        $this->xmlOffer->unitCode = $this->getUnitCode($item['CATALOG_MEASURE'], $item['ID']);
        $this->xmlOffer->activity = $item['ACTIVE'];
        $this->xmlOffer->markable = $this->isMarkableOffer($item['ID']);
    }

    /**
     * Возвращает закупочную цену, если она требуется настройками
     *
     * @param array     $product
     * @param bool|null $isLoadPrice
     * @param string    $purchasePriceNull
     * @return float|null
     */
    private function getPurchasePrice(array $product, ?bool $isLoadPrice, string $purchasePriceNull): ?float
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
     * Возвращает значение активной ставки НДС, если она указана в товаре
     *
     * @param array $product
     * @return string
     */
    private function getVatRate(array $product): string
    {
        if (
            !empty($product['VAT_ID'])
            && array_key_exists($product['VAT_ID'], $this->vatRates)
            && is_string($this->vatRates[$product['VAT_ID']]['RATE'])
        ) {
            return $this->vatRates[$product['VAT_ID']]['RATE'];
        }

        if (
            !empty($product['CATALOG_VAT_ID'])
            && array_key_exists($product['CATALOG_VAT_ID'], $this->vatRates)
            && is_string($this->vatRates[$product['CATALOG_VAT_ID']]['RATE'])
        ) {
            return $this->vatRates[$product['CATALOG_VAT_ID']]['RATE'];
        }

        return 'none';
    }

    /**
     * Возвращает массив обычных свойств
     *
     * @param array $resultParams
     * @param array $configurableParams
     * @param array $productProps
     * @return array
     */
    private function getSimpleParams(array $resultParams, array $configurableParams, array $productProps): array
    {
        foreach ($configurableParams as $key => $params) {
            if (isset($resultParams[$key])) {
                continue;
            }

            $codeWithValue = strtoupper($params) . '_VALUE';

            if (isset($productProps[$codeWithValue])) {
                $resultParams[$key] = $productProps[$codeWithValue];
            } elseif (isset($productProps[$params])) {
                $resultParams[$key] = $productProps[$params];
            }
        }

        return $resultParams;
    }

    /**
     * Удаляет параметры с пустыми и нулевыми значениями
     *
     * @param array $params
     * @return array
     */
    private function dropEmptyParams(array $params): array
    {
        return array_diff($params, ['', 0, '0']);
    }

    /**
     * Разделяем вендора и остальные параметры
     *
     * @param array $resultParams
     * @return array
     */
    private function extractVendorFromParams(array $resultParams): array
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
    private function extractWeightFromParams(
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
    private function extractDimensionsFromParams(
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

    /**
     * Собираем объект параметре заказа
     *
     * @param array $params
     * @return OfferParam[]
     */
    private function createParamObject(array $params): array
    {
        $offerParams = [];
        $names = $this->settingsService->actualPropList;

        foreach ($params as $code => $value) {

            if (!isset($names[$code])) {
                continue;
            }

            $offerParam        = new OfferParam();
            $offerParam->name  = $names[$code];
            $offerParam->code  = $code;
            $offerParam->value = $value;
            $offerParams[]     = $offerParam;
        }

        return $offerParams;
    }

    /**
     * Собираем объект единицы измерения для товара
     *
     * @param int $measureIndex
     * @return \Intaro\RetailCrm\Model\Bitrix\Xml\Unit
     */
    private function createUnitFromCode(int $measureIndex): Unit
    {
        $unit       = new Unit();
        $unit->name = $this->measures[$measureIndex]['MEASURE_TITLE'] ?? '';
        $unit->code = $this->measures[$measureIndex]['SYMBOL_INTL'] ?? '';
        $unit->sym  = $this->measures[$measureIndex]['SYMBOL_RUS'] ?? '';

        return $unit;
    }

    /**
     * Удаляет запрещенные в unit сode символы
     *
     * @link https://docs.retailcrm.ru/Developers/modules/ICML
     *
     * @param array $measures
     *
     * @return array
     */
    private function prepareMeasures(array $measures): array
    {
        foreach ($measures as &$measure) {
            if (isset($measure['SYMBOL_INTL'])) {
                $measure['SYMBOL_INTL'] =  preg_replace("/[^a-zA-Z_\-]/",'', $measure['SYMBOL_INTL']);
            }
        }

        return $measures;
    }

    /**
     * @param array $currentMeasure
     *
     * @return \Intaro\RetailCrm\Model\Bitrix\Xml\Unit
     */
    private function createUnitFromProductTable(array $currentMeasure): Unit
    {
        $clearCurrentMeasure = $this->prepareMeasures(array_shift($currentMeasure));

        $unit = new Unit();
        $unit->name = $clearCurrentMeasure['MEASURE']['MEASURE_TITLE'] ?? '';
        $unit->code = $clearCurrentMeasure['MEASURE']['SYMBOL_INTL'] ?? '';
        $unit->sym = $clearCurrentMeasure['MEASURE']['SYMBOL_RUS'] ?? '';

        return $unit;
    }

    /**
     * @param int|null $measureId
     * @param int      $itemId
     *
     * @return \Intaro\RetailCrm\Model\Bitrix\Xml\Unit|null
     */
    private function getUnitCode(?int $measureId, int $itemId): ?Unit
    {
        if (isset($measureId) && !empty($measureId)) {
           return $this->createUnitFromCode($measureId);
        }

        try {
            $currentMeasure = ProductTable::getCurrentRatioWithMeasure($itemId);

            if (is_array($currentMeasure)) {
                return $this->createUnitFromProductTable($currentMeasure);
            }
        } catch (ArgumentException $exception) {
            Logger::getInstance()->write(GetMessage('UNIT_ERROR'), 'i_crm_load_log');
        }

        return null;
    }

    /**
     * Метод для проверки можно ли маркировать товар.
     *
     * @param $offerId
     *
     * @return string|null
     */
    private function isMarkableOffer($offerId): ?string
    {
        $idHlBlock = $this->getHighloadBlockIdByName('ProductMarkingCodeGroup');
        $hlBlock = HighloadBlockTable::getById($idHlBlock)->fetch();
        $hlBlockData = HighloadBlockTable::compileEntity($hlBlock)->getDataClass();
        $userFieldManager = UserFieldHelper::getInstance()->getManager();
        $userFieldsData = $userFieldManager->GetUserFields(ProductTable::getUfId(), $offerId);
        $ufProductGroup = $userFieldsData['UF_PRODUCT_GROUP']['VALUE'] ?? null;
        $isMarkableOffer = null;

        if ($ufProductGroup !== null) {
            $productGroup =  $hlBlockData::getList(["select" => ["UF_NAME"], "filter" => ['ID' => $ufProductGroup]]);
            $isMarkableOffer = !empty($productGroup->Fetch()['UF_NAME']) ? 'Y' : 'N';
        }

        return $isMarkableOffer;
    }

    /**
     * Метод для получения ID Highload-блока по названию блока.
     *
     * Таблица в БД - b_hlsys_marking_code_group
     * По умолчанию ID Highload-блока ProductMarkingCodeGroup - 1.
     *
     * @param $blockName
     *
     * @return mixed|null
     */
    private function getHighloadBlockIdByName($blockName)
    {
        $hlBlock = HighloadBlockTable::getList(['filter' => ['NAME' => $blockName], 'select' => ['ID']])->fetch();

        return $hlBlock['ID'] ?? 1;
    }
}
