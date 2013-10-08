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
    $IBLOCK_PROPERTY_PRODUCT = $oldValues['IBLOCK_PROPERTY_PRODUCT'];
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
        "weight" => GetMessage("PROPERTY_WEIGHT_HEADER_NAME"),
        "size" => GetMessage("PROPERTY_SIZE_HEADER_NAME"),
    );
    
    $iblockPropertiesHint = Array(
        "article" => Array("ARTICLE", "ART", "ARTNUMBER", "ARTICUL", "ARTIKUL"),
        "manufacturer" => Array("MANUFACTURER", "PROISVODITEL", "PROISVOD", "PROISV"),
        "color" => Array("COLOR", "CVET"),
        "weight" => Array("WEIGHT", "VES", "VEC"),
        "size" => Array("SIZE", "RAZMER"),
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
                            'OLD_PROPERTY_PRODUCT_SELECT' => $oldPropertyProduct,
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
                                    </select>
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
                                    </select>
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


