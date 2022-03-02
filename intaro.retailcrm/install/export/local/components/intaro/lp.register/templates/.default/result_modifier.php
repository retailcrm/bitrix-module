<?php

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Intaro\RetailCrm\Component\ConfigProvider;
use Intaro\RetailCrm\Component\Constants;
use Intaro\RetailCrm\Component\ServiceLocator;
use Intaro\RetailCrm\Repository\AgreementRepository;
use Intaro\RetailCrm\Service\CustomerService;
use Intaro\RetailCrm\Service\LoyaltyAccountService;
use RetailCrm\Exception\CurlException;

/** RetailCRM loyalty program start */
function checkLoadIntaro(): bool
{
    try {
        return Loader::includeModule('intaro.retailcrm');
    } catch (LoaderException $e) {
        return false;
    }
}

if (checkLoadIntaro()) {
    $arResult['LOYALTY_STATUS'] = ConfigProvider::getLoyaltyProgramStatus();

    global $USER;

    if ('Y' === $arResult['LOYALTY_STATUS'] && $USER->IsAuthorized()) {
        try {
            /** @var CustomerService $customerService */
            $customerService = ServiceLocator::get(CustomerService::class);
            $customer = $customerService->createModel($USER->GetID());

            $customerService->createCustomer($customer);

            /* @var LoyaltyAccountService $service */
            $service = ServiceLocator::get(LoyaltyAccountService::class);
            $arResult['LP_REGISTER'] = $service->checkRegInLp();
        } catch (CurlException $exception){
            Logger::getInstance()->write($exception->getMessage(), Constants::TEMPLATES_ERROR);

            $arResult['LOYALTY_CONNECTION_ERROR'] = true;
        }
    }

    $arResult['ACTIVATE'] = isset($_GET['activate'])
        && $_GET['activate'] === 'Y'
        && $arResult['LP_REGISTER']['form']['button']['action'] === 'activateAccount';

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
    } catch (ObjectPropertyException | ArgumentException | SystemException $exception) {
        Logger::getInstance()->write($exception->getMessage(), Constants::TEMPLATES_ERROR);
    }

    $arResult['AGREEMENT_PERSONAL_DATA'] = $agreementPersonalData['AGREEMENT_TEXT'];
    $arResult['AGREEMENT_LOYALTY_PROGRAM'] = $agreementLoyaltyProgram['AGREEMENT_TEXT'];
} else {
    AddMessage2Log(GetMessage('INTARO_NOT_INSTALLED'));
}
/** RetailCRM loyalty program end */
