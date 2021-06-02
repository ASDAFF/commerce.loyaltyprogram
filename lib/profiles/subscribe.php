<?
namespace Commerce\Loyaltyprogram\Profiles;
use \Bitrix\Main\Application,
	Bitrix\Main\Localization\Loc,
	\Commerce\Loyaltyprogram;
	
class Subscribe extends Profile implements Profileinterface{
	
	function __construct(){
		parent::__construct();
		$this->profileSetting['type']='Subscribe';
	}
	
	public function getParametersMain(){
		foreach(['type', 'profileActive', 'profileName', 'profileSort'] as $nextRow){
			$this->drawRow($nextRow);
		}
	}
	
	protected function checkSMSList(){
		$this->profileSetting['sms_settings']=empty($this->profileSetting['sms_settings'])?[]:$this->profileSetting['sms_settings'];
		if(empty($this->profileSetting['sms_settings']['COMMERCE_LOYAL_SUBSCRIBE_SMS'])){
			$this->profileSetting['sms_settings']['COMMERCE_LOYAL_SUBSCRIBE_SMS']=[
				'userTemplate'=>0,
				'refTemplate'=>0
			];
		}
		$this->checkSMSMain();
	}
	
	protected function SMSType($type){
		$SMSType = [
			'COMMERCE_LOYAL_SUBSCRIBE_SMS'=>[
				'LID'=>LANGUAGE_ID,
				'EVENT_NAME'=>'COMMERCE_LOYAL_SUBSCRIBE_SMS',
				'EVENT_TYPE'  => 'sms',
				'NAME'=>Loc::getMessage("commerce.loyaltyprogram_SMS_SUBSCRIBE"),
				'DESCRIPTION'=>Loc::getMessage("commerce.loyaltyprogram_SMS_SUBSCRIBE_DESC")
			]
		];
		return $SMSType[$type];
	}
	
	protected function SMSTemplates($key){
		$sites=array_keys($this->globalSettings->getSites());
		$smsTemplates = [
			'userTemplate'=>[
				'EVENT_NAME'=>'COMMERCE_LOYAL_SUBSCRIBE_SMS',
				"ACTIVE" => false,
				"SENDER" => "#DEFAULT_SENDER#",
				"RECEIVER" => "#USER_PHONE#",
				"MESSAGE" => GetMessage("commerce.loyaltyprogram_SMS_SUBSCRIBE_USERBONUSADD")
			],
			'refTemplate'=>[
				'EVENT_NAME'=>'COMMERCE_LOYAL_SUBSCRIBE_SMS',
				"ACTIVE" => false,
				"SENDER" => "#DEFAULT_SENDER#",
				"RECEIVER" => "#USER_PHONE#",
				"MESSAGE" => GetMessage("commerce.loyaltyprogram_SMS_SUBSCRIBE_REFBONUSADD")
			]
		];
		return $smsTemplates[$key];
	}
	
