<?
namespace Commerce\Loyaltyprogram\Profiles;
use \Bitrix\Main\Application,
	Bitrix\Main\Localization\Loc,
	\Commerce\Loyaltyprogram;
/**
* type register user
*/
class TurnoverRef extends Profile implements Profileinterface{
	
	function __construct(){
		parent::__construct();
		$this->profileSetting['type']='TurnoverRef';
		$this->allUsers=[];
		$this->usersPeriodFrom=0;
		$this->usersPeriodTo=0;
	}
	
	public function getParametersMain(){
		foreach(['type', 'profileActive', 'profileName', 'profileSort'] as $nextRow){
			$this->drawRow($nextRow);
		}
	}
	
	protected function checkSMSList(){
		$this->profileSetting['sms_settings']=empty($this->profileSetting['sms_settings'])?[]:$this->profileSetting['sms_settings'];
		if(empty($this->profileSetting['sms_settings']['COMMERCE_LOYAL_TURNOVER_REF_SMS'])){
			$this->profileSetting['sms_settings']['COMMERCE_LOYAL_TURNOVER_REF_SMS']=[
				'refTemplate'=>0
			];
		}
		$this->checkSMSMain();
	}
	
	protected function SMSType($type){
		$SMSType = [
			'COMMERCE_LOYAL_TURNOVER_REF_SMS'=>[
				'LID'=>LANGUAGE_ID,
				'EVENT_NAME'=>'COMMERCE_LOYAL_TURNOVER_REF_SMS',
				'EVENT_TYPE'  => 'sms',
				'NAME'=>Loc::getMessage("commerce.loyaltyprogram_SMS_TURNOVER_REF"),
				'DESCRIPTION'=>Loc::getMessage("commerce.loyaltyprogram_SMS_TURNOVER_REF_DESC")
			]
		];
		return $SMSType[$type];
	}
	
	protected function SMSTemplates($key){
		$sites=array_keys($this->globalSettings->getSites());
		$smsTemplates = [
			'refTemplate'=>[
				'EVENT_NAME'=>'COMMERCE_LOYAL_TURNOVER_REF_SMS',
				"ACTIVE" => false,
				"SENDER" => "#DEFAULT_SENDER#",
				"RECEIVER" => "#USER_PHONE#",
				"MESSAGE" => GetMessage("commerce.loyaltyprogram_SMS_TURNOVER_REF_REFBONUSADD")
			]
		];
		return $smsTemplates[$key];
	}
	
