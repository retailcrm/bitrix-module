<?php
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');
$APPLICATION->SetTitle('Регистрация');
?>

<?php $APPLICATION->IncludeComponent(
    'bitrix:main.register',
    'default_loyalty',
    [
        'AUTH'               => 'Y',
        'REQUIRED_FIELDS'    => [],
        'SET_TITLE'          => 'Y',
        'SHOW_FIELDS'        => ['NAME'],
        'SUCCESS_PAGE'       => '',
        'USER_PROPERTY'      => [],
        'USER_PROPERTY_NAME' => '',
        'USE_BACKURL'        => 'Y',
    ]
); ?>

<?php require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'); ?>
