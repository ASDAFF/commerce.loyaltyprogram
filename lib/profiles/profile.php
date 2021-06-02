<?
namespace Commerce\Loyaltyprogram\Profiles;
use \Bitrix\Main\Localization\Loc,
	\Bitrix\Main\Application,
	\Bitrix\Main\EventManager as BitrixEventManager,
	\Commerce\Loyaltyprogram,
	\Bitrix\Main\SystemException;
Loc::loadMessages(dirname(__DIR__).'/lang.php');

\Bitrix\Main\Loader::includeModule('sale');
\Bitrix\Main\Loader::includeModule('iblock');
\Bitrix\Main\Loader::includeModule('catalog');
/**
* main profile class
*/
class Profile{

	protected $profileSetting;
	protected $timePart;
	protected $globalSettings;
	protected $ranksObject;

	function __construct (){
		$this->globalSettings=Loyaltyprogram\Settings::getInstance();
		$this->ranksObject=new Loyaltyprogram\Ranks;
		
		$this->profileSetting=[
			'id'=>'new',
			'active'=>'N',
			'name'=>'',
			'type'=>'',
			'site'=>'',
			'date_setting'=>'',
			'settings'=>[],
			'email_settings'=>[],
			'sms_settings'=>[]
		];
		$this->timePart=[
			'hour'=>3600,
			'day'=>86400,
			'week'=>604800,
			'month'=>2592000
		];
	}

	public function getOptions(){
	    return $this->globalSettings->getOptions();
    }

	public function setProperties(array $props){
	    foreach($props as $key=>$value){
	        $this->$key=$value;
        }
    }
	
	/**
	* fix that there were no duplicating records in bonus table
	*/
	protected function isAlreadyRow($fields){
		global $DB;
		if(count($fields)>0){
			$sql='select * from '.$this->globalSettings->getTableBonusList().' where 1=1';
			foreach($fields as $key=>$value){
				if(
				        $key=='status'
                        || $key=='date_add'
                        || $key=='date_remove'
                        || $key=='bonus'
                        || $key=='bonus_start'
                        || $key=='email'
                        || $key=='sms'
                        || $key=='add_comment'
                        || $key=='comments'
                ){continue;}
				/*if($key=='bonus' || $key=='bonus_start'){
					$value=round($value);
				}*/
				$sql.=' and '.$key.'='.$value;
			}
			$results=$DB->Query($sql);
			if($res = $results->Fetch()){
				return true;
			}
		}
		return false;
	}
	
	public function deleteProfile($idProfile){
		global $DB;
		$res=$DB->Query('select * from '.$this->globalSettings->getTableProfilesList().' where id='.$idProfile.';');
		if($row = $res->Fetch()){
			if(!empty($row['email_settings'])){
				$row['email_settings']=unserialize($row['email_settings']);
				foreach($row['email_settings'] as $nextEvent){
					foreach($nextEvent as $nextTemplate){
						if((int) $nextTemplate>0){
							\Bitrix\Main\Mail\Internal\EventMessageTable::delete($nextTemplate);
						}
					}
				}
			}
			if(!empty($row['sms_settings'])){
				$row['sms_settings']=unserialize($row['sms_settings']);
				foreach($row['sms_settings'] as $nextEvent){
					foreach($nextEvent as $nextTemplate){
						if((int) $nextTemplate>0){
							\Bitrix\Main\Sms\TemplateTable::delete($nextTemplate);
						}
					}
				}
			}

			$DB->Query('delete from '.$this->globalSettings->getTableProfilesList().' where id='.$idProfile.';');
			return true;
		}
		return false;
	}
	
	public function setProfile($settings=[]){
		$this->profileSetting=$settings;
		if(!empty($this->profileSetting['site'])){
			$this->profileSetting['site']=explode(',',$this->profileSetting['site']);
		}
	}
	
	public function isNew(){
		return $this->profileSetting['id']=='new';
	}
	
