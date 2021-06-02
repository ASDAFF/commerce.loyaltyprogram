<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
use Bitrix\Main\Localization\Loc;
$this->setFrameMode(true);
\Bitrix\Main\Page\Asset::getInstance()->addCss("/bitrix/css/main/font-awesome.css");
CJSCore::RegisterExt('loyalty', ['lang'=>'/bitrix/components/commerce/loyaltyprogram.room/templates/.default/lang/'.LANGUAGE_ID.'/template.php']);
CJSCore::Init(['fx', 'loyalty']);
?>
<div class="lpReferralCabinet">
	<?$desc=(!empty($arParams['R_DESC_ROOM']))?htmlspecialchars_decode(htmlspecialchars_decode($arParams['R_DESC_ROOM'])):Loc::getMessage('sw24_loyaltyprogram.REF_DESC');?>
	<div class="refDesc"><?=$desc?></div>
	<?if(!empty($arParams['R_LINK_DESC'])){?>
	<a href="<?=$arParams['R_LINK_DESC']?>" class="refDescLink" target="_blank"><?=Loc::getMessage('sw24_loyaltyprogram.REF_DESC_LINK')?></a>
	<?}?>
<?
$refLink=Loc::getMessage('sw24_loyaltyprogram.REF_LINK_EMPTY');
$refUsers=Loc::getMessage('sw24_loyaltyprogram.REF_USERS_EMPTY');

