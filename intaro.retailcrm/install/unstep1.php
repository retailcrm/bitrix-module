<?php IncludeModuleLangFile(__FILE__); 
      bitrix_sessid_post(); 
      echo CAdminMessage::ShowNote(GetMessage("MOD_UNINST_OK")); ?>    
    
<form action="<?echo $APPLICATION->GetCurPage()?>">
	<input type="hidden" name="lang" value="<?echo LANG?>">
	<input type="submit" name="" value="<?echo GetMessage("MOD_BACK")?>">	
</form>