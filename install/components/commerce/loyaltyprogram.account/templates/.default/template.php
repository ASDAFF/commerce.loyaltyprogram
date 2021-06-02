<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
use Bitrix\Main\Localization\Loc;
CJSCore::Init(['masked_input']);
$this->setFrameMode(true);

CJSCore::RegisterExt('loyaltyAccount', ['lang'=>$templateFolder.'/lang/'.LANGUAGE_ID.'/template.php']);
CJSCore::Init(['loyaltyAccount']);

?>
<?if(!empty($arResult['ERRORS'])){
	ShowMessage(implode('<br>', $arResult['ERRORS']));
}?>
<div class="lpBonusAccount">
	<div class="soFlexItems">
		<article class="Algal-Fuel">
			<div>
				<?$balance=empty($arResult['ACCOUNTS'])?0:$arResult['ACCOUNTS']['AMOUNT_FORMAT'];?>
				<header><?=Loc::getMessage('sw24_loyaltyprogram.ACCOUNT_TOTAL');?></header>
				<section><?=$balance?></section>
				<footer><a href="#history"><span><?=Loc::getMessage('sw24_loyaltyprogram.VIEW_HISTORY');?></span></a></footer>
			</div>
		</article>
		<article class="Royal-Blue">
			<div>
				<header><?=Loc::getMessage('sw24_loyaltyprogram.ACCOUNT_LAST_TRANSACTION');?></header>
				<?
					$cTransaction=$arResult['LAST_TRANSACTIONS'][0];
					$debit=($cTransaction['DEBIT']=='Y')?'+':'-';
				?>
				<section><?=$debit?> <?=$cTransaction['AMOUNT_FORMAT']?></section>
				<footer><span><?=$cTransaction['TRANSACT_DATE']?></span></footer>
			</div>
		</article>
		<article class="Desire">
			<div>
				<header><?=Loc::getMessage('sw24_loyaltyprogram.ACCOUNT_TRANSACTION_OVERDUE');?></header>	
				<section>
				<?if(count($arResult['OVERDUE'])){?>
				<?=$arResult['OVERDUE']['AMOUNT_FORMAT']?>
				<?} else {?>
				0
				<?}?>
				</section>
				<footer><span><?=$arResult['OVERDUE']['date_remove']?></span></footer>
			</div>
		</article>
		<?if(!empty($arParams['WRITE_OFF_SERVICE']) && $arParams['WRITE_OFF_SERVICE']=='Y'){?>
			<?if($arResult['WRITEOFF']['SUCCESS']>0){?>
			<article class="Orange">
				<div>
					<header><?=Loc::getMessage('sw24_loyaltyprogram.ACCOUNT_TRANSACTION_WRITEOFF');?></header>	
					<section>
					<?if(!empty($arResult['WRITEOFF']['SUCCESS'])){?>
					<?=$arResult['WRITEOFF']['SUCCESS_FORMAT']?>
					<?} else {?>
					0
					<?}?>
					</section>
					<footer><a href="javascript:void(0);" class="writeoff_select"><span><?=Loc::getMessage('sw24_loyaltyprogram.VIEW_HISTORY');?></span></a></footer>
				</div>
			</article>
			<?}?>
			<?if($arResult['WRITEOFF']['AVAILABLE']!==false){?>
				<?if($arResult['WRITEOFF']['AVAILABLE']['BONUS']>0){?>
				<article class="Exodus">
					<div>
						<header><?=Loc::getMessage('sw24_loyaltyprogram.ACCOUNT_TRANSACTION_WRITEOFF_AV');?></header>	
						<section>
						<?if(!empty($arResult['WRITEOFF']['AVAILABLE']) && $arResult['WRITEOFF']['AVAILABLE']['IS_ALREADY_REQUEST']==false){?>
						<?=$arResult['WRITEOFF']['AVAILABLE']['BONUS_FORMAT']?>
						<?} else {?>
						0
						<?}?>
						</section>
						<footer>
							<?if($arResult['WRITEOFF']['AVAILABLE']['IS_ALREADY_REQUEST']){?>
							<span><?=Loc::getMessage('sw24_loyaltyprogram.ACCOUNT_TRANSACTION_WRITEOFF_IS_ALREADY');?></span>
							<?}else{?>
							<a href="javascript:void(0);" class="show_write_form"><span><?=Loc::getMessage('sw24_loyaltyprogram.ACCOUNT_TRANSACTION_WRITEOFF_NOW');?></span></a>
							<?}?>
						</footer>
					</div>
				</article>
				<?}?>
				<?if($arResult['WRITEOFF']['AVAILABLE']['IS_ALREADY_REQUEST']!=false){?>
				<article class="Apple">
					<div>
						<header><?=Loc::getMessage('sw24_loyaltyprogram.ACCOUNT_TRANSACTION_WRITEOFF_LAST_ORDER');?></header>	
						<section><?=$arResult['WRITEOFF']['AVAILABLE']['IS_ALREADY_REQUEST']['BONUS_FORMAT']?></section>
						<footer>
							<span><?=$arResult['WRITEOFF']['AVAILABLE']['IS_ALREADY_REQUEST']['DATE_ORDER']?></span>
						</footer>
					</div>
				</article>
				<?}
			}?>
		<?}?>
	</div>
	
	<?if(!empty($arParams['WRITE_OFF_SERVICE']) && $arParams['WRITE_OFF_SERVICE']=='Y' && $arResult['WRITEOFF']['AVAILABLE']!==false && $arResult['WRITEOFF']['AVAILABLE']['IS_ALREADY_REQUEST']==false){?>
	<form name="write_off" id="write_off" method="post">
		<h3><?=Loc::getMessage('sw24_loyaltyprogram.WRITEOFF_TITLE');?></h3>
        <input type="hidden" name="currency" value="<?=$arResult['WRITEOFF']['AVAILABLE']['CURRENCY']?>">
		<label id="select_cart_area"></label>
		<label>
			<span><?=Loc::getMessage('sw24_loyaltyprogram.WRITEOFF_GET');?></span>
			<input name="bonus" type="number" min="<?=$arResult['WRITEOFF']['AVAILABLE']['MIN_BONUS']?>" max="<?=$arResult['WRITEOFF']['AVAILABLE']['BONUS']?>" value="<?=$arResult['WRITEOFF']['AVAILABLE']['BONUS']?>" />
		</label>
		<button type="submit"><?=Loc::getMessage('sw24_loyaltyprogram.WRITEOFF_SUBMIT');?></button>
	</form>
	
	<div id="requisite_area" style="display:none;">
		<table>
			<thead>
				<tr>
					<td><?=Loc::getMessage('sw24_loyaltyprogram.ACCOUNT_TABLE_TYPE');?></td>
					<td><?=Loc::getMessage('sw24_loyaltyprogram.PAY_CART');?></td>
					<td><?=Loc::getMessage('sw24_loyaltyprogram.PAY_INVOICE');?></td>
					<td><?=Loc::getMessage('sw24_loyaltyprogram.PAY_BIK');?></td>
					<td><?=Loc::getMessage('sw24_loyaltyprogram.PAY_ACTION');?></td>
				</tr>
			</thead>
			<tbody class="list"></tbody>
			<tbody id="add_area_form" style="display:none;">
				<tr>
					<td>
						<label>
							<select name="type_req">
								<option name="type_req" value="cart"><?=Loc::getMessage('sw24_loyaltyprogram.PAY_TYPE_CART');?></option>
								<option name="type_req" value="invoice"><?=Loc::getMessage('sw24_loyaltyprogram.PAY_TYPE_INVOICE');?></option>
							</select>
						</label>
					</td>
					<td><label><input type="text" name="cart" value=""> <?=Loc::getMessage('sw24_loyaltyprogram.PAY_CART');?></label></td>
					<td><label style="display:none;"><input type="text" name="invoice" value=""> <?=Loc::getMessage('sw24_loyaltyprogram.PAY_INVOICE');?></label></td>
					<td><label style="display:none;"><input type="text" name="bik" value=""> <?=Loc::getMessage('sw24_loyaltyprogram.PAY_BIK');?></label></td>
					<td><label><button type="button" id="addRequisiteButton"><?=Loc::getMessage('sw24_loyaltyprogram.ADD_REQUISITE');?></button></label></td>
				</tr>
			</tbody>
		</table>
		<p class="info"></p>
		<div><a href="javascript:void(0);" id="addRequisite"><?=Loc::getMessage('sw24_loyaltyprogram.ADD_REQUISITE');?></a></div>
		<div><a href="javascript:void(0);" id="cancelAddRequisite" style="display:none;"><?=Loc::getMessage('sw24_loyaltyprogram.CANCEL_ADD_REQUISITE');?></a></div>
	</div>
	<?}?>
	
	<a name="history"></a>
	<form action="" name="bonus_filter" id="bonus_filter" method="post">
		<h3><?=Loc::getMessage('sw24_loyaltyprogram.ACCOUNT_YOUR_TRANSACTION');?></h3>
		<div class="periodButtons">
			<a href="javascript:void(0);" class="selectTime" data-period="today"><?=Loc::getMessage('sw24_loyaltyprogram.INTERVAL_TODAY');?></a>
			<a href="javascript:void(0);" class="selectTime" data-period="yesterday"><?=Loc::getMessage('sw24_loyaltyprogram.INTERVAL_YESTERDAY');?></a>
			<a href="javascript:void(0);" class="selectTime" data-period="week"><?=Loc::getMessage('sw24_loyaltyprogram.INTERVAL_WEEK');?></a>
			<a href="javascript:void(0);" class="selectTime" data-period="month"><?=Loc::getMessage('sw24_loyaltyprogram.INTERVAL_MONTH');?></a>
			<a href="javascript:void(0);" class="selectTime" data-period="quarter"><?=Loc::getMessage('sw24_loyaltyprogram.INTERVAL_QUARTER');?></a>
			<a href="javascript:void(0);" class="selectTime" data-period="year"><?=Loc::getMessage('sw24_loyaltyprogram.INTERVAL_YEAR');?></a>
			<a href="javascript:void(0);" class="selectTime" data-period="all"><b><?=Loc::getMessage('sw24_loyaltyprogram.INTERVAL_ALLTIME');?></b></a>
		</div>
		<h4><?=Loc::getMessage('sw24_loyaltyprogram.FORM_INTERVAL');?></h4>