if(!empty($arResult['REF_LINK']) || count($arResult['COUPONS'])>0 || !empty($arResult['PARTNER_SITE'])){
ob_start();?>
	<div class="lpBindingTools">
		<h2><?=Loc::getMessage('sw24_loyaltyprogram.REF_TOOLS')?></h2>
		
		<?if(!empty($arResult['PARTNER_SITE'])){?>
			<h3><?=Loc::getMessage('sw24_loyaltyprogram.REF_TOOLS_SITES')?></h3>
			<div class="descTools"><?=Loc::getMessage('sw24_loyaltyprogram.REF_TOOLS_SITES_DESC', ['#SITE#'=>SITE_SERVER_NAME])?></div>
			<div class="siteItem">
			<a class="link add_site" href="javascript:void(0);"><?=GetMessage("commerce_loyaltyprogram_ADD_SITE")?></a><br>
			<form style="display:none;" name="add_site">
				<?=bitrix_sessid_post()?>
				<span class="info_error"></span>
				<input type="hidden" name="ajax" value="Y" />
				<input type="text" name="newSite" />
				<button type="submit"><?=GetMessage("commerce_loyaltyprogram_BUTTON_SITE_SUBMIT")?></button>
				<button type="button"><?=GetMessage("commerce_loyaltyprogram_BUTTON_CANCEL")?></button>
			</form>
			<? $tableVisible=count($arResult['PARTNER_SITE']['SITES'])==0?' style="display:none;"':''; ?>
			<table<?=$tableVisible?>>
				<thead>
					<tr>
						<td><?=GetMessage("commerce_loyaltyprogram_TABLE_SITE_SITE")?></td>
						<td><?=GetMessage("commerce_loyaltyprogram_TABLE_SITE_STATUS")?></td>
						<td><?=GetMessage("commerce_loyaltyprogram_TABLE_SITE_DATE_CONFIRMED")?></td>
						<td><?=GetMessage("commerce_loyaltyprogram_TABLE_SITE_ACTION")?></td>
					</tr>
				</thead>
				<tbody><?if(count($arResult['PARTNER_SITE']['SITES'])>0){foreach($arResult['PARTNER_SITE']['SITES'] as $nextSite){
					$status=GetMessage("commerce_loyaltyprogram_TABLE_SITE_STATUS_Y");
					$actionConfirm='';
					$dateConfirm=$nextSite['date_confirm'];
					if($nextSite['confirmed']=='N'){
						$status=GetMessage("commerce_loyaltyprogram_TABLE_SITE_STATUS_N");
						$actionConfirm='<span data-code="'.$nextSite['code'].'" class="fa fa-check" title="'.GetMessage("commerce_loyaltyprogram_ACTION_CONFIRM").'"></span>';
						$dateConfirm='';
					}
					?>
					<tr data-id="<?=$nextSite['id']?>">
						<td><?=$nextSite['site']?></td>
						<td><?=$status?></td>
						<td><?=$dateConfirm?></td>
						<td><?=$actionConfirm?> <span class="fa fa-remove" title="<?=GetMessage("commerce_loyaltyprogram_ACTION_DELETE")?>"></span></td>
					</tr>
				<?}}?></tbody>
			</table>
			</div>
		<?}?>
		
		<?if(!empty($arResult['REF_LINK'])){?>
		<h3><?=Loc::getMessage('sw24_loyaltyprogram.REF_TOOLS_1')?></h3>
		<?$desc=(!empty($arParams['R_DESC_LINK']))?htmlspecialchars_decode(htmlspecialchars_decode($arParams['R_DESC_LINK'])):Loc::getMessage('sw24_loyaltyprogram.REF_TOOLS_1_DESC');?>
		<div class="descTools"><?=$desc?></div>
		<div>
		
			<div class="refLink">
				<span class="perfectPreview" id="url_get"><?=$arResult['REF_LINK']?></span>
				<span class="link" onclick="show_url(this);"><?=GetMessage("commerce_loyaltyprogram.CLICK_ON_THE_LINK")?></span>
				<span class="link sub_url" style="display:none;"><?=GetMessage("commerce_loyaltyprogram.REFERENCE_IS_COPIED")?></span>
				<?if(count($arResult['SOCIAL'])>0){?>
				<span class="link share" onclick="show_share('socialButtons');"><?=Loc::getMessage('sw24_loyaltyprogram.SHARE')?></span>
				<?}?>
			</div>
            <?if(!empty($arResult['QRCODE_IMG'])){?>
                <?=$arResult['QRCODE_IMG'];?>
            <?}?>
        </div>
		
		
		
		<div class="socialButtons" id="socialButtons">
			<?if(isset($arResult['SOCIAL']['VK'])){?>
			<a href="<?=$arResult['SOCIAL']['VK']?>" target="_blank" title="<?=GetMessage("commerce_loyaltyprogram_SHARE_IN_VK")?>"><img src="<?=$templateFolder?>/img/vk.jpg"></a>
			<?}?>
			<?if(isset($arResult['SOCIAL']['ODNOKLASSNIKI'])){?>
			<a href="<?=$arResult['SOCIAL']['ODNOKLASSNIKI']?>" target="_blank" title="<?=GetMessage("commerce_loyaltyprogram_SHARE_IN_OK")?>"><img src="<?=$templateFolder?>/img/odnoklassniki.jpg"></a>
			<?}?>
			<?if(isset($arResult['SOCIAL']['FACEBOOK'])){?>
			<a href="<?=$arResult['SOCIAL']['FACEBOOK']?>" target="_blank" title="<?=GetMessage("commerce_loyaltyprogram_SHARE_IN_FB")?>"><img src="<?=$templateFolder?>/img/faceb.jpg"></a>
			<?}?>
			<?if(isset($arResult['SOCIAL']['TWITTER'])){?>
			<a href="<?=$arResult['SOCIAL']['TWITTER']?>" target="_blank" title="<?=GetMessage("commerce_loyaltyprogram_SHARE_IN_TWITTER")?>"><img src="<?=$templateFolder?>/img/tw.jpg"></a>
			<?}?>
			<?if(isset($arResult['SOCIAL']['MOYMIR'])){?>
			<a href="<?=$arResult['SOCIAL']['MOYMIR']?>" target="_blank" title="<?=GetMessage("commerce_loyaltyprogram_SHARE_IN_MM")?>"><img src="<?=$templateFolder?>/img/mail.jpg"></a>
			<?}?>
			<?if(isset($arResult['SOCIAL']['WHATSAPP'])){?>
			<a href="<?=$arResult['SOCIAL']['WHATSAPP']?>" target="_blank" title="<?=GetMessage("commerce_loyaltyprogram_SHARE_IN_WHATSAPP")?>"><img src="<?=$templateFolder?>/img/whatsapp.jpg"></a>
			<?}?>
			<?if(isset($arResult['SOCIAL']['VIBER'])){?>
			<a href="<?=$arResult['SOCIAL']['VIBER']?>" target="_blank" title="<?=GetMessage("commerce_loyaltyprogram_SHARE_IN_VIBER")?>"><img src="<?=$templateFolder?>/img/viber.jpg"></a>
			<?}?>
			<?if(isset($arResult['SOCIAL']['TELEGRAM'])){?>
			<a href="<?=$arResult['SOCIAL']['TELEGRAM']?>" target="_blank" title="<?=GetMessage("commerce_loyaltyprogram_SHARE_IN_TELEGRAM")?>"><img src="<?=$templateFolder?>/img/telegram.jpg"></a>
			<?}?>
			<?/*if(isset($arResult['SOCIAL']['EMAIL'])){?>
			<a href="javascript:void(0);" onclick="loyaltyEmail('email');"><img src="<?=$templateFolder?>/img/email.jpg" title="<?=GetMessage("commerce_loyaltyprogram_SEND_LINK")?>"></a>
			<?}*/?>
		</div>
		
		<?if(!empty($arResult['STATISTIC']['transfer'])){?>
		<p><b><?=Loc::getMessage('sw24_loyaltyprogram.STAT_transfer')?></b>: <?=$arResult['STATISTIC']['transfer']?></p>
		<?}?>
		<?if(!empty($arResult['STATISTIC']['registration'])){?>
		<p><b><?=Loc::getMessage('sw24_loyaltyprogram.STAT_registration')?></b>: <?=$arResult['STATISTIC']['registration']?></p>
		<?}?>
		<?}?>
	
		
		
		<?if(count($arResult['COUPONS'])>0){?>
			<h3><?=Loc::getMessage('sw24_loyaltyprogram.REF_TOOLS_2')?></h3>
			<?$desc=(!empty($arParams['R_DESC_COUPON']))?htmlspecialchars_decode(htmlspecialchars_decode($arParams['R_DESC_COUPON'])):Loc::getMessage('sw24_loyaltyprogram.REF_TOOLS_2_DESC');?>
			<div class="descTools"><?=$desc?></div>
			<div>
			<?foreach($arResult['COUPONS'] as $keyCoupon=>$nextCoupon){
				if(empty($nextCoupon['COUPON'])){
					continue;
				}?>
				<div class="couponItem">
					<span class="perfectPreview"><?=$nextCoupon['COUPON']?></span>
					<span class="link" onclick="show_url(this);"><?=GetMessage("commerce_loyaltyprogram.CLICK_ON_THE_LINK")?></span>
					<span class="link sub_url" style="display:none;"><?=GetMessage("commerce_loyaltyprogram.COUPON_IS_COPIED")?></span>
					<?if(!empty($arParams['EDIT_COUPON']) && $arParams['EDIT_COUPON']=='Y' && $nextCoupon['TYPE']=='user_prop'){?>
						<a class="link edit_coupon" onclick="show_edit_coupon(this)" href="javascript:void(0);"><?=GetMessage("commerce_loyaltyprogram_EDIT_COUPON")?></a>
						<form name="edit_coupon" style="display:none;">
							<?=bitrix_sessid_post()?>
							<span class="info_error"></span>
							<input type="hidden" name="ajax" value="Y" />
							<input type="hidden" name="old_name" value="<?=$nextCoupon['COUPON']?>" />
							<input type="hidden" name="rule_id" value="<?=$keyCoupon?>" />
							<input type="text" name="newCoupon" />
							<button type="submit"><?=GetMessage("commerce_loyaltyprogram_BUTTON_SUBMIT")?></button>
							<button type="button"><?=GetMessage("commerce_loyaltyprogram_BUTTON_CANCEL")?></button>
						</form><br>
					<?}?>
					<?if(!empty($nextCoupon['DESCRIPTION'])){?>
						<span class="link" onclick="show_desc(this);" data-hide="<?=GetMessage("commerce_loyaltyprogram_CLICK_DESCRIPTION_HIDE")?>" data-show="<?=GetMessage("commerce_loyaltyprogram_CLICK_DESCRIPTION_SHOW")?>"><?=GetMessage("commerce_loyaltyprogram_CLICK_DESCRIPTION_SHOW")?></span>
						<div style="display: none;"><?=$nextCoupon['DESCRIPTION']?></div>
					<?}?>
				</div>
				<?if(!empty($arResult['STATISTIC']['COUPONS'][$nextCoupon['COUPON']])){?>
				<p><b><?=Loc::getMessage('sw24_loyaltyprogram.STAT_orders')?></b>: <?=$arResult['STATISTIC']['COUPONS'][$nextCoupon['COUPON']]['orders']?></p>
				<br>
				<?}?>
			<?}?>
			</div>
		<?}else{?>
			<?//=Loc::getMessage('sw24_loyaltyprogram.REF_TOOLS_NO_ACTIVE')?>
		<?}?>
		
		<?if(isset($arResult['SOCIAL']['EMAIL'])){?>
		<?=bitrix_sessid_post()?>
		<script>
			var emailSRC='<?=$componentPath?>/ajax.php';
			var buttonName='<?=Loc::getMessage('commerce_loyaltyprogram_SEND')?>';
			var errorMessage='<?=Loc::getMessage('commerce_loyaltyprogram_ERRORMESSAGE')?>';
			var successMessage='<?=Loc::getMessage('commerce_loyaltyprogram_SUCCESSMESSAGE')?>';
		</script>
		<?}?>
	</div>
<?$refLink = ob_get_contents();
ob_end_clean();
}
?>

