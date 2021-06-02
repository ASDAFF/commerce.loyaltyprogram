<?
namespace Commerce\Loyaltyprogram\Profiles;
use \Bitrix\Main\Application,
	Bitrix\Main\Localization\Loc,
	\Commerce\Loyaltyprogram;
	
	//include /bitrix/modules/main/lang/ru/admin/user_edit.php
	IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/admin/user_edit.php");
	
	
/**
* type profilecompleted
*/
class Profilecompleted extends Profile implements Profileinterface{
	
	function __construct(){
		parent::__construct();
		$this->profileSetting['type']='Profilecompleted';
	}
	
	public function getParametersMain(){
		foreach(['type', 'profileActive', 'profileName', 'profileSort'] as $nextRow){
			$this->drawRow($nextRow);
		}
		$this->drawProfile();
	}
	
	protected function checkSMSList(){
		$this->profileSetting['sms_settings']=empty($this->profileSetting['sms_settings'])?[]:$this->profileSetting['sms_settings'];
		if(empty($this->profileSetting['sms_settings']['COMMERCE_LOYAL_PROFILECOMPLETED_SMS'])){
			$this->profileSetting['sms_settings']['COMMERCE_LOYAL_PROFILECOMPLETED_SMS']=[
				'userTemplate'=>0,
				'refTemplate'=>0
			];
		}
		$this->checkSMSMain();
	}
	
	protected function SMSType($type){
		$SMSType = [
			'COMMERCE_LOYAL_PROFILECOMPLETED_SMS'=>[
				'LID'=>LANGUAGE_ID,
				'EVENT_NAME'=>'COMMERCE_LOYAL_PROFILECOMPLETED_SMS',
				'EVENT_TYPE'  => 'sms',
				'NAME'=>Loc::getMessage("commerce.loyaltyprogram_SMS_PROFILECOMPLETED"),
				'DESCRIPTION'=>Loc::getMessage("commerce.loyaltyprogram_SMS_PROFILECOMPLETED_DESC")
			]
		];
		return $SMSType[$type];
	}
	
	protected function SMSTemplates($key){
		$sites=array_keys($this->globalSettings->getSites());
		$smsTemplates = [
			'userTemplate'=>[
				'EVENT_NAME'=>'COMMERCE_LOYAL_PROFILECOMPLETED_SMS',
				"ACTIVE" => false,
				"SENDER" => "#DEFAULT_SENDER#",
				"RECEIVER" => "#USER_PHONE#",
				"MESSAGE" => GetMessage("commerce.loyaltyprogram_SMS_PROFILECOMPLETED_USERBONUSADD")
			],
			'refTemplate'=>[
				'EVENT_NAME'=>'COMMERCE_LOYAL_PROFILECOMPLETED_SMS',
				"ACTIVE" => false,
				"SENDER" => "#DEFAULT_SENDER#",
				"RECEIVER" => "#USER_PHONE#",
				"MESSAGE" => GetMessage("commerce.loyaltyprogram_SMS_PROFILECOMPLETED_REFBONUSADD")
			]
		];
		return $smsTemplates[$key];
	}
	
	private function getRefSMS($userId, $bonus, $percent){
		$SMSSettings=$this->profileSetting['sms_settings'];
		if(!empty($SMSSettings['COMMERCE_LOYAL_PROFILECOMPLETED_SMS']['refTemplate']) && $bonus>0){
			$userData=Loyaltyprogram\Tools::getUserData($userId);
			if(!empty($userData['PERSONAL_PHONE'])){
				$tmpPhone=$userData['PERSONAL_PHONE'];
			}elseif(!empty($userData['PERSONAL_MOBILE'])){
				$tmpPhone=$userData['PERSONAL_PHONE'];
			}
			if(!empty($tmpPhone)){
				$tmpPhone=\Bitrix\Main\UserPhoneAuthTable::normalizePhoneNumber($tmpPhone);
				return [
					"EVENT_NAME" => "COMMERCE_LOYAL_PROFILECOMPLETED_SMS",
					"MESSAGE_ID" => $SMSSettings['COMMERCE_LOYAL_PROFILECOMPLETED_SMS']['refTemplate'],
					"LID" => $userData['LID'],
					"C_FIELDS" => [
						"USER_PHONE" => $tmpPhone,
						"BONUS"=>strval($bonus),
						"PERCENT"=>strval($percent)
					]
				];
			}
		}
		return false;
	}
	
