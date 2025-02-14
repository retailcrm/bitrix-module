<?php

use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Application;

function update()
{
    Loader::includeModule('sale');
    Loader::includeModule('highloadblock');
    Option::set('intaro.retailcrm', 'api_version', 'v5');

    customFieldsCheck();
    addEventSaveOrder();
    loadJsExport();
}

function loadJsExport()
{
    $pathFrom = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/intaro.retailcrm/install/export/bitrix/js/intaro/export';

    CopyDirFiles(
        $pathFrom,
        $_SERVER['DOCUMENT_ROOT'] . '/bitrix/js/intaro/export/',
        true,
        true,
        false
    );
}

function customFieldsCheck()
{
    $option = Option::get('intaro.retailcrm', 'custom_fields_toggle', null);

    if (!$option) {
        Option::set('intaro.retailcrm', 'custom_fields_toggle', 'N');
    }
}

function addEventSaveOrder()
{
    $loyaltyEventClass = 'Intaro\RetailCrm\Component\Handlers\EventsHandlers';

    $connection = Application::getConnection();
    $sqlHelper = $connection->getSqlHelper();

    $query = sprintf(
        "SELECT COUNT(*) FROM b_module_to_module WHERE FROM_MODULE_ID = '%s' AND TO_MODULE_ID = '%s' AND MESSAGE_ID = '%s' and TO_CLASS = '%s' AND TO_METHOD = '%s'",
        $sqlHelper->forSql('sale'),
        $sqlHelper->forSql('intaro.retailcrm'),
        $sqlHelper->forSql('OnSaleOrderSaved'),
        $sqlHelper->forSql('RetailCrmEvent'),
        $sqlHelper->forSql('orderSave')
    );

    $result = $connection->queryScalar($query);

    if ($result <= 0) {
        RegisterModuleDependences('sale', 'OnSaleOrderSaved', 'intaro.retailcrm', 'RetailCrmEvent', 'orderSave', 99);
    }


    if (Option::get('intaro.retailcrm', 'loyalty_program_toggle') !== 'Y') {
        $query = sprintf(
            "SELECT COUNT(*) FROM b_module_to_module WHERE FROM_MODULE_ID = '%s' AND TO_MODULE_ID = '%s' AND MESSAGE_ID = '%s' and TO_CLASS = '%s' AND TO_METHOD = '%s'",
            $sqlHelper->forSql('sale'),
            $sqlHelper->forSql('intaro.retailcrm'),
            $sqlHelper->forSql('OnSaleOrderSaved'),
            $sqlHelper->forSql($loyaltyEventClass),
            $sqlHelper->forSql('OnSaleOrderSavedHandler')
        );

        $result = $connection->queryScalar($query);

        if ($result > 0) {
            UnRegisterModuleDependences('sale', 'OnSaleOrderSaved', 'intaro.retailcrm', $loyaltyEventClass, 'OnSaleOrderSavedHandler');
        }

        $query = sprintf(
            "SELECT COUNT(*) FROM b_module_to_module WHERE FROM_MODULE_ID = '%s' AND TO_MODULE_ID = '%s' AND MESSAGE_ID = '%s' and TO_CLASS = '%s' AND TO_METHOD = '%s'",
            $sqlHelper->forSql('sale'),
            $sqlHelper->forSql('intaro.retailcrm'),
            $sqlHelper->forSql('OnSaleComponentOrderResultPrepared'),
            $sqlHelper->forSql($loyaltyEventClass),
            $sqlHelper->forSql('OnSaleComponentOrderResultPreparedHandler')
        );

        $result = $connection->queryScalar($query);

        if ($result > 0) {
            UnRegisterModuleDependences('sale', 'OnSaleComponentOrderResultPrepared', 'intaro.retailcrm', $loyaltyEventClass, 'OnSaleComponentOrderResultPreparedHandler');
        }
    }
}

try {
    update();
} catch (\Throwable $exception) {
    print_r($exception->getMessage());

    CEventLog::Add([
        "SEVERITY" => "ERROR",
        "AUDIT_TYPE_ID" => "UPDATE_MODULE",
        "MODULE_ID" => "intaro.retailcrm",
        "DESCRIPTION" => sprintf('Error by processing updater.php: %s', $exception->getMessage()),
    ]);

    return;
}
