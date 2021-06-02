<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
use \Bitrix\Main\Application,
	\Bitrix\Main\Page\Asset,
	\Bitrix\Main\Request,
	\Bitrix\Main\Localization\Loc,
	\Bitrix\Sale\Internals;
	Loc::loadMessages(__FILE__);
	
$module_id ='commerce.loyaltyprogram';
\Bitrix\Main\Loader::includeModule($module_id);

$context = Application::getInstance()->getContext();
$request = $context->getRequest();
$ranks=new \Commerce\Loyaltyprogram\Ranks;

if(!empty($_REQUEST['id']) && ($_REQUEST['id']=='new' || (int) $_REQUEST['id']>0)){
	if($_REQUEST['id']=='new'){
		$APPLICATION->SetTitle(Loc::getMessage("commerce.loyaltyprogram_TITLE_RANK_NEW"));
	}else{
		$APPLICATION->SetTitle(Loc::getMessage("commerce.loyaltyprogram_TITLE_RANK_EDIT"));
	}
}else{
	$APPLICATION->SetTitle(Loc::getMessage("commerce.loyaltyprogram_ADMIN_TITLE"));
}


if((!empty($request['apply']) || !empty($request['save'])) && check_bitrix_sessid()){
	$id=$ranks->saveSetting($request);
	if(!empty($request['save'])){
		LocalRedirect($APPLICATION->GetCurPage().'?lang='.LANGUAGE_ID);
	}elseif(!empty($request['id']) && $request['id']=='new'){
		LocalRedirect($APPLICATION->GetCurPage().'?id='.$id.'&add=success&lang='.LANGUAGE_ID);
	}
	$info= new CAdminMessage([
		"TYPE"=>"OK",
		"MESSAGE"=>Loc::getMessage("commerce.ranks_SAVE_SETTING_SUCCESS")
	]);
}/*elseif(!empty($request['ajax']) && $request['ajax']=='Y' && !empty($request['command'])){
	if($request['command']=='update_ranks'){
		\Commerce\Loyaltyprogram\Ranks::setRanks();
		echo \Bitrix\Main\Web\Json::encode(['status'=>'success']);
	}
	die();
}*/

Asset::getInstance()->addJs('/bitrix/js/'.$module_id.'/script.js');
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/".$module_id."/lib/informer.php");
Commerce\Informer::createInfo();

if(!empty($request['add']) && $request['add']=='success' && !empty($request['id'])){
	$info=new CAdminMessage([
		"TYPE"=>"OK",
		"MESSAGE"=>Loc::getMessage("commerce.ranks_ADD_RANK_SUCCESS")
	]);
}

if(!empty($info)){
	echo $info->Show();
}