	/**
	* check order props with code commerce_bonus
	* if not - create this prop
	*/
	public function checkOrderProps(){
		$typePropsName=Loc::getMessage("commerce.loyaltyprogram_TYPE_PROPS_NAME");
		$persons=[];
		$res=\Bitrix\Sale\Internals\PersonTypeTable::getList();
		while($nextRes=$res->fetch()){
			$persons[$nextRes['ID']]=$nextRes['ID'];
		}
		$res=\Bitrix\Sale\Internals\OrderPropsGroupTable::getList(
			['filter'=>['%=NAME'=>'Commerce%']]
		);
		$groups=[];
		while($nextRes=$res->fetch()){
			$typePropsName=$nextRes['NAME'];
			$groups[$nextRes['ID']]=$nextRes;
			unset($persons[$nextRes['PERSON_TYPE_ID']]);
		}
		if(count($persons)>0){
			foreach($persons as $nextPerson){
				$res=\Bitrix\Sale\Internals\OrderPropsGroupTable::add([
					'PERSON_TYPE_ID'=>$nextPerson,
					'NAME'=>$typePropsName,
					'SORT'=>3
				]);
				$id = $res->getId();
				$groups[$id]=[
					'PERSON_TYPE_ID'=>$nextPerson,
					'NAME'=>$typePropsName,
					'ID'=>$id
				];
			}
		}
		$res=\Bitrix\Sale\Internals\OrderPropsTable::getList(
			['filter'=>['CODE'=>'commerce_bonus']]
		);
		while($nextRes=$res->fetch()){
			unset($groups[$nextRes['PROPS_GROUP_ID']]);
			$props[]=$nextRes;
		}
		
		if(count($groups)>0){
			foreach($groups as $nextGroup){
				try{
					$res=\Bitrix\Sale\Internals\OrderPropsTable::add([
						'PERSON_TYPE_ID'=>$nextGroup['PERSON_TYPE_ID'],
						'NAME'=>Loc::getMessage("commerce.loyaltyprogram_PROPS_NAME"),
						'TYPE'=>'NUMBER',
						'REQUIRED'=>'N',
						'DEFAULT_VALUE'=>'',
						'SORT'=>100,
						'USER_PROPS'=>'N',
						'IS_LOCATION'=>'N',
						'PROPS_GROUP_ID'=>$nextGroup['ID'],
						'DESCRIPTION'=>'',
						'IS_EMAIL'=>'N',
						'IS_PROFILE_NAME'=>'N',
						'IS_PAYER'=>'N',
						'IS_LOCATION4TAX'=>'N',
						'IS_FILTERED'=>'N',
						'CODE'=>'commerce_bonus',
						'IS_ZIP'=>'N',
						'IS_PHONE'=>'N',
						'IS_ADDRESS'=>'N',
						'ACTIVE'=>'Y',
						'UTIL'=>'N',
						'ENTITY_REGISTRY_TYPE'=>'ORDER',
						'INPUT_FIELD_LOCATION'=>'0',
						'MULTIPLE'=>'N',
						'SETTINGS'=>[
							'MIN'=>'0',
							'MAX'=>'',
							'STEP'=>''
						]					
					]);
				}catch (SystemException $e){//old version bitrix
					$res=\Bitrix\Sale\Internals\OrderPropsTable::add([
						'PERSON_TYPE_ID'=>$nextGroup['PERSON_TYPE_ID'],
						'NAME'=>Loc::getMessage("commerce.loyaltyprogram_PROPS_NAME"),
						'TYPE'=>'NUMBER',
						'REQUIRED'=>'N',
						'DEFAULT_VALUE'=>'',
						'SORT'=>100,
						'USER_PROPS'=>'N',
						'IS_LOCATION'=>'N',
						'PROPS_GROUP_ID'=>$nextGroup['ID'],
						'DESCRIPTION'=>'',
						'IS_EMAIL'=>'N',
						'IS_PROFILE_NAME'=>'N',
						'IS_PAYER'=>'N',
						'IS_LOCATION4TAX'=>'N',
						'IS_FILTERED'=>'N',
						'CODE'=>'commerce_bonus',
						'IS_ZIP'=>'N',
						'IS_PHONE'=>'N',
						'IS_ADDRESS'=>'N',
						'ACTIVE'=>'Y',
						'UTIL'=>'N',
						'INPUT_FIELD_LOCATION'=>'0',
						'MULTIPLE'=>'N',
						'SETTINGS'=>[
							'MIN'=>'0',
							'MAX'=>'',
							'STEP'=>''
						]					
					]);
				}
			}
		}
	}
	
	public function getProfile(){
		return $this->profileSetting;
	}
	
	public static function getProfileByType($type){
		$classNameNew='\Commerce\Loyaltyprogram\\Profiles\\'.$type;
		return new $classNameNew();
	}
	
	public static function getActiveProfileByType($type){
		$idActiveProfiles=[];
		global $DB;
		$tmpSettings=Loyaltyprogram\Settings::getInstance();
		$oprions=$tmpSettings->getOptions();
		//if($oprions['ref_active']=='Y'){
			$res=$DB->Query('select * from '.$tmpSettings->getTableProfilesList().' where type="'.$type.'" and active="Y" order by sort asc,id asc;');
			while($row = $res->Fetch()){
				$idActiveProfiles[]=$row['id'];
			}
		//}
		return $idActiveProfiles;
	}
	
	public static function getProfileById($id){
		global $DB;
		$tmpSettings=Loyaltyprogram\Settings::getInstance();
		$res=$DB->Query('select * from '.$tmpSettings->getTableProfilesList().' where id='.$id.';');
		$row = $res->Fetch();
		$row['settings']=unserialize($row['settings']);
		$row['email_settings']=unserialize($row['email_settings']);
		$row['sms_settings']=unserialize($row['sms_settings']);
		$classNameNew='\\Commerce\\Loyaltyprogram\\Profiles\\'.$row['type'];
		$profileO=new $classNameNew();
		$profileO->setProfile($row);
		return $profileO;
	}
		
