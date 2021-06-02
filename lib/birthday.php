<?
namespace Commerce\Loyaltyprogram;
use \Bitrix\Main\Application,
	Bitrix\Main\Localization\Loc;
/**
type register user
*/
class Birthday extends Profile implements Profileinterface{
	
	function __construct(){
		parent::__construct();
		$this->profileSetting['type']='Birthday';
	}
	
	public function getParametersMain(){
		foreach(['type', 'profileActive', 'profileName', 'profileSort', 'activeSite', 'propBirthday'] as $nextRow){
			$this->drawRow($nextRow);
		}
	}
	
	public function getParametersBonuses(){
		foreach(['bonusSize', 'bonusDelay', 'bonusLive'] as $nextRow){
			$this->drawRow($nextRow);
		}
	}
	
	protected function checkEmailList(){
		$this->profileSetting['email_settings']=empty($this->profileSetting['email_settings'])?[]:$this->profileSetting['email_settings'];
		if(empty($this->profileSetting['email_settings']['COMMERCE_LOYAL_BIRTHDAY'])){
			$this->profileSetting['email_settings']['COMMERCE_LOYAL_BIRTHDAY']=[
				'userTemplate'=>0,
				'refTemplate'=>0
			];
		}
		$this->checkEmailMain();
	}
	
	protected function mailType($type){
		$mailType = [
			'COMMERCE_LOYAL_BIRTHDAY'=>[
				'LID'=>LANGUAGE_ID,
				'EVENT_NAME'=>'COMMERCE_LOYAL_BIRTHDAY',
				'NAME'=>Loc::getMessage("commerce.loyaltyprogram_EVENT_BIRTHDAY"),
				'DESCRIPTION'=>'
					#EMAIL_TO# - '.Loc::getMessage("commerce.loyaltyprogram_EVENT_BIRTHDAY_EMAIL_TO").'
					#BONUS# - '.Loc::getMessage("commerce.loyaltyprogram_EVENT_BIRTHDAY_BONUS").'
				',
				'SORT'=>500
			]
		];
		return $mailType[$type];
	}
	
	protected function mailTemplates($key){
		$sites=array_keys($this->globalSettings->getSites());
		$mailTemplates = [
			'userTemplate'=>[
				'EVENT_NAME'=>'COMMERCE_LOYAL_BIRTHDAY',
				'LID'=>$sites[0],
				'ACTIVE'=>'Y',
				'EMAIL_FROM'=>'#DEFAULT_EMAIL_FROM#',
				'EMAIL_TO'=>'#EMAIL_TO#',
				'SUBJECT'=>'#SITE_NAME#: '.Loc::getMessage("commerce.loyaltyprogram_EVENT_BIRTHDAY_USERBIRTHDAY"),
				'MESSAGE'=>$this->globalSettings->getEmailTemplate('birthday_user'),
				'BODY_TYPE'=>'html'
			],
			'refTemplate'=>[
				'EVENT_NAME'=>'COMMERCE_LOYAL_BIRTHDAY',
				'LID'=>$sites[0],
				'ACTIVE'=>'Y',
				'EMAIL_FROM'=>'#DEFAULT_EMAIL_FROM#',
				'EMAIL_TO'=>'#EMAIL_TO#',
				'SUBJECT'=>'#SITE_NAME#: '.Loc::getMessage("commerce.loyaltyprogram_EVENT_BIRTHDAY_REFUSERBIRTHDAY"),
				'MESSAGE'=>$this->globalSettings->getEmailTemplate('birthday_referal'),
				'BODY_TYPE'=>'html'
			]
		];
		return $mailTemplates[$key];
	}
	
