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
    <input type="hidden" name="step" value="11">

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
            <tr>
                <td width="50%" class="adm-detail-content-cell-l"><?php echo GetMessage('ICRM_API_HOST'); ?></td>
                <td width="50%" class="adm-detail-content-cell-r"><input type="text" id="api_host" name="api_host" value="<?php if(isset($arResult['API_HOST'])) echo $arResult['API_HOST'];?>"></td>
            </tr>
            <tr>
                <td width="50%" class="adm-detail-content-cell-l"><?php echo GetMessage('ICRM_API_KEY'); ?></td>
                <td width="50%" class="adm-detail-content-cell-r"><input type="text" id="api_key" name="api_key" value="<?php if(isset($arResult['API_KEY'])) echo $arResult['API_KEY'];?>"></td>
            </tr>
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