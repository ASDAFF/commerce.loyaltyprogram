<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
$module_id ='commerce.loyaltyprogram';
use \Bitrix\Main\Application,
	Bitrix\Main\Page\Asset,
	Bitrix\Main\Request,
	Bitrix\Main\Localization\Loc,
	Bitrix\Sale\Internals;
	Loc::loadMessages(__FILE__);

$context = Application::getInstance()->getContext();
$request = $context->getRequest();
$module_id ='commerce.loyaltyprogram';
Asset::getInstance()->addJs('/bitrix/js/'.$module_id.'/script.js');

\Bitrix\Main\Loader::includeModule($module_id);
$groups=new \Commerce\Loyaltyprogram\Groups;

global $APPLICATION;
$rights=$APPLICATION->GetGroupRight($module_id);
if($rights<'E'){
	$APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));
}

$APPLICATION->SetTitle(Loc::getMessage("commerce.loyaltyprogram_ADMIN_TITLE"));
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/".$module_id."/lib/informer.php");
Commerce\Informer::createInfo();

	CJSCore::Init(array("jquery2"));
	
	$aTabs = [
		["DIV" => "sw24_loyal_groups", "TAB" => Loc::getMessage("commerce.loyaltyprogram_TAB_SETTING_NAME"), "TITLE" => Loc::getMessage("commerce.loyaltyprogram_TAB_SETTING_TITLE")]
	];
	
	$tabControl = new CAdminTabControl("tabControl_groups", $aTabs);
	
	$groupsType=[];
	//$groupsType['REFERENCE'][]='...';
	//$groupsType['REFERENCE_ID'][]=0;
	foreach($groups->getGroupList() as $key=>$val){
		$groupsType['REFERENCE'][]=$val;
		$groupsType['REFERENCE_ID'][]=$key;
	}
	$selectType=0;
	//update or insert groups
	if((!empty($request['apply']) || !empty($request['save'])) && check_bitrix_sessid()){
		echo $groups->doSomething($request);
	}
