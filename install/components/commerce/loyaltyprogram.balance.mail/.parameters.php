<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arComponentParameters = [
    "PARAMETERS" => [
        "USER_ID" => [
            "NAME" => GetMessage("SW24_LOYALTYPROGRAM_USER_ID"),
            "TYPE" => "LIST",
            "MULTIPLE" => "N",
            "VALUES" => Array(
                "{#USER_ID#}" => "={#USER_ID#}",
                "{#ORDER_USER_ID#}" => "={#ORDER_USER_ID#}",
                "{#ID#}" => "={#ID#}",
            ),
            "ADDITIONAL_VALUES" => "Y",
            "DEFAULT" => Array(
                "{#USER_ID#}" => "{#USER_ID#}"
            ),
            #"COLS" => 25,
            "PARENT" => "BASE",
        ],
        "ORDER_ID" => [
            "NAME" => GetMessage("SW24_LOYALTYPROGRAM_ORDER_ID"),
            "TYPE" => "LIST",
            "MULTIPLE" => "N",
            "VALUES" => Array(
                "{#ORDER_ID#}" => "={#ORDER_ID#}",
                "{#ORDER_USER_ID#}" => "={#ORDER_USER_ID#}",
                "{#ID#}" => "={#ID#}",
            ),
            "ADDITIONAL_VALUES" => "Y",
            "DEFAULT" => Array(
                "{#ORDER_ID#}" => "{#ORDER_ID#}"
            ),
            #"COLS" => 25,
            "PARENT" => "BASE",
        ],

        "SHOW_LAST_ADD_BONUSES" => [
            "NAME" => GetMessage('SW24_LOYALTYPROGRAM_SHOW_SHOW_LAST_ADD_BONUSES'),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "Y",
            "MULTIPLE" => "N",
            "PARENT" => "BASE",
        ],
        "SHOW_LAST_WRITEOFF_BONUSES" => [
            "NAME" => GetMessage('SW24_LOYALTYPROGRAM_SHOW_SHOW_LAST_WRITEOFF_BONUSES'),
            "TYPE" => "CHECKBOX",
            "DEFAULT" => "Y",
            "MULTIPLE" => "N",
            "PARENT" => "BASE",
        ]
    ]
];
?>