<?$APPLICATION->IncludeComponent(
	"bitrix:main.calendar",
	"",
	Array(
		"FORM_NAME" => "bonus_filter",
		"HIDE_TIMEBAR" => "Y",
		"INPUT_NAME" => "from_date",
		"INPUT_NAME_FINISH" => "to_date",
		"INPUT_VALUE" => (!empty($arParams['FILTER']['from_date']))?$arParams['FILTER']['from_date']:'',
		"INPUT_VALUE_FINISH" => (!empty($arParams['FILTER']['to_date']))?$arParams['FILTER']['to_date']:'',
		"SHOW_INPUT" => "Y",
		"SHOW_TIME" => "N"
	)
);?>
<h4><?=Loc::getMessage('sw24_loyaltyprogram.FORM_TRANSACT');?></h4>
<select name="type_transactions">
	<option value="0"><?=Loc::getMessage('sw24_loyaltyprogram.SELECT_TRANSACT_ALL')?></option>
	<optgroup label="<?=Loc::getMessage('sw24_loyaltyprogram.SELECT_TRANSACT_ADD')?>">
	<?if(!empty($arResult['TRANSACTIONS_TYPE']['acc'])){
		foreach($arResult['TRANSACTIONS_TYPE']['acc'] as $keyTr=>$typeTr){
			$selected=(!empty($arParams['FILTER']['type_transactions']) && $arParams['FILTER']['type_transactions']==$keyTr)?' selected="selected"':'';
			?>
			<option value="<?=$keyTr?>"<?=$selected?>><?=$typeTr?></option>
		<?}
	}?>
	</optgroup>
	<optgroup label="<?=Loc::getMessage('sw24_loyaltyprogram.SELECT_TRANSACT_REMOVE')?>">
	<?if(!empty($arResult['TRANSACTIONS_TYPE']['withdraw'])){
		foreach($arResult['TRANSACTIONS_TYPE']['withdraw'] as $keyTr=>$typeTr){
			$selected=(!empty($arParams['FILTER']['type_transactions']) && $arParams['FILTER']['type_transactions']==$keyTr)?' selected="selected"':'';
			?>
			<option value="<?=$keyTr?>"<?=$selected?>><?=$typeTr?></option>
		<?}
	}?>
	</optgroup>
	<?if(!empty($arResult['TRANSACTIONS_TYPE']['other'])){?>
	<optgroup label="<?=Loc::getMessage('sw24_loyaltyprogram.SELECT_TRANSACT_OTHER')?>">
		<?foreach($arResult['TRANSACTIONS_TYPE']['other'] as $keyTr=>$typeTr){
			$selected=(!empty($arParams['FILTER']['type_transactions']) && $arParams['FILTER']['type_transactions']==$keyTr)?' selected="selected"':'';
			?>
			<option value="<?=$keyTr?>"<?=$selected?>><?=$typeTr?></option>
		<?}?>
	</optgroup>
	<?}?>
