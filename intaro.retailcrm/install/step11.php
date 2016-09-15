<?php 
    IncludeModuleLangFile(__FILE__);

    if(isset($arResult['errCode']) && $arResult['errCode']) 
        echo CAdminMessage::ShowMessage(GetMessage($arResult['errCode'])); 
?>

<div class="adm-detail-content-item-block">
<form action="<?php echo $APPLICATION->GetCurPage() ?>" method="POST">
    <?php echo bitrix_sessid_post(); ?>
    <input type="hidden" name="lang" value="<?php echo LANGUAGE_ID ?>">
    <input type="hidden" name="id" value="intaro.retailcrm">
    <input type="hidden" name="install" value="Y">
    <input type="hidden" name="step" value="2">

    <table class="adm-detail-content-table edit-table" id="edit1_edit_table">
        <tbody>
            <tr class="heading">
                <td colspan="2">
                    <b><?php echo GetMessage('STEP_NAME'); ?></b>
                </td>
            </tr>
            <tr align="center">
                <td colspan="2"><b><?php echo GetMessage('INFO_1'); ?></b></td>
            </tr>
            <tr align="center">
                <td colspan="2"><b><?php echo GetMessage('INFO_2'); ?></b></td>
            </tr>
            <tr align="center">
                <td colspan="2">&nbsp;</td>
            </tr>
            <?php foreach ($arResult['arSites'] as $site): ?>
            <tr>
                <td width="50%" class="adm-detail-content-cell-l"><?php echo $site['NAME'] . ' (' . $site['LID'] . ')'; ?></td>
                <td width="50%" class="adm-detail-content-cell-r">
                    <select class="typeselect" name="sites-id-<?php echo $site['LID']?>">
                        <option value=""></option>
                        <?php foreach ($arResult['sitesList'] as $sitesList): ?>
                            <option value="<?php echo $sitesList['code'] ?>" <?php if(isset($arResult['SITES_LIST'][$site['LID']]) && $arResult['SITES_LIST'][$site['LID']] == $sitesList['code'])  echo 'selected'; ?>><?php echo $sitesList['name']?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <br />
    <div style="padding: 1px 13px 2px; height:28px;">
        <div align="right" style="float:right; position:relative;">
            <input type="submit" name="inst" value="<?php echo GetMessage("MOD_NEXT_STEP"); ?>" class="adm-btn-save">
        </div>
    </div>
</form>
</div>