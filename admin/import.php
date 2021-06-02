<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
$module_id ='commerce.loyaltyprogram';
use \Bitrix\Main\Application,
	Bitrix\Main\Page\Asset,
	Bitrix\Main\Request,
	Bitrix\Main\Localization\Loc,
	Bitrix\Sale\Internals;
	Loc::loadMessages(__FILE__);
	
$context = Application::getInstance()->getContext();
$request = $context->getRequest();

\Bitrix\Main\Loader::includeModule($module_id);
global $APPLICATION;
$rights=$APPLICATION->GetGroupRight($module_id);
if($rights<'E'){
	$APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));
}
if(!empty($_REQUEST['ajax']) && $_REQUEST['ajax']=='y' && $rights>'D'){
	$importData=new Commerce\Loyaltyprogram\Import();
	if(!empty($_REQUEST['action']) && $_REQUEST['action']=='uploadFile'){
		if($_REQUEST['type']=='bonus'){
			$data=$importData->setBonus((int) $_REQUEST['idFile'], $_REQUEST['bonus_type']);
			//echo \Bitrix\Main\Web\Json::encode(['status'=>$status]);			
			echo \Bitrix\Main\Web\Json::encode($data);
			$importData->clearFiles();
		}elseif($_REQUEST['type']=='refNet'){
			$data=$importData->setReferalNet((int) $_REQUEST['idFile']);
			//echo \Bitrix\Main\Web\Json::encode(['status'=>$status]);
			echo \Bitrix\Main\Web\Json::encode($data);
			$importData->clearFiles();
		}
	}
	die();
}

$APPLICATION->SetTitle(Loc::getMessage("commerce.loyaltyprogram_ADMIN_TITLE"));
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
CJSCore::Init(['ajax']);

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/".$module_id."/lib/informer.php");
Commerce\Informer::createInfo();

$aTabs = [
		["DIV" => "sw24_loyal_import_users", "TAB" => Loc::getMessage("commerce.loyaltyprogram_TAB_USERS_NAME"), "TITLE" => Loc::getMessage("commerce.loyaltyprogram_TAB_USERS_TITLE")],
		["DIV" => "sw24_loyal_import_bonus", "TAB" => Loc::getMessage("commerce.loyaltyprogram_TAB_BONUS_NAME"), "TITLE" => Loc::getMessage("commerce.loyaltyprogram_TAB_BONUS_TITLE")]
	];
$tabControl = new CAdminTabControl("tabControl_import", $aTabs);?>
<section class="imports">
<?$tabControl->Begin();
$tabControl->BeginNextTab();?>
<tr>
	<td><?CAdminMessage::ShowMessage(['HTML'=>true,'MESSAGE'=>Loc::getMessage("commerce.loyaltyprogram_IMPORT_USERS_NOTE")]);?></td>
</tr>
<tr>
	<td>
		<?$APPLICATION->IncludeComponent("bitrix:main.file.input", "drag_n_drop",
		   array(
			 "INPUT_NAME"=>"FILE_USERS",
			 "MULTIPLE"=>"N",
			 "MODULE_ID"=>$module_id,
			 "MAX_FILE_SIZE"=>"1000000",
			 "ALLOW_UPLOAD"=>"F", 
			 "ALLOW_UPLOAD_EXT"=>"csv"
		),
		false
		);?>
	</td>
</tr>
<tr id="refnet_send" style="display:none;">
	<td>
		<button class="adm-btn adm-btn-save"><?=Loc::getMessage("commerce.loyaltyprogram_IMPORT_BUTTON_SEND");?></button>
	</td>
</tr>
<tr id="refnet_error" style="display:none;">
	<td>
		<?=CAdminMessage::ShowMessage(Loc::getMessage("commerce.loyaltyprogram_IMPORT_UPLOADREFTNETFAIL"));?>
	</td>
</tr>
<tr id="refnet_succes" style="display:none;">
	<td>
		<?=CAdminMessage::ShowNote(Loc::getMessage("commerce.loyaltyprogram_IMPORT_UPLOADREFTNETSUCCESS"));?>
	</td>
</tr>
<?$tabControl->BeginNextTab();?>
<tr>
	<td >
		<?CAdminMessage::ShowMessage(['HTML'=>true,'MESSAGE'=>Loc::getMessage("commerce.loyaltyprogram_IMPORT_BONUSES_NOTE")]);?>
	</td>
</tr>
<tr>
	<td >
		<?=Loc::getMessage("commerce.loyaltyprogram_IMPORT_BONUS_TYPE");?>
		<select id="bonus_type">
			<option value="add"><?=Loc::getMessage("commerce.loyaltyprogram_IMPORT_BONUS_TYPE_ADD");?></option>
			<option value="replace"><?=Loc::getMessage("commerce.loyaltyprogram_IMPORT_BONUS_TYPE_REPLACE");?></option>
		</select>
		<br>&nbsp;
	</td>
</tr>
<tr>
	<td>
		<?$APPLICATION->IncludeComponent("bitrix:main.file.input", "drag_n_drop",
		   array(
			 "INPUT_NAME"=>"FILE_BONUS",
			 "MULTIPLE"=>"N",
			 "MODULE_ID"=>$module_id,
			 "MAX_FILE_SIZE"=>"1000000",
			 "ALLOW_UPLOAD"=>"F", 
			 "ALLOW_UPLOAD_EXT"=>"csv"
		),
		false
		);?>
	</td>
