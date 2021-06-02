<?
namespace Commerce\Loyaltyprogram;
use \Bitrix\Main\Application,
	Bitrix\Main\Localization\Loc;
/**
type register user
*/
class Bonuspay extends Profile implements Profileinterface{
	
	function __construct(){
		parent::__construct();
		$this->profileSetting['type']='Bonuspay';
		$this->userEmail='';
		$this->currentBudget=0;
		$options=$this->globalSettings->getOptions();
		$this->currency=$options['currency'];
		$this->profileSetting['settings']['withdraw']=(!empty($this->profileSetting['settings']['withdraw']))?$this->profileSetting['settings']['withdraw']:0;
		$this->profileSetting['settings']['withdraw_max']=(!empty($this->profileSetting['settings']['withdraw_max']))?$this->profileSetting['settings']['withdraw_max']:0;
	}
	
	public function getParametersMain(){
		foreach(['type', 'profileActive', 'profileName', 'profileSort', 'activeSite'] as $nextRow){
			$this->drawRow($nextRow);
		}
	}
	
	public function getParametersBonuses(){
		foreach(['withdraw', 'withdrawMax', 'roundBonus'] as $nextRow){
			$this->drawRow($nextRow);
		}
	}
	
	protected function checkEmailList(){
		$this->profileSetting['email_settings']=empty($this->profileSetting['email_settings'])?[]:$this->profileSetting['email_settings'];
		if(empty($this->profileSetting['email_settings']['COMMERCE_LOYAL_BONUSPAY'])){
			$this->profileSetting['email_settings']['COMMERCE_LOYAL_BONUSPAY']=[
				'userTemplate'=>0
			];
		}
		$this->checkEmailMain();
	}
	
	protected function mailType($type){
		$mailType = [
			'COMMERCE_LOYAL_BONUSPAY'=>[
				'LID'=>LANGUAGE_ID,
				'EVENT_NAME'=>'COMMERCE_LOYAL_BONUSPAY',
				'NAME'=>Loc::getMessage("commerce.loyaltyprogram_EVENT_BONUSPAY"),
				'DESCRIPTION'=>'
					#EMAIL_TO# - '.Loc::getMessage("commerce.loyaltyprogram_EVENT_BONUSPAY_EMAIL_TO").'
					#BONUS# - '.Loc::getMessage("commerce.loyaltyprogram_EVENT_BONUSPAY_BONUS").'
					#BONUS_LEFT# - '.Loc::getMessage("commerce.loyaltyprogram_EVENT_BONUSPAY_BONUS_LEFT").'
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
				'EVENT_NAME'=>'COMMERCE_LOYAL_BONUSPAY',
				'LID'=>$sites[0],
				'ACTIVE'=>'Y',
				'EMAIL_FROM'=>'#DEFAULT_EMAIL_FROM#',
				'EMAIL_TO'=>'#EMAIL_TO#',
				'SUBJECT'=>'#SITE_NAME#: '.Loc::getMessage("commerce.loyaltyprogram_EVENT_BONUSPAY_USERBONUSPAY"),
				'MESSAGE'=>$this->globalSettings->getEmailTemplate('bonuspay'),
				'BODY_TYPE'=>'html'
			]
		];
		return $mailTemplates[$key];
	}
	
	public function setBonus($userId=''){
		if($this->profileSetting['settings']['withdraw']>0 && $this->ruleCheck()==true){
			/*$basket = \Bitrix\Sale\Basket::loadItemsForFUser(
				   \Bitrix\Sale\Fuser::getId(),
				   \Bitrix\Main\Context::getCurrent()->getSite()
				);
			$price=$basket->getPrice();
			if($price>0){
				$basketItems = $basket->getBasketItems();
				foreach ($basketItems as $basketItem) {
					$currency=$basketItem->getField('CURRENCY');
					break;
				}
	
			}*/
		}
		return false;
	}
	
	public function sendEvent($bonus){
		if(!empty($this->profileSetting['email_settings']['COMMERCE_LOYAL_BONUSPAY']['userTemplate'])){
			$etemplate=$this->mailTemplates('userTemplate');
			
			\Bitrix\Main\Mail\Event::send([
				"EVENT_NAME" => $etemplate['EVENT_NAME'],
				"LID" => $etemplate['LID'],
				"C_FIELDS" => [
					'EMAIL_TO'=>$this->userEmail,
					'BONUS'=>\CurrencyFormat($bonus, $this->currency),
					'BONUS_LEFT'=>\CurrencyFormat(($this->currentBudget-$bonus), $this->currency),
				]
			]);
		}
	}
	
