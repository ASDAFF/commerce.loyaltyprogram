<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
$module_id ='commerce.loyaltyprogram';
use \Bitrix\Main\Application,
	Bitrix\Main\Page\Asset,
	Bitrix\Main\Request,
	Bitrix\Main\Localization\Loc,
	Bitrix\Sale\Internals;
	Loc::loadMessages(__DIR__ .'/lang.php');



$context = Application::getInstance()->getContext();
$request = $context->getRequest();
$module_id ='commerce.loyaltyprogram';

Asset::getInstance()->addJs('/bitrix/js/'.$module_id.'/script.js');

Asset::getInstance()->addJs('/bitrix/js/main/amcharts/3.3/amcharts.js');
Asset::getInstance()->addJs('/bitrix/js/main/amcharts/3.3/pie.js');
Asset::getInstance()->addJs('/bitrix/js/main/amcharts/3.3/serial.js');
Asset::getInstance()->addJs('/bitrix/js/main/amcharts/3.3/themes/light.js');


\Bitrix\Main\Loader::includeModule($module_id);

global $APPLICATION;
$rights=$APPLICATION->GetGroupRight($module_id);
if($rights<'E'){
	$APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));
}

//update or insert profile
if((!empty($request['apply']) || !empty($request['save'])) && check_bitrix_sessid()){
	if(!empty($request['id'])){
		if($request['id']=='new'){
			$cProfile=\Commerce\Loyaltyprogram\Profiles\Profile::getProfileByType($request['type']);
		}else{
			$cProfile=\Commerce\Loyaltyprogram\Profiles\Profile::getProfileById($request['id']);
		}
		$idProfile=$cProfile->save($request);
		if($request['id']=='new'){
			LocalRedirect($APPLICATION->GetCurPage().'?id='.$idProfile.'&save=Y&lang='.LANGUAGE_ID);
		}elseif(!empty($request['save'])){
			LocalRedirect($APPLICATION->GetCurPage().'?save=Y&lang='.LANGUAGE_ID);
		}
	}
}

if(!empty($request['action_button'])&&$request['action_button']=='copy'&&!empty($request['id'])){
	$listProfiles=new \Commerce\Loyaltyprogram\Profiles;
	$newIdProfile=$listProfiles->CopyProfile($request['id']);
	global $APPLICATION;
	$CURRENT_PAGE = (CMain::IsHTTPS()) ? "https://" : "http://";
	$CURRENT_PAGE .= $_SERVER["HTTP_HOST"];
	header("Location: ".$CURRENT_PAGE."/bitrix/admin/commerce_loyaltyprogram_profiles.php?id=".$newIdProfile.'&lang='.LANGUAGE_ID);
}

if(empty($request['id'])){
	$listProfiles=new \Commerce\Loyaltyprogram\Profiles;
	$listProfiles->initTableList();
	$APPLICATION->SetTitle(Loc::getMessage("commerce.loyaltyprogram_ADMIN_TITLE"));
}

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/".$module_id."/lib/informer.php");
Commerce\Informer::createInfo();

if(!empty($request['save']) && $request['save']=='Y'){
	CAdminMessage::ShowMessage([
		"TYPE"=>"OK",
		"MESSAGE"=>Loc::getMessage("commerce.loyaltyprogram_SAVE_PROFILE_SUCCESS")
	]); 
}