?>
	<section class="groups">
	<form class="groups_edit_block" name="groups_action" method="post" action="<?echo $APPLICATION->GetCurPage()?>?lang=<?=LANGUAGE_ID?>">
	<?=bitrix_sessid_post();?>
	<?$tabControl->Begin();
	$tabControl->BeginNextTab();?>
		<tr><td width="50%"><?=Loc::getMessage("commerce.loyaltyprogram_GROUPS_TYPE")?></td><td><?=SelectBoxFromArray("groups_type", $groupsType, $selectType);?></td></tr>
	<?foreach($groups->getFields() as $keyType=>$valType){?>
		<tbody class="type_fields <?=$keyType?>"><?
		foreach($valType as $nextField){
			$required=(!empty($nextField['required']) && $nextField['required']===true)?'<span class="required">*</span>':'';
			$nextField['val']=(empty($nextField['val']))?'':$nextField['val'];
			$nextField['val']=(empty($request[$nextField['code']]))?$nextField['val']:$request[$nextField['code']];
			switch ($nextField['type']){
				case 'textarea':
					$input='<textarea name="'.$nextField['code'].'">'.$nextField['val'].'</textarea>';
				break;
				case 'number':
					$input='<input width="100" type="number" name="'.$nextField['code'].'" value="'.$nextField['val'].'" />';
				break;
				case 'radiobutton';
					$input='';
					$i=0;
					foreach($nextField['list'] as $key=>$val){
						$checked=($i==0)?' checked="checked"':'';
						$input.='<label><input type="radio" name="'.$nextField['code'].'" value="'.$key.'"'.$checked.' /> '.$val.'</label>';
						$i++;
					}
				break;
				case 'multiselect':
					$tmpList=[];
					$nextField['val']=(empty($nextField['val']))?[]:$nextField['val'];
					foreach($nextField['list'] as $key=>$val){
						$tmpList['REFERENCE'][]=$val;
						$tmpList['REFERENCE_ID'][]=$key;
					}
					$input=SelectBoxMFromArray($nextField['code']."[]", $tmpList, $nextField['val']);
				break;
				case 'live_type':
					$input='<input width="100" min="0" type="number" name="'.$nextField['code'].'" value="'.$nextField['val'].'" />
					<select name="bonus_live_type">';
						foreach($nextField['list'] as $key=>$value){
							$selected=(!empty($request['bonus_live_type']) && $request['bonus_live_type']==$key)?' selected="selected"':'';
							$input.='<option value="'.$key.'" '.$selected.'>'.$value.'</option>';
						}
					$input.='</select>';
				break;
				case 'user_select':
					$user_name = "";
					if ($ID > 0)
						$user_name = "[<a title=\"".GetMessage("STE_USER_PROFILE")."\" href=\"/bitrix/admin/user_edit.php?lang=".LANGUAGE_ID."&ID=".$str_USER_ID."\">".$str_USER_ID."</a>] (".$str_USER_LOGIN.") ".$str_USER_NAME." ".$str_USER_LAST_NAME;
					$input ='<input type="hidden" name="'.$keyType.'_'.$nextField['type'].'" value="1">';
					$input.=FindUserID("USER_ID_".$keyType.'_1', $str_USER_ID, $user_name,'groups_action');
					$input.='<br><a title="addRow" href="javascript:;" onclick="addUserInput(this)">'.GetMessage("commerce.loyaltyprogram_GROUPS_ADD_USER").'</a>';
				break;
			}
				if($nextField['type']=='textarea'){}
			?>
			<tr><td width="50%"><?=$nextField['name']?><?=$required?></td><td><?=$input?></td></tr>
		<?}?></tbody>
	<?}$tabControl->Buttons([
		"back_url" => $APPLICATION->GetCurPage().'?lang='.LANGUAGE_ID
	]);
	$tabControl->End();?>
	</form>
	<script>
	var groupRow, UserIdRow, groupRowRemove, UserIdRowRemove;
	BX.ready(function(){
		BX.bind(BX('groups_type'), 'change', BX.proxy(changeTypeGroup, BX('groups_type')));
		changeTypeGroup.call(BX('groups_type'));
		
		let allRows=BX('sw24_loyal_groups_edit_table').querySelectorAll('tr');
		for(let i=0; i<allRows.length; i++){
			let nextRow=allRows[i];
			let tmpGroups=allRows[i].querySelector("select[name='user_groups[]']");
			if(tmpGroups){
				groupRow=allRows[i];
			}
			let tmpUsersId=allRows[i].querySelector("#USER_ID_bonusAcc_1");
			if(tmpUsersId){
				UserIdRow=allRows[i];
			}
			let tmpGroupsRemove=allRows[i].querySelector("select[name='user_groups_remove[]']");
			if(tmpGroupsRemove){
				groupRowRemove=allRows[i];
			}
			let tmpUsersIdRemove=allRows[i].querySelector("#USER_ID_bonusRemove_1");
			if(tmpUsersIdRemove){
				UserIdRowRemove=allRows[i];
			}
		}
		
		let selectUsers=document.querySelectorAll('input[name=select_user], input[name=select_user_remove]');
		for(let i=0; i<selectUsers.length; i++){
			BX.bind(selectUsers[i], 'change', function(){changeSelectUsers();});
		}
		changeSelectUsers();
		
	});
	
	function addUserInput(elem){
		
		var tmpType = BX('groups_type').value;
		var tmpCol = $(elem).closest('td').find('[name="'+tmpType+'_user_select"]').val();
		tmpCol = (parseInt(tmpCol)+1);
		$(elem).closest('td').find('[name="'+tmpType+'_user_select"]').val(tmpCol);
		var tmpAddedText='<input type="text" name="USER_ID_'+tmpType+'_'+tmpCol+'" id="USER_ID_'+tmpType+'_'+tmpCol+'" value="" size="3" maxlength="" class="typeinput">\
<iframe style="width:0px; height:0px; border:0px" src="javascript:\'\'" name="hiddenframeUSER_ID_'+tmpType+'_'+tmpCol+'" id="hiddenframeUSER_ID_'+tmpType+'_'+tmpCol+'"></iframe>&nbsp;&nbsp;\
<input class="tablebodybutton" type="button" name="FindUser" id="FindUser" onclick="window.open(\'/bitrix/admin/user_search.php?lang=ru&amp;FN=groups_action&amp;FC=USER_ID_'+tmpType+'_'+tmpCol+'\', \'\', \'scrollbars=yes,resizable=yes,width=760,height=500,top=\'+Math.floor((screen.height - 560)/2-14)+\',left=\'+Math.floor((screen.width - 760)/2-5));" value="...">\
<span id="div_USER_ID_'+tmpType+'_'+tmpCol+'" class="adm-filter-text-search"></span>\
\<script type="text/javascript"\>\
var tvUSERxIDx'+tmpType+'x'+tmpCol+'=\'\';\
function ChUSERxIDx'+tmpType+'x'+tmpCol+'(){\
	var DV_USERxIDx'+tmpType+'x'+tmpCol+';\
	DV_USERxIDx'+tmpType+'x'+tmpCol+'=BX("div_USER_ID_'+tmpType+'_'+tmpCol+'");\
	if(!!DV_USERxIDx'+tmpType+'x'+tmpCol+'){\
		if(tvUSERxIDx'+tmpType+'x'+tmpCol+'!=document.groups_action[\'USER_ID_'+tmpType+'_'+tmpCol+'\'].value){\
			tvUSERxIDx'+tmpType+'x'+tmpCol+'=document.groups_action[\'USER_ID_'+tmpType+'_'+tmpCol+'\'].value;\
			if (tvUSERxIDx'+tmpType+'x'+tmpCol+'!=\'\'){\
				BX("hiddenframeUSER_ID_'+tmpType+'_'+tmpCol+'").src=\'/bitrix/admin/get_user.php?ID=\'+tvUSERxIDx'+tmpType+'x'+tmpCol+'+\'&strName=USER_ID_'+tmpType+'_'+tmpCol+'&lang=ru&admin_section=Y\';\
			}else{\
				DV_USERxIDx'+tmpType+'x'+tmpCol+'.innerHTML = \'\';\
			}\
		}\
	}\
	setTimeout(function(){ChUSERxIDx'+tmpType+'x'+tmpCol+'()},1000);\
}\
BX.ready(function(){\
	if(BX.browser.IsIE){\
		setTimeout(function(){ChUSERxIDx'+tmpType+'x'+tmpCol+'()},3000);\
	}else\
		ChUSERxIDx'+tmpType+'x'+tmpCol+'();\
});\</script\>\<br\>';
		$(tmpAddedText).insertBefore($(elem));
		
	}
	
	function changeTypeGroup(){
		elementList = document.querySelectorAll('.groups .type_fields');
		for(var i=0; i<elementList.length; i++){
			elementList[i].style.display='none';
		}
		if(this.value!=0){
			cElement = document.querySelector('.groups .type_fields.'+this.value);
			cElement.style.display='table-row-group';
		}
	}
	
	function changeSelectUsers(){
		
		let typeUsers = document.querySelectorAll('input[name=select_user]'),
			mode='group';
		for(var i = 0; i < typeUsers.length; i++){
			if(typeUsers[i].checked){
				mode=typeUsers[i].value;
				break;
			}
		}
		if(mode=='group'){
			UserIdRow.style.display='none';
			groupRow.style.display='table-row';
		}else{
			UserIdRow.style.display='table-row';
			groupRow.style.display='none';
		}
		
		let typeUsersRemove = document.querySelectorAll('input[name=select_user_remove]');
		for(var i = 0; i < typeUsers.length; i++){
			if(typeUsersRemove[i].checked){
				mode=typeUsers[i].value;
				break;
			}
		}
		if(mode=='group'){
			UserIdRowRemove.style.display='none';
			groupRowRemove.style.display='table-row';
		}else{
			UserIdRowRemove.style.display='table-row';
			groupRowRemove.style.display='none';
		}
		
	}
	
	</script>
	</section>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");?>