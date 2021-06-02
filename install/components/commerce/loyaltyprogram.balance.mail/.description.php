<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
    "NAME" => GetMessage("SW24_LOYALTYPROGRAM_BALANCE_MAIN_NAME_CHILD"),
    "TYPE" => "mail",
    "DESCRIPTION" => GetMessage("SW24_LOYALTYPROGRAM_BALANCE_MAIN_DESC"),
    "CACHE_PATH" => "Y",
    "PATH" => array(
        "ID" => "commerce",
        "NAME" => GetMessage("SW24_LOYALTYPROGRAM_NAME")
    ),
);
?>