<?
namespace Commerce\Loyaltyprogram\Profiles;
use \Bitrix\Main\Application,
	Bitrix\Main\Localization\Loc,
	\Commerce\Loyaltyprogram;
/**
* type register user
*/
class Registration extends Profile implements Profileinterface{
	
	function __construct(){
		parent::__construct();
		$this->profileSetting['type']='Registration';
	}
	
	public function getParametersMain(){
		foreach(['type', 'profileActive', 'profileName', 'profileSort'] as $nextRow){
			$this->drawRow($nextRow);
		}
	}
	
	protected function checkSMSList(){
		$this->profileSetting['sms_settings']=empty($this->profileSetting['sms_settings'])?[]:$this->profileSetting['sms_settings'];
		if(empty($this->profileSetting['sms_settings']['COMMERCE_LOYAL_REGISTRATION_SMS'])){
			$this->profileSetting['sms_settings']['COMMERCE_LOYAL_REGISTRATION_SMS']=[
				'userTemplate'=>0,
				'refTemplate'=>0
			];
		}
		$this->checkSMSMain();
	}
	
	protected function SMSType($type){
		$SMSType = [
			'COMMERCE_LOYAL_REGISTRATION_SMS'=>[
				'LID'=>LANGUAGE_ID,
				'EVENT_NAME'=>'COMMERCE_LOYAL_REGISTRATION_SMS',
				'EVENT_TYPE'  => 'sms',
				'NAME'=>Loc::getMessage("commerce.loyaltyprogram_SMS_REGISTRATION"),
				'DESCRIPTION'=>Loc::getMessage("commerce.loyaltyprogram_SMS_REGISTRATION_DESC")
			]
		];
		return $SMSType[$type];
	}
	
	protected function SMSTemplates($key){
		$sites=array_keys($this->globalSettings->getSites());
		$smsTemplates = [
			'userTemplate'=>[
				'EVENT_NAME'=>'COMMERCE_LOYAL_REGISTRATION_SMS',
				"ACTIVE" => false,
				"SENDER" => "#DEFAULT_SENDER#",
				"RECEIVER" => "#USER_PHONE#",
				"MESSAGE" => GetMessage("commerce.loyaltyprogram_SMS_REGISTRATION_USERBONUSADD")
			],
			'refTemplate'=>[
				'EVENT_NAME'=>'COMMERCE_LOYAL_REGISTRATION_SMS',
				"ACTIVE" => false,
				"SENDER" => "#DEFAULT_SENDER#",
				"RECEIVER" => "#USER_PHONE#",
				"MESSAGE" => GetMessage("commerce.loyaltyprogram_SMS_REGISTRATION_REFBONUSADD")
			]
		];
		return $smsTemplates[$key];
	}
	
	private function getRefSMS($userId, $bonus){
		$SMSSettings=$this->profileSetting['sms_settings'];
		if(!empty($SMSSettings['COMMERCE_LOYAL_REGISTRATION_SMS']['refTemplate'])){
			$userData=Loyaltyprogram\Tools::getUserData($userId);
			if(!empty($userData['PERSONAL_PHONE'])){
				$tmpPhone=$userData['PERSONAL_PHONE'];
			}elseif(!empty($userData['PERSONAL_MOBILE'])){
				$tmpPhone=$userData['PERSONAL_PHONE'];
			}
			if(!empty($tmpPhone)){
				$tmpPhone=\Bitrix\Main\UserPhoneAuthTable::normalizePhoneNumber($tmpPhone);
				return [
					"EVENT_NAME" => "COMMERCE_LOYAL_REGISTRATION_SMS",
					"MESSAGE_ID" => $SMSSettings['COMMERCE_LOYAL_REGISTRATION_SMS']['refTemplate'],
					"LID" => $userData['LID'],
					"C_FIELDS" => [
						"USER_PHONE" => $tmpPhone,
						"BONUS"=>strval($bonus)
					]
				];
			}
		}
		return false;
	}
	
