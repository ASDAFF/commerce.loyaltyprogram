<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$this->setFrameMode(true);
?>
<?$APPLICATION->IncludeComponent(
	"commerce:loyaltyprogram.room",
	"",
	Array(
		"CACHE_TIME" => $arParams['CACHE_TIME'],
		"CACHE_TYPE" => $arParams['CACHE_TYPE'],
		"CHAIN_REFERRAL" => $arParams['CHAIN_REFERRAL'],
		"TITLE_REFERRAL" => $arParams['TITLE_REFERRAL'],
		"SHOW_USER_FIELDS" => $arParams['SHOW_USER_FIELDS'],

		"VK" => $arParams['VK'],
		"ODNOKLASSNIKI" => $arParams['ODNOKLASSNIKI'],
		"FACEBOOK" => $arParams['FACEBOOK'],
		"TWITTER" => $arParams['TWITTER'],
		"GOOGLE" => $arParams['GOOGLE'],
		"MOYMIR" => $arParams['MOYMIR'],
		"WHATSAPP" => $arParams['WHATSAPP'],
		"VIBER" => $arParams['VIBER'],
		"TELEGRAM" => $arParams['TELEGRAM'],
		//"EMAIL" => $arParams['EMAIL']
		
		"R_LINK_DESC" => $arParams['R_LINK_DESC'],
		"R_DESC_ROOM" => $arParams['R_DESC_ROOM'],
		"R_DESC_LINK" => $arParams['R_DESC_LINK'],
		"R_DESC_COUPON" => $arParams['R_DESC_COUPON'],
		"R_DESC_REF" => $arParams['R_DESC_REF'],
		"OUTER_ROOM" => $arParams['OUTER_ROOM'],
		"EDIT_COUPON" => $arParams['EDIT_COUPON'],

		"SHOW_QRCODE" => $arParams['SHOW_QRCODE'],
		"QRCODE_LEVEL" => $arParams['QRCODE_LEVEL'],
		"QRCODE_SIZE" => $arParams['QRCODE_SIZE'],
		"QRCODE_MARGIN" => $arParams['QRCODE_MARGIN']
	)
);?>