	public function getUserByRef($ref){
		//get id user by ref value
		$options=$this->globalSettings->getOptions();
		if($options['ref_link_value']=='XML_ID'){
			$res =\Bitrix\Main\UserTable::getList([
				"select"=>["ID","NAME"],
				"filter"=>['=XML_ID'=>$ref]
			]);
			$ref=0;
			if($arRes = $res->fetch()){
			  $ref=$arRes['ID'];
			}
		}elseif($options['ref_link_value']=='LOGIN'){
			$res =\Bitrix\Main\UserTable::getList([
				"select"=>["ID","NAME"],
				"filter"=>['=LOGIN'=>$ref]
			]);
			$ref=0;
			if($arRes = $res->fetch()){
			  $ref=$arRes['ID'];
			}
		}elseif($options['ref_link_value']=='PROP'){
			$ar_res = \CUserTypeEntity::GetByID($options['ref_prop']);
			$res =\Bitrix\Main\UserTable::getList([
				"select"=>["ID","NAME"],
				"filter"=>['='.$options['ref_prop']=>$ref]
			]);
			$ref=0;
			if($arRes = $res->fetch()){
			  $ref=$arRes['ID'];
			}
		}else{
			// global $DB;
			// $results=$DB->Query('select * from b_user where id='.$ref);
			$ref = htmlspecialchars($ref);
			$ref = (int)$ref;
			$results = \CUser::GetByID($ref);
			$ref=0;
			if($arUser = $results->Fetch()){
				$ref=$arUser['ID'];
			}
		}
		return $ref;
	}
	
	private function checkUserGroup($idUser){
		$refGroups=$this->moduleOptions['ref_link_group'];
		if(!empty($refGroups)){
			$refGroups=explode(',',$refGroups);
			if(count($refGroups)>0){
				$tmpArr=array_intersect($refGroups, \CUser::GetUserGroup($idUser));
				if(count($tmpArr)>0){
					return true;
				}
			}
			return false;
		}
		return true;
	}
	
	public function getChainReferalByFirstParent($refUserId){
		global $DB;
		$options=$this->globalSettings->getOptions();
		$maxLevel=$options['ref_level'];
		$rewards=[];
		if($maxLevel>0){
			for($key=0; $key<$maxLevel; $key++){
				$res=$DB->Query('select * from '.$this->globalSettings->getTableUsersList().' where user='.$refUserId.';');
				if($row = $res->Fetch()){
					$rewards[]=$row['user'];
					if(empty($row['ref_user'])){
						break;
					}else{
						$refUserId=$row['ref_user'];
					}
				}else{
					break;
				}
			}
		}
		if(count($rewards)==0){
			$rewards[]=$refUserId;
		}
		return $rewards;
	}
	
	public function getChainReferal($userId){
		global $DB, $USER;
		$options=$this->globalSettings->getOptions();
		$maxLevel=$options['ref_level'];
		$context = Application::getInstance()->getContext();
		$request = $context->getRequest();
	
		//$cookieRefId only unauthorized users!!!
		$cookieRefId=$request->getCookie("skwb24_loyaltyprogram_ref");
		if(empty($cookieRefId) && !empty($_SESSION['skwb24_loyaltyprogram_ref'])){
			$cookieRefId=$_SESSION['skwb24_loyaltyprogram_ref'];
		}
		if(!empty($USER) && $USER->IsAuthorized() && empty($_SESSION['sw24_register_ref'])){
			$cookieRefId='';
		}
		//check ref chain in table
		$refUserId=(!empty($cookieRefId))?$this->getUserByRef($cookieRefId):0;
		$userLevel=1;
		
		$res=$DB->Query('select * from '.$this->globalSettings->getTableUsersList().' where user='.$userId.';');
		if(!$row = $res->Fetch()){
			
			if($refUserId>0 && $this->checkUserGroup($refUserId)){
				$resRef=$DB->Query('select * from '.$this->globalSettings->getTableUsersList().' where user='.$refUserId.';');
				if($rowRef = $resRef->Fetch()){
					$userLevel=$rowRef['level']+1;
				}else{
					$DB->Insert($this->globalSettings->getTableUsersList(), [
						'user'=>$refUserId,
						'ref_user'=>0,
						'level'=>1
					], $err_mess.__LINE__);
					$userLevel=2;
				}
			}
			
			$typeRef=($refUserId>0)?'link':'simple';
			
			$DB->Insert($this->globalSettings->getTableUsersList(), [
				'user'=>$userId,
				'ref_user'=>$refUserId,
				'type'=>'"'.$typeRef.'"',
				'level'=>$userLevel,
				'date_create'=>'NOW()'
			], $err_mess.__LINE__);
			if($refUserId>0 && $typeRef=='link'){
				Loyaltyprogram\Statistic::setRegisterByLink($refUserId, $userId);
			}
			
			//fire event register in refsystem
			$event = new \Bitrix\Main\Event($this->globalSettings->getModuleId(), "OnRegisterInRefSystem",[
				'USER_ID'=>$userId,
				'REFERRAL_ID'=>$refUserId,
				'PROFILE_ID'=>$this->profileSetting['id']
			]);
			$event->send();
		}else{
			$refUserId=$row['ref_user'];
		}
		//get chain rewards
		$rewards=[];
		if($maxLevel>0){
			for($key=0; $key<$maxLevel; $key++){
				
				$res=$DB->Query('select * from '.$this->globalSettings->getTableUsersList().' where user='.$refUserId.';');
				if($row = $res->Fetch()){
					$rewards[]=$row['user'];
					if(empty($row['ref_user'])){
						break;
					}else{
						$refUserId=$row['ref_user'];
					}
				}else{
					break;
				}
			}
		}
		return $rewards;
	}
	
