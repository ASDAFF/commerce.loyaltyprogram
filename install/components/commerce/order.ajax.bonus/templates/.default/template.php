<? if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
use Bitrix\Main\Localization\Loc;
?>
<div style="display:none"><div id="bx-soa-bonus-hidden" class="bx-soa-section"></div></div>
<script>
var commerce_bonus_messages = {
		title:'<?=$arParams['MESS_TITLE']?>',
		bonus:'<?=$arParams['MESS_BONUS']?>',
		no_bonus:'<?=$arParams['MESS_NO_BONUS']?>',
		edit:'<?=$arParams['MESS_EDIT']?>',
		maximum_bonus:'<?=$arParams['MESS_MAX']?>',
		bonus_pay_total:'<?=$arParams['MESS_BONUS_PAY_TOTAL']?>',
		templateFolder:'<?=$templateFolder?>',
		all_bonus:'<?=$arParams['MESS_ALL_BONUS']?>'
	}
	
		
		<?foreach($arParams['currency'] as $key=>$currency){?>
			BX.Currency.setCurrencyFormat('<?=$key?>',<?=CUtil::PhpToJSObject($currency,false,true)?>)		;
		<?}?>
	var commerce_bonus_max = '<?=base64_encode($arParams['MAX_BONUS'])?>';
	var commerce_current_bonus = '<?=$arResult['CURRENT_BONUS']['AMOUNT']?>';
	var commerce_current_bonus_format = '<?=$arResult['CURRENT_BONUS'][0]['AMOUNT_FORMAT']?>';
	BX.message({hasbonusAdded:'<?=Loc::getMessage('commerce.bonus_hasbonusAdded')?>'});
</script>