<?
if(!empty($arResult['CHAIN']) && count($arResult['CHAIN'])>0){
ob_start();?>
	<h2><?=Loc::getMessage('sw24_loyaltyprogram.REF_CHAIN')?></h2>
	<?$desc=(!empty($arParams['R_DESC_REF']))?htmlspecialchars_decode(htmlspecialchars_decode($arParams['R_DESC_REF'])):Loc::getMessage('sw24_loyaltyprogram.REF_CHAIN_DESC');?>
	<div class="descRef"><?=$desc?></div>
  <table class="ref_table">
	<thead><tr>
		<td><?=Loc::getMessage('sw24_loyaltyprogram.TABLE_REF_NAME')?></td>
		<td><?=Loc::getMessage('sw24_loyaltyprogram.TABLE_REF_LEVEL')?></td>
		<td><?=Loc::getMessage('sw24_loyaltyprogram.TABLE_REF_TYPE')?></td>
		<td><?=Loc::getMessage('sw24_loyaltyprogram.TABLE_REF_SOURCE')?></td>
		<td><?=Loc::getMessage('sw24_loyaltyprogram.TABLE_REF_DATE')?></td>
		<td><?=Loc::getMessage('sw24_loyaltyprogram.TABLE_REF_TOTAL')?></td>
	</tr></thead>
	<tbody>
		<?foreach($arResult['CHAIN'] as $nextChain){
			$bonuses=(!empty($arResult['BONUSES'][$nextChain['user']]))?$arResult['BONUSES'][$nextChain['user']]:['bonus'=>0, 'currency'=>$arResult['CURRENCY']];
			?>
			<tr><td><?=str_repeat(" > ", ($nextChain['level']-1)).$nextChain['name']?></td>
			<td><?=$nextChain['level']?></td>
			<?
			$name=$nextChain['type'];
			if ($nextChain['type']=='link'){
				$name=Loc::getMessage('sw24_loyaltyprogram.TABLE_REF_TYPE_LINK');
			} elseif($nextChain['type']=='coupon') {
				$name=Loc::getMessage('sw24_loyaltyprogram.TABLE_REF_TYPE_COUPON');
			}elseif($nextChain['type']=='manual'){
				$name=Loc::getMessage('sw24_loyaltyprogram.TABLE_REF_TYPE_MANUAL');
			}elseif($nextChain['type']=='partnerSite'){
				$name=Loc::getMessage('sw24_loyaltyprogram.TABLE_REF_TYPE_PARTNERSITE');
			}elseif($nextChain['type']=='import'){
				$name=Loc::getMessage('sw24_loyaltyprogram.TABLE_REF_TYPE_IMPORT');
			}?>
			<td>
				<?=$name?>
			</td>
			<td><?=$nextChain['source']?></td>
			<td data-sort="<?=MakeTimeStamp($nextChain['date_create'])?>"><?=$nextChain['date_create']?></td>
			<td data-sort="<?=$bonuses['bonus']?>"><?=CurrencyFormat($bonuses['bonus'], $bonuses['currency'])?></td></tr>
		<?}?>
	</tbody></table>
	<?if(!empty($arResult['TOTAL_LEVEL_CHAIN']) && count($arResult['TOTAL_LEVEL_CHAIN'])>0){?>
		<div class="total_ref">
		<?foreach($arResult['TOTAL_LEVEL_CHAIN'] as $keyChain=>$valueChain){?>
			<p><?=Loc::getMessage('sw24_loyaltyprogram.REFERRALS_LEVEL', ['#ID#'=>$keyChain])?>: <b><?=$valueChain['count']?></b>, <?=Loc::getMessage('sw24_loyaltyprogram.REFERRALS_TOTAL')?>: <b><?=$valueChain['bonuses_format']?></b></p>
		<?}?>
		<p><?=Loc::getMessage('sw24_loyaltyprogram.TOTAL_CHAIN')?>: <b><?=$arResult['TOTAL_CHAIN']['count']?></b>, <?=Loc::getMessage('sw24_loyaltyprogram.REFERRALS_TOTAL2')?>: <b><?=$arResult['TOTAL_CHAIN']['bonuses_format']?></b></p></div>
	<?}?>
<?$refUsers = ob_get_contents();
ob_end_clean();
}

$arData=[
	"tab1"=>[
		"NAME" => Loc::getMessage('sw24_loyaltyprogram.REF_TOOLS'),
		"CONTENT" =>$refLink
	],
	"tab2"=>[
		"NAME" => Loc::getMessage('sw24_loyaltyprogram.REF_CHAIN'),
		"CONTENT" =>$refUsers
	]
];


$arTabsParams = [
	"DATA" => $arData,
];

?>
<?if(!empty($arResult['ERRORS'])){
	ShowMessage(implode('<br>', $arResult['ERRORS']));
}?>
<section class="loyalty_room">
<?/*<h2><?=Loc::getMessage('sw24_loyaltyprogram.REF_NAME');?></h2>*/?>
<?$APPLICATION->IncludeComponent (
   "bitrix:catalog.tabs",
   "",
   $arTabsParams,
   $component,
   array("HIDE_ICONS" => "Y")
);?>
<?if(!empty($arParams['OUTER_ROOM'])){?>
<?
$APPLICATION->IncludeFile($arParams['OUTER_ROOM'], [], [
    "MODE"      => "html",                                 
    "NAME"      => Loc::getMessage('sw24_loyaltyprogram.INCLUDE_FILE')
]);
?>
<?}?>
</section>
</div>