	protected function registerEvent($module, $moduleEvent, $event){
		$register=true;
		$handlers = BitrixEventManager::getInstance()->findEventHandlers($module, $moduleEvent);
		foreach($handlers as $nextHandler){
			if($nextHandler['TO_MODULE_ID']==$this->globalSettings->getModuleId() && $nextHandler['TO_CLASS']==$event){
				$register=false;
				break;
			}
		}
		if($register===true){
			BitrixEventManager::getInstance()->registerEventHandler(
				$module,
				$moduleEvent,
				$this->globalSettings->getModuleId(),
				"Commerce\\Loyaltyprogram\\Eventmanager",
				$event
			);
		}
		return true;
	}
	
	protected function registerAgent($function, $period=86400, $delay=0){
		$delayExec=ConvertTimeStamp(time()+$delay, "FULL", LANGUAGE_ID);
		\CAgent::AddAgent("\\Commerce\\Loyaltyprogram\\Eventmanager::".$function."();", $this->globalSettings->getModuleId(), "N", $period, "", "Y", $delayExec);
	}
	
	private function getDescription(){
		$tmpPath=$_SERVER["DOCUMENT_ROOT"].'/bitrix/modules/'.Loyaltyprogram\Settings::getInstance()->getModuleId().'/include/descprofiles/'.strtolower($this->profileSetting['type']).'.html';
		if(file_exists($tmpPath)){
			$tmpStr=file_get_contents($tmpPath);
			if(LANG_CHARSET!='windows-1251'){
				$tmpStr=iconv('windows-1251', 'utf-8', $tmpStr);
			}
			return $tmpStr;
		}
		return false;
	}
	
