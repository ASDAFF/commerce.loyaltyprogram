<?
namespace Commerce\Loyaltyprogram\Profiles;
use \Bitrix\Main\Application,
	Bitrix\Main\Localization\Loc,
	\Commerce\Loyaltyprogram;
	
class Outersource extends Profile implements Profileinterface{
	
	function __construct(){
		parent::__construct();
		$this->profileSetting['type']='Outersource';
		$this->options=$this->globalSettings->getOptions();
		$this->uniqueMode=false;
		$this->additionalParam='';
	}
	
	/**
	* this function change mode profile to unique accrue for each user or not
	* @param bool $mode vome profile
	*/
	public function setUniqueMode($mode){
		if(is_bool($mode)){
			$this->uniqueMode=$mode;
		}
	}
	
	/**
	* this function set additionalParam
	* @param string $param additional param for param condition
	*/
	public function setAdditionalParam($param){
		$this->additionalParam=$param;
	}
	
	public function getParametersMain(){
		foreach(['type', 'profileActive', 'profileName', 'profileSort'] as $nextRow){
			$this->drawRow($nextRow);
		}
	}
	
	/**
	* this function checks the fact of bonuses accrual on this profile for $userId
	* @param int $userId iser id
	* @param int $sum the amount to accrue
	* @return bool|string status true or false - accrue or not, not conditions - not conditions for $userId
	*/
	public function setBonusTest($userId, $sum=0){
		$userData=Loyaltyprogram\Tools::getUserData($userId);
		if(!empty($this->profileSetting['settings']["condition"]["children"]) && $userData!=false){

			$this->userId=(int) $userId;
			$this->site=$userData['LID'];
			$this->sum=(int) $sum;
			foreach($this->profileSetting['settings']["condition"]["children"] as $nextO){
				if(!isset($nextO['children']) || !is_array($nextO['children'])){
					continue;
				}else{
					$params=$nextO['values'];
					if(!empty($params['bonus']) && (int) $params['bonus']>0 && $nextO['controlId']=='registerbyRef'){
						$status=$this->checkConditionGroup($nextO);
						if($status){
							$fields['bonus']=$params['bonus'];
							if($params['bonus_unit']=='percent'){
								$fields['bonus']=$this->sum/100*$fields['bonus'];
								if($params['bonus_round']=='more'){
									$fields['bonus']=ceil($fields['bonus']);
								}elseif($params['bonus_round']=='less'){
									$fields['bonus']=floor($fields['bonus']);
								}elseif($params['bonus_round']=='auto'){
									$fields['bonus']=round($fields['bonus']);
								}else{
									$fields['bonus']=round($fields['bonus'], 2);
								}
							}
							
							if(!empty($params['allow_bonus_max']) && $params['allow_bonus_max']=='N'){
								$params['bonus_max']=0;
							}
							if(!empty($params['bonus_max']) && (int) $params['bonus_max']>0 && $params['bonus_max']<$fields['bonus']){
								$fields['bonus']=(int) $params['bonus_max'];
							}
							if($fields['bonus']>0){
								global $DB;

								$fields['bonus_start']=$fields['bonus'];
								$fields['user_id']=$this->userId;
								$fields['currency']='"'.$this->options['currency'].'"';
								$fields['profile_type']='"'.$this->profileSetting['type'].'"';
								$fields['profile_id']=$this->profileSetting['id'];
								$fields['status']='"inactive"';
								
								$startBonus=$currentTime=time(); $endBonus='null';
								$fields['date_add']='FROM_UNIXTIME('.$currentTime.')';
								if(!empty($params['through_time']) && $params['through_time']=='N'){
									$params['bonus_delay']=0;
								}
								if(!empty($params['bonus_delay']) && $params['bonus_delay']>0){
									$startBonus+=$params['bonus_delay']*$this->timePart[$params['bonus_delay_type']];
									$fields['date_add']='FROM_UNIXTIME('.$startBonus.')';
								}
								if(!empty($params['limit_time']) && $params['limit_time']=='N'){
									$params['bonus_live']=0;
								}
								if(!empty($params['bonus_live']) && $params['bonus_live']>0){
									$endBonus=$startBonus+$params['bonus_live']*$this->timePart[$params['bonus_live_type']];
									$fields['date_remove']='FROM_UNIXTIME('.$endBonus.')';
								}
								
								//action_id
								if(!empty($params['number_action'])){
									$fields['action_id']=$params['number_action'];
								}

								$fields['add_comment']='"'.$DB->ForSql(Loc::getMessage("commerce.loyaltyprogram_OUTERSOURCE_BONUS_ADD", ["#NUM#"=>$fields['bonus']])).' ('.$this->additionalParam.')"';
								return $this->isAlreadyRow($fields);
							}
						}
					}
				}
			}
		}
		return 'not conditions';
	}