	private function getUserSMS($userId, $bonus, $percent){
		$SMSSettings=$this->profileSetting['sms_settings'];
		if(!empty($SMSSettings['COMMERCE_LOYAL_PROFILECOMPLETED_SMS']['userTemplate']) && $bonus>0){
			$userData=Loyaltyprogram\Tools::getUserData($userId);
			if(!empty($userData['PERSONAL_PHONE'])){
				$tmpPhone=$userData['PERSONAL_PHONE'];
			}elseif(!empty($userData['PERSONAL_MOBILE'])){
				$tmpPhone=$userData['PERSONAL_PHONE'];
			}
			if(!empty($tmpPhone)){
				$tmpPhone=\Bitrix\Main\UserPhoneAuthTable::normalizePhoneNumber($tmpPhone);
				return [
					"EVENT_NAME" => "COMMERCE_LOYAL_PROFILECOMPLETED_SMS",
					"MESSAGE_ID" => $SMSSettings['COMMERCE_LOYAL_PROFILECOMPLETED_SMS']['userTemplate'],
					"LID" => $userData['LID'],
					"C_FIELDS" => [
						"USER_PHONE" => $tmpPhone,
						"BONUS"=>strval($bonus),
						"PERCENT"=>strval($percent)
					]
				];
			}
		}
		return false;
	}

	private function getUserFields(){
		$listProfile=[
			'main'=>[
				'title'=>Loc::getMessage("MAIN_USER_TAB1"),
				'child'=>[
					'NAME'=>Loc::getMessage("NAME"),
					'LAST_NAME'=>Loc::getMessage("LAST_NAME"),
					'SECOND_NAME'=>Loc::getMessage("SECOND_NAME"),
					'EMAIL'=>Loc::getMessage("EMAIL")
				]
			],
			'personal'=>[
				'title'=>Loc::getMessage("USER_PERSONAL_INFO"),
				'child'=>[
					'PERSONAL_PROFESSION'=>Loc::getMessage("USER_PROFESSION"),
					'PERSONAL_WWW'=>Loc::getMessage("USER_WWW"),
					'PERSONAL_GENDER' => Loc::getMessage("USER_GENDER"),
					'PERSONAL_BIRTHDAY' => Loc::getMessage("USER_BIRTHDAY_DT"),
					'PERSONAL_PHOTO' => Loc::getMessage("USER_PHOTO"),
					'PERSONAL_PHONE' => Loc::getMessage("USER_PHONE"),
					'PERSONAL_FAX' => Loc::getMessage("USER_FAX"),
					'PERSONAL_MOBILE' => Loc::getMessage("USER_MOBILE"),
					'PERSONAL_STREET' => Loc::getMessage("USER_STREET"),
					'PERSONAL_MAILBOX' => Loc::getMessage("USER_MAILBOX"),
					'PERSONAL_CITY' => Loc::getMessage("USER_CITY"),
					'PERSONAL_STATE' => Loc::getMessage("USER_STATE"),
					'PERSONAL_ZIP' => Loc::getMessage("USER_ZIP"),
					'PERSONAL_COUNTRY' => Loc::getMessage("USER_COUNTRY")
				]
			],
			'work'=>[
				'title'=>Loc::getMessage("MAIN_USER_TAB4"),
				'child'=>[
					'WORK_WWW' => Loc::getMessage("USER_WWW"),
					'WORK_DEPARTMENT' => Loc::getMessage("USER_DEPARTMENT"),
					'WORK_POSITION' => Loc::getMessage("USER_POSITION"),
					'WORK_PROFILE' => Loc::getMessage("USER_WORK_PROFILE"),
					'WORK_PHONE' => Loc::getMessage("USER_PHONES"),
					'WORK_FAX' => Loc::getMessage("USER_FAX"),
					'WORK_STREET' => Loc::getMessage("USER_STREET"),
					'WORK_MAILBOX' => Loc::getMessage("USER_MAILBOX"),
					'WORK_CITY' => Loc::getMessage("USER_CITY"),
					'WORK_STATE' => Loc::getMessage("USER_STATE"),
					'WORK_ZIP' => Loc::getMessage("USER_ZIP"),
					'WORK_COUNTRY' => Loc::getMessage("USER_COUNTRY"),
					'WORK_LOGO' => Loc::getMessage("USER_LOGO")
				]
			]
		];
		$props=[];
		$rsData = \CUserTypeEntity::GetList([$by=>$order], ['ENTITY_ID'=>'USER', 'LANG'=>LANGUAGE_ID]);
		while($arRes = $rsData->Fetch()){
			$props[$arRes['FIELD_NAME']]=$arRes['EDIT_FORM_LABEL'];
		}
		if(count($props)>0){
			$listProfile['prop']=[
				'title'=>Loc::getMessage("commerce.loyaltyprogram_PROFCOMPLETED_PROPS"),
				'child'=>$props
			];
		}
		return $listProfile;
	}
	