</select>
<hr>
<button type="submit"><?=Loc::getMessage('sw24_loyaltyprogram.FORM_FILTER');?></button>
	</form>
	
	<div id="account_area">
		<div class="table-wrapper">
            <table>
                <thead>
                <tr>
                    <?
                    $headList=[
                        'date'=>Loc::getMessage('sw24_loyaltyprogram.ACCOUNT_TABLE_DATA'),
                        'acc'=>Loc::getMessage('sw24_loyaltyprogram.ACCOUNT_TABLE_AMOUNT_PLUS'),
                        'withdraw'=>Loc::getMessage('sw24_loyaltyprogram.ACCOUNT_TABLE_AMOUNT_MINUS'),
                        'desc'=>Loc::getMessage('sw24_loyaltyprogram.ACCOUNT_TABLE_TYPE'),
                        'notes'=>Loc::getMessage('sw24_loyaltyprogram.ACCOUNT_TABLE_DESC'),
                        'order_id'=>Loc::getMessage('sw24_loyaltyprogram.ACCOUNT_TABLE_ORDER_ID'),
                        'date_remove'=>Loc::getMessage('sw24_loyaltyprogram.ACCOUNT_TABLE_DATE_REMOVE')
                    ];

                    foreach($arParams['ORDER'] as $sort=>$order){
                        break;
                    }
                    $arrow=($order=='asc')?'arrow-down':'arrow-up';
                    $order=($order=='asc')?'desc':'asc';
                    foreach($headList as $headKey=>$headVal){
                        $class=($sort==$headKey)?'class="active '.$arrow.'"':'';?>
                        <td><a <?=$class?> href="<?=$APPLICATION->GetCurPageParam("sort=".$headKey."&order=".$order, array("sort", "order"))?>"><?=$headVal;?></a></td>
                    <?}?>
                </tr>
                </thead>
                <tfoot>
                <tr>
                    <td><?=Loc::getMessage('sw24_loyaltyprogram.ACCOUNT_TABLE_TOTAL')?></td>
                    <td class="add_transaction"><?=$arResult['TOTAL_TRANSACTIONS']['add']['AMOUNT_FORMAT']?></td>
                    <td class="remove_transaction"><?=$arResult['TOTAL_TRANSACTIONS']['remove']['AMOUNT_FORMAT']?></td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                </tr>
                </tfoot>
                <tbody>
                <?foreach($arResult['TRANSACTIONS'] as $nextTransaction){
                    $addBonus=$removeBonus='';
                    $typeName=$arResult['TRANSACTIONS_NAME'][$nextTransaction['DESCRIPTION']];
                    if($nextTransaction['DEBIT']=='Y'){
                        $addBonus=$nextTransaction['AMOUNT_FORMAT'];
                        if(!empty($arResult['TRANSACTIONS_NAME']['acc'][$nextTransaction['DESCRIPTION']])){
                            $typeName=$arResult['TRANSACTIONS_NAME']['acc'][$nextTransaction['DESCRIPTION']];
                        }
                    }else{
                        $removeBonus=$nextTransaction['AMOUNT_FORMAT'];
                        if(!empty($arResult['TRANSACTIONS_NAME']['withdraw'][$nextTransaction['DESCRIPTION']])){
                            $typeName=$arResult['TRANSACTIONS_NAME']['withdraw'][$nextTransaction['DESCRIPTION']];
                        }
                    }
                    //$nextTransaction['DESCRIPTION']=$arResult['TRANSACTIONS_TYPE'][$nextTransaction['DESCRIPTION']];
                    $orderId=empty($nextTransaction['ACCOUNT_NUMBER'])?$nextTransaction['ORDER_ID']:$nextTransaction['ACCOUNT_NUMBER'];
                    ?>
                    <tr>
                        <td><?=$nextTransaction['TRANSACT_DATE']?></td>
                        <td class="add_transaction"><?=$addBonus?></td>
                        <td class="remove_transaction"><?=$removeBonus?></td>
                        <td><?=$typeName?></td>
                        <td><?=$nextTransaction['NOTES']?></td>
                        <td><?=$orderId?></td>
                        <td><?=$nextTransaction['date_remove']?></td>
                    </tr>
                <?}?>
                </tbody>
            </table>
        </div>
		<?if($arParams['DISPLAY_PAGER']=='Y'){?>
		<?=$arResult["NAV_STRING"]?>
		<?}?>
	</div>
</div>
<script>
var bonusParams=<?=\Bitrix\Main\Web\Json::encode($arParams);?>;
var tmpltName='<?=$templateName?>';
var pathBonusTemplate='<?=$componentPath?>';

<?if(!empty($arParams['WRITE_OFF_SERVICE']) && $arParams['WRITE_OFF_SERVICE']=='Y' && $arResult['WRITEOFF']['AVAILABLE']!==false && $arResult['WRITEOFF']['AVAILABLE']['IS_ALREADY_REQUEST']==false){?>
managerRequisite.init({
	requisite_area:BX('requisite_area'),
	requisites:<?=\Bitrix\Main\Web\Json::encode($arResult['REQUISITES']);?>
});
<?}?>
</script>
<?if(!empty($arParams['OUTER_ACCOUNT'])){?>
<?
$APPLICATION->IncludeFile($arParams['OUTER_ACCOUNT'], [], [
    "MODE"      => "html",                                 
    "NAME"      => Loc::getMessage('sw24_loyaltyprogram.INCLUDE_FILE')
]);
?>
<?}?>