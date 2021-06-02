<?
namespace Commerce\Loyaltyprogram;
use \Bitrix\Main\Application,
	Bitrix\Main\Localization\Loc;
/**
type register user
*/
class Turnover extends Profile implements Profileinterface{
	
	function __construct(){
		parent::__construct();
		$this->profileSetting['type']='Turnover';
		$options=$this->globalSettings->getOptions();
		$maxLevel=$options['ref_level'];
		for($i=0; $i<$maxLevel; $i++){
			$this->profileSetting['settings']['rewards_unit'][$i]=(!empty($this->profileSetting['settings']['rewards_unit'][$i]))?$this->profileSetting['settings']['rewards_unit'][$i]:'bonus';
		}
	}
	
	public function getParametersMain(){
		foreach(['type', 'profileActive', 'profileName', 'profileSort', 'activeSite'] as $nextRow){
			$this->drawRow($nextRow);
		}
	}
	
	public function getParametersBonuses(){
		foreach(['nextCharge', 'bonusAdd', 'bonusLive', 'bonusSizeTurnover', 'bonusPeriod', 'orderStatuses',  'roundBonus'] as $nextRow){
			$this->drawRow($nextRow);
		}
	}
	
	protected function checkEmailList(){
		$this->profileSetting['email_settings']=empty($this->profileSetting['email_settings'])?[]:$this->profileSetting['email_settings'];
		if(empty($this->profileSetting['email_settings']['COMMERCE_LOYAL_BONUSTURNOVER'])){
			$this->profileSetting['email_settings']['COMMERCE_LOYAL_BONUSTURNOVER']=[
				'userTemplate'=>0,
				'refTemplate'=>0
			];
		}
		$this->checkEmailMain();
	}
	
	protected function mailType($type){
		$mailType = [
			'COMMERCE_LOYAL_BONUSTURNOVER'=>[
				'LID'=>LANGUAGE_ID,
				'EVENT_NAME'=>'COMMERCE_LOYAL_BONUSTURNOVER',
				'NAME'=>Loc::getMessage("commerce.loyaltyprogram_EVENT_BONUSTURNOVER"),
				'DESCRIPTION'=>'
					#EMAIL_TO# - '.Loc::getMessage("commerce.loyaltyprogram_EVENT_BONUSTURNOVER_EMAIL_TO").'
					#BONUS# - '.Loc::getMessage("commerce.loyaltyprogram_EVENT_BONUSTURNOVER_BONUS").'
					#TURNOVER# - '.Loc::getMessage("commerce.loyaltyprogram_EVENT_BONUSTURNOVER_TURNOVER").'
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
				'EVENT_NAME'=>'COMMERCE_LOYAL_BONUSTURNOVER',
				'LID'=>$sites[0],
				'ACTIVE'=>'Y',
				'EMAIL_FROM'=>'#DEFAULT_EMAIL_FROM#',
				'EMAIL_TO'=>'#EMAIL_TO#',
				'SUBJECT'=>'#SITE_NAME#: '.Loc::getMessage("commerce.loyaltyprogram_EVENT_BONUSTURNOVER_USERBONUSADD"),
				'MESSAGE'=>$this->globalSettings->getEmailTemplate('user_turnover'),
				'BODY_TYPE'=>'html'
			],
			'refTemplate'=>[
				'EVENT_NAME'=>'COMMERCE_LOYAL_BONUSTURNOVER',
				'LID'=>$sites[0],
				'ACTIVE'=>'Y',
				'EMAIL_FROM'=>'#DEFAULT_EMAIL_FROM#',
				'EMAIL_TO'=>'#EMAIL_TO#',
				'SUBJECT'=>'#SITE_NAME#: '.Loc::getMessage("commerce.loyaltyprogram_EVENT_BONUSTURNOVER_REFBONUSADD"),
				'MESSAGE'=>$this->globalSettings->getEmailTemplate('referal_turnover'),
				'BODY_TYPE'=>'html'
			]
		];
		return $mailTemplates[$key];
	}
	
