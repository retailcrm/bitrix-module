<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("Бонусный счет");
?>

<?php $APPLICATION->IncludeComponent(
    "intaro:lp.score",
    "",
    []
);
?>

<?php require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>