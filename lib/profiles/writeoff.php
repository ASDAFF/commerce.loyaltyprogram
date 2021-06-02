<?
namespace Commerce\Loyaltyprogram\Profiles;
use \Bitrix\Main\Application,
	Bitrix\Main\Localization\Loc,
	\Commerce\Loyaltyprogram;

\Bitrix\Main\Loader::includeModule('sale');
/**
* writeoff profile
*/
class Writeoff extends Profile implements Profileinterface{
	
	function __construct($idUser=0){
		parent::__construct();
		$this->profileSetting['type']='Writeoff';
		$this->userEmail='';
		$options=$this->globalSettings->getOptions();
		$this->currency=$options['currency'];
		if($idUser==0){
			global $USER;
			$this->idUser=$USER->GetID();
		}else{
			$this->idUser=$idUser;
		}
		$this->userdata=Loyaltyprogram\Tools::getUserData($this->idUser);
	}

	public function setCurrency($currency){
        $this->currency=$currency;
    }
	
	public function getParametersMain(){
		foreach(['type', 'profileActive', 'profileName', 'profileSort'] as $nextRow){
			$this->drawRow($nextRow);
		}
	}
	
	protected function checkEmailList(){
		$this->profileSetting['email_settings']=empty($this->profileSetting['email_settings'])?[]:$this->profileSetting['email_settings'];
		if(empty($this->profileSetting['email_settings']['COMMERCE_LOYAL_WRITEOFF'])){
			$this->profileSetting['email_settings']['COMMERCE_LOYAL_WRITEOFF']=[
				'userTemplate'=>0,
				'userTemplateReject'=>0,
				'adminTemplate'=>0
			];
		}
		$this->checkEmailMain();
	}
	
	protected function mailType($type){
		$mailType = [
			'COMMERCE_LOYAL_WRITEOFF'=>[
				'LID'=>LANGUAGE_ID,
				'EVENT_NAME'=>'COMMERCE_LOYAL_WRITEOFF',
				'NAME'=>Loc::getMessage("commerce.loyaltyprogram_EVENT_BONUSWRITEOFF"),
				'DESCRIPTION'=>'
					#EMAIL_TO# - '.Loc::getMessage("commerce.loyaltyprogram_EVENT_BONUSWRITEOFF_EMAIL_TO").'
					#BONUS# - '.Loc::getMessage("commerce.loyaltyprogram_EVENT_BONUSWRITEOFF_BONUS").'
					#BONUS_ID# - '.Loc::getMessage("commerce.loyaltyprogram_EVENT_BONUSWRITEOFF_BONUS_ID").'
					#USER_ID# - '.Loc::getMessage("commerce.loyaltyprogram_EVENT_BONUSWRITEOFF_USER_ID").'
					#USER_NAME# - '.Loc::getMessage("commerce.loyaltyprogram_EVENT_BONUSWRITEOFF_USER_NAME").'
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
				'EVENT_NAME'=>'COMMERCE_LOYAL_WRITEOFF',
				'LID'=>$sites[0],
				'ACTIVE'=>'Y',
				'EMAIL_FROM'=>'#DEFAULT_EMAIL_FROM#',
				'EMAIL_TO'=>'#EMAIL_TO#',
				'SUBJECT'=>'#SITE_NAME#: '.Loc::getMessage("commerce.loyaltyprogram_EVENT_BONUSPAY_USERBONUSWRITEOF"),
				'MESSAGE'=>$this->globalSettings->getEmailTemplate('writeoff_user'),
				'BODY_TYPE'=>'html'
			],
            'userTemplateReject'=>[
				'EVENT_NAME'=>'COMMERCE_LOYAL_WRITEOFF',
				'LID'=>$sites[0],
				'ACTIVE'=>'Y',
				'EMAIL_FROM'=>'#DEFAULT_EMAIL_FROM#',
				'EMAIL_TO'=>'#EMAIL_TO#',
				'SUBJECT'=>'#SITE_NAME#: '.Loc::getMessage("commerce.loyaltyprogram_EVENT_BONUSPAY_USERBONUSWRITEOF"),
				'MESSAGE'=>$this->globalSettings->getEmailTemplate('writeoff_user_reject'),
				'BODY_TYPE'=>'html'
			],
			'adminTemplate'=>[
				'EVENT_NAME'=>'COMMERCE_LOYAL_WRITEOFF',
				'LID'=>$sites[0],
				'ACTIVE'=>'Y',
				'EMAIL_FROM'=>'#DEFAULT_EMAIL_FROM#',
				'EMAIL_TO'=>'#DEFAULT_EMAIL_FROM#',
				'SUBJECT'=>'#SITE_NAME#: '.Loc::getMessage("commerce.loyaltyprogram_EVENT_BONUSPAY_ADMINBONUSWRITEOF"),
				'MESSAGE'=>$this->globalSettings->getEmailTemplate('writeoff_admin'),
				'BODY_TYPE'=>'html'
			]
		];
		return $mailTemplates[$key];
	}
	