	private function getInterval(){
		switch ($this->profileSetting['settings']['bonuses']['bonus_period']){
			case 'week':
				$cTimeEnd=strtotime('last monday', time());
				$cTimeStart=$cTimeEnd-604800;
				break;
			case 'month':
				$cTimeStart = mktime (0, 0, 0, date("n")-1, 1, date("Y"));
				$cTimeEnd = strtotime("+1 month", $cTimeStart);
				break;
			case 'quarter':
				$cMonth=date("n");
				if($cMonth>9){
					$cTimeStart = mktime (0, 0, 0, 7, 1, date("Y"));
				}elseif($cMonth>6){
					$cTimeStart = mktime (0, 0, 0, 4, 1, date("Y"));
				}elseif($cMonth>3){
					$cTimeStart = mktime (0, 0, 0, 1, 1, date("Y"));
				}else{
					$cTimeStart = mktime (0, 0, 0, 10, 1, date("Y")-1);
				}
				$cTimeEnd = strtotime("+3 month", $cTimeStart);
				break;
			case 'year':
				$cTimeStart = mktime (0, 0, 0, 1, 1, date("Y")-1);
				$cTimeEnd = strtotime("+1 year", $cTimeStart);
				break;
		}
		return ['startTime'=>$cTimeStart, 'endTime'=>$cTimeEnd];
	}

	public function setBonus($event=false){
		if($this->profileSetting['settings']['bonuses']['bonus_add']>0 && !empty($this->profileSetting['settings']['bonuses']['bonus_period']) && $this->ruleCheck()==true){
			$timePeriod=$this->getInterval();
			global $DB;
			//already turnover
			$alreadyTurnover=[];
			$res=$DB->Query('select * from commerce_loyal_bonuses where date_add=FROM_UNIXTIME('.$timePeriod['endTime'].') and profile_id='.$this->profileSetting['id'].';');
			while($row = $res->Fetch()){
				$alreadyTurnover[]=$row['user_id'];
			}
			
			$selectSite=(!empty($this->profileSetting['site']))?' and b_sale_order.LID in ("'.implode('","',$this->profileSetting['site']).'")':'';
			$selectStatus=(!empty($this->profileSetting['settings']['order_statuses']))?' and STATUS_ID="'.$this->profileSetting['settings']['order_statuses'].'"':'';
			
			$select='select
				sum(PRICE) as total_price,
				USER_ID as user_id
				from b_sale_order
				where 1=1'.$selectStatus.$selectSite.' and DATE_INSERT>=FROM_UNIXTIME('.$timePeriod['startTime'].') and DATE_INSERT < FROM_UNIXTIME('.$timePeriod['endTime'].')
				group by USER_ID having sum(PRICE)>'.$this->profileSetting['settings']['bonuses']['bonus_size_turnover'].';';
			$res=$DB->Query($select);
			$options=$this->globalSettings->getOptions();
			while($row = $res->Fetch()){
				if(!in_array($row['user_id'], $alreadyTurnover)){
					$bonusAdd=$this->profileSetting['settings']['bonuses']['bonus_add'];
					if($this->profileSetting['settings']['bonuses']['bonus_unit']=='percent'){
						$bonusAdd=$this->profileSetting['settings']['bonuses']['bonus_add']*$row['total_price']/100;
					}
					if(!empty($this->profileSetting['settings']['round_bonus']) && $this->profileSetting['settings']['round_bonus']!='none'){
						$bonusAdd=$this->profileSetting['settings']['round_bonus']($bonusAdd);
					}
					$startBonus=$timePeriod['endTime']; $endBonus='null';
					if(!empty($this->profileSetting['settings']['bonuses']['bonus_live']) && $this->profileSetting['settings']['bonuses']['bonus_live']>0){
						$endBonus=$startBonus+$this->profileSetting['settings']['bonuses']['bonus_live']*$this->timePart[$this->profileSetting['settings']['bonuses']['bonus_live_type']];
						$endBonus='FROM_UNIXTIME('.$endBonus.')';
					}
					$mailTmplt=$this->getUserEmail($row['user_id'], \CurrencyFormat($bonusAdd, $options['currency']), \CurrencyFormat($row['total_price'], $options['currency']));
					$mailTmplt=($mailTmplt!=false)?serialize($mailTmplt):'';
					
					$idIns=$DB->Insert($this->globalSettings->getTableBonusList(), [
						'bonus_start'=>$bonusAdd,
						'bonus'=>$bonusAdd,
						'user_id'=>$row['user_id'],
						'currency'=>'"'.$options['currency'].'"',
						'profile_type'=>'"'.$this->profileSetting['type'].'"',
						'profile_id'=>$this->profileSetting['id'],
						'status'=>'"inactive"',
						'date_add'=>'FROM_UNIXTIME('.$startBonus.')',
						'date_remove'=>$endBonus, 
						'add_comment'=>'"'.$DB->ForSql(Loc::getMessage("commerce.loyaltyprogram_BONUS_USER_BONUSTURNOVER_ADD", ["#NUM#"=>$bonusAdd])).'"',
						'email'=>"'".$mailTmplt."'"
					], $err_mess.__LINE__);
					Eventmanager::manageBonuses($idIns);
					if(!empty($this->profileSetting['settings']['rewards']) && count($this->profileSetting['settings']['rewards'])>0){
						$this->setReferalBonuses($row['user_id'], $bonusAdd, $row['total_price']);
					}
				}
			}
		}
		return false;
	}
	
