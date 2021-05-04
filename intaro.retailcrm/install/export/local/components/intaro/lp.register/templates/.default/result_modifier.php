<?php

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Intaro\RetailCrm\Component\ConfigProvider;
use Intaro\RetailCrm\Component\ServiceLocator;
use Intaro\RetailCrm\Repository\AgreementRepository;
use Intaro\RetailCrm\Service\CustomerService;
use Intaro\RetailCrm\Service\LoyaltyService;

/** RetailCRM loyalty program start */
try {
    Loader::includeModule('intaro.retailcrm');
    
    $arResult['LOYALTY_STATUS'] = ConfigProvider::getLoyaltyProgramStatus();
    
    global $USER;
    
    if ('Y' === $arResult['LOYALTY_STATUS'] && $USER->IsAuthorized()) {
        /** @var CustomerService $customerService */
        $customerService = ServiceLocator::get(CustomerService::class);
        $customer        = $customerService->createModel($USER->GetID());
        
        $customerService->createCustomer($customer);
        
        /* @var LoyaltyService $service */
        $service = ServiceLocator::get(LoyaltyService::class);
        $arResult['LP_REGISTER'] = $service->checkRegInLp();
    }
    
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
    $arResult['AGREEMENT_PERSONAL_DATA'] = $agreementPersonalData['AGREEMENT_TEXT'];
    $arResult['AGREEMENT_LOYALTY_PROGRAM'] = $agreementLoyaltyProgram['AGREEMENT_TEXT'];
} catch (Throwable $exception) {
    AddMessage2Log($exception->getMessage());
}
/** RetailCRM loyalty program end */
