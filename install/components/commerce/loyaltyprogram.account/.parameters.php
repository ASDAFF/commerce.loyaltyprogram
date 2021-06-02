<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

\Bitrix\Main\Loader::includeModule('sale');
$lcur = CCurrency::GetList(($by="name"), ($order="asc"), LANGUAGE_ID);
$currencyList=[''=>'...'];
while($lcur_res = $lcur->Fetch()) {
	$currencyList[$lcur_res["CURRENCY"]]=$lcur_res["FULL_NAME"].' ['.$lcur_res["CURRENCY"].']';
}

$arComponentParameters = [
	"PARAMETERS" => [
		"CACHE_TIME"  => [],
		'CHAIN_BONUSES' => [
			"NAME" => GetMessage("SW24_LOYALTYPROGRAM_CHAIN_BONUSES"),
		],
		'TITLE_BONUSES' => [
			"NAME" => GetMessage("SW24_LOYALTYPROGRAM_TITLE_BONUSES"),
		],
		'CURRENCY'=>[
			"NAME" => GetMessage("SW24_LOYALTYPROGRAM_CURRENCY"),
			"TYPE" => "LIST",
			"PARENT" => "BASE",
			"ADDITIONAL_VALUES" => "N",
			"MULTIPLE" => "N",
			"VALUES"=>$currencyList
		],
		"DISPLAY_PAGER" => [
			"NAME" => GetMessage("SW24_LOYALTYPROGRAM__DISPLAY_PAGER"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "Y",
		],
		"PAGER_TEMPLATE" => [
			"PARENT" => "DETAIL_PAGER_SETTINGS",
			"NAME" => GetMessage("SW24_LOYALTYPROGRAM_PAGER_TEMPLATE"),
			"TYPE" => "STRING",
			"DEFAULT" => "",
		],
		"PAGER_NAME" => [
			"NAME" => GetMessage("SW24_LOYALTYPROGRAM_PAGER_NAME"),
			"TYPE" => "STRING",
			"DEFAULT" => "",
		],
		"PAGER_COUNT" => [
			"NAME" => GetMessage("SW24_LOYALTYPROGRAM_PAGER_COUNT"),
			"TYPE" => "STRING",
			"DEFAULT" => "20",
		],
	]
];
?>
