<?php

use Intaro\RetailCrm\Icml\SettingsService;

CModule::IncludeModule('intaro.retailcrm');

/**
 * Документация по шаблонам экспорта:
 * @link https://dev.1c-bitrix.ru/api_help/catalog/templates.php
 *
 * Предопределенные переменные:
 *
 * Ранее сохраненные настройки экспорта из SETUP_VARS b_catalog_export
 * @var $arOldSetupVars
 *
 * @var $APPLICATION
 * @var $ACTION
 *
 * 1 - вывод настроек, 2 - сохранение формы с настройками
 * @var $STEP
 * @var $PROFILE_ID
 * @var $SETUP_FILE_NAME
 * @var $SETUP_PROFILE_NAME
 */

//TODO заменить вызов на сервис-локатор, когда он приедет
$settingsService = SettingsService::getInstance(
    $arOldSetupVars ?? [],
    $ACTION
);

$isSetupModulePage = $settingsService->isSetupModulePage();

if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/bitrix/php_interface/retailcrm/export_setup.php')) {
    require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/php_interface/retailcrm/export_setup.php');

    return;
}

if (!check_bitrix_sessid()) {
    return;
}

__IncludeLang(GetLangFileName(
        $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/intaro.retailcrm/lang/',
        '/icml_export_setup.php')
);

$basePriceId = RetailcrmConfigProvider::getCrmBasePrice($_REQUEST['PROFILE_ID']);
$priceTypes = $settingsService->priceTypes;
$iblockFieldsName = $settingsService->getIblockFieldsNames();
$iblockPropertiesHint = $settingsService->getHintProps();
$units = $settingsService->getUnitsNames();
$hintUnit = $settingsService->getHintUnit();

//highloadblock
if (CModule::IncludeModule('highloadblock')) {
    $hlblockModule = true;
    $hlBlockList = $settingsService->getHlBlockList();
}

if (($ACTION === 'EXPORT' || $ACTION === 'EXPORT_EDIT' || $ACTION === 'EXPORT_COPY') && $STEP === 1) {
	$SETUP_FILE_NAME = $settingsService->setupFileName;
	$SETUP_PROFILE_NAME = $settingsService->setupProfileName;

    $iblockProperties = $settingsService->getIblockPropsPreset();
    $loadPurchasePrice = $settingsService->loadPurchasePrice;
    $iblockExport = $settingsService->iblockExport;
    $loadNonActivity = $settingsService->loadNonActivity;

    if ($iblockExport) {
        $maxOffersValue = $settingsService->getSingleSetting('maxOffersValue');
    }

    $settingsService->setProps();

    $iblockPropertySku = $settingsService->iblockPropertySku;
    $iblockPropertyUnitSku = $settingsService->iblockPropertyUnitSku;
    $iblockPropertyProduct = $settingsService->iblockPropertyProduct;
    $iblockPropertyUnitProduct = $settingsService->iblockPropertyUnitProduct;

    $boolAll = false;
    $intCountChecked = 0;
    $intCountAvailIBlock = 0;
}

if (!isset($iblockExport) || !is_array($iblockExport)) {
    $iblockExport = [];
}

[$arIBlockList, $intCountChecked, $intCountAvailIBlock, $isExportIblock]
    = $settingsService->getSettingsForIblocks();

if (count($iblockExport) !== 0) {
    if ($intCountChecked === $intCountAvailIBlock) {
        $boolAll = true;
    }
} else {
    $intCountChecked = $intCountAvailIBlock;
    $boolAll = true;
}

//Проверка на ошибки
$STEP = $settingsService->returnIfErrors($STEP, $SETUP_FILE_NAME, $SETUP_PROFILE_NAME);

