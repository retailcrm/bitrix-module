<?php

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Intaro\RetailCrm\Repository\AgreementRepository;
Loader::includeModule('intaro.retailcrm');

global $USER;
if($USER->IsAuthorized()){
    $rsUser = CUser::GetByID($USER->GetID());
    $arResult['USER_FIELDS'] = $rsUser->Fetch();
    
    if (isset(
        $arResult['USER_FIELDS']['UF_EXT_REG_PL_INTARO'],
        $arResult['USER_FIELDS']['UF_AGREE_PL_INTARO'],
        $arResult['USER_FIELDS']['UF_PD_PROC_PL_INTARO'],
        $arResult['USER_FIELDS']['UF_EXT_REG_PL_INTARO'],
        $arResult['USER_FIELDS']['UF_LP_ID_INTARO']
    )) {
        $arResult['LP_ERRORS'] = true;
        
        AddMessage2Log(GetMessage('LP_FIELDS_NOT_EXIST'));
    }else{
        $arResult['LP_ERRORS'] = false;
    }
}

try {
    $agreementPersonalData = AgreementRepository::getFirstByWhere(
        ['AGREEMENT_TEXT'],
        [
            ['CODE', '=', 'AGREEMENT_PERSONAL_DATA_CODE'],
        ]
    );
    $agreementLoyaltyProgram = AgreementRepository::getFirstByWhere(
        ['AGREEMENT_TEXT'],
        [
            ['CODE', '=', 'AGREEMENT_LOYALTY_PROGRAM_CODE'],
        ]
    );
    $arResult['AGREEMENT_PERSONAL_DATA']   = $agreementPersonalData['AGREEMENT_TEXT'];
    $arResult['AGREEMENT_LOYALTY_PROGRAM'] = $agreementLoyaltyProgram['AGREEMENT_TEXT'];
} catch (ObjectPropertyException | ArgumentException | SystemException $e) {
    AddMessage2Log($e->getMessage());
}
