<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
$this->setFrameMode(true);
?>
<? $APPLICATION->IncludeComponent(
    "commerce:loyaltyprogram.account",
    "",
    [
        "AJAX_MODE" => 'Y',
        //"AJAX_OPTION_JUMP" => "Y",
        "CACHE_TIME" => $arParams['CACHE_TIME'],
        "CACHE_TYPE" => $arParams['CACHE_TYPE'],
        "CHAIN_BONUSES" => $arParams['CHAIN_BONUSES'],
        "TITLE_BONUSES" => $arParams['TITLE_BONUSES'],
        "DISPLAY_PAGER" => $arParams['DISPLAY_PAGER'],
        "PAGER_TEMPLATE" => $arParams['PAGER_TEMPLATE'],
        "PAGER_NAME" => $arParams['PAGER_NAME'],
        "PAGER_COUNT" => $arParams['PAGER_COUNT'],
        "OUTER_ACCOUNT" => $arParams['OUTER_ACCOUNT'],
        "CURRENCY" => $arParams['CURRENCY']
    ]
); ?>