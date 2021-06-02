<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$this->setFrameMode(true);
?>
<?$APPLICATION->IncludeComponent(
	"commerce:loyaltyprogram.main",
	"",
	Array(
		"CACHE_TIME" => $arParams['CACHE_TIME'],
		"CACHE_TYPE" => $arParams['CACHE_TYPE'],
		"R_AUTOSHOW" => $arParams['R_AUTOSHOW'],
		"PATH_ACCOUNT" => $arResult['FOLDER'].$arResult['URL_TEMPLATES']['bonuses'],
		"PATH_REFERRAL" => $arResult['FOLDER'].$arResult['URL_TEMPLATES']['referral']
	)
);?>