	/**
	* if a variable userId is set - then the bonuses will be added regardless of the birthday
	*/
	public function setBonus($userId=''){
		if($this->profileSetting['settings']['bonuses']['bonus_size']>0 && $this->ruleCheck()==true){
			global $DB;
			$propBirthDays=(empty($this->profileSetting['settings']["propbirthday"]))?'PERSONAL_BIRTHDAY':$this->profileSetting['settings']["propbirthday"];
			
			if($propBirthDays=='PERSONAL_BIRTHDAY'){
				
				$filter=["PERSONAL_BIRTHDAY_DATE" => date("m-d", time()), '!PERSONAL_BIRTHDAY_DATE'=>false];
				if(!empty($this->profileSetting['site'])){
					$filter['LID']=implode('|', $this->profileSetting['site']);
				}
				
				 $rsUsers = \CUser::GetList(($by = "TIMESTAMP_X"), ($order = "DESC"), $filter);
			}else{
				$rsUsersProp=$DB->Query('select * from b_user_field where FIELD_NAME="'.$propBirthDays.'"');
				if($arUserProp = $rsUsersProp->Fetch()) {
					
					$filterSite=!empty($this->profileSetting['site'])?' and b_user.LID in("'.implode('","',$this->profileSetting['site']).'")':'';
					
					$query='select 
						b_user.id as ID,
						b_user.name as name,
						b_user.EMAIL as email,
						b_utm_user.VALUE as burth_day
						from b_user
						left join b_utm_user on
						(b_utm_user.VALUE_ID=b_user.id and b_utm_user.FIELD_ID='.$arUserProp['ID'].')
						where b_utm_user.VALUE like "'.date("m-d", time()).'%"'.$filterSite.';';
					$rsUsers=$DB->Query($query);
				}else{
					$filter=[$propBirthDays => date("m-d", time())];
					if(!empty($this->profileSetting['site'])){
						$filter['LID']=implode('|', $this->profileSetting['site']);
					}
					$rsUsers = \CUser::GetList(($by = "TIMESTAMP_X"), ($order = "DESC"), $filter);
				}
			}
			$tmpUsers=[];
			while ($arUser = $rsUsers->Fetch()) {
				$tmpUsers[$arUser['ID']]='Y';
			}
			if(count($tmpUsers)>0){
				$res = Application::getConnection()->query('select user_id, max(date_add) as date_add, CURDATE() from commerce_loyal_bonuses where profile_type="'.$this->profileSetting['type'].'" and date_add >= CURDATE() group by user_id;');
				while ($arRes = $res->fetch()){
					unset($tmpUsers[$arRes['user_id']]);
				}
			}

			if(count($tmpUsers)>0){
				$tmpUsers=array_keys($tmpUsers);
				$options=$this->globalSettings->getOptions();
				$startBonus=$currentTime=time(); $endBonus='null';
				if(!empty($this->profileSetting['settings']['bonuses']['bonus_delay']) && $this->profileSetting['settings']['bonuses']['bonus_delay']>0){
					$bonusDelay=$this->profileSetting['settings']['bonuses']['bonus_delay'];
					$bonusType=$this->profileSetting['settings']['bonuses']['bonus_delay_type'];
					if($bonusType=='month'){
						$startBonus=strtotime( '+'.$bonusDelay.' month' , $startBonus);
					}elseif($bonusType=='week'){
						$startBonus=strtotime( '+'.$bonusDelay.' week' , $startBonus);
					}else{
						$startBonus+=$bonusDelay*$this->timePart[$bonusType];
					}
				}
				if(!empty($this->profileSetting['settings']['bonuses']['bonus_live']) && $this->profileSetting['settings']['bonuses']['bonus_live']>0){
					$bonusLive=$this->profileSetting['settings']['bonuses']['bonus_live'];
					$bonusType=$this->profileSetting['settings']['bonuses']['bonus_live_type'];
					if($bonusType=='month'){
						$endBonus=strtotime( '+'.$bonusLive.' month' , $startBonus);
					}elseif($bonusType=='week'){
						$endBonus=strtotime( '+'.$bonusLive.' week' , $startBonus);
					}else{
						$endBonus=$startBonus+$bonusLive*$this->timePart[$bonusType];
					}
					$endBonus='FROM_UNIXTIME('.$endBonus.')';
				}
				
				$connection = \Bitrix\Main\Application::getConnection();
				$sqlHelper = $connection->getSqlHelper();
				foreach($tmpUsers as $nextUser){
					
					$mailTmplt=$this->getUserEmail($nextUser);
					$mailTmplt=($mailTmplt!=false)?serialize($mailTmplt):'';
					$connection->queryExecute("insert into ".$this->globalSettings->getTableBonusList()." (bonus_start, bonus, user_id, currency, profile_type, profile_id, status, date_add, date_remove, add_comment, email) values(
						".$this->profileSetting['settings']['bonuses']['bonus_size'].",
						".$this->profileSetting['settings']['bonuses']['bonus_size'].",
						".$nextUser.",
						'".$options['currency']."',
						'".$this->profileSetting['type']."',
						".$this->profileSetting['id'].",
						'inactive',
						FROM_UNIXTIME(".$startBonus."),
						".$endBonus.",
						'".$sqlHelper->ForSql(Loc::getMessage("commerce.loyaltyprogram_BONUS_USER_BIRTHDAY", ["#NUM#"=>$this->profileSetting['settings']['bonuses']['bonus_size']]))."',
						'".$mailTmplt."'
					);");
					
					if(!empty($this->profileSetting['settings']['rewards']) && count($this->profileSetting['settings']['rewards'])>0 && $this->ruleCheck()==true){
						$this->setReferalBonuses($nextUser);
					}
					
				}
				return true;
			}
		}
		return false;
	}
	
