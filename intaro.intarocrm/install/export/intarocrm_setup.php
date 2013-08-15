<?
//<title>IntaroCRM</title>

if(!check_bitrix_sessid()) return;

__IncludeLang(GetLangFileName($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intaro.intarocrm/lang/", "/icml_export_setup.php"));

if (($ACTION == 'EXPORT_EDIT' || $ACTION == 'EXPORT_COPY') && $STEP == 1)
{
	if (isset($arOldSetupVars['SETUP_FILE_NAME']))
		$SETUP_FILE_NAME = $arOldSetupVars['SETUP_FILE_NAME'];
	if (isset($arOldSetupVars['SETUP_PROFILE_NAME']))
		$SETUP_PROFILE_NAME = $arOldSetupVars['SETUP_PROFILE_NAME'];
}


if ($STEP>1)
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


if ($STEP==1)
{
    

?>
<form method="post" action="<?php echo $APPLICATION->GetCurPage(); ?>" >
    <font class="text"><?=GetMessage("EXPORT_CATALOGS");?><br><br></font>
    <?
    if (!isset($IBLOCK_EXPORT) || !is_array($IBLOCK_EXPORT))
    {
            $IBLOCK_EXPORT = array();
    }
    
    $boolAll = false;
    $intCountChecked = 2;
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
                            
                            
                            $boolExport = (in_array($res['ID'],$IBLOCK_EXPORT));
                            $arIBlockList[] = array(
                                    'ID' => $res['ID'],
                                    'NAME' => $res['NAME'],
                                    'IBLOCK_TYPE_ID' => $res['IBLOCK_TYPE_ID'],
                                    'IBLOCK_EXPORT' => $boolExport,
                                    'PROPERTIES' => $properties,
                                    'SITE_LIST' => '('.implode(' ',$arSiteList).')',
                            );
                            
                            if ($boolExport)
                                    $intCountChecked++;
                            $intCountAvailIBlock++;
                    }
            }
    }
    if ($intCountChecked == $intCountAvailIBlock)
            $boolAll = true;
    
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
                                            checked
                                                type="checkbox"
                                                name="IBLOCK_EXPORT[<?=$key?>]"
                                                id="IBLOCK_EXPORT<?=$key?>"
                                                value="<?=$arIBlock["ID"]?>"
                                                <? if ($arIBlock['IBLOCK_EXPORT']) echo " checked"; ?>
                                                onclick="checkOne(this,<? echo $intCountAvailIBlock; ?>);"
                                        >
                                </font>
                        </td>
                        <td class="adm-list-table-cell">
                                <select style="width: 200px;" name="IBLOCK_PROPERTY_ARTICLE[<?=$key?>]" class="property-export">
                                        <?
                                        foreach ($arIBlock['PROPERTIES'] as $prop)
                                        {
                                                ?>
                                                <option value="<?=$prop['CODE'] ?>"
                                                <?if ($prop["CODE"] == "ARTICLE" ||
                                                      $prop["CODE"] == "ART" ||
                                                      $prop["CODE"] == "ARTNUMBER"  )
                                                        echo " selected";?>
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
                                        .'testintarocrm'/* .mt_rand(0, 999999) */.'.xml'
                                        ); ?>" size="50">
                            
    <br>
    <br>
    <br>
    
   
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
            };
    </script>
    
    
    <?//Следующие переменные должны быть обязательно установлены?>
    <?=bitrix_sessid_post();?>
    
    <input type="hidden" name="lang" value="<?echo LANGUAGE_ID ?>">
    <input type="hidden" name="ACT_FILE" value="<?echo htmlspecialcharsbx($_REQUEST["ACT_FILE"]) ?>">
    <input type="hidden" name="ACTION" value="<?echo htmlspecialcharsbx($ACTION) ?>">
    <input type="hidden" name="STEP" value="<?echo intval($STEP) + 1 ?>">
    <input type="hidden" name="SETUP_FIELDS_LIST" value="SETUP_FILE_NAME,IBLOCK_EXPORT,IBLOCK_PROPERTY_ARTICLE">
    <input type="submit" value="<?echo ($ACTION=="EXPORT")?GetMessage("CET_EXPORT"):GetMessage("CET_SAVE")?>">

        
</form>

<?
}
elseif ($STEP==2)
{
	
	$FINITE = true;
}

?>