if(!empty($_REQUEST['id']) && ($_REQUEST['id']=='new' || (int) $_REQUEST['id']>0)){?>
	<form name="bidding_setting" method="post" action="<?echo $APPLICATION->GetCurPage()?>?id=<?=$_REQUEST['id']?>&lang=<?=LANGUAGE_ID?>">
	<?$aTabs = [
		["DIV" => "sw24_loyal_rank_edit", "TAB" => Loc::getMessage("commerce.loyaltyprogram_TAB_RANK_NAME"), "TITLE" => Loc::getMessage("commerce.loyaltyprogram_TAB_RANK_TITLE")]
	];
	$tabControl = new CAdminTabControl("tabControl_import", $aTabs);
	$tabControl->Begin();
	$tabControl->BeginNextTab();
	$data=$ranks->getData($request['id']);
	$rankSettings=$ranks->getRankSettings();
	?>
	<tr>
		<td style="text-align: right" width="50%"><?= Loc::getMessage("commerce.ranks_edit_ACTIVE")?></td>
		<td style="text-align: left" width="50%"><input type="checkbox" name="active" value="Y"<?=$data['active']=='Y'?' checked':'';?>></td>
	</tr>
	<tr>
		<td style="text-align: right" width="50%"><?= Loc::getMessage("commerce.ranks_edit_NAME")?></td>
		<td style="text-align: left" width="50%"><input type="text" name="name" value="<?=$data['name']?>"></td>
	</tr>
	<tr>
		<td style="text-align: right" width="50%"><?= Loc::getMessage("commerce.ranks_edit_SORT")?></td>
		<td style="text-align: left" width="50%"><input type="number" name="sort" value="<?=$data['sort']?>"></td>
	</tr>
	<tr>
		<td style="text-align: right" width="50%"><?= Loc::getMessage("commerce.ranks_edit_COEFF")?></td>
		<td style="text-align: left" width="50%"><input type="number" name="coeff" step=".01" value="<?=$data['coeff']?>"></td>
	</tr>
	<tr>
		<td style="text-align: right" width="50%"><?= Loc::getMessage("commerce.ranks_edit_VALUE")?></td>
		<td style="text-align: left" width="50%"><input type="number" name="value" step=".01" value="<?=$data['value']?>"></td>
	</tr>
	<tr>
		<td style="text-align: right" width="50%"><?= Loc::getMessage("commerce.ranks_edit_PERIOD")?></td>
		<td style="text-align: left" width="50%">
			<?/*?><input type="number" name="period_size"  value="<?=$data['settings']['period']['size']?>"><?*/?>
			<select name="period_type">
			<?foreach($rankSettings['period'] as $keyRank=>$valRank){
				$selected=$keyRank==$data['settings']['period']['type']?' selected':'';?>
				<option value="<?=$keyRank?>"<?=$selected?>><?=$valRank?></option>
			<?}?>
			</select>
		</td>
	</tr>
	<tr>
		<td style="text-align: right" width="50%"><?= Loc::getMessage("commerce.ranks_edit_PROFILES")?></td>
		<td style="text-align: left" width="50%">
			<select name="profiles[]" multiple size="4">
			<?foreach($ranks->getProfiles() as $keyProfile=>$valProfile){
				$selected=in_array($keyProfile, $data['profiles'])?' selected':'';?>
				<option value="<?=$keyProfile?>"<?=$selected?>><?=$valProfile?></option>
			<?}?>
			</select>
		</td>
	</tr>
	<?/*?><tr>
		<td style="text-align: right" width="50%"><?= Loc::getMessage("commerce.ranks_edit_REWRITERANK")?></td>
		<td style="text-align: left" width="50%"><input type="checkbox" name="active" value="Y"<?=$data['settings']['rewriteRank']=='Y'?' checked':'';?>></td>
	</tr><?*/?>
	<?$tabControl->Buttons(["back_url" => $APPLICATION->GetCurPage().'?lang='.LANGUAGE_ID]);
	$tabControl->End();
	echo bitrix_sessid_post();?>
	</form>
<?}else{
$ranks->show();
?>
<script>
BX.ready(function(){
	BX.bindDelegate(BX('sw24_rank_list_table'), 'keypress', {attribute:{name : 'coeff'}}, function(){
		if(!event.key.match(/[0-9]/) && event.key!='.'){
			event.preventDefault();
			//return false;
        };
	});
	BX.bindDelegate(BX('sw24_rank_list_table'), 'keypress', {attribute:{name : 'value'}}, function(){
		if(!event.key.match(/[0-9]/) && event.key!='.'){
			event.preventDefault();
			//return false;
        };
	}); 
	BX.bindDelegate(BX('sw24_rank_list_table'), 'keypress', {attribute:{name : 'sort'}}, function(){
		if(!event.key.match(/[0-9]/)){
			event.preventDefault();
			//return false;
        };
	});
})

<?/*function update_ranks(){
	BX.ajax({
		method: 'POST',
		dataType: 'json',
		url: location.href,
		data: {ajax:'Y', command:'update_ranks'},
		onsuccess: function(data){
			console.log(data);
		},
		onfailure: function(data){
			console.log('error');
			console.log(data);
		}
	});
}*/?>
</script>
<?}?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");?>