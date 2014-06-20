<?php

if(!check_bitrix_sessid()) return;
IncludeModuleLangFile(__FILE__);
__IncludeLang(GetLangFileName($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intaro.intarocrm/lang/", "/icml_export_setup.php"));
?>
<h3><?=GetMessage("EXPORT_CATALOGS_INFO");?></h3>
<?php
if(isset($arResult['errCode']) && $arResult['errCode'])
        echo CAdminMessage::ShowMessage(GetMessage($arResult['errCode']));
global $oldValues;
if (!empty($oldValues)) {
    $IBLOCK_EXPORT = $oldValues['IBLOCK_EXPORT'];
    $IBLOCK_PROPERTY_SKU = $oldValues['IBLOCK_PROPERTY_SKU'];
    $IBLOCK_PROPERTY_UNIT_SKU = $oldValues['IBLOCK_PROPERTY_UNIT_SKU'];
    $IBLOCK_PROPERTY_PRODUCT = $oldValues['IBLOCK_PROPERTY_PRODUCT'];
    $IBLOCK_PROPERTY_UNIT_PRODUCT = $oldValues['IBLOCK_PROPERTY_UNIT_PRODUCT'];
    $SETUP_FILE_NAME = $oldValues['SETUP_FILE_NAME'];
    $SETUP_PROFILE_NAME = $oldValues['SETUP_PROFILE_NAME'];
}
?>


<style type="text/css">
    .iblock-export-table-display-none {
        display: none;
    }
</style>

<form method="post" action="<?php echo $APPLICATION->GetCurPage(); ?>" >            
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
    );
    
    $iblockFieldsName = Array(
        
        "weight" => Array("code" => "catalog_size" , "name" => GetMessage("SELECT_WEIGHT_PROPERTY_NAME"), 'unit' => 'mass'),
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
            <div id="IBLOCK_EXPORT_TABLE<?=$checkBoxCounter?>">
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
                                            onchange="propertyChange(this);">
                                                <option value=""></option>
                                                <?if (version_compare(SM_VERSION, '14.0.0', '>=') && array_key_exists($key, $iblockFieldsName) && $arIBlock['PROPERTIES_SKU'] == null) :?>
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
                                                        if ($arIBlock['OLD_PROPERTY_PRODUCT_SELECT'] != null) {
                                                            if ($prop["CODE"] == $arIBlock['OLD_PROPERTY_PRODUCT_SELECT'][$key]  ) {
                                                                echo " selected";
                                                                $productSelected = true;
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
                                                                        }?>
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
                                                        if (!$productSelected) {
                                                            if ($arIBlock['OLD_PROPERTY_SKU_SELECT'] != null) {
                                                                if ($prop["CODE"] == $arIBlock['OLD_PROPERTY_SKU_SELECT'][$key]  ) {
                                                                    echo " selected";
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


    <font class="text"><?=GetMessage("FILENAME");?><br><br></font>
    <input type="text" name="SETUP_FILE_NAME"
           value="<?=htmlspecialcharsbx(strlen($SETUP_FILE_NAME) > 0 ?
                                        $SETUP_FILE_NAME :
                                        (COption::GetOptionString(
                                            'catalog',
                                            'export_default_path',
                                            '/bitrix/catalog_export/'))
                                        .'intarocrm'/* .mt_rand(0, 999999) */.'.xml'
                                        ); ?>" size="50">

    <br>
    <br>
    <br>

    <font class="text"><?=GetMessage("LOAD_PERIOD");?><br><br></font>
    <input type="radio" name="TYPE_LOADING" value="none" onclick="checkProfile(this);"><?=GetMessage("NOT_LOADING");?><Br>
    <input type="radio" name="TYPE_LOADING" value="cron" onclick="checkProfile(this);"><?=GetMessage("CRON_LOADING");?><Br>
    <input type="radio" name="TYPE_LOADING" value="agent"  checked  onclick="checkProfile(this);"><?=GetMessage("AGENT_LOADING");?><Br>
    <br>
    <br>
    <font class="text"><?=GetMessage("LOAD_NOW");?>&nbsp;</font>
    <input id="load-now" type="checkbox" name="LOAD_NOW" value="now" checked >
    <br>
    <br>
    <font class="text"><?=GetMessage("BASE_PRICE");?>&nbsp;</font>
    <select name="price-types" class="typeselect">
        <option value=""></option>
        <?php foreach($arResult['PRICE_TYPES'] as $priceType): ?>
            <option value="<?php echo $priceType['ID']; ?>"
                <?php if($priceType['BASE'] == 'Y') echo 'selected'; ?>>
                <?php echo $priceType['NAME']; ?>
            </option>
        <?php endforeach; ?>
    </select>
    <br>
    <br>
    <br>

    <div id="profile-field" >
        <font class="text"><?=GetMessage("PROFILE_NAME");?>&nbsp;</font>
        <input
            type="text"
            name="SETUP_PROFILE_NAME"
            value="<?= ($SETUP_PROFILE_NAME ? $SETUP_PROFILE_NAME: GetMessage("PROFILE_NAME_EXAMPLE"));?>"
            size="30">
        <br>
        <br>
        <br>
    </div>

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
                    if (obj.id.indexOf("SKU") !== -1)
                        BX(obj.id.replace('SKU','PRODUCT')).value = 'none';
                    else
                        BX(obj.id.replace('PRODUCT','SKU')).value = 'none';
                }
            };
            function checkProfile(obj)
            {
                if (obj.value !== 'none')
                    $('#profile-field').show();
                else
                    $('#profile-field').hide();
            };
    </script>


    <?//Следующие переменные должны быть обязательно установлены?>
    <?=bitrix_sessid_post();?>

    <input type="hidden" name="lang" value="<?php echo LANG; ?>">
    <input type="hidden" name="id" value="intaro.intarocrm">
    <input type="hidden" name="install" value="Y">
    <input type="hidden" name="step" value="6">
    <input type="hidden" name="continue" value="5">
    <div style="padding: 1px 13px 2px; height:28px;">
        <div align="right" style="float:right; width:50%; position:relative;">
            <input type="submit" name="inst" value="<?php echo GetMessage("MOD_NEXT_STEP"); ?>" class="adm-btn-save">
        </div>
        <div align="left" style="float:right; width:50%; position:relative;">
            <input type="submit" name="back" value="<?php echo GetMessage("MOD_PREV_STEP"); ?>" class="adm-btn-save">
        </div>
    </div>
</form>