	private function getUserSMS($userId, $bonus){
		$SMSSettings=$this->profileSetting['sms_settings'];
		if(!empty($SMSSettings['COMMERCE_LOYAL_REGISTRATION_SMS']['userTemplate'])){
			$userData=Loyaltyprogram\Tools::getUserData($userId);
			if(!empty($userData['PERSONAL_PHONE'])){
				$tmpPhone=$userData['PERSONAL_PHONE'];
			}elseif(!empty($userData['PERSONAL_MOBILE'])){
				$tmpPhone=$userData['PERSONAL_PHONE'];
			}
			if(!empty($tmpPhone)){
				$tmpPhone=\Bitrix\Main\UserPhoneAuthTable::normalizePhoneNumber($tmpPhone);
				return [
					"EVENT_NAME" => "COMMERCE_LOYAL_REGISTRATION_SMS",
					"MESSAGE_ID" => $SMSSettings['COMMERCE_LOYAL_REGISTRATION_SMS']['userTemplate'],
					"LID" => $userData['LID'],
					"C_FIELDS" => [
						"USER_PHONE" => $tmpPhone,
						"BONUS"=>strval($bonus)
					]
				];
			}
		}
		return false;
	}
	
	protected function checkEmailList(){
		$this->profileSetting['email_settings']=empty($this->profileSetting['email_settings'])?[]:$this->profileSetting['email_settings'];
		if(empty($this->profileSetting['email_settings']['COMMERCE_LOYAL_REGISTRATION'])){
			$this->profileSetting['email_settings']['COMMERCE_LOYAL_REGISTRATION']=[
				'userTemplate'=>0,
				'refTemplate'=>0
			];
		}
		$this->checkEmailMain();
	}
	
	protected function mailType($type){
		$mailType = [
			'COMMERCE_LOYAL_REGISTRATION'=>[
				'LID'=>LANGUAGE_ID,
				'EVENT_NAME'=>'COMMERCE_LOYAL_REGISTRATION',
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
				'EVENT_NAME'=>'COMMERCE_LOYAL_REGISTRATION',
				'LID'=>$sites[0],
				'ACTIVE'=>'Y',
				'EMAIL_FROM'=>'#DEFAULT_EMAIL_FROM#',
				'EMAIL_TO'=>'#EMAIL_TO#',
				'SUBJECT'=>'#SITE_NAME#: '.Loc::getMessage("commerce.loyaltyprogram_EVENT_REGISTER_USERREGISTER"),
				'MESSAGE'=>$this->globalSettings->getEmailTemplate('registration_user'),
				'BODY_TYPE'=>'html'
			],
			'refTemplate'=>[
				'EVENT_NAME'=>'COMMERCE_LOYAL_REGISTRATION',
				'LID'=>$sites[0],
				'ACTIVE'=>'Y',
				'EMAIL_FROM'=>'#DEFAULT_EMAIL_FROM#',
				'EMAIL_TO'=>'#EMAIL_TO#',
				'SUBJECT'=>'#SITE_NAME#: '.Loc::getMessage("commerce.loyaltyprogram_EVENT_REGISTER_REFREGISTER"),
				'MESSAGE'=>$this->globalSettings->getEmailTemplate('registration_ref'),
				'BODY_TYPE'=>'html'
			]
		];
		return $mailTemplates[$key];
	}
	
