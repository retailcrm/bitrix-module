<?php
use Bitrix\Main\Config\Option;
use Bitrix\Main\SiteTable;

function update_5_6_3()
{
    if (!class_exists('COption')) {
        return;
    }

    $mid    = 'intaro.retailcrm';
    $option = 'contragent_type';
    $contragentType = Option::get($mid, $option);
    $original_array = unserialize($contragentType);
    $arSites = [];

    $rsSite = SiteTable::getList();
    while ($site = $rsSite->fetch()) {
        if (array_key_exists($site["LID"], $original_array)) {
            $arSites[$site["LID"]] = $original_array[$site["LID"]];
        } else {
            $arSites[$site["LID"]] = $original_array;
        }
    }

    Option::set($mid, $option, serialize($arSites));
}