	public function sendEvent($params){
		$etemplate=$this->mailTemplates($params['type']);
		$templateId=$this->profileSetting['email_settings']['COMMERCE_LOYAL_WRITEOFF'][$params['type']];
		$email=!empty($params['email'])?$params['email']:'';
		$userId=!empty($params['user_id'])?$params['user_id']:$this->idUser;
		$userName=!empty($params['user_name'])?$params['user_name']:$this->userdata['FULL_NAME'];
		
		\Bitrix\Main\Mail\Event::send([
			"EVENT_NAME" => $etemplate['EVENT_NAME'],
			"LID" => $etemplate['LID'],
			"C_FIELDS" => [
				'BONUS'=>\CurrencyFormat($params['bonus'], $this->currency),
				'EMAIL_TO'=>$email,
				'BONUS_ID'=>$params['id_bonus'],
				'USER_ID'=>$userId,
				'USER_NAME'=>$userName
			],
			'MESSAGE_ID'=>$templateId
		]);
		
	}
	
	public function getMinBonus(){
		if(!empty((float) $this->minBonus)){
			return (float) $this->minBonus;
		}
		return 1;
	}
	
	public function getMaxBonus(){
		$status=false;
		foreach($this->profileSetting['settings']["condition"]["children"] as $nextO){
			if(!isset($nextO['children'])){
				continue;
			}else{
				$status=$this->checkConditionGroup($nextO);
				if($status){
				
					$params=$nextO['values'];
	
					$account=\CSaleUserAccount::GetByUserID($this->idUser, $this->currency);
					$this->currentBudget=$account['CURRENT_BUDGET'];
					$bonusPay=$params['bonus'];
					
					$coeff=$this->getRankCoeff($this->idUser);
					$bonusPay=$coeff*$bonusPay;
					
					if($params['bonus_unit']=='percent'){
						$bonusPay=$this->currentBudget*$bonusPay/100;
					}
					if($params['bonus_round']=='more'){
						$bonusPay=ceil($bonusPay);
					}elseif($params['bonus_round']=='less'){
						$bonusPay=floor($bonusPay);
					}elseif($params['bonus_round']=='auto'){
						$bonusPay=round($bonusPay);
					}
					if(!empty($params['allow_bonus_max']) && $params['allow_bonus_max']=='N'){
						$params['bonus_max']=0;
					}
					if($bonusPay>$params['bonus_max'] && !empty($params['bonus_max'])){
						$bonusPay=$params['bonus_max'];
					}
					$bonusPay=min((float) $bonusPay, (float) $account['CURRENT_BUDGET']);
					if(!empty((float) $this->maxBonus)){
						$bonusPay=min((float) $this->maxBonus-1, $bonusPay);
					}
					return $bonusPay;
				}
			}
		}
	
		return $status;
	}
	