	private function getRefSMS($userId, $bonus, $turnover){
		$SMSSettings=$this->profileSetting['sms_settings'];
		if(!empty($SMSSettings['COMMERCE_LOYAL_TURNOVER_REF_SMS']['refTemplate'])){
			$userData=Loyaltyprogram\Tools::getUserData($userId);
			if(!empty($userData['PERSONAL_PHONE'])){
				$tmpPhone=$userData['PERSONAL_PHONE'];
			}elseif(!empty($userData['PERSONAL_MOBILE'])){
				$tmpPhone=$userData['PERSONAL_PHONE'];
			}
			if(!empty($tmpPhone)){
				$tmpPhone=\Bitrix\Main\UserPhoneAuthTable::normalizePhoneNumber($tmpPhone);
				return [
					"EVENT_NAME" => "COMMERCE_LOYAL_TURNOVER_REF_SMS",
					"MESSAGE_ID" => $SMSSettings['COMMERCE_LOYAL_TURNOVER_REF_SMS']['refTemplate'],
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
		if(empty($this->profileSetting['email_settings']['COMMERCE_LOYAL_TURNOVER_REF'])){
			$this->profileSetting['email_settings']['COMMERCE_LOYAL_TURNOVER_REF']=[
				'refTemplate'=>0
			];
		}
		$this->checkEmailMain();
	}
	
	protected function mailType($type){
		$mailType = [
			'COMMERCE_LOYAL_TURNOVER_REF'=>[
				'LID'=>LANGUAGE_ID,
				'EVENT_NAME'=>'COMMERCE_LOYAL_TURNOVER_REF',
				'NAME'=>Loc::getMessage("commerce.loyaltyprogram_EVENT_BONUSTURNOVER_REF"),
				'DESCRIPTION'=>'
					#EMAIL_TO# - '.Loc::getMessage("commerce.loyaltyprogram_EVENT_BONUSTURNOVER_REF_EMAIL_TO").'
					#BONUS# - '.Loc::getMessage("commerce.loyaltyprogram_EVENT_BONUSTURNOVER_REF_BONUS").'
					#TURNOVER# - '.Loc::getMessage("commerce.loyaltyprogram_EVENT_BONUSTURNOVER_REF_TURNOVER").'
				',
				'SORT'=>500
			]
		];
		return $mailType[$type];
	}
	
	protected function mailTemplates($key){
		$sites=array_keys($this->globalSettings->getSites());
		$mailTemplates = [
			'refTemplate'=>[
				'EVENT_NAME'=>'COMMERCE_LOYAL_TURNOVER_REF',
				'LID'=>$sites[0],
				'ACTIVE'=>'Y',
				'EMAIL_FROM'=>'#DEFAULT_EMAIL_FROM#',
				'EMAIL_TO'=>'#EMAIL_TO#',
				'SUBJECT'=>'#SITE_NAME#: '.Loc::getMessage("commerce.loyaltyprogram_EVENT_BONUSTURNOVER_PARENTREFBONUSADD"),
				'MESSAGE'=>$this->globalSettings->getEmailTemplate('turnover_parentref'),
				'BODY_TYPE'=>'html'
			]
		];
		return $mailTemplates[$key];
	}

    private function getInterval($period){
        unset($this->usersTurnoverFrom, $this->usersTurnoverTo);
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
                $cTimeStart = gmmktime(0, 0, 0, date("n")-1, 1, date("Y"));
                $cTimeEnd = strtotime("+1 month", $cTimeStart);
                break;
            case 'quarter':
                $cMonth=date("n");
                if($cMonth>9){
                    $cTimeStart = gmmktime(0, 0, 0, 7, 1, date("Y"));
                }elseif($cMonth>6){
                    $cTimeStart = gmmktime(0, 0, 0, 4, 1, date("Y"));
                }elseif($cMonth>3){
                    $cTimeStart = gmmktime(0, 0, 0, 1, 1, date("Y"));
                }else{
                    $cTimeStart = gmmktime(0, 0, 0, 10, 1, date("Y")-1);
                }
                $cTimeEnd = strtotime("+3 month", $cTimeStart);
                break;
            case 'year':
                $cTimeStart = gmmktime(0, 0, 0, 1, 1, date("Y")-1);
                $cTimeEnd = strtotime("+1 year", $cTimeStart);
                break;
        }
        $this->usersTurnoverFrom=$cTimeStart;
        $this->usersTurnoverTo=$cTimeEnd;
    }

	private function getAllUsers(){
		global $DB;
		$query='select 
			b_user.id as id,
			b_user.name as name,
			b_user.lid as site,
			b_user.email as email,
			'.$this->globalSettings->getTableUsersList().'.level as ref_level,
			'.$this->globalSettings->getTableUsersList().'.user as ref_id,
			'.$this->globalSettings->getTableUsersList().'.ref_user as ref_parent,
			'.$this->globalSettings->getTableUsersList().'.date_create as ref_date,
			GROUP_CONCAT(b_user_group.group_id) as groups
			from b_user left join b_user_group on (b_user_group.user_id=b_user.id)
			left join '.$this->globalSettings->getTableUsersList().' on (b_user.id='.$this->globalSettings->getTableUsersList().'.user)
			where '.$this->globalSettings->getTableUsersList().'.user is not null
			group by b_user.id;';
		$rsUsers=$DB->Query($query);
		while ($arUser = $rsUsers->Fetch()) {
			$arUser['groups']=explode(',',$arUser['groups']);
			$arUser['is_referral']=false;
			$this->allUsers[$arUser['id']]=$arUser;
		}
		foreach($this->allUsers as $nextUser){
			if($nextUser['ref_parent']>0 && !empty($this->allUsers[$nextUser['ref_parent']])){
				$this->allUsers[$nextUser['ref_parent']]['is_referral']=true;
			}
		}
	}

	private function setChildrenTree($id_user, $level){
		$cLevel=$level+1;
		foreach($this->allUsers as &$nextUser){
			if($nextUser['ref_parent']==$id_user){
				$this->childrens[$nextUser['id']]=$cLevel;
				$nextUser['level_children']=$cLevel;
				if($nextUser['is_referral']){
					$this->setChildrenTree($nextUser['id'], $cLevel);
				}
			}
		}
	}

	public function setBonus(){
		$this->getAllUsers();
		if(!empty($this->profileSetting['settings']["condition"]["children"])){
			foreach($this->profileSetting['settings']["condition"]["children"] as $nextO){
				if(!isset($nextO['children'])){
					continue;
				}else{
					if($nextO['values']['bonus']>0 && !empty($nextO['values']['period'])){
						$this->getInterval($nextO['values']['period']);
						foreach($this->allUsers as $nextUser){
							if(!$nextUser['is_referral']){
								continue;
							}

							/*if($this->isAcc($nextUser['id'], $nextO['values'])){
								continue;
							}*/
							unset($this->usersTurnover);
							//get childrenUser
							$this->childrens=[];
							$this->setChildrenTree($nextUser['id'], 0);
							$countUsers=count($this->childrens);

							if($countUsers>0){
								$successChildren=[];
								foreach($this->childrens as $childrenId=>$childrenLevel){
									$tmpChildren=$this->allUsers[$childrenId];
									$tmpChildren['site']=$nextUser['site'];
									$tmpChildren['groups']=$nextUser['groups'];
									$tmpChildren['level_children']=$childrenLevel;
									$tmpChildren['count_users']=$countUsers;
									$status=$this->checkConditionGroup($nextO,$tmpChildren);
									if($status){
										$successChildren[]=$childrenId;
									}
								}

								if(count($successChildren)>0){
									$bonus=$nextO['values']['bonus'];
									$unit=(!empty($nextO['values']['bonus_unit']) && $nextO['values']['bonus_unit']=='bonus')?'bonus':'percent';
									if($unit=='percent'){
										$bonus=0;
										foreach($successChildren as $nextChildren){
											if(!empty($this->allUsers[$nextChildren]['total_price'])){
												$bonus+=$this->allUsers[$nextChildren]['total_price'];
											}
										}
										$bonus=$bonus*$nextO['values']['bonus']/100;
									}
									if($bonus>0){
										$usersTurnover=isset($this->usersTurnover)?$this->usersTurnover['turnover']:0;
										$tmpParams=$nextO['values'];
										$tmpParams['bonus']=$bonus;
										
										$coeff=$this->getRankCoeff($nextUser['id']);
										$tmpParams['bonus']=$coeff*$tmpParams['bonus'];
										$tmpParams=$this->calculateBonus($tmpParams);

										$fields=[
											'bonus'=>$tmpParams['size'],
											'start_bonus'=>$tmpParams['startBonus'],
											'end_bonus'=>$tmpParams['endBonus']
										];
										//action_id
										if(!empty($nextO['values']['number_action'])){
											$fields['action_id']=$nextO['values']['number_action'];
										}
										
										$this->setReferalBonuses($nextUser['id'], $fields, $usersTurnover);
									}
								}
							}
						}
					}
				}
			}
			return true;
		}
		return false;
	}
	
	/*private function isAcc($id_user, $params){
		$cbonus=$this->calculateBonus($params);
		$endBonus=(empty($cbonus['endBonus']) || $cbonus['endBonus']=='null')?'null':'FROM_UNIXTIME('.$cbonus['endBonus'].')';
		global $DB;
		$options=$this->globalSettings->getOptions();
		$results=$DB->Query('select * from '.$this->globalSettings->getTableBonusList().' where 
			user_id='.$id_user.' and
			currency="'.$options['currency'].'" and
			profile_type="'.$this->profileSetting['type'].'" and
			profile_id='.$this->profileSetting['id'].' and
			date_add=FROM_UNIXTIME('.$this->usersTurnoverTo.') and
			date_remove='.$endBonus.'
		;');
		if($results->Fetch()){
			return true;
		}
		return false;
	}*/

	private function setReferalBonuses($refUserId, $bonus, $turnover){
		global $DB;
		$options=$this->globalSettings->getOptions();
		
		$mailTmplt=$this->getRefEmail($refUserId, \CurrencyFormat($bonus['bonus'], $options['currency']), \CurrencyFormat($turnover, $options['currency']));
		$mailTmplt=($mailTmplt!=false)?serialize($mailTmplt):'';
		
		$endBonus=(empty($bonus['end_bonus']) || $bonus['end_bonus']=='null')?' is null':'=FROM_UNIXTIME('.$bonus['end_bonus'].')';
		$updEndBonus=(empty($bonus['end_bonus']) || $bonus['end_bonus']=='null')?'null':'FROM_UNIXTIME('.$bonus['end_bonus'].')';
		
		$results=$DB->Query('select * from '.$this->globalSettings->getTableBonusList().' where 
			user_id='.$refUserId.' and
			currency="'.$options['currency'].'" and
			profile_type="'.$this->profileSetting['type'].'" and
			profile_id='.$this->profileSetting['id'].' and
			date_add=FROM_UNIXTIME('.$bonus['start_bonus'].') and
			action_id='.$bonus['action_id'].' and
			date_remove'.$endBonus.'
		;');
		$arRes = $results->Fetch();

		if($arRes!==false){return;}

		$fields=[
			'bonus_start'=>$bonus['bonus'],
			'bonus'=>$bonus['bonus'],
			'user_id'=>$refUserId,
			'currency'=>'"'.$options['currency'].'"',
			'profile_type'=>'"'.$this->profileSetting['type'].'"',
			'profile_id'=>$this->profileSetting['id'],
			'status'=>'"inactive"',
			'date_add'=>'FROM_UNIXTIME('.$bonus['start_bonus'].')',
			'date_remove'=>$updEndBonus,
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
		
		//if($arRes==false){
		$DB->Insert($this->globalSettings->getTableBonusList(), $fields, $err_mess.__LINE__);
		//}
		//else{
			//$DB->Update($this->globalSettings->getTableBonusList(), $fields, "WHERE ID='".$arRes['id']."'", $err_mess.__LINE__);
		//}
	}

	private function calculateBonus($params){
		$bonus=[
			'size'=>$params['bonus'],
			'startBonus'=>$this->usersTurnoverTo,
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
	
	private function getRefEmail($userId, $bonus, $turnover){
		$emailSettings=$this->profileSetting['email_settings'];
		if(!empty($emailSettings['COMMERCE_LOYAL_TURNOVER_REF']['refTemplate'])){
			$rsEM = \CEventMessage::GetByID($emailSettings['COMMERCE_LOYAL_TURNOVER_REF']['refTemplate']);
			if($arEM = $rsEM->Fetch()){
				$sites=array_keys($this->globalSettings->getSites());
				global $DB;
				$results=$DB->Query('select * from b_user where id='.$userId);
				$arUser = $results->Fetch();
				
				if(!empty($arUser['EMAIL'])){
					return [
						"EVENT_NAME" => "COMMERCE_LOYAL_TURNOVER_REF",
						"MESSAGE_ID" => $emailSettings['COMMERCE_LOYAL_TURNOVER_REF']['refTemplate'],
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
		$this->registerAgent('turnoverRef', 86400, 10);
		return $id;
	}

	private function getUsersTurnover(){
		if(!isset($this->usersTurnover)){
			$options=$this->globalSettings->getOptions();
			$where='USER_ID in ('.implode(',',array_keys($this->childrens)).')';
			$this->usersTurnover=['turnover'=>0, 'orders'=>0];
			if(!empty($this->usersTurnoverFrom)){
				$where.=' AND DATE_INSERT>=FROM_UNIXTIME('.$this->usersTurnoverFrom.')';
			}
			if(!empty($this->usersTurnoverTo)){
				$where.=' AND DATE_INSERT < FROM_UNIXTIME('.$this->usersTurnoverTo.')';
			}
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
			where '.$where.'
			group by USER_ID;';
			$this->usersTurnover=[];
			$res=$DB->Query($query);

			while($row = $res->Fetch()){
				$this->usersTurnover['turnover']+=$row['total_price'];
				$this->usersTurnover['orders']+=$row['orders'];
				$this->allUsers[$row['user_id']]['total_price']=$row['total_price'];
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
				"controlId"=>"registerbyParentRef",
				"values"=>[
					"bonus"=>"10",
					"bonus_unit"=>"0",
					"bonus_delay"=>"",
					"bonus_delay_type"=>"day",
					"allow_bonus_max"=>"Y",
					"through_time"=>"Y",
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
            'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_sites_ref"),
            'showIn'=> ['registerbyParentRef', 'registerbyParentRefSubGrp'],
            'control'=> [[
                'id'=> 'prefix',
                'type'=> 'prefix',
                'text'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_siteEqual_ref")
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
                'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_GroupRef"),
                'showIn'=> ['registerbyParentRef', 'registerbyParentRefSubGrp'],
                'control'=> [[
                    'id'=> 'prefix',
                    'type'=> 'prefix',
                    'text'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_GroupRef")
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
                'controlId'=> 'countUsers',
                'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_hint_countUsersRef"),
                'group'=> false,
                'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_countUsersRef"),
                'showIn'=> ['registerbyParentRef', 'registerbyParentRefSubGrp'],
                'control'=> [[
                    'id'=> 'prefix',
                    'type'=> 'prefix',
                    'text'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_countUsersRef")
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
                        'id'=> 'count_users',
                        'name'=> 'count_users',
                        'param_id'=>'n',
                        'show_value'=>'Y',
                        'defaultValue'=>'100'
                    ]
                ]
            ],
            [
                'controlId'=> 'turnover',
                'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_hint_turnoverPeriodRef"),
                'group'=> false,
                'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_turnoverPeriodRef"),
                'showIn'=> ['registerbyParentRef', 'registerbyParentRefSubGrp'],
                'control'=> [[
                    'id'=> 'prefix',
                    'type'=> 'prefix',
                    'text'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_turnoverPeriodRef")
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
                'controlId'=> 'countOrders',
                'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_hint_countOrdersRef"),
                'group'=> false,
                'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_countOrdersRef"),
                'showIn'=> ['registerbyParentRef', 'registerbyParentRefSubGrp'],
                'control'=> [[
                    'id'=> 'prefix',
                    'type'=> 'prefix',
                    'text'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_countOrdersRef")
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
            ],[
                'controlId'=> 'levelChildren',
                'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_hint_levelUserRef"),
                'group'=> false,
                'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_levelUserRef"),
                'showIn'=> ['registerbyParentRef', 'registerbyParentRefSubGrp'],
                'control'=> [[
                    'id'=> 'prefix',
                    'type'=> 'prefix',
                    'text'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_levelUserRef")
                ], [
                    'id'=> 'logic',
                    'name'=> 'logic',
                    'type'=> 'select',
                    'values'=> [
                        'Equal'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_EQUAL"),
                        'more'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_MORE"),
                        'less'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_LESS")
                    ],
                    'defaultText'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_EQUAL"),
                    'defaultValue'=> 'Equal'
                ], [
                    'type'=> 'input',
                    'id'=> 'level_children',
                    'name'=> 'level_children',
                    'defaultText'=> '...',
                    'show_value'=>'Y',
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
                'showIn'=> ['registerbyParentRef', 'registerbyParentRefSubGrp'],
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
        }

		$params = [
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
							'percent'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_UNIT_PERCENT_TURNOVER_USER_REF"),
							'bonus'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_UNIT_BONUS")
						],
						'defaultText'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_UNIT_PERCENT_TURNOVER_USER_REF"),
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
						//'defaultText'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_MONTH"),
						'defaultValue'=> ''
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
				'showIn'=> ['registerbyParentRef', 'registerbyParentRefSubGrp'],
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
							$condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $user['groups'], $nextChildren['values']['users']);
						break;
						case 'turnover':
							$turnover=$this->getUsersTurnover();
							$condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $turnover['turnover'], $nextChildren['values']['turnover']);
						break;
						case 'levelChildren':
							$condStatus=$this->checkConditionChildren($nextChildren['values']['logic'],  $user['level_children'], $nextChildren['values']['level_children']);
						break;
						case 'countUsers':
							$condStatus=$this->checkConditionChildren($nextChildren['values']['logic'],  $user['count_users'], $nextChildren['values']['count_users']);
						break;
						case 'countOrders':
							$turnover=$this->getUsersTurnover();
							$condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $turnover['orders'], $nextChildren['values']['count_orders']);
						break;
                        case 'ranksUser':
                            $rank=$this->ranksObject->getRankUser($user['id']);
                            $condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $rank, $nextChildren['values']['ranks']);
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