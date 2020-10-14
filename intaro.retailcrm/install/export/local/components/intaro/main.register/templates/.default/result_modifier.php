<?php

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Intaro\RetailCrm\Repository\AgreementRepository;
Loader::includeModule('intaro.retailcrm');

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


