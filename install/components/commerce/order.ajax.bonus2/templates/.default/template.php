<? if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
use Bitrix\Main\Localization\Loc;
?>

<div id="bx-soa-bonus-2" style="display:none;">
	<div class="pay_loyalty_bonus">
		<div class="bx-soa-pp-company-subTitle"><?=$arParams['MESS_BONUS']?></div>
		<div class="bx-soa-pp-company-desc"><?=$arParams['MESS_ALL_BONUS']?><b>#ALL_BONUS#</b></div>
		<div class="bx-soa-pp-company-desc"><?=$arParams['MESS_MAX']?><b>#MAX_BONUS_FORMAT#</b></div>
		<label><input type="number" name="loyalty_bonus" min="0" max="#MAX_BONUS#" step="1" value="#CURRENT_BONUS#"> <b><?=$arResult['CURRENT_BONUS']['CURRENCY_FORMAT']?></b></label>
		<hr>
	</div>
</div>
<script>
BX.message({
	hasbonusAdded:'<?=$arParams['MESS_HASBONUSADDED']?>',
	title:'<?=$arParams['MESS_TITLE']?>',
	bonus:'<?=$arParams['MESS_BONUS']?>',
	no_bonus:'<?=$arParams['MESS_NO_BONUS']?>',
	maximum_bonus:'<?=$arParams['MESS_MAX']?>',
	bonus_pay_total:'<?=$arParams['MESS_BONUS_PAY_TOTAL']?>',
	templateFolder:'<?=$templateFolder?>',
	all_bonus:'<?=$arParams['MESS_ALL_BONUS']?>'
});	
		
<?foreach($arParams['currency'] as $key=>$currency){?>
	BX.Currency.setCurrencyFormat('<?=$key?>',<?=CUtil::PhpToJSObject($currency,false,true)?>);
<?}?>

commerceOrderAjaxBonus.init(
	'<?=base64_encode($arParams['MAX_BONUS'])?>',
	<?=$arResult['CURRENT_BONUS']['AMOUNT']?>,
	'<?=$arResult['CURRENT_BONUS']['AMOUNT_FORMAT']?>',
	'<?=$arResult['CURRENT_BONUS']['CURRENCY']?>');
</script>