	protected function drawRow($type){
		switch ($type){
			case 'type':
			$tmpProfiles=new Loyaltyprogram\Profiles;
			$tmpProfiles=$tmpProfiles->getListProfiles();
			$desc=$this->getDescription();
			?>
			<tr><td width="40%" style="vertical-align:top;"><?=Loc::getMessage("commerce.loyaltyprogram_PARAM_TYPE")?></td><td>
				<?if($desc!==false){?>
				<div class="outer_desc">
					<a id="show_desc_profile" href="javascript:void(0);"><?=$tmpProfiles[$this->profileSetting['type']]?></a>
					<div style="display:none;" class="inner_desc"><?=$desc?></div>
					<script>
						BX('show_desc_profile').addEventListener('click', function (event) {
							let cblock=this.parentNode.querySelector('.inner_desc'),
								cView=(cblock.style.display=='block')?'none':'block';
								cblock.style.display=cView;
						});
					</script>
				</div>
				<?}else{?>
					<?=$tmpProfiles[$this->profileSetting['type']]?>
				<?}?>
			</td></tr>
			<?break; case 'baseCalculate':?>
			<tr><td width="40%" style="vertical-align:top;"><?=Loc::getMessage("commerce.loyaltyprogram_PARAM_BASECALCULATE")?></td>
			<td>
				<select name="base_calculate">
					<?
					$selectedN='';
					if(!empty($this->profileSetting['settings']['base_calculate']) && $this->profileSetting['settings']['base_calculate']=='N'){
						$selectedN=' selected';
					}
					?>
					<option value="Y"><?=Loc::getMessage("commerce.loyaltyprogram_Y")?></option>
					<option value="N"<?=$selectedN?>><?=Loc::getMessage("commerce.loyaltyprogram_N")?></option>
				</select>
			</td></tr>
			<?break; case 'profileActive':
			$checked=($this->profileSetting['active']=='N' || empty($this->profileSetting['active']))?'':' checked="checked"';?>
			<tr><td width="40%"><?=Loc::getMessage("commerce.loyaltyprogram_PARAM_ACTIVE")?></td><td><input type="checkbox" name="active" value="Y"<?=$checked?> /></td></tr>
			<?break; case 'profileName':
				if(empty($this->profileSetting['name'])){
					$listProfiles=new \Commerce\Loyaltyprogram\Profiles;
					$availableProfiles=$listProfiles->getListProfiles();
					$profileName=$availableProfiles[$this->profileSetting['type']];
				}else{
					$profileName=$this->profileSetting['name'];
				}
			?>
			<tr><td width="40%"><?=Loc::getMessage("commerce.loyaltyprogram_PARAM_NAME")?></td><td><input type="text" name="profile_name" value="<?=$profileName?>" /></td></tr>
			<?break;
			case 'activeSite':
			$siteList=$this->globalSettings->getSites();
			$sizeList=count($siteList)>3?4:(count($siteList)+1);
			?>
			<tr>
				<td width="40%"><?=Loc::getMessage("commerce.loyaltyprogram_PARAM_SITES")?></td>
				<td>
					<select name="site[]" size="<?=$sizeList?>" multiple="multiple"><option value="">...</option>
						<?
						foreach($siteList as $key=>$value){
							$selected=(!empty($this->profileSetting['site']) && in_array($key, $this->profileSetting['site']))?' selected="selected"':'';
							?>
							<option value="<?=$key?>" <?=$selected?>><?=$value?></option>
						<?}?>
					</select>
				</td>
			</tr>
			<?break;case 'propBirthday':
			$propList=$this->globalSettings->getUsersProps(['string', 'date', 'datetime']);?>
			<tr>
				<td width="40%"><?=Loc::getMessage("commerce.loyaltyprogram_PARAM_PROP_BIRTHDAY")?></td>
				<td>
					<select name="prop_birthday"><option value="PERSONAL_BIRTHDAY"><?=Loc::getMessage("commerce.loyaltyprogram_PARAM_PERSONAL_BIRTHDAY")?></option>
						<?foreach($propList as $key=>$value){
							$label=(!empty($value['EDIT_FORM_LABEL']))?$value['EDIT_FORM_LABEL'].' ['.$key.']':$key;
							$selected=(!empty($this->profileSetting['settings']['propbirthday']) && $this->profileSetting['settings']['propbirthday']==$key)?' selected="selected"':'';
							?>
							<option value="<?=$key?>" <?=$selected?>><?=$label?></option>
						<?}?>
					</select>
				</td>
			</tr>
			<?break; case 'propCopyright':
			\Bitrix\Main\Loader::includeModule('catalog');
			?>
			<tr>
				<td width="40%"><?=Loc::getMessage("commerce.loyaltyprogram_PARAM_PROPCOPYRIGHT")?></td>
				<td>
					<select name="prop_copyright">
					<?
					$paramsProp=\CCatalogCondCtrlIBlockProps::GetControlShow(['SHOW_IN_GROUPS'=>['copyrighter']]);
					$selectProp=$this->profileSetting['settings']['prop_copyright'];
					foreach($paramsProp as $nextOptGroup){?>
						<optgroup label="<?=$nextOptGroup['label']?>">
						<?foreach($nextOptGroup['children'] as $prop){
							$propId=explode(':',$prop['controlId']);
							$propId=$propId[2];
							$selected=($selectProp==$propId)?' selected="selected"':'';?>
							<option value="<?=$propId?>"<?=$selected?>><?=$prop['label']?></option>
						<?}?>
						</optgroup>
					<?}?>
					</select>
				</td>
			</tr>
			<?break;
			case 'profileSort':
			$profileSort=empty($this->profileSetting['sort'])?100:$this->profileSetting['sort'];
			?>
			<tr>
				<td width="40%"><?=Loc::getMessage("commerce.loyaltyprogram_PARAM_SORT")?></td>
				<td>
					<input type="number" min="0" step="1" name="sort" value="<?=$profileSort?>" />
				</td>
			</tr>
			<?break;
            case 'bonusProperty':
            ?>
                <tr>
                    <td width="40%"><?=Loc::getMessage("commerce.loyaltyprogram_PARAM_BONUS_PROP")?></td>
                    <td>
                        <select name="prop_bonus">
                            <option value="">...</option><?
                        $selectProp=!empty($this->profileSetting['settings']["prop_bonus"])?$this->profileSetting['settings']["prop_bonus"]:'';
                        $optns=$this->globalSettings->getOptions();
                        $filterProps=[];
                        if(!empty($optns['filter_prop'])){
                            $data = \Bitrix\Iblock\PropertyTable::getList(['filter'=>['ID'=>explode(',',$optns['filter_prop'])]]);
                            while($row=$data->fetch()){
                                $filterProps[$row['IBLOCK_ID']][] = $row['ID'];
                            }
                        }
                        $data = \Bitrix\Catalog\CatalogIblockTable::getList([
                            'select'=>['*', 'iblockName'=>'IBLOCK.NAME'],
                            'order'=>['iblockName'=>'ASC']
                        ])->fetchAll();
                        foreach($data as $nextIblock){
                            if(count($filterProps)>0 && empty($filterProps[$nextIblock['IBLOCK_ID']])){
                                continue;
                            }
                            $currentFilter=["ACTIVE" => "Y", "IBLOCK_ID" => $nextIblock['IBLOCK_ID']];
                            if(!empty($filterProps[$nextIblock['IBLOCK_ID']])){
                                $currentFilter['ID']=$filterProps[$nextIblock['IBLOCK_ID']];
                            }
                            ?>
                            <optgroup label="<?=$nextIblock['iblockName']?> [<?=$nextIblock['IBLOCK_ID']?>]"></optgroup>
                            <?
                            //$properties = \CIBlockProperty::GetList(["sort" => "asc", "name" => "asc"], $currentFilter);
                            $data = \Bitrix\Iblock\PropertyTable::getList([
                                'filter'=>$currentFilter,
                                'order'=>["sort" => "asc", "name" => "asc"]
                            ]);
                            while ($prop_fields = $data->fetch()) {
                                $selected=(!empty($selectProp) && $prop_fields['ID']==$selectProp)?' selected="selected"':'';?>
                                <option value="<?=$prop_fields['ID']?>"<?=$selected?>><?=$prop_fields['NAME']?> [<?=$prop_fields['ID']?>]</option>
                            <?}?>
                        <?}?>
                        </select>
                    </td>
                </tr>
            <?break;
		}
	}
	
