<?php
if(!check_bitrix_sessid()) return;
echo CAdminMessage::ShowNote(GetMessage("MOD_INST_OK")); ?>

<form action="<?php echo $APPLICATION->GetCurPage(); ?>">
	<input type="hidden" name="lang" value="<?php echo LANG; ?>">
	<input type="hidden" name="id" value="intaro.crm">
	<input type="hidden" name="install" value="Y">
	<input type="submit" name="" value="<?php echo GetMessage("MOD_BACK"); ?>" class="adm-btn-save">
<form>
