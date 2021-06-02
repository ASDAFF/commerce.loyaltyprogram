<?
namespace Commerce\Loyaltyprogram\Profiles;
use Bitrix\Main\Application,
	Bitrix\Main\Localization\Loc,
	Commerce\Loyaltyprogram,
	Commerce\Loyaltyprogram\Tools;
	
\Bitrix\Main\Loader::includeModule('iblock');
\Bitrix\Main\Loader::includeModule('forum');
\Bitrix\Main\Loader::includeModule('blog');
/**
* type Reviews
*/
class Reviews extends Profile implements Profileinterface{
	
	function __construct(){
		parent::__construct();
		$this->profileSetting['type']='Reviews';
	}
	
	public function getParametersMain(){
		foreach(['type', 'profileActive', 'profileName', 'profileSort'] as $nextRow){
			$this->drawRow($nextRow);
		}
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
		
		$this->registerEvent('blog', 'OnCommentAdd', 'ReviewAdd');
		$this->registerEvent('forum', 'onAfterMessageAdd', 'ReviewAdd');
		$this->registerEvent('forum', 'onAfterMessageUpdate', 'ReviewAdd');
		$this->registerEvent('iblock', 'OnAfterIBlockElementUpdate', 'ReviewAddIBlock'); // OnBeforeIBlockElementAdd
		$this->registerEvent('iblock', 'OnAfterIBlockElementAdd', 'ReviewAddIBlock'); 
		$this->registerEvent('iblock', 'OnAfterIBlockElementAdd', 'ReviewAddTest');

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
					"bonus"=>"10",
					"bonus_unit"=>"0",
					"bonus_delay"=>"2",
					"bonus_delay_type"=>"day",
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
                'values'=> Tools::getTypeLinkList(['manual', 'import']),
                'id'=> 'type_link',
                'name'=> 'type_link',
                'show_value'=>'Y',
                'first_option'=> '...',
                'defaultText'=> '...',
                'defaultValue'=> ''
            ]]
        ], [
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
						'defaultValue'=>'1000'
					],
					Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_bonusbyReview"),
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
					Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_BonusParentRefByRegReview"),
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
				'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_ReviewParameters"),
				'showIn'=> ['registerbyRef', 'registerbyRefSubGrp', 'registerbyParentRef', 'registerbyParentRefSubGrp'],
				'children'=> 
				[
					// [
					// 	'controlId'=> 'typeReview',
					// 	'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_typeReview_hint"),
					// 	'group'=> false,
					// 	'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_typeReview"),
					// 	'showIn'=> ['registerbyRef', 'registerbyRefSubGrp', 'registerbyParentRef', 'registerbyParentRefSubGrp'],
					// 	'control'=>
					// 	[
					// 		[
					// 		'id'=> 'prefix',
					// 		'type'=> 'prefix',
					// 		'text'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_typeReview")
					// 		], 
					// 		[
					// 		'id'=> 'logic',
					// 		'name'=> 'logic',
					// 		'type'=> 'select',
					// 		'values'=> 
					// 			[
					// 			'Equal'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_EQUAL"),
					// 			'Not'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_NOTEQUAL")
					// 			],
					// 		'defaultText'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_EQUAL"),
					// 		'defaultValue'=> 'Equal'
					// 		], 
					// 		[
					// 		'type'=> 'select',
					// 		'multiple'=>'Y',
					// 		'values'=> 
					// 			[
					// 			'blog'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_typeLinkBlog"),
					// 			'forum'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_typeLinkForum")
					// 			],
					// 		'id'=> 'type_review',
					// 		'name'=> 'type_review',
					// 		'show_value'=>'Y',
					// 		'first_option'=> '...',
					// 		'defaultText'=> '...',
					// 		'defaultValue'=> ''
					// 		]
					// 	]
					// ],
				[
					'controlId'=> 'IDForum',
					'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_IDForum_hint"),
					'group'=> false,
					'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_IDForum"),
					'showIn'=> ['registerbyRef', 'registerbyRefSubGrp', 'registerbyParentRef', 'registerbyParentRefSubGrp'],
					'control'=> [[
						'id'=> 'prefix',
						'type'=> 'prefix',
						'text'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_IDForum")
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
						'id'=> 'id_forum',
						'name'=> 'id_forum',
						'param_id'=>'n',
						'show_value'=>'Y',
						'defaultValue'=>''
					]]
				],
				[
					'controlId' => 'IDIBlock',
					'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_IDIBlock"),
					'group'=> false,
					'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_IDIBlock"),
					'showIn'=> ['registerbyRef', 'registerbyRefSubGrp', 'registerbyParentRef', 'registerbyParentRefSubGrp'],
					'control'=> [[
						'id'=> 'prefix',
						'type'=> 'prefix',
						'text'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_IDIBlock")
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
						'id'=> 'id_forum',
						'name'=> 'id_forum',
						'param_id'=>'n',
						'show_value'=>'Y',
						'defaultValue'=>''
					]]
				],[
					'controlId'=> 'moderate',
					'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_REVIEW_MODERATE_hint"),
					'group'=> false,
					'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_REVIEW_MODERATE"),
					'showIn'=> ['registerbyRef', 'registerbyRefSubGrp', 'registerbyParentRef', 'registerbyParentRefSubGrp'],
					'control'=> [[
						'id'=> 'prefix',
						'type'=> 'prefix',
						'text'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_REVIEW_MODERATE")
					],[
						'type'=> 'select',
						'multiple'=>'Y',
						'values'=> [
							'yes'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_YES")
						],
						'id'=> 'moderate',
						'name'=> 'moderate',
						'first_option'=> '...',
						'defaultText'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_YES"),
						'defaultValue'=> 'yes'
					]]
				],[
					'controlId'=> 'IDBlog',
					'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_IDBlog_hint"),
					'group'=> false,
					'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_IDBlog"),
					'showIn'=> ['registerbyRef', 'registerbyRefSubGrp', 'registerbyParentRef', 'registerbyParentRefSubGrp'],
					'control'=> [[
						'id'=> 'prefix',
						'type'=> 'prefix',
						'text'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_IDBlog")
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
						'id'=> 'id_blog',
						'name'=> 'id_blog',
						'param_id'=>'n',
						'show_value'=>'Y',
						'defaultValue'=>''
					]]
				]]
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
						case 'typeReview':
							$condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $this->parameters['type'], $nextChildren['values']['type_review']);
						break;
						case 'IDForum':
							$condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $this->parameters['id_forum'], $nextChildren['values']['id_forum']);
						break;
						case 'IDBlog':
							$condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $this->parameters['id_blog'], $nextChildren['values']['id_blog']);
						break;
						case 'IDIBlock':
							$condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $this->parameters['id_iblock'], $nextChildren['values']['id_forum']);							
						break;
						case 'moderate':
							$condStatus=$this->checkConditionChildren('Equal', $this->parameters['moderate'], $nextChildren['values']['moderate']);
						break;
						case 'typeLink':
							$condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], Tools::getIdTypeLinkUser($this->userId), $nextChildren['values']['type_link']);
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
						case 'groupUsers':
							$userGroup=\CUser::GetUserGroup($this->userId);
							$condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $userGroup, $nextChildren['values']['users']);
						break;
                        case 'ranksUser':
                            $rank=$this->ranksObject->getRankUser($this->userId);
                            $condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $rank, $nextChildren['values']['ranks']);
                        break;
						case 'levelParent':
							$refRarentLevel=[];
							$rewards=$this->getChainReferal($this->userId);
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
	
	public function setParameters($id, $fields){	
		$this->parameters=[];
		$this->parameters['type']='blog';
		$this->parameters['topuc_name']='';
		$this->parameters['moderate']='no';
		$this->parameters['id_forum']=0;
		$this->parameters['id_blog']=0;
		if(!empty($fields['APPROVED'])){
			$this->parameters['type']='forum';
			if($fields['APPROVED']=='Y'){
				$this->parameters['moderate']='yes';
			}
			if(!empty($fields['FORUM_ID'])){
				$this->parameters['id_forum']=$fields['FORUM_ID'];
			}
			if(empty($fields['PARAM2'])){
				$arMessage = \CForumMessage::GetByID($id);
				$fields['PARAM2']=$arMessage['PARAM2'];
				$this->parameters['id_forum']=$arMessage['FORUM_ID'];
				$fields['AUTHOR_ID']=$arMessage['AUTHOR_ID'];
			}
			$res = \CIBlockElement::GetByID($fields['PARAM2']);
			$ar_res = $res->GetNext();
			$this->parameters['topuc_name']=$ar_res['NAME'];
		}
		if($this->parameters['type']=='blog'){
			$this->parameters['id_blog']=$fields['BLOG_ID'];
			$arPost = \CBlogPost::GetByID($fields['POST_ID']);
			$this->parameters['topuc_name']=$arPost['TITLE'];
		}
		if($fields["type"] = "IBlock"){
			$this->parameters['id_iblock'] = $id;
			$this->parameters['type'] = $fields['type'];
			if($fields["ACTIVE"] == "Y"){
				$this->parameters['moderate']='yes';
			}
		}
		
		$this->userId=!empty($fields['AUTHOR_ID'])?$fields['AUTHOR_ID']:0;
	}
	
	public function setBonus($id, $fields){
		if(empty($id) || empty($fields)){
			return false;
		}
		$this->setParameters($id, $fields);		
		if(!empty($this->profileSetting['settings']["condition"]["children"]) && $this->userId>0){
			global $DB;
			$options=$this->globalSettings->getOptions();
			$this->status=false;
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
								
								$coeff=$this->getRankCoeff($this->userId);
								$nextO['values']['bonus']=round($coeff*$nextO['values']['bonus']);
								
								$mailTmplt=$this->getUserEmail($this->userId, $nextO['values']['bonus'], $this->parameters['topuc_name']);
								$mailTmplt=($mailTmplt!=false)?serialize($mailTmplt):'';
								
								$SMSTmplt=$this->getUserSMS($this->userId, $nextO['values']['bonus']);
								$SMSTmplt=($SMSTmplt!=false)?serialize($SMSTmplt):'';
								
								$fields=[
									'bonus_start'=>$nextO['values']['bonus'],
									'bonus'=>$nextO['values']['bonus'],
									'user_id'=>$this->userId,
									'currency'=>'"'.$options['currency'].'"',
									'profile_type'=>'"'.$this->profileSetting['type'].'"',
									'profile_id'=>$this->profileSetting['id'],
									'status'=>'"inactive"',
									'date_add'=>'FROM_UNIXTIME('.$startBonus.')',
									'date_remove'=>$endBonus, 
									'add_comment'=>'"'.$DB->ForSql(Loc::getMessage("commerce.loyaltyprogram_BONUS_USER_REVIEW", ["#NUM#"=>$nextO['values']['bonus']])).' ('.$this->parameters['topuc_name'].')"',
									'email'=>"'".$mailTmplt."'",
									'sms'=>"'".$SMSTmplt."'"
								];
								//action_id
								if(!empty($nextO['values']['number_action'])){
									$fields['action_id']=$nextO['values']['number_action'];
								}
		
								if(!$this->isAlreadyRow($fields)){
									$idIns=$DB->Insert($this->globalSettings->getTableBonusList(), $fields, $err_mess.__LINE__);
									$this->status=true;
									if($startBonus==$currentTime){
										Loyaltyprogram\Eventmanager::manageBonuses($idIns);
									}
								}
							}
						}elseif($nextO['controlId']=='registerbyParentRef'){
							$this->setReferalBonuses($nextO);
						}
					}
				}
			}
		}
		
		return $this->status;
	}
	
	private function setReferalBonuses($nextO){
		
		$rewards=$this->getChainReferal($this->userId);
		
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
		$newRew=(isset($newRew))?array_unique($newRew):$rewards;
		
		$params=$nextO['values'];
		if((int) $params['bonus']>0 && count($newRew)>0){
			$options=$this->globalSettings->getOptions();
			global $DB;
			$insertArr=[
				'currency'=>'"'.$options['currency'].'"',
				'user_bonus'=>$this->userId,
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
				$params['bonus']=round($coeff*$params['bonus']);
				
				$insertArr['bonus_start']=$params['bonus'];
				$insertArr['bonus']=$params['bonus'];
				$insertArr['add_comment']='"'.$DB->ForSql(Loc::getMessage("commerce.loyaltyprogram_BONUS_REF_REVIEW", ["#NUM#"=>$params['bonus']])).' ('.$this->parameters['topuc_name'].')"';
				
				$insertArr['user_id']=$nextRew;
				$mailTmplt=$this->getRefEmail($nextRew, $params['bonus'], $this->parameters['topuc_name']);
				$mailTmplt=($mailTmplt!=false)?serialize($mailTmplt):'';
				$insertArr['email']="'".$mailTmplt."'";
				
				$SMSTmplt=$this->getRefSMS($nextRew, $params['bonus']);
				$SMSTmplt=($SMSTmplt!=false)?serialize($SMSTmplt):'';
				$insertArr['sms']="'".$SMSTmplt."'";
				if(!$this->isAlreadyRow($insertArr)){
					$this->status=true;
					$idIns=$DB->Insert($this->globalSettings->getTableBonusList(), $insertArr, $err_mess.__LINE__);
					Loyaltyprogram\Eventmanager::manageBonuses($idIns);
				}
			}
			
		}
	}
	
	/* SMS */
	
	protected function checkSMSList(){
		$this->profileSetting['sms_settings']=empty($this->profileSetting['sms_settings'])?[]:$this->profileSetting['sms_settings'];
		if(empty($this->profileSetting['sms_settings']['COMMERCE_LOYAL_REVIEWS_SMS'])){
			$this->profileSetting['sms_settings']['COMMERCE_LOYAL_REVIEWS_SMS']=[
				'userTemplate'=>0,
				'refTemplate'=>0
			];
		}
		$this->checkSMSMain();
	}
	
	protected function SMSType($type){
		$SMSType = [
			'COMMERCE_LOYAL_REVIEWS_SMS'=>[
				'LID'=>LANGUAGE_ID,
				'EVENT_NAME'=>'COMMERCE_LOYAL_REVIEWS_SMS',
				'EVENT_TYPE'  => 'sms',
				'NAME'=>Loc::getMessage("commerce.loyaltyprogram_SMS_REVIEWS"),
				'DESCRIPTION'=>Loc::getMessage("commerce.loyaltyprogram_SMS_REVIEWS_DESC")
			]
		];
		return $SMSType[$type];
	}
	
	protected function SMSTemplates($key){
		$sites=array_keys($this->globalSettings->getSites());
		$smsTemplates = [
			'userTemplate'=>[
				'EVENT_NAME'=>'COMMERCE_LOYAL_REVIEWS_SMS',
				"ACTIVE" => false,
				"SENDER" => "#DEFAULT_SENDER#",
				"RECEIVER" => "#USER_PHONE#",
				"MESSAGE" => GetMessage("commerce.loyaltyprogram_SMS_REVIEWS_USERBONUSADD")
			],
			'refTemplate'=>[
				'EVENT_NAME'=>'COMMERCE_LOYAL_REVIEWS_SMS',
				"ACTIVE" => false,
				"SENDER" => "#DEFAULT_SENDER#",
				"RECEIVER" => "#USER_PHONE#",
				"MESSAGE" => GetMessage("commerce.loyaltyprogram_SMS_REVIEWS_REFBONUSADD")
			]
		];
		return $smsTemplates[$key];
	}
	
	private function getRefSMS($userId, $bonus){
		$SMSSettings=$this->profileSetting['sms_settings'];
		if(!empty($SMSSettings['COMMERCE_LOYAL_REVIEWS_SMS']['refTemplate'])){
			$userData=Tools::getUserData($userId);
			if(!empty($userData['PERSONAL_PHONE'])){
				$tmpPhone=$userData['PERSONAL_PHONE'];
			}elseif(!empty($userData['PERSONAL_MOBILE'])){
				$tmpPhone=$userData['PERSONAL_PHONE'];
			}
			if(!empty($tmpPhone)){
				$tmpPhone=\Bitrix\Main\UserPhoneAuthTable::normalizePhoneNumber($tmpPhone);
				return [
					"EVENT_NAME" => "COMMERCE_LOYAL_REVIEWS_SMS",
					"MESSAGE_ID" => $SMSSettings['COMMERCE_LOYAL_REVIEWS_SMS']['refTemplate'],
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
		if(!empty($SMSSettings['COMMERCE_LOYAL_REVIEWS_SMS']['userTemplate'])){
			$userData=Tools::getUserData($userId);
			if(!empty($userData['PERSONAL_PHONE'])){
				$tmpPhone=$userData['PERSONAL_PHONE'];
			}elseif(!empty($userData['PERSONAL_MOBILE'])){
				$tmpPhone=$userData['PERSONAL_PHONE'];
			}
			if(!empty($tmpPhone)){
				$tmpPhone=\Bitrix\Main\UserPhoneAuthTable::normalizePhoneNumber($tmpPhone);
				return [
					"EVENT_NAME" => "COMMERCE_LOYAL_REVIEWS_SMS",
					"MESSAGE_ID" => $SMSSettings['COMMERCE_LOYAL_REVIEWS_SMS']['userTemplate'],
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
	
	/* EMAIL */
	
	protected function checkEmailList(){
		$this->profileSetting['email_settings']=empty($this->profileSetting['email_settings'])?[]:$this->profileSetting['email_settings'];
		if(empty($this->profileSetting['email_settings']['COMMERCE_LOYAL_REVIEWS'])){
			$this->profileSetting['email_settings']['COMMERCE_LOYAL_REVIEWS']=[
				'userTemplate'=>0,
				'refTemplate'=>0
			];
		}
		$this->checkEmailMain();
	}

	protected function mailType($type){
		$mailType = [
			'COMMERCE_LOYAL_REVIEWS'=>[
				'LID'=>LANGUAGE_ID,
				'EVENT_NAME'=>'COMMERCE_LOYAL_REVIEWS',
				'NAME'=>Loc::getMessage("commerce.loyaltyprogram_EVENT_REVIEWS"),
				'DESCRIPTION'=>'
					#EMAIL_TO# - '.Loc::getMessage("commerce.loyaltyprogram_REVIEWS_EMAIL_TO").'
					#BONUS# - '.Loc::getMessage("commerce.loyaltyprogram_EVENT_REVIEWS_BONUS").'
					#REVIEWS_NAME# - '.Loc::getMessage("commerce.loyaltyprogram_EVENT_REVIEWS_REVIEWSNAME").'
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
				'EVENT_NAME'=>'COMMERCE_LOYAL_REVIEWS',
				'LID'=>$sites[0],
				'ACTIVE'=>'Y',
				'EMAIL_FROM'=>'#DEFAULT_EMAIL_FROM#',
				'EMAIL_TO'=>'#EMAIL_TO#',
				'SUBJECT'=>'#SITE_NAME#: '.Loc::getMessage("commerce.loyaltyprogram_EVENT_REVIEWS_USERBONUSADD"),
				'MESSAGE'=>$this->globalSettings->getEmailTemplate('reviews_user'),
				'BODY_TYPE'=>'html'
			],
			'refTemplate'=>[
				'EVENT_NAME'=>'COMMERCE_LOYAL_REVIEWS',
				'LID'=>$sites[0],
				'ACTIVE'=>'Y',
				'EMAIL_FROM'=>'#DEFAULT_EMAIL_FROM#',
				'EMAIL_TO'=>'#EMAIL_TO#',
				'SUBJECT'=>'#SITE_NAME#: '.Loc::getMessage("commerce.loyaltyprogram_EVENT_REVIEWS_REFBONUSADD"),
				'MESSAGE'=>$this->globalSettings->getEmailTemplate('reviews_ref'),
				'BODY_TYPE'=>'html'
			]
		];
		return $mailTemplates[$key];
	}

	private function getUserEmail($userId, $bonus, $review){
		global $DB;
		$emailSettings=$this->profileSetting['email_settings'];
		if(!empty($emailSettings['COMMERCE_LOYAL_REVIEWS']['userTemplate'])){
			$rsEM = \CEventMessage::GetByID($emailSettings['COMMERCE_LOYAL_REVIEWS']['userTemplate']);
			if($arEM = $rsEM->Fetch()){
				$sites=array_keys($this->globalSettings->getSites());
				$results=$DB->Query('select * from b_user where id='.$userId);
				$arUser = $results->Fetch();
				if(!empty($arUser['EMAIL'])){
					return [
						"EVENT_NAME" => "COMMERCE_LOYAL_REVIEWS",
						"MESSAGE_ID" => $emailSettings['COMMERCE_LOYAL_REVIEWS']['userTemplate'],
						"LANGUAGE_ID" => LANGUAGE_ID,
						"LID" => $sites[0],
						"C_FIELDS" => [
							"EMAIL_TO" => $arUser['EMAIL'],
							"BONUS"=>$bonus,
							"REVIEWS_NAME"=>$review
						]
					];
				}
			}
		}
		return false;
	}

	private function getRefEmail($userId, $bonus, $review){	
		$emailSettings=$this->profileSetting['email_settings'];
		if(!empty($emailSettings['COMMERCE_LOYAL_REVIEWS']['refTemplate'])){
			global $DB;
			$results=$DB->Query('select * from b_user where id='.$userId);
			$arUser = $results->Fetch();
			if(!empty($arUser['EMAIL'])){
				$sites=array_keys($this->globalSettings->getSites());
				if(!isset($this->refTemplate)){
					$rsEM = \CEventMessage::GetByID($emailSettings['COMMERCE_LOYAL_REVIEWS']['refTemplate']);
					if($arEM = $rsEM->Fetch()){
						$this->refTemplate=$arEM;
					}else{
						$this->refTemplate=false;
					}
				}
				if($this->refTemplate!==false){
					return [
						"EVENT_NAME" => "COMMERCE_LOYAL_REVIEWS",
						"MESSAGE_ID" => $emailSettings['COMMERCE_LOYAL_REVIEWS']['refTemplate'],
						"LANGUAGE_ID" => LANGUAGE_ID,
						"LID" => $sites[0],
						"C_FIELDS" => [
							"EMAIL_TO" => $arUser['EMAIL'],
							"BONUS"=>$bonus,
							"REVIEWS_NAME"=>$review
						]
					]; 
				}
			}
		}
		return false;
	}
	
	
}