	public function writeBonus($bonus, $regId, $comment=[]){
		global $DB;
		
		$upd=\CSaleUserAccount::UpdateAccount(
			$this->idUser,
			(-1*$bonus),
			$this->currency,
			"COMMERCE_LOYAL_WRITEOFF",
			'',
			Loc::getMessage("commerce.loyaltyprogram_PROGRAM_WRITEOFF_RESERV")
		);

		if($upd!==false){
			$res = \CSaleUserTransact::GetList(["ID" => "DESC"], ["USER_ID" => $this->idUser, 'CURRENCY'=>$this->currency]);
			$arFields = $res->Fetch();

			$insertFields=[
                'bonus'=>$bonus,
                'user_id'=>$this->idUser,
                'status'=>'"request"',
                'transact_id'=>$arFields['ID'],
                'requisites_id'=>$regId,
                'profile_id'=>((int)$this->profileSetting['id']>0)?$this->profileSetting['id']:0
            ];
			if(count($comment)>0){
                $insertFields['log']="'".serialize($comment)."'";
            }
			$id = $DB->Insert($this->globalSettings->getTableWriteOff(), $insertFields, $err_mess.__LINE__);

			$this->sendEvent([
				'type'=>'adminTemplate',
				'id_bonus'=>$id,
				'bonus'=>$bonus
			]);
			return $id;
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
		return $id;
	}
	
	public function isAlreadyRequest(){
		global $DB;
		$res=$DB->Query('select '.$DB->DateToCharFunction("date_order").' date_order, bonus from '.$this->globalSettings->getTableWriteOff().' where user_id='.$this->idUser.' and status="request" order by id desc;');
		if($row = $res->Fetch()){
			return $row;
		}
		return false;
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
					"bonus_max"=>"1000",
					"bonus_live"=>"4",
					"bonus_round"=>"none",
					"All"=>"AND",
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

		$mainParams=[[
            'controlId'=> 'sites',
            'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_orderpay_sites"),
            'group'=> false,
            'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_sites"),
            'showIn'=> ['registerbyRef', 'registerbyRefSubGrp'],
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
        ],[
            'controlId'=> 'groupUsers',
            'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_orderpay_GroupRef"),
            'group'=> false,
            'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_Group"),
            'showIn'=> ['registerbyRef', 'registerbyRefSubGrp'],
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
            'controlId'=> 'sizeWriteoff',
            'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_hint_size_writeoff"),
            'group'=> false,
            'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_size_writeoff"),
            'showIn'=> ['registerbyRef', 'registerbyRefSubGrp'],
            'control'=> [[
                'id'=> 'prefix',
                'type'=> 'prefix',
                'text'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_size_writeoff")
            ],
                [
                    'id'=> 'logic',
                    'name'=> 'logic',
                    'type'=> 'select',
                    'values'=> [
                        'more'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_MORE"),
                        'less'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_LESS")
                    ],
                    'defaultText'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_EQUAL"),
                    'defaultValue'=> 'less'
                ],
                [
                    'type'=> 'input',
                    'id'=> 'size_writeoff',
                    'name'=> 'size_writeoff',
                    'param_id'=>'n',
                    'show_value'=>'Y',
                    'defaultValue'=>'100',
                    'defaultText'=> ''
                ],
                $optns['currency']
            ]
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
        }

		$params = [
			[
				'controlId'=> 'registerbyRef',
				'group'=> true,
				'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_bonusWriteOff"),
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
					Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_allowWriteOff"),
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
							'percent'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_UNIT_PERCENT"),
							'bonus'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_UNIT_BONUS")
						],
						'defaultText'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_UNIT_PERCENT"),
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
			],[
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
				'controlgroup'=> true,
				'group'=> false,
				'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_MainParameters"),
				'showIn'=> ['registerbyRef', 'registerbyRefSubGrp'],
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
	
	private function checkConditionGroup($group){
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
					$condStatus=$this->checkConditionGroup($nextChildren);
				}else{
					$condStatus=false;
					switch ($nextChildren['controlId']){
						case 'sites':
							$condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], SITE_ID, $nextChildren['values']['sites']);
						break;
						case 'groupUsers':
							global $USER;
							if($USER->IsAuthorized()){
								$userGroup=\CUser::GetUserGroup($USER->GetID());
							}else{
								$userGroup=[0];
							}
							$condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $userGroup, $nextChildren['values']['users']);
						break;
                        case 'ranksUser':
                            global $USER;
                            $rank=$this->ranksObject->getRankUser($USER->GetID());
                            $condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $rank, $nextChildren['values']['ranks']);
                        break;
						case 'sizeWriteoff':
							$condStatus=true;
							if($condition=='ANDNOT' || $condition=='OR'){
								$condStatus=false;
							}
							if($nextChildren['values']['logic']=='more'){
								$this->minBonus=$nextChildren['values']['size_writeoff'];
							}else{
								$this->maxBonus=$nextChildren['values']['size_writeoff'];
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
			if($condition=='AND' || $condition=='ANDNOT'){
				return true;
			}
		}
		return false;
	}

}

?>