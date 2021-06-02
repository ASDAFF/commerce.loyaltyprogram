<?
namespace Commerce\Loyaltyprogram\Profiles;
use \Bitrix\Main\Application,
	Bitrix\Main\Localization\Loc,
	\Commerce\Loyaltyprogram;
/**
* type register user
*/
class Turnover extends Profile implements Profileinterface{
	
	function __construct(){
		parent::__construct();
		$this->profileSetting['type']='Turnover';
		$this->usersPeriodFrom=0;
		$this->usersPeriodTo=0;
		$this->usersTurnoverFrom=0;
		$this->usersTurnoverTo=0;
	}
	
	public function getParametersMain(){
		foreach(['type', 'profileActive', 'profileName', 'profileSort'] as $nextRow){
			$this->drawRow($nextRow);
		}
	}
	
	protected function checkSMSList(){
		$this->profileSetting['sms_settings']=empty($this->profileSetting['sms_settings'])?[]:$this->profileSetting['sms_settings'];
		if(empty($this->profileSetting['sms_settings']['COMMERCE_LOYAL_TURNOVER_SMS'])){
			$this->profileSetting['sms_settings']['COMMERCE_LOYAL_TURNOVER_SMS']=[
				'userTemplate'=>0,
				'refTemplate'=>0
			];
		}
		$this->checkSMSMain();
	}
	
	protected function SMSType($type){
		$SMSType = [
			'COMMERCE_LOYAL_TURNOVER_SMS'=>[
				'LID'=>LANGUAGE_ID,
				'EVENT_NAME'=>'COMMERCE_LOYAL_TURNOVER_SMS',
				'EVENT_TYPE'  => 'sms',
				'NAME'=>Loc::getMessage("commerce.loyaltyprogram_SMS_TURNOVER"),
				'DESCRIPTION'=>Loc::getMessage("commerce.loyaltyprogram_SMS_TURNOVER_DESC")
			]
		];
		return $SMSType[$type];
	}
	
	protected function SMSTemplates($key){
		$sites=array_keys($this->globalSettings->getSites());
		$smsTemplates = [
			'userTemplate'=>[
				'EVENT_NAME'=>'COMMERCE_LOYAL_TURNOVER_SMS',
				"ACTIVE" => false,
				"SENDER" => "#DEFAULT_SENDER#",
				"RECEIVER" => "#USER_PHONE#",
				"MESSAGE" => GetMessage("commerce.loyaltyprogram_SMS_TURNOVER_USERBONUSADD")
			],
			'refTemplate'=>[
				'EVENT_NAME'=>'COMMERCE_LOYAL_TURNOVER_SMS',
				"ACTIVE" => false,
				"SENDER" => "#DEFAULT_SENDER#",
				"RECEIVER" => "#USER_PHONE#",
				"MESSAGE" => GetMessage("commerce.loyaltyprogram_SMS_TURNOVER_REFBONUSADD")
			]
		];
		return $smsTemplates[$key];
	}
	
	private function getRefSMS($userId, $bonus, $turnover){
		$SMSSettings=$this->profileSetting['sms_settings'];
		if(!empty($SMSSettings['COMMERCE_LOYAL_TURNOVER_SMS']['refTemplate'])){
			$userData=Loyaltyprogram\Tools::getUserData($userId);
			if(!empty($userData['PERSONAL_PHONE'])){
				$tmpPhone=$userData['PERSONAL_PHONE'];
			}elseif(!empty($userData['PERSONAL_MOBILE'])){
				$tmpPhone=$userData['PERSONAL_PHONE'];
			}
			if(!empty($tmpPhone)){
				$tmpPhone=\Bitrix\Main\UserPhoneAuthTable::normalizePhoneNumber($tmpPhone);
				return [
					"EVENT_NAME" => "COMMERCE_LOYAL_TURNOVER_SMS",
					"MESSAGE_ID" => $SMSSettings['COMMERCE_LOYAL_TURNOVER_SMS']['refTemplate'],
					"LID" => $userData['LID'],
					"C_FIELDS" => [
						"USER_PHONE" => $tmpPhone,
						"BONUS"=>strval($bonus),
						"TURNOVER"=>strval($turnover)
					]
				];
			}
		}
		return false;
	}
	
	private function getUserSMS($userId, $bonus, $turnover){
		$SMSSettings=$this->profileSetting['sms_settings'];
		if(!empty($SMSSettings['COMMERCE_LOYAL_TURNOVER_SMS']['userTemplate'])){
			$userData=Loyaltyprogram\Tools::getUserData($userId);
			if(!empty($userData['PERSONAL_PHONE'])){
				$tmpPhone=$userData['PERSONAL_PHONE'];
			}elseif(!empty($userData['PERSONAL_MOBILE'])){
				$tmpPhone=$userData['PERSONAL_PHONE'];
			}
			if(!empty($tmpPhone)){
				$tmpPhone=\Bitrix\Main\UserPhoneAuthTable::normalizePhoneNumber($tmpPhone);
				return [
					"EVENT_NAME" => "COMMERCE_LOYAL_TURNOVER_SMS",
					"MESSAGE_ID" => $SMSSettings['COMMERCE_LOYAL_TURNOVER_SMS']['userTemplate'],
					"LID" => $userData['LID'],
					"C_FIELDS" => [
						"USER_PHONE" => $tmpPhone,
						"BONUS"=>strval($bonus),
						"TURNOVER"=>strval($turnover)
					]
				];
			}
		}
		return false;
	}

	protected function checkEmailList(){
		$this->profileSetting['email_settings']=empty($this->profileSetting['email_settings'])?[]:$this->profileSetting['email_settings'];
		if(empty($this->profileSetting['email_settings']['COMMERCE_LOYAL_TURNOVER'])){
			$this->profileSetting['email_settings']['COMMERCE_LOYAL_TURNOVER']=[
				'userTemplate'=>0,
				'refTemplate'=>0
			];
		}
		$this->checkEmailMain();
	}
	
	protected function mailType($type){
		$mailType = [
			'COMMERCE_LOYAL_TURNOVER'=>[
				'LID'=>LANGUAGE_ID,
				'EVENT_NAME'=>'COMMERCE_LOYAL_TURNOVER',
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
				'EVENT_NAME'=>'COMMERCE_LOYAL_TURNOVER',
				'LID'=>$sites[0],
				'ACTIVE'=>'Y',
				'EMAIL_FROM'=>'#DEFAULT_EMAIL_FROM#',
				'EMAIL_TO'=>'#EMAIL_TO#',
				'SUBJECT'=>'#SITE_NAME#: '.Loc::getMessage("commerce.loyaltyprogram_EVENT_BONUSTURNOVER_USERBONUSADD"),
				'MESSAGE'=>$this->globalSettings->getEmailTemplate('turnover_user'),
				'BODY_TYPE'=>'html'
			],
			'refTemplate'=>[
				'EVENT_NAME'=>'COMMERCE_LOYAL_TURNOVER',
				'LID'=>$sites[0],
				'ACTIVE'=>'Y',
				'EMAIL_FROM'=>'#DEFAULT_EMAIL_FROM#',
				'EMAIL_TO'=>'#EMAIL_TO#',
				'SUBJECT'=>'#SITE_NAME#: '.Loc::getMessage("commerce.loyaltyprogram_EVENT_BONUSTURNOVER_REFBONUSADD"),
				'MESSAGE'=>$this->globalSettings->getEmailTemplate('turnover_ref'),
				'BODY_TYPE'=>'html'
			]
		];
		return $mailTemplates[$key];
	}
	
