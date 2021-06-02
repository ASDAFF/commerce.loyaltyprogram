<?
namespace Commerce\Loyaltyprogram;
use \Bitrix\Main\Application,
	Bitrix\Main\Localization\Loc;
/**
type register user
*/
class Bonusadd extends Profile implements Profileinterface{
	
	function __construct(){
		parent::__construct();
		$this->profileSetting['type']='Bonusadd';
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
		foreach(['bonusAdd', 'bonusDelay', 'bonusLive', 'orderStatuses', 'roundBonus'] as $nextRow){
			$this->drawRow($nextRow);
		}
	}
	
	protected function checkEmailList(){
		$this->profileSetting['email_settings']=empty($this->profileSetting['email_settings'])?[]:$this->profileSetting['email_settings'];
		if(empty($this->profileSetting['email_settings']['COMMERCE_LOYAL_BONUSADD'])){
			$this->profileSetting['email_settings']['COMMERCE_LOYAL_BONUSADD']=[
				'userTemplate'=>0,
				'refTemplate'=>0
			];
		}
		$this->checkEmailMain();
	}
	
	protected function mailType($type){
		$mailType = [
			'COMMERCE_LOYAL_BONUSADD'=>[
				'LID'=>LANGUAGE_ID,
				'EVENT_NAME'=>'COMMERCE_LOYAL_BONUSADD',
				'NAME'=>Loc::getMessage("commerce.loyaltyprogram_EVENT_BONUSADD"),
				'DESCRIPTION'=>'
					#EMAIL_TO# - '.Loc::getMessage("commerce.loyaltyprogram_EVENT_BONUSADD_EMAIL_TO").'
					#BONUS# - '.Loc::getMessage("commerce.loyaltyprogram_EVENT_BONUSADD_BONUS").'
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
				'EVENT_NAME'=>'COMMERCE_LOYAL_BONUSADD',
				'LID'=>$sites[0],
				'ACTIVE'=>'Y',
				'EMAIL_FROM'=>'#DEFAULT_EMAIL_FROM#',
				'EMAIL_TO'=>'#EMAIL_TO#',
				'SUBJECT'=>'#SITE_NAME#: '.Loc::getMessage("commerce.loyaltyprogram_EVENT_BONUSADD_USERBONUSADD"),
				'MESSAGE'=>$this->globalSettings->getEmailTemplate('user_bonus_add'),
				'BODY_TYPE'=>'html'
			],
			'refTemplate'=>[
				'EVENT_NAME'=>'COMMERCE_LOYAL_BONUSADD',
				'LID'=>$sites[0],
				'ACTIVE'=>'Y',
				'EMAIL_FROM'=>'#DEFAULT_EMAIL_FROM#',
				'EMAIL_TO'=>'#EMAIL_TO#',
				'SUBJECT'=>'#SITE_NAME#: '.Loc::getMessage("commerce.loyaltyprogram_EVENT_BONUSADD_REFBONUSADD"),
				'MESSAGE'=>$this->globalSettings->getEmailTemplate('referal_bonus_add'),
				'BODY_TYPE'=>'html'
			]
		];
		return $mailTemplates[$key];
	}

	public function setBonus($event=false){
		if($this->ruleCheck($event)==true){

			$order = $event->getParameter("ENTITY");
			$sum = $order->getPrice();
			global $USER;
			
			$basket=$order->getBasket();
			$price=$basket->getPrice();
			if($price>0){
				global $DB;
				$basketItems = $basket->getBasketItems();
				foreach ($basketItems as $basketItem) {
					$currency=$basketItem->getField('CURRENCY');
					break;
				}
				$bonusAdd=$this->profileSetting['settings']['bonuses']['bonus_add'];
				if($this->profileSetting['settings']['bonuses']['bonus_unit']=='percent'){
					$bonusAdd=$this->profileSetting['settings']['bonuses']['bonus_add']*$price/100;
				}
				if(!empty($this->profileSetting['settings']['round_bonus']) && $this->profileSetting['settings']['round_bonus']!='none'){
					$bonusAdd=$this->profileSetting['settings']['round_bonus']($bonusAdd);
				}
				
				$startBonus=$currentTime=time(); $endBonus='null';
				if(!empty($this->profileSetting['settings']['bonuses']['bonus_delay']) && $this->profileSetting['settings']['bonuses']['bonus_delay']>0){
					$startBonus+=$this->profileSetting['settings']['bonuses']['bonus_delay']*$this->timePart[$this->profileSetting['settings']['bonuses']['bonus_delay_type']];
				}
				if(!empty($this->profileSetting['settings']['bonuses']['bonus_live']) && $this->profileSetting['settings']['bonuses']['bonus_live']>0){
					$endBonus=$startBonus+$this->profileSetting['settings']['bonuses']['bonus_live']*$this->timePart[$this->profileSetting['settings']['bonuses']['bonus_live_type']];
					$endBonus='FROM_UNIXTIME('.$endBonus.')';
				}
				$userId=$order->getUserId();
				$mailTmplt=$this->getUserEmail($userId, $bonusAdd);
				$mailTmplt=($mailTmplt!=false)?serialize($mailTmplt):'';
				if($bonusAdd>0){
					$idIns=$DB->Insert($this->globalSettings->getTableBonusList(), [
						'bonus_start'=>$bonusAdd,
						'bonus'=>$bonusAdd,
						'user_id'=>$userId,
						'order_id'=>$order->getId(),
						'currency'=>'"'.$currency.'"',
						'profile_type'=>'"'.$this->profileSetting['type'].'"',
						'profile_id'=>$this->profileSetting['id'],
						'status'=>'"inactive"',
						'date_add'=>'FROM_UNIXTIME('.$startBonus.')',
						'date_remove'=>$endBonus, 
						'add_comment'=>'"'.$DB->ForSql(Loc::getMessage("commerce.loyaltyprogram_BONUS_USER_BONUS_ADD", ["#NUM#"=>$bonusAdd])).'"',
						'email'=>"'".$mailTmplt."'"
					], $err_mess.__LINE__);
					if($startBonus==$currentTime){
						Eventmanager::manageBonuses($idIns);
					}
				}
				if(!empty($this->profileSetting['settings']['rewards']) && count($this->profileSetting['settings']['rewards'])>0){
					$this->setReferalBonuses($userId, $price, $order->getId());
				}
				return true;
			}
		}
		return false;
	}
	