	private function getUserEmail($userId, $bonus, $turnover){
		$emailSettings=$this->profileSetting['email_settings'];
		if(!empty($emailSettings['COMMERCE_LOYAL_BONUSTURNOVER']['userTemplate'])){
			$rsEM = \CEventMessage::GetByID($emailSettings['COMMERCE_LOYAL_BONUSTURNOVER']['userTemplate']);
			if($arEM = $rsEM->Fetch()){
				$sites=array_keys($this->globalSettings->getSites());
				$rsUser=\CUser::GetByID($userId);
				$arUser = $rsUser->Fetch();
				if(!empty($arUser['EMAIL'])){
					return [
						"EVENT_NAME" => "COMMERCE_LOYAL_BONUSTURNOVER",
						"MESSAGE_ID" => $emailSettings['COMMERCE_LOYAL_BONUSTURNOVER']['userTemplate'],
						"LANGUAGE_ID" => LANGUAGE_ID,
						"LID" => $sites[0],
						"C_FIELDS" => [
							"EMAIL_TO" => $arUser['EMAIL'],
							"BONUS"=>$bonus,
							"TURNOVER"=>$turnover
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
		
	private function setReferalBonuses($userId, $price, $turnover){
		global $DB;
		$timePeriod=$this->getInterval();
		$res=$DB->Query('select * from commerce_loyal_bonuses where date_add=FROM_UNIXTIME('.$timePeriod['endTime'].') and profile_id='.$this->profileSetting['id'].' and user_bonus='.$userId.';');
		if(!$row = $res->Fetch()){
			$rewards=$this->getChainReferal($userId, $price);
			$options=$this->globalSettings->getOptions();
			$startBonus=$timePeriod['endTime']; $endBonus='null';
			if(!empty($this->profileSetting['settings']['bonuses']['bonus_live']) && $this->profileSetting['settings']['bonuses']['bonus_live']>0){
				$endBonus=$startBonus+$this->profileSetting['settings']['bonuses']['bonus_live']*$this->timePart[$this->profileSetting['settings']['bonuses']['bonus_live_type']];
				$endBonus='FROM_UNIXTIME('.$endBonus.')';
			}
			foreach($rewards as $key=>$val){
				if($val>0){
					
					$mailTmplt=$this->getRefEmail($key, \CurrencyFormat($val, $options['currency']), \CurrencyFormat($turnover, $options['currency']));
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
						'add_comment'=>'"'.$DB->ForSql(Loc::getMessage("commerce.loyaltyprogram_BONUS_USER_BONUSTURNOVERREF_ADD", ["#NUM#"=>$val])).'"',
						'email'=>"'".$mailTmplt."'"
					], $err_mess.__LINE__);
					Eventmanager::manageBonuses($idIns);
				}
			}
		}
	}
	
	private function getRefEmail($userId, $bonus, $turnover){
		$emailSettings=$this->profileSetting['email_settings'];
		if(!empty($emailSettings['COMMERCE_LOYAL_BONUSTURNOVER']['refTemplate'])){
			
			$rsUser=\CUser::GetByID($userId);
			$arUser = $rsUser->Fetch();
			if(!empty($arUser['EMAIL'])){
				$sites=array_keys($this->globalSettings->getSites());
				if(!isset($this->refTemplate)){
					$rsEM = \CEventMessage::GetByID($emailSettings['COMMERCE_LOYAL_BONUSTURNOVER']['refTemplate']);
					if($arEM = $rsEM->Fetch()){
						$this->refTemplate=$arEM;
					}else{
						$this->refTemplate=false;
					}
				}
				if($this->refTemplate!==false){
					return [
						"EVENT_NAME" => "COMMERCE_LOYAL_BONUSTURNOVER",
						"MESSAGE_ID" => $emailSettings['COMMERCE_LOYAL_BONUSTURNOVER']['refTemplate'],
						"LANGUAGE_ID" => LANGUAGE_ID,
						"LID" => $sites[0],
						"C_FIELDS" => [
							"EMAIL_TO" => $arUser['EMAIL'],
							"BONUS"=>$bonus,
							"TURNOVER"=>$turnover
						]
					]; 
				}
			}
		}
		return false;
	}
	
	public function save($params){
		global $DB;
		$saveFields=[];
		$saveFields['sort']=(int) $params['sort'];
		$saveFields['active']='"'.(empty($params['active'])?'N':'Y').'"';
		$saveFields['name']='"'.(empty($params['profile_name'])?'noname':$DB->ForSql($params['profile_name'])).'"';
		$saveFields['type']='"'.$this->profileSetting['type'].'"';
		$saveFields['site']='"'.$this->clearSites($params['site']).'"';
		$saveFields['date_setting']='NOW()';
		$tmpSettings=[
			'bonuses'=>[
				'bonus_add'=>empty($params['bonus_add'])?0:$params['bonus_add'],
				'bonus_unit'=>(!empty($params['bonus_unit']) && $params['bonus_unit']=='percent')?'percent':'bonus',
				'bonus_live'=>empty($params['bonus_live'])?'':$params['bonus_live'],
				'bonus_live_type'=>empty($params['bonus_live_type'])?'day':$params['bonus_live_type'],
				'bonus_period'=>empty($params['bonus_period'])?'month':$params['bonus_period'],
				'bonus_size_turnover'=>empty($params['bonus_size_turnover'])?0:$params['bonus_size_turnover']
			],
			'round_bonus'=> $params['round_bonus'],
			'order_statuses'=>$params['order_statuses']
		];
		$options=$this->globalSettings->getOptions();
		$maxLevel=$options['ref_level'];
		for($i=0; $i<$maxLevel; $i++){
			$tmpSettings['rewards'][]=empty($params['rewards'][$i])?0:$params['rewards'][$i];
			$tmpSettings['rewards_unit'][]=empty($params['rewards_unit'][$i])?0:$params['rewards_unit'][$i];
		}
		$saveFields['settings']="'".serialize($tmpSettings)."'";
		if($params['id']=='new'){
			$id = $DB->Insert($this->globalSettings->getTableProfilesList(), $saveFields, $err_mess.__LINE__);
		}else{
			$id = $DB->Update($this->globalSettings->getTableProfilesList(), $saveFields, "where id='".$params['id']."'", $err_mess.__LINE__);
		}
		$this->registerAgent('turnover', 86400, 10);
		return $id;
	}

}

?>