	public function setBonus($userId, $sum=0){
		$userData=Loyaltyprogram\Tools::getUserData($userId);
		if(!empty($this->profileSetting['settings']["condition"]["children"]) && $userData!=false){

			$this->userId=(int) $userId;
			$this->site=$userData['LID'];
			$this->sum=(int) $sum;
			
			foreach($this->profileSetting['settings']["condition"]["children"] as $nextO){
				if(!isset($nextO['children']) || !is_array($nextO['children'])){
					continue;
				}else{
					$params=$nextO['values'];
					if(!empty($params['bonus']) && (int) $params['bonus']>0){
						$status=$this->checkConditionGroup($nextO);
						if($status){
							$fields['bonus']=$params['bonus'];
							
							$coeff=$this->getRankCoeff($this->userId);
							$fields['bonus']=$coeff*$fields['bonus'];
							
							if($params['bonus_unit']=='percent'){
								$fields['bonus']=$this->sum/100*$fields['bonus'];
							}
							
							if($params['bonus_round']=='more'){
								$fields['bonus']=ceil($fields['bonus']);
							}elseif($params['bonus_round']=='less'){
								$fields['bonus']=floor($fields['bonus']);
							}elseif($params['bonus_round']=='auto'){
								$fields['bonus']=round($fields['bonus']);
							}else{
								$fields['bonus']=round($fields['bonus'], 2);
							}
							
							if(!empty($params['allow_bonus_max']) && $params['allow_bonus_max']=='N'){
								$params['bonus_max']=0;
							}
							if(!empty($params['bonus_max']) && (int) $params['bonus_max']>0 && $params['bonus_max']<$fields['bonus']){
								$fields['bonus']=(int) $params['bonus_max'];
							}
							if($fields['bonus']>0){
								global $DB;

								$fields['bonus_start']=$fields['bonus'];
								$fields['user_id']=$this->userId;
								$fields['currency']='"'.$this->options['currency'].'"';
								$fields['profile_type']='"'.$this->profileSetting['type'].'"';
								$fields['profile_id']=$this->profileSetting['id'];
								$fields['status']='"inactive"';
								
								$startBonus=$currentTime=time(); $endBonus='null';
								$fields['date_add']='FROM_UNIXTIME('.$currentTime.')';
								if(!empty($params['through_time']) && $params['through_time']=='N'){
									$params['bonus_delay']=0;
								}
								if(!empty($params['bonus_delay']) && $params['bonus_delay']>0){
									$startBonus+=$params['bonus_delay']*$this->timePart[$params['bonus_delay_type']];
									$fields['date_add']='FROM_UNIXTIME('.$startBonus.')';
								}
								if(!empty($params['limit_time']) && $params['limit_time']=='N'){
									$params['bonus_live']=0;
								}
								if(!empty($params['bonus_live']) && $params['bonus_live']>0){
									$endBonus=$startBonus+$params['bonus_live']*$this->timePart[$params['bonus_live_type']];
									$fields['date_remove']='FROM_UNIXTIME('.$endBonus.')';
								}
								
								//action_id
								if(!empty($params['number_action'])){
									$fields['action_id']=$params['number_action'];
								}

								if($nextO['controlId']=='registerbyRef'){
									$mailTmplt=$this->getUserEmail($this->userId, $fields['bonus']);
									$mailTmplt=($mailTmplt!=false)?serialize($mailTmplt):'';
									$fields['email']="'".$mailTmplt."'";
									$addParam=empty($this->additionalParam)?'':' ('.$this->additionalParam.')';
									$fields['add_comment']='"'.$DB->ForSql(Loc::getMessage("commerce.loyaltyprogram_OUTERSOURCE_BONUS_ADD", ["#NUM#"=>$fields['bonus']])).$addParam.'"';
									if(
										(!$this->isAlreadyRow($fields) && $this->uniqueMode) ||
										!$this->uniqueMode
									){
										$DB->Insert($this->globalSettings->getTableBonusList(), $fields, $err_mess.__LINE__);
									}
								}elseif($nextO['controlId']=='registerbyParentRef'){
									$this->setReferalBonuses($nextO, $fields);
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

	private function setReferalBonuses($nextO=[], $fields){
		$rewards=$this->getChainReferal($this->userId);
		
		//check selected referrals (groups, level)...
		/*if(!empty($this->conditionParameters['levelParent'])){
			$newRew=$this->conditionParameters['levelParent'];
		}*/
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
		$newRew=(isset($newRew))?array_unique($newRew):$rewards;
		
		if(count($newRew)>0 && !empty($nextO['values'])){
			if($fields['bonus']>0){
				global $DB;
				$fields['add_comment']='"'.$DB->ForSql(Loc::getMessage("commerce.loyaltyprogram_OUTERSOURCE_REF_BONUS_ADD", ["#NUM#"=>$fields['bonus']])).' ('.$this->additionalParam.')"';
				
				foreach($newRew as $nextRew){
					
					$fields['user_id']=$nextRew;
					$mailTmplt=$this->getRefEmail($nextRew, $fields['bonus']);
					$mailTmplt=($mailTmplt!=false)?serialize($mailTmplt):'';
					$fields['email']="'".$mailTmplt."'";
					if(
						(!$this->isAlreadyRow($fields) && $this->uniqueMode) ||
						!$this->uniqueMode
					){
						$DB->Insert($this->globalSettings->getTableBonusList(), $fields, $err_mess.__LINE__);
					}
				}
			}
		}
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
							$condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $this->site, $nextChildren['values']['sites']);
						break;
						case 'groupUsers':
							$userGroup=\CUser::GetUserGroup($this->userId);
							$condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $userGroup, $nextChildren['values']['users']);
						break;
                        case 'ranksUser':
                            $rank=$this->ranksObject->getRankUser($this->userId);
                            $condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $rank, $nextChildren['values']['ranks']);
                        break;
						case 'outerSumm':
							$condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $this->sum, $nextChildren['values']['outer_summ']);
						break;
						case 'additionalParam':
							$condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $this->additionalParam, $nextChildren['values']['additional_param']);
						break;
						case 'levelParent':
							$refRarentLevel=[];
							$rewards=$this->getChainReferal($this->userId);
							$i=1;
							foreach($rewards as $val){
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
	
	public function save($params){
		$saveFields=[];
		$saveFields['sort']=(int) $params['sort'];
		$saveFields['active']='"'.(empty($params['active'])?'N':'Y').'"';
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
		return $id;
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
            ],
            [
                'controlId'=> 'outerSumm',
                'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_hint_skip_outer_summ"),
                'group'=> false,
                'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_SKIP_OUTER_SUMM"),
                'showIn'=> ['registerbyRef', 'registerbyRefSubGrp', 'registerbyParentRef', 'registerbyParentRefSubGrp'],
                'control'=> [[
                    'id'=> 'prefix',
                    'type'=> 'prefix',
                    'text'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_SKIP_OUTER_SUMM")
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
                        'id'=> 'outer_summ',
                        'name'=> 'outer_summ',
                        'param_id'=>'n',
                        'show_value'=>'Y',
                        'defaultValue'=>'100',
                        'defaultText'=> ''
                    ],
                    $optns['currency']
                ]
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
            ]

            ,
            [
                'controlId'=> 'additionalParam',
                'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_additionalParam_hint"),
                'group'=> false,
                'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_additionalParam"),
                'showIn'=> ['registerbyRef', 'registerbyRefSubGrp', 'registerbyParentRef', 'registerbyParentRefSubGrp'],
                'control'=> [[
                    'id'=> 'prefix',
                    'type'=> 'prefix',
                    'text'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_additionalParam")
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
                    'type'=> 'input',
                    'id'=> 'additional_param',
                    'name'=> 'additional_param',
                    'param_id'=>'n',
                    'show_value'=>'Y',
                    'defaultValue'=>'100',
                    'defaultText'=> ''
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
                'showIn'=> ['registerbyRef', 'registerbyRefSubGrp','registerbyParentRef', 'registerbyParentRefSubGrp'],
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
						'defaultValue'=>'0',
						'defaultText'=>0
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
						'defaultValue'=>'',
						'defaultText'=>0
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
					],
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
						'defaultValue'=>'',
						'defaultText'=>0
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
						'defaultValue'=>'0',
						'defaultText'=>''
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

	protected function checkEmailList(){
		$this->profileSetting['email_settings']=empty($this->profileSetting['email_settings'])?[]:$this->profileSetting['email_settings'];
		if(empty($this->profileSetting['email_settings']['COMMERCE_LOYAL_OUTERSOURCE'])){
			$this->profileSetting['email_settings']['COMMERCE_LOYAL_OUTERSOURCE']=[
				'userTemplate'=>0,
				'refTemplate'=>0
			];
		}
		$this->checkEmailMain();
	}

	protected function mailType($type){
		$mailType = [
			'COMMERCE_LOYAL_OUTERSOURCE'=>[
				'LID'=>LANGUAGE_ID,
				'EVENT_NAME'=>'COMMERCE_LOYAL_OUTERSOURCE',
				'NAME'=>Loc::getMessage("commerce.loyaltyprogram_EVENT_OUTERSOURCE"),
				'DESCRIPTION'=>'
					#EMAIL_TO# - '.Loc::getMessage("commerce.loyaltyprogram_EVENT_OUTERSOURCE_EMAIL_TO").'
					#BONUS# - '.Loc::getMessage("commerce.loyaltyprogram_EVENT_OUTERSOURCE_BONUS").'
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
				'EVENT_NAME'=>'COMMERCE_LOYAL_OUTERSOURCE',
				'LID'=>$sites[0],
				'ACTIVE'=>'Y',
				'EMAIL_FROM'=>'#DEFAULT_EMAIL_FROM#',
				'EMAIL_TO'=>'#EMAIL_TO#',
				'SUBJECT'=>'#SITE_NAME#: '.Loc::getMessage("commerce.loyaltyprogram_EVENT_OUTERSOURCE_USERBONUSADD"),
				'MESSAGE'=>$this->globalSettings->getEmailTemplate('outersource_user'),
				'BODY_TYPE'=>'html'
			],
			'refTemplate'=>[
				'EVENT_NAME'=>'COMMERCE_LOYAL_OUTERSOURCE',
				'LID'=>$sites[0],
				'ACTIVE'=>'Y',
				'EMAIL_FROM'=>'#DEFAULT_EMAIL_FROM#',
				'EMAIL_TO'=>'#EMAIL_TO#',
				'SUBJECT'=>'#SITE_NAME#: '.Loc::getMessage("commerce.loyaltyprogram_EVENT_OUTERSOURCE_REFBONUSADD"),
				'MESSAGE'=>$this->globalSettings->getEmailTemplate('outersource_ref'),
				'BODY_TYPE'=>'html'
			]
		];
		return $mailTemplates[$key];
	}

	private function getUserEmail($userId, $bonus){
		$emailSettings=$this->profileSetting['email_settings'];
		if(!empty($emailSettings['COMMERCE_LOYAL_OUTERSOURCE']['userTemplate'])){
			$rsEM = \CEventMessage::GetByID($emailSettings['COMMERCE_LOYAL_OUTERSOURCE']['userTemplate']);
			if($arEM = $rsEM->Fetch()){
				$sites=array_keys($this->globalSettings->getSites());
				$userData=Loyaltyprogram\Tools::getUserData($userId);
				
				if(!empty($userData['EMAIL'])){
					return [
						"EVENT_NAME" => "COMMERCE_LOYAL_OUTERSOURCE",
						"MESSAGE_ID" => $emailSettings['COMMERCE_LOYAL_OUTERSOURCE']['userTemplate'],
						"LANGUAGE_ID" => LANGUAGE_ID,
						"LID" => $sites[0],
						"C_FIELDS" => [
							"EMAIL_TO" => $userData['EMAIL'],
							"BONUS"=>strval($bonus)
						]
					];
				}
			}
		}
		return false;
	}

	private function getRefEmail($userId, $bonus){
		$emailSettings=$this->profileSetting['email_settings'];
		if(!empty($emailSettings['COMMERCE_LOYAL_OUTERSOURCE']['refTemplate'])){
			$userData=Loyaltyprogram\Tools::getUserData($userId);
			if(!empty($userData['EMAIL'])){
				$sites=array_keys($this->globalSettings->getSites());
				if(!isset($this->refTemplate)){
					$rsEM = \CEventMessage::GetByID($emailSettings['COMMERCE_LOYAL_OUTERSOURCE']['refTemplate']);
					if($arEM = $rsEM->Fetch()){
						$this->refTemplate=$arEM;
					}else{
						$this->refTemplate=false;
					}
				}
				if($this->refTemplate!==false){
					return [
						"EVENT_NAME" => "COMMERCE_LOYAL_OUTERSOURCE",
						"MESSAGE_ID" => $emailSettings['COMMERCE_LOYAL_OUTERSOURCE']['refTemplate'],
						"LANGUAGE_ID" => LANGUAGE_ID,
						"LID" => $sites[0],
						"C_FIELDS" => [
							"EMAIL_TO" => $userData['EMAIL'],
							"BONUS"=>strval($bonus)
						]
					]; 
				}
			}
		}
		return false;
	}
	
}
?>