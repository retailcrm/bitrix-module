<?php
if(!check_bitrix_sessid()) return;
echo CAdminMessage::ShowNote(GetMessage("MOD_INST_OK"));
echo GetMessage("INTAROCRM_INFO"); ?>

<form action="<?php echo $APPLICATION->GetCurPage(); ?>">
	<input type="hidden" name="lang" value="<?php echo LANG; ?>">
	<input type="hidden" name="id" value="intaro.retailcrm">
	<input type="hidden" name="install" value="Y">
	<input type="submit" name="" value="<?php echo GetMessage("MOD_BACK"); ?>">
<form>
