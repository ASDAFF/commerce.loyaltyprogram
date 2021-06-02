<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arComponentParameters = [
	"PARAMETERS" => [
		"CACHE_TIME"  =>  [],
		'R_AUTOSHOW'=>[
			"NAME" => GetMessage("SW24_LOYALTYPROGRAM_R_AUTOSHOW"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "Y",
			"REFRESH" => "N",
		],
		'PATH_REFERRAL' => [
			"NAME" => GetMessage("SW24_LOYALTYPROGRAM_PATH_REFERAL"),
			"SORT" => 10,
		],
		'PATH_ACCOUNT' => [
			"NAME" => GetMessage("SW24_LOYALTYPROGRAM_PATH_ACCOUNT"),
			"SORT" => 10,
		]
	],
];
?>