	private function getInterval($period){
		switch ($period){
			case 'week':
				if(date("D",time())=='Mon'){
					$cTimeEnd=gmmktime(0, 0, 0, date("n"), date("j"), date("Y"));
					$cTimeStart=$cTimeEnd-604800;
				}else{
					$utc = new \DateTimeZone('UTC');
					$cTimeEnd = new \DateTime('last Monday', $utc);
					//$cTimeEnd=strtotime('last monday', time());
					$cTimeStart=$cTimeEnd->getTimestamp()-604800;
					$cTimeEnd=$cTimeEnd->getTimestamp();
				}
				break;
			case 'month':
				$cTimeStart = gmmktime (0, 0, 0, date("n")-1, 1, date("Y"));
				$cTimeEnd = strtotime("+1 month", $cTimeStart);
				break;
			case 'quarter':
				$cMonth=date("n");
				if($cMonth>9){
					$cTimeStart = gmmktime (0, 0, 0, 7, 1, date("Y"));
				}elseif($cMonth>6){
					$cTimeStart = gmmktime (0, 0, 0, 4, 1, date("Y"));
				}elseif($cMonth>3){
					$cTimeStart = gmmktime (0, 0, 0, 1, 1, date("Y"));
				}else{
					$cTimeStart = gmmktime (0, 0, 0, 10, 1, date("Y")-1);
				}
				$cTimeEnd = strtotime("+3 month", $cTimeStart);
				break;
			case 'year':
				$cTimeStart = gmmktime (0, 0, 0, 1, 1, date("Y")-1);
				$cTimeEnd = strtotime("+1 year", $cTimeStart);
				break;
		}
		return ['startTime'=>$cTimeStart, 'endTime'=>$cTimeEnd];
	}

	public function setBonus(){
		if(!empty($this->profileSetting['settings']["condition"]["children"])){
			global $DB;
			$query='select 
				b_user.id as id,
				b_user.name as name,
				b_user.lid as site,
				b_user.email as email,
				GROUP_CONCAT(b_user_group.group_id) as groups
				from b_user left join b_user_group on (b_user_group.user_id=b_user.id)
				group by b_user.id
				;';
			$rsUsers=$DB->Query($query);
			$users=[];
            $ranks=$this->ranksObject->getRankUsers();
			while ($arUser = $rsUsers->Fetch()) {
                $arUser['rank']=empty($ranks[$arUser['id']])?0:$ranks[$arUser['id']];
                $users[]=$arUser;
			}
			$totalUserPeriod=[];
			$totalRefPeriod=[];
			$options=$this->globalSettings->getOptions();
			foreach($this->profileSetting['settings']["condition"]["children"] as $nextO){
				if(!isset($nextO['children'])){
					continue;
				}else{
					if($nextO['controlId']=='registerbyRef' && $nextO['values']['bonus']>0){
						$periodTime=$this->getInterval($nextO['values']['period']);
						$bonus=$this->calculateBonus($nextO['values']);
						$usersPeriod=$this->usersPeriod($bonus['startBonus'], $bonus['endBonus']);//already set bonus
						
						foreach($users as $nextUser){
							$status=$this->checkConditionGroup($nextO,$nextUser);
							if($status){
								if(!in_array($nextUser['id'], $usersPeriod)){//already added to bonus table
									$bonus=$nextO['values']['bonus'];
									$unit=(!empty($nextO['values']['bonus_unit']) && $nextO['values']['bonus_unit']=='bonus')?'bonus':'percent';
									
									if($unit=='percent'){
										$turnovers=$this->getUsersTurnover($periodTime['startTime'], $periodTime['endTime']);
										$turnover=(!empty($turnovers[$nextUser['id']]))?$turnovers[$nextUser['id']]['total_price']:0;
										$bonus=$turnover*$bonus/100;
									}
									if($bonus>0){
										$keyPeriod=$periodTime['startTime'].'_'.$periodTime['endTime'];
										$tmpParams=$nextO['values'];
										$tmpParams['bonus']=$bonus;
										
										$coeff=$this->getRankCoeff($nextUser['id']);
										$tmpParams['bonus']=$coeff*$tmpParams['bonus'];
										
										$bonus=$this->calculateBonus($tmpParams);
										
										$totalUserPeriod[$keyPeriod]['start_bonus']=$bonus['startBonus'];
										$totalUserPeriod[$keyPeriod]['end_bonus']=$bonus['endBonus'];
										
										//action_id
										if(!empty($nextO['values']['number_action'])){
											$totalUserPeriod[$keyPeriod]['action_id']=$nextO['values']['number_action'];
										}
										
										if(!empty($totalUserPeriod[$keyPeriod]['users'][$nextUser['id']])){
											$totalUserPeriod[$keyPeriod]['users'][$nextUser['id']]+=$bonus['size'];
										}else{
											$totalUserPeriod[$keyPeriod]['users'][$nextUser['id']]=$bonus['size'];
										}
									}
								}
							}
						}
					}elseif($nextO['controlId']=='registerbyParentRef' && $nextO['values']['bonus']>0){
						if(!isset($nextO['children'])){
							continue;
						}else{
							if(!isset($users[0]['levels'])){//add refelal levels
								$users=$this->getRefParents($users);
							}
							foreach($users as $nextUser){
								$this->conditionParameters=[];
								$status=$this->checkConditionGroup($nextO,$nextUser);
								if($status){
									foreach($nextUser['levels'] as $keyParent=>$nextParent){
										if($nextParent>0){
											if(isset($this->conditionParameters['levelParent']) && !in_array($nextParent, $this->conditionParameters['levelParent'])){
												continue;
											}
											$periodTime=$this->getInterval($nextO['values']['period']);
											$bonusPeriod=$this->calculateBonus($nextO['values']);
											$referralsPeriod=$this->referralsPeriod($bonusPeriod['startBonus'], $bonusPeriod['endBonus']);//already set bonus
											if(empty($referralsPeriod[$nextUser['id']]) || !in_array($nextParent, $referralsPeriod[$nextUser['id']])){//already added to bonus table
												$bonus=$nextO['values']['bonus'];
												$unit=(!empty($nextO['values']['bonus_unit']) && $nextO['values']['bonus_unit']=='bonus')?'bonus':'percent';
												
												if($unit=='percent'){
													$turnovers=$this->getUsersTurnover($periodTime['startTime'], $periodTime['endTime']);
													$turnover=(!empty($turnovers[$nextUser['id']]))?$turnovers[$nextUser['id']]['total_price']:0;
													$bonus=$turnover*$nextO['values']['bonus']/100;
												}
												if($bonus>0){
													$keyPeriod=$periodTime['startTime'].'_'.$periodTime['endTime'];
													$tmpParams=$nextO['values'];
													$tmpParams['bonus']=$bonus;
													
													$coeff=$this->getRankCoeff($nextUser['id']);
													$tmpParams['bonus']=$coeff*$tmpParams['bonus'];
													
													$bonus=$this->calculateBonus($tmpParams);
													$totalRefPeriod[$keyPeriod]['start_bonus']=$bonus['startBonus'];
													$totalRefPeriod[$keyPeriod]['end_bonus']=$bonus['endBonus'];
													
													//action_id
													if(!empty($nextO['values']['number_action'])){
														$totalRefPeriod[$keyPeriod]['action_id']=$nextO['values']['number_action'];
													}
													
													if(!empty($totalRefPeriod[$keyPeriod]['users'][$nextUser['id']][$nextParent])){
														$totalRefPeriod[$keyPeriod]['users'][$nextUser['id']][$nextParent]+=$bonus['size'];
													}else{
														$totalRefPeriod[$keyPeriod]['users'][$nextUser['id']][$nextParent]=$bonus['size'];
														
													}
												}
											}
										}else{
											break;
										}
									}
								}
							}
						}
					}
				}
			}
			if(count($totalUserPeriod)>0){
				foreach($totalUserPeriod as $keyPeriod=>$nextUser){
					$keyPeriods=explode('_',$keyPeriod);
					$turnovers=$this->getUsersTurnover($keyPeriods[0], $keyPeriods[1]);
					
					foreach($nextUser['users'] as $keyUser=>$bonusUser){
						$totalBonus=(!empty($turnovers[$keyUser]))?$turnovers[$keyUser]['total_price']:0;
						
						$mailTmplt=$this->getUserEmail($keyUser, \CurrencyFormat($bonusUser, $options['currency']), \CurrencyFormat($totalBonus, $options['currency']));
						$mailTmplt=($mailTmplt!=false)?serialize($mailTmplt):'';
						
						$SMSTmplt=$this->getUserSMS($keyUser, $bonusUser, $totalBonus);
						$SMSTmplt=($SMSTmplt!=false)?serialize($SMSTmplt):'';
						
						$dateRemove=(empty($nextUser['end_bonus']))?'null':'FROM_UNIXTIME('.$nextUser['end_bonus'].')';
						
						$fields=[
							'bonus_start'=>$bonusUser,
							'bonus'=>$bonusUser,
							'user_id'=>$keyUser,
							'currency'=>'"'.$options['currency'].'"',
							'profile_type'=>'"'.$this->profileSetting['type'].'"',
							'profile_id'=>$this->profileSetting['id'],
							'status'=>'"inactive"',
							'date_add'=>'FROM_UNIXTIME('.$nextUser['start_bonus'].')',
							'date_remove'=>$dateRemove, 
							'add_comment'=>'"'.$DB->ForSql(Loc::getMessage("commerce.loyaltyprogram_BONUS_USER_BONUSTURNOVER_ADD", ["#NUM#"=>$bonusUser])).'"',
							'email'=>"'".$mailTmplt."'",
							'sms'=>"'".$SMSTmplt."'"
						];
						
						//action_id
						if(!empty($nextUser['action_id'])){
							$fields['action_id']=$nextUser['action_id'];
						}
						
						$idIns=$DB->Insert($this->globalSettings->getTableBonusList(), $fields, $err_mess.__LINE__);
					}
				}
			}
			if(count($totalRefPeriod)>0){
				foreach($totalRefPeriod as $keyPeriod=>$nextUsers){
					$keyPeriods=explode('_',$keyPeriod);
					$turnovers=$this->getUsersTurnover($keyPeriods[0], $keyPeriods[1]);
					foreach($nextUsers['users'] as $keyUser=>$nextUser){
						foreach($nextUser as $keyRef=>$valRef){
							$nextTurn=(empty($turnovers[$keyRef]))?0:$turnovers[$keyRef]['total_price'];
							$fields=['bonus'=>$valRef, 'start_bonus'=>$nextUsers['start_bonus'], 'end_bonus'=>$nextUsers['end_bonus']];
							//action_id
							if(!empty($nextUsers['action_id'])){
								$fields['action_id']=$nextUsers['action_id'];
							}
							$this->setReferalBonuses($keyUser, $keyRef, $fields, $nextTurn);
						}
						
					}
				}
			}
			return true;
		}
		return false;
	}

