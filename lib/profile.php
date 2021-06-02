<?
namespace Commerce\Loyaltyprogram;
use \Bitrix\Main\Localization\Loc,
	\Bitrix\Main\Application,
	\Bitrix\Main\EventManager as BitrixEventManager;
Loc::loadMessages(__DIR__ .'/lang.php');

\Bitrix\Main\Loader::includeModule('sale');
/**
main profile class
*/
class Profile{

	protected $profileSetting;
	protected $timePart;
	
	function __construct (){
		$this->globalSettings=Settings::getInstance();
		$this->profileSetting=[
			'id'=>'new',
			'active'=>'N',
			'name'=>'',
			'type'=>'',
			'site'=>'',
			'date_setting'=>'',
			'settings'=>[],
			'email_settings'=>[]
		];
		$this->timePart=[
			'hour'=>3600,
			'day'=>86400,
			'week'=>604800,
			'month'=>2592000
		];
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
		$props=[];
		$res=\Bitrix\Sale\Internals\OrderPropsTable::getList(
			['filter'=>['CODE'=>'commerce_bonus']]
		);
		while($nextRes=$res->fetch()){
			unset($groups[$nextRes['PROPS_GROUP_ID']]);
			$props[]=$nextRes;
		}
		
		if(count($groups)>0){
			foreach($groups as $nextGroup){
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
						'MIN'=>0,
						'MAX'=>'',
						'STEP'=>''
					]					
				]);
			}
		}
	}
	
	public function getProfile(){
		return $this->profileSetting;
	}
	
	public static function getProfileByType($type){
		$classNameNew='\Commerce\Loyaltyprogram\\'.$type;
		return new $classNameNew();
	}
	
	public static function getActiveProfileByType($type){
		$idActiveProfiles=[];
		global $DB;
		$tmpSettings=Settings::getInstance();
		$oprions=$tmpSettings->getOptions();
		if($oprions['ref_active']=='Y'){
			$res=$DB->Query('select * from '.$tmpSettings->getTableProfilesList().' where type="'.$type.'" and active="Y" order by sort asc,id asc;');
			if($row = $res->Fetch()){
				$idActiveProfiles[]=$row['id'];
			}
		}
		return $idActiveProfiles;
	}
	
	public static function getProfileById($id){
		global $DB;
		$tmpSettings=Settings::getInstance();
		$res=$DB->Query('select * from '.$tmpSettings->getTableProfilesList().' where id='.$id.';');
		$row = $res->Fetch();
		$row['settings']=unserialize($row['settings']);
		$row['email_settings']=unserialize($row['email_settings']);
		$classNameNew='\\Commerce\\Loyaltyprogram\\'.$row['type'];
		$profileO=new $classNameNew();
		$profileO->setProfile($row);
		return $profileO;
	}
	
	public function drawReferal(){
		$options=$this->globalSettings->getOptions();
		$maxLevel=$options['ref_level'];
		if(empty($this->profileSetting['settings']['rewards'])){
			$this->profileSetting['settings']['rewards']=[0];
		}
		$additionalLink=0;
		for($i=1; $i<=$maxLevel; $i++){
			if(!isset($this->profileSetting['settings']['rewards'][($i-1)])){
				$additionalLink++;
				$hide=' style="display:none;"';
				$disabled=' disabled="disabled"';
				$nextVal=0;
			}else{
				$hide='';
				$disabled='';
				$nextVal=$this->profileSetting['settings']['rewards'][($i-1)];
			}
			?>
			<tr class="ref_level"<?=$hide?>>
				<td width="40%"><?=Loc::getMessage("commerce.loyaltyprogram_PARAM_REWARDS", ["#NUM#"=>$i])?></td>
				<td>
					<input type="number" min="0" step="1" name="rewards[]" value="<?=$nextVal?>"<?=$disabled?> />
					<?if(!empty($this->profileSetting['settings']['rewards_unit'])){?>
						<select name="rewards_unit[]">
						<?
							$selectedBonus=(!empty($this->profileSetting['settings']['rewards_unit'][($i-1)]) && $this->profileSetting['settings']['rewards_unit'][($i-1)]=='bonus')?' selected="selected"':'';
							$selectedPercent=(!empty($this->profileSetting['settings']['rewards_unit'][($i-1)]) && $this->profileSetting['settings']['rewards_unit'][($i-1)]=='percent')?' selected="selected"':'';
						?>
							<option value="bonus"<?=$selectedBonus?>><?=Loc::getMessage("commerce.loyaltyprogram_PARAM_UNITS_BONUS")?></option>
							<option value="percent"<?=$selectedPercent?>><?=Loc::getMessage("commerce.loyaltyprogram_PARAM_UNITS_PERCENT")?></option>
						</select>
					<?}?>
				</td>
			</tr>
		<?}
		if($additionalLink>0){?>
			<tr>
				<td width="40%"></td>
				<td>
					<a href="javascript:void(0);" class="ref_link"><?=Loc::getMessage("commerce.loyaltyprogram_PARAM_ADDREWARDS")?></a>
					<script>
						var refLevel=document.querySelectorAll('.ref_level'),
							refLink=document.querySelector('.ref_link');
						refLink.addEventListener('click', function(){
							var hideLink=true;
							for(var i=0; i<refLevel.length; i++){
								if(refLevel[i].style.display=='none'){
									refLevel[i].style.display='table-row';
									refLevel[i].querySelector('input[type=number').disabled=false;
									break;
								}
							}
							for(var i=0; i<refLevel.length; i++){
								if(refLevel[i].style.display=='none'){
									hideLink=false;
									break;
								}
							}
							if(hideLink){
								this.parentNode.parentNode.remove();
							}
						});
					</script>
				</td>
			</tr>
		<?}
	}
	
	public function getUserByRef($ref){
		//get id user by ref value
		$options=$this->globalSettings->getOptions();
		if($options['ref_link_value']=='XML_ID'){
			$res =\ Bitrix\Main\UserTable::getList([
				"select"=>["ID","NAME"],
				"filter"=>['=XML_ID'=>$ref]
			]);
			$ref=0;
			if($arRes = $res->fetch()){
			  $ref=$arRes['ID'];
			}
		}elseif($options['ref_link_value']=='PROP'){
			$ar_res = \CUserTypeEntity::GetByID($options['ref_prop']);
			$res =\ Bitrix\Main\UserTable::getList([
				"select"=>["ID","NAME"],
				"filter"=>['='.$options['ref_prop']=>$ref]
			]);
			$ref=0;
			if($arRes = $res->fetch()){
			  $ref=$arRes['ID'];
			}
		}else{
			$rsUser = \CUser::GetByID($ref);
			$ref=0;
			if($arUser = $rsUser->Fetch()){
				$ref=$arUser['ID'];
			}
		}
		return $ref;
	}
	
	public function getChainReferal($userId, $summ=0){
		global $DB;
		$options=$this->globalSettings->getOptions();
		$maxLevel=$options['ref_level'];
		$context = Application::getInstance()->getContext();
		$request = $context->getRequest();
		$cookieRefId=$request->getCookie("skwb24_loyaltyprogram_ref");
		if(empty($cookieRefId) && !empty($_SESSION['skwb24_loyaltyprogram_ref'])){
			$cookieRefId=$_SESSION['skwb24_loyaltyprogram_ref'];
		}
		//check ref chain in table
		$refUserId=(!empty($cookieRefId))?$this->getUserByRef($cookieRefId):0;
		$userLevel=1;
		
		$res=$DB->Query('select * from '.$this->globalSettings->getTableUsersList().' where user='.$userId.';');
		if(!$row = $res->Fetch()){
			
			if($refUserId>0){
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
			
			$DB->Insert($this->globalSettings->getTableUsersList(), [
				'user'=>$userId,
				'ref_user'=>$refUserId,
				'level'=>$userLevel,
				'date_create'=>'NOW()'
			], $err_mess.__LINE__);
		}else{
			$refUserId=$row['ref_user'];
		}
		
		//get chain rewards
		$rewards=[];
		if(!empty($this->profileSetting['settings']['rewards'])){
			foreach($this->profileSetting['settings']['rewards'] as $key=>$val){
				if($key==$maxLevel){
					break;
				}
				$res=$DB->Query('select * from '.$this->globalSettings->getTableUsersList().' where user='.$refUserId.';');
				if($row = $res->Fetch()){
					$userLevel=$row['level']+1;
					if(!empty($this->profileSetting['settings']['rewards_unit'][$key]) && $this->profileSetting['settings']['rewards_unit'][$key]=='percent'){
						$val=$summ*$val/100;
					}
					if(!empty($this->profileSetting['settings']['round_bonus']) && $this->profileSetting['settings']['round_bonus']!='none'){
						$val=$this->profileSetting['settings']['round_bonus']($val);
					}
					$rewards[$row['user']]=$val;
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
	
	/*protected function unRegisterEvent($module, $moduleEvent, $event){
		$handlers = BitrixEventManager::getInstance()->findEventHandlers($module, $moduleEvent);
		foreach($handlers as $nextHandler){
			if($nextHandler['TO_MODULE_ID']==$this->globalSettings->getModuleId() && $nextHandler['TO_METHOD']==$event){
				BitrixEventManager::getInstance()->unRegisterEventHandler(
					$module,
					$moduleEvent,
					$this->globalSettings->getModuleId(),
					"Commerce\\Loyaltyprogram\\Eventmanager",
					$event
				);
				break;
			}
		}
	}*/
	
	protected function drawRow($type){
		switch ($type){
			case 'type':
			$tmpProfiles=new Profiles;
			$tmpProfiles=$tmpProfiles->getListProfiles()
			?>
			<tr><td width="40%"><?=Loc::getMessage("commerce.loyaltyprogram_PARAM_TYPE")?></td><td><?=$tmpProfiles[$this->profileSetting['type']]?></td></tr>
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
			<?break; case 'nextCharge':
			if($this->profileSetting['id']!='new'){
				$period=Tools::getPeriod($this->profileSetting['settings']['bonuses']['bonus_period']);?>
				<tr><td colspan="2"><?\CAdminMessage::ShowMessage(["MESSAGE"=>Loc::getMessage("commerce.loyaltyprogram_PARAM_BONUS_TURNOVER_NEXT", ['#CURRENT#'=>$bxDataStr, '#START#'=>$period['dateFrom']['format'], '#END#'=>$period['dateTo']['format']]), "TYPE"=>"OK","HTML"=>true]);?></td></tr>
			<?}?>
			<?break; case 'bonusSizeTurnover':
			$options=$this->globalSettings->getOptions();
			$bonusSizeTurnover=(!empty($this->profileSetting['settings']['bonuses']['bonus_size_turnover']))?$this->profileSetting['settings']['bonuses']['bonus_size_turnover']:0;?>
			<tr><td width="40%"><?=Loc::getMessage("commerce.loyaltyprogram_PARAM_BONUS_SIZE_TURNOVER")?></td><td><input type="number" min="0" step="1" name="bonus_size_turnover" value="<?=$bonusSizeTurnover?>" /> <?=$options['currency']?> <?/*=Loc::getMessage("commerce.loyaltyprogram_PARAM_UNITS_BONUS")*/?></td></tr>
			<?break; case 'bonusSize':
			$bonusSize=(!empty($this->profileSetting['settings']['bonuses']['bonus_size']))?$this->profileSetting['settings']['bonuses']['bonus_size']:0;?>
			<tr><td width="40%"><?=Loc::getMessage("commerce.loyaltyprogram_PARAM_BONUS_SIZE")?></td><td><input type="number" min="0" step="1" name="bonus_size" value="<?=$bonusSize?>" /> <?=Loc::getMessage("commerce.loyaltyprogram_PARAM_UNITS_BONUS")?></td></tr>
			<?break; case 'bonusLive':
			$bonusLive=(!empty($this->profileSetting['settings']['bonuses']['bonus_live']))?$this->profileSetting['settings']['bonuses']['bonus_live']:0;
			$bonusTypeLive=[
				'day'=>Loc::getMessage("commerce.loyaltyprogram_TIME_DAY"),
				'week'=>Loc::getMessage("commerce.loyaltyprogram_TIME_WEEK"),
				'month'=>Loc::getMessage("commerce.loyaltyprogram_TIME_MONTH")
			];
			?>
			<tr>
				<td width="40%"><?=Loc::getMessage("commerce.loyaltyprogram_PARAM_BONUS_LIVE")?></td>
				<td>
					<input type="number" min="0" step="1" name="bonus_live" value="<?=$bonusLive?>" />
					<select name="bonus_live_type">
						<?foreach($bonusTypeLive as $key=>$value){
							$selected=(!empty($this->profileSetting['settings']['bonuses']['bonus_live_type']) && $this->profileSetting['settings']['bonuses']['bonus_live_type']==$key)?' selected="selected"':'';
							?>
							<option value="<?=$key?>" <?=$selected?>><?=$value?></option>
						<?}?>
					</select>
				</td>
			</tr>
			<?break; case 'bonusPeriod':
			$bonusPeriod=(!empty($this->profileSetting['settings']['bonuses']['bonus_period']))?$this->profileSetting['settings']['bonuses']['bonus_period']:'month';
			$bonusTypePeriod=[
				'week'=>Loc::getMessage("commerce.loyaltyprogram_TIME_WEEK"),
				'month'=>Loc::getMessage("commerce.loyaltyprogram_TIME_MONTH"),
				'quarter'=>Loc::getMessage("commerce.loyaltyprogram_TIME_QUARTER"),
				'year'=>Loc::getMessage("commerce.loyaltyprogram_TIME_YEAR")
			];
			?>
			<tr>
				<td width="40%"><?=Loc::getMessage("commerce.loyaltyprogram_PARAM_BONUS_PERIOD", ['#START#'=>$periodStart, '#END#'=>$periodEnd])?></td>
				<td>
					<select name="bonus_period">
						<?foreach($bonusTypePeriod as $key=>$value){
							$selected=($bonusPeriod==$key)?' selected="selected"':'';
							?>
							<option value="<?=$key?>" <?=$selected?>><?=$value?></option>
						<?}?>
					</select>
					<script>
					<?
						$dataArr=[];
						
						$dataArr['week']=Tools::getPeriod('week');
						$dataArr['month']=Tools::getPeriod('month');
						$dataArr['quarter']=Tools::getPeriod('quarter');
						$dataArr['year']=Tools::getPeriod('year');
					?>
						var bonusPeriod=document.querySelector('[name=bonus_period]');
						function setPeriod(){
							var periodData=<?=\CUtil::PhpToJSObject($dataArr)?>,
								bonusPeriod=document.querySelector('[name=bonus_period]');
							BX('sw24_bonus_period_from').innerHTML=periodData[bonusPeriod.value].dateFrom.format;
							BX('sw24_bonus_period_to').innerHTML=periodData[bonusPeriod.value].dateTo.format;
						}
						BX.ready(function(){
							setPeriod();
							bonusPeriod.addEventListener("change", setPeriod);
						})
					</script>
				</td>
			</tr>
			<?break; case 'bonusDelay':
			$bonusDelay=(!empty($this->profileSetting['settings']['bonuses']['bonus_delay']))?$this->profileSetting['settings']['bonuses']['bonus_delay']:0;
			$bonusTypeSelect=[
				'hour'=>Loc::getMessage("commerce.loyaltyprogram_TIME_HOUR"),
				'day'=>Loc::getMessage("commerce.loyaltyprogram_TIME_DAY"),
				'week'=>Loc::getMessage("commerce.loyaltyprogram_TIME_WEEK"),
				'month'=>Loc::getMessage("commerce.loyaltyprogram_TIME_MONTH")
			];
			?>
			<tr>
				<td width="40%"><?=Loc::getMessage("commerce.loyaltyprogram_PARAM_BONUS_DELAY")?></td>
				<td>
					<input type="number" min="0" step="1" name="bonus_delay" value="<?=$bonusDelay?>" />
					<select name="bonus_delay_type">
						<?foreach($bonusTypeSelect as $key=>$value){
							$selected=(!empty($this->profileSetting['settings']['bonuses']['bonus_delay_type']) && $this->profileSetting['settings']['bonuses']['bonus_delay_type']==$key)?' selected="selected"':'';
							?>
							<option value="<?=$key?>" <?=$selected?>><?=$value?></option>
						<?}?>
					</select>
				</td>
			</tr>
			<?break;case 'activeSite':
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
			<?break;case 'profileSort':
			$profileSort=empty($this->profileSetting['sort'])?100:$this->profileSetting['sort'];
			?>
			<tr>
				<td width="40%"><?=Loc::getMessage("commerce.loyaltyprogram_PARAM_SORT")?></td>
				<td>
					<input type="number" min="0" step="1" name="sort" value="<?=$profileSort?>" />
				</td>
			</tr>
			<?break; case 'withdraw';
			$withdraw=empty($this->profileSetting['settings']['withdraw'])?0:$this->profileSetting['settings']['withdraw'];
			?>
			<tr>
				<td width="40%"><?=Loc::getMessage("commerce.loyaltyprogram_PARAM_WITHDRAW")?></td>
				<td>
					<input type="number" min="0" step="1" name="withdraw" value="<?=$withdraw?>" />
					<select name="withdraw_unit">
					<?
						$selectedBonus=(!empty($this->profileSetting['settings']['withdraw_unit']) && $this->profileSetting['settings']['withdraw_unit']=='bonus')?' selected="selected"':'';
						$selectedPercent=(!empty($this->profileSetting['settings']['withdraw_unit']) && $this->profileSetting['settings']['withdraw_unit']=='percent')?' selected="selected"':'';
					?>
						<option value="bonus"<?=$selectedBonus?>><?=Loc::getMessage("commerce.loyaltyprogram_PARAM_UNITS_BONUS")?></option>
						<option value="percent"<?=$selectedPercent?>><?=Loc::getMessage("commerce.loyaltyprogram_PARAM_UNITS_PERCENT")?></option>
					</select>
				</td>
			</tr>
			<?break; case 'withdrawMax';
			$withdrawMax=empty($this->profileSetting['settings']['withdraw_max'])?0:$this->profileSetting['settings']['withdraw_max'];
			?>
			<tr>
				<td width="40%"><?=Loc::getMessage("commerce.loyaltyprogram_PARAM_WITHDRAW_MAX")?></td>
				<td>
					<input type="number" min="0" step="1" name="withdraw_max" value="<?=$withdrawMax?>" />
				</td>
			</tr>
			<?break; case 'bonusAdd';
			$bonusAdd=!isset($this->profileSetting['settings']['bonuses']['bonus_add'])?100:$this->profileSetting['settings']['bonuses']['bonus_add'];
			?>
			<tr>
				<td width="40%"><?=Loc::getMessage("commerce.loyaltyprogram_PARAM_BONUS_SIZE_PER_ORDER")?></td>
				<td>
					<input type="number" min="0" step="1" name="bonus_add" value="<?=$bonusAdd?>" />
					<select name="bonus_unit">
					<?
						$selectedBonus=(!empty($this->profileSetting['settings']['bonuses']['bonus_unit']) && $this->profileSetting['settings']['bonuses']['bonus_unit']=='bonus')?' selected="selected"':'';
						$selectedPercent=(!empty($this->profileSetting['settings']['bonuses']['bonus_unit']) && $this->profileSetting['settings']['bonuses']['bonus_unit']=='percent')?' selected="selected"':'';
						$percentName=($this->profileSetting['type']=='Turnover')?Loc::getMessage("commerce.loyaltyprogram_PARAM_UNITS_PERCENT_TURNOVER"):Loc::getMessage("commerce.loyaltyprogram_PARAM_UNITS_PERCENT_ORDER");
					?>
						<option value="bonus"<?=$selectedBonus?>><?=Loc::getMessage("commerce.loyaltyprogram_PARAM_UNITS_BONUS")?></option>
						<option value="percent"<?=$selectedPercent?>><?=$percentName?></option>
					</select>
				</td>
			</tr>
			<?break; case 'orderStatuses';?>
			<tr>
				<td width="40%"><?=Loc::getMessage("commerce.loyaltyprogram_PARAM_ORDER_STATUSES")?></td>
				<td>
					<select name="order_statuses"><option value="0">...</option>
					<?foreach($this->globalSettings->getOrderStatuses() as $nextStatus){
						$selected=(!empty($this->profileSetting['settings']['order_statuses']) && $this->profileSetting['settings']['order_statuses']==$nextStatus['STATUS_ID'])?' selected="selected"':'';?>
						<option value="<?=$nextStatus['STATUS_ID']?>"<?=$selected?>>[<?=$nextStatus['STATUS_ID']?>] <?=$nextStatus['NAME']?></option>
					<?}?>
					</select>
				</td>
			</tr>
			<?break; case 'roundBonus';?>
			<tr>
				<td width="40%"><?=Loc::getMessage("commerce.loyaltyprogram_PARAM_ROUND_BONUS")?></td>
				<td><select name="round_bonus"><option value="none"><?=Loc::getMessage("commerce.loyaltyprogram_PARAM_ROUND_BONUS_NO")?></option>
				<?
					$listRound=[
						'ceil'=>Loc::getMessage("commerce.loyaltyprogram_PARAM_ROUND_BONUS_CEIL"),
						'floor'=>Loc::getMessage("commerce.loyaltyprogram_PARAM_ROUND_BONUS_FLOOR"),
						'round'=>Loc::getMessage("commerce.loyaltyprogram_PARAM_ROUND_BONUS_AUTO")
					];
					foreach($listRound as $keyRound=>$valRound){
						$selected=(!empty($this->profileSetting['settings']['round_bonus']) && $this->profileSetting['settings']['round_bonus']==$keyRound)?' selected="selected"':'';?>
						<option value="<?=$keyRound?>"<?=$selected?>><?=$valRound?></option>
					<?}
				?>
				</select></td>
			</tr>
			<?break;
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
	
	public function drawEmailList(){
		$this->checkEmailList();
		foreach($this->profileSetting['email_settings'] as $keyEvent=>$nextEvent){
			$mailType=$this->mailType($keyEvent);
			?>
			<?foreach($nextEvent as $keyTemplate=>$nextTemplate){
					$mailTemplate=$this->mailTemplates($keyTemplate);?>
			<tr>
				<td width="40%"><?=Loc::getMessage("commerce.loyaltyprogram_".$mailType['EVENT_NAME']."_".$keyTemplate)?></td>
				<td>
					<a href="/bitrix/admin/message_edit.php?lang=<?=LANGUAGE_ID?>&ID=<?=$nextTemplate?>" target="_blank">#<?=$nextTemplate?></a><br>
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
	
}

?>