	public function getMaxBonus($basket=false){
		if($this->profileSetting['settings']['withdraw']>0 && $this->ruleCheck()==true){
			global $USER;
			if($USER->IsAuthorized()){
				if($basket===false){
					$basket = \Bitrix\Sale\Basket::loadItemsForFUser(
					   \Bitrix\Sale\Fuser::getId(),
					   \Bitrix\Main\Context::getCurrent()->getSite()
					);
				}
				$price=$basket->getPrice();
				if($price>0){
					$basketItems = $basket->getBasketItems();
					foreach ($basketItems as $basketItem) {
						$currency=$basketItem->getField('CURRENCY');
						break;
					}
					$account=\CSaleUserAccount::GetByUserID($USER->GetID(), $currency);
					
					$this->userEmail=$USER->GetEmail();
					$this->currentBudget=$account['CURRENT_BUDGET'];
					$this->currency=$currency;
					
					$bonusPay=($this->profileSetting['settings']['withdraw_unit']=='percent')?$this->profileSetting['settings']['withdraw']*$price/100:$this->profileSetting['settings']['withdraw'];
					if(!empty($this->profileSetting['settings']['withdraw_max']) && $bonusPay>$this->profileSetting['settings']['withdraw_max']){
						$bonusPay=$this->profileSetting['settings']['withdraw_max'];
					}
					$bonusPay=min((float) $bonusPay, (float) $account['CURRENT_BUDGET']);
					if(!empty($this->profileSetting['settings']['round_bonus']) && $this->profileSetting['settings']['round_bonus']!='none'){
						$bonusPay=$this->profileSetting['settings']['round_bonus']($bonusPay);
					}
					return $bonusPay;
				}
			}
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
		
		$startBonus=$currentTime=time(); $endBonus='';
		if(!empty($this->profileSetting['settings']['bonuses']['bonus_delay']) && $this->profileSetting['settings']['bonuses']['bonus_delay']>0){
			$startBonus+=$this->profileSetting['settings']['bonuses']['bonus_delay']*$this->timePart[$this->profileSetting['settings']['bonuses']['bonus_delay_type']];
		}
		if(!empty($this->profileSetting['settings']['bonuses']['bonus_live']) && $this->profileSetting['settings']['bonuses']['bonus_live']>0){
			$endBonus=$startBonus+$this->profileSetting['settings']['bonuses']['bonus_live']*$this->timePart[$this->profileSetting['settings']['bonuses']['bonus_live_type']];
			$endBonus='FROM_UNIXTIME('.$endBonus.')';
		}
		
		foreach($rewards as $key=>$val){
			if($val>0){
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
					'add_comment'=>'"'.$DB->ForSql(Loc::getMessage("commerce.loyaltyprogram_BONUS_REF_BIRTHDAY", ["#NUM#"=>$val])).'"'
				], $err_mess.__LINE__);
				if($startBonus==$currentTime){
					Eventmanager::manageBonuses($idIns);
				}
			}
		}
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
			'withdraw'=>(int) $params['withdraw'],
			'withdraw_unit'=>(!empty($params['withdraw_unit']) && $params['withdraw_unit']=='bonus')?'bonus':'percent',
			'withdraw_max'=>(int) $params['withdraw_max'],
			'round_bonus'=> $params['round_bonus']
		];
		$saveFields['settings']="'".serialize($tmpSettings)."'";
		if($params['id']=='new'){
			$id = $DB->Insert($this->globalSettings->getTableProfilesList(), $saveFields, $err_mess.__LINE__);
		}else{
			$id = $DB->Update($this->globalSettings->getTableProfilesList(), $saveFields, "where id='".$params['id']."'", $err_mess.__LINE__);
		}
		$this->checkOrderProps();
		$this->registerEvent('sale', 'OnSaleOrderSaved', 'AfterOrderSave');
		$this->registerEvent('sale', 'OnSaleOrderCanceled', 'AfterOrderCancel');
		return $id;
	}

}

?>