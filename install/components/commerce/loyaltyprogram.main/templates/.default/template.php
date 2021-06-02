<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
use Bitrix\Main\Localization\Loc;
?>
<div class="lpNavigation">
	<?if($arParams['R_AUTOSHOW']=='Y' && $arResult['ACTIVE_ROOM']=='N'){
		//hide cabinet
	}else{?>
	<article class="Boyzone">
		<a href="<?=$arResult['PATH_REFERRAL']?>">
			<img src="/bitrix/themes/.default/commerce.loyaltyprogram/images/referral.png">
			<div><?=Loc::getMessage("SW24_LOYALTYPROGRAM_URLPATH_REFERRAL")?></div>
		</a>
	</article>
	<?}?>
	<article class="Turquoise-Topaz">
		<a href="<?=$arResult['PATH_ACCOUNT']?>">
			<img src="/bitrix/themes/.default/commerce.loyaltyprogram/images/bonus.png">
			<div><?=Loc::getMessage("SW24_LOYALTYPROGRAM_URLPATH_BONUSES")?></div>
		</a>
	</article>
</div>