	private function getUserEmail($userId){
		global $DB;
		$emailSettings=$this->profileSetting['email_settings'];
		if(!empty($emailSettings['COMMERCE_LOYAL_BIRTHDAY']['userTemplate'])){
			$rsEM = \CEventMessage::GetByID($emailSettings['COMMERCE_LOYAL_BIRTHDAY']['userTemplate']);
			if($arEM = $rsEM->Fetch()){
				$sites=array_keys($this->globalSettings->getSites());
				$results=$DB->Query('select * from b_user where id='.$userId);
				$arUser = $results->Fetch();
				//$rsUser=\CUser::GetByID($userId);
				//$arUser = $rsUser->Fetch();
				if(!empty($arUser['EMAIL'])){
					return [
						"EVENT_NAME" => "COMMERCE_LOYAL_BIRTHDAY",
						"MESSAGE_ID" => $emailSettings['COMMERCE_LOYAL_BIRTHDAY']['userTemplate'],
						"LANGUAGE_ID" => LANGUAGE_ID,
						"LID" => $sites[0],
						"C_FIELDS" => [
							"EMAIL_TO" => $arUser['EMAIL'],
							"BONUS"=>$this->profileSetting['settings']['bonuses']['bonus_size']
						]
					];
				}
			}
		}
		return false;
	}
	
	//Check if conditions are suitable for calculating bonuses
	private function ruleCheck(){
		$check=false;
		//if(empty($this->profileSetting['site']) || $this->profileSetting['site']==SITE_ID){
			$check=true;
		//}
		return $check;
	}
		
	private function setReferalBonuses($userId){
		global $DB;
		$rewards=$this->getChainReferal($userId);
		$options=$this->globalSettings->getOptions();
		
		$startBonus=$currentTime=time(); $endBonus='null';
		if(!empty($this->profileSetting['settings']['bonuses']['bonus_delay']) && $this->profileSetting['settings']['bonuses']['bonus_delay']>0){
			$bonusDelay=$this->profileSetting['settings']['bonuses']['bonus_delay'];
			$bonusType=$this->profileSetting['settings']['bonuses']['bonus_delay_type'];
			if($bonusType=='month'){
				$startBonus=strtotime( '+'.$bonusDelay.' month' , $startBonus);
			}elseif($bonusType=='week'){
				$startBonus=strtotime( '+'.$bonusDelay.' week' , $startBonus);
			}else{
				$startBonus+=$bonusDelay*$this->timePart[$bonusType];
			}
		}
		if(!empty($this->profileSetting['settings']['bonuses']['bonus_live']) && $this->profileSetting['settings']['bonuses']['bonus_live']>0){
			
			$bonusLive=$this->profileSetting['settings']['bonuses']['bonus_live'];
			$bonusType=$this->profileSetting['settings']['bonuses']['bonus_live_type'];
			if($bonusType=='month'){
				$endBonus=strtotime( '+'.$bonusLive.' month' , $startBonus);
			}elseif($bonusType=='week'){
				$endBonus=strtotime( '+'.$bonusLive.' week' , $startBonus);
			}else{
				$endBonus=$startBonus+$bonusLive*$this->timePart[$bonusType];
			}
			$endBonus='FROM_UNIXTIME('.$endBonus.')';
		}
		
		foreach($rewards as $key=>$val){
			if($val>0){
				
				$mailTmplt=$this->getRefEmail($key, $val);
				$mailTmplt=($mailTmplt!=false)?serialize($mailTmplt):'';
				
				$idIns=$DB->Insert($this->globalSettings->getTableBonusList(), [
					'bonus_start'=>$val,
					'bonus'=>$val,
					'user_id'=>$key,
					'user_bonus'=>$userId,
					'currency'=>'"'.$options['currency'].'"',
					'profile_type'=>'"'.$this->profileSetting['type'].'"',
					'profile_id'=>$this->profileSetting['id'],
					'status'=>'"inactive"',
					'date_add'=>'FROM_UNIXTIME('.$startBonus.')',
					'date_remove'=>$endBonus,
					'add_comment'=>'"'.$DB->ForSql(Loc::getMessage("commerce.loyaltyprogram_BONUS_REF_BIRTHDAY", ["#NUM#"=>$val])).'"',
					'email'=>"'".$mailTmplt."'"
				], $err_mess.__LINE__);
				/*if($startBonus==$currentTime){
					Eventmanager::manageBonuses($idIns);
				}*/
			}
		}
	}
	