	public function setBonus($userId){
		
		if(!empty($this->profileSetting['settings']["condition"]["children"])){
			global $DB;
			$options=$this->globalSettings->getOptions();
			$this->userId=$userId;
			foreach($this->profileSetting['settings']["condition"]["children"] as $nextO){
				if(!isset($nextO['children'])){
					continue;
				}else{
					$this->conditionParameters=[];//clear condition tmp data for next iteration
					$status=$this->checkConditionGroup($nextO);
					if($status){
						if($nextO['controlId']=='registerbyRef'){
							if(!empty($nextO['values']['bonus'])){
								$startBonus=$currentTime=time();
								$endBonus='null';
								if(!empty($nextO['values']['through_time']) && $nextO['values']['through_time']=='N'){
									$nextO['values']['bonus_delay']=0;
								}
								if(!empty($nextO['values']['bonus_delay']) && $nextO['values']['bonus_delay']>0){
									$bonusDelay=(!empty($nextO['values']['bonus_delay']))?$nextO['values']['bonus_delay']:0;
									$bonusType=(!empty($nextO['values']['bonus_delay_type']))?$nextO['values']['bonus_delay_type']:'day';
									if($bonusType=='month'){
										$startBonus=strtotime( '+'.$bonusDelay.' month' , $startBonus);
									}elseif($bonusType=='week'){
										$startBonus=strtotime( '+'.$bonusDelay.' week' , $startBonus);
									}else{
										$startBonus+=$bonusDelay*$this->timePart[$bonusType];
									}
								}
								if(!empty($nextO['values']['limit_time']) && $nextO['values']['limit_time']=='N'){
									$nextO['values']['bonus_live']=0;
								}
								if(!empty($nextO['values']['bonus_live']) && $nextO['values']['bonus_live']>0){
									$bonusLive=(!empty($nextO['values']['bonus_live']))?$nextO['values']['bonus_live']:0;
									$bonusType=(!empty($nextO['values']['bonus_live_type']))?$nextO['values']['bonus_live_type']:'day';
									if($bonusType=='month'){
										$endBonus=strtotime( '+'.$bonusLive.' month' , $startBonus);
									}elseif($bonusType=='week'){
										$endBonus=strtotime( '+'.$bonusLive.' week' , $startBonus);
									}else{
										$endBonus=$startBonus+$bonusLive*$this->timePart[$bonusType];
									}
									$endBonus='FROM_UNIXTIME('.$endBonus.')';
								}
								
								$mailTmplt=$this->getUserEmail($userId, $nextO['values']['bonus']);
								$mailTmplt=($mailTmplt!=false)?serialize($mailTmplt):'';
								
								$SMSTmplt=$this->getUserSMS($userId, $nextO['values']['bonus']);
								$SMSTmplt=($SMSTmplt!=false)?serialize($SMSTmplt):'';
								
								$fields=[
									'bonus_start'=>$nextO['values']['bonus'],
									'bonus'=>$nextO['values']['bonus'],
									'user_id'=>$userId,
									'currency'=>'"'.$options['currency'].'"',
									'profile_type'=>'"'.$this->profileSetting['type'].'"',
									'profile_id'=>$this->profileSetting['id'],
									'status'=>'"inactive"',
									'date_add'=>'FROM_UNIXTIME('.$startBonus.')',
									'date_remove'=>$endBonus, 
									'add_comment'=>'"'.$DB->ForSql(Loc::getMessage("commerce.loyaltyprogram_BONUS_USER_REGISTRATION", ["#NUM#"=>$nextO['values']['bonus']])).'"',
									'email'=>"'".$mailTmplt."'",
									'sms'=>"'".$SMSTmplt."'"
								];
								//action_id
								if(!empty($nextO['values']['number_action'])){
									$fields['action_id']=$nextO['values']['number_action'];
								}
								if(!$this->isAlreadyRow($fields)){
									$idIns=$DB->Insert($this->globalSettings->getTableBonusList(), $fields, $err_mess.__LINE__);
									if($startBonus==$currentTime){
										Loyaltyprogram\Eventmanager::manageBonuses($idIns);
									}
								}
							}
						}elseif($nextO['controlId']=='registerbyParentRef'){
							$this->setReferalBonuses($userId, $nextO);
						}
					}
				}
			}
		}
		return true;
	}
	
