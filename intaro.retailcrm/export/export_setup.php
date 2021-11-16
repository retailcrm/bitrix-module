<?

use Bitrix\Highloadblock\HighloadBlockTable;

if (file_exists($_SERVER["DOCUMENT_ROOT"]."/bitrix/php_interface/retailcrm/export_setup.php")){
    require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/php_interface/retailcrm/export_setup.php");
} else {
    if (isset($_POST['ajax']) && $_POST['ajax'] == '1') {
        CModule::IncludeModule('highloadblock');
        $rsData = HighloadBlockTable::getList(['filter' => ['TABLE_NAME' => $_POST['table']]]);
        $hlblockArr = $rsData->Fetch();
        $hlblock = HighloadBlockTable::getById($hlblockArr["ID"])->fetch();
        $entity = HighloadBlockTable::compileEntity($hlblock);
        $hbFields = $entity->getFields();
        $hlblockList['table'] = $hlblockArr["TABLE_NAME"];

        foreach ($hbFields as $hbFieldCode => $hbField) {
            $hlblockList['fields'][] = $hbFieldCode;
        }

        $APPLICATION->RestartBuffer();
        header('Content-Type: application/x-javascript; charset=' . LANG_CHARSET);
        die(json_encode($hlblockList));
    }

    $iblockProperties = array(
        "article" => "article",
        "manufacturer" => "manufacturer",
        "color" =>"color",
        "size" => "size",
        "weight" => "weight",
        "length" => "length",
        "width" => "width",
        "height" => "height",
        "picture" => "picture"
    );

    if (!check_bitrix_sessid()) {
        return;
    }

    __IncludeLang(GetLangFileName($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/intaro.retailcrm/lang/", "/icml_export_setup.php"));

    $MODULE_ID = 'intaro.retailcrm';
    $CRM_CATALOG_BASE_PRICE = 'catalog_base_price';
    $basePriceId = COption::GetOptionString($MODULE_ID, $CRM_CATALOG_BASE_PRICE . '_' . $_REQUEST['PROFILE_ID'], 1);

    $arResult['PRICE_TYPES'] = array();
    $dbPriceType = CCatalogGroup::GetList(
        array("SORT" => "ASC"), array(), array(), array(), array("ID", "NAME", "BASE")
    );

    while ($arPriceType = $dbPriceType->Fetch()) {
        $arResult['PRICE_TYPES'][$arPriceType['ID']] = $arPriceType;
    }

    //highloadblock
    if (CModule::IncludeModule('highloadblock')) {
        $hlblockModule = true;
        $hlblockList = array();
        $hlblockListDb = HighloadBlockTable::getList();

        while ($hlblockArr = $hlblockListDb->Fetch()) {
            $hlblock = HighloadBlockTable::getById($hlblockArr["ID"])->fetch();
            $entity = HighloadBlockTable::compileEntity($hlblock);
            $hbFields = $entity->getFields();
            $hlblockList[$hlblockArr["TABLE_NAME"]]['LABEL'] = $hlblockArr["NAME"];

            foreach ($hbFields as $hbFieldCode => $hbField) {
                $hlblockList[$hlblockArr["TABLE_NAME"]]['FIELDS'][] = $hbFieldCode;
            }
        }
    }

    if (($ACTION == 'EXPORT' || $ACTION == 'EXPORT_EDIT' || $ACTION == 'EXPORT_COPY') && $STEP == 1) {
        if (isset($arOldSetupVars['SETUP_FILE_NAME']))
            $SETUP_FILE_NAME = $arOldSetupVars['SETUP_FILE_NAME'];
        if (isset($arOldSetupVars['LOAD_PURCHASE_PRICE']))
            $LOAD_PURCHASE_PRICE = $arOldSetupVars['LOAD_PURCHASE_PRICE'];
        if (isset($arOldSetupVars['SETUP_PROFILE_NAME']))
            $SETUP_PROFILE_NAME = $arOldSetupVars['SETUP_PROFILE_NAME'];
        if (isset($arOldSetupVars['IBLOCK_EXPORT']))
            $IBLOCK_EXPORT = $arOldSetupVars['IBLOCK_EXPORT'];
        if (isset($arOldSetupVars['IBLOCK_EXPORT']))
            $MAX_OFFERS_VALUE = $arOldSetupVars['MAX_OFFERS_VALUE'];
        $IBLOCK_PROPERTY_SKU = [];
        $IBLOCK_PROPERTY_UNIT_SKU = [];
        foreach ($iblockProperties as $prop) {
            foreach ($arOldSetupVars['IBLOCK_PROPERTY_SKU' . '_' . $prop] as $iblock => $val) {
                $IBLOCK_PROPERTY_SKU[$iblock][$prop] = $val;
            }
            foreach ($arOldSetupVars['IBLOCK_PROPERTY_UNIT_SKU' . '_' . $prop] as $iblock => $val) {
                $IBLOCK_PROPERTY_UNIT_SKU[$iblock][$prop] = $val;
            }
        }

        $IBLOCK_PROPERTY_PRODUCT = [];
        $IBLOCK_PROPERTY_UNIT_PRODUCT = [];
        foreach ($iblockProperties as $prop) {
            foreach ($arOldSetupVars['IBLOCK_PROPERTY_PRODUCT' . '_' . $prop] as $iblock => $val) {
                $IBLOCK_PROPERTY_PRODUCT[$iblock][$prop] = $val;
            }
            foreach ($arOldSetupVars['IBLOCK_PROPERTY_UNIT_PRODUCT' . '_' . $prop] as $iblock => $val) {
                $IBLOCK_PROPERTY_UNIT_PRODUCT[$iblock][$prop] = $val;
            }
        }
    }

    if ($STEP > 1)
    {

        if (strlen($SETUP_FILE_NAME)<=0)
        {
                $arSetupErrors[] = GetMessage("CET_ERROR_NO_FILENAME");
        }
        elseif ($APPLICATION->GetFileAccessPermission($SETUP_FILE_NAME) < "W")
        {
                $arSetupErrors[] = str_replace("#FILE#", $SETUP_FILE_NAME, GetMessage('CET_YAND_RUN_ERR_SETUP_FILE_ACCESS_DENIED'));
        }

        if (($ACTION=="EXPORT_SETUP" || $ACTION == 'EXPORT_EDIT' || $ACTION == 'EXPORT_COPY') && strlen($SETUP_PROFILE_NAME)<=0)
        {
                $arSetupErrors[] = GetMessage("CET_ERROR_NO_PROFILE_NAME");
        }

        if (!empty($arSetupErrors))
        {
                $STEP = 1;
        }
    }

    if (!empty($arSetupErrors))
        echo ShowError(implode('<br />', $arSetupErrors));


    if ($STEP==1) {
    ?>

    <style type="text/css">
        .iblock-export-table-display-none {
            display: none;
        }
    </style>

    <form method="post" action="<?php echo $APPLICATION->GetCurPage(); ?>" >
        <?if ($ACTION == 'EXPORT_EDIT' || $ACTION == 'EXPORT_COPY')
            {
                ?><input type="hidden" name="PROFILE_ID" value="<? echo intval($PROFILE_ID); ?>"><?
            }
            ?>

        <h3><?=GetMessage("SETTINGS_INFOBLOCK");?></h3>
        <font class="text"><?=GetMessage("EXPORT_CATALOGS");?><br><br></font>
        <?
        if (!isset($IBLOCK_EXPORT) || !is_array($IBLOCK_EXPORT))
        {
                $IBLOCK_EXPORT = array();
        }

        $iblockPropertiesName = Array(
            "article" => GetMessage("PROPERTY_ARTICLE_HEADER_NAME"),
            "manufacturer" => GetMessage("PROPERTY_MANUFACTURER_HEADER_NAME"),
            "color" => GetMessage("PROPERTY_COLOR_HEADER_NAME"),
            "size" => GetMessage("PROPERTY_SIZE_HEADER_NAME"),
            "weight" => GetMessage("PROPERTY_WEIGHT_HEADER_NAME"),
            "length" => GetMessage("PROPERTY_LENGTH_HEADER_NAME"),
            "width" => GetMessage("PROPERTY_WIDTH_HEADER_NAME"),
            "height" => GetMessage("PROPERTY_HEIGHT_HEADER_NAME"),
            "picture" => GetMessage("PROPERTY_PICTURE_HEADER_NAME")
        );

        $iblockFieldsName = Array(
            "weight" => Array("code" => "catalog_weight" , "name" => GetMessage("SELECT_WEIGHT_PROPERTY_NAME"), 'unit' => 'mass'),
            "length" => Array("code" => "catalog_length" , "name" => GetMessage("SELECT_LENGTH_PROPERTY_NAME"), 'unit' => 'length'),
            "width" => Array("code" => "catalog_width" , "name" => GetMessage("SELECT_WIDTH_PROPERTY_NAME"), 'unit' => 'length'),
            "height" => Array("code" => "catalog_height" , "name" => GetMessage("SELECT_HEIGHT_PROPERTY_NAME"), 'unit' => 'length'),
        );

        $iblockPropertiesHint = Array(
            "article" => Array("ARTICLE", "ART", "ARTNUMBER", "ARTICUL", "ARTIKUL"),
            "manufacturer" => Array("MANUFACTURER", "PROISVODITEL", "PROISVOD", "PROISV"),
            "color" => Array("COLOR", "CVET"),
            "size" => Array("SIZE", "RAZMER"),
            "weight" => Array("WEIGHT", "VES", "VEC"),
            "length" => Array("LENGTH", "DLINA"),
            "width" => Array("WIDTH", "SHIRINA"),
            "height" => Array("HEIGHT", "VISOTA"),
            "picture" => Array("PICTURE", "PICTURE")
        );

        $units = Array(
            'length' => Array(
                'mm' => GetMessage("UNIT_MEASUREMENT_MM"),
                'cm' => GetMessage("UNIT_MEASUREMENT_CM"),
                'm' => GetMessage("UNIT_MEASUREMENT_M"),
            ),
            'mass' => Array(
                'mg' => GetMessage("UNIT_MEASUREMENT_MG"),
                'g' => GetMessage("UNIT_MEASUREMENT_G"),
                'kg' => GetMessage("UNIT_MEASUREMENT_KG"),
            )
        );

        $hintUnit = Array(
            'length' => 'mm',
            'mass' => 'g'
        );

        $boolAll = false;
        $intCountChecked = 0;
        $intCountAvailIBlock = 0;
        $arIBlockList = array();
        $db_res = CIBlock::GetList(Array("IBLOCK_TYPE"=>"ASC", "NAME"=>"ASC"),array('CHECK_PERMISSIONS' => 'Y','MIN_PERMISSION' => 'W'));

        while ($iblock = $db_res->Fetch())
        {
            if ($arCatalog = CCatalog::GetByIDExt($iblock["ID"]))
            {
                if($arCatalog['CATALOG_TYPE'] == "D" || $arCatalog['CATALOG_TYPE'] == "X" || $arCatalog['CATALOG_TYPE'] == "P")
                {
                    $propertiesSKU = null;
                    if ($arCatalog['CATALOG_TYPE'] == "X" || $arCatalog['CATALOG_TYPE'] == "P")
                    {
                        $iblockOffer = CCatalogSKU::GetInfoByProductIBlock($iblock["ID"]);

                        $db_properties = CIBlock::GetProperties($iblockOffer['IBLOCK_ID'], Array());
                        while($prop = $db_properties->Fetch())
                            $propertiesSKU[] = $prop;

                        $oldPropertySKU = null;
                        if (isset($IBLOCK_PROPERTY_SKU[$iblock['ID']])) {
                            foreach ($iblockPropertiesName as $key => $prop) {
                                $oldPropertySKU[$key] = $IBLOCK_PROPERTY_SKU[$iblock['ID']][$key];
                            }
                        }

                        $oldPropertyUnitSKU = null;
                        if (isset($IBLOCK_PROPERTY_UNIT_SKU[$iblock['ID']])) {
                            foreach ($iblockPropertiesName as $key => $prop) {
                                $oldPropertyUnitSKU[$key] = $IBLOCK_PROPERTY_UNIT_SKU[$iblock['ID']][$key];
                            }
                        }
                    }

                    $propertiesProduct = null;
                    $db_properties = CIBlock::GetProperties($iblock['ID'], Array());
                    while($prop = $db_properties->Fetch())
                        $propertiesProduct[] = $prop;

                    $oldPropertyProduct = null;
                    if (isset($IBLOCK_PROPERTY_PRODUCT[$iblock['ID']])) {
                        foreach ($iblockPropertiesName as $key => $prop) {
                            $oldPropertyProduct[$key] = $IBLOCK_PROPERTY_PRODUCT[$iblock['ID']][$key];
                        }
                    }

                    $oldPropertyUnitProduct = null;
                    if (isset($IBLOCK_PROPERTY_UNIT_PRODUCT[$iblock['ID']])) {
                        foreach ($iblockPropertiesName as $key => $prop) {
                            $oldPropertyUnitProduct[$key] = $IBLOCK_PROPERTY_UNIT_PRODUCT[$iblock['ID']][$key];
                        }
                    }

                    $arSiteList = array();
                    $rsSites = CIBlock::GetSite($iblock["ID"]);
                    while ($arSite = $rsSites->Fetch())
                    {
                        $arSiteList[] = $arSite["SITE_ID"];
                    }

                    if (count($IBLOCK_EXPORT) != 0)
                        $boolExport = (in_array($iblock['ID'], $IBLOCK_EXPORT));
                    else
                        $boolExport = true;

                    $arIBlockList[] = array(
                        'ID' => $iblock['ID'],
                        'NAME' => $iblock['NAME'],
                        'IBLOCK_TYPE_ID' => $iblock['IBLOCK_TYPE_ID'],
                        'IBLOCK_EXPORT' => $boolExport,
                        'PROPERTIES_SKU' => $propertiesSKU,
                        'PROPERTIES_PRODUCT' => $propertiesProduct,
                        'OLD_PROPERTY_SKU_SELECT' => $oldPropertySKU,
                        'OLD_PROPERTY_UNIT_SKU_SELECT' => $oldPropertyUnitSKU,
                        'OLD_PROPERTY_PRODUCT_SELECT' => $oldPropertyProduct,
                        'OLD_PROPERTY_UNIT_PRODUCT_SELECT' => $oldPropertyUnitProduct,
                        'SITE_LIST' => '('.implode(' ',$arSiteList).')',
                    );

                    if ($boolExport)
                            $intCountChecked++;
                    $intCountAvailIBlock++;
                }
            }
        }
        if (count($IBLOCK_EXPORT) != 0) {
            if ($intCountChecked == $intCountAvailIBlock)
                $boolAll = true;
        } else {
            $intCountChecked = $intCountAvailIBlock;
            $boolAll = true;
        }
        ?>

        <font class="text" style="font-weight: bold;"><?=GetMessage("CHECK_ALL_INFOBLOCKS");?></font>
        <input
            style="vertical-align: middle;"
            type="checkbox"
            name="icml_export_all"
            id="icml_export_all"
            value="Y"
            onclick="checkAll(this,<? echo $intCountAvailIBlock; ?>);"
            <? echo ($boolAll ? ' checked' : ''); ?>>
        </br>
        </br>
        <div>
            <? $checkBoxCounter = 0;?>
            <? foreach ($arIBlockList as $key => $arIBlock):?>
            <div>
                <div>
                    <font class="text" style="font-weight: bold;"><? echo htmlspecialcharsex("[".$arIBlock["IBLOCK_TYPE_ID"]."] ".$arIBlock["NAME"]." ".$arIBlock['SITE_LIST']); ?></font>
                    <input
                        type="checkbox"
                        name="IBLOCK_EXPORT[<?=$arIBlock["ID"]?>]"
                        id="IBLOCK_EXPORT<?=++$checkBoxCounter?>"
                        value="<?=$arIBlock["ID"]?>"
                        <? if ($arIBlock['IBLOCK_EXPORT']) echo " checked"; ?>
                        onclick="checkOne(this,<? echo $intCountAvailIBlock; ?>);"
                    >
                </div>
                <br>
                <div id="IBLOCK_EXPORT_TABLE<?=$checkBoxCounter?>" class="IBLOCK_EXPORT_TABLE" data-type="<?=$arIBlock["ID"]?>">
                    <table class="adm-list-table" id="export_setup" <?=($arIBlock['PROPERTIES_SKU'] == null ? 'style="width: 66%;"': "" )?> >
                        <thead>
                            <tr class="adm-list-table-header">
                                <td class="adm-list-table-cell">
                                    <div class="adm-list-table-cell-inner"><?=GetMessage("LOADED_PROPERTY");?></div>
                                </td>
                                <td class="adm-list-table-cell">
                                    <div class="adm-list-table-cell-inner"><?=GetMessage("PROPERTY_PRODUCT_HEADER_NAME");?></div>
                                </td>
                                <? if ($arIBlock['PROPERTIES_SKU'] != null): ?>
                                    <td class="adm-list-table-cell">
                                        <div class="adm-list-table-cell-inner"><?=GetMessage("PROPERTY_OFFER_HEADER_NAME");?></div>
                                    </td>
                                <? endif;?>
                            </tr>
                        </thead>
                        <tbody>

                              <? foreach ($iblockPropertiesName as $key => $property): ?>
                                <? $productSelected = false;?>

                                <tr class="adm-list-table-row">
                                    <td class="adm-list-table-cell">
                                        <? echo htmlspecialcharsex($property); ?>
                                    </td>

                                    <td class="adm-list-table-cell">
                                            <select
                                                style="width: 200px;"
                                                id="IBLOCK_PROPERTY_PRODUCT_<?=$key?><?=$arIBlock["ID"]?>"
                                                name="IBLOCK_PROPERTY_PRODUCT_<?=$key?>[<?=$arIBlock["ID"]?>]"
                                                class="property-export"
                                                data-type="<?=$key?>"
                                                onchange="propertyChange(this);">
                                                    <option value=""></option>
                                                    <?if (version_compare(SM_VERSION, '14.0.0', '>=') && array_key_exists($key, $iblockFieldsName)) :?>
                                                        <optgroup label="<?=GetMessage("SELECT_FIELD_NAME");?>">
                                                            <? foreach ($iblockFieldsName as $keyField => $field): ?>

                                                                <? if ($keyField == $key): ?>
                                                                    <option value="<?=$field['code'];?>"
                                                                        <?
                                                                        if ($arIBlock['OLD_PROPERTY_PRODUCT_SELECT'] != null) {
                                                                            if ($field['code'] == $arIBlock['OLD_PROPERTY_PRODUCT_SELECT'][$key]  ) {
                                                                                echo " selected";
                                                                                $productSelected = true;
                                                                            }
                                                                        } else {
                                                                            foreach ($iblockPropertiesHint[$key] as $hint) {
                                                                                if ($field['code'] == $hint  ) {
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
                                                                <? endif; ?>

                                                            <? endforeach;?>
                                                        </optgroup>
                                                        <optgroup label="<?=GetMessage("SELECT_PROPERTY_NAME");?>">
                                                    <?endif; ?>

                                                    <? foreach ($arIBlock['PROPERTIES_PRODUCT'] as $prop): ?>
                                                        <option value="<?=$prop['CODE'] ?>"
                                                            <?
                                                            if ($prop['USER_TYPE'] == 'directory') {
                                                                echo 'class="highloadblock-product"';
                                                                echo 'id="'. $prop['USER_TYPE_SETTINGS']['TABLE_NAME'] .'"';
                                                            } else {
                                                                echo 'class="not-highloadblock"';
                                                            }
                                                            if ($arIBlock['OLD_PROPERTY_PRODUCT_SELECT'] != null) {
                                                                if ($prop["CODE"] == $arIBlock['OLD_PROPERTY_PRODUCT_SELECT'][$key]  ) {
                                                                    echo " selected";
                                                                    $productSelected = true;
                                                                    if ($prop['USER_TYPE'] == 'directory') {
                                                                        $selected = $prop['USER_TYPE_SETTINGS']['TABLE_NAME'];
                                                                    }
                                                                }
                                                            } else {
                                                                foreach ($iblockPropertiesHint[$key] as $hint) {
                                                                    if ($prop["CODE"] == $hint  ) {
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
                                                    <? endforeach;?>
                                                    <?if (version_compare(SM_VERSION, '14.0.0', '>=')  && array_key_exists($key, $iblockFieldsName)){?>
                                                        </optgroup>
                                                    <?}?>

                                            </select>
                                            <? if (isset($selected)
                                                && isset($arOldSetupVars['highloadblock_product' . $selected . '_' . $key][$arIBlock['ID']])
                                                ) : ?>
                                                <select name="highloadblock_product<?=$selected;?>_<?=$key;?>[<? echo $arIBlock['ID']?>]" id="highloadblock" style="width: 100px; margin-left: 50px;">
                                                    <? foreach ($hlblockList[$selected]['FIELDS'] as $field) : ?>
                                                        <option value="<?=$field;?>"<? if ($arOldSetupVars['highloadblock_product' . $selected . '_' . $key][$arIBlock['ID']] == $field) : echo "selected"; endif; ?>><?=$field;?></option>
                                                    <? endforeach; ?>
                                                </select>
                                            <? endif; ?>
                                            <?if (array_key_exists($key, $iblockFieldsName)) :?>
                                                <select
                                                    style="width: 100px; margin-left: 50px;"
                                                    id="IBLOCK_PROPERTY_UNIT_PRODUCT_<?=$key?><?=$arIBlock["ID"]?>"
                                                    name="IBLOCK_PROPERTY_UNIT_PRODUCT_<?=$key?>[<?=$arIBlock["ID"]?>]"
                                                    >
                                                    <? foreach ($units as $unitTypeName => $unitType): ?>
                                                        <? if ($unitTypeName == $iblockFieldsName[$key]['unit']): ?>
                                                            <? foreach ($unitType as $keyUnit => $unit): ?>
                                                                <option value="<?=$keyUnit;?>"
                                                                    <?
                                                                        if ($arIBlock['OLD_PROPERTY_UNIT_PRODUCT_SELECT'] != null) {
                                                                            if ($keyUnit == $arIBlock['OLD_PROPERTY_UNIT_PRODUCT_SELECT'][$key]  ) {
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
                                                            <? endforeach;?>
                                                        <?endif; ?>
                                                    <? endforeach;?>
                                                </select>
                                            <?endif; ?>
                                    </td>

                                    <? if ($arIBlock['PROPERTIES_SKU'] != null): ?>
                                        <td class="adm-list-table-cell">
                                            <select
                                                style="width: 200px;"
                                                id="IBLOCK_PROPERTY_SKU_<?=$key?><?=$arIBlock["ID"]?>"
                                                name="IBLOCK_PROPERTY_SKU_<?=$key?>[<?=$arIBlock["ID"]?>]"
                                                class="property-export"
                                                data-type="<?=$key?>"
                                                onchange="propertyChange(this);">

                                                    <option value=""></option>
                                                    <?if (version_compare(SM_VERSION, '14.0.0', '>=') && array_key_exists($key, $iblockFieldsName)) :?>
                                                        <optgroup label="<?=GetMessage("SELECT_FIELD_NAME");?>">
                                                            <? foreach ($iblockFieldsName as $keyField => $field): ?>

                                                                <? if ($keyField == $key) :?>
                                                                    <option value="<?=$field['code'];?>"
                                                                        <?
                                                                            if (!$productSelected) {
                                                                                if ($arIBlock['OLD_PROPERTY_SKU_SELECT'] != null) {
                                                                                    if ($field['code'] == $arIBlock['OLD_PROPERTY_SKU_SELECT'][$key]  ) {
                                                                                        echo " selected";
                                                                                    }
                                                                                } else {
                                                                                    foreach ($iblockPropertiesHint[$key] as $hint) {
                                                                                        if ($field['code'] == $hint  ) {
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
                                                                <? endif; ?>

                                                            <? endforeach;?>
                                                        </optgroup>
                                                        <optgroup label="<?=GetMessage("SELECT_PROPERTY_NAME");?>">
                                                    <? endif; ?>

                                                    <? foreach ($arIBlock['PROPERTIES_SKU'] as $prop): ?>
                                                        <option value="<?=$prop['CODE'] ?>"
                                                            <?
                                                            if ($prop['USER_TYPE'] == 'directory') {
                                                                echo 'class="highloadblock"';
                                                                echo 'id="'. $prop['USER_TYPE_SETTINGS']['TABLE_NAME'] .'"';
                                                            } else {
                                                                echo 'class="not-highloadblock"';
                                                            }
                                                            if (!$productSelected) {
                                                                if ($arIBlock['OLD_PROPERTY_SKU_SELECT'] != null) {
                                                                    if ($prop["CODE"] == $arIBlock['OLD_PROPERTY_SKU_SELECT'][$key]  ) {
                                                                        echo " selected";
                                                                        if ($prop['USER_TYPE'] == 'directory') {
                                                                            $selected = $prop['USER_TYPE_SETTINGS']['TABLE_NAME'];
                                                                        }
                                                                    }
                                                                } else {
                                                                    foreach ($iblockPropertiesHint[$key] as $hint) {
                                                                        if ($prop["CODE"] == $hint  ) {
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
                                                    <? endforeach;?>
                                                    <? if (version_compare(SM_VERSION, '14.0.0', '>=')  && array_key_exists($key, $iblockFieldsName)) : ?>
                                                        </optgroup>
                                                    <? endif; ?>
                                            </select>
                                            <? if (isset($selected)
                                                && isset($arOldSetupVars['highloadblock' . $selected . '_' . $key][$arIBlock['ID']])
                                                ) : ?>
                                                <select name="highloadblock<?=$selected;?>_<?=$key;?>[<? echo $arIBlock['ID']?>]" id="highloadblock" style="width: 100px; margin-left: 50px;">
                                                    <? foreach ($hlblockList[$selected]['FIELDS'] as $field) : ?>
                                                        <option value="<?=$field;?>"<? if ($arOldSetupVars['highloadblock' . $selected . '_' . $key][$arIBlock['ID']] == $field) : echo "selected"; endif; ?>><?=$field;?></option>
                                                    <? endforeach; ?>
                                                </select>
                                            <? endif; ?>
                                            <?if (array_key_exists($key, $iblockFieldsName)) :?>
                                                <select
                                                    style="width: 100px; margin-left: 50px;"
                                                    id="IBLOCK_PROPERTY_UNIT_SKU_<?=$key?><?=$arIBlock["ID"]?>"
                                                    name="IBLOCK_PROPERTY_UNIT_SKU_<?=$key?>[<?=$arIBlock["ID"]?>]"
                                                    >
                                                    <? foreach ($units as $unitTypeName => $unitType): ?>
                                                        <? if ($unitTypeName == $iblockFieldsName[$key]['unit']): ?>
                                                            <? foreach ($unitType as $keyUnit => $unit): ?>
                                                                <option value="<?=$keyUnit;?>"
                                                                    <?
                                                                        if ($arIBlock['OLD_PROPERTY_UNIT_SKU_SELECT'] != null) {
                                                                            if ($keyUnit == $arIBlock['OLD_PROPERTY_UNIT_SKU_SELECT'][$key]  ) {
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
                                                            <? endforeach;?>
                                                        <?endif; ?>
                                                    <? endforeach;?>
                                                </select>
                                            <?endif; ?>
                                        </td>

                                    <? endif;?>
                                </tr>

                            <? endforeach;?>
                        </tbody>
                    </table>
                    <br>
                    <br>
                </div>
            </div>


            <? endforeach;?>
        </div>

        <input type="hidden" name="count_checked" id="count_checked" value="<? echo $intCountChecked; ?>">
        <br>

        <h3><?=GetMessage("SETTINGS_EXPORT");?></h3>

        <font class="text"><?=GetMessage("FILENAME");?><br><br></font>
        <input type="text" name="SETUP_FILE_NAME"
               value="<?=htmlspecialcharsbx(strlen($SETUP_FILE_NAME) > 0 ?
                    $SETUP_FILE_NAME :
                    (COption::GetOptionString(
                        'catalog',
                        'export_default_path',
                        '/bitrix/catalog_export/'))
                    .'retailcrm'.'.xml'
                    ); ?>" size="50">
        <br>
        <br>

        <font class="text"><?=GetMessage("LOAD_PURCHASE_PRICE");?>&nbsp;</font>
        <input type="checkbox" name="LOAD_PURCHASE_PRICE" value="Y" <?= $LOAD_PURCHASE_PRICE == 'Y' ? 'checked' : '' ?>>

        <br>
        <br>
        <br>

        <font class="text"><?=GetMessage("BASE_PRICE");?>&nbsp;</font>
        <select name="price-types" class="typeselect">
            <option value=""></option>
            <?php foreach($arResult['PRICE_TYPES'] as $priceType): ?>
                <option value="<?php echo $priceType['ID']; ?>"
                    <?php if($priceType['ID'] == $basePriceId) echo 'selected'; ?>>
                    <?php echo $priceType['NAME']; ?>
                </option>
            <?php endforeach; ?>
        </select>

        <br>
        <br>
        <br>

        <?if ($ACTION=="EXPORT_SETUP" || $ACTION == 'EXPORT_EDIT' || $ACTION == 'EXPORT_COPY'):?>
            <font class="text"><?=GetMessage("OFFERS_VALUE");?><br><br></font>
            <input
                type="text"
                name="MAX_OFFERS_VALUE"
                value="<?echo htmlspecialchars($MAX_OFFERS_VALUE)?>"
                size="15">
            <br>
            <br>
            <br>
        <?endif;?>

        <?if ($ACTION=="EXPORT_SETUP" || $ACTION == 'EXPORT_EDIT' || $ACTION == 'EXPORT_COPY'):?>
            <font class="text"><?=GetMessage("PROFILE_NAME");?><br><br></font>
            <input
                type="text"
                name="SETUP_PROFILE_NAME"
                value="<?echo htmlspecialchars($SETUP_PROFILE_NAME)?>"
                size="50">
            <br>
            <br>
            <br>
        <?endif;?>

        <script type="text/javascript" src="/bitrix/js/main/jquery/jquery-1.7.min.js"></script>
        <script type="text/javascript">
                function checkAll(obj,cnt)
                {
                    for (i = 0; i < cnt; i++)
                    {
                        if (obj.checked)
                            BX.removeClass('IBLOCK_EXPORT_TABLE'+(i+1),"iblock-export-table-display-none");
                    }
                    var table = BX(obj.id.replace('IBLOCK_EXPORT','IBLOCK_EXPORT_TABLE'));
                    if (obj.checked)
                        BX.removeClass(table,"iblock-export-table-display-none");
                    var easing = new BX.easing({
                            duration : 150,
                            start : {opacity : obj.checked ? 0 : 100 },
                            finish : {opacity: obj.checked ? 100 : 0 },
                            transition : BX.easing.transitions.linear,
                            step : function(state){
                                for (i = 0; i < cnt; i++)
                                    {
                                        BX('IBLOCK_EXPORT_TABLE'+(i+1)).style.opacity = state.opacity/100;
                                    }
                            },
                            complete : function() {
                                 for (i = 0; i < cnt; i++)
                                    {
                                        if (!obj.checked)
                                            BX.addClass('IBLOCK_EXPORT_TABLE'+(i+1),"iblock-export-table-display-none");
                                    }
                            }
                    });
                    easing.animate();
                    var boolCheck = obj.checked;
                    for (i = 0; i < cnt; i++)
                    {
                            BX('IBLOCK_EXPORT'+(i+1)).checked = boolCheck;
                    }
                    BX('count_checked').value = (boolCheck ? cnt : 0);
                };
                function checkOne(obj,cnt)
                {
                    var table = BX(obj.id.replace('IBLOCK_EXPORT','IBLOCK_EXPORT_TABLE'));
                    if (obj.checked)
                        BX.removeClass(table,"iblock-export-table-display-none");
                    var easing = new BX.easing({
                            duration : 150,
                            start : {opacity : obj.checked ? 0 : 100 },
                            finish : {opacity: obj.checked ? 100 : 0 },
                            transition : BX.easing.transitions.linear,
                            step : function(state){
                                table.style.opacity = state.opacity/100;
                            },
                            complete : function() {
                                 if (!obj.checked)
                                    BX.addClass(table,"iblock-export-table-display-none");
                            }
                    });
                    easing.animate();
                    var boolCheck = obj.checked;
                    var intCurrent = parseInt(BX('count_checked').value);
                    intCurrent += (boolCheck ? 1 : -1);
                    BX('icml_export_all').checked = (intCurrent < cnt ? false : true);
                    BX('count_checked').value = intCurrent;
                };
                function propertyChange(obj)
                {
                    if (BX(obj.id).value !== 'none') {
                        if (obj.id.indexOf("SKU") !== -1) {
                            BX(obj.id.replace('SKU','PRODUCT')).value = 'none';
                            var bid = obj.id.replace('SKU','PRODUCT');
                            $("#" + bid).siblings('#highloadblock').remove();
                        } else if(BX(obj.id.replace('PRODUCT','SKU'))) {
                            BX(obj.id.replace('PRODUCT','SKU')).value = 'none';
                            var bid = obj.id.replace('PRODUCT','SKU');
                            $("#" + bid).siblings('#highloadblock').remove();
                        }
                    }

                    if ($(obj).find('option')[obj.selectedIndex].className == 'not-highloadblock') {
                        var a = $(obj).find('option')[obj.selectedIndex].parent('select').siblings('#highloadblock');
                        $(a).remove();
                    }

                    if ($(obj).find('option')[obj.selectedIndex].className == 'highloadblock') {
                        getHbFromAjax($(obj).find('option')[obj.selectedIndex], 'sku');
                    }

                    if ($(obj).find('option')[obj.selectedIndex].className == 'highloadblock-product') {
                        getHbFromAjax($(obj).find('option')[obj.selectedIndex], 'product');
                    }
                };

                function getHbFromAjax(that, type) {
                    var url = $('td .adm-list-table-cell').parents('form').attr('action');
                    var get = '<?php echo http_build_query($_GET); ?>';
                    var td = $(that).parents('td .adm-list-table-cell');
                    var select = $(that).parent('select').siblings('#highloadblock');
                    var table_name = $(that).attr('id');
                    var iblock = $(that).parents('.IBLOCK_EXPORT_TABLE').attr('data-type');
                    var key = $(that).parent('select').attr('data-type');

                    $.ajax({
                        url: url + '?' + get,
                        type: 'POST',
                        data: {ajax: '1', table: table_name},
                        dataType: "json",
                        success: function(res) {
                            $(select).remove();
                            $('#waiting').remove();
                            var new_options = '';
                            $.each(res.fields, function(key, value) {
                                new_options += '<option value="' + value + '">' + value + '</option>';
                            });
                            if (type === 'sku') {
                                $(td).append('<select name="highloadblock' + res.table + '_' + key + '[' + iblock + ']" id="highloadblock" style="width: 100px; margin-left: 50px;">' + new_options + '</select>');
                            }
                            if (type === 'product') {
                                $(td).append('<select name="highloadblock_product' + res.table + '_' + key + '[' + iblock + ']" id="highloadblock" style="width: 100px; margin-left: 50px;">' + new_options + '</select>');
                            }
                        },
                        beforeSend: function() {
                            $(td).append('<span style="margin-left:50px;" id="waiting"><?=GetMessage("WAIT")?></span>');
                        }
                    });
                }
        </script>

        <?//Следующие переменные должны быть обязательно установлены?>
        <?=bitrix_sessid_post();?>

        <?
        $vals = "LOAD_PURCHASE_PRICE,SETUP_FILE_NAME,IBLOCK_EXPORT,MAX_OFFERS_VALUE";
        foreach ($iblockProperties as $val) {
            $vals .= ",IBLOCK_PROPERTY_SKU_" . $val;
            $vals .= ",IBLOCK_PROPERTY_UNIT_SKU_" . $val;
            $vals .= ",IBLOCK_PROPERTY_PRODUCT_" . $val;
            $vals .= ",IBLOCK_PROPERTY_UNIT_PRODUCT_" . $val;

            if ($hlblockModule === true && $val != 'picture') {
                foreach ($hlblockList as $hlblockTable => $hlblock) {
                    $vals .= ',highloadblock' . $hlblockTable . '_' . $val;
                }

                foreach ($hlblockList as $hlblockTable => $hlblock) {
                    $vals .= ',highloadblock_product' . $hlblockTable . '_' . $val;
                }
            }
        }

        ?>
        <input type="hidden" name="lang" value="<?echo LANGUAGE_ID ?>">
        <input type="hidden" name="ACT_FILE" value="<?echo htmlspecialcharsbx($_REQUEST["ACT_FILE"]) ?>">
        <input type="hidden" name="ACTION" value="<?echo htmlspecialcharsbx($ACTION) ?>">
        <input type="hidden" name="STEP" value="<?echo intval($STEP) + 1 ?>">
        <input type="hidden" name="SETUP_FIELDS_LIST" value="<? echo $vals ?>">
        <input type="submit" value="<?echo ($ACTION=="EXPORT")?GetMessage("CET_EXPORT"):GetMessage("CET_SAVE")?>">

    </form>

    <?
    }
    elseif ($STEP == 2)
    {
        COption::SetOptionString($MODULE_ID, $CRM_CATALOG_BASE_PRICE . '_' . $_REQUEST['PROFILE_ID'], htmlspecialchars(trim($_POST['price-types'])));
        $FINITE = true;
    }
}
?>
