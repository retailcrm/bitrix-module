<?php

use Intaro\RetailCrm\Icml\SettingsService;

CModule::IncludeModule('intaro.retailcrm');

/**
 * Документация по шаблонам экспорта:
 * @link https://dev.1c-bitrix.ru/api_help/catalog/templates.php
 *
 * Предопределенные переменные:
 *
 * @var $arOldSetupVars
 * @var $APPLICATION
 * @var $ACTION
 * @var $STEP
 * @var $PROFILE_ID
 * @var $SETUP_FILE_NAME
 * @var $SETUP_PROFILE_NAME
 */
//TODO заменить вызов на сервис-локатор, когда он приедет
$settingsService = new SettingsService($arOldSetupVars, $ACTION);

if (file_exists($_SERVER["DOCUMENT_ROOT"] . "/bitrix/php_interface/retailcrm/export_setup.php")) {
    require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/php_interface/retailcrm/export_setup.php");

    return;
}
if (!check_bitrix_sessid()) {
    return;
}

__IncludeLang(GetLangFileName(
        $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/intaro.retailcrm/lang/",
        "/icml_export_setup.php")
);

$MODULE_ID = 'intaro.retailcrm';
$CRM_CATALOG_BASE_PRICE = 'catalog_base_price';
$basePriceId = COption::GetOptionString($MODULE_ID, $CRM_CATALOG_BASE_PRICE . '_' . $_REQUEST['PROFILE_ID'], 1);

$priceTypes = [];
$dbPriceType = CCatalogGroup::GetList(["SORT" => "ASC"], [], [], [], ['ID', 'NAME', 'BASE']);

while ($arPriceType = $dbPriceType->Fetch()) {
    $priceTypes[$arPriceType['ID']] = $arPriceType;
}

//highloadblock
if (CModule::IncludeModule('highloadblock')) {
    $hlblockModule = true;
    $hlBlockList = $settingsService->getHlBlockList();
}

if (($ACTION === 'EXPORT' || $ACTION === 'EXPORT_EDIT' || $ACTION === 'EXPORT_COPY') && $STEP === 1) {
    $SETUP_FILE_NAME = $settingsService->getSingleSetting('SETUP_FILE_NAME');
    $SETUP_PROFILE_NAME = $settingsService->getSingleSetting('SETUP_PROFILE_NAME');
    $loadPurchasePrice = $settingsService->getSingleSetting('loadPurchasePrice');
    $iblockExport = $settingsService->getSingleSetting('iblockExport');

    if ($iblockExport) {
        $maxOffersValue = $settingsService->getSingleSetting('maxOffersValue');
    }

    $iblockPropertySku = [];
    $iblockPropertyUnitSku = [];
    $iblockPropertyProduct = [];
    $iblockPropertyUnitProduct = [];

    $iblockProperties = $settingsService->getIblockPropsPreset();

    foreach ($iblockProperties as $prop) {
        $settingsService->setProperties($iblockPropertySku, 'iblockPropertySku_' . $prop);
        $settingsService->setProperties(
            $iblockPropertyUnitSku,
            'iblockPropertyUnitSku_' . $prop
        );
        $settingsService->setProperties(
            $iblockPropertyProduct,
            'iblockPropertyProduct_' . $prop
        );
        $settingsService->setProperties(
            $iblockPropertyUnitProduct,
            'iblockPropertyUnitProduct_' . $prop
        );
    }
}

if ($STEP === 2) {
    $arSetupErrors = $settingsService->checkFileAndProfile(
        $SETUP_FILE_NAME ?? null,
        $SETUP_PROFILE_NAME ?? null
    );

    if (count($arSetupErrors) === 0) {
        $STEP = 1;
        ShowError(implode('<br />', $arSetupErrors));
    }
}