	private function setReferalBonuses($userId, $nextO=[]){
		
		/*if(!empty($this->conditionParameters['groupParentRef']) && !empty($this->conditionParameters['levelParent'])){
			$newRew=array_intersect($this->conditionParameters['groupParentRef'], $this->conditionParameters['levelParent']);
		}elseif(!empty($this->conditionParameters['groupParentRef'])){
			$newRew=$this->conditionParameters['groupParentRef'];
		}elseif(!empty($this->conditionParameters['levelParent'])){
			$newRew=$this->conditionParameters['levelParent'];
		}*/
        foreach(['ranksParentRef', 'groupParentRef', 'levelParent'] as $nextParameters){
            if(!empty($this->conditionParameters[$nextParameters])){
                if(empty($newRew)){
                    $newRew=$this->conditionParameters[$nextParameters];
                }else{
                    $newRew=array_intersect($newRew, $this->conditionParameters[$nextParameters]);
                    sort($newRew);
                }
            }
        }
		
		$newRew=(isset($newRew))?array_unique($newRew):$this->getChainReferal($userId);
		
		if(count($newRew)>0 && $nextO['values']['bonus']>0){
			global $DB;
			$options=$this->globalSettings->getOptions();
			
			$startBonus=$currentTime=time();
			$endBonus='null';
			if(!empty($nextO['values']['through_time']) && $nextO['values']['through_time']=='N'){
				$nextO['values']['bonus_delay']=0;
			}
			if(!empty($nextO['values']['bonus_delay']) && $nextO['values']['bonus_delay']>0){
				$bonusDelay=(!empty($nextO['values']['bonus_delay']))?$nextO['values']['bonus_delay']:0;
				$bonusType=(!empty($nextO['values']['bonus_delay_type']))?$nextO['values']['bonus_delay_type']:'day';
				if($bonusType=='month'){
					$startBonus=strtotime( '+'.$bonusDelay.' month' , $startBonus);
				}elseif($bonusType=='week'){
					$startBonus=strtotime( '+'.$bonusDelay.' week' , $startBonus);
				}else{
					$startBonus+=$bonusDelay*$this->timePart[$bonusType];
				}
			}
			if(!empty($nextO['values']['limit_time']) && $nextO['values']['limit_time']=='N'){
				$nextO['values']['bonus_live']=0;
			}
			if(!empty($nextO['values']['bonus_live']) && $nextO['values']['bonus_live']>0){
				$bonusLive=(!empty($nextO['values']['bonus_live']))?$nextO['values']['bonus_live']:0;
				$bonusType=(!empty($nextO['values']['bonus_live_type']))?$nextO['values']['bonus_live_type']:'day';
				if($bonusType=='month'){
					$endBonus=strtotime( '+'.$bonusLive.' month' , $startBonus);
				}elseif($bonusType=='week'){
					$endBonus=strtotime( '+'.$bonusLive.' week' , $startBonus);
				}else{
					$endBonus=$startBonus+$bonusLive*$this->timePart[$bonusType];
				}
				$endBonus='FROM_UNIXTIME('.$endBonus.')';
			}
			//$i=1;
			foreach($newRew as $nextRew){
				//if(in_array($i, $newRew)){
					$coeff=$this->getRankCoeff($nextRew);
					$nextO['values']['bonus']=round($coeff*$nextO['values']['bonus']);
					
					$mailTmplt=$this->getRefEmail($nextRew, $nextO['values']['bonus']);
					$mailTmplt=($mailTmplt!=false)?serialize($mailTmplt):'';
					
					$SMSTmplt=$this->getRefSMS($nextRew, $nextO['values']['bonus']);
					$SMSTmplt=($SMSTmplt!=false)?serialize($SMSTmplt):'';
					
					$fields=[
						'bonus_start'=>$nextO['values']['bonus'],
						'bonus'=>$nextO['values']['bonus'],
						'user_id'=>$nextRew,
						'user_bonus'=>$userId,
						'currency'=>'"'.$options['currency'].'"',
						'profile_type'=>'"'.$this->profileSetting['type'].'"',
						'profile_id'=>$this->profileSetting['id'],
						'status'=>'"inactive"',
						'date_add'=>'FROM_UNIXTIME('.$startBonus.')',
						'date_remove'=>$endBonus,
						'add_comment'=>'"'.$DB->ForSql(Loc::getMessage("commerce.loyaltyprogram_BONUS_REF_REGISTRATION", ["#NUM#"=>$nextO['values']['bonus']])).'"',
						'email'=>"'".$mailTmplt."'",
						'sms'=>"'".$SMSTmplt."'"
					];
					//action_id
					if(!empty($nextO['values']['number_action'])){
						$fields['action_id']=$nextO['values']['number_action'];
					}
					if(!$this->isAlreadyRow($fields)){
						$idIns=$DB->Insert($this->globalSettings->getTableBonusList(), $fields, $err_mess.__LINE__);
						if($startBonus==$currentTime){
							Loyaltyprogram\Eventmanager::manageBonuses($idIns);
						}
					}
				//}
				//$i++;
			}
		}
	}
	