	private function getRefSMS($userId, $bonus){
		$SMSSettings=$this->profileSetting['sms_settings'];
		if(!empty($SMSSettings['COMMERCE_LOYAL_SUBSCRIBE_SMS']['refTemplate'])){
			$userData=Loyaltyprogram\Tools::getUserData($userId);
			if(!empty($userData['PERSONAL_PHONE'])){
				$tmpPhone=$userData['PERSONAL_PHONE'];
			}elseif(!empty($userData['PERSONAL_MOBILE'])){
				$tmpPhone=$userData['PERSONAL_PHONE'];
			}
			if(!empty($tmpPhone)){
				$tmpPhone=\Bitrix\Main\UserPhoneAuthTable::normalizePhoneNumber($tmpPhone);
				return [
					"EVENT_NAME" => "COMMERCE_LOYAL_SUBSCRIBE_SMS",
					"MESSAGE_ID" => $SMSSettings['COMMERCE_LOYAL_SUBSCRIBE_SMS']['refTemplate'],
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
		if(!empty($SMSSettings['COMMERCE_LOYAL_SUBSCRIBE_SMS']['userTemplate'])){
			$userData=Loyaltyprogram\Tools::getUserData($userId);
			if(!empty($userData['PERSONAL_PHONE'])){
				$tmpPhone=$userData['PERSONAL_PHONE'];
			}elseif(!empty($userData['PERSONAL_MOBILE'])){
				$tmpPhone=$userData['PERSONAL_PHONE'];
			}
			if(!empty($tmpPhone)){
				$tmpPhone=\Bitrix\Main\UserPhoneAuthTable::normalizePhoneNumber($tmpPhone);
				return [
					"EVENT_NAME" => "COMMERCE_LOYAL_SUBSCRIBE_SMS",
					"MESSAGE_ID" => $SMSSettings['COMMERCE_LOYAL_SUBSCRIBE_SMS']['userTemplate'],
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
		if(empty($this->profileSetting['email_settings']['COMMERCE_LOYAL_SUBSCRIBE'])){
			$this->profileSetting['email_settings']['COMMERCE_LOYAL_SUBSCRIBE']=[
				'userTemplate'=>0,
				'refTemplate'=>0
			];
		}
		$this->checkEmailMain();
	}

	protected function mailType($type){
		$mailType = [
			'COMMERCE_LOYAL_SUBSCRIBE'=>[
				'LID'=>LANGUAGE_ID,
				'EVENT_NAME'=>'COMMERCE_LOYAL_SUBSCRIBE',
				'NAME'=>Loc::getMessage("commerce.loyaltyprogram_EVENT_SUBSCRIBE"),
				'DESCRIPTION'=>'
					#EMAIL_TO# - '.Loc::getMessage("commerce.loyaltyprogram_SUBSCRIBE_EMAIL_TO").'
					#BONUS# - '.Loc::getMessage("commerce.loyaltyprogram_EVENT_SUBSCRIBE_BONUS").'
					#SUBSCRIBE_NAME# - '.Loc::getMessage("commerce.loyaltyprogram_EVENT_SUBSCRIBE_SUBSCRIBENAME").'
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
				'EVENT_NAME'=>'COMMERCE_LOYAL_SUBSCRIBE',
				'LID'=>$sites[0],
				'ACTIVE'=>'Y',
				'EMAIL_FROM'=>'#DEFAULT_EMAIL_FROM#',
				'EMAIL_TO'=>'#EMAIL_TO#',
				'SUBJECT'=>'#SITE_NAME#: '.Loc::getMessage("commerce.loyaltyprogram_EVENT_SUBSCRIBE_USERBONUSADD"),
				'MESSAGE'=>$this->globalSettings->getEmailTemplate('subscribe_user'),
				'BODY_TYPE'=>'html'
			],
			'refTemplate'=>[
				'EVENT_NAME'=>'COMMERCE_LOYAL_SUBSCRIBE',
				'LID'=>$sites[0],
				'ACTIVE'=>'Y',
				'EMAIL_FROM'=>'#DEFAULT_EMAIL_FROM#',
				'EMAIL_TO'=>'#EMAIL_TO#',
				'SUBJECT'=>'#SITE_NAME#: '.Loc::getMessage("commerce.loyaltyprogram_EVENT_SUBSCRIBE_REFBONUSADD"),
				'MESSAGE'=>$this->globalSettings->getEmailTemplate('subscribe_ref'),
				'BODY_TYPE'=>'html'
			]
		];
		return $mailTemplates[$key];
	}

	private function getUserEmail($userId, $bonus, $subscribe){
		global $DB;
		$emailSettings=$this->profileSetting['email_settings'];
		if(!empty($emailSettings['COMMERCE_LOYAL_SUBSCRIBE']['userTemplate'])){
			$rsEM = \CEventMessage::GetByID($emailSettings['COMMERCE_LOYAL_SUBSCRIBE']['userTemplate']);
			if($arEM = $rsEM->Fetch()){
				$sites=array_keys($this->globalSettings->getSites());
				$results=$DB->Query('select * from b_user where id='.$userId);
				$arUser = $results->Fetch();
				if(!empty($arUser['EMAIL'])){
					return [
						"EVENT_NAME" => "COMMERCE_LOYAL_SUBSCRIBE",
						"MESSAGE_ID" => $emailSettings['COMMERCE_LOYAL_SUBSCRIBE']['userTemplate'],
						"LANGUAGE_ID" => LANGUAGE_ID,
						"LID" => $sites[0],
						"C_FIELDS" => [
							"EMAIL_TO" => $arUser['EMAIL'],
							"BONUS"=>$bonus,
							"SUBSCRIBE_NAME"=>$subscribe
						]
					];
				}
			}
		}
		return false;
	}

	private function getRefEmail($userId, $bonus, $subscribe){	
		$emailSettings=$this->profileSetting['email_settings'];
		if(!empty($emailSettings['COMMERCE_LOYAL_SUBSCRIBE']['refTemplate'])){
			global $DB;
			$results=$DB->Query('select * from b_user where id='.$userId);
			$arUser = $results->Fetch();
			if(!empty($arUser['EMAIL'])){
				$sites=array_keys($this->globalSettings->getSites());
				if(!isset($this->refTemplate)){
					$rsEM = \CEventMessage::GetByID($emailSettings['COMMERCE_LOYAL_SUBSCRIBE']['refTemplate']);
					if($arEM = $rsEM->Fetch()){
						$this->refTemplate=$arEM;
					}else{
						$this->refTemplate=false;
					}
				}
				if($this->refTemplate!==false){
					return [
						"EVENT_NAME" => "COMMERCE_LOYAL_SUBSCRIBE",
						"MESSAGE_ID" => $emailSettings['COMMERCE_LOYAL_SUBSCRIBE']['refTemplate'],
						"LANGUAGE_ID" => LANGUAGE_ID,
						"LID" => $sites[0],
						"C_FIELDS" => [
							"EMAIL_TO" => $arUser['EMAIL'],
							"BONUS"=>$bonus,
							"SUBSCRIBE_NAME"=>$subscribe
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
		$saveFields['date_setting']='NOW()';

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
		$this->registerEvent('sender', 'MailingSubscriptionOnAfterAdd', 'subscribeBonusAdd');
		$this->registerEvent('main', '\\Bitrix\\Main\\Mail\\Internal\\Event::onBeforeAdd', 'subscribeFixUserWrite');
		return $id;
	}

	public function setSubscribeUser($userId, $email){
		$rsUsers = \CUser::GetList(($by = "ID"), ($order = "asc"), ['EMAIL'=> $email]);
		if(!$rsUsers->Fetch()){
			global $DB;
			$DB->Insert($this->globalSettings->getTableSubscribeUser(), [
				'user_id'=>$userId,
				'email'=>'"'.$email.'"'
			], $err_mess.__LINE__);
		}
	}

	private function getSubscribeUser($email){
		$rsUsers = \CUser::GetList(($by = "ID"), ($order = "asc"), ['EMAIL'=> $email]);
		if($user=$rsUsers->Fetch()){
			return $user;
		}else{
			global $DB;
			$subscriptionUser=$DB->Query('select * from '.$this->globalSettings->getTableSubscribeUser().' where email="'.$email.'" order by id desc;');
				if(($subscription = $subscriptionUser->fetch())){
					return ['ID'=>$subscription['user_id']];
				}
		}
		return false;
	}

	private function checkConditionGroup($group){
		if(count($group['children'])==0){
			//set bonus
			return true;
		}else{
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
							$condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $this->user['LID'], $nextChildren['values']['sites']);
						break;
						case 'groupUsers':
							$condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $this->user['GROUPS'], $nextChildren['values']['users']);
						break;
                        case 'ranksUser':
                            $rank=$this->ranksObject->getRankUser($this->user['ID']);
                            $condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $rank, $nextChildren['values']['ranks']);
                        break;
						case 'levelParent':
							$refRarentLevel=[];
							$rewards=$this->getChainReferal($this->user['ID']);
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
                            $rewards=$this->getChainReferal($this->user['ID']);

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
						case 'subscribe':
							$condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $this->user['SUBSCRIBE_ID'], $nextChildren['values']['subscribe']);
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
	
	protected function getStartCondition($mode=''){
		$params=[
			"id"=>"0",
			"controlId"=>"CondGroup",
			"children"=>[[
				"id"=>"0",
				"controlId"=>"registerbyRef",
				"values"=>[
					"bonus"=>"10",
					"bonus_live"=>"4",
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
		
		\Bitrix\Main\Loader::includeModule('sender');
		$subscribeTmp=\Bitrix\Sender\Subscription::getMailingList([]);
		$subscribe=[];
		foreach($subscribeTmp as $nextSubscribe){
			$subscribe[$nextSubscribe['ID']]=$nextSubscribe['NAME'];
		}

		$mainParams=[array(
            'controlId'=> 'sites',
            'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_orderpay_sites"),
            'group'=> false,
            'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_sites"),
            'showIn'=> array('registerbyRef', 'registerbyRefSubGrp', 'registerbyParentRef', 'registerbyParentRefSubGrp'),
            'control'=> array(array(
                'id'=> 'prefix',
                'type'=> 'prefix',
                'text'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_siteEqual")
            ), array(
                'id'=> 'logic',
                'name'=> 'logic',
                'type'=> 'select',
                'values'=> array(
                    'Equal'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_EQUAL"),
                    'Not'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_NOTEQUAL")
                ),
                'defaultText'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_EQUAL"),
                'defaultValue'=> 'Equal'
            ), array(
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
            ))
        ),[
            'controlId'=> 'groupUsers',
            'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_orderpay_GroupRef"),
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
        ],
            [
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
            ],
            [
                'controlId'=> 'subscribe',
                'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_subscribe_desc"),
                'group'=> false,
                'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_subscribe"),
                'showIn'=> ['registerbyRef', 'registerbyRefSubGrp', 'registerbyParentRef', 'registerbyParentRefSubGrp'],
                'control'=> [[
                    'id'=> 'prefix',
                    'type'=> 'prefix',
                    'text'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_subscribe")
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
                    'values'=> $subscribe,
                    'id'=> 'subscribe',
                    'name'=> 'subscribe',
                    'show_value'=>'Y',
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
						'defaultValue'=>'10'
					],
					Loc::getMessage("commerce.loyaltyprogram_CONDITION_UNIT_BONUS"),
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
					Loc::getMessage("commerce.loyaltyprogram_CONDITION_UNIT_BONUS"),
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
						'defaultValue'=>'0',
						'defaultText'=>''
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
	
	public function setBonus($event=false){
		if(\Bitrix\Main\Loader::includeModule('sender') && !empty($this->profileSetting['settings']["condition"]["children"]) && $event!==false){
			$fields = $event->getParameters();
			//for test!!
			//$fields['fields']['CODE']='commerce@yandex.ru';
			global $DB;
			if(!empty($fields['fields']['MAILING_ID']) && !empty($fields['fields']['CONTACT_ID'])){
				$subscriptionDb=$DB->Query('select * from b_sender_contact where id='.$fields['fields']['CONTACT_ID'].';');
				if(($subscription = $subscriptionDb->fetch())){
					$fields['fields']['CODE']=$subscription['CODE'];
				}
			}
			if(!empty($fields['fields']['CODE'])){
				$this->user=$this->getSubscribeUser($fields['fields']['CODE']);
				if($this->user != false){
					//if  already setBonus
					$rsUsersBonus=$DB->Query('select * from '.$this->globalSettings->getTableBonusList().' where profile_id='.$this->profileSetting['id'].' and user_id='.$this->user['ID'].' and user_bonus=0;');
					if(!$rsUsersBonus->Fetch()){
						$this->user['GROUPS']=\CUser::GetUserGroup($this->user['ID']);
						
						$subscribesUser=[];

						/*$subscriptionDb=$DB->Query('select * from b_sender_mailing_subscription where contact_id='.$fields['id'].';');
						while(($subscription = $subscriptionDb->fetch())){
							$subscribesUser[]=$subscription['MAILING_ID'];
						}*/
						$this->user['SUBSCRIBES']='';
						$subscriptionDb=$DB->Query('select * from b_sender_mailing where ID='.$fields['fields']['MAILING_ID'].';');
						if(($subscription = $subscriptionDb->fetch())){
							$subscribesUser[]=$subscription['MAILING_ID'];
							$this->user['SUBSCRIBES']=$subscription['NAME'];
							$this->user['SUBSCRIBE_ID']=$subscription['ID'];
						}
						
						//$this->user['SUBSCRIBES']=[$fields['fields']['MAILING_ID']];
						$options=$this->globalSettings->getOptions();
						foreach($this->profileSetting['settings']["condition"]["children"] as $nextO){
							if(!isset($nextO['children'])){
								continue;
							}else{
                                $this->conditionParameters=[];
								$status=$this->checkConditionGroup($nextO);
								$params=$nextO['values'];
								if(!empty($params['bonus']) && (int) $params['bonus']>0){
									if($status){
										if($nextO['controlId']=='registerbyRef'){
											
											$coeff=$this->getRankCoeff($this->user['ID']);
											$params['bonus']=$coeff*$params['bonus'];
											if($params['bonus_round']=='more'){
												$params['bonus']=ceil($params['bonus']);
											}elseif($params['bonus_round']=='less'){
												$params['bonus']=floor($params['bonus']);
											}elseif($params['bonus_round']=='auto'){
												$params['bonus']=round($params['bonus']);
											}
											
											$mailTmplt=$this->getUserEmail($this->user['ID'], $params['bonus'], $this->user['SUBSCRIBES']);
											$mailTmplt=($mailTmplt!=false)?serialize($mailTmplt):'';
											
											$SMSTmplt=$this->getUserSMS($this->user['ID'], $params['bonus']);
											$SMSTmplt=($SMSTmplt!=false)?serialize($SMSTmplt):'';
											
											$insertArr=[
												'bonus_start'=>(int) $params['bonus'],
												'bonus'=>(int) $params['bonus'],
												'user_id'=>$this->user['ID'],
												'currency'=>'"'.$options['currency'].'"',
												'profile_type'=>'"'.$this->profileSetting['type'].'"',
												'profile_id'=>$this->profileSetting['id'],
												'status'=>'"inactive"',
												'add_comment'=>'"'.$DB->ForSql(Loc::getMessage("commerce.loyaltyprogram_BONUS_USER_SUBSCRIBE", ["#NUM#"=>$params['bonus']])).'"',
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
											if(!$this->isAlreadyRow($insertArr)){
												$idIns=$DB->Insert($this->globalSettings->getTableBonusList(), $insertArr, $err_mess.__LINE__);
												Loyaltyprogram\Eventmanager::manageBonuses($idIns);
											}
										}
										elseif($nextO['controlId']=='registerbyParentRef'){
											$this->setReferalBonuses($nextO);
										}
									}
								}
							}
						}
						return true;
						
					}
				}
			}
		}
		return false;
	}

	private function setReferalBonuses($nextO){

        foreach(['ranksParentRef', 'levelParent'] as $nextParameters){
            if(!empty($this->conditionParameters[$nextParameters])){
                if(empty($newRew)){
                    $newRew=$this->conditionParameters[$nextParameters];
                }else{
                    $newRew=array_intersect($newRew, $this->conditionParameters[$nextParameters]);
                    sort($newRew);
                }
            }
        }
	    $rewards=$this->getChainReferal($this->user['ID']);

		$newRew=(isset($newRew))?array_unique($newRew):$rewards;
		$params=$nextO['values'];
		if((int) $params['bonus']>0 && count($newRew)>0){
			$options=$this->globalSettings->getOptions();
			global $DB;
			$insertArr=[
				'currency'=>'"'.$options['currency'].'"',
				'user_bonus'=>$this->user['ID'],
				'profile_type'=>'"'.$this->profileSetting['type'].'"',
				'profile_id'=>$this->profileSetting['id'],
				'status'=>'"inactive"'
			];
			
			//action_id
			if(!empty($params['number_action'])){
				$insertArr['action_id']=$params['number_action'];
			}
			
			$startBonus=$currentTime=time(); $endBonus='null';
			$insertArr['date_add']='FROM_UNIXTIME('.$currentTime.')';
			if(!empty($params['limit_time']) && $params['limit_time']=='N'){
				$params['bonus_live']=0;
			}
			if(!empty($params['bonus_live']) && $params['bonus_live']>0){
				$endBonus=$startBonus+$params['bonus_live']*$this->timePart[$params['bonus_live_type']];
				$insertArr['date_remove']='FROM_UNIXTIME('.$endBonus.')';
			}
			foreach($newRew as $nextRew){
				
				$coeff=$this->getRankCoeff($nextRew);
				$params['bonus']=$coeff*$params['bonus'];
				if($params['bonus_round']=='more'){
					$params['bonus']=ceil($params['bonus']);
				}elseif($params['bonus_round']=='less'){
					$params['bonus']=floor($params['bonus']);
				}elseif($params['bonus_round']=='auto'){
					$params['bonus']=round($params['bonus']);
				}
				
				$insertArr['bonus_start']=$params['bonus'];
				$insertArr['bonus']=$params['bonus'];
				$insertArr['add_comment']='"'.$DB->ForSql(Loc::getMessage("commerce.loyaltyprogram_BONUS_REF_SUBSCRIBE", ["#NUM#"=>$params['bonus']])).'"';
				
				$insertArr['user_id']=$nextRew;
				$mailTmplt=$this->getRefEmail($nextRew, $params['bonus'], $this->user['SUBSCRIBES']);
				$mailTmplt=($mailTmplt!=false)?serialize($mailTmplt):'';
				$insertArr['email']="'".$mailTmplt."'";
				
				$SMSTmplt=$this->getRefSMS($nextRew, $params['bonus']);
				$SMSTmplt=($SMSTmplt!=false)?serialize($SMSTmplt):'';
				$insertArr['sms']="'".$SMSTmplt."'";
				if(!$this->isAlreadyRow($insertArr)){
					$idIns=$DB->Insert($this->globalSettings->getTableBonusList(), $insertArr, $err_mess.__LINE__);
					Loyaltyprogram\Eventmanager::manageBonuses($idIns);
				}
			}
			
		}
	}
	
}
?>