	private function getRefEmail($userId, $bonus){	
		$emailSettings=$this->profileSetting['email_settings'];
		if(!empty($emailSettings['COMMERCE_LOYAL_BIRTHDAY']['refTemplate'])){
			
			$rsUser=\CUser::GetByID($userId);
			$arUser = $rsUser->Fetch();
			if(!empty($arUser['EMAIL'])){
				$sites=array_keys($this->globalSettings->getSites());
				if(!isset($this->refTemplate)){
					$rsEM = \CEventMessage::GetByID($emailSettings['COMMERCE_LOYAL_BIRTHDAY']['refTemplate']);
					if($arEM = $rsEM->Fetch()){
						$this->refTemplate=$arEM;
					}else{
						$this->refTemplate=false;
					}
				}
				if($this->refTemplate!==false){
					return [
						"EVENT_NAME" => "COMMERCE_LOYAL_BIRTHDAY",
						"MESSAGE_ID" => $emailSettings['COMMERCE_LOYAL_BIRTHDAY']['refTemplate'],
						"LANGUAGE_ID" => LANGUAGE_ID,
						"LID" => $sites[0],
						"C_FIELDS" => [
							"EMAIL_TO" => $arUser['EMAIL'],
							"BONUS"=>$bonus
						]
					]; 
				}
			}
		}
		return false;
	}
	
	public function save($params){
		$options=$this->globalSettings->getOptions();
		$maxLevel=$options['ref_level'];
		$saveFields=[];
		$saveFields['sort']=(int) $params['sort'];
		$saveFields['active']='"'.(empty($params['active'])?'N':'Y').'"';
		$saveFields['site']='"'.$this->clearSites($params['site']).'"';
		$saveFields['name']='"'.(empty($params['profile_name'])?'noname':$params['profile_name']).'"';
		$saveFields['type']='"'.$this->profileSetting['type'].'"';
		$saveFields['date_setting']='NOW()';
		$tmpSettings=[
			'bonuses'=>[
				'bonus_size'=>empty($params['bonus_size'])?0:$params['bonus_size'],
				'bonus_delay'=>empty($params['bonus_delay'])?0:$params['bonus_delay'],
				'bonus_delay_type'=>empty($params['bonus_delay_type'])?'day':$params['bonus_delay_type'],
				'bonus_live'=>empty($params['bonus_live'])?'':$params['bonus_live'],
				'bonus_live_type'=>empty($params['bonus_live_type'])?'day':$params['bonus_live_type']
			],
			'propbirthday'=>$params['prop_birthday'],
			'rewards'=>[]
		];
		for($i=0; $i<$maxLevel; $i++){
			$tmpSettings['rewards'][]=empty($params['rewards'][$i])?0:$params['rewards'][$i];
		}
		$saveFields['settings']="'".serialize($tmpSettings)."'";
		global $DB;
		if($params['id']=='new'){
			$id = $DB->Insert($this->globalSettings->getTableProfilesList(), $saveFields, $err_mess.__LINE__);
		}else{
			$id = $DB->Update($this->globalSettings->getTableProfilesList(), $saveFields, "where id='".$params['id']."'", $err_mess.__LINE__);
		}
		$this->registerAgent('birthday', 86400);
		return $id;
	}

}

?>