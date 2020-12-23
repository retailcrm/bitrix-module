<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

$arComponentDescription = [
    "NAME"        => GetMessage("COMP_MAIN_USER_REGISTER_TITLE"),
    "DESCRIPTION" => GetMessage("COMP_MAIN_USER_REGISTER_DESCR"),
    "PATH"        => [
        "ID"    => "utility",
        "CHILD" => [
            "ID"   => "user",
            "NAME" => GetMessage("MAIN_USER_GROUP_NAME"),
        ],
    ],
];
