<? if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
use Bitrix\Main\Localization\Loc;
$inputName=empty($arParams['INPUT_SIMPLE_NAME'])?$arParams['INPUT_SIMPLE_NAME']:'loyalty_bonus';
if($arParams['MAX_BONUS']>0){
?>
<div class="right">
<table class="order_info_tab" id="bx-soa-bonus-2">
	<tr>
		<td><b><?=$arParams['MESS_ALL_BONUS']?></b></td>
		<td><?=$arResult['CURRENT_BONUS']['AMOUNT_FORMAT']?></td>
	</tr>
	<tr>
		<td><b><?=$arParams['MESS_MAX']?></b></td>
		<td><?=$arParams['MAX_BONUS']?> <?=$arResult['CURRENT_BONUS']['CURRENCY_FORMAT']?></td>
	</tr>
	<tr>
		<td><b><?=$arParams['MESS_BONUS']?></b></td>
		<td><label><input type="number" name="<?=$inputName?>" min="0" max="<?=$arParams['MAX_BONUS']?>" step="1" value="0"> <b><?=$arResult['CURRENT_BONUS']['CURRENCY_FORMAT']?></b></label></td>
	</tr>
	<tr class="added_bonus" style="display:none">
		<td><b><?=$arParams['MESS_HASBONUSADDED']?></b></td>
		<td>0 <?=$arResult['CURRENT_BONUS']['CURRENCY_FORMAT']?>!!</td>
	</tr>
</table>
</div>
<div class="clearfix"></div>
<script>

<?foreach($arParams['currency'] as $key=>$currency){?>
	BX.Currency.setCurrencyFormat('<?=$key?>',<?=CUtil::PhpToJSObject($currency,false,true)?>);
<?}?>
BX.ready(function(){
commerceOrderAjaxBonus.init(
	'<?=$arParams['MAX_BONUS']?>',
	'<?=$arResult['CURRENT_BONUS']['CURRENCY']?>',
	'<?=$arResult['BASKET_PRICE']?>',
	'<?=$inputName?>');
})
</script>
<?}?>