//Отображение формы
if ($STEP === 1) {
    ?>
    <style>
        .iblock-export-table-display-none {
            display: none;
        }
    </style>

    <form method="post" action="<?=$APPLICATION->GetCurPage()?>">
        <?php
        if ($ACTION === 'EXPORT_EDIT' || $ACTION === 'EXPORT_COPY') {
            ?>
            <input type="hidden" name="PROFILE_ID" value="<?=(int)$PROFILE_ID?>">
            <?php
        }
        ?>
        <h3><?=GetMessage('SETTINGS_INFOBLOCK')?></h3>
        <span class="text"><?=GetMessage('EXPORT_CATALOGS');?><br><br></span>
        <span class="text" style="font-weight: bold;"><?=GetMessage('CHECK_ALL_INFOBLOCKS')?></span>
        <input
            style="vertical-align: middle;"
            type="checkbox"
            name="icml_export_all"
            id="icml_export_all"
            value="Y"
            onclick="checkAll(this,<?=$intCountAvailIBlock?>);"
            <?=($boolAll ? ' checked' : '')?>>
        </br>
        </br>
        <div>
            <?php
            $checkBoxCounter = 0;

            //Перебираем все торговые каталоги, формируя для каждого таблицу настроек
            foreach ($arIBlockList as $arIBlock) {
                ?>
                <div>
                    <div>
                        <span class="text" style="font-weight: bold;">
                            <?= htmlspecialcharsex('['
                                . $arIBlock['IBLOCK_TYPE_ID']
                                . '] '
                                . $arIBlock['NAME']
                                . ' '
                                . $arIBlock['SITE_LIST']) ?>
                        </span>
                        <input
                            type="checkbox"
                            name="iblockExport[<?=$arIBlock['ID']?>]"
                            id="iblockExport<?=++$checkBoxCounter?>"
                            value="<?=$arIBlock['ID']?>"
                            <?php
                            if ($arIBlock['iblockExport']) {
                                echo ' checked';
                            } ?>
                            onclick="checkOne(this,<?=$intCountAvailIBlock?>);"
                        >
                    </div>
                    <br>
                    <div id="iblockExportTable<?=$checkBoxCounter?>" class="iblockExportTable"
                         data-type="<?=$arIBlock['ID']?>">
                        <table class="adm-list-table" id="export_setup"
                            <?=($arIBlock['PROPERTIES_SKU'] === null ? 'style="width: 66%;"' : '')?>
                        >
                            <thead>
                            <tr class="adm-list-table-header">
                                <td class="adm-list-table-cell">
                                    <div class="adm-list-table-cell-inner"><?=GetMessage('LOADED_PROPERTY');?></div>
                                </td>
                                <td class="adm-list-table-cell">
                                    <div class="adm-list-table-cell-inner">
                                        <?=GetMessage('PROPERTY_PRODUCT_HEADER_NAME')?>
                                    </div>
                                </td>
                                <?php
                                if ($arIBlock['PROPERTIES_SKU'] !== null) {?>
                                    <td class="adm-list-table-cell">
                                        <div class="adm-list-table-cell-inner">
                                            <?=GetMessage('PROPERTY_OFFER_HEADER_NAME');?>
                                        </div>
                                    </td>
                                <?php
                                } ?>
                            </tr>
                            </thead>
                            <tbody>

                            <?php
                            foreach ($settingsService->getIblockPropsNames() as $propertyKey => $property) {
                                $productSelected = false; ?>

                                <tr class="adm-list-table-row">
                                    <td class="adm-list-table-cell">
                                        <?=htmlspecialcharsex($property)?>
                                    </td>
                                    <td class="adm-list-table-cell">
                                        <select
                                            style="width: 200px;"
                                            id="iblockPropertyProduct_<?=$propertyKey . $arIBlock['ID']?>"
                                            name="iblockPropertyProduct_<?=$propertyKey?>[<?=$arIBlock['ID']?>]"
                                            class="property-export"
                                            data-type="<?=$propertyKey?>"
                                            onchange="propertyChange(this);">
                                            <option value=""></option>
                                            <?php
                                            if ($settingsService->isOptionHasPreset($propertyKey)) {
                                                ?>
                                            <optgroup label="<?=GetMessage('SELECT_FIELD_NAME')?>">
                                                <?php
                                                foreach ($iblockFieldsName as $keyField => $field) {
                                                    if ($keyField === $propertyKey) { ?>
                                                        <option value="<?=$field['CODE']?>"
                                                            <?php
                                                            $productSelected = $settingsService->isOptionSelected(
                                                                $field,
                                                                $arIBlock['OLD_PROPERTY_PRODUCT_SELECT'],
                                                                $propertyKey
                                                            );
                                                            ?>

                                                            <?= $productSelected ? ' selected' : ''?>
                                                        >
                                                            <?=$field['name']?>
                                                        </option>
                                                        <?php
                                                    }
                                                } ?>
                                            </optgroup>
                                            <optgroup label="<?=GetMessage('SELECT_PROPERTY_NAME')?>">
                                                <?php
                                                }

                                                $productHlTableName = '';

                                                foreach ($arIBlock['PROPERTIES_PRODUCT'] as $prop) { ?>
                                                    <option value="<?=$prop['CODE']?>"
                                                        <?php
                                                        echo $settingsService->getOptionClass($prop, true);

                                                        $productSelected = $settingsService->isOptionSelected(
                                                            $prop,
                                                            $arIBlock['OLD_PROPERTY_PRODUCT_SELECT'],
                                                            $propertyKey
                                                        );

                                                        $productHlTableName
                                                            = $settingsService->getHlTableName($prop)
                                                            ?? $productHlTableName;

                                                        echo $productSelected ? ' selected' : '';
                                                        ?>
                                                    >
                                                        <?=$prop['NAME']?>
                                                    </option>
                                                    <?php
                                                }

                                                if ($settingsService->isOptionHasPreset($propertyKey)) {
                                                ?>
                                            </optgroup>
                                        <?php
                                        } ?>
                                        </select>
                                        <?php
                                        if ($settingsService->isHlSelected(
                                            $propertyKey,
                                            $arIBlock['ID'],
                                            $productHlTableName,
                                            '_product'
                                        )
                                        ) {?>
                                            <select name="highloadblock_product<?=$productHlTableName . '_' .
                                            $propertyKey . '[' . $arIBlock['ID'] . ']' ?>" id="highloadblock"
                                                    style="width: 100px; margin-left: 50px;">
                                                <?php
                                                foreach ($hlBlockList[$productHlTableName]['FIELDS'] as $field) {
                                                    ?>
                                                    <option value="<?=$field?>"
                                                        <?= $settingsService->getHlOptionStatus(
                                                            $productHlTableName,
                                                            $propertyKey,
                                                            $arIBlock['ID'],
                                                            (string) $field,
                                                            'highloadblock_product'
                                                        ) ?>
                                                    >
                                                        <?=$field?>
                                                    </option>
                                                <?php
                                                } ?>
                                            </select>
                                        <?php
                                        }

                                        //Единицы измерения для товаров
                                        if (array_key_exists($propertyKey, $iblockFieldsName)) :?>
                                            <select
                                                style="width: 100px; margin-left: 50px;"
                                                id="iblockPropertyUnitProduct_<?=$propertyKey . $arIBlock['ID']?>"
                                                name="iblockPropertyUnitProduct_<?=$propertyKey?>[<?=$arIBlock['ID']?>]"
                                            >
                                                <?php
                                                foreach ($units as $unitTypeName => $unitType) { ?>
                                                    <?php
                                                    if ($unitTypeName == $iblockFieldsName[$propertyKey]['unit']): ?>
                                                        <?php
                                                        foreach ($unitType as $keyUnit => $unit): ?>
                                                            <option value="<?=$keyUnit?>"
                                                                <?=$settingsService->getUnitOptionStatus(
                                                                    $arIBlock['OLD_PROPERTY_UNIT_PRODUCT_SELECT'],
                                                                    $keyUnit,
                                                                    $propertyKey,
                                                                    (string) $unitTypeName
                                                                )
                                                                ?>
                                                            >
                                                                <?=$unit?>
                                                            </option>
                                                        <?php
                                                        endforeach; ?>
                                                    <?php
                                                    endif; ?>
                                                <?php
                                                } ?>
                                            </select>
                                        <?php
                                        endif; ?>
                                    </td>
                                    <?php
                                    //Столбец со свойствами тороговых предложений
                                    if ($arIBlock['PROPERTIES_SKU'] !== null) {?>
                                        <td class="adm-list-table-cell">
                                            <select
                                                style="width: 200px;"
                                                id="iblockPropertySku_<?=$propertyKey?><?=$arIBlock['ID']?>"
                                                name="iblockPropertySku_<?=$propertyKey?>[<?=$arIBlock['ID']?>]"
                                                class="property-export"
                                                data-type="<?=$propertyKey?>"
                                                onchange="propertyChange(this);">

                                                <option value=""></option>
                                                <?php
                                                if ($settingsService->isOptionHasPreset($propertyKey)) {
                                                    ?>
                                                <optgroup label="<?=GetMessage('SELECT_FIELD_NAME');?>">
                                                    <?php
                                                    foreach ($iblockFieldsName as $keyField => $field) {
                                                        if ($keyField === $propertyKey) :?>
                                                            <option value="<?=$field['CODE']?>"
                                                                <?php
                                                                $isSelected = $settingsService->isOptionSelected(
                                                                    $field,
                                                                    $arIBlock['OLD_PROPERTY_SKU_SELECT'],
                                                                    $propertyKey
                                                                );
                                                                echo $isSelected ? ' selected' : '';
                                                                ?>
                                                            >
                                                                <?=$field['name']?>
                                                            </option>
                                                        <?php
                                                        endif;
                                                    } ?>
                                                </optgroup>
                                                <optgroup label="<?=GetMessage('SELECT_PROPERTY_NAME');?>">
                                                    <?php
                                                    }

                                                    $skuHlTableName = '';

                                                    foreach ($arIBlock['PROPERTIES_SKU'] as $prop) { ?>
                                                        <option value="<?=$prop['CODE']?>"
                                                            <?php
                                                            echo $settingsService->getOptionClass($prop, false);
                                                            if (!$productSelected) {
                                                                $isSelected = $settingsService->isOptionSelected(
                                                                    $prop,
                                                                    $arIBlock['OLD_PROPERTY_SKU_SELECT'],
                                                                    $propertyKey
                                                                );

                                                                $skuHlTableName
                                                                    = $settingsService->getHlTableName($prop)
                                                                    ?? $skuHlTableName;

                                                                echo $isSelected ? ' selected' : '';
                                                            }
                                                            ?>
                                                        >
                                                            <?=$prop['NAME']?>
                                                        </option>
                                                        <?php
                                                    }

                                                    if ($settingsService->isOptionHasPreset($propertyKey)) {
                                                        ?>
                                                </optgroup>
                                            <?php
                                            } ?>
                                            </select>
                                            <?php
                                            if (
                                            $settingsService->isHlSelected(
                                                $propertyKey,
                                                $arIBlock['ID'],
                                                $skuHlTableName
                                            )
                                            ) { ?>
                                                <select
                                                    name="highloadblock<?=$skuHlTableName . '_' . $propertyKey . '['
                                                    . $arIBlock['ID'] . ']'?>"
                                                    id="highloadblock"
                                                    style="width: 100px;
                                                    margin-left: 50px;"
                                                >
                                                    <?php
                                                    foreach ($hlBlockList[$skuHlTableName]['FIELDS'] as $field)
                                                        : ?>
                                                        <option value="<?=$field?>"
                                                            <?=
                                                            $settingsService->getHlOptionStatus(
                                                                $skuHlTableName,
                                                                $propertyKey,
                                                                $arIBlock['ID'],
                                                                (string) $field,
                                                                'highloadblock'
                                                            )?>
                                                        >
                                                            <?=$field?>
                                                        </option>
                                                    <?php
                                                    endforeach; ?>
                                                </select>
                                            <?php
                                            }

                                            if (array_key_exists($propertyKey, $iblockFieldsName)) {?>
                                                <select
                                                    style="width: 100px; margin-left: 50px;"
                                                    id="iblockPropertyUnitSku_<?=$propertyKey?><?=$arIBlock['ID']?>"
                                                    name="iblockPropertyUnitSku_<?=$propertyKey?>[<?=$arIBlock['ID']?>]"
                                                >
                                                    <?php
                                                    foreach ($units as $unitTypeName => $unitType) {
                                                        if ($unitTypeName == $iblockFieldsName[$propertyKey]['unit']) {
                                                            foreach ($unitType as $keyUnit => $unit) { ?>
                                                                <option value="<?=$keyUnit?>"
                                                                    <?php
                                                                    echo $settingsService->getUnitOptionStatus(
                                                                        $arIBlock['OLD_PROPERTY_UNIT_SKU_SELECT'],
                                                                        $keyUnit,
                                                                        $propertyKey,
                                                                        $unitTypeName
                                                                    );
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
                                    } ?>
                                </tr>
                            <?php
                            } ?>
                            </tbody>
                        </table>
                        <br>
                        <br>
                    </div>
                </div>
                <?php
            } ?>
        </div>
        <input type="hidden" name="count_checked" id="count_checked" value="<?=$intCountChecked?>">
        <br>
        <h3><?=GetMessage('SETTINGS_EXPORT')?></h3>
        <span class="text"><?=GetMessage('FILENAME')?><br><br></span>
        <input type="text" name="SETUP_FILE_NAME" value="<?=htmlspecialcharsbx(strlen($SETUP_FILE_NAME) > 0 ?
                    $SETUP_FILE_NAME : $settingsService->setupFileName); ?>" size="50"><br><br>
        <span class="text"><?=GetMessage('LOAD_PURCHASE_PRICE')?>&nbsp;</span>
        <input type="checkbox" name="loadPurchasePrice" value="Y" <?=$loadPurchasePrice === 'Y' ? 'checked' : ''?>>
        <br>
        <span class="text"><?=GetMessage('LOAD_NON_ACTIVITY')?>&nbsp;</span>
        <input type="checkbox" name="loadNonActivity" value="Y" <?=$loadNonActivity === 'Y' ? 'checked' : ''?>>
        <br><br><br>
        <?php
        if ($isSetupModulePage) { ?>
        <span class="text"><?=GetMessage('AGENT_LOADING')?>&nbsp;</span>
        <input type="checkbox" name="NEED_CATALOG_AGENT" value="agent" onclick="checkProfile(this);"><br>
        <br>
        <br>
        <span class="text"><?=GetMessage('LOAD_NOW')?>&nbsp;</span>
        <input id="load-now" onchange="checkLoadStatus(this)" type="checkbox" name="LOAD_NOW" value="now">
        <br>
        <div id="loadMessage" hidden><?=GetMessage('LOAD_NOW_MSG')?></div>
        <br>
            <?php
        }?>

        <span class="text"><?=GetMessage('BASE_PRICE')?>&nbsp;</span>
        <select name="price-types" class="typeselect">
            <option value=""></option>
            <?php
            foreach ($priceTypes as $priceType) { ?>
                <option value="<?=$priceType['ID']?>" <?= $priceType['ID'] == $basePriceId ? ' selected' : ''?>>
                    <?=$priceType['NAME']?>
                </option>
                <?php
            } ?>
        </select><br><br><br>
        <?php
        if ($ACTION === 'EXPORT_SETUP' || $ACTION === 'EXPORT_EDIT' || $ACTION === 'EXPORT_COPY') { ?>
            <span class="text"><?=GetMessage('OFFERS_VALUE')?><br><br></span>
            <label>
                <input
                    type="text"
                    name="maxOffersValue"
                    value="<?=htmlspecialchars($maxOffersValue)?>"
                    size="15">
            </label><br><br><br>

            <span class="text"><?=GetMessage('PROFILE_NAME')?><br><br></span>
            <label>
                <input
                    type="text"
                    name="SETUP_PROFILE_NAME"
                    value="<?=htmlspecialchars(strlen($SETUP_PROFILE_NAME) > 0 ?
                    $SETUP_PROFILE_NAME : $settingsService->setupProfileName)?>"
                    size="50">
            </label><br><br><br>
            <?php
        } 
?>
        <?=bitrix_sessid_post()?>
        <?php
        if ($isSetupModulePage) { ?>
            <input type="hidden" name="lang" value="<?= LANG; ?>">
            <input type="hidden" name="id" value="intaro.retailcrm">
            <input type="hidden" name="install" value="Y">
            <input type="hidden" name="step" value="6">
            <input type="hidden" name="continue" value="5">
            <div style="padding: 1px 13px 2px; height:28px;">
                <div align="right" style="float:right; width:50%; position:relative;">
                    <input type="submit" name="inst" onclick="BX.showWait()" value="<?= GetMessage('MOD_NEXT_STEP'); ?>"
                           class="adm-btn-save">
                </div>
                <div align="left" style="float:right; width:50%; position:relative;">
                    <input type="submit" name="back" value="<?= GetMessage('MOD_PREV_STEP'); ?>" class="adm-btn-save">
                </div>
            </div>

        <?php
        } else {?>
            <input type="hidden" name="lang" value="<?=LANGUAGE_ID?>">
            <input type="hidden" name="ACT_FILE" value="<?=htmlspecialcharsbx($_REQUEST['ACT_FILE'])?>">
            <input type="hidden" name="ACTION" value="<?=htmlspecialcharsbx($ACTION)?>">
            <input type="hidden" name="STEP" value="<?=$STEP + 1?>">
            <input type="hidden" name="SETUP_FIELDS_LIST" value="<?=
            $settingsService->getSetupFieldsString(
                $iblockProperties ?? [],
                $hlblockModule === true,
                $hlBlockList ?? []
            )
            ?>">
            <input type="submit" value="<?=($ACTION === 'EXPORT') ? GetMessage('EXPORT') : GetMessage('SAVE')?>">
        <?php
        } ?>
    </form>

    <?php CJSCore::Init(['jquery']);?>

    <script type="text/javascript">
        function checkLoadStatus(object)
        {
            if (object.checked) {
                $('#loadMessage').show();
            } else {
                $('#loadMessage').hide();
            }
        }

        function checkAll(obj, cnt) {
            for (let i = 0; i < cnt; i++) {
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

            for (let i = 0; i < cnt; i++) {
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
                getHlTablesFromController(selectedOption, 'sku', obj.getAttribute('data-type'));
            }

            if (selectedOption.className === 'highloadblock-product') {
                getHlTablesFromController(selectedOption, 'product', obj.getAttribute('data-type'));
            }
        }

        function  setHlFieldsInInstallPage(that, type, key){
            const td         = $(that).parents('td .adm-list-table-cell');
            const select     = $(that).parent('select').siblings('#highloadblock');
            const iblock     = $(that).parents('.iblockExportTable').attr('data-type');
            const sessid  = BX.bitrix_sessid();
            const table_name = $(that).attr('id');
            const step    = $('input[name="continue"]').val();
            const id      = $('input[name="id"]').val();
            const install = $('input[name="install"]').val();
            const data    = 'install=' + install + '&step=' + step + '&sessid=' + sessid +
                '&id=' + id + '&ajax=1&table=' + table_name;

            $.ajax({
                url: '/bitrix/admin/partner_modules.php',
                type: 'POST',
                data: data,
                dataType: "json",
                success: function(res) {
                    $(select).remove();
                    $('#waiting').remove();
                    let new_options = '';
                    $.each(res.fields, function(key, value) {
                        new_options += '<option value="' + value + '">' + value + '</option>';
                    });

                    if (type === 'sku') {
                        $(td).append(getSelect(res, key, iblock, new_options, 'highloadblock'));
                    }

                    if (type === 'product') {
                        $(td).append(getSelect(res, key, iblock, new_options, 'highloadblock_product'));
                    }
                },
                beforeSend: function() {
                    $(td).append('<span style="margin-left:50px;" id="waiting"><?=GetMessage('WAIT')?></span>');
                }
            });
        }

        function setHlFieldsInSettingsPage(that, type, key){
            const td         = $(that).parents('td .adm-list-table-cell');
            const select     = $(that).parent('select').siblings('#highloadblock');
            const table_name = $(that).attr('id');
            const iblock     = $(that).parents('.iblockExportTable').attr('data-type');

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

                    let typeValue = 'highloadblock';

                    if (type === 'product') {
                        typeValue += '_product'
                    }

                    $(td).append(getSelect (response.data, key, iblock, new_options, typeValue));
                }
            );
        }

        function getHlTablesFromController(that, type, key) {
            const url = $('td .adm-list-table-cell').parents('form').attr('action');

            if (url === '/bitrix/admin/partner_modules.php') {
                setHlFieldsInInstallPage(that, type, key);
            } else {
                setHlFieldsInSettingsPage(that, type, key)
            }
        }

        function getSelect (res, key, iblock, new_options, type){
            let select = document.createElement('select');
            let atrName = type + res.table + '_' + key + '[' + iblock + ']';
            select.setAttribute('name', atrName);
            select.setAttribute('id', 'highloadblock');
            select.setAttribute('style','width: 100px; margin-left: 50px;');
            select.innerHTML = new_options;

            return select;
        }
    </script>
    <?php
}

//Сохранение и выход
if ($STEP === 2) {
    RetailcrmConfigProvider::setProfileBasePrice($_REQUEST['PROFILE_ID'], $_POST['price-types']);
    $FINITE = true;
}
?>
