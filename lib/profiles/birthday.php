<?
namespace Commerce\Loyaltyprogram\Profiles;
use \Bitrix\Main\Application,
	Bitrix\Main\Localization\Loc,
	\Commerce\Loyaltyprogram;
/**
* type dirthday 
*/
class Birthday extends Profile implements Profileinterface{
	
	function __construct(){
		parent::__construct();
		$this->profileSetting['type']='Birthday';
	}
	
	public function getParametersMain(){
		foreach(['type', 'profileActive', 'profileName', 'profileSort', 'propBirthday'] as $nextRow){
			$this->drawRow($nextRow);
		}
	}
	
	protected function checkSMSList(){
		$this->profileSetting['sms_settings']=empty($this->profileSetting['sms_settings'])?[]:$this->profileSetting['sms_settings'];
		if(empty($this->profileSetting['sms_settings']['COMMERCE_LOYAL_BIRTHDAY_SMS'])){
			$this->profileSetting['sms_settings']['COMMERCE_LOYAL_BIRTHDAY_SMS']=[
				'userTemplate'=>0,
				'refTemplate'=>0
			];
		}
		$this->checkSMSMain();
	}
	
	protected function SMSType($type){
		$SMSType = [
			'COMMERCE_LOYAL_BIRTHDAY_SMS'=>[
				'LID'=>LANGUAGE_ID,
				'EVENT_NAME'=>'COMMERCE_LOYAL_BIRTHDAY_SMS',
				'EVENT_TYPE'  => 'sms',
				'NAME'=>Loc::getMessage("commerce.loyaltyprogram_SMS_BIRTHDAY"),
				'DESCRIPTION'=>Loc::getMessage("commerce.loyaltyprogram_SMS_BIRTHDAY_DESC")
			]
		];
		return $SMSType[$type];
	}
	
	protected function SMSTemplates($key){
		$sites=array_keys($this->globalSettings->getSites());
		$smsTemplates = [
			'userTemplate'=>[
				'EVENT_NAME'=>'COMMERCE_LOYAL_BIRTHDAY_SMS',
				"ACTIVE" => false,
				"SENDER" => "#DEFAULT_SENDER#",
				"RECEIVER" => "#USER_PHONE#",
				"MESSAGE" => GetMessage("commerce.loyaltyprogram_SMS_BIRTHDAY_USERBONUSADD")
			],
			'refTemplate'=>[
				'EVENT_NAME'=>'COMMERCE_LOYAL_BIRTHDAY_SMS',
				"ACTIVE" => false,
				"SENDER" => "#DEFAULT_SENDER#",
				"RECEIVER" => "#USER_PHONE#",
				"MESSAGE" => GetMessage("commerce.loyaltyprogram_SMS_BIRTHDAY_REFBONUSADD")
			]
		];
		return $smsTemplates[$key];
	}
	