	//sms setting
	protected function checkSMSMain(){
		$sites=array_keys($this->globalSettings->getSites());
		$isUpdate=false;
		foreach($this->profileSetting['sms_settings'] as $keyEvent=>&$valEvent){
			
			try{
				$res=\Bitrix\Main\Mail\Internal\EventTypeTable::getList(
					['filter'=>['EVENT_NAME'=>$keyEvent, 'EVENT_TYPE'=>'sms']]
				);
				$nextRes=$res->fetch();
			}catch (\Exception $e) {
				continue;
			}
			
			if($nextRes==false){
				$fieldsEvent=$this->SMSType($keyEvent);
				$resAdd=\Bitrix\Main\Mail\Internal\EventTypeTable::add($fieldsEvent);
				$id = $resAdd->getId();
			}
			foreach($valEvent as $keyTmplt=>&$nextTmplt){
				if($nextTmplt==0){
					$isUpdate=true;
					$fieldsEvent=$this->SMSTemplates($keyTmplt);
					$entity = \Bitrix\Main\Sms\TemplateTable::getEntity();
					$site = \Bitrix\Main\SiteTable::getEntity()->wakeUpObject($sites[0]);
					$template = $entity->createObject();
					foreach($fieldsEvent as $field => $value){
						$template->set($field, $value);
					}
					$template->addToSites($site);
					$template->save();
					$nextTmplt=$template->getId();
				}
			}
		}
		if($isUpdate){
			global $DB;
			$DB->Update($this->globalSettings->getTableProfilesList(), [
				'sms_settings'=>"'".serialize($this->profileSetting['sms_settings'])."'"
			], "where id='".$this->profileSetting['id']."'", $err_mess.__LINE__);
		}
	}
	
	//email setting
	protected function checkEmailMain(){
		
		//add a check for the existence of templates - and if deleted, create anew and overwrite in the profile!!!
		
		$isUpdate=false;
		foreach($this->profileSetting['email_settings'] as $keyEvent=>&$valEvent){
			$res=\Bitrix\Main\Mail\Internal\EventTypeTable::getList(
				['filter'=>['EVENT_NAME'=>$keyEvent]]
			);
			if(!$nextRes=$res->fetch()){
				$fieldsEvent=$this->mailType($keyEvent);
				$fieldsEvent['DESCRIPTION']=preg_replace('/[ ]{2,}|[\t]/', ' ', trim($fieldsEvent['DESCRIPTION']));
				$resAdd=\Bitrix\Main\Mail\Internal\EventTypeTable::add($fieldsEvent);
				$id = $resAdd->getId();
			}
			foreach($valEvent as $keyTmplt=>&$nextTmplt){
				if($nextTmplt==0){
					$isUpdate=true;
					$fieldsEvent=$this->mailTemplates($keyTmplt);
					$fieldsEvent['MESSAGE']=preg_replace('/[ ]{2,}|[\t]/', ' ', trim($fieldsEvent['MESSAGE']));
					$resAdd=\Bitrix\Main\Mail\Internal\EventMessageTable::add($fieldsEvent);
					//$id = $resAdd->getId();
					$nextTmplt=$resAdd->getId();
					$tmpSites=array_keys($this->globalSettings->getSites());
					foreach($tmpSites as $nextSite){
						\Bitrix\Main\Mail\Internal\EventMessageSiteTable::add([
							'EVENT_MESSAGE_ID'=>$nextTmplt,
							'SITE_ID'=>$nextSite
						]);
					}
				}
			}
		}
		//insert new email templates into profile table
		if($isUpdate){
			global $DB;
			$DB->Update($this->globalSettings->getTableProfilesList(), [
				'email_settings'=>"'".serialize($this->profileSetting['email_settings'])."'"
			], "where id='".$this->profileSetting['id']."'", $err_mess.__LINE__);
		}
	}
	
	protected function checkSMSList(){
		$this->profileSetting['sms_settings']=[];
	}
	
	private function getStatusSMSTemplates(){
		$activeIDS=[];
		global $DB;
		$results=$DB->Query('SHOW tables like "%b_sms_template%"');
		if(!$results->Fetch()){
			return [];
		}
		foreach($this->profileSetting['sms_settings'] as $keyEvent=>$nextEvent){
			$results=$DB->Query('select * from b_sms_template where EVENT_NAME="'.$keyEvent.'";');
			while($arTemplate = $results->Fetch()){
				if($arTemplate['ACTIVE']=='Y'){
					$activeIDS[]=$arTemplate['ID'];
				}
			}
		}
		return $activeIDS;
	}
	
	public function drawSMSList(){
		?>
		<tr class="heading">
			<td colspan="2"><?=Loc::getMessage("commerce.loyaltyprogram_TAB_REF_SMS");?></td>
		</tr>
		<?
		$this->checkSMSList();
		$activeIDS=$this->getStatusSMSTemplates();
		if(count($this->profileSetting['sms_settings'])>0){
			foreach($this->profileSetting['sms_settings'] as $keyEvent=>$nextEvent){
				$SMSType=$this->SMSType($keyEvent);
				foreach($nextEvent as $keyTemplate=>$nextTemplate){
					$activeShecked=in_array($nextTemplate, $activeIDS)?Loc::getMessage("commerce.loyaltyprogram_ACTIVE"):Loc::getMessage("commerce.loyaltyprogram_NOACTIVE");?>
				<tr>
					<td width="50%"><?=Loc::getMessage("commerce.loyaltyprogram_".$SMSType['EVENT_NAME']."_".$keyTemplate)?></td>
					<td>
						<a href="/bitrix/admin/sms_template_edit.php?lang=<?=LANGUAGE_ID?>&ID=<?=$nextTemplate?>" target="_blank">#<?=$nextTemplate?></a> <span>(<?=$activeShecked?>)</span><br>
					</td>
				</tr>
				<?}
			}
		}else{?>
			<tr>
				<td>
					<?=Loc::getMessage("commerce.loyaltyprogram_SMS_TEMPOPARY_UNAVAILABLE")?>
				</td>
			</tr>
		<?}?>
	<?}
	
