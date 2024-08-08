<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle(GetMessage('BONUS_ACCOUNT'));
?>

<?php $APPLICATION->IncludeComponent(
    "intaro:lp.score",
    "",
    []
);
?>

<?php require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