	private function getUserEmail($userId, $bonus){
		if(!empty($userId)){
			global $DB;
			$emailSettings=$this->profileSetting['email_settings'];
			if(!empty($emailSettings['COMMERCE_LOYAL_REGISTRATION']['userTemplate'])){
				$rsEM = \CEventMessage::GetByID($emailSettings['COMMERCE_LOYAL_REGISTRATION']['userTemplate']);
				if($arEM = $rsEM->Fetch()){
					$sites=array_keys($this->globalSettings->getSites());				
					$results=$DB->Query('select * from b_user where id='.$userId);
					$arUser = $results->Fetch();
					
					if(!empty($arUser['EMAIL'])){
						return [
							"EVENT_NAME" => "COMMERCE_LOYAL_REGISTRATION",
							"MESSAGE_ID" => $emailSettings['COMMERCE_LOYAL_REGISTRATION']['userTemplate'],
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
	
	private function getRefEmail($userId, $bonus){
		if(!empty($userId)){
			global $DB;
			$emailSettings=$this->profileSetting['email_settings'];
			if(!empty($emailSettings['COMMERCE_LOYAL_REGISTRATION']['refTemplate'])){
				$results=$DB->Query('select * from b_user where id='.$userId);
				$arUser = $results->Fetch();
				
				if(!empty($arUser['EMAIL'])){
					$sites=array_keys($this->globalSettings->getSites());
					if(!isset($this->refTemplate)){
						$rsEM = \CEventMessage::GetByID($emailSettings['COMMERCE_LOYAL_REGISTRATION']['refTemplate']);
						if($arEM = $rsEM->Fetch()){
							$this->refTemplate=$arEM;
						}else{
							$this->refTemplate=false;
						}
					}
					if($this->refTemplate!==false){
						return [
							"EVENT_NAME" => "COMMERCE_LOYAL_REGISTRATION",
							"MESSAGE_ID" => $emailSettings['COMMERCE_LOYAL_REGISTRATION']['refTemplate'],
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
		//$saveFields['site']='"'.$this->clearSites($params['site']).'"';
		$saveFields['name']='"'.(empty($params['profile_name'])?'noname':$params['profile_name']).'"';
		$saveFields['type']='"'.$this->profileSetting['type'].'"';
		$saveFields['date_setting']='NOW()';
		$tmpSettings=[];
		$condition=$this->getTreeFromRequest();
		if(count($condition)>0){
			$tmpSettings['condition']=$condition;
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
					"bonus"=>"1000",
					"bonus_delay"=>"2",
					"bonus_delay_type"=>"day",
					"bonus_live"=>"4",
					"bonus_live_type"=>"month",
					"All"=>"OR",
					"True"=>"True"
				],
				"children"=>[[
					"id"=>"0",
					"controlId"=>"groupUsers",
					"values"=>[
						"logic"=>"Equal",
						"users"=>["2"]
					]
				]]
			],
			[
				"id"=>"1",
				"controlId"=>"registerbyParentRef",
				"values"=>[
					"bonus"=>"1000",
					"bonus_delay"=>"2",
					"bonus_delay_type"=>"day",
					"bonus_live"=>"0",
					"bonus_live_type"=>"day",
					"All"=>"AND",
					"True"=>"True"
				],
				"children"=>[[
					"id"=>"0",
					"controlId"=>"levelParent",
					"values"=>[
						"logic"=>"Equal",
						"level_parent"=>["1"]
					]
				]]
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
		
		$basketRules=$this->globalSettings->getBasketRules();
		
		$optns=$this->globalSettings->getOptions();
		$refRarentArr=[];
		for($i=1; $i<=$optns['ref_level']; $i++){
			$refRarentArr[$i]=Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_nLevel", ['#N#'=>$i]);
		}

		$mainParams=[[
            'controlId'=> 'typeLink',
            'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_typeLink_hint"),
            'group'=> false,
            'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_typeLink"),
            'showIn'=> ['registerbyRef', 'registerbyRefSubGrp'],
            'control'=> [[
                'id'=> 'prefix',
                'type'=> 'prefix',
                'text'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_typeLinkEqual")
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
                'values'=> Loyaltyprogram\Tools::getTypeLinkList(['manual', 'import']),
                'id'=> 'type_link',
                'name'=> 'type_link',
                'show_value'=>'Y',
                'first_option'=> '...',
                'defaultText'=> '...',
                'defaultValue'=> ''
            ]]
        ], [
                'controlId'=> 'typeLinkParent',
                'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_typeLinkEqual_hint"),
                'group'=> false,
                'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_typeLink"),
                'showIn'=> ['registerbyParentRef', 'registerbyParentRefSubGrp'],
                'control'=> [[
                    'id'=> 'prefix',
                    'type'=> 'prefix',
                    'text'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_typeLinkEqual")
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
                    'values'=> Loyaltyprogram\Tools::getTypeLinkList(['simple', 'manual', 'import']),
                    'id'=> 'type_link_parent',
                    'name'=> 'type_link_parent',
                    'show_value'=>'Y',
                    'first_option'=> '...',
                    'defaultText'=> '...',
                    'defaultValue'=> ''
                ]]
            ],[
                'controlId'=> 'sites',
                'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_sites_hint"),
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
                    'first_option'=> '...',
                    'defaultText'=> '...',
                    'defaultValue'=> ''
                ]]
            ], [
                'controlId'=> 'groupUsers',
                'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_birthday_GroupRef"),
                'group'=> false,
                'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_Group"),
                'showIn'=> ['registerbyRef', 'registerbyRefSubGrp', 'registerbyParentRef', 'registerbyParentRefSubGrp'],
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
                    'first_option'=> '...',
                    'defaultText'=> '...',
                    'defaultValue'=> ''
                ]]
            ],[
            'controlId'=> 'groupParentRef',
            'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_ordering_GroupParentRef"),
            'group'=> false,
            'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_GroupParentRef"),
            'showIn'=> ['registerbyParentRef', 'registerbyParentRefSubGrp'],
            'control'=> [[
                'id'=> 'prefix',
                'type'=> 'prefix',
                'text'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_GroupParentRef")
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
                'id'=> 'group_parent',
                'name'=> 'group_parent',
                'show_value'=>'Y',
                'first_option'=> '...',
                'defaultText'=> '...',
                'defaultValue'=> ''
            ]]
        ],[
                'controlId'=> 'levelParent',
                'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_levelParentRef_hint"),
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
                'showIn'=> ['registerbyRef', 'registerbyRefSubGrp', 'registerbyParentRef', 'registerbyParentRefSubGrp'],
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
						'defaultValue'=>'1000'
					],
					Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_bonusbyRegister"),
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
						'defaultValue'=>'1000'
					],
					Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_BonusParentRefByRegRef"),
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
					'controlId'=> 'discount',
					'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_DISCOUNT_hint"),
					'group'=> false,
					'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_DISCOUNT"),
					'showIn'=> ['registerbyRef', 'registerbyRefSubGrp', 'registerbyParentRef', 'registerbyParentRefSubGrp'],
					'control'=> [[
						'id'=> 'prefix',
						'type'=> 'prefix',
						'text'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_DISCOUNT")
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
						'values'=> $basketRules,
						'id'=> 'discount',
						'name'=> 'discount',
						'first_option'=> '...',
						'defaultText'=> '...',
						'defaultValue'=> ''
					]]
				]]
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
	
	private function checkConditionGroup($group){
		if(empty($group['children'])){
			//set bonus
			return true;
		}else{
			$optns=$this->globalSettings->getOptions();
			$condition='AND';
			if($group['values']['All']=='AND' && $group['values']['True']=='False'){$condition='ANDNOT';}
			elseif($group['values']['All']=='OR' && $group['values']['True']=='True'){$condition='OR';}
			elseif($group['values']['All']=='OR' && $group['values']['True']=='False'){$condition='ORNOT';}
			foreach($group['children'] as $nextChildren){
				if(!empty($nextChildren['children'])){
					$condStatus=$this->checkConditionGroup($nextChildren);
				}else{
					$condStatus=false;
					switch ($nextChildren['controlId']){
						case 'sites':
							$condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], SITE_ID, $nextChildren['values']['sites']);
						break;
						case 'groupUsers':
							$parentRefCroup=\CUser::GetUserGroup($this->userId);
							$condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $parentRefCroup, $nextChildren['values']['users']);
						break;
                        case 'ranksUser':
                            $rank=$this->ranksObject->getRankUser($this->userId);
                            $condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $rank, $nextChildren['values']['ranks']);
                        break;
						case 'typeLink':
							$condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], Loyaltyprogram\Tools::getIdTypeLinkUser($this->userId), $nextChildren['values']['type_link']);
						break;
						case 'groupParentRef':
							$rewards=$this->getChainReferal($this->userId);
							$settingsGroup= $nextChildren['values']['group_parent'];
							$refGroups=[];
							$this->conditionParameters['groupParentRef']=[];
							if(count($rewards)>0){
								foreach($rewards as $valRew){
									$parentRefCroup=\CUser::GetUserGroup($valRew);
									$refGroups=array_merge($refGroups, $parentRefCroup);
									if(
										(count(array_intersect($parentRefCroup, $settingsGroup))>0 && $nextChildren['values']['logic']=='Equal') ||
										(count(array_intersect($parentRefCroup, $settingsGroup))==0 && $nextChildren['values']['logic']=='Not')
									){
										$this->conditionParameters['groupParentRef'][]=$valRew;//id referral users
									}
								}
							}else{
								$refGroups=[0];
							}
							$condStatus=$this->checkConditionChildren($nextChildren['values']['logic'],  array_unique($refGroups), $nextChildren['values']['group_parent']);
						break;
                        case 'ranksParentRef':
                            $rewards=$this->getChainReferal($this->userId);
                            $settingsGroup= $nextChildren['values']['ranks_parent'];
                            $ranksGroup = [];
                            $this->conditionParameters['ranksParentRef']=[];
                            if(count($rewards)>0){
                                foreach($rewards as $valRew){
                                    $parentRefRank=$this->ranksObject->getRankUser($valRew);
                                    $ranksGroup[]=$parentRefRank;
                                    if(
                                        (in_array($parentRefRank, $settingsGroup) && $nextChildren['values']['logic']=='Equal') ||
                                        (!in_array($parentRefRank, $settingsGroup) && $nextChildren['values']['logic']=='Not')
                                    ){
                                        $this->conditionParameters['ranksParentRef'][]=$valRew;//id referral users
                                    }
                                }
                            }else{
                                $ranksGroup=[0];
                            }
                            $condStatus=$this->checkConditionChildren($nextChildren['values']['logic'],  array_unique($ranksGroup), $nextChildren['values']['ranks_parent']);
                        break;
						case 'typeLinkParent':
							$condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], Loyaltyprogram\Tools::getIdTypeLinkUser($this->userId), $nextChildren['values']['type_link_parent']);
						break;
						case 'levelParent':
							$refRarentArr=[];
							$rewards=$this->getChainReferal($this->userId);
							$i=1;
							foreach($rewards as $key=>$val){
								$refRarentArr[]=$i;
								if(
									(in_array($i, $nextChildren['values']['level_parent']) && $nextChildren['values']['logic']=='Equal') ||
									(!in_array($i, $nextChildren['values']['level_parent']) && $nextChildren['values']['logic']=='Not')
								){
									$this->conditionParameters['levelParent'][]=$val;
								}
								$i++;
							}
							$condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $refRarentArr, $nextChildren['values']['level_parent']);
						break;
						case 'discount':
							if(!empty($_SESSION['sw24_register_discount'])){
								$condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $_SESSION['sw24_register_discount'], $nextChildren['values']['discount']);
							}else{
								$condStatus=false;
							}
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