	private function getStatusEmailTemplates(){
		$activeIDS=[];
		foreach($this->profileSetting['email_settings'] as $keyEvent=>$nextEvent){
			$rsMess = \CEventMessage::GetList($by="site_id", $order="desc", ['TYPE_ID'=>$keyEvent]);
			while($arMess = $rsMess->GetNext()){
				if($arMess["ACTIVE"]=='Y'){
					$activeIDS[]=$arMess["ID"];
					
				}
			}
		}
		return $activeIDS;
	}
	
	public function drawEmailList(){
		?>
		<tr class="heading">
			<td colspan="2"><?=Loc::getMessage("commerce.loyaltyprogram_TAB_REF_MAIL");?></td>
		</tr>
		<?
		$this->checkEmailList();
		$activeIDS=$this->getStatusEmailTemplates();
		foreach($this->profileSetting['email_settings'] as $keyEvent=>$nextEvent){
			$mailType=$this->mailType($keyEvent);
			?>
			<?foreach($nextEvent as $keyTemplate=>$nextTemplate){
					$mailTemplate=$this->mailTemplates($keyTemplate);
					$activeShecked=in_array($nextTemplate, $activeIDS)?Loc::getMessage("commerce.loyaltyprogram_ACTIVE"):Loc::getMessage("commerce.loyaltyprogram_NOACTIVE");
					?>
			<tr>
				<td width="50%"><?=Loc::getMessage("commerce.loyaltyprogram_".$mailType['EVENT_NAME']."_".$keyTemplate)?></td>
				<td>
					<a href="/bitrix/admin/message_edit.php?lang=<?=LANGUAGE_ID?>&ID=<?=$nextTemplate?>" target="_blank">#<?=$nextTemplate?></a> <span>(<?=$activeShecked?>)</span><br>
				</td>
			</tr>
			<?}?>
		<?}
	}
	//e. o. email setting
	
	protected function clearSites($sites){
		if(!empty($sites) && count($sites)>0){
			$sites=array_diff($sites, ['']);
			if(count($sites)>0){
				return implode(',',$sites);
			}
		}
		return '';
	}
	
	/**
	*condition block
	*/
	public function getBaseCondition($mode=''){
		$params=[
			'parentContainer'=>'popupPropsCont',
			'form'=>'',
			'formName'=>'pfofiles_edit_block',
			'sepID'=>'__',
			'prefix'=>'condTreeLoyalty',
			'messTree'=>[
				'SELECT_CONTROL'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_SELECT_GROUPCONTROL"),
				'ADD_CONTROL'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_ADD_GROUPCONTROL"),
				'DELETE_CONTROL'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_DELETE_CONTROL")
			]
		];
		if($mode=='json'){
			return \Bitrix\Main\Web\Json::encode($params);
		}
		return $params;
	}
	
	public function getCurrentCondition($mode=''){
		$settings=$this->getProfile();
		$params=(!empty($settings['settings']['condition']))?$settings['settings']['condition']:[];
		if(empty($params['children'])){
			$params=$this->getStartCondition();
		}
		if($mode=='json'){
			return \Bitrix\Main\Web\Json::encode($params);
		}
		return $params;
	}
	
	private function setCondNode($key, $val){
		if(isset($val['number_action']) && empty($val['number_action'])){
			$val['number_action']=(string) Loyaltyprogram\Tools::getLastAction();
		}
		\Bitrix\Main\Loader::includeModule('iblock');
		$node=[];
		$node['id']=$key;
		foreach($val as $condKey=>$condVal){
			if($condKey=='controlId'){
				$node['controlId']=$condVal;
			}elseif($condKey=='aggregator'){
				$node['values']['All']=$condVal;
				//$node['group']=true;
				$node['children']=[];
			}elseif($condKey=='value'){
				$node['values']['True']=$condVal;
				$node['values']['value']=$condVal;
			}else{
				if(is_array($condVal)){
					$condVal=array_values(array_diff($condVal, ['']));
				}
				if(!empty($condVal)){
					if($node['controlId']=='productBasket' && (int) $condVal>0){
						$tmp_label=\CIBlockElement::GetList(array(),array('ID'=>$condVal),false,false,array('NAME'));
						if($tmp_label=$tmp_label->Fetch()){
							$node['labels']['product_basket'][0]=$tmp_label['NAME'];
						}
					}elseif($node['controlId']=='product' && (int) $condVal>0){
						$tmp_label=\CIBlockElement::GetList(array(),array('ID'=>$condVal),false,false,array('NAME'));
						if($tmp_label=$tmp_label->Fetch()){
							$node['labels']['product'][0]=$tmp_label['NAME'];
						}
					}
					elseif($node['controlId']=='sectionBasket' && (int) $condVal>0){
						$tmp_label=\CIBlockSection::GetList(array(),array('ID'=>(int) $condVal),false,array('NAME'));
						if($tmp_label=$tmp_label->Fetch()){
							$node['labels']['section_basket'][0]=$tmp_label['NAME'];
						}
					}
					elseif($node['controlId']=='productCat' && (int) $condVal>0){
						$tmp_label=\CIBlockSection::GetList(array(),array('ID'=>(int) $condVal),false,array('NAME'));
						if($tmp_label=$tmp_label->Fetch()){
							$node['labels']['product_cat'][0]=$tmp_label['NAME'];
						}
					}elseif($node['controlId']=='dateRegister' && $condKey=='date_register'){
					    $tmstmp=new \Bitrix\Main\Type\Date($condVal);
                        $node['timestamp']=$tmstmp->getTimestamp();
                    }
				}
				$node['values'][$condKey]=$condVal;
			}
		}
		return $node;
	}
	