	private function drawProfile(){
		$checkedFields=!empty($this->profileSetting['settings']['profuile_fields'])?$this->profileSetting['settings']['profuile_fields']:[];
		$listProfile=$this->getUserFields();?>
		<tr class="heading"><td colspan="2"><?=Loc::getMessage("commerce.loyaltyprogram_PROFCOMPLETED_TITLE")?></td></tr><?
		foreach($listProfile as $nextProfile){?>
			<tr><td colspan="2" style="text-align:center"><b><?=$nextProfile['title']?></b></td></tr>
			<?foreach($nextProfile['child'] as $keyChild=>$nextChild){
				$checked=(in_array($keyChild, $checkedFields))?' checked="checked"':'';
				?>
				<tr>
					<td width="50%" class="adm-detail-content-cell-l"><label for="profile_<?=$keyChild?>"><?=$nextChild?></label></td>
					<td width="50%" class="adm-detail-content-cell-r"><input type="checkbox" id="profile_<?=$keyChild?>" name="profile[<?=$keyChild?>]" value="Y"<?=$checked?>></td>
				</tr>
			<?}?>
		<?}
	}

	protected function checkEmailList(){
		$this->profileSetting['email_settings']=empty($this->profileSetting['email_settings'])?[]:$this->profileSetting['email_settings'];
		if(empty($this->profileSetting['email_settings']['COMMERCE_LOYAL_PROFILECOMPLETED'])){
			$this->profileSetting['email_settings']['COMMERCE_LOYAL_PROFILECOMPLETED']=[
				'userTemplate'=>0,
				'refTemplate'=>0
			];
		}
		$this->checkEmailMain();
	}

