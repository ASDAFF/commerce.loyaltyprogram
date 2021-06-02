<?
namespace Commerce\Loyaltyprogram;
use \Bitrix\Main\Application,
	Bitrix\Main\Localization\Loc;
/**
type register user
*/
class Register extends Profile implements Profileinterface{
	
	function __construct(){
		parent::__construct();
		$this->profileSetting['type']='Register';
	}
	
	public function getParametersMain(){
		foreach(['type', 'profileActive', 'profileName', 'profileSort', 'activeSite'] as $nextRow){
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
		if(empty($this->profileSetting['email_settings']['COMMERCE_LOYAL_REGISTER'])){
			$this->profileSetting['email_settings']['COMMERCE_LOYAL_REGISTER']=[
				'userTemplate'=>0,
				'refTemplate'=>0
			];
		}
		$this->checkEmailMain();
	}
	
	protected function mailType($type){
		$mailType = [
			'COMMERCE_LOYAL_REGISTER'=>[
				'LID'=>LANGUAGE_ID,
				'EVENT_NAME'=>'COMMERCE_LOYAL_REGISTER',
				'NAME'=>Loc::getMessage("commerce.loyaltyprogram_EVENT_REGISTER"),
				'DESCRIPTION'=>'
					#EMAIL_TO# - '.Loc::getMessage("commerce.loyaltyprogram_EVENT_REGISTER_EMAIL_TO").'
					#BONUS# - '.Loc::getMessage("commerce.loyaltyprogram_EVENT_REGISTER_BONUS").'
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
				'EVENT_NAME'=>'COMMERCE_LOYAL_REGISTER',
				'LID'=>$sites[0],
				'ACTIVE'=>'Y',
				'EMAIL_FROM'=>'#DEFAULT_EMAIL_FROM#',
				'EMAIL_TO'=>'#EMAIL_TO#',
				'SUBJECT'=>'#SITE_NAME#: '.Loc::getMessage("commerce.loyaltyprogram_EVENT_REGISTER_USERREGISTER"),
				'MESSAGE'=>$this->globalSettings->getEmailTemplate('register_userregister'),
				'BODY_TYPE'=>'html'
			],
			'refTemplate'=>[
				'EVENT_NAME'=>'COMMERCE_LOYAL_REGISTER',
				'LID'=>$sites[0],
				'ACTIVE'=>'Y',
				'EMAIL_FROM'=>'#DEFAULT_EMAIL_FROM#',
				'EMAIL_TO'=>'#EMAIL_TO#',
				'SUBJECT'=>'#SITE_NAME#: '.Loc::getMessage("commerce.loyaltyprogram_EVENT_REGISTER_REFREGISTER"),
				'MESSAGE'=>$this->globalSettings->getEmailTemplate('register_refregister'),
				'BODY_TYPE'=>'html'
			]
		];
		return $mailTemplates[$key];
	}
	
	public function setBonus($userId){
		if($this->profileSetting['settings']['bonuses']['bonus_size']>0 && $this->ruleCheck()==true){
			$options=$this->globalSettings->getOptions();
			global $DB;
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
			$mailTmplt=$this->getUserEmail($userId);
			$mailTmplt=($mailTmplt!=false)?serialize($mailTmplt):'';

			$idIns=$DB->Insert($this->globalSettings->getTableBonusList(), [
				'bonus_start'=>$this->profileSetting['settings']['bonuses']['bonus_size'],
				'bonus'=>$this->profileSetting['settings']['bonuses']['bonus_size'],
				'user_id'=>$userId,
				'currency'=>'"'.$options['currency'].'"',
				'profile_type'=>'"'.$this->profileSetting['type'].'"',
				'profile_id'=>$this->profileSetting['id'],
				'status'=>'"inactive"',
				'date_add'=>'FROM_UNIXTIME('.$startBonus.')',
				'date_remove'=>$endBonus, 
				'add_comment'=>'"'.$DB->ForSql(Loc::getMessage("commerce.loyaltyprogram_BONUS_USER_REGISTRATION", ["#NUM#"=>$this->profileSetting['settings']['bonuses']['bonus_size']])).'"',
				'email'=>"'".$mailTmplt."'"
			], $err_mess.__LINE__);
			if($startBonus==$currentTime){
				Eventmanager::manageBonuses($idIns);
			}
			if(!empty($this->profileSetting['settings']['rewards']) && count($this->profileSetting['settings']['rewards'])>0){
				$this->setReferalBonuses($userId);
			}
			return true;
		}
		return false;
	}
	
	//Check if conditions are suitable for calculating bonuses
	private function ruleCheck(){
		$check=false;
		if(empty($this->profileSetting['site']) || in_array(SITE_ID, $this->profileSetting['site'])){
			$check=true;
		}
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
					'add_comment'=>'"'.$DB->ForSql(Loc::getMessage("commerce.loyaltyprogram_BONUS_REF_REGISTRATION", ["#NUM#"=>$val])).'"',
					'email'=>"'".$mailTmplt."'"
				], $err_mess.__LINE__);
				if($startBonus==$currentTime){
					Eventmanager::manageBonuses($idIns);
				}
			}
		}
	}
	
	private function getUserEmail($userId){
		if(!empty($userId)){
			$emailSettings=$this->profileSetting['email_settings'];
			if(!empty($emailSettings['COMMERCE_LOYAL_REGISTER']['userTemplate'])){
				$rsEM = \CEventMessage::GetByID($emailSettings['COMMERCE_LOYAL_REGISTER']['userTemplate']);
				if($arEM = $rsEM->Fetch()){
					$sites=array_keys($this->globalSettings->getSites());
					$rsUser=\CUser::GetByID($userId);
					$arUser = $rsUser->Fetch();
					if(!empty($arUser['EMAIL'])){
						return [
							"EVENT_NAME" => "COMMERCE_LOYAL_REGISTER",
							"MESSAGE_ID" => $emailSettings['COMMERCE_LOYAL_REGISTER']['userTemplate'],
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
		}
		return false;
	}
	
	private function getRefEmail($userId, $bonus){
		if(!empty($userId)){
			$emailSettings=$this->profileSetting['email_settings'];
			if(!empty($emailSettings['COMMERCE_LOYAL_REGISTER']['refTemplate'])){
				
				$rsUser=\CUser::GetByID($userId);
				$arUser = $rsUser->Fetch();
				if(!empty($arUser['EMAIL'])){
					$sites=array_keys($this->globalSettings->getSites());
					if(!isset($this->refTemplate)){
						$rsEM = \CEventMessage::GetByID($emailSettings['COMMERCE_LOYAL_REGISTER']['refTemplate']);
						if($arEM = $rsEM->Fetch()){
							$this->refTemplate=$arEM;
						}else{
							$this->refTemplate=false;
						}
					}
					if($this->refTemplate!==false){
						return [
							"EVENT_NAME" => "COMMERCE_LOYAL_REGISTER",
							"MESSAGE_ID" => $emailSettings['COMMERCE_LOYAL_REGISTER']['refTemplate'],
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
		$this->registerEvent('main', 'OnAfterUserAdd', 'registerUser');
		return $id;
	}

}

?>