	public function getTreeFromRequest($nameCond='condTreeLoyalty'){
		$cond=[];
		if(!empty($_REQUEST[$nameCond])){
			$tmpCond=$_REQUEST[$nameCond];
			foreach($tmpCond as $key=>$val){
				$keys=explode('__', $key);
				switch (count($keys)){
					case 1:
						$cond=$this->setCondNode($keys[0], $val);
						break;
					case 2:
						$cond['children'][]=$this->setCondNode($keys[1], $val);
						$levelCond_2=&$cond['children'][count($cond['children'])-1];
						//fix error number count
						$cCount=(!empty($cond['children']) && count($cond['children'])>0)?(count($cond['children'])-1):0;
						$levelCond_2['id']=$cCount;
						//e. o. fix error number count
						break;
					case 3:
						$levelCond_2['children'][]=$this->setCondNode($keys[2], $val);
						$levelCond_3=&$levelCond_2['children'][count($levelCond_2['children'])-1];
						//fix error number count
						$cCount=(!empty($levelCond_2['children']) && count($levelCond_2['children'])>0)?(count($levelCond_2['children'])-1):0;
						$levelCond_3['id']=$cCount;
						//e. o. fix error number count
						break;
					case 4:
						$levelCond_3['children'][]=$this->setCondNode($keys[3], $val);
						$levelCond_4=&$levelCond_3['children'][count($levelCond_3['children'])-1];
						break;
					case 5:
						$levelCond_4['children'][]=$this->setCondNode($keys[4], $val);
						break;
				}
			}
		}
		return $cond;
	}
	
	protected function checkConditionChildren($logic, $currentVal, $settingVal){
		$isVal=false;
		if(
			$logic=='more' && $currentVal>=$settingVal ||
			$logic=='less' && $currentVal<$settingVal
		){
			return true;
		}
		if(
			$logic=='more' && $currentVal<$settingVal ||
			$logic=='less' && $currentVal>=$settingVal
		){
			return false;
		}
		if(
			(is_array($settingVal) && !is_array($currentVal) && in_array($currentVal, $settingVal)) ||
			(is_array($currentVal) && !is_array($settingVal) && in_array($settingVal, $currentVal)) ||
			(is_array($settingVal) && is_array($currentVal) && count(array_intersect($currentVal, $settingVal))>0) ||
			($settingVal==$currentVal && !is_array($currentVal))
		){
			$isVal=true;
		}
		if(
			($logic=='Equal' && $isVal) ||
			($logic=='Not' && !$isVal)
		){
			return true;
		}else{
			return false;
		}
	}
	
	public function getRankCoeff($user_id){
		if(!empty($this->profileSetting['type']) && !empty($user_id)){
			global $DB;
			$profile_type=$this->profileSetting['type'];
			$results=$DB->Query('select * from commerce_loyal_rank_users, commerce_loyal_ranks where
				commerce_loyal_ranks.id=commerce_loyal_rank_users.rank_id
				and commerce_loyal_rank_users.user_id='.$user_id.'
				and commerce_loyal_rank_users.active="Y"
				and commerce_loyal_ranks.active="Y";'
			);
			if($row = $results->Fetch()){
				$profiles=unserialize($row['profiles']);
				if(in_array($profile_type, $profiles)){
					return $row['coeff'];
				}
			}
		}
		return 1;
	}

	protected function getAlreadyProps($node=false){
	    if($node===false){
	        $this->alReadyProps=[];
            foreach($this->profileSetting['settings']["condition"]["children"] as $nextNode){
                $this->getAlreadyProps($nextNode);
            }
        }else{
            if(!empty($node['children'])){
                $this->getAlreadyProps($node['children']);
            }else{
                foreach ($node as $nextNode){
                    if(is_array($nextNode) && !empty($nextNode["controlId"])){
                        if(stripos($nextNode["controlId"], 'CondIBProp:')!==false){
                            $tmpPropArr=explode(':', $nextNode["controlId"]);
                            if(!empty($tmpPropArr[2]) && (int) $tmpPropArr[2]>0){
                                $this->alReadyProps[]=(int) $tmpPropArr[2];
                            }
                        }
                    }
                }
            }
        }
        return $this->alReadyProps;
    }
	
}

?>