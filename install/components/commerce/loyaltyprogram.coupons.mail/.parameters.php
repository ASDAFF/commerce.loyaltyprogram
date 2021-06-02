<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

\Bitrix\Main\Loader::includeModule('commerce.loyaltyprogram');
$componentsData=new \Commerce\Loyaltyprogram\Components();
$rules=$componentsData->getActiveBasketRules();

$arComponentParameters = [
	"PARAMETERS" => [
		"USER_ID" => [
			"NAME" => GetMessage("SW24_LOYALTYPROGRAM_USER_ID"),
			"TYPE" => "LIST",
			"MULTIPLE" => "N",
			"VALUES" => Array(
				"{#USER_ID#}" => "={#USER_ID#}",
				"{#ID#}" => "={#ID#}",
			),
			"ADDITIONAL_VALUES" => "Y",
			"DEFAULT" => [
				"{#USER_ID#}" => "{#USER_ID#}"
            ],
			"PARENT" => "BASE",
        ],
        "RULES"=>[
			"NAME" => GetMessage("SW24_LOYALTYPROGRAM_RULES_AV"),
			"TYPE" => "LIST",
			"MULTIPLE" => "Y",
			"VALUES" =>$rules,
			"ADDITIONAL_VALUES" => "N",
			"DEFAULT" => [
				"0" => "..."
            ],
			"PARENT" => "BASE",
        ]
	]
];
?>