	protected function mailType($type){
		$mailType = [
			'COMMERCE_LOYAL_PROFILECOMPLETED'=>[
				'LID'=>LANGUAGE_ID,
				'EVENT_NAME'=>'COMMERCE_LOYAL_PROFILECOMPLETED',
				'NAME'=>Loc::getMessage("commerce.loyaltyprogram_EVENT_PROFILECOMPLETED"),
				'DESCRIPTION'=>'
					#EMAIL_TO# - '.Loc::getMessage("commerce.loyaltyprogram_PROFILECOMPLETED_EMAIL_TO").'
					#BONUS# - '.Loc::getMessage("commerce.loyaltyprogram_EVENT_PROFILECOMPLETED_BONUS").'
					#PERCENT# - '.Loc::getMessage("commerce.loyaltyprogram_EVENT_PROFILECOMPLETED_PERCENT").'
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
				'EVENT_NAME'=>'COMMERCE_LOYAL_PROFILECOMPLETED',
				'LID'=>$sites[0],
				'ACTIVE'=>'Y',
				'EMAIL_FROM'=>'#DEFAULT_EMAIL_FROM#',
				'EMAIL_TO'=>'#EMAIL_TO#',
				'SUBJECT'=>'#SITE_NAME#: '.Loc::getMessage("commerce.loyaltyprogram_EVENT_PROFILECOMPLETED_USERBONUSADD"),
				'MESSAGE'=>$this->globalSettings->getEmailTemplate('profcompleted_user'),
				'BODY_TYPE'=>'html'
			],
			'refTemplate'=>[
				'EVENT_NAME'=>'COMMERCE_LOYAL_PROFILECOMPLETED',
				'LID'=>$sites[0],
				'ACTIVE'=>'Y',
				'EMAIL_FROM'=>'#DEFAULT_EMAIL_FROM#',
				'EMAIL_TO'=>'#EMAIL_TO#',
				'SUBJECT'=>'#SITE_NAME#: '.Loc::getMessage("commerce.loyaltyprogram_EVENT_PROFILECOMPLETED_REFBONUSADD"),
				'MESSAGE'=>$this->globalSettings->getEmailTemplate('profcompleted_ref'),
				'BODY_TYPE'=>'html'
			]
		];
		return $mailTemplates[$key];
	}

	private function getUserEmail($userId, $bonus, $percent){
		global $DB;
		$emailSettings=$this->profileSetting['email_settings'];
		if(!empty($emailSettings['COMMERCE_LOYAL_PROFILECOMPLETED']['userTemplate'])){
			$rsEM = \CEventMessage::GetByID($emailSettings['COMMERCE_LOYAL_PROFILECOMPLETED']['userTemplate']);
			if($arEM = $rsEM->Fetch()){
				$sites=array_keys($this->globalSettings->getSites());
				$results=$DB->Query('select * from b_user where id='.$userId);
				$arUser = $results->Fetch();
				if(!empty($arUser['EMAIL'])){
					return [
						"EVENT_NAME" => "COMMERCE_LOYAL_PROFILECOMPLETED",
						"MESSAGE_ID" => $emailSettings['COMMERCE_LOYAL_PROFILECOMPLETED']['userTemplate'],
						"LANGUAGE_ID" => LANGUAGE_ID,
						"LID" => $sites[0],
						"C_FIELDS" => [
							"EMAIL_TO" => $arUser['EMAIL'],
							"BONUS"=>$bonus,
							"PERCENT"=>$percent
						]
					];
				}
			}
		}
		return false;
	}
	
