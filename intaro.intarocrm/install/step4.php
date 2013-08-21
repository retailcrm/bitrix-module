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
    $IBLOCK_PROPERTY_ARTICLE = $oldValues['IBLOCK_PROPERTY_ARTICLE'];
    $SETUP_FILE_NAME = $oldValues['SETUP_FILE_NAME'];
    $SETUP_PROFILE_NAME = $oldValues['SETUP_PROFILE_NAME'];
}
?>
<form method="post" action="<?php echo $APPLICATION->GetCurPage(); ?>" >
    <font class="text"><?=GetMessage("EXPORT_CATALOGS");?><br><br></font>
    <?
    if (!isset($IBLOCK_EXPORT) || !is_array($IBLOCK_EXPORT))
    {
            $IBLOCK_EXPORT = array();
    }

    $boolAll = false;
    $intCountChecked = 0;
    $intCountAvailIBlock = 0;
    $arIBlockList = array();
    $db_res = CIBlock::GetList(Array("IBLOCK_TYPE"=>"ASC", "NAME"=>"ASC"),array('CHECK_PERMISSIONS' => 'Y','MIN_PERMISSION' => 'W'));
    while ($res = $db_res->Fetch())
    {
            if ($arCatalog = CCatalog::GetByIDExt($res["ID"]))
            {
                    if($arCatalog['CATALOG_TYPE'] == "D" || $arCatalog['CATALOG_TYPE'] == "X" || $arCatalog['CATALOG_TYPE'] == "P")
                    {
                            $arSiteList = array();
                            $rsSites = CIBlock::GetSite($res["ID"]);
                            while ($arSite = $rsSites->Fetch())
                            {
                                    $arSiteList[] = $arSite["SITE_ID"];
                            }
                            $db_properties = CIBlock::GetProperties($res['ID'], Array());

                            $properties = Array();
                            while($prop = $db_properties->Fetch())
                                    $properties[] = $prop;

                            if (count($IBLOCK_EXPORT) != 0) 
                                $boolExport = (in_array($res['ID'], $IBLOCK_EXPORT));
                            else
                                $boolExport = true;

                            $arIBlockList[] = array(
                                    'ID' => $res['ID'],
                                    'NAME' => $res['NAME'],
                                    'IBLOCK_TYPE_ID' => $res['IBLOCK_TYPE_ID'],
                                    'IBLOCK_EXPORT' => $boolExport,
                                    'PROPERTIES' => $properties,
                                    'OLD_PROPERTY_SELECT' => $IBLOCK_PROPERTY_ARTICLE[$res['ID']] != "" ? $IBLOCK_PROPERTY_ARTICLE[$res['ID']]  : null,
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



    <table class="adm-list-table" id="export_setup">
            <thead>
                    <tr class="adm-list-table-header">
                            <td class="adm-list-table-cell">
                                    <div class="adm-list-table-cell-inner"><?echo GetMessage("CATALOG");?></div>
                            </td>
                            <td class="adm-list-table-cell">
                                    <div class="adm-list-table-cell-inner">
                                            <?echo GetMessage("EXPORT2INTAROCML");?>&nbsp;
                                    </div>
                            </td>
                            <td class="adm-list-table-cell">
                                    <div class="adm-list-table-cell-inner"><?echo GetMessage("PROPERTY");?></div>
                            </td>
                    </tr>
            </thead>
            <tbody>
                    <tr class="adm-list-table-row">
                            <td class="adm-list-table-cell">
                                    <?echo GetMessage("ALL_CATALOG");?>
                            </td>
                            <td class="adm-list-table-cell">
                                    <input style="vertical-align: middle;" type="checkbox" name="icml_export_all" id="icml_export_all" value="Y" onclick="checkAll(this,<? echo $intCountAvailIBlock; ?>);"<? echo ($boolAll ? ' checked' : ''); ?>>

                            </td>
                            <td class="adm-list-table-cell">
                                    &nbsp;
                            </td>
                    </tr>
            <?
            foreach ($arIBlockList as $key => $arIBlock)
            {
            ?>
                    <tr class="adm-list-table-row">
                        <td class="adm-list-table-cell" style="padding-left: 5em">
                                <? echo htmlspecialcharsex("[".$arIBlock["IBLOCK_TYPE_ID"]."] ".$arIBlock["NAME"]." ".$arIBlock['SITE_LIST']); ?>
                        </td>
                        <td class="adm-list-table-cell">
                                <font class="tablebodytext">
                                        <input
                                                type="checkbox"
                                                name="IBLOCK_EXPORT[<?=$arIBlock["ID"]?>]"
                                                id="IBLOCK_EXPORT<?=$arIBlock["ID"]?>"
                                                value="<?=$arIBlock["ID"]?>"
                                                <? if ($arIBlock['IBLOCK_EXPORT']) echo " checked"; ?>
                                                onclick="checkOne(this,<? echo $intCountAvailIBlock; ?>);"
                                        >
                                </font>
                        </td>
                        <td class="adm-list-table-cell">
                                <select 
                                    style="width: 200px;" 
                                    id="IBLOCK_PROPERTY_ARTICLE<?=$arIBlock["ID"]?>"
                                    name="IBLOCK_PROPERTY_ARTICLE[<?=$arIBlock["ID"]?>]" 
                                    class="property-export">
                                        <option value=""></option>
                                        <?
                                        foreach ($arIBlock['PROPERTIES'] as $prop)
                                        {
                                                ?>
                                                <option value="<?=$prop['CODE'] ?>"
                                                <?
                                                if ($arIBlock['OLD_PROPERTY_SELECT'] == $prop["CODE"]){
                                                    echo " selected";
                                                } else {
                                                    if ($prop["CODE"] == "ARTICLE" ||
                                                          $prop["CODE"] == "ART" ||
                                                          $prop["CODE"] == "ARTNUMBER"  ) 
                                                            echo " selected";
                                                }
                                                      
                                                
                                                ?>
                                                >
                                                        <?=$prop["NAME"];?>
                                                </option>
                                                <?
                                        }
                                        ?>
                                </select>
                        </td>
                    </tr>
            <?
            }
            ?>
            </tbody>
    </table>
    <input type="hidden" name="count_checked" id="count_checked" value="<? echo $intCountChecked; ?>">
    <br>
    <br>
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
                    var boolCheck = obj.checked;
                    for (i = 0; i < cnt; i++)
                    {
                            BX('IBLOCK_EXPORT'+i).checked = boolCheck;
                    }
                    BX('count_checked').value = (boolCheck ? cnt : 0);
            };
            function checkOne(obj,cnt)
            {
                var boolCheck = obj.checked;
                var intCurrent = parseInt(BX('count_checked').value);
                intCurrent += (boolCheck ? 1 : -1);
                BX('icml_export_all').checked = (intCurrent < cnt ? false : true);
                BX('count_checked').value = intCurrent;
                if (!boolCheck)
                    BX(obj.id.replace('IBLOCK_EXPORT','IBLOCK_PROPERTY_ARTICLE')).value = 'none';
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
    <input type="hidden" name="step" value="5">
    <input type="hidden" name="continue" value="4">
    <div style="padding: 1px 13px 2px; height:28px;">
        <div align="right" style="float:right; width:50%; position:relative;">
            <input type="submit" name="inst" value="<?php echo GetMessage("MOD_NEXT_STEP"); ?>" class="adm-btn-save">
        </div>
        <div align="left" style="float:right; width:50%; position:relative;">
            <input type="submit" name="back" value="<?php echo GetMessage("MOD_PREV_STEP"); ?>" class="adm-btn-save">
        </div>
    </div>
</form>


