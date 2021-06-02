<?
namespace Commerce\Loyaltyprogram\Profiles;
use \Bitrix\Main\Application,
	Bitrix\Main\Localization\Loc,
	\Commerce\Loyaltyprogram;
	
/**
* type copyrightholder profile
*/

class Copyrighter extends Profile implements Profileinterface{
	
	function __construct(){
		parent::__construct();
		$this->profileSetting['type']='Copyrighter';
		$this->moduleOptions=$this->globalSettings->getOptions();
		$this->profileSetting['settings']['prop_copyright']=empty($this->profileSetting['settings']['prop_copyright'])?0:$this->profileSetting['settings']['prop_copyright'];
	}
	
	public function getParametersMain(){
		foreach(['type', 'profileActive', 'profileName', 'propCopyright', 'baseCalculate', 'profileSort'] as $nextRow){
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
		$tmpSettings=[];
		
		if(!empty($params['prop_copyright'])){
			$tmpSettings['prop_copyright']=$params['prop_copyright'];
		}
		
		if(!empty($params['base_calculate'])){
			$tmpSettings['base_calculate']=$params['base_calculate'];
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
		$this->registerEvent('sale', 'OnSaleOrderSaved', 'copyrightBonusAdd');
		$this->registerEvent('sale', 'OnSaleOrderCanceled', 'AfterOrderCancel');
		$this->registerEvent('sale', 'OnSalePaymentEntitySaved', 'AfterOrderInnerPaymentRefund');
		return $id;
	}
	
	private function getCopyrightProducts(){
		if(!isset($this->copyrightProducts)){
			$this->copyrightProducts=[];
			if(!empty($this->profileSetting['settings']['prop_copyright'])){
				if(!empty($this->products) && count($this->products)>0){
					\Bitrix\Main\Loader::includeModule('iblock');
					$dbEl = \CIBlockElement::GetList([], ["ID"=>$this->products]);
					while($obEl = $dbEl->GetNextElement()){
						$fields=$obEl->GetFields();
						$props = $obEl->GetProperties(false, ['ID'=>$this->profileSetting['settings']['prop_copyright']]);
						$prop=current($props);
						if($prop!=false && !empty($prop['VALUE'])){
							$this->copyrightProducts[$fields['ID']]=$prop['VALUE'];
						}
					}
				}
			}
		}
		return $this->copyrightProducts;
	}
	
	public function setBonus($event=false, $debug=false){
		if(!empty($this->profileSetting['settings']["condition"]["children"]) && $event!==false){
			
			if($debug){
				$order=$event;//for test!!!
			}else{
				$order = $event->getParameter("ENTITY");
			}
			$this->order=$order;
			$basket = $this->order->getBasket();
			$this->basket=$basket;
			foreach ($basket as $basketItem){
				$this->products[]=$basketItem->getProductId();
				
				//base calculate
				if(!empty($this->profileSetting['settings']['base_calculate']) && $this->profileSetting['settings']['base_calculate']=='N'){
					$tmpPrice=$basketItem->getBasePrice()*$basketItem->getQuantity();
				}else{
					$tmpPrice=$basketItem->getPrice()*$basketItem->getQuantity();
				}
				
				$this->productsInfo[$basketItem->getProductId()]=[
					'PRICE'=>$tmpPrice,
					'NAME'=>$basketItem->getField('NAME')
				];
			}
			$copyrightProducts=$this->getCopyrightProducts();
			if(count($copyrightProducts)==0){
				return false;
			}
			$this->userId=$order->getUserId();
			$this->setBonus=false;
			
			foreach($this->profileSetting['settings']["condition"]["children"] as $nextO){
				if(!isset($nextO['children'])){
					continue;
				}else{
					$this->conditionParameters=[];
					$status=$this->checkConditionGroup($nextO);
					if($status){
						$cCopyrightProducts=$copyrightProducts;
						
						$skipItems=[];
						if(!empty($this->conditionParameters['discountProducts'])){
							$skipItems=array_merge($skipItems, $this->conditionParameters['discountProducts']);
						}
						if(!empty($this->conditionParameters['skipPriceProduct'])){
							$skipItems=array_merge($skipItems, $this->conditionParameters['skipPriceProduct']);
						}
						if(!empty($this->conditionParameters['sectionProducts'])){
							$skipItems=array_merge($skipItems, $this->conditionParameters['sectionProducts']);
						}
						if(count($skipItems)>0){
							$skipItems=array_unique($skipItems);
							foreach($skipItems as $nextItem){
								unset($cCopyrightProducts[$nextItem]);
							}
						}
						if(count($cCopyrightProducts)==0){
							return false;
						}
						
						global $DB;
						$params=$nextO['values'];
						if(!empty($params['bonus']) && (int) $params['bonus']>0){
							foreach($cCopyrightProducts as $cProduct=>$cUser){
								
								$coeff=$this->getRankCoeff($cUser);
								$params['bonus']=$coeff*$params['bonus'];
					
								$fields['bonus']=$params['bonus'];
								$fields['user_id']=$cUser;
								$fields['order_id']=$this->order->getId();
								$fields['currency']='"'.$this->order->getCurrency().'"';
								$fields['profile_type']='"'.$this->profileSetting['type'].'"';
								$fields['profile_id']=$this->profileSetting['id'];
								$fields['status']='"inactive"';
								if($params['bonus_unit']=='percent'){
									$price = $this->productsInfo[$cProduct]['PRICE'];
									$fields['bonus']=$fields['bonus']*$price/100;
								}
								
								if($params['bonus_round']=='more'){
									$fields['bonus']=ceil($fields['bonus']);
								}elseif($params['bonus_round']=='less'){
									$fields['bonus']=floor($fields['bonus']);
								}elseif($params['bonus_round']=='auto'){
									$fields['bonus']=round($fields['bonus']);
								}else{
									$fields['bonus']=$fields['bonus'];
								}
								
								if(!empty($params['allow_bonus_max']) && $params['allow_bonus_max']=='N'){
									$params['bonus_max']=0;
								}
								if(!empty($params['bonus_max']) && (int) $params['bonus_max']>0 && $params['bonus_max']<$fields['bonus']){
									$fields['bonus']=(int) $params['bonus_max'];
								}
								if($fields['bonus']>0){
									$this->setBonus=true;
									$fields['bonus_start']=$fields['bonus'];
									$mailTmplt=$this->getUserEmail($cUser, $fields['bonus'], $cProduct);
									$mailTmplt=($mailTmplt!=false)?serialize($mailTmplt):'';
									$fields['email']="'".$mailTmplt."'";
									
									$SMSTmplt=$this->getUserSMS($cUser, $fields['bonus']);
									$SMSTmplt=($SMSTmplt!=false)?serialize($SMSTmplt):'';
									$fields['sms']="'".$SMSTmplt."'";
									
									$fields['add_comment']='"'.$DB->ForSql(Loc::getMessage("commerce.loyaltyprogram_BONUS_COPYRIGHT_BONUS_ADD", ["#NUM#"=>$fields['bonus'], '#PRODUCT_ID#'=>$cProduct])).'"';
									
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
									
									if(!$this->isAlreadyRow($fields)){
										$idIns=$DB->Insert($this->globalSettings->getTableBonusList(), $fields, $err_mess.__LINE__);
										if($startBonus==$currentTime){
											Loyaltyprogram\Eventmanager::manageBonuses($idIns);
										}
									}
								}
							}
						}
					}
				}
			}
			return $this->setBonus;
		}
		return false;
	}
	
	/**
	* send info set
	*/
	
	protected function checkSMSList(){
		$this->profileSetting['sms_settings']=empty($this->profileSetting['sms_settings'])?[]:$this->profileSetting['sms_settings'];
		if(empty($this->profileSetting['sms_settings']['COMMERCE_LOYAL_COPYRIGHTER_SMS'])){
			$this->profileSetting['sms_settings']['COMMERCE_LOYAL_COPYRIGHTER_SMS']=[
				'copyrighter'=>0
			];
		}
		$this->checkSMSMain();
	}
	
	protected function SMSType($type){
		$SMSType = [
			'COMMERCE_LOYAL_COPYRIGHTER_SMS'=>[
				'LID'=>LANGUAGE_ID,
				'EVENT_NAME'=>'COMMERCE_LOYAL_COPYRIGHTER_SMS',
				'EVENT_TYPE'  => 'sms',
				'NAME'=>Loc::getMessage("commerce.loyaltyprogram_SMS_BONUSCOPYRIGHTADD"),
				'DESCRIPTION'=>Loc::getMessage("commerce.loyaltyprogram_SMS_BONUSCOPYRIGHTADD_DESC")
			]
		];
		return $SMSType[$type];
	}
	
	protected function SMSTemplates($key){
		$sites=array_keys($this->globalSettings->getSites());
		$smsTemplates = [
			'copyrighter'=>[
				'EVENT_NAME'=>'COMMERCE_LOYAL_COPYRIGHTER_SMS',
				"ACTIVE" => false,
				"SENDER" => "#DEFAULT_SENDER#",
				"RECEIVER" => "#USER_PHONE#",
				"MESSAGE" => GetMessage("commerce.loyaltyprogram_SMS_BONUSCOPYRIGHTADD_USERBONUSADD")
			]
		];
		return $smsTemplates[$key];
	}
	
	private function getUserSMS($userId, $bonus){
		$SMSSettings=$this->profileSetting['sms_settings'];
		if(!empty($SMSSettings['COMMERCE_LOYAL_COPYRIGHTER_SMS']['copyrighter'])){
			$userData=Loyaltyprogram\Tools::getUserData($userId);
			if(!empty($userData['PERSONAL_PHONE'])){
				$tmpPhone=$userData['PERSONAL_PHONE'];
			}elseif(!empty($userData['PERSONAL_MOBILE'])){
				$tmpPhone=$userData['PERSONAL_PHONE'];
			}
			if(!empty($tmpPhone)){
				$tmpPhone=\Bitrix\Main\UserPhoneAuthTable::normalizePhoneNumber($tmpPhone);
				return [
					"EVENT_NAME" => "COMMERCE_LOYAL_COPYRIGHTER_SMS",
					"MESSAGE_ID" => $SMSSettings['COMMERCE_LOYAL_COPYRIGHTER_SMS']['copyrighter'],
					"LID" => $this->order->getField('LID'),
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
		if(empty($this->profileSetting['email_settings']['COMMERCE_LOYAL_COPYRIGHTER'])){
			$this->profileSetting['email_settings']['COMMERCE_LOYAL_COPYRIGHTER']=[
				'copyrighter'=>0
			];
		}
		$this->checkEmailMain();
	}
	
	protected function mailType($type){
		$mailType = [
			'COMMERCE_LOYAL_COPYRIGHTER'=> array(
				'LID'=>LANGUAGE_ID,
				'EVENT_NAME'=>'COMMERCE_LOYAL_COPYRIGHTER',
				'NAME'=>Loc::getMessage("commerce.loyaltyprogram_EVENT_BONUSCOPYRIGHTADD"),
				'DESCRIPTION'=>'
					#EMAIL_TO# - '.Loc::getMessage("commerce.loyaltyprogram_EVENT_BONUSCOPYRIGHTADD_EMAIL_TO").'
					#BONUS# - '.Loc::getMessage("commerce.loyaltyprogram_EVENT_BONUSCOPYRIGHTADD_BONUS").'
					#USER_ID# - '.Loc::getMessage("commerce.loyaltyprogram_EVENT_BONUSCOPYRIGHTADD_USER_ID").'
					#PRODUCT_ID# - '.Loc::getMessage("commerce.loyaltyprogram_EVENT_BONUSCOPYRIGHTADD_PRODUCT_ID").'
					#PRODUCT_NAME# - '.Loc::getMessage("commerce.loyaltyprogram_EVENT_BONUSCOPYRIGHTADD_PRODUCT_NAME").'
				',
				'SORT'=>500
            )
		];
		return $mailType[$type];
	}
	
	protected function mailTemplates($key){
		$sites=array_keys($this->globalSettings->getSites());
		$mailTemplates = [
			'copyrighter'=>[
				'EVENT_NAME'=>'COMMERCE_LOYAL_COPYRIGHTER',
				'LID'=>$sites[0],
				'ACTIVE'=>'Y',
				'EMAIL_FROM'=>'#DEFAULT_EMAIL_FROM#',
				'EMAIL_TO'=>'#EMAIL_TO#',
				'SUBJECT'=>'#SITE_NAME#: '.Loc::getMessage("commerce.loyaltyprogram_EVENT_BONUSCOPYRIGHTADD_USERBONUSADD"),
				'MESSAGE'=>$this->globalSettings->getEmailTemplate('ordering_copyright'),
				'BODY_TYPE'=>'html'
			]
		];
		return $mailTemplates[$key];
	}
	
	private function getUserEmail($userId, $bonus, $idProduct){
		$emailSettings=$this->profileSetting['email_settings'];
		if(!empty($emailSettings['COMMERCE_LOYAL_COPYRIGHTER']['copyrighter'])){
			$rsEM = \CEventMessage::GetByID($emailSettings['COMMERCE_LOYAL_COPYRIGHTER']['copyrighter']);
			if($arEM = $rsEM->Fetch()){
				$sites=array_keys($this->globalSettings->getSites());
				global $DB;
				$results=$DB->Query('select * from b_user where id='.$userId);
				$arUser = $results->Fetch();
				
				if(!empty($arUser['EMAIL'])){
					return [
						"EVENT_NAME" => "COMMERCE_LOYAL_COPYRIGHTER",
						"MESSAGE_ID" => $emailSettings['COMMERCE_LOYAL_COPYRIGHTER']['copyrighter'],
						"LANGUAGE_ID" => LANGUAGE_ID,
						"LID" => $sites[0],
						"C_FIELDS" => [
							"EMAIL_TO" => $arUser['EMAIL'],
							"BONUS"=>strval($bonus),
							"USER_ID" => $userId,
							"PRODUCT_ID" => $idProduct,
							"PRODUCT_NAME" => $this->productsInfo[$idProduct]['NAME']
						]
					];
				}
			}
		}
		return false;
	}
	
	/**
	* condition set
	*/
	
	private function getDiscountProducts(){
		if(!isset($this->discountProducts)){
			$basket = $this->order->getBasket();
			$discounts = \Bitrix\Sale\Discount::buildFromOrder($this->order, new \Bitrix\Sale\Discount\Context\Fuser($basket->getFUserId(true)));
			$discounts->calculate();
			$result = $discounts->getApplyResult(true);
			$items = $basket->getOrderableItems();
			$this->discountProducts=[];
			foreach ($items as $item){
				$basketCode = $item->getBasketCode();
				if(isset($result['RESULT']['BASKET'][$basketCode])){
					$this->discountProducts[]=$item->getProductId();
				}elseif($item->getBasePrice()>$item->getPrice()){
					$this->discountProducts[]=$item->getProductId();
				}
			}
		}
		return $this->discountProducts;
	}
	
	/**
	* the function returns an array of keys that occur in all nested arrays
	*/
	/*private function clearOrArray($arr){
		$clearArr=[];
		foreach($arr as $innerArr){
			foreach($innerArr as $nextKey){
				if(!in_array($nextKey, $clearArr)){
					$keyCount=0;
					foreach($arr as $inner2Arr){
						if(in_array($nextKey,  $inner2Arr)){
							$keyCount++;
						}
					}
					if($keyCount==count($arr)){$clearArr[]=$nextKey;}
				}
			}
		}
		return $clearArr;
	}*/
	
	private function getSections(){
		if(!isset($this->sectionsProduct)){
			$this->sectionsProduct=[];
			if(count($this->products)>0){
				\Bitrix\Main\Loader::includeModule('iblock');
				$offers=\CCatalogSKU::getProductList($this->products, 0);
				foreach($this->products as $nextProduct){
					$this->sectionsProduct[$nextProduct]=[];
					if(!empty($offers[$nextProduct])){
						$this->sectionsProduct[$offers[$nextProduct]['ID']]=[];
					}
				}
				$db_old_groups = \CIBlockElement::GetElementGroups(array_keys($this->sectionsProduct), true);
				while($ar_group = $db_old_groups->Fetch()){
					$this->sectionsProduct[$ar_group['IBLOCK_ELEMENT_ID']][] = $ar_group["ID"];
					$resSection=\CIBlockSection::GetNavChain(false, $ar_group["ID"]);
					while ($arSection = $resSection->GetNext()) {
						$this->sectionsProduct[$ar_group['IBLOCK_ELEMENT_ID']][]=$arSection['ID'];
					}
					$this->sectionsProduct[$ar_group['IBLOCK_ELEMENT_ID']]=array_unique($this->sectionsProduct[$ar_group['IBLOCK_ELEMENT_ID']]);
				}
				if(count($offers)>0){
					foreach($offers as $keyOffer=>$nextOffer){
						$this->sectionsProduct[$keyOffer]=$this->sectionsProduct[$nextOffer['ID']];
						unset($this->sectionsProduct[$nextOffer['ID']]);
					}
				}
			}
		}
		return $this->sectionsProduct;
	}
	
	private function getAllProducts(){
		if(!isset($this->productsWithOffers)){
			$this->productsWithOffers=[];
			if(count($this->products)>0){
				$this->productsWithOffers=$this->products;
				$offers=\CCatalogSKU::getProductList($this->products, 0);
				if(count($offers)>0){
					foreach($offers as $nextOffer){
						$this->productsWithOffers[]=$nextOffer['ID'];
					}
					$this->productsWithOffers=array_unique($this->productsWithOffers);
				}
			}
		}
		return $this->productsWithOffers;
	}
	
	protected function getStartCondition($mode=''){
		$params=[
			"id"=>"0",
			"controlId"=>"CondGroup",
			"children"=>[[
				"id"=>"0",
				"controlId"=>"copyrighter",
				"values"=>[
					"bonus"=>"10",
					"bonus_unit"=>"0",
					"bonus_delay"=>"2",
					"bonus_delay_type"=>"day",
					"allow_bonus_max"=>"Y",
					"through_time"=>"Y",
					"limit_time"=>"Y",
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
		
		$basketRules=$this->globalSettings->getBasketRules();
		$orderStatuses=[];
		foreach($this->globalSettings->getOrderStatuses() as $nextOrder){
			$orderStatuses[$nextOrder['STATUS_ID']]=$nextOrder['NAME'].' ['.$nextOrder['STATUS_ID'].']';
		}
		
		$paySystems=[];
		foreach($this->globalSettings->getPaySystems() as $nextPaySystems){
			$paySystems[$nextPaySystems['ID']]=$nextPaySystems['NAME'].' ['.$nextPaySystems['ID'].']';
		}
		
		$delyvery=[];
		foreach($this->globalSettings->getDelivery() as $nextDelivery){
			$delyvery[$nextDelivery['ID']]=$nextDelivery['NAME'].' ['.$nextDelivery['ID'].']';
		}
		
		$personTypes=$this->globalSettings->getPersonTypes();
		
		$optns=$this->globalSettings->getOptions();

        $mainParams=[[
            'controlId'=> 'sites',
            'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_ordering_sites"),
            'group'=> false,
            'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_sites"),
            'showIn'=> ['copyrighter', 'copyrighterSubGrp'],
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
            'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_copyright_GroupRef"),
            'group'=> false,
            'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_GroupCopyright"),
            'showIn'=> ['copyrighter', 'copyrighterSubGrp'],
            'control'=> [[
                'id'=> 'prefix',
                'type'=> 'prefix',
                'text'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_GroupCopyright")
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
                'showIn'=> ['copyrighter', 'copyrighterSubGrp'],
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
				'controlId'=> 'copyrighter',
				'group'=> true,
				'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_registerbyCopyrighter"),
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
					Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_registerbyCopyrighter"),
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
					]
				],
				'mess'=> [
					'ADD_CONTROL'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_ADD_CONTROL"),
					'SELECT_CONTROL'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_SELECT_CONTROL")
				]
			],
			[
				'controlId'=> 'copyrighterSubGrp',
				'group'=> true,
				'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_GROUP_CONTROL"),
				'defaultText'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_SELECT_VALUE"),
				'showIn'=> ['copyrighter'],
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
				'showIn'=> ['copyrighter', 'copyrighterSubGrp'],
				'children'=> $mainParams
			],
			[
				'controlgroup'=> true,
				'group'=> false,
				'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_ShopParameters"),
				'showIn'=> ['copyrighter', 'copyrighterSubGrp'],
				'children'=> [[
						'controlId' => 'orderPriceWithoutDiscount',
						'description' => Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_ordering_order_price_without_discount"),
						'group' => false,
						'label' => Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_ORDER_PRICE_WITHOUT_DISCOUNT"),
						'showIn' => ['copyrighter', 'copyrighterSubGrp'],
						'control' => [[
							'id' => 'prefix',
							'type' => 'prefix',
							'text' => Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_ORDER_PRICE_WITHOUT_DISCOUNT")
						], [
							'id' => 'logic',
							'name' => 'logic',
							'type' => 'select',
							'values' => [
								'more' => Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_MORE"),
								'less' => Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_LESS")
							],
							'defaultText' => Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_MORE"),
							'defaultValue' => 'more'
						],
							[
								'type' => 'input',
								'id' => 'order_price_without_discount',
								'name' => 'order_price_without_discount',
								'param_id' => 'n',
								'show_value' => 'Y',
								'defaultValue' => '100'
							],
							$optns['currency']
						]
					],[
					'controlId'=> 'skipDiscountProduct',
					'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_SKIP_hint_discount_product"),
					'group'=> false,
					'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_SKIP_DISCOUNT_PRODUCT"),
					'showIn'=> ['copyrighter', 'copyrighterSubGrp'],
					'control'=> [[
						'id'=> 'prefix',
						'type'=> 'prefix',
						'text'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_SKIP_DISCOUNT_PRODUCT")
					],[
						'type'=> 'select',
						'multiple'=>'Y',
						'values'=> [
							'yes'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_YES")
						],
						'id'=> 'skip_discount_product',
						'name'=> 'skip_discount_product',
						'first_option'=> '...',
						'defaultText'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_YES"),
						'defaultValue'=> 'yes'
					]]
				],[
					'controlId'=> 'skipPriceProduct',
					'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_hint_skip_price_product"),
					'group'=> false,
					'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_SKIP_PRICE_PRODUCT"),
					'showIn'=> ['copyrighter', 'copyrighterSubGrp'],
					'control'=> [[
						'id'=> 'prefix',
						'type'=> 'prefix',
						'text'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_SKIP_PRICE_PRODUCT")
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
						'id'=> 'skip_price_product',
						'name'=> 'skip_price_product',
						'param_id'=>'n',
						'show_value'=>'Y',
						'defaultValue'=>'100',
						'defaultText'=> ''
					],
					$optns['currency']
					]
				],[
					'controlId'=> 'product',
					'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_ordering_product"),
					'group'=> false,
					'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_PRODUCT"),
					'showIn'=> ['copyrighter', 'copyrighterSubGrp'],
					'control'=> [[
						'id'=> 'prefix',
						'type'=> 'prefix',
						'text'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_PRODUCT")
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
						'type'=>'dialog',
						//'type'=>'multiDialog',
						'popup_url'=>'/bitrix/tools/sale/product_search_dialog.php',
						//'popup_url'=>'cat_product_search_dialog.php',
						'popup_params'=> [
							'lang'=> LANGUAGE_ID,
							'caller'=> 'discount_rules'
						],
						'param_id'=>'n',
						'show_value'=>'Y',
						//'multiple'=>'Y',
						'id'=>'product',
						'name'=>'product'
					]]
				],[
					'controlId'=> 'productCat',
					'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_ordering_product_cat"),
					'group'=> false,
					'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_PRODUCT_CAT"),
					'showIn'=> ['copyrighter', 'copyrighterSubGrp'],
					'control'=> [[
						'id'=> 'prefix',
						'type'=> 'prefix',
						'text'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_PRODUCT_CAT")
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
						'type'=>'popup',
						//'type'=>'multiDialog',
						'popup_url'=>'iblock_section_search.php',
						//'popup_url'=>'cat_product_search_dialog.php',
						'popup_params'=> [
							'lang'=> LANGUAGE_ID,
							'caller'=> 'discount_rules'
						],
						'param_id'=>'n',
						'multiple'=>'Y',
						'show_value'=>'Y',
						'id'=>'product_cat',
						'name'=>'product_cat'
					]]
				],[
					'controlId'=> 'discount',
					'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_ordering_discount"),
					'group'=> false,
					'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_DISCOUNT"),
					'showIn'=> ['copyrighter', 'copyrighterSubGrp'],
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
						//'defaultText'=> $firstBasketRule,
						//'defaultValue'=> $firstBasketRuleKey
						'first_option'=> '...',
						'defaultText'=> '...',
						'defaultValue'=> ''
					]]
				],[
					'controlId'=> 'orderPrice',
					'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_ordering_order_price"),
					'group'=> false,
					'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_ORDER_PRICE"),
					'showIn'=> ['copyrighter', 'copyrighterSubGrp'],
					'control'=> [[
						'id'=> 'prefix',
						'type'=> 'prefix',
						'text'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_ORDER_PRICE")
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
						'id'=> 'order_price',
						'name'=> 'order_price',
						'param_id'=>'n',
						'show_value'=>'Y',
						'defaultValue'=>'100'
					],
					$optns['currency']
					]
				],[
					'controlId'=> 'orderStatuses',
					'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_ordering_orderstatuses"),
					'group'=> false,
					'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_ORDERSTATUSES"),
					'showIn'=> ['copyrighter', 'copyrighterSubGrp'],
					'control'=> [[
						'id'=> 'prefix',
						'type'=> 'prefix',
						'text'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_ORDERSTATUSES")
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
						'values'=> $orderStatuses,
						'id'=> 'order_statuses',
						'name'=> 'order_statuses',
						'first_option'=> '...',
						'defaultText'=> '...',
						'defaultValue'=> ''
					]]
				],[
					'controlId'=> 'paySystems',
					'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_ordering_paysystems"),
					'group'=> false,
					'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_PAYSYSTEMS"),
					'showIn'=> ['copyrighter', 'copyrighterSubGrp'],
					'control'=> [[
						'id'=> 'prefix',
						'type'=> 'prefix',
						'text'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_PAYSYSTEMS")
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
						'values'=> $paySystems,
						'id'=> 'pay_systems',
						'name'=> 'pay_systems',
						'first_option'=> '...',
						'defaultText'=> '...',
						'defaultValue'=> ''
					]]
				],[
					'controlId'=> 'delyvery',
					'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_ordering_delyvery"),
					'group'=> false,
					'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_DELYVERY"),
					'showIn'=> ['copyrighter', 'copyrighterSubGrp'],
					'control'=> [[
						'id'=> 'prefix',
						'type'=> 'prefix',
						'text'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_DELYVERY")
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
						'values'=> $delyvery,
						'id'=> 'delyvery',
						'name'=> 'delyvery',
						'first_option'=> '...',
						'defaultText'=> '...',
						'defaultValue'=> ''
					]]
				],[
					'controlId'=> 'personTypes',
					'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_ordering_persontypes"),
					'group'=> false,
					'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_PERSONTYPES"),
					'showIn'=> ['copyrighter', 'copyrighterSubGrp'],
					'control'=> [[
						'id'=> 'prefix',
						'type'=> 'prefix',
						'text'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_PERSONTYPES")
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
						'values'=> $personTypes,
						'id'=> 'person_types',
						'name'=> 'person_types',
						'first_option'=> '...',
						'defaultText'=> '...',
						'defaultValue'=> ''
					]]
				]]
			]
		];

		$params[]=[
			'controlId'=> 'CondGroup',
			'group'=> true,
			'label'=> '',
			'defaultText'=> '',
			'showIn'=> [],
			'control'=> [Loc::getMessage("commerce.loyaltyprogram_CONDITION_PERFORM_OPERATIONS")]
		];
		if($mode=='json'){
			return \Bitrix\Main\Web\Json::encode($params);
		}
		return $params;
	}
	
	private function checkConditionGroup($group){
		if(empty($group['children'])){
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
							$condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $this->order->getSiteId(), $nextChildren['values']['sites']);
						break;
						case 'groupUsers':
							$parentRefCroup=\CUser::GetUserGroup($this->userId);
							$condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $parentRefCroup, $nextChildren['values']['users']);
						break;
                        case 'ranksUser':
                            if(empty($this->userId)){
                                $rank=0;
                            }else{
                                $rank=$this->ranksObject->getRankUser($this->userId);
                            }
                            $condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $rank, $nextChildren['values']['ranks']);
                        break;
						case 'orderPriceWithoutDiscount':
							$discountProducts=$this->getDiscountProducts();
							$basketPrice = $this->basket->getPrice();
							if (count($discountProducts) > 0) {
								foreach ($this->basket as $basketItem) {
									if (in_array($basketItem->getProductId(), $discountProducts)) {
										$basketPrice -= $basketItem->getPrice() * $basketItem->getQuantity();
									}
								}
							}
							$condStatus = $this->checkConditionChildren($nextChildren['values']['logic'], $basketPrice, $nextChildren['values']['order_price_without_discount']);
						break;
						case 'skipDiscountProduct':
							$discountProducts=$this->getDiscountProducts();
							if(count($discountProducts)>0){
								foreach ($this->basket as $basketItem){
									if(in_array($basketItem->getProductId(), $discountProducts)){
										$this->conditionParameters['discountProducts'][]=$basketItem->getProductId();
									}
								}
							}
							$condStatus=true;
							$condStatusProps=true;
							if($condition=='ANDNOT' || $condition=='OR'){
								$condStatus=false;
							}
						break;
						case 'skipPriceProduct':
							$logic=($nextChildren['values']['logic']=='Equal')?'less':$nextChildren['values']['logic'];
							foreach ($this->basket as $basketItem){
								
								if(
									($basketItem->getPrice()<$nextChildren['values']['skip_price_product'] && $logic=='less') ||
									($basketItem->getPrice()>=$nextChildren['values']['skip_price_product'] && $logic=='more')
								){
									$this->conditionParameters['skipPriceProduct'][]=$basketItem->getProductId();
								}
							}
							$condStatus=true;
							$condStatusProps=true;
							if($condition=='ANDNOT' || $condition=='OR'){
								$condStatus=false;
							}
						break;
						case 'product':
							$condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $this->getAllProducts(), $nextChildren['values']['product']);
						break;
						case 'productCat':
							$logic=$nextChildren['values']['logic'];
							foreach ($this->basket as $basketItem){
								$nextProduct=$basketItem->getProductId();
								$sections=$this->getSections();
								if(
									($logic=='Not' && in_array($nextChildren['values']['product_cat'], $sections[$nextProduct])) ||
									($logic=='Equal' && !in_array($nextChildren['values']['product_cat'], $sections[$nextProduct]))
								){
									$this->conditionParameters['sectionProducts'][]=$nextProduct;
								}elseif(//remove product from exception list
									($logic=='Not' && !in_array($nextChildren['values']['product_cat'], $sections[$nextProduct]) && $condition=='OR') ||
									($logic=='Equal' && in_array($nextChildren['values']['product_cat'], $sections[$nextProduct]) && $condition=='OR')
								){
									$this->conditionParameters['sectionProductsNot'][]=$nextProduct;
								}
							}
							$condStatus=true;
							$condStatusProps=true;
							if($condition=='ANDNOT' || $condition=='OR'){
								$condStatus=false;
							}
						break;
						case 'discount':
							$discountData=$this->order->getDiscount()->getApplyResult();
							$discountList=[];
							if(!empty($discountData["DISCOUNT_LIST"]) && count($discountData["DISCOUNT_LIST"])>0){
								foreach($discountData["DISCOUNT_LIST"] as $nextDiscount){
									if(!empty($nextDiscount['REAL_DISCOUNT_ID'])){
										$discountList[]=$nextDiscount['REAL_DISCOUNT_ID'];
									}
								}
							}
							$condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], array_unique($discountList), $nextChildren['values']['discount']);
						break;
						case 'orderPrice':
							$condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $this->basket->getPrice(), $nextChildren['values']['order_price']);
						break;
						case 'orderStatuses':
							$condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $this->order->getField("STATUS_ID"), $nextChildren['values']['order_statuses']);
						break;
						case 'paySystems':
							$paymentCollection = $this->order->getPaymentCollection();
							$currentPays=[];
							foreach ($paymentCollection as $payment) {
								$currentPays[]=$payment->getPaymentSystemId();
							}
							$condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $currentPays, $nextChildren['values']['pay_systems']);
						break;
						case 'delyvery':
							$condStatus=$this->checkConditionChildren($nextChildren['values']['logic'],  $this->order->getDeliverySystemId(), $nextChildren['values']['delyvery']);
						break;
						case 'personTypes':
							$condStatus=$this->checkConditionChildren($nextChildren['values']['logic'],  $this->order->getPersonTypeId(), $nextChildren['values']['person_types']);
						break;
					}
				}
				//remove from exception lists
				if(
					!empty($this->conditionParameters['sectionProductsNot'])
					&& !empty($this->conditionParameters['sectionProducts'])
					&& count($this->conditionParameters['sectionProductsNot'])>0
					&& count($this->conditionParameters['sectionProducts'])>0
				){
					$this->conditionParameters['sectionProducts']=array_diff($this->conditionParameters['sectionProducts'], $this->conditionParameters['sectionProductsNot']);
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