</tr>
<tr id="bonus_send" style="display:none;">
	<td>
		<button class="adm-btn adm-btn-save"><?=Loc::getMessage("commerce.loyaltyprogram_IMPORT_BUTTON_SEND");?></button>
	</td>
</tr>
<tr id="bonus_error" style="display:none;">
	<td>
		<?=CAdminMessage::ShowMessage(Loc::getMessage("commerce.loyaltyprogram_IMPORT_UPLOADBONUSFAIL"));?>
	</td>
</tr>
<tr id="bonus_succes" style="display:none;">
	<td>
		<?=CAdminMessage::ShowNote(Loc::getMessage("commerce.loyaltyprogram_IMPORT_UPLOADBONUSSUCCESS"));?>
	</td>
</tr>
<?$tabControl->End();?>
</section>
<script>

BX.message({
	'succesupload':'<?=Loc::getMessage("commerce.loyaltyprogram_IMPORT_SUCCES_UPLOAD")?>',
});

let uploadFileLO={
	init:function(){
		
		this.blocks={};
		
		this.blocks.bonus={};
		this.blocks.bonus.buttonRow=document.querySelector('#bonus_send');
		this.blocks.bonus.button=document.querySelector('#bonus_send button');
		this.blocks.bonus.bonusType=document.querySelector('#bonus_type');
		this.blocks.bonus.errorRow=document.querySelector('#bonus_error');
		this.blocks.bonus.successRow=document.querySelector('#bonus_succes');
		this.blocks.bonus.successRowArea=document.querySelector('#bonus_succes .adm-info-message-title');
		this.blocks.bonus.successRowBaseText=this.blocks.bonus.successRowArea.innerHTML;
		
		this.blocks.bonus.button.addEventListener("click", uploadFileLO.sendData);
		
		this.blocks.refnet={};
		this.blocks.refnet.buttonRow=document.querySelector('#refnet_send');
		this.blocks.refnet.button=document.querySelector('#refnet_send button');
		this.blocks.refnet.errorRow=document.querySelector('#refnet_error');
		this.blocks.refnet.successRow=document.querySelector('#refnet_succes');
		this.blocks.refnet.successRowArea=document.querySelector('#refnet_succes .adm-info-message-title');
		this.blocks.refnet.successRowBaseText=this.blocks.refnet.successRowArea.innerHTML;
		
		this.blocks.refnet.button.addEventListener("click", uploadFileLO.sendData);
	},
	
	showButton:function(id, type){
		if(type=='bonus'){
			this.currentBlock=this.blocks.bonus;
		}else if(type=='refNet'){
			this.currentBlock=this.blocks.refnet;
		}
		this.dataSend={
			'ajax':'y',
			'action':'uploadFile',
			'type':type,
			'idFile':id,
			'bonus_type':this.blocks.bonus.bonusType.value
		}
	
		this.currentBlock.buttonRow.style.display='table-row';
		this.currentBlock.errorRow.style.display='none';
		this.currentBlock.successRow.style.display='none';
		this.currentBlock.successRowArea.innerHTML=this.currentBlock.successRowBaseText;
	},
	
	sendData:function(){
		uploadFileLO.currentBlock.button.classList.add('adm-btn-load');
		uploadFileLO.currentBlock.button.disabled = true;
		
		BX.ajax({
			url: location.href,
			method: 'post',
			dataType: 'json',
			async: true,
			processData: true,
			emulateOnload: true,
			start: true,
			data: uploadFileLO.dataSend,
			//cache: true,
			onsuccess: function(result){
				uploadFileLO.currentBlock.button.classList.remove('adm-btn-load');
				uploadFileLO.currentBlock.button.disabled = false;
				uploadFileLO.currentBlock.buttonRow.style.display='none';
				uploadFileLO.currentBlock.errorRow.style.display='none';
				uploadFileLO.currentBlock.successRow.style.display='none';
				if(result.status){
					uploadFileLO.currentBlock.successRowArea.innerHTML+='<br>'+BX.message('succesupload')+': '+result.count;
					uploadFileLO.currentBlock.successRow.style.display='table-row';
				}else{
					uploadFileLO.currentBlock.errorRow.style.display='table-row';
				}
			},
			onfailure: function(type, e){
				console.log(type);
			}
		});
		
	}
	
}

BX.ready(function(){
	
	let buttonsUpload=document.querySelectorAll('.file-selectdialog-switcher');
	for(buttonUpload of buttonsUpload){
		buttonUpload.click();
	}
	
	uploadFileLO.init();
	BX.addCustomEvent('OnFileUploadSuccess', function(data,o) {
		if(o.controlID=='mfiFILE_BONUS'){//upload bonus
			uploadFileLO.showButton(data.element_id, 'bonus');
		}else if(o.controlID=='mfiFILE_USERS'){//upload users
			uploadFileLO.showButton(data.element_id, 'refNet');
		}
	});
	
})
</script>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");?>