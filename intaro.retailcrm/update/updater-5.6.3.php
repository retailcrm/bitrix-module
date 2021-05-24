<?php

use Bitrix\Main\Config\Option;
use Bitrix\Main\SiteTable;

/**
 * Data migration for multisite settings
 *
 */
function update_5_6_3()
{
    $mid = 'intaro.retailcrm';
    $bdContragentType = 'contragent_type';
    $bdOrderProps = 'order_props';
    $bdOrderTypes = 'order_types_arr';

    $contragentType = Option::get($mid, $bdContragentType);
    $orderProps = Option::get($mid, $bdOrderProps);
    $orderTypes = Option::get($mid, $bdOrderTypes);

    $originalContragentType = unserialize($contragentType);
    $originalOrderProps = unserialize($orderProps);
    $originalOrderTypes = unserialize($orderTypes);

    $newContragentType = [];
    $newOrderProps = [];
    $newOrderTypes = [];

    $rsSite = SiteTable::getList();

    while ($site = $rsSite->fetch()) {
        if (array_key_exists($site["LID"], $originalContragentType)) {
            $newContragentType[$site["LID"]] = $originalContragentType[$site["LID"]];
        } else {
            $newContragentType[$site["LID"]] = $originalContragentType;
        }

        if (array_key_exists($site["LID"], $originalOrderProps)) {
            $newOrderProps[$site["LID"]] = $originalOrderProps[$site["LID"]];
        } else {
            $newOrderProps[$site["LID"]] = $originalOrderProps;
        }

        if (array_key_exists($site["LID"], $originalOrderTypes)) {
            $newOrderTypes[$site["LID"]] = $originalOrderTypes[$site["LID"]];
        } else {
            $newOrderTypes[$site["LID"]] = $originalOrderTypes;
        }
    }

    Option::set($mid, $bdContragentType, serialize($newContragentType));
    Option::set($mid, $bdOrderProps, serialize($newOrderProps));
    Option::set($mid, $bdOrderTypes, serialize($newOrderTypes));
}
