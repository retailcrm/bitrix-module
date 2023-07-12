<?php

use Intaro\RetailCrm\Service\SubscriberService;

global $USER;

Loc::loadMessages(__FILE__);

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true || !$USER->IsAuthorized()) {
    die();
}

try {
    if (!Loader::includeModule('intaro.retailcrm')) {
        die(GetMessage('MODULE_NOT_INSTALL'));
    }
} catch (Throwable $exception) {
    die(GetMessage('MODULE_NOT_INSTALL') . ': ' . $exception->getMessage());
}

try {
    $arResult["arUser"]["SUBSCRIBE"] = SubscriberService::getSubscribeStatusUser();

    $this->IncludeComponentTemplate();
} catch (\Throwable $exception) {
    $arResult['ERRORS'] = $exception->getMessage();

    $this->IncludeComponentTemplate();
}