if(empty($request['id'])){
	$listProfiles->getTableList();
}else{
	//edit profile
	CJSCore::Init(['jquery2','core_condtree']);
	$currentId=$request['id'];
	$APPLICATION->SetTitle(Loc::getMessage("commerce.loyaltyprogram_ADMIN_TITLE_DET"));
	$aTabs = [
		["DIV" => "sw24_mp_source".$currentId, "TAB" => Loc::getMessage("commerce.loyaltyprogram_TAB_SETTING_NAME"), "TITLE" => Loc::getMessage("commerce.loyaltyprogram_TAB_SETTING_TITLE")],
		["DIV" => "sw24_mp_cond".$currentId, "TAB" => Loc::getMessage("commerce.loyaltyprogram_TAB_CONDITION"), "TITLE" => Loc::getMessage("commerce.loyaltyprogram_TAB_CONDITION_TITLE")],
		["DIV" => "sw24_mp_send_tmplts".$currentId, "TAB" => Loc::getMessage("commerce.loyaltyprogram_TAB_REF_SENDTMPLT"), "TITLE" => Loc::getMessage("commerce.loyaltyprogram_TAB_REF_SENDTMPLT_TITLE")],
		//["DIV" => "sw24_mp_mail".$currentId, "TAB" => Loc::getMessage("commerce.loyaltyprogram_TAB_REF_MAIL"), "TITLE" => Loc::getMessage("commerce.loyaltyprogram_TAB_MAIL_TITLE")],
		//["DIV" => "sw24_mp_sms".$currentId, "TAB" => Loc::getMessage("commerce.loyaltyprogram_TAB_REF_SMS"), "TITLE" => Loc::getMessage("commerce.loyaltyprogram_TAB_SMS_TITLE")],
		["DIV" => "sw24_mp_statistic".$currentId, "TAB" => Loc::getMessage("commerce.loyaltyprogram_TAB_STATISTIC_NAME"), "TITLE" => Loc::getMessage("commerce.loyaltyprogram_TAB_STATISTIC_TITLE"), 'ONSELECT'=>'show_statistic()']
	];
	
	if($currentId=='new'){
		$listProfiles=new \Commerce\Loyaltyprogram\Profiles;
		$availableProfiles=$listProfiles->getListProfiles();
		if(empty($request['type']) || empty($availableProfiles[$request['type']])){
			CAdminMessage::ShowMessage(Loc::getMessage("commerce.loyaltyprogram_EMPTY_TYPE_PROFILE"));
		}else{
			$currentProfile=['NAME'=>$availableProfiles[$request['type']], 'TYPE'=>$request['type']];
			$APPLICATION->SetTitle(Loc::getMessage("commerce.loyaltyprogram_ADMIN_TITLE_DET").' '.$currentProfile['NAME']);
			
			$cProfile=\Commerce\Loyaltyprogram\Profiles\Profile::getProfileByType($currentProfile['TYPE']);
		}
	}else{
		if((int) $currentId==0){
			CAdminMessage::ShowMessage(Loc::getMessage("commerce.loyaltyprogram_INVALID_NUMBER_PROFILE"));
		}else{
			$cProfile=\Commerce\Loyaltyprogram\Profiles\Profile::getProfileById($currentId);
			$profSetting=$cProfile->getProfile();
			$APPLICATION->SetTitle(Loc::getMessage("commerce.loyaltyprogram_ADMIN_TITLE_DET").' '.$profSetting['name']);
		}
	}
if(!empty($cProfile) && $cProfile!==false){
	if(!empty($request['apply']) && check_bitrix_sessid()){
		CAdminMessage::ShowMessage([
			"TYPE"=>"OK",
			"MESSAGE"=>Loc::getMessage("commerce.loyaltyprogram_SAVE_PROFILE_SUCCESS")
		]); 
	}	
	
	$tabControl = new CAdminTabControl("tabControl".$currentId, $aTabs);
?>
	<section class="pfofiles loyalty">
	<?$tabControl->Begin();?>
	<form class="pfofiles_edit_block" name="pfofiles_edit_block" method="post" action="<?echo $APPLICATION->GetCurPage()?>?lang=<?=LANGUAGE_ID?>">
	<input type="hidden" name="id" value="<?=$currentId?>" />
	<?if(!empty($currentProfile)){?>
	<input type="hidden" name="type" value="<?=$currentProfile['TYPE']?>" />
	<?}
$tabControl->BeginNextTab();
$cProfile->getParametersMain();
$tabControl->BeginNextTab();?>
<tr><td>
<div id="popupPropsCont"></div>
<script>
	let showParams=<?=$cProfile->getBaseCondition('json');?>;
	let showConditions=<?=$cProfile->getCurrentCondition('json');?>;
	let showControls=<?=$cProfile->getConditions('json');?>;
	var JSSaleAct=new BX.TreeConditions(showParams,showConditions,showControls);
	BX.message({
		periodLang:'<?=Loc::getMessage("commerce.loyaltyprogram_WILL_ADDED_PERIOD")?>',
		periodLangOver:'<?=Loc::getMessage("commerce.loyaltyprogram_WILL_ADDED_OVER_PERIOD")?>',
	})
	
	function show_statistic(){
		if(window['setGraphics']){
			setGraphics();
		}
	}
</script>
</td></tr>
<?
	$tabControl->BeginNextTab();
		if($cProfile->isNew()){
			CAdminMessage::ShowMessage([
				"TYPE"=>"OK",
				"MESSAGE"=>Loc::getMessage("commerce.loyaltyprogram_EVENT_NOT_AVAILABLE")
			]);
		}else{
			$cProfile->drawEmailList();
			$cProfile->drawSMSList();
		}
	/*$tabControl->BeginNextTab();
	if($cProfile->isNew()){
		CAdminMessage::ShowMessage([
			"TYPE"=>"OK",
			"MESSAGE"=>Loc::getMessage("commerce.loyaltyprogram_EVENT_NOT_AVAILABLE")
		]);
	}else{
		$cProfile->drawEmailList();
	}
		
	$tabControl->BeginNextTab();
	if($cProfile->isNew()){
		CAdminMessage::ShowMessage([
			"TYPE"=>"OK",
			"MESSAGE"=>Loc::getMessage("commerce.loyaltyprogram_SMS_NOT_AVAILABLE")
		]);
	}else{
		$cProfile->drawSMSList();
	}*/
	
	$tabControl->BeginNextTab();
		if($cProfile->isNew()){
			CAdminMessage::ShowMessage([
				"TYPE"=>"OK",
				"MESSAGE"=>Loc::getMessage("commerce.loyaltyprogram_STATISTICS_NOT_AVAILABLE")
			]);
		}else{
			\Commerce\Loyaltyprogram\Statistic::drawStatistics($currentId);
		}
		
	$tabControl->Buttons([
		"back_url" => $APPLICATION->GetCurPage().'?lang='.LANGUAGE_ID
	]);
	echo bitrix_sessid_post();?>
	</form>
	<?$tabControl->End();?>
	</section>
<?}
}?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");?>