	private function calculateBonus($params){
		$periodTime=$this->getInterval($params['period']);
		$bonus=[
			'size'=>$params['bonus'],
			'startBonus'=>$periodTime['endTime'],
			'endBonus'=>'null'
		];
		if($params['bonus_round']=='more'){
			$bonus['size']=ceil($bonus['size']);
		}elseif($params['bonus_round']=='less'){
			$bonus['size']=floor($bonus['size']);
		}else{
			$bonus['size']=round($bonus['size']);
		}
		if(!empty($params['allow_bonus_max']) && $params['allow_bonus_max']=='N'){
			$params['bonus_max']=0;
		}
		if(!empty($params['bonus_max']) && $params['bonus_max']<$bonus['size']){
			$bonus['size']=$params['bonus_max'];
		}
		if(!empty($params['through_time']) && $params['through_time']=='N'){
			$params['bonus_delay']=0;
		}
		if(!empty($params['bonus_delay']) && $params['bonus_delay']>0){
			$bonus['startBonus']+=$params['bonus_delay']*$this->timePart[$params['bonus_delay_type']];
		
		}
		if(!empty($params['limit_time']) && $params['limit_time']=='N'){
			$params['bonus_live']=0;
		}
		if(!empty($params['bonus_live']) && $params['bonus_live']>0){
			$bonus['endBonus']=$bonus['startBonus']+$params['bonus_live']*$this->timePart[$params['bonus_live_type']];
		}
		return $bonus;
	}
	
	private function getUserEmail($userId, $bonus, $turnover){
		$emailSettings=$this->profileSetting['email_settings'];
		if(!empty($emailSettings['COMMERCE_LOYAL_TURNOVER']['userTemplate'])){
			$rsEM = \CEventMessage::GetByID($emailSettings['COMMERCE_LOYAL_TURNOVER']['userTemplate']);
			if($arEM = $rsEM->Fetch()){
				$sites=array_keys($this->globalSettings->getSites());
				global $DB;
				$results=$DB->Query('select * from b_user where id='.$userId);
				$arUser = $results->Fetch();
				
				if(!empty($arUser['EMAIL'])){
					return [
						"EVENT_NAME" => "COMMERCE_LOYAL_TURNOVER",
						"MESSAGE_ID" => $emailSettings['COMMERCE_LOYAL_TURNOVER']['userTemplate'],
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
	
	private function setReferalBonuses($userId, $refUserId, $bonus, $turnover){
		global $DB;
		$options=$this->globalSettings->getOptions();
		
		$mailTmplt=$this->getRefEmail($refUserId, \CurrencyFormat($bonus['bonus'], $options['currency']), \CurrencyFormat($turnover, $options['currency']));
		$mailTmplt=($mailTmplt!=false)?serialize($mailTmplt):'';
		
		$endBonus=(empty($bonus['end_bonus']))?'null':'FROM_UNIXTIME('.$bonus['end_bonus'].')';
		$fields=[
			'bonus_start'=>$bonus['bonus'],
			'bonus'=>$bonus['bonus'],
			'user_id'=>$refUserId,
			'user_bonus'=>$userId,
			'currency'=>'"'.$options['currency'].'"',
			'profile_type'=>'"'.$this->profileSetting['type'].'"',
			'profile_id'=>$this->profileSetting['id'],
			'status'=>'"inactive"',
			'date_add'=>'FROM_UNIXTIME('.$bonus['start_bonus'].')',
			'date_remove'=>$endBonus,
			'add_comment'=>'"'.$DB->ForSql(Loc::getMessage("commerce.loyaltyprogram_BONUS_USER_BONUSTURNOVERREF_ADD", ["#NUM#"=>$bonus['bonus']])).'"',
			'email'=>"'".$mailTmplt."'"
		];
		
		$SMSTmplt=$this->getRefSMS($refUserId, $bonus['bonus'], $turnover);
		$SMSTmplt=($SMSTmplt!=false)?serialize($SMSTmplt):'';
		$fields['sms']="'".$SMSTmplt."'";
		
		//action_id
		if(!empty($bonus['action_id'])){
			$fields['action_id']=$bonus['action_id'];
		}
		$idIns=$DB->Insert($this->globalSettings->getTableBonusList(), $fields, $err_mess.__LINE__);
					
	
	}
	
	private function getRefEmail($userId, $bonus, $turnover){
		$emailSettings=$this->profileSetting['email_settings'];
		if(!empty($emailSettings['COMMERCE_LOYAL_TURNOVER']['refTemplate'])){
			global $DB;
			$results=$DB->Query('select * from b_user where id='.$userId);
			$arUser = $results->Fetch();
			if(!empty($arUser['EMAIL'])){
				$sites=array_keys($this->globalSettings->getSites());
				if(!isset($this->refTemplate)){
					$rsEM = \CEventMessage::GetByID($emailSettings['COMMERCE_LOYAL_TURNOVER']['refTemplate']);
					if($arEM = $rsEM->Fetch()){
						$this->refTemplate=$arEM;
					}else{
						$this->refTemplate=false;
					}
				}
				if($this->refTemplate!==false){
					return [
						"EVENT_NAME" => "COMMERCE_LOYAL_TURNOVER",
						"MESSAGE_ID" => $emailSettings['COMMERCE_LOYAL_TURNOVER']['refTemplate'],
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
		//$saveFields['site']='"'.$this->clearSites($params['site']).'"';
		$saveFields['date_setting']='NOW()';
		$tmpSettings=[];
		$condition=$this->getTreeFromRequest();
		if(count($condition)>0){
			$tmpSettings['condition']=$condition;
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

	private function usersPeriod($from, $to){
		if($from!=$this->usersPeriodFrom || $to!=$this->usersPeriodTo){
			unset($this->alreadyUsersPeriod);
		}
		$toFilter=(!empty($to) && $to!='null')?'and date_remove=FROM_UNIXTIME('.$to.')':'';
		if(empty($this->alreadyUsersPeriod)){
			$this->alreadyUsersPeriod=[];
			global $DB;
			$query='select * from '.$this->globalSettings->getTableBonusList().'
			where user_bonus=0 and profile_type="'.$this->profileSetting['type'].'" and profile_id='.$this->profileSetting['id'].'
			and date_add=FROM_UNIXTIME('.$from.') '.$toFilter.';';
			$res=$DB->Query($query);
			while($row = $res->Fetch()){
				$this->alreadyUsersPeriod[]=$row['user_id'];
			}
		}
		return $this->alreadyUsersPeriod;
	}

	private function referralsPeriod($from, $to){
		if($from!=$this->usersPeriodFrom || $to!=$this->usersPeriodTo){
			unset($this->alreadyReferralsPeriod);
		}
		if(empty($this->alreadyReferralsPeriod)){
			$to=($to=='null')?'':'date_remove=FROM_UNIXTIME('.$to.')';
			$this->alreadyReferralsPeriod=[];
			global $DB;
			$query='select * from '.$this->globalSettings->getTableBonusList().'
			where user_bonus>0 and profile_type="'.$this->profileSetting['type'].'" and profile_id='.$this->profileSetting['id'].'
			and date_add=FROM_UNIXTIME('.$from.') '.$to.';';
			$res=$DB->Query($query);
			while($row = $res->Fetch()){
				$this->alreadyReferralsPeriod[$row['user_bonus']][]=$row['user_id'];
			}
		}
		return $this->alreadyReferralsPeriod;
	}

	private function getRefParents($users){
		$options=$this->globalSettings->getOptions();
		$ref_level=(!empty($options['ref_level']))?$options['ref_level']:1;

		global $DB;
		$query='select user, ref_user from '.$this->globalSettings->getTableUsersList().';';
		$rsUsers=$DB->Query($query);
		$refs=[];
		while ($arUser = $rsUsers->Fetch()) {
			$refs[$arUser['user']]=$arUser['ref_user'];
		}
		for($i=0; $i<$ref_level; $i++){
			foreach($users as &$nextUser){
				if($i==0){
					$prevId=$nextUser['id'];
				}else{
					$prevId=$nextUser['levels'][($i-1)];
				}
				if($prevId==0 || empty($refs[$prevId])){
					$nextLevel=0;
				}else{
					$nextLevel=$refs[$prevId];
				}
				$nextUser['levels'][]=$nextLevel;
			}
		}
		return $users;
	}

	private function getUsersTurnover($from, $to){
		if($from!=$this->usersTurnoverFrom || $to!=$this->usersTurnoverTo){
			unset($this->usersTurnover);
		}
		if(!isset($this->usersTurnover)){
			$this->usersTurnoverFrom=$from;
			$this->usersTurnoverTo=$to;
			$options=$this->globalSettings->getOptions();
			$where='1=1';
			if(!empty($options['orderstatus'])){
				$statuses=[];
	
				$skip=false;
				foreach($this->globalSettings->getOrderStatuses() as $nextStatus){
					if($nextStatus['STATUS_ID']==$options['orderstatus']){
						$skip=true;
					}
					if($skip){
						$statuses[]=$nextStatus['STATUS_ID'];
					}
				}
				if(count($statuses)>0){
					//$where.=' AND  STATUS_ID="'.$options['orderstatus'].'"';
					$where.=' AND  STATUS_ID IN ("'.implode('","',$statuses).'")';
				}
			}
			global $DB;
			$query='select 
			sum(PRICE-PRICE_DELIVERY) as total_price,
			count(ID) as orders,
			USER_ID as user_id
			from b_sale_order
			where '.$where.' AND DATE_INSERT>=FROM_UNIXTIME('.$from.') and DATE_INSERT < FROM_UNIXTIME('.$to.')
			group by USER_ID;';
			$this->usersTurnover=[];
			$res=$DB->Query($query);
			while($row = $res->Fetch()){
				$this->usersTurnover[$row['user_id']]=['total_price'=>$row['total_price'], 'orders'=>$row['orders']];
			}
		}
		return $this->usersTurnover;
	}
	
	/**
	* condition set
	*/
	protected function getStartCondition($mode=''){
		$params=[
			"id"=>"0",
			"controlId"=>"CondGroup",
			"children"=>[[
				"id"=>"0",
				"controlId"=>"registerbyRef",
				"values"=>[
					"bonus"=>"10",
					"bonus_unit"=>"0",
					"bonus_delay"=>"2",
					"bonus_delay_type"=>"day",
					"allow_bonus_max"=>"Y",
					"bonus_max"=>"1000",
					"bonus_live"=>"4",
					"bonus_live_type"=>"month",
					"bonus_round"=>"none",
					"All"=>"OR",
					"True"=>"True"
				],
				"children"=>[]
			]]
		];
		if($mode=='json'){
			return \Bitrix\Main\Web\Json::encode($params);
		}
		return $params;
	}
	
	public function getConditions($mode=''){
		$sites=$this->globalSettings->getSites();
		$groupUsers=$this->globalSettings->getUserGroups();
		$optns=$this->globalSettings->getOptions();
		$refRarentArr=[];
		for($i=1; $i<=$optns['ref_level']; $i++){
			$refRarentArr[$i]=Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_nLevel", ['#N#'=>$i]);
		}

		$mainParams=[[
            'controlId'=> 'sites',
            'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_turn_sites"),
            'group'=> false,
            'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_sites"),
            'showIn'=> ['registerbyRef', 'registerbyRefSubGrp', 'registerbyParentRef', 'registerbyParentRefSubGrp'],
            'control'=> [[
                'id'=> 'prefix',
                'type'=> 'prefix',
                'text'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_siteEqual")
            ], [
                'id'=> 'logic',
                'name'=> 'logic',
                'type'=> 'select',
                'values'=> [
                    'Equal'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_EQUAL"),
                    'Not'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_NOTEQUAL")
                ],
                'defaultText'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_EQUAL"),
                'defaultValue'=> 'Equal'
            ], [
                'type'=> 'select',
                'multiple'=>'Y',
                'values'=> $sites,
                'id'=> 'sites',
                'name'=> 'sites',
                'show_value'=>'Y',
                //'defaultText'=> $firstSiteName,
                //'defaultValue'=> $firstSiteKey
                'first_option'=> '...',
                'defaultText'=> '...',
                'defaultValue'=> ''
            ]]
        ],
            [
                'controlId'=> 'groupUsers',
                'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_turn_GroupRef"),
                'group'=> false,
                'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_Group"),
                'showIn'=> ['registerbyRef', 'registerbyRefSubGrp','registerbyParentRef', 'registerbyParentRefSubGrp'],
                'control'=> [[
                    'id'=> 'prefix',
                    'type'=> 'prefix',
                    'text'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_Group")
                ], [
                    'id'=> 'logic',
                    'name'=> 'logic',
                    'type'=> 'select',
                    'values'=> [
                        'Equal'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_EQUAL"),
                        'Not'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_NOTEQUAL")
                    ],
                    'defaultText'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_EQUAL"),
                    'defaultValue'=> 'Equal'
                ], [
                    'type'=> 'select',
                    'multiple'=>'Y',
                    'values'=> $groupUsers,
                    'id'=> 'users',
                    'name'=> 'users',
                    'show_value'=>'Y',
                    //'defaultText'=> $firstGroupName,
                    //'defaultValue'=> '',
                    'first_option'=> '...',
                    'defaultText'=> '...',
                    'defaultValue'=> ''
                ]]
            ],[
                'controlId'=> 'turnover',
                'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_hint_turnoverPeriod"),
                'group'=> false,
                'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_turnoverPeriod"),
                'showIn'=> ['registerbyRef', 'registerbyRefSubGrp', 'registerbyParentRef', 'registerbyParentRefSubGrp'],
                'control'=> [[
                    'id'=> 'prefix',
                    'type'=> 'prefix',
                    'text'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_turnoverPeriod")
                ], [
                    'id'=> 'logic',
                    'name'=> 'logic',
                    'type'=> 'select',
                    'values'=> [
                        'more'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_MORE"),
                        'less'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_LESS")
                    ],
                    'defaultText'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_MORE"),
                    'defaultValue'=> 'more'
                ],
                    [
                        'type'=> 'input',
                        'id'=> 'turnover',
                        'name'=> 'turnover',
                        'param_id'=>'n',
                        'show_value'=>'Y',
                        'defaultValue'=>'100'
                    ],
                    $optns['currency']
                ]
            ],[
                'controlId'=> 'bonusAdded',
                'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_hint_turnoverBonusAdded"),
                'group'=> false,
                'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_turnoverBonusAdded"),
                'showIn'=> ['registerbyRef', 'registerbyRefSubGrp', 'registerbyParentRef', 'registerbyParentRefSubGrp'],
                'control'=> [[
                    'id'=> 'prefix',
                    'type'=> 'prefix',
                    'text'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_turnoverBonusAdded")
                ], [
                    'id'=> 'logic',
                    'name'=> 'logic',
                    'type'=> 'select',
                    'values'=> [
                        'more'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_MORE"),
                        'less'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_LESS")
                    ],
                    'defaultText'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_MORE"),
                    'defaultValue'=> 'more'
                ],
                    [
                        'type'=> 'input',
                        'id'=> 'bonus_added',
                        'name'=> 'bonus_added',
                        'param_id'=>'n',
                        'show_value'=>'Y',
                        'defaultValue'=>'100'
                    ],
                    $optns['currency']
                ]
            ],[
                'controlId'=> 'bonusRemove',
                'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_hint_turnoverBonusRemove"),
                'group'=> false,
                'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_turnoverBonusRemove"),
                'showIn'=> ['registerbyRef', 'registerbyRefSubGrp', 'registerbyParentRef', 'registerbyParentRefSubGrp'],
                'control'=> [[
                    'id'=> 'prefix',
                    'type'=> 'prefix',
                    'text'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_turnoverBonusRemove")
                ], [
                    'id'=> 'logic',
                    'name'=> 'logic',
                    'type'=> 'select',
                    'values'=> [
                        'more'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_MORE"),
                        'less'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_LESS")
                    ],
                    'defaultText'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_MORE"),
                    'defaultValue'=> 'more'
                ],
                    [
                        'type'=> 'input',
                        'id'=> 'bonus_remove',
                        'name'=> 'bonus_remove',
                        'param_id'=>'n',
                        'show_value'=>'Y',
                        'defaultValue'=>'100'
                    ],
                    $optns['currency']
                ]
            ],[
                'controlId'=> 'levelParent',
                'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_hint_levelParentRef"),
                'group'=> false,
                'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_levelParentRef"),
                'showIn'=> ['registerbyParentRef', 'registerbyParentRefSubGrp'],
                'control'=> [[
                    'id'=> 'prefix',
                    'type'=> 'prefix',
                    'text'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_levelParentRef")
                ], [
                    'id'=> 'logic',
                    'name'=> 'logic',
                    'type'=> 'select',
                    'values'=> [
                        'Equal'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_EQUAL"),
                        'Not'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_NOTEQUAL")
                    ],
                    'defaultText'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_EQUAL"),
                    'defaultValue'=> 'Equal'
                ], [
                    'type'=> 'select',
                    'multiple'=>'Y',
                    'values'=> $refRarentArr,
                    'id'=> 'level_parent',
                    'name'=> 'level_parent',
                    //'defaultText'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_nLevel", ['#N#'=>1]),
                    //'defaultValue'=> '1'
                    'first_option'=> '...',
                    'defaultText'=> '...',
                    'defaultValue'=> ''
                ]]
            ]];
        $ranks=$this->ranksObject->getRanks();
        if(count($ranks)>0){
            $ranksList=[];
            foreach ($ranks as $nextRank){
                $ranksList[$nextRank['id']]=$nextRank['name'];
            }
            $mainParams[]=[
                'controlId'=> 'ranksUser',
                'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_RankUser_hint"),
                'group'=> false,
                'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_RankUser"),
                'showIn'=> ['registerbyRef', 'registerbyRefSubGrp'],
                'control'=> [[
                    'id'=> 'prefix',
                    'type'=> 'prefix',
                    'text'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_RankUser")
                ], [
                    'id'=> 'logic',
                    'name'=> 'logic',
                    'type'=> 'select',
                    'values'=> [
                        'Equal'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_EQUAL"),
                        'Not'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_NOTEQUAL")
                    ],
                    'defaultText'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_EQUAL"),
                    'defaultValue'=> 'Equal'
                ], [
                    'type'=> 'select',
                    'multiple'=>'Y',
                    'values'=> $ranksList,
                    'id'=> 'ranks',
                    'name'=> 'ranks',
                    'show_value'=>'Y',
                    'first_option'=> '...',
                    'defaultText'=> '...',
                    'defaultValue'=> ''
                ]]
            ];

            $mainParams[]=[
                'controlId'=> 'ranksParentRef',
                'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_RankParent_hint"),
                'group'=> false,
                'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_RankParent"),
                'showIn'=> ['registerbyParentRef', 'registerbyParentRefSubGrp'],
                'control'=> [[
                    'id'=> 'prefix',
                    'type'=> 'prefix',
                    'text'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_RankParent")
                ], [
                    'id'=> 'logic',
                    'name'=> 'logic',
                    'type'=> 'select',
                    'values'=> [
                        'Equal'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_EQUAL"),
                        'Not'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_NOTEQUAL")
                    ],
                    'defaultText'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_EQUAL"),
                    'defaultValue'=> 'Equal'
                ], [
                    'type'=> 'select',
                    'multiple'=>'Y',
                    'values'=> $ranksList,
                    'id'=> 'ranks_parent',
                    'name'=> 'ranks_parent',
                    'show_value'=>'Y',
                    'first_option'=> '...',
                    'defaultText'=> '...',
                    'defaultValue'=> ''
                ]]
            ];
        }
		
		$params = [
			[
				'controlId'=> 'registerbyRef',
				'group'=> true,
				'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_registerbyRef"),
				'showIn'=> ['CondGroup'],
				'visual'=> [
					'controls'=> ['All', 'True'],
					'values'=> [[
							'All'=> 'AND',
							'True'=> 'True'
						],
						[
							'All'=> 'AND',
							'True'=> 'False'
						],
						[
							'All'=> 'OR',
							'True'=> 'True'
						],
						[
							'All'=> 'OR',
							'True'=> 'False'
						]
					],
					'logic'=> [[
							'style'=> 'condition-logic-and',
							'message'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_AND")//and
						],
						[
							'style'=> 'condition-logic-and',
							'message'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_ANDNOT")//and not
						],
						[
							'style'=> 'condition-logic-or',
							'message'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_OR")//or
						],
						[
							'style'=> 'condition-logic-or',
							'message'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_ORNOT")//not or
						]
					]
				],
				'control'=> [
					[
						'id'=> 'number_action',
						'name'=> 'number_action',
						'type'=> 'input',
						'show_value'=>'Y',
						'defaultValue'=>'0'
					],
					Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_registerbyRef"),
					[
						'id'=> 'bonus',
						'name'=> 'bonus',
						'type'=> 'input',
						'show_value'=>'Y',
						'defaultValue'=>''
					],
					[
						'id'=> 'bonus_unit',
						'name'=> 'bonus_unit',
						'type'=> 'select',
						'values'=> [
							'percent'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_UNIT_PERCENT_TURNOVER"),
							'bonus'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_UNIT_BONUS")
						],
						'defaultText'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_UNIT_PERCENT_TURNOVER"),
						'defaultValue'=> 'percent',
					],
					[
						'id'=>'allow_bonus_max',
						'name'=> 'allow_bonus_max',
						'type'=> 'select',
						'values'=> [
							'Y'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_maxbonus"),
							'N'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_UNLIMITED_BONUS")
						],
						'defaultValue'=> 'Y',
					],
					[
						'id'=> 'bonus_max',
						'name'=> 'bonus_max',
						'type'=> 'input',
						'show_value'=>'Y',
						'defaultValue'=>''
					],
					Loc::getMessage("commerce.loyaltyprogram_CONDITION_UNIT_BONUS"),
					[
						'id'=>'through_time',
						'name'=>'through_time',
						'type'=> 'select',
						'values'=> [
							'Y'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_through"),
							'N'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_UNLIMITED_THROUGH")
						],
						'defaultValue'=> 'Y',
					],
					[
						'id'=> 'bonus_delay',
						'name'=> 'bonus_delay',
						'type'=> 'input',
						'show_value'=>'Y',
						'defaultValue'=>''
					],
					[
						'id'=> 'bonus_delay_type',
						'name'=> 'bonus_delay_type',
						'type'=> 'select',
						'values'=> [
							'hour'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_HOUR"),
							'day'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_DAY"),
							'week'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_WEEK"),
							'month'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_MONTH"),
						],
						'defaultText'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_DAY"),
						'defaultValue'=> 'day',
					],
					[
						'id'=>'limit_time',
						'name'=>'limit_time',
						'type'=> 'select',
						'values'=> [
							'Y'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_lengthOn"),
							'N'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_UNLIMITED_TIME")
						],
						'defaultValue'=> 'Y',
					],
					[
						'id'=> 'bonus_live',
						'name'=> 'bonus_live',
						'type'=> 'input',
						'show_value'=>'Y',
						'defaultValue'=>'0'
					],
					[
						'id'=> 'bonus_live_type',
						'name'=> 'bonus_live_type',
						'type'=> 'select',
						'values'=> [
							'day'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_DAY"),
							'week'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_WEEK"),
							'month'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_MONTH"),
						],
						'defaultText'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_DAY"),
						'defaultValue'=> 'day',
					],
					Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_BY"),
					[
						'type'=> 'select',
						'values'=>[
							'week'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_WEEK"),
							'month'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_MONTH"),
							'quarter'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_QUARTER"),
							'year'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_YEAR")
						],
						'id'=> 'period',
						'name'=> 'period',
						'first_option'=> '...',
						'defaultText'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_MONTH"),
						'defaultValue'=> 'month'
					],
					Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_round"),
					[
						'id'=> 'bonus_round',
						'name'=> 'bonus_round',
						'type'=> 'select',
						'values'=> [
							'none'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_ROUND_NONE"),
							'more'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_ROUND_MORE"),
							'less'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_ROUND_LESS"),
							'auto'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_ROUND_AUTO")
						],
						'defaultText'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_ROUND_NONE"),
						'defaultValue'=> 'none',
					],
					Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_forWhich"),
					[
						'id'=> 'All',
						'name'=> 'aggregator',
						'type'=> 'select',
						'values'=> [
							'AND'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_ALL_CONDITIONS"),
							'OR'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_ANY_CONDITIONS"),
						],
						'defaultText'=> '...',
						'defaultValue'=> 'AND',
						'first_option'=> '...'
					],
					[
						'id'=> 'True',
						'name'=> 'value',
						'type'=> 'select',
						'values'=> [
							'True'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_ANY_EXECUTE"),
							'False'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_ANY_NOTEXECUTE")
						],
						'defaultText'=> '...',
						'defaultValue'=> 'True',
						'first_option'=> '...'
					]
				],
				'mess'=> [
					'ADD_CONTROL'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_ADD_CONTROL"),
					'SELECT_CONTROL'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_SELECT_CONTROL")
				]
			],
			[
				'controlId'=> 'registerbyRefSubGrp',
				'group'=> true,
				'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_GROUP_CONTROL"),
				'defaultText'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_SELECT_VALUE"),
				'showIn'=> ['registerbyRef'],
				'visual'=> [
					'controls'=> ['All', 'True'],
					'values'=> [[
							'All'=> 'AND',
							'True'=> 'True'
						],
						[
							'All'=> 'AND',
							'True'=> 'False'
						],
						[
							'All'=> 'OR',
							'True'=> 'True'
						],
						[
							'All'=> 'OR',
							'True'=> 'False'
						]
					],
					'logic'=> [[
							'style'=> 'condition-logic-and',
							'message'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_AND")
						],
						[
							'style'=> 'condition-logic-and',
							'message'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_ANDNOT")
						],
						[
							'style'=> 'condition-logic-or',
							'message'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_OR")
						],
						[
							'style'=> 'condition-logic-or',
							'message'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_ORNOT")
						]
					]
				],
				'control'=> [[
						'id'=> 'All',
						'name'=> 'aggregator',
						'type'=> 'select',
						'values'=> [
							'AND'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_ALL_CONDITIONS"),
							'OR'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_ANY_CONDITIONS")
						],
						'defaultText'=> '...',
						'defaultValue'=> 'AND',
						'first_option'=> '...'
					],
					[
						'id'=> 'True',
						'name'=> 'value',
						'type'=> 'select',
						'values'=> [
							'True'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_ANY_EXECUTE"),
							'False'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_ANY_NOTEXECUTE")
						],
						'defaultText'=> '...',
						'defaultValue'=> 'True',
						'first_option'=> '...'
					]
				]
			],
			[
				'controlId'=> 'registerbyParentRef',
				'group'=> true,
				'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_BonusParentRef"),
				'showIn'=> ['CondGroup'],
				'visual'=> [
					'controls'=> ['All', 'True'],
					'values'=> [[
							'All'=> 'AND',
							'True'=> 'True'
						],
						[
							'All'=> 'AND',
							'True'=> 'False'
						],
						[
							'All'=> 'OR',
							'True'=> 'True'
						],
						[
							'All'=> 'OR',
							'True'=> 'False'
						]
					],
					'logic'=> [[
							'style'=> 'condition-logic-and',
							'message'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_AND")
						],
						[
							'style'=> 'condition-logic-and',
							'message'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_ANDNOT")
						],
						[
							'style'=> 'condition-logic-or',
							'message'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_OR")
						],
						[
							'style'=> 'condition-logic-or',
							'message'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_ORNOT")
						]
					]
				],
				'control'=> [
					[
						'id'=> 'number_action',
						'name'=> 'number_action',
						'type'=> 'input',
						'show_value'=>'Y',
						'defaultValue'=>'0'
					],
					Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_BonusParentRef"),
					[
						'id'=> 'bonus',
						'name'=> 'bonus',
						'type'=> 'input',
						'show_value'=>'Y',
						'defaultValue'=>'10'
					],
					[
						'id'=> 'bonus_unit',
						'name'=> 'bonus_unit',
						'type'=> 'select',
						'values'=> [
							'percent'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_UNIT_PERCENT_TURNOVER_USER"),
							'bonus'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_UNIT_BONUS")
						],
						'defaultText'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_UNIT_PERCENT_TURNOVER_USER"),
						'defaultValue'=> 'percent',
					],
					[
						'id'=>'allow_bonus_max',
						'name'=> 'allow_bonus_max',
						'type'=> 'select',
						'values'=> [
							'Y'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_maxbonus"),
							'N'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_UNLIMITED_BONUS")
						],
						'defaultValue'=> 'Y',
					],
					[
						'id'=> 'bonus_max',
						'name'=> 'bonus_max',
						'type'=> 'input',
						'show_value'=>'Y',
						'defaultValue'=>''
					],
					Loc::getMessage("commerce.loyaltyprogram_CONDITION_UNIT_BONUS"),
					[
						'id'=>'through_time',
						'name'=>'through_time',
						'type'=> 'select',
						'values'=> [
							'Y'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_through"),
							'N'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_UNLIMITED_THROUGH")
						],
						'defaultValue'=> 'Y',
					],
					[
						'id'=> 'bonus_delay',
						'name'=> 'bonus_delay',
						'type'=> 'input',
						'show_value'=>'Y',
						'defaultValue'=>'2'
					],
					[
						'id'=> 'bonus_delay_type',
						'name'=> 'bonus_delay_type',
						'type'=> 'select',
						'values'=> [
							'hour'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_HOUR"),
							'day'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_DAY"),
							'week'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_WEEK"),
							'month'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_MONTH"),
						],
						'defaultText'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_DAY"),
						'defaultValue'=> 'day',
					],
					[
						'id'=>'limit_time',
						'name'=>'limit_time',
						'type'=> 'select',
						'values'=> [
							'Y'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_lengthOn"),
							'N'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_UNLIMITED_TIME")
						],
						'defaultValue'=> 'Y',
					],
					[
						'id'=> 'bonus_live',
						'name'=> 'bonus_live',
						'type'=> 'input',
						'show_value'=>'Y',
						'defaultValue'=>'0'
					],
					[
						'id'=> 'bonus_live_type',
						'name'=> 'bonus_live_type',
						'type'=> 'select',
						'values'=> [
							'day'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_DAY"),
							'week'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_WEEK"),
							'month'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_MONTH"),
						],
						'defaultText'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_DAY"),
						'defaultValue'=> 'day',
					],
					Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_BY"),
					[
						'type'=> 'select',
						'values'=>[
							'week'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_WEEK"),
							'month'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_MONTH"),
							'quarter'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_QUARTER"),
							'year'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_YEAR")
						],
						'id'=> 'period',
						'name'=> 'period',
						'first_option'=> '...',
						'defaultText'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_MONTH"),
						'defaultValue'=> 'month'
					],
					Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_round"),
					[
						'id'=> 'bonus_round',
						'name'=> 'bonus_round',
						'type'=> 'select',
						'values'=> [
							'none'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_ROUND_NONE"),
							'more'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_ROUND_MORE"),
							'less'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_ROUND_LESS"),
							'auto'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_ROUND_AUTO")
						],
						'defaultText'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_ROUND_NONE"),
						'defaultValue'=> 'none',
					],
					Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_forWhich"),
					[
						'id'=> 'All',
						'name'=> 'aggregator',
						'type'=> 'select',
						'values'=> [
							'AND'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_ALL_CONDITIONS"),
							'OR'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_ANY_CONDITIONS")
						],
						'defaultText'=> '...',
						'defaultValue'=> 'AND',
						'first_option'=> '...'
					],
					[
						'id'=> 'True',
						'name'=> 'value',
						'type'=> 'select',
						'values'=> [
							'True'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_ANY_EXECUTE"),
							'False'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_ANY_NOTEXECUTE")
						],
						'defaultText'=> '...',
						'defaultValue'=> 'True',
						'first_option'=> '...'
					]
				],
				'mess'=> [
					'ADD_CONTROL'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_ADD_CONTROL"),
					'SELECT_CONTROL'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_SELECT_CONTROL")
				]
			],
			[
				'controlId'=> 'registerbyParentRefSubGrp',
				'group'=> true,
				'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_GROUP_CONTROL"),
				'defaultText'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_SELECT_VALUE"),
				'showIn'=> ['registerbyParentRef'],
				'visual'=> [
					'controls'=> ['All', 'True'],
					'values'=> [[
							'All'=> 'AND',
							'True'=> 'True'
						],
						[
							'All'=> 'AND',
							'True'=> 'False'
						],
						[
							'All'=> 'OR',
							'True'=> 'True'
						],
						[
							'All'=> 'OR',
							'True'=> 'False'
						]
					],
					'logic'=> [[
							'style'=> 'condition-logic-and',
							'message'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_AND")
						],
						[
							'style'=> 'condition-logic-and',
							'message'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_ANDNOT")
						],
						[
							'style'=> 'condition-logic-or',
							'message'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_OR")
						],
						[
							'style'=> 'condition-logic-or',
							'message'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_ORNOT")
						]
					]
				],
				'control'=> [[
						'id'=> 'All',
						'name'=> 'aggregator',
						'type'=> 'select',
						'values'=> [
							'AND'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_ALL_CONDITIONS"),
							'OR'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_ANY_CONDITIONS")
						],
						'defaultText'=> '...',
						'defaultValue'=> 'AND',
						'first_option'=> '...'
					],
					[
						'id'=> 'True',
						'name'=> 'value',
						'type'=> 'select',
						'values'=> [
							'True'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_ANY_EXECUTE"),
							'False'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_ANY_NOTEXECUTE")
						],
						'defaultText'=> '...',
						'defaultValue'=> 'True',
						'first_option'=> '...'
					]
				]
			],
			[
				'controlgroup'=> true,
				'group'=> false,
				'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_MainParameters"),
				'showIn'=> ['registerbyRef', 'registerbyRefSubGrp', 'registerbyParentRef', 'registerbyParentRefSubGrp'],
				'children'=> $mainParams
			],
			[
				'controlgroup'=> true,
				'group'=> false,
				'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_ShopParameters"),
				'showIn'=> ['registerbyRef', 'registerbyRefSubGrp', 'registerbyParentRef', 'registerbyParentRefSubGrp'],
				'children'=> [[
					'controlId'=> 'countOrders',
					'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_hint_countOrders"),
					'group'=> false,
					'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_countOrders"),
					'showIn'=> ['registerbyRef', 'registerbyRefSubGrp', 'registerbyParentRef', 'registerbyParentRefSubGrp'],
					'control'=> [[
						'id'=> 'prefix',
						'type'=> 'prefix',
						'text'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_countOrders")
					], [
						'id'=> 'logic',
						'name'=> 'logic',
						'type'=> 'select',
						'values'=> [
							'more'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_MORE"),
							'less'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_LESS")
						],
						'defaultText'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_MORE"),
						'defaultValue'=> 'more'
					],
					[
						'type'=> 'input',
						'id'=> 'count_orders',
						'name'=> 'count_orders',
						'param_id'=>'n',
						'show_value'=>'Y',
						'defaultValue'=>'100'
					]
					]
				],]
			],
			[
				'controlId'=> 'CondGroup',
				'group'=> true,
				'label'=> '',
				'defaultText'=> '',
				'showIn'=> [],
				'control'=> [Loc::getMessage("commerce.loyaltyprogram_CONDITION_PERFORM_OPERATIONS")]
			]
		];
		if($mode=='json'){
			return \Bitrix\Main\Web\Json::encode($params);
		}
		return $params;
	}

	private function getBonusAdded($from, $to){
		if($from!=$this->usersTurnoverFrom || $to!=$this->usersTurnoverTo){
			unset($this->usersBonusAdded);
		}
		if(!isset($this->usersBonusAdded)){
			$this->usersTurnoverFrom=$from;
			$this->usersTurnoverTo=$to;
			global $DB;
			$query='select 
			sum(bonus_start) as bonus,
			user_id as user_id
			from '.$this->globalSettings->getTableBonusList().'
			where date_add>=FROM_UNIXTIME('.$from.') and date_add < FROM_UNIXTIME('.$to.')
			group by user_id;';
			$this->usersBonusAdded=[];
			$res=$DB->Query($query);
			while($row = $res->Fetch()){
				$this->usersBonusAdded[$row['user_id']]=$row['bonus'];
			}
		}
		return $this->usersBonusAdded;
	}

	private function getBonusRemove($from, $to){
		if($from!=$this->usersTurnoverFrom || $to!=$this->usersTurnoverTo){
			unset($this->usersBonusRemove);
		}
		if(!isset($this->usersBonusRemove)){
			$this->usersTurnoverFrom=$from;
			$this->usersTurnoverTo=$to;
			global $DB;
			$query='select 
			sum(AMOUNT) as bonus,
			USER_ID as user_id
			from b_sale_user_transact
			where DEBIT="N" AND TRANSACT_DATE>=FROM_UNIXTIME('.$from.') and TRANSACT_DATE < FROM_UNIXTIME('.$to.')
			group by USER_ID;';
			$this->usersBonusRemove=[];
			$res=$DB->Query($query);
			while($row = $res->Fetch()){
				$this->usersBonusRemove[$row['user_id']]=$row['bonus'];
			}
		}
		return $this->usersBonusRemove;
	}

	private function checkConditionGroup($group,$user){
		if(empty($group['children']) || count($group['children'])==0){
			//set bonus
			return true;
		}else{
			$condition='AND';
			if($group['values']['All']=='AND' && $group['values']['True']=='False'){$condition='ANDNOT';}
			elseif($group['values']['All']=='OR' && $group['values']['True']=='True'){$condition='OR';}
			elseif($group['values']['All']=='OR' && $group['values']['True']=='False'){$condition='ORNOT';}
			
			foreach($group['children'] as $nextChildren){
				if(!empty($nextChildren['children'])){
					$condStatus=$this->checkConditionGroup($nextChildren, $user);
				}else{
					$condStatus=false;
					switch ($nextChildren['controlId']){
						case 'sites':
							$condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $user['site'], $nextChildren['values']['sites']);
						break;
						case 'groupUsers':
							$groups=explode(',',$user['groups']);
							$condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $groups, $nextChildren['values']['users']);
						break;
                        case 'ranksUser':
                            $condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $user['rank'], $nextChildren['values']['ranks']);
                        break;
						case 'turnover':
							$per=$this->getInterval($group["values"]["period"]);
							$turnovers=$this->getUsersTurnover($per['startTime'], $per['endTime']);
							$turnover=(!empty($turnovers[$user['id']]))?$turnovers[$user['id']]['total_price']:0;
							$condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $turnover, $nextChildren['values']['turnover']);
						break;
						case 'bonusAdded':
							$per=$this->getInterval($group["values"]["period"]);
							$bonuses=$this->getBonusAdded($per['startTime'], $per['endTime']);
							$bonus=(!empty($bonuses[$user['id']]))?$bonuses[$user['id']]:0;
							$condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $bonus, $nextChildren['values']['bonus_added']);
						break;
						case 'bonusRemove':
							$per=$this->getInterval($group["values"]["period"]);
							$bonuses=$this->getBonusRemove($per['startTime'], $per['endTime']);
							$bonus=(!empty($bonuses[$user['id']]))?$bonuses[$user['id']]:0;
							$condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $bonus, $nextChildren['values']['bonus_remove']);
						break;
						case 'countOrders':
							$per=$this->getInterval($group["values"]["period"]);
							$turnovers=$this->getUsersTurnover($per['startTime'], $per['endTime']);
							$orders=(!empty($turnovers[$user['id']]['orders']))?$turnovers[$user['id']]['orders']:0;
							$condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $orders, $nextChildren['values']['count_orders']);
						break;
						case 'levelParent':
							$refRarentLevel=[];
							$this->conditionParameters['levelParent']=[];
							$i=1;
							foreach($user['levels'] as $val){
								$refRarentLevel[]=$i;
								if(
									(in_array($i, $nextChildren['values']['level_parent']) && $nextChildren['values']['logic']=='Equal' && $val>0) ||
									(!in_array($i, $nextChildren['values']['level_parent']) && $nextChildren['values']['logic']=='Not' && $val>0)
								){
									$this->conditionParameters['levelParent'][]=$val;
								}
								$i++;
							}
							$condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $refRarentLevel, $nextChildren['values']['level_parent']);
						break;
                        case 'ranksParentRef':
                            $condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $user['rank'], $nextChildren['values']['ranks_parent']);
                        break;
					}
				}
				if($condition=='AND' && $condStatus==false){
					return false;
				}elseif($condition=='ANDNOT' && $condStatus==true){
					return false;
				}elseif($condition=='OR' && $condStatus==true){
					return true;
				}elseif($condition=='ORNOT' && $condStatus==false){
					return true;
				}				
			}
			if($condition=='AND'){
				return true;
			}
		}
		return false;
	}

}

?>