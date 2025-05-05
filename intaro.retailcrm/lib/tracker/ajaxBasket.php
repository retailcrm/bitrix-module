<?php

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

if (!check_bitrix_sessid()) {
    echo json_encode(['success' => false, 'message' => 'Некорректная сессия']);
    return;
}

if (!CModule::IncludeModule('sale')) {
    echo json_encode(['success' => false, 'message' => 'Модуль sale не подключен']);
    return;
}

use Bitrix\Main\Context;
use Bitrix\Sale\Basket;
use Bitrix\Sale\Fuser;

global $USER;

$basketItems = [];

if ($_POST['event'] === 'cart') {
    $basket = Basket::loadItemsForFUser(Fuser::getId(), Context::getCurrent()->getSite());

    foreach ($basket as $item) {
        $basketItems[] = [
            'product_id' => $item->getId(),
            'offer_id' => $item->getProductId(),
            'quantity' => $item->getQuantity(),
            'price' => $item->getPrice(),
            'xml_id' => $item->getField('XML_ID'),
        ];
    }
}

echo json_encode([
    'success' => true,
    'items' => $basketItems,
    'email' => $USER->GetEmail(),
]);
