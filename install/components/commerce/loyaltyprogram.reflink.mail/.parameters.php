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
				"{#ID#}" => "={#ID#}",
			),
			"ADDITIONAL_VALUES" => "Y",
			"DEFAULT" => Array(
				"{#USER_ID#}" => "{#USER_ID#}"
			),
			#"COLS" => 25,
			"PARENT" => "BASE",
		]
	]
];
?>
