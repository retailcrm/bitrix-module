<?php

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

if (!check_bitrix_sessid()) {
    echo json_encode(['success' => false, 'message' => 'Invalid session']);
    die();
}

if (!CModule::IncludeModule("sale")) {
    echo json_encode(['success' => false, 'message' => 'Модуль sale не подключен']);
    die();
}

use Bitrix\Sale;

global $USER;

$basketItems = [];
$basket = Sale\Basket::loadItemsForFUser(Sale\Fuser::getId(), \Bitrix\Main\Context::getCurrent()->getSite());

if ($_POST['event'] === 'cart') {
    foreach ($basket as $item) {
        $basketItems[] = [
            'id' => $item->getId(),
            'product_id' => $item->getProductId(),
            'quantity' => $item->getQuantity(),
            'price' => $item->getPrice(),
        ];
    }
}

echo json_encode([
    'success' => true,
    'items' => $basketItems,
	'email' => $USER->GetEmail(),
]);