	private function getRefEmail($userId, $bonus, $percent){	
		$emailSettings=$this->profileSetting['email_settings'];
		if(!empty($emailSettings['COMMERCE_LOYAL_PROFILECOMPLETED']['refTemplate'])){
			global $DB;
			$results=$DB->Query('select * from b_user where id='.$userId);
			$arUser = $results->Fetch();
			if(!empty($arUser['EMAIL'])){
				$sites=array_keys($this->globalSettings->getSites());
				if(!isset($this->refTemplate)){
					$rsEM = \CEventMessage::GetByID($emailSettings['COMMERCE_LOYAL_PROFILECOMPLETED']['refTemplate']);
					if($arEM = $rsEM->Fetch()){
						$this->refTemplate=$arEM;
					}else{
						$this->refTemplate=false;
					}
				}
				if($this->refTemplate!==false){
					return [
						"EVENT_NAME" => "COMMERCE_LOYAL_PROFILECOMPLETED",
						"MESSAGE_ID" => $emailSettings['COMMERCE_LOYAL_PROFILECOMPLETED']['refTemplate'],
						"LANGUAGE_ID" => LANGUAGE_ID,
						"LID" => $sites[0],
						"C_FIELDS" => [
							"EMAIL_TO" => $arUser['EMAIL'],
							"BONUS"=>$bonus,
							"PERCENT"=>$percent
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
		$tmpSettings['profuile_fields']=[];
		if(!empty($params['profile'])){
			$tmpSettings['profuile_fields']=array_keys($params['profile']);
		}

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
		$this->registerAgent('completedProfile', 86400);
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
					"bonus_delay"=>"2",
					"bonus_delay_type"=>"day",
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
        $objDate = new \Bitrix\Main\Type\Date();

		$mainParams=[[
            'controlId'=> 'sites',
            'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_orderpay_sites"),
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
        ],[
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
                'controlId'=> 'compProfile',
                'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_compprofile_desc"),
                'group'=> false,
                'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_compprofile"),
                'showIn'=> ['registerbyRef', 'registerbyRefSubGrp', 'registerbyParentRef', 'registerbyParentRefSubGrp'],
                'control'=> [[
                    'id'=> 'prefix',
                    'type'=> 'prefix',
                    'text'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_compprofile")
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
                ],[
                    'type'=> 'input',
                    'id'=> 'completed_profile',
                    'name'=> 'completed_profile',
                    'param_id'=>'n',
                    'show_value'=>'Y',
                    'defaultValue'=>'80',
                    'defaultText'=> ''
                ],
                    Loc::getMessage("commerce.loyaltyprogram_CONDITION_UNIT_PERCENT")
                ]
            ],
            [
                'controlId'=> 'dateRegister',
                'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_dateregister_desc"),
                'group'=> false,
                'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_dateregister"),
                'showIn'=> ['registerbyRef', 'registerbyRefSubGrp', 'registerbyParentRef', 'registerbyParentRefSubGrp'],
                'control'=> [[
                    'id'=> 'prefix',
                    'type'=> 'prefix',
                    'text'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_dateregister")
                ], [
                    'id'=> 'logic',
                    'name'=> 'logic',
                    'type'=> 'select',
                    'values'=> [
                        'more'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_LATER"),
                        'less'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_PREVIOUSLY")
                    ],
                    'defaultText'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_MORE"),
                    'defaultValue'=> 'more'
                ],[
                    'type'=> 'datetime',
                    'id'=> 'date_register',
                    'name'=> 'date_register',
                    'param_id'=>'n',
                    'show_value'=>'Y',
                    'format'=>'date',
                    'defaultValue'=>$objDate->toString(),
                    'defaultText'=> $objDate->toString()
                ]
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

	public function setBonus(){
		if(count($this->profileSetting['settings']['profuile_fields'])==0){
			return false;
		}
		global $DB;

		//all groups
		$groups=[];
		$query='select 
			b_user.id as id,
			GROUP_CONCAT(b_user_group.group_id) as groups
			from b_user left join b_user_group on (b_user_group.user_id=b_user.id)
			group by b_user.id;';
		$rsUsers=$DB->Query($query);
		while ($arUser = $rsUsers->Fetch()) {
			$groups[$arUser['id']]=explode(',',$arUser['groups']);
		}

		//if  already setBonus
		$isAlreadyBonuses=[];
		$rsUsers=$DB->Query('select * from '.$this->globalSettings->getTableBonusList().' where profile_id='.$this->profileSetting['id'].' and user_bonus=0;');
		while ($arUser = $rsUsers->Fetch()){
			$isAlreadyBonuses['!ID'][]=$arUser['user_id'];
		}
		$rsUsers = \CUser::GetList(($by = "ID"), ($order = "asc"), $isAlreadyBonuses, ['SELECT'=>['UF_*'], 'FIELDS'=>[]]);
		$options=$this->globalSettings->getOptions();
        $ranks=$this->ranksObject->getRankUsers();
		while ($nextUser = $rsUsers->Fetch()){
			if(in_array($nextUser['ID'], $isAlreadyBonuses)){
				continue;
			}
            $nextUser['rank']=empty($ranks[$nextUser['ID']])?0:$ranks[$nextUser['ID']];
			$nextUser['GROUPS']=$groups[$nextUser['ID']];
			$nextUser['PERCENT']=$this->getPercent($nextUser);
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
								
								$coeff=$this->getRankCoeff($nextUser['ID']);
								$params['bonus']=$coeff*$params['bonus'];
								if($params['bonus_round']=='more'){
									$params['bonus']=ceil($params['bonus']);
								}elseif($params['bonus_round']=='less'){
									$params['bonus']=floor($params['bonus']);
								}elseif($params['bonus_round']=='auto'){
									$params['bonus']=round($params['bonus']);
								}
								
								$mailTmplt=$this->getUserEmail($nextUser['ID'], $params['bonus'], $nextUser['PERCENT']);
								$mailTmplt=($mailTmplt!=false)?serialize($mailTmplt):'';
								
								$SMSTmplt=$this->getUserSMS($nextUser['id'], $params['bonus'], $nextUser['PERCENT']);
								$SMSTmplt=($SMSTmplt!=false)?serialize($SMSTmplt):'';
								
								$insertArr=[
									'bonus_start'=>(int) $params['bonus'],
									'bonus'=>(int) $params['bonus'],
									'user_id'=>$nextUser['ID'],
									'currency'=>'"'.$options['currency'].'"',
									'profile_type'=>'"'.$this->profileSetting['type'].'"',
									'profile_id'=>$this->profileSetting['id'],
									'status'=>'"inactive"',
									'add_comment'=>'"'.$DB->ForSql(Loc::getMessage("commerce.loyaltyprogram_BONUS_USER_PROFILECOMPLETED", ["#NUM#"=>$params['bonus']])).'"',
									'email'=>"'".$mailTmplt."'",
									'email'=>"'".$SMSTmplt."'"
								];
								
								$startBonus=time(); $endBonus='null';
								if(!empty($params['through_time']) && $params['through_time']=='N'){
									$params['bonus_delay']=0;
								}
								if(!empty($params['bonus_delay']) && $params['bonus_delay']>0){
									$startBonus+=$params['bonus_delay']*$this->timePart[$params['bonus_delay_type']];
								}
								$insertArr['date_add']='FROM_UNIXTIME('.$startBonus.')';

								if(!empty($params['limit_time']) && $params['limit_time']=='N'){
									$params['bonus_live']=0;
								}
								
								//action_id
								if(!empty($params['number_action'])){
									$insertArr['action_id']=$params['number_action'];
								}
								
								if(!empty($params['bonus_live']) && $params['bonus_live']>0){
									$endBonus=$startBonus+$params['bonus_live']*$this->timePart[$params['bonus_live_type']];
									$insertArr['date_remove']='FROM_UNIXTIME('.$endBonus.')';
								}
								if(!$this->isAlreadyRow($insertArr)){
									$DB->Insert($this->globalSettings->getTableBonusList(), $insertArr, $err_mess.__LINE__);
								}
							}
							elseif($nextO['controlId']=='registerbyParentRef'){
								$this->setReferalBonuses($nextUser, $nextO);
							}
						}
					}
				}
			}
		}
	}
	
	private function setReferalBonuses($nextUser, $nextO){
		
		/*if(!empty($this->conditionParameters['levelParent'])){
			$newRew=array_unique($this->conditionParameters['levelParent']);
		}else{
			$rewards=$this->getChainReferal($nextUser['ID']);
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
        $rewards=$this->getChainReferal($nextUser['ID']);

		$newRew=(isset($newRew))?array_unique($newRew):$rewards;
		$params=$nextO['values'];
		if((int) $params['bonus']>0 && count($newRew)>0){
			$options=$this->globalSettings->getOptions();
			global $DB;
			$insertArr=[
				'currency'=>'"'.$options['currency'].'"',
				'user_bonus'=>$nextUser['ID'],
				'profile_type'=>'"'.$this->profileSetting['type'].'"',
				'profile_id'=>$this->profileSetting['id'],
				'status'=>'"inactive"'
			];
			
			//action_id
			if(!empty($params['number_action'])){
				$insertArr['action_id']=$params['number_action'];
			}
			
			$startBonus=time(); $endBonus='null';
			if(!empty($params['through_time']) && $params['through_time']=='N'){
				$params['bonus_delay']=0;
			}
			if(!empty($params['bonus_delay']) && $params['bonus_delay']>0){
				$startBonus+=$params['bonus_delay']*$this->timePart[$params['bonus_delay_type']];
			}
			$insertArr['date_add']='FROM_UNIXTIME('.$startBonus.')';
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
				$insertArr['add_comment']='"'.$DB->ForSql(Loc::getMessage("commerce.loyaltyprogram_BONUS_REF_PROFILECOMPLETED", ["#NUM#"=>$params['bonus']])).'"';
				
				$insertArr['user_id']=$nextRew;
				
				$mailTmplt=$this->getRefEmail($nextRew, $params['bonus'], $nextUser['PERCENT']);
				$mailTmplt=($mailTmplt!=false)?serialize($mailTmplt):'';
				$insertArr['email']="'".$mailTmplt."'";
				
				$SMSTmplt=$this->getRefSMS($nextRew, $params['bonus'], $nextUser['PERCENT']);
				$SMSTmplt=($SMSTmplt!=false)?serialize($SMSTmplt):'';
				$insertArr['sms']="'".$SMSTmplt."'";
				
				if(!$this->isAlreadyRow($insertArr)){
					$DB->Insert($this->globalSettings->getTableBonusList(), $insertArr, $err_mess.__LINE__);
				}
			}
			
		}
	}

	private function getPercent($user){
		$countFields=count($this->profileSetting['settings']['profuile_fields']);
		$filledCount=0;
		foreach($this->profileSetting['settings']['profuile_fields'] as $keyField){
			if(is_array($user[$keyField]) && count($user[$keyField])>0){
				$filledCount++;
			}elseif(!empty($user[$keyField])){
				$filledCount++;
			}
		}
		return round($filledCount*100/$countFields);
	}

	private function checkConditionGroup($group, $user){
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
					$condStatus=$this->checkConditionGroup($nextChildren, $user);
				}else{
					$condStatus=false;
					switch ($nextChildren['controlId']){
						case 'sites':
							$condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $user['LID'], $nextChildren['values']['sites']);
						break;
						case 'groupUsers':
							$condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $user['GROUPS'], $nextChildren['values']['users']);
						break;
                        case 'ranksUser':
                            $condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $user['rank'], $nextChildren['values']['ranks']);
                        break;
						case 'levelParent':
							$refRarentLevel=[];
							$rewards=$this->getChainReferal($user['ID']);
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
						case 'compProfile':
							$condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $user['PERCENT'], $nextChildren['values']['completed_profile']);
						break;
                        case 'dateRegister':
                            if(empty($nextChildren['timestamp'])){
                                $tmpTime=new \Bitrix\Main\Type\Date($nextChildren['values']['date_register']);
                                $currentTime=$tmpTime->getTimestamp();
                            }else{
                                $currentTime=$nextChildren['timestamp'];
                            }
                            $tmpTime=new \Bitrix\Main\Type\DateTime($user['DATE_REGISTER']);
                            $userTime=$tmpTime->getTimestamp();
                            $condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $userTime, $currentTime);
                        break;
                        case 'ranksParentRef':
                            $rewards=$this->getChainReferal($user['ID']);
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
			if($condition=='AND'){
				return true;
			}
			return false;
		}
	}

}