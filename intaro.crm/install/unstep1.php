<?php IncludeModuleLangFile(__FILE__); ?>
<form action="<?php echo $APPLICATION->GetCurPage(); ?>">
<?php bitrix_sessid_post(); ?>
	<input type="hidden" name="lang" value="<?php echo LANGUAGE_ID; ?>">
	<input type="hidden" name="id" value="intaro.crm">
	<input type="hidden" name="uninstall" value="Y">
	<input type="hidden" name="step" value="2">
	<?php echo CAdminMessage::ShowMessage(GetMessage("MOD_UNINST_WARN")); ?>
	<input type="submit" name="inst" value="<?php echo GetMessage("MOD_UNINST_DEL"); ?>">
</form>