	private function getRefSMS($userId, $bonus){
		$SMSSettings=$this->profileSetting['sms_settings'];
		if(!empty($SMSSettings['COMMERCE_LOYAL_BIRTHDAY_SMS']['refTemplate'])){
			$userData=Loyaltyprogram\Tools::getUserData($userId);
			if(!empty($userData['PERSONAL_PHONE'])){
				$tmpPhone=$userData['PERSONAL_PHONE'];
			}elseif(!empty($userData['PERSONAL_MOBILE'])){
				$tmpPhone=$userData['PERSONAL_PHONE'];
			}
			if(!empty($tmpPhone)){
				$tmpPhone=\Bitrix\Main\UserPhoneAuthTable::normalizePhoneNumber($tmpPhone);
				return [
					"EVENT_NAME" => "COMMERCE_LOYAL_BIRTHDAY_SMS",
					"MESSAGE_ID" => $SMSSettings['COMMERCE_LOYAL_BIRTHDAY_SMS']['refTemplate'],
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
		if(!empty($SMSSettings['COMMERCE_LOYAL_BIRTHDAY_SMS']['userTemplate'])){
			$userData=Loyaltyprogram\Tools::getUserData($userId);
			if(!empty($userData['PERSONAL_PHONE'])){
				$tmpPhone=$userData['PERSONAL_PHONE'];
			}elseif(!empty($userData['PERSONAL_MOBILE'])){
				$tmpPhone=$userData['PERSONAL_PHONE'];
			}
			if(!empty($tmpPhone)){
				$tmpPhone=\Bitrix\Main\UserPhoneAuthTable::normalizePhoneNumber($tmpPhone);
				return [
					"EVENT_NAME" => "COMMERCE_LOYAL_BIRTHDAY_SMS",
					"MESSAGE_ID" => $SMSSettings['COMMERCE_LOYAL_BIRTHDAY_SMS']['userTemplate'],
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
				'MESSAGE'=>$this->globalSettings->getEmailTemplate('birthday_ref'),
				'BODY_TYPE'=>'html'
			]
		];
		return $mailTemplates[$key];
	}
	
	private function isAcc(){
		if(!isset($this->acc)){
			global $DB;
			$this->acc=[];
			$rsUsers=$DB->Query('select * from '.$this->globalSettings->getTableBonusList().'
				where profile_type="Birthday"
				and profile_id='.$this->profileSetting['id'].'
				and user_bonus=0
				and YEAR(date_add)=YEAR(CURRENT_TIMESTAMP);');
			while($arUser = $rsUsers->Fetch()){
				$this->acc[]=$arUser['user_id'];
			}
		}
		return $this->acc;
	}
	
	/**
	* if a variable userId is set - then the bonuses will be added regardless of the birthday
	*/
	public function setBonus(){
		if(!empty($this->profileSetting['settings']["condition"]["children"])){
			//select
			global $DB, $USER;
			$propBirthDays=(empty($this->profileSetting['settings']["propbirthday"]))?'PERSONAL_BIRTHDAY':$this->profileSetting['settings']["propbirthday"];
			
			if($propBirthDays=='PERSONAL_BIRTHDAY'){
				$query='select 
						b_user.id as id,
						b_user.name as name,
						b_user.lid as site,
						b_user.email as email,
						b_user.PERSONAL_BIRTHDAY as birthday,
						GROUP_CONCAT(b_user_group.group_id) as groups
						from b_user left join b_user_group on (b_user_group.user_id=b_user.id)
						where b_user.PERSONAL_BIRTHDAY is not null
						group by b_user.id;';
			}else{
				$query='select 
						b_user.id as id,
						b_user.name as name,
						b_user.email as email,
						b_user.lid as site,
						b_uts_user.'.$propBirthDays.' as birthday,
						GROUP_CONCAT(b_user_group.group_id) as groups
					from b_user left join b_user_group on (b_user_group.user_id=b_user.id)
					left join b_uts_user on (b_uts_user.VALUE_ID=b_user.id)
					where b_uts_user.'.$propBirthDays.' is not null
					group by b_user.id;';
			}
			$rsUsers=$DB->Query($query);
			$tmpUsers=[];
			$this->isAcc();
            $ranks=$this->ranksObject->getRankUsers();
			while ($arUser = $rsUsers->Fetch()) {
				if(in_array($arUser['id'], $this->acc)){
					continue;
				}
				if(empty($arUser['birthday'])){
					continue;
				}
				
				$tm=strtotime($arUser['birthday']);
		
				if($tm===false){
					continue;
				}
                $arUser['rank']=empty($ranks[$arUser['id']])?0:$ranks[$arUser['id']];
				$arUser['birthday_timestamp']=$tm;
				$tmpUsers[$arUser['id']]=$arUser;
			}
			if(count($tmpUsers)>0){
				$options=$this->globalSettings->getOptions();
				foreach($tmpUsers as $nextUser){
					foreach($this->profileSetting['settings']["condition"]["children"] as $nextO){
						if(!isset($nextO['children'])){
							continue;
						}else{
							$this->conditionParameters=[];//clear condition tmp data for next iteration
							$status=$this->checkConditionGroup($nextO, $nextUser);
							$params=$nextO['values'];
							if(!empty($params['bonus']) && (int) $params['bonus']>0){
								if($status){
									if($nextO['controlId']=='registerbyRef'){
			
										$cTime=time();
										if(!empty($params['before_date']) && $params['before_date']=='N'){
											$params['bonus_delay']=0;
										}
										if(!empty($params['bonus_delay']) && $params['bonus_delay']>0){
										    if($params['before_date']=='A'){
                                                $cTime-=$params['bonus_delay']*$this->timePart[$params['bonus_delay_type']];
                                            }else{
											    $cTime+=$params['bonus_delay']*$this->timePart[$params['bonus_delay_type']];
										    }
										}
										
										$datetime1 = new \DateTime();
										$datetime1->setTimestamp($nextUser['birthday_timestamp']);
										$datetime2 = new \DateTime();
										$datetime2->setTimestamp($cTime);
										$isDay=false;
										if($datetime1->format('m')==$datetime2->format('m') && $datetime1->format('d')==$datetime2->format('d')){
											$isDay=true;
										}
										if($isDay){
											$coeff=$this->getRankCoeff($nextUser['id']);
											$params['bonus']=round($coeff*$params['bonus']);
											
											$mailTmplt=$this->getUserEmail($nextUser['id'], $params['bonus']);
											$mailTmplt=($mailTmplt!=false)?serialize($mailTmplt):'';
											
											$SMSTmplt=$this->getUserSMS($nextUser['id'], $params['bonus']);
											$SMSTmplt=($SMSTmplt!=false)?serialize($SMSTmplt):'';
											
											$insertArr=[
												'bonus_start'=>(int) $params['bonus'],
												'bonus'=>(int) $params['bonus'],
												'user_id'=>$nextUser['id'],
												'currency'=>'"'.$options['currency'].'"',
												'profile_type'=>'"'.$this->profileSetting['type'].'"',
												'profile_id'=>$this->profileSetting['id'],
												'status'=>'"inactive"',
												'add_comment'=>'"'.$DB->ForSql(Loc::getMessage("commerce.loyaltyprogram_BONUS_USER_BIRTHDAY", ["#NUM#"=>$params['bonus']])).'"',
												'email'=>"'".$mailTmplt."'",
												'sms'=>"'".$SMSTmplt."'"
											];
											
											//action_id
											if(!empty($params['number_action'])){
												$insertArr['action_id']=$params['number_action'];
											}
											
											$startBonus=time(); $endBonus='null';
											$insertArr['date_add']='FROM_UNIXTIME('.$startBonus.')';
											if(!empty($params['limit_time']) && $params['limit_time']=='N'){
												$params['bonus_live']=0;
											}
											if(!empty($params['bonus_live']) && $params['bonus_live']>0){
												$endBonus=$startBonus+$params['bonus_live']*$this->timePart[$params['bonus_live_type']];
												$insertArr['date_remove']='FROM_UNIXTIME('.$endBonus.')';
											}
											$idIns=$DB->Insert($this->globalSettings->getTableBonusList(), $insertArr, $err_mess.__LINE__);
											Loyaltyprogram\Eventmanager::manageBonuses($idIns);
										}
									}elseif($nextO['controlId']=='registerbyParentRef'){
										$this->setReferalBonuses($nextUser, $nextO);
									}
								}
							}
						}
					}
				}
			}
		}
		return true;
	}
	
	private function getUserEmail($userId, $bonus){
		global $DB;
		$emailSettings=$this->profileSetting['email_settings'];
		if(!empty($emailSettings['COMMERCE_LOYAL_BIRTHDAY']['userTemplate'])){
			$rsEM = \CEventMessage::GetByID($emailSettings['COMMERCE_LOYAL_BIRTHDAY']['userTemplate']);
			if($arEM = $rsEM->Fetch()){
				$sites=array_keys($this->globalSettings->getSites());
				$results=$DB->Query('select * from b_user where id='.$userId);
				$arUser = $results->Fetch();
				if(!empty($arUser['EMAIL'])){
					return [
						"EVENT_NAME" => "COMMERCE_LOYAL_BIRTHDAY",
						"MESSAGE_ID" => $emailSettings['COMMERCE_LOYAL_BIRTHDAY']['userTemplate'],
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
	
	private function setReferalBonuses($nextUser, $nextO){
		
		if(!empty($this->conditionParameters['levelParent'])){
			$newRew=array_unique($this->conditionParameters['levelParent']);
		}else{
			$rewards=$this->getChainReferal($nextUser['id']);
		}
		$newRew=(isset($newRew))?array_unique($newRew):$rewards;
		
		$params=$nextO['values'];
		if((int) $params['bonus']>0 && count($newRew)>0){
			
			$cTime=time();
			if(!empty($params['before_date']) && $params['before_date']=='N'){
				$params['bonus_delay']=0;
			}
			if(!empty($params['bonus_delay']) && $params['bonus_delay']>0){
                if($params['before_date']=='A'){
                    $cTime-=$params['bonus_delay']*$this->timePart[$params['bonus_delay_type']];
                }else{
                    $cTime+=$params['bonus_delay']*$this->timePart[$params['bonus_delay_type']];
                }
			}
			
			$datetime1 = new \DateTime();
			$datetime1->setTimestamp($nextUser['birthday_timestamp']);
			$datetime2 = new \DateTime();
			$datetime2->setTimestamp($cTime);
			$isDay=false;
			if($datetime1->format('m')==$datetime2->format('m') && $datetime1->format('d')==$datetime2->format('d')){
				$isDay=true;
			}
			
			if($isDay){
				$options=$this->globalSettings->getOptions();
				global $DB;
				$insertArr=[
					'bonus_start'=>(int) $params['bonus'],
					'bonus'=>(int) $params['bonus'],
					'currency'=>'"'.$options['currency'].'"',
					'user_bonus'=>$nextUser['id'],
					'profile_type'=>'"'.$this->profileSetting['type'].'"',
					'profile_id'=>$this->profileSetting['id'],
					'status'=>'"inactive"',
					'add_comment'=>'"'.$DB->ForSql(Loc::getMessage("commerce.loyaltyprogram_BONUS_REF_BIRTHDAY", ["#NUM#"=>$params['bonus']])).'"',
				];
				
				//action_id
				if(!empty($params['number_action'])){
					$insertArr['action_id']=$params['number_action'];
				}
				
				$startBonus=$currentTime=time(); $endBonus='null';
				$insertArr['date_add']='FROM_UNIXTIME('.$currentTime.')';
				if(!empty($params['before_date']) && $params['before_date']=='N'){
					$params['bonus_delay']=0;
				}
				if(!empty($params['bonus_delay']) && $params['bonus_delay']>0){
                    if($params['before_date']=='Y') {
                        $startBonus += $params['bonus_delay'] * $this->timePart[$params['bonus_delay_type']];
                        $insertArr['date_add'] = 'FROM_UNIXTIME(' . $startBonus . ')';
                    }
				}
				if(!empty($params['limit_time']) && $params['limit_time']=='N'){
					$params['bonus_live']=0;
				}
				if(!empty($params['bonus_live']) && $params['bonus_live']>0){
					$endBonus=$startBonus+$params['bonus_live']*$this->timePart[$params['bonus_live_type']];
					$insertArr['date_remove']='FROM_UNIXTIME('.$endBonus.')';
				}
				foreach($newRew as $nextRew){
					
					$coeff=$this->getRankCoeff($nextRew);
					$params['bonus']=round($coeff*$params['bonus']);
					
					$insertArr['bonus_start']=$params['bonus'];
					$insertArr['bonus']=$params['bonus'];
					$insertArr['add_comment']='"'.$DB->ForSql(Loc::getMessage("commerce.loyaltyprogram_BONUS_REF_BIRTHDAY", ["#NUM#"=>$params['bonus']])).'"';
				
					$insertArr['user_id']=$nextRew;
					$mailTmplt=$this->getRefEmail($nextRew, $params['bonus']);
					$mailTmplt=($mailTmplt!=false)?serialize($mailTmplt):'';
					$insertArr['email']="'".$mailTmplt."'";
					
					$SMSTmplt=$this->getRefSMS($nextRew, $params['bonus']);
					$SMSTmplt=($SMSTmplt!=false)?serialize($SMSTmplt):'';
					$insertArr['sms']="'".$SMSTmplt."'";
					
					$idIns=$DB->Insert($this->globalSettings->getTableBonusList(), $insertArr, $err_mess.__LINE__);
					if($startBonus==$currentTime){
						Loyaltyprogram\Eventmanager::manageBonuses($idIns);
					}
				}
			}
		}
	}
	
	private function getRefEmail($userId, $bonus){	
		$emailSettings=$this->profileSetting['email_settings'];
		if(!empty($emailSettings['COMMERCE_LOYAL_BIRTHDAY']['refTemplate'])){
			global $DB;
			$results=$DB->Query('select * from b_user where id='.$userId);
			$arUser = $results->Fetch();
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
		//$saveFields['site']='"'.$this->clearSites($params['site']).'"';
		$saveFields['name']='"'.(empty($params['profile_name'])?'noname':$params['profile_name']).'"';
		$saveFields['type']='"'.$this->profileSetting['type'].'"';
		$saveFields['date_setting']='NOW()';
		$tmpSettings=[];
		$tmpSettings['propbirthday']=$params['prop_birthday'];
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
		$this->registerAgent('birthday', 86400);
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
            'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_birthday_sites"),
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
        ],
            [
                'controlId'=> 'age',
                'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_birthday_age"),
                'group'=> false,
                'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_age"),
                'showIn'=> ['registerbyRef', 'registerbyRefSubGrp', 'registerbyParentRef', 'registerbyParentRefSubGrp'],
                'control'=> [[
                    'id'=> 'prefix',
                    'type'=> 'prefix',
                    'text'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_age")
                ], [
                    'id'=> 'logic',
                    'name'=> 'logic',
                    'type'=> 'select',
                    'values'=> [
                        'Equal'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_EQUAL"),
                        'Not'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_NOTEQUAL"),
                        'more'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_MORE"),
                        'less'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_LESS")
                    ],
                    'defaultText'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_EQUAL"),
                    'defaultValue'=> 'Equal'
                ], [
                    'type'=> 'input',
                    'id'=> 'age',
                    'name'=> 'age',
                    'param_id'=>'n',
                    'show_value'=>'Y',
                    'defaultValue'=>'',
                    'defaultText'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_UNLIMITED_AGE"),
                ]]
            ],
            [
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
                    //'defaultText'=> $firstGroupName,
                    //'defaultValue'=> '',
                    'first_option'=> '...',
                    'defaultText'=> '...',
                    'defaultValue'=> ''
                ]]
            ],[
                'controlId'=> 'levelParent',
                'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_birthday_levelParentRef"),
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
						'defaultValue'=>'1000'
					],
					Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_bonusbyBitrhDay"),
					//Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_UNLIMITED_BEFORE"),
					[
						'id'=>'before_date',
						'name'=>'before_date',
						'type'=> 'select',
						'values'=> [
							'Y'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_UNLIMITED_BEFORE"),
							'N'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_UNLIMITED_THROUGH"),
							'A'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_UNLIMITED_AFTER")
						],
						'defaultValue'=> 'N',
					],
					[
						'id'=> 'bonus_delay',
						'name'=> 'bonus_delay',
						'type'=> 'input',
						'show_value'=>'Y',
						'defaultValue'=>'',
						'defaultText'=>0
					],
					[
						'id'=> 'bonus_delay_type',
						'name'=> 'bonus_delay_type',
						'type'=> 'select',
						'values'=> [
							//'hour'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_HOUR"),
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
						'defaultValue'=>'',
						'defaultText'=>0
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
					Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_BonusParentRefByBirthDay"),
					[
						'id'=>'before_date',
						'name'=>'before_date',
						'type'=> 'select',
						'values'=> [
							'Y'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_UNLIMITED_BEFORE"),
							'N'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_UNLIMITED_THROUGH"),
                            'A'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_UNLIMITED_AFTER")
						],
						'defaultValue'=> 'N',
					],
					[
						'id'=> 'bonus_delay',
						'name'=> 'bonus_delay',
						'type'=> 'input',
						'show_value'=>'Y',
						'defaultValue'=>'',
						'defaultText'=>0
					],
					[
						'id'=> 'bonus_delay_type',
						'name'=> 'bonus_delay_type',
						'type'=> 'select',
						'values'=> [
							//'hour'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_HOUR"),
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
						'defaultValue'=>'',
						'defaultText'=>0
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

	private function checkConditionGroup($group, $user){
		if(count($group['children'])==0){
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
					$condStatus=$this->checkConditionGroup($nextChildren, $user);
				}else{
					$condStatus=false;
					switch ($nextChildren['controlId']){
						case 'sites':
							$condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $user['site'], $nextChildren['values']['sites']);
						break;
						case 'age':
							$cTime=time();

							if(!empty($group['values']['before_date']) && $group['values']['before_date']=='N'){
								$group['values']['bonus_delay']=0;
							}

							if(!empty($group['values']['bonus_delay'])){
								$diff=$group['values']['bonus_delay']*$this->timePart[$group['values']['bonus_delay_type']];
                                if($group['values']['before_date']=='A'){
                                    $cTime=$cTime-$diff;
                                }else{
                                    $cTime=$cTime+$diff;
                                }
							}
							$datetime1 = new \DateTime();
							$datetime1->setTimestamp($user['birthday_timestamp']);
							$datetime2 = new \DateTime();
							$datetime2->setTimestamp($cTime);
							$interval = $datetime1->diff($datetime2);
							$condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $interval->format('%Y'), $nextChildren['values']['age']);
						break;
						case 'groupUsers':
							$groups=explode(',',$user['groups']);
							$condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $groups, $nextChildren['values']['users']);
						break;
                        case 'ranksUser':
                            $condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $user['rank'], $nextChildren['values']['ranks']);
                        break;
						case 'levelParent':
							$refRarentLevel=[];
							$rewards=$this->getChainReferal($user['id']);
							$i=1;
							foreach($rewards as $key=>$val){
								$refRarentLevel[]=$i;
								if(
									(in_array($i, $nextChildren['values']['level_parent']) && $nextChildren['values']['logic']=='Equal') ||
									(!in_array($i, $nextChildren['values']['level_parent']) && $nextChildren['values']['logic']=='Not')
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
			return false;
		}
	}

}

?>