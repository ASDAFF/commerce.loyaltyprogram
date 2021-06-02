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
		"SHOW_REF_LINK" => [
			"NAME" => GetMessage('SW24_LOYALTYPROGRAM_SHOW_REF_LINK'),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "Y",
			"MULTIPLE" => "N",
			"PARENT" => "BASE",
		],
		"SHOW_REF_COUPONS" => [
			"NAME" => GetMessage('SW24_LOYALTYPROGRAM_SHOW_REF_COUPONS'),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "Y",
			"MULTIPLE" => "N",
			"PARENT" => "BASE",
		],
		"SHOW_BONUSES" => [
			"NAME" => GetMessage('SW24_LOYALTYPROGRAM_SHOW_BONUSES'),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "Y",
			"MULTIPLE" => "N",
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
