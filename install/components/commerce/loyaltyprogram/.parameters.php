<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

\Bitrix\Main\Loader::includeModule('sale');
$lcur = CCurrency::GetList(($by="name"), ($order="asc"), LANGUAGE_ID);
$currencyList=[''=>'...'];
while($lcur_res = $lcur->Fetch()) {
	$currencyList[$lcur_res["CURRENCY"]]=$lcur_res["FULL_NAME"].' ['.$lcur_res["CURRENCY"].']';
}

$arComponentParameters = [
	"GROUPS" => [
		"SHARE" => ["NAME" => GetMessage("SW24_LOYALTYPROGRAM_SHARE_REFERRAL")],
		"DESC_ROOM" => ["NAME" => GetMessage("SW24_LOYALTYPROGRAM_DESC_ROOM")],
		"OUTER_FILES" => ["NAME" => GetMessage("SW24_LOYALTYPROGRAM_OUTER_FILES")],
	],
	"PARAMETERS" => [
		'OUTER_ROOM'=>[
			"NAME" => GetMessage("SW24_LOYALTYPROGRAM_OUTER_ROOM"),
			"PARENT" => "OUTER_FILES",
		],
		'OUTER_ACCOUNT'=>[
			"NAME" => GetMessage("SW24_LOYALTYPROGRAM_OUTER_ACCOUNT"),
			"PARENT" => "OUTER_FILES",
		],
		'R_AUTOSHOW'=>[
			"NAME" => GetMessage("SW24_LOYALTYPROGRAM_R_AUTOSHOW"),
			"PARENT" => "DESC_ROOM",
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "Y",
			"REFRESH" => "N",
		],
		'R_LINK_DESC'=>[
			"NAME" => GetMessage("SW24_LOYALTYPROGRAM_R_LINK_DESC"),
			"PARENT" => "DESC_ROOM",
			"DEFAULT" => $_SERVER['SERVER_NAME'],
		],
		'R_DESC_ROOM'=>[
			"NAME" => GetMessage("SW24_LOYALTYPROGRAM_R_DESC_ROOM"),
			"PARENT" => "DESC_ROOM",
			"ROWS" => "10",
			"COLS" => "40",
			"DEFAULT" => GetMessage("SW24_LOYALTYPROGRAM_R_DESC_ROOM_DEF"),
		],
		'R_DESC_LINK'=>[
			"NAME" => GetMessage("SW24_LOYALTYPROGRAM_R_DESC_LINK"),
			"PARENT" => "DESC_ROOM",
			"ROWS" => "10",
			"COLS" => "40",
			"DEFAULT" => GetMessage("SW24_LOYALTYPROGRAM_R_DESC_LINK_DEF"),
		],
		'R_DESC_COUPON'=>[
			"NAME" => GetMessage("SW24_LOYALTYPROGRAM_R_DESC_COUPON"),
			"PARENT" => "DESC_ROOM",
			"ROWS" => "10",
			"COLS" => "40",
			"DEFAULT" => GetMessage("SW24_LOYALTYPROGRAM_R_DESC_COUPON_DEF"),
		],
		"EDIT_COUPON" => [
			"NAME" => GetMessage("SW24_LOYALTYPROGRAM_EDIT_COUPON"),
			"TYPE" => "CHECKBOX",
			"PARENT" => "DESC_ROOM",
			"DEFAULT" => "N",
		],
		"SHOW_QRCODE" => [
			"NAME" => GetMessage("SW24_LOYALTYPROGRAM_SHOW_QRCODE"),
			"TYPE" => "CHECKBOX",
			"PARENT" => "DESC_ROOM",
			"DEFAULT" => "N",
		],
		'QRCODE_LEVEL'=>[
			"NAME" => GetMessage("SW24_LOYALTYPROGRAM_QRCODE_LEVEL"),
			"TYPE" => "LIST",
			"PARENT" => "DESC_ROOM",
			"ADDITIONAL_VALUES" => "N",
			"MULTIPLE" => "N",
			"VALUES"=>[
				'L'=>'L(7%)',
				'M'=>'M(15%)',
				'Q'=>'Q(25%)',
				'H'=>'H(30%)'
			]
		],
		'QRCODE_SIZE'=>[
			"NAME" => GetMessage("SW24_LOYALTYPROGRAM_QRCODE_SIZE"),
			"TYPE" => "STRING",
			"PARENT" => "DESC_ROOM",
			"DEFAULT"=>4
		],
		'QRCODE_MARGIN'=>[
			"NAME" => GetMessage("SW24_LOYALTYPROGRAM_QRCODE_MARGIN"),
			"TYPE" => "STRING",
			"PARENT" => "DESC_ROOM",
			"DEFAULT"=>8
		],
		'VK' => [
			"NAME" => GetMessage("SW24_LOYALTYPROGRAM_SHARE_VK"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "Y",
			"REFRESH" => "N",
			"PARENT" => "SHARE",
		],
		'ODNOKLASSNIKI' => [
			"NAME" => GetMessage("SW24_LOYALTYPROGRAM_SHARE_ODNOKLASSNIKI"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "Y",
			"REFRESH" => "N",
			"PARENT" => "SHARE",
		],
		'FACEBOOK' => [
			"NAME" => GetMessage("SW24_LOYALTYPROGRAM_SHARE_FACEBOOK"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "Y",
			"REFRESH" => "N",
			"PARENT" => "SHARE",
		],
		'TWITTER' => [
			"NAME" => GetMessage("SW24_LOYALTYPROGRAM_SHARE_TWITTER"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "Y",
			"REFRESH" => "N",
			"PARENT" => "SHARE",
		],
		'GOOGLE' => [
			"NAME" => GetMessage("SW24_LOYALTYPROGRAM_SHARE_GOOGLE"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "Y",
			"REFRESH" => "N",
			"PARENT" => "SHARE",
		],
		'MOYMIR' => [
			"NAME" => GetMessage("SW24_LOYALTYPROGRAM_SHARE_MOYMIR"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "Y",
			"REFRESH" => "N",
			"PARENT" => "SHARE",
		],
		'WHATSAPP' => [
			"NAME" => GetMessage("SW24_LOYALTYPROGRAM_SHARE_WHATSAPP"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "Y",
			"REFRESH" => "N",
			"PARENT" => "SHARE",
		],
		'VIBER' => [
			"NAME" => GetMessage("SW24_LOYALTYPROGRAM_SHARE_VIBER"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "Y",
			"REFRESH" => "N",
			"PARENT" => "SHARE",
		],
		'TELEGRAM' => [
			"NAME" => GetMessage("SW24_LOYALTYPROGRAM_SHARE_TELEGRAM"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "Y",
			"REFRESH" => "N",
			"PARENT" => "SHARE",
		],/*
		'EMAIL' => [
			"NAME" => GetMessage("SW24_LOYALTYPROGRAM_SHARE_EMAIL"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "Y",
			"REFRESH" => "N",
			"PARENT" => "SHARE",
		],*/
		"CACHE_TIME"  =>  [],
		'CHAIN_REFERRAL' => [
			"NAME" => GetMessage("SW24_LOYALTYPROGRAM_CHAIN_REFERRAL"),
			"PARENT" => "BASE",
		],
		'TITLE_REFERRAL' => [
			"NAME" => GetMessage("SW24_LOYALTYPROGRAM_TITLE_REFERRAL"),
			"PARENT" => "BASE",
		],
		'CHAIN_BONUSES' => [
			"NAME" => GetMessage("SW24_LOYALTYPROGRAM_CHAIN_BONUSES"),
			"PARENT" => "BASE",
		],
		'TITLE_BONUSES' => [
			"NAME" => GetMessage("SW24_LOYALTYPROGRAM_TITLE_BONUSES"),
			"PARENT" => "BASE",
		],
		'SHOW_USER_FIELDS'=>[
			"NAME" => GetMessage("SW24_LOYALTYPROGRAM_SHOW_USER_FIELDS"),
			"TYPE" => "LIST",
			"PARENT" => "BASE",
			"ADDITIONAL_VALUES" => "N",
			"MULTIPLE" => "Y",
			"VALUES"=>[
				'LOGIN'=>GetMessage("SW24_LOYALTYPROGRAM_SHOW_USER_LOGIN"),
				'NAME'=>GetMessage("SW24_LOYALTYPROGRAM_SHOW_USER_NAME"),
				'SECOND_NAME'=>GetMessage("SW24_LOYALTYPROGRAM_SHOW_USER_SECOND_NAME"),
				'LAST_NAME'=>GetMessage("SW24_LOYALTYPROGRAM_SHOW_USER_LAST_NAME"),
				'EMAIL'=>GetMessage("SW24_LOYALTYPROGRAM_SHOW_USER_EMAIL"),
			]
		],
		'CURRENCY'=>[
			"NAME" => GetMessage("SW24_LOYALTYPROGRAM_CURRENCY"),
			"TYPE" => "LIST",
			"PARENT" => "BASE",
			"ADDITIONAL_VALUES" => "N",
			"MULTIPLE" => "N",
			"VALUES"=>$currencyList
		],
		"VARIABLE_ALIASES" => [
			"REFERRAL" => [
				"NAME" => GetMessage("SW24_LOYALTYPROGRAM_REFERAL_NAME_VAL"),
			],
			"BONUSES" => [
				"NAME" => GetMessage("SW24_LOYALTYPROGRAM_BONUSES_NAME_VAL"),
			],
		],
		"SEF_MODE" => [
			"referral" => [
				"NAME" => GetMessage("SW24_LOYALTYPROGRAM_REFERAL_NAME"),
				"DEFAULT" => "index.php",
				"VARIABLES" => []
			],
			"bonuses" => [
				"NAME" => GetMessage("SW24_LOYALTYPROGRAM_BONUSES_NAME"),
				"DEFAULT" => "/bonuses/",
				"VARIABLES" => ['BONUSES']
			]
		],
		"DISPLAY_PAGER" => [
			"NAME" => GetMessage("SW24_LOYALTYPROGRAM__DISPLAY_PAGER"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "Y",
		],
		"PAGER_TEMPLATE" => [
			"NAME" => GetMessage("SW24_LOYALTYPROGRAM_PAGER_TEMPLATE"),
			"TYPE" => "STRING",
			"DEFAULT" => "",
		],/*
		"PAGER_NAME" => [
			"NAME" => GetMessage("SW24_LOYALTYPROGRAM_PAGER_NAME"),
			"TYPE" => "STRING",
			"DEFAULT" => "",
		],*/
		"PAGER_COUNT" => [
			"NAME" => GetMessage("SW24_LOYALTYPROGRAM_PAGER_COUNT"),
			"TYPE" => "STRING",
			"DEFAULT" => "20",
		],
	]
];
?>