	private function getUserEmail($userId, $bonus){
		$emailSettings=$this->profileSetting['email_settings'];
		if(!empty($emailSettings['COMMERCE_LOYAL_BONUSADD']['userTemplate'])){
			$rsEM = \CEventMessage::GetByID($emailSettings['COMMERCE_LOYAL_BONUSADD']['userTemplate']);
			if($arEM = $rsEM->Fetch()){
				$sites=array_keys($this->globalSettings->getSites());
				$rsUser=\CUser::GetByID($userId);
				$arUser = $rsUser->Fetch();
				if(!empty($arUser['EMAIL'])){
					return [
						"EVENT_NAME" => "COMMERCE_LOYAL_BONUSADD",
						"MESSAGE_ID" => $emailSettings['COMMERCE_LOYAL_BONUSADD']['userTemplate'],
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
	
	//Check if conditions are suitable for calculating bonuses
	private function ruleCheck($event){
		$check=false;
		
		if(!$event){ return false;}
		$oldVals=$event->getParameter("VALUES");
		$order = $event->getParameter("ENTITY");
		$isNew = $event->getParameter("IS_NEW");
		$currentStatus=$order->getField('STATUS_ID');
		$orderSite = $order->getSiteId();
	
		if($isNew && (empty($this->profileSetting['settings']["order_statuses"]) || $this->profileSetting['settings']["order_statuses"]=='N')){
			$check=true;
		}elseif($currentStatus==$this->profileSetting['settings']["order_statuses"] && !empty($oldVals['STATUS_ID']) && $currentStatus!=$oldVals['STATUS_ID']){
			$check=true;
		}
		if(!empty($this->profileSetting['site']) && !in_array($orderSite, $this->profileSetting['site'])){
			$check=false;
		}
		return $check;
	}
		
	private function setReferalBonuses($userId, $price, $orderId=0){
		global $DB;
		$rewards=$this->getChainReferal($userId, $price);
		$options=$this->globalSettings->getOptions();
		
		$startBonus=$currentTime=time(); $endBonus='null';
		if(!empty($this->profileSetting['settings']['bonuses']['bonus_delay']) && $this->profileSetting['settings']['bonuses']['bonus_delay']>0){
			$startBonus+=$this->profileSetting['settings']['bonuses']['bonus_delay']*$this->timePart[$this->profileSetting['settings']['bonuses']['bonus_delay_type']];
		}
		if(!empty($this->profileSetting['settings']['bonuses']['bonus_live']) && $this->profileSetting['settings']['bonuses']['bonus_live']>0){
			$endBonus=$startBonus+$this->profileSetting['settings']['bonuses']['bonus_live']*$this->timePart[$this->profileSetting['settings']['bonuses']['bonus_live_type']];
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
					'order_id'=>$orderId,
					'currency'=>'"'.$options['currency'].'"',
					'profile_type'=>'"'.$this->profileSetting['type'].'"',
					'profile_id'=>$this->profileSetting['id'],
					'status'=>'"inactive"',
					'date_add'=>'FROM_UNIXTIME('.$startBonus.')',
					'date_remove'=>$endBonus,
					'add_comment'=>'"'.$DB->ForSql(Loc::getMessage("commerce.loyaltyprogram_BONUS_REF_BONUS_ADD", ["#NUM#"=>$val])).'"',
					'email'=>"'".$mailTmplt."'"
				], $err_mess.__LINE__);
				if($startBonus==$currentTime){
					Eventmanager::manageBonuses($idIns);
				}
			}
		}
	}
	
	private function getRefEmail($userId, $bonus){
		$emailSettings=$this->profileSetting['email_settings'];
		if(!empty($emailSettings['COMMERCE_LOYAL_BONUSADD']['refTemplate'])){
			
			$rsUser=\CUser::GetByID($userId);
			$arUser = $rsUser->Fetch();
			if(!empty($arUser['EMAIL'])){
				$sites=array_keys($this->globalSettings->getSites());
				if(!isset($this->refTemplate)){
					$rsEM = \CEventMessage::GetByID($emailSettings['COMMERCE_LOYAL_BONUSADD']['refTemplate']);
					if($arEM = $rsEM->Fetch()){
						$this->refTemplate=$arEM;
					}else{
						$this->refTemplate=false;
					}
				}
				if($this->refTemplate!==false){
					return [
						"EVENT_NAME" => "COMMERCE_LOYAL_BONUSADD",
						"MESSAGE_ID" => $emailSettings['COMMERCE_LOYAL_BONUSADD']['refTemplate'],
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
				'bonus_delay'=>empty($params['bonus_delay'])?0:$params['bonus_delay'],
				'bonus_delay_type'=>empty($params['bonus_delay_type'])?'day':$params['bonus_delay_type'],
				'bonus_live'=>empty($params['bonus_live'])?'':$params['bonus_live'],
				'bonus_live_type'=>empty($params['bonus_live_type'])?'day':$params['bonus_live_type']
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
		$this->registerEvent('sale', 'OnSaleOrderSaved', 'orderBonusAdd');
		return $id;
	}

}

?>