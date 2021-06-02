<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arComponentParameters = [
	"PARAMETERS" => [
		"CACHE_TIME" => [],
		'CHAIN_REFERRAL' => [
			"NAME" => GetMessage("SW24_LOYALTYPROGRAM_CHAIN_REFERRAL"),
		],
		'TITLE_REFERRAL' => [
			"NAME" => GetMessage("SW24_LOYALTYPROGRAM_TITLE_REFERRAL")
		],
		'SHOW_USER_FIELDS'=>[
			"NAME" => GetMessage("SW24_LOYALTYPROGRAM_SHOW_USER_FIELDS"),
			"TYPE" => "LIST",
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
		"EDIT_COUPON" => [
			"NAME" => GetMessage("SW24_LOYALTYPROGRAM_EDIT_COUPON"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "N",
		],
		"SHOW_QRCODE" => [
			"NAME" => GetMessage("SW24_LOYALTYPROGRAM_SHOW_QRCODE"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "N",
		],
		'QRCODE_LEVEL'=>[
			"NAME" => GetMessage("SW24_LOYALTYPROGRAM_QRCODE_LEVEL"),
			"TYPE" => "LIST",
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
			"DEFAULT"=>4
		],
		'QRCODE_MARGIN'=>[
			"NAME" => GetMessage("SW24_LOYALTYPROGRAM_QRCODE_MARGIN"),
			"TYPE" => "STRING",
			"DEFAULT"=>8
		]
	]
];
?>