//Отображение формы
if ($STEP === 1) {
    ?>
    <style type="text/css">
        .iblock-export-table-display-none {
            display: none;
        }
    </style>

    <form method="post" action="<?=$APPLICATION->GetCurPage();?>">
        <?php
        if ($ACTION === 'EXPORT_EDIT' || $ACTION === 'EXPORT_COPY') {
            ?><input type="hidden" name="PROFILE_ID" value="<?=intval($PROFILE_ID);?>">
            <?php
        }
        ?>

        <h3><?=GetMessage("SETTINGS_INFOBLOCK");?></h3>
        <span class="text"><?=GetMessage("EXPORT_CATALOGS");?><br><br></span>
        <?php
        if (!isset($iblockExport) || !is_array($iblockExport)) {
            $iblockExport = [];
        }

        $iblockPropertiesName = $settingsService->getIblockPropsNames();
        $iblockFieldsName = $settingsService->getIblockFieldsNames();
        $iblockPropertiesHint = $settingsService->getHintProps();
        $units = $settingsService->getUnitsNames();
        $hintUnit = $settingsService->getHintUnit();
        $boolAll = false;
        $intCountChecked = 0;
        $intCountAvailIBlock = 0;
        $arIBlockList = [];
        $dbRes = CIBlock::GetList(
            ['IBLOCK_TYPE' => 'ASC', 'NAME' => 'ASC'],
            ['CHECK_PERMISSIONS' => 'Y', 'MIN_PERMISSION' => 'W']
        );

        while ($iblock = $dbRes->Fetch()) {
            $arCatalog = CCatalog::GetByIDExt($iblock["ID"]);

            if (!$arCatalog) {
                continue;
            }

            if (
                $arCatalog['CATALOG_TYPE'] !== 'D'
                && $arCatalog['CATALOG_TYPE'] !== 'X'
                && $arCatalog['CATALOG_TYPE'] !== 'P'
            ) {
                continue;
            }

            $propertiesSKU = null;

            if ($arCatalog['CATALOG_TYPE'] === 'X' || $arCatalog['CATALOG_TYPE'] === 'P') {
                $iblockOffer = CCatalogSKU::GetInfoByProductIBlock($iblock["ID"]);
                $dbSkuProperties = CIBlock::GetProperties($iblockOffer['IBLOCK_ID'], []);

                while ($prop = $dbSkuProperties->Fetch()) {
                    $propertiesSKU[] = $prop;
                }

                $oldPropertySKU = null;

                if (isset($iblockPropertySku[$iblock['ID']])) {
                    foreach ($iblockPropertiesName as $key => $prop) {
                        $oldPropertySKU[$key] = $iblockPropertySku[$iblock['ID']][$key];
                    }
                }

                $oldPropertyUnitSKU = null;

                if (isset($iblockPropertyUnitSku[$iblock['ID']])) {
                    foreach ($iblockPropertiesName as $key => $prop) {
                        $oldPropertyUnitSKU[$key] = $iblockPropertyUnitSku[$iblock['ID']][$key];
                    }
                }
            }

            $propertiesProduct = null;
            $dbProductProps = CIBlock::GetProperties($iblock['ID'], []);

            while ($prop = $dbProductProps->Fetch()) {
                $propertiesProduct[] = $prop;
            }

            $oldPropertyProduct = null;

            if (isset($iblockPropertyProduct[$iblock['ID']])) {
                foreach ($iblockPropertiesName as $key => $prop) {
                    $oldPropertyProduct[$key] = $iblockPropertyProduct[$iblock['ID']][$key];
                }
            }

            $oldPropertyUnitProduct = null;

            if (isset($iblockPropertyUnitProduct[$iblock['ID']])) {
                foreach ($iblockPropertiesName as $key => $prop) {
                    $oldPropertyUnitProduct[$key] = $iblockPropertyUnitProduct[$iblock['ID']][$key];
                }
            }

            $arSiteList = [];
            $rsSites = CIBlock::GetSite($iblock["ID"]);

            while ($arSite = $rsSites->Fetch()) {
                $arSiteList[] = $arSite["SITE_ID"];
            }

            if (count($iblockExport) != 0) {
                $boolExport = (in_array($iblock['ID'], $iblockExport));
            } else {
                $boolExport = true;
            }

            $arIBlockList[] = [
                'ID' => $iblock['ID'],
                'NAME' => $iblock['NAME'],
                'IBLOCK_TYPE_ID' => $iblock['IBLOCK_TYPE_ID'],
                'iblockExport' => $boolExport,
                'PROPERTIES_SKU' => $propertiesSKU,
                'PROPERTIES_PRODUCT' => $propertiesProduct,
                'OLD_PROPERTY_SKU_SELECT' => $oldPropertySKU,
                'OLD_PROPERTY_UNIT_SKU_SELECT' => $oldPropertyUnitSKU ?? null,
                'OLD_PROPERTY_PRODUCT_SELECT' => $oldPropertyProduct,
                'OLD_PROPERTY_UNIT_PRODUCT_SELECT' => $oldPropertyUnitProduct,
                'SITE_LIST' => '(' . implode(' ', $arSiteList) . ')',
            ];

            if ($boolExport) {
                $intCountChecked++;
            }

            $intCountAvailIBlock++;
        }

        if (count($iblockExport) != 0) {
            if ($intCountChecked == $intCountAvailIBlock) {
                $boolAll = true;
            }
        } else {
            $intCountChecked = $intCountAvailIBlock;
            $boolAll = true;
        }
        ?>

        <span class="text" style="font-weight: bold;"><?=GetMessage("CHECK_ALL_INFOBLOCKS");?></span>
        <input
            style="vertical-align: middle;"
            type="checkbox"
            name="icml_export_all"
            id="icml_export_all"
            value="Y"
            onclick="checkAll(this,<?=$intCountAvailIBlock;?>);"
            <?=($boolAll ? ' checked' : '');?>>
        </br>
        </br>
        <div>
            <?php
            $checkBoxCounter = 0;

            //Перебираем все торговые каталоги, формируя для каждого таблицу настроек
            foreach ($arIBlockList as $key => $arIBlock) { ?>
                <div>
                    <div>
                        <span class="text" style="font-weight: bold;"><?= htmlspecialcharsex("["
                                . $arIBlock["IBLOCK_TYPE_ID"]
                                . "] "
                                . $arIBlock["NAME"]
                                . " "
                                . $arIBlock['SITE_LIST']); ?></span>
                        <input
                            type="checkbox"
                            name="iblockExport[<?=$arIBlock["ID"]?>]"
                            id="iblockExport<?=++$checkBoxCounter?>"
                            value="<?=$arIBlock["ID"]?>"
                            <?php
                            if ($arIBlock['iblockExport']) {
                                echo " checked";
                            } ?>
                            onclick="checkOne(this,<?=$intCountAvailIBlock;?>);"
                        >
                    </div>
                    <br>
                    <div id="iblockExportTable<?=$checkBoxCounter?>" class="iblockExportTable"
                         data-type="<?=$arIBlock["ID"]?>">
                        <table class="adm-list-table" id="export_setup"
                            <?=($arIBlock['PROPERTIES_SKU'] == null ? 'style="width: 66%;"' : "")?>
                        >
                            <thead>
                            <tr class="adm-list-table-header">
                                <td class="adm-list-table-cell">
                                    <div class="adm-list-table-cell-inner"><?=GetMessage("LOADED_PROPERTY");?></div>
                                </td>
                                <td class="adm-list-table-cell">
                                    <div class="adm-list-table-cell-inner">
                                        <?=GetMessage("PROPERTY_PRODUCT_HEADER_NAME");?>
                                    </div>
                                </td>
                                <?php
                                if ($arIBlock['PROPERTIES_SKU'] != null) {?>
                                    <td class="adm-list-table-cell">
                                        <div
                                            class="adm-list-table-cell-inner">
                                            <?=GetMessage("PROPERTY_OFFER_HEADER_NAME");?>
                                        </div>
                                    </td>
                                <?php
                                } ?>
                            </tr>
                            </thead>
                            <tbody>

                            <?php
                            foreach ($iblockPropertiesName as $key => $property): ?>
                                <?php
                                $productSelected = false; ?>

                                <tr class="adm-list-table-row">
                                    <td class="adm-list-table-cell">
                                        <?=htmlspecialcharsex($property);?>
                                    </td>

                                    <td class="adm-list-table-cell">
                                        <select
                                            style="width: 200px;"
                                            id="iblockPropertyProduct_<?=$key?><?=$arIBlock["ID"]?>"
                                            name="iblockPropertyProduct_<?=$key?>[<?=$arIBlock["ID"]?>]"
                                            class="property-export"
                                            data-type="<?=$key?>"
                                            onchange="propertyChange(this);">
                                            <option value=""></option>
                                            <?php
                                            if (
                                            version_compare(SM_VERSION, '14.0.0', '>=')
                                            && array_key_exists($key, $iblockFieldsName)
                                            ) : ?>
                                            <optgroup label="<?=GetMessage("SELECT_FIELD_NAME");?>">
                                                <?php
                                                foreach ($iblockFieldsName as $keyField => $field) {
                                                    if ($keyField == $key): ?>
                                                        <option value="<?=$field['code'];?>"
                                                            <?php
                                                            if ($arIBlock['OLD_PROPERTY_PRODUCT_SELECT'] != null) {
                                                                if ($field['code']
                                                                    == $arIBlock['OLD_PROPERTY_PRODUCT_SELECT'][$key]) {
                                                                    echo " selected";
                                                                    $productSelected = true;
                                                                }
                                                            } else {
                                                                foreach ($iblockPropertiesHint[$key] as $hint) {
                                                                    if ($field['code'] == $hint) {
                                                                        echo " selected";
                                                                        $productSelected = true;
                                                                        break;
                                                                    }
                                                                }
                                                            }
                                                            ?>
                                                        >

                                                            <?=$field['name'];?>
                                                        </option>
                                                    <?php
                                                    endif; ?>

                                                    <?php
                                                } ?>
                                            </optgroup>
                                            <optgroup label="<?=GetMessage("SELECT_PROPERTY_NAME");?>">
                                                <?php
                                                endif; ?>

                                                <?php
                                                foreach ($arIBlock['PROPERTIES_PRODUCT'] as $prop): ?>
                                                    <option value="<?=$prop['CODE']?>"
                                                        <?php
                                                        if ($prop['USER_TYPE'] == 'directory') {
                                                            echo 'class="highloadblock-product"';
                                                            echo 'id="'
                                                                . $prop['USER_TYPE_SETTINGS']['TABLE_NAME']
                                                                . '"';
                                                        } else {
                                                            echo 'class="not-highloadblock"';
                                                        }
                                                        if ($arIBlock['OLD_PROPERTY_PRODUCT_SELECT'] != null) {
                                                            if ($prop["CODE"]
                                                                == $arIBlock['OLD_PROPERTY_PRODUCT_SELECT'][$key]) {
                                                                echo " selected";
                                                                $productSelected = true;
                                                                if ($prop['USER_TYPE'] == 'directory') {
                                                                    $selected = $prop['USER_TYPE_SETTINGS']['TABLE_NAME'];
                                                                }
                                                            }
                                                        } else {
                                                            foreach ($iblockPropertiesHint[$key] as $hint) {
                                                                if ($prop["CODE"] == $hint) {
                                                                    echo " selected";
                                                                    $productSelected = true;
                                                                    break;
                                                                }
                                                            }
                                                        }
                                                        ?>
                                                    >
                                                        <?=$prop["NAME"];?>
                                                    </option>
                                                <?php
                                                endforeach; ?>
                                                <?php
                                                if (version_compare(SM_VERSION, '14.0.0', '>=')
                                                && array_key_exists($key, $iblockFieldsName)){
                                                ?>
                                            </optgroup>
                                        <?php
                                        } ?>

                                        </select>
                                        <?php
                                        if (
                                            isset($selected)
                                            && isset($arOldSetupVars['highloadblock_product'
                                                . $selected
                                                . '_'
                                                . $key][$arIBlock['ID']])
                                        ) : ?>
                                            <select name="highloadblock_product<?=$selected;?>_<?=$key;?>[
                                            <?=$arIBlock['ID'] ?>]" id="highloadblock"
                                                    style="width: 100px; margin-left: 50px;">
                                                <?php
                                                foreach ($hlBlockList[$selected]['FIELDS'] as $field) : ?>
                                                    <option value="<?=$field;?>"<?php
                                                    if ($arOldSetupVars['highloadblock_product'
                                                        . $selected
                                                        . '_'
                                                        . $key][$arIBlock['ID']]
                                                        == $field) : echo "selected"; endif; ?>>
                                                        <?=$field;?>
                                                    </option>
                                                <?php
                                                endforeach; ?>
                                            </select>
                                        <?php
                                        endif; ?>
                                        <?php
                                        if (array_key_exists($key, $iblockFieldsName)) :?>
                                            <select
                                                style="width: 100px; margin-left: 50px;"
                                                id="iblockPropertyUnitProduct_<?=$key?><?=$arIBlock["ID"]?>"
                                                name="iblockPropertyUnitProduct_<?=$key?>[<?=$arIBlock["ID"]?>]"
                                            >
                                                <?php
                                                foreach ($units as $unitTypeName => $unitType): ?>
                                                    <?php
                                                    if ($unitTypeName == $iblockFieldsName[$key]['unit']): ?>
                                                        <?php
                                                        foreach ($unitType as $keyUnit => $unit): ?>
                                                            <option value="<?=$keyUnit;?>"
                                                                <?php
                                                                if ($arIBlock['OLD_PROPERTY_UNIT_PRODUCT_SELECT']
                                                                    != null) {
                                                                    if ($keyUnit
                                                                        == $arIBlock['OLD_PROPERTY_UNIT_PRODUCT_SELECT'][$key]) {
                                                                        echo " selected";
                                                                    }
                                                                } else {
                                                                    if ($keyUnit == $hintUnit[$unitTypeName]) {
                                                                        echo " selected";
                                                                    }
                                                                }
                                                                ?>
                                                            >
                                                                <?=$unit?>
                                                            </option>
                                                        <?php
                                                        endforeach; ?>
                                                    <?php
                                                    endif; ?>
                                                <?php
                                                endforeach; ?>
                                            </select>
                                        <?php
                                        endif; ?>
                                    </td>

                                    <?php
                                    //Столбец со свойствами тороговых предложений
                                    if ($arIBlock['PROPERTIES_SKU'] != null): ?>
                                        <td class="adm-list-table-cell">
                                            <select
                                                style="width: 200px;"
                                                id="iblockPropertySku_<?=$key?><?=$arIBlock["ID"]?>"
                                                name="iblockPropertySku_<?=$key?>[<?=$arIBlock["ID"]?>]"
                                                class="property-export"
                                                data-type="<?=$key?>"
                                                onchange="propertyChange(this);">

                                                <option value=""></option>
                                                <?php
                                                if (
                                                version_compare(SM_VERSION, '14.0.0', '>=')
                                                && array_key_exists($key, $iblockFieldsName)
                                                ) { ?>
                                                <optgroup label="<?=GetMessage("SELECT_FIELD_NAME");?>">
                                                    <?php
                                                    foreach ($iblockFieldsName as $keyField => $field): ?>

                                                        <?php
                                                        if ($keyField == $key) :?>
                                                            <option value="<?=$field['code'];?>"
                                                                <?php
                                                                if (!$productSelected) {
                                                                    if ($arIBlock['OLD_PROPERTY_SKU_SELECT']
                                                                        != null) {
                                                                        if ($field['code']
                                                                            == $arIBlock['OLD_PROPERTY_SKU_SELECT'][$key]) {
                                                                            echo " selected";
                                                                        }
                                                                    } else {
                                                                        foreach ($iblockPropertiesHint[$key] as $hint) {
                                                                            if ($field['code'] == $hint) {
                                                                                echo " selected";
                                                                                break;
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                                ?>
                                                            >
                                                                <?=$field['name'];?>
                                                            </option>
                                                        <?php
                                                        endif;
                                                    endforeach; ?>
                                                </optgroup>
                                                <optgroup label="<?=GetMessage("SELECT_PROPERTY_NAME");?>">
                                                    <?php
                                                    }

                                                    foreach ($arIBlock['PROPERTIES_SKU'] as $prop): ?>
                                                        <option value="<?=$prop['CODE']?>"
                                                            <?php
                                                            if ($prop['USER_TYPE'] == 'directory') {
                                                                echo 'class="highloadblock" id="'
                                                                    . $prop['USER_TYPE_SETTINGS']['TABLE_NAME']
                                                                    . '"';
                                                            } else {
                                                                echo 'class="not-highloadblock"';
                                                            }
                                                            if (!$productSelected) {
                                                                if ($arIBlock['OLD_PROPERTY_SKU_SELECT'] != null) {
                                                                    if ($prop["CODE"]
                                                                        == $arIBlock['OLD_PROPERTY_SKU_SELECT'][$key]) {
                                                                        echo " selected";
                                                                        if ($prop['USER_TYPE'] === 'directory') {
                                                                            $selected = $prop['USER_TYPE_SETTINGS']['TABLE_NAME'];
                                                                        }
                                                                    }
                                                                } else {
                                                                    foreach ($iblockPropertiesHint[$key] as $hint) {
                                                                        if ($prop["CODE"] == $hint) {
                                                                            echo " selected";
                                                                            break;
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                            ?>
                                                        >
                                                            <?=$prop["NAME"];?>
                                                        </option>
                                                    <?php
                                                    endforeach; ?>
                                                    <?php
                                                    if (
                                                    version_compare(SM_VERSION, '14.0.0', '>=')
                                                    && array_key_exists($key, $iblockFieldsName)
                                                    ) : ?>
                                                </optgroup>
                                            <?php
                                            endif; ?>
                                            </select>
                                            <?php
                                            if (
                                                isset($selected)
                                                && isset($arOldSetupVars['highloadblock'
                                                    . $selected
                                                    . '_'
                                                    . $key][$arIBlock['ID']])
                                            ) : ?>
                                                <select name="highloadblock<?=$selected;?>_<?=$key;?>[
                                                <?= $arIBlock['ID'] ?>
                                                ]" id="highloadblock"
                                                        style="width: 100px; margin-left: 50px;">
                                                    <?php
                                                    foreach ($hlBlockList[$selected]['FIELDS'] as $field) : ?>
                                                        <option value="<?=$field;?>"<?php
                                                        if ($arOldSetupVars['highloadblock'
                                                            . $selected
                                                            . '_'
                                                            . $key][$arIBlock['ID']]
                                                            == $field) {
                                                            echo "selected";
                                                        } ?>>
                                                            <?=$field;?>
                                                        </option>
                                                    <?php
                                                    endforeach; ?>
                                                </select>
                                            <?php
                                            endif; ?>
                                            <?php
                                            if (array_key_exists($key, $iblockFieldsName)) {?>
                                                <select
                                                    style="width: 100px; margin-left: 50px;"
                                                    id="iblockPropertyUnitSku_<?=$key?><?=$arIBlock["ID"]?>"
                                                    name="iblockPropertyUnitSku_<?=$key?>[<?=$arIBlock["ID"]?>]"
                                                >
                                                    <?php
                                                    foreach ($units as $unitTypeName => $unitType) {
                                                        if ($unitTypeName == $iblockFieldsName[$key]['unit']) {
                                                            foreach ($unitType as $keyUnit => $unit) { ?>
                                                                <option value="<?=$keyUnit;?>"
                                                                    <?php
                                                                    if ($arIBlock['OLD_PROPERTY_UNIT_SKU_SELECT']
                                                                        != null) {
                                                                        if ($keyUnit
                                                                            == $arIBlock['OLD_PROPERTY_UNIT_SKU_SELECT'][$key]) {
                                                                            echo " selected";
                                                                        }
                                                                    } else {
                                                                        if ($keyUnit == $hintUnit[$unitTypeName]) {
                                                                            echo " selected";
                                                                        }
                                                                    }
                                                                    ?>
                                                                >
                                                                    <?=$unit?>
                                                                </option>
                                                                <?php
                                                            }
                                                        }
                                                    } ?>
                                                </select>
                                            <?php
                                            } ?>
                                        </td>

                                    <?php
                                    endif; ?>
                                </tr>

                            <?php
                            endforeach; ?>
                            </tbody>
                        </table>
                        <br>
                        <br>
                    </div>
                </div>
                <?php
            } ?>
        </div>

        <input type="hidden" name="count_checked" id="count_checked" value="<?= $intCountChecked; ?>">
        <br>

        <h3><?=GetMessage("SETTINGS_EXPORT");?></h3>

        <span class="text"><?=GetMessage("FILENAME");?><br><br></span>
        <input type="text" name="SETUP_FILE_NAME"
               value="<?=htmlspecialcharsbx(strlen($SETUP_FILE_NAME) > 0 ?
                   $SETUP_FILE_NAME :
                   (COption::GetOptionString(
                       'catalog',
                       'export_default_path',
                       '/bitrix/catalog_export/'))
                   . 'retailcrm' . '.xml'
               );?>" size="50"><br><br>

        <span class="text"><?=GetMessage("LOAD_PURCHASE_PRICE");?>&nbsp;</span>
        <input type="checkbox" name="loadPurchasePrice" value="Y"
            <?=$loadPurchasePrice === 'Y' ? 'checked' : ''?>
        ><br><br><br>

        <span class="text"><?=GetMessage("BASE_PRICE");?>&nbsp;</span>
        <select name="price-types" class="typeselect">
            <option value=""></option>
            <?php
            foreach ($priceTypes as $priceType) { ?>
                <option value="<?=$priceType['ID'];?>"
                    <?php
                    if ($priceType['ID'] == $basePriceId) {
                        echo 'selected';
                    } ?>>
                    <?=$priceType['NAME'];?>
                </option>
                <?php
            } ?>
        </select><br><br><br>

        <?php
        if ($ACTION === "EXPORT_SETUP" || $ACTION === 'EXPORT_EDIT' || $ACTION === 'EXPORT_COPY') { ?>
            <span class="text"><?=GetMessage("OFFERS_VALUE");?><br><br></span>
            <input
                type="text"
                name="maxOffersValue"
                value="<?=htmlspecialchars($maxOffersValue)?>"
                size="15"><br><br><br>

            <span class="text"><?=GetMessage("PROFILE_NAME");?><br><br></span>
            <input
                type="text"
                name="SETUP_PROFILE_NAME"
                value="<?=htmlspecialchars($SETUP_PROFILE_NAME)?>"
                size="50"><br><br><br>
            <?php
        } ?>

        <script type="text/javascript" src='/bitrix/js/main/jquery/jquery-1.7.min.js'></script>
        <script type="text/javascript">
            function checkAll(obj, cnt) {
                let i;
                for (i = 0; i < cnt; i++) {
                    if (obj.checked) {
                        BX.removeClass('iblockExportTable' + (i + 1), "iblock-export-table-display-none");
                    }
                }

                const table = BX(obj.id.replace('iblockExport', 'iblockExportTable'));

                if (obj.checked) {
                    BX.removeClass(table, "iblock-export-table-display-none");
                }

                const easing = new BX.easing({
                    duration:   150,
                    start:      {opacity: obj.checked ? 0 : 100},
                    finish:     {opacity: obj.checked ? 100 : 0},
                    transition: BX.easing.transitions.linear,
                    step:       function(state) {
                        for (let i = 0; i < cnt; i++) {
                            BX('iblockExportTable' + (i + 1)).style.opacity = state.opacity / 100;
                        }
                    },
                    complete:   function() {
                        for (let i = 0; i < cnt; i++) {
                            if (!obj.checked) {
                                BX.addClass('iblockExportTable' + (i + 1), "iblock-export-table-display-none");
                            }
                        }
                    }
                });

                easing.animate();
                const boolCheck = obj.checked;

                for (i = 0; i < cnt; i++) {
                    BX('iblockExport' + (i + 1)).checked = boolCheck;
                }

                BX('count_checked').value = (boolCheck ? cnt : 0);
            }

            function checkOne(obj, cnt) {
                const table = BX(obj.id.replace('iblockExport', 'iblockExportTable'));

                if (obj.checked) {
                    BX.removeClass(table, "iblock-export-table-display-none");
                }

                const easing = new BX.easing({
                    duration:   150,
                    start:      {opacity: obj.checked ? 0 : 100},
                    finish:     {opacity: obj.checked ? 100 : 0},
                    transition: BX.easing.transitions.linear,
                    step:       function(state) {
                        table.style.opacity = state.opacity / 100;
                    },
                    complete:   function() {
                        if (!obj.checked) {
                            BX.addClass(table, "iblock-export-table-display-none");
                        }
                    }
                });

                easing.animate();
                const boolCheck               = obj.checked;
                let intCurrent                = parseInt(BX('count_checked').value);
                intCurrent += (boolCheck ? 1 : -1);
                BX('icml_export_all').checked = (intCurrent >= cnt);
                BX('count_checked').value     = intCurrent;
            }

            function propertyChange(obj) {
                let selectedOption = $(obj).find('option')[obj.selectedIndex];

                if (selectedOption.className === 'not-highloadblock') {
                    let objId = '#' + obj.id;

                    $(objId).parent().children('#highloadblock').remove();
                }

                if (selectedOption.className === 'highloadblock') {
                    getHbFromAjax(selectedOption, 'sku');
                }

                if (selectedOption.className === 'highloadblock-product') {
                    getHbFromAjax(selectedOption, 'product');
                }
            }

            function getHbFromAjax(that, type) {
                const td         = $(that).parents('td .adm-list-table-cell');
                const select     = $(that).parent('select').siblings('#highloadblock');
                const table_name = $(that).attr('id');
                const iblock     = $(that).parents('.iblockExportTable').attr('data-type');
                const key        = $(that).parent('select').attr('data-type');

                BX.ajax.runAction('intaro:retailcrm.api.icml.getHlTable',
                    {
                        method: 'POST',
                        data:   {
                            sessid:    BX.bitrix_sessid(),
                            tableName: table_name
                        }
                    }
                ).then((response) => {
                        $(select).remove();
                        $('#waiting').remove();
                        let new_options = '';
                        $.each(response.data.fields, function(key, value) {
                            new_options += '<option value="' + value + '">' + value + '</option>';
                        });

                        if (type === 'sku') {
                            $(td).append(
                                '<select name="highloadblock'
                                + response.data.table
                                + '_'
                                + key
                                + '['
                                + iblock
                                + ']" id="highloadblock" style="width: 100px; margin-left: 50px;">'
                                + new_options
                                + '</select>'
                            );
                        }

                        if (type === 'product') {
                            $(td).append(
                                '<select name="highloadblock_product'
                                + response.data.table
                                + '_'
                                + key
                                + '['
                                + iblock
                                + ']" id="highloadblock" style="width: 100px; margin-left: 50px;">'
                                + new_options
                                + '</select>'
                            );
                        }
                    }
                );
            }
        </script>

        <?=bitrix_sessid_post();?>
        <?php
        $setupFieldsValues = $settingsService->getSetupFieldsString(
            $iblockProperties ?? [],
            $hlblockModule === true,
            $hlBlockList ?? []
        );
        ?>
        <input type="hidden" name="lang" value="<?=LANGUAGE_ID?>">
        <input type="hidden" name="ACT_FILE" value="<?=htmlspecialcharsbx($_REQUEST["ACT_FILE"])?>">
        <input type="hidden" name="ACTION" value="<?=htmlspecialcharsbx($ACTION)?>">
        <input type="hidden" name="STEP" value="<?=$STEP + 1?>">
        <input type="hidden" name="SETUP_FIELDS_LIST" value="<?=$setupFieldsValues?>">
        <input type="submit" value="<?=($ACTION === "EXPORT") ? GetMessage("EXPORT") : GetMessage("SAVE")?>">
    </form>

    <?php
}

//Сохранение и выход
if ($STEP === 2) {
    COption::SetOptionString(
        $MODULE_ID,
        $CRM_CATALOG_BASE_PRICE . '_' . $_REQUEST['PROFILE_ID'],
        htmlspecialchars(trim($_POST['price-types']))
    );
    $FINITE = true;
}
?>
