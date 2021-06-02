<?
namespace Commerce\Loyaltyprogram\Profiles;
use Bitrix\Main\Application,
	Bitrix\Main\Localization\Loc,
	Commerce\Loyaltyprogram\Tools;
/**
* type orderpay
*/
class Orderpay extends Profile implements Profileinterface{
	
	function __construct(){
		parent::__construct();
		$this->profileSetting['type']='Orderpay';
		$this->userId=0;
		$this->order=false;
		$this->userEmail='';
		$this->currentBudget=0;
		$this->siteId='';
		$options=$this->globalSettings->getOptions();
		$this->currency=$options['currency'];
		$this->profileSetting['settings']['withdraw']=(!empty($this->profileSetting['settings']['withdraw']))?$this->profileSetting['settings']['withdraw']:0;
		$this->profileSetting['settings']['withdraw_max']=(!empty($this->profileSetting['settings']['withdraw_max']))?$this->profileSetting['settings']['withdraw_max']:0;
	}
	
	public function getParametersMain(){
		foreach(['type', 'profileActive', 'profileName', 'profileSort'] as $nextRow){
			$this->drawRow($nextRow);
		}
	}
	
	protected function checkSMSList(){
		$this->profileSetting['sms_settings']=empty($this->profileSetting['sms_settings'])?[]:$this->profileSetting['sms_settings'];
		if(empty($this->profileSetting['sms_settings']['COMMERCE_LOYAL_ORDERPAY_SMS'])){
			$this->profileSetting['sms_settings']['COMMERCE_LOYAL_ORDERPAY_SMS']=[
				'userTemplate'=>0
			];
		}
		$this->checkSMSMain();
	}
	
	protected function SMSType($type){
		$SMSType = [
			'COMMERCE_LOYAL_ORDERPAY_SMS'=>[
				'LID'=>LANGUAGE_ID,
				'EVENT_NAME'=>'COMMERCE_LOYAL_ORDERPAY_SMS',
				'EVENT_TYPE'  => 'sms',
				'NAME'=>Loc::getMessage("commerce.loyaltyprogram_SMS_ORDERPAY"),
				'DESCRIPTION'=>Loc::getMessage("commerce.loyaltyprogram_SMS_ORDERPAY_DESC")
			]
		];
		return $SMSType[$type];
	}
	
	protected function SMSTemplates($key){
		$sites=array_keys($this->globalSettings->getSites());
		$smsTemplates = [
			'userTemplate'=>[
				'EVENT_NAME'=>'COMMERCE_LOYAL_ORDERPAY_SMS',
				"ACTIVE" => false,
				"SENDER" => "#DEFAULT_SENDER#",
				"RECEIVER" => "#USER_PHONE#",
				"MESSAGE" => GetMessage("commerce.loyaltyprogram_SMS_ORDERPAY_USERBONUSPAY")
			]
		];
		return $smsTemplates[$key];
	}
	
	public function sendSMS($bonus=0){
		$SMSSettings=$this->profileSetting['sms_settings'];
		if(!empty($SMSSettings['COMMERCE_LOYAL_ORDERPAY_SMS']['userTemplate']) && $bonus>0){
			$userData=Tools::getUserData($this->userId);
			if(!empty($userData['PERSONAL_PHONE'])){
				$tmpPhone=$userData['PERSONAL_PHONE'];
			}elseif(!empty($userData['PERSONAL_MOBILE'])){
				$tmpPhone=$userData['PERSONAL_PHONE'];
			}
			if(!empty($tmpPhone)){
				$tmpPhone=\Bitrix\Main\UserPhoneAuthTable::normalizePhoneNumber($tmpPhone);
				
				$sms = new \Bitrix\Main\Sms\Event(
					'COMMERCE_LOYAL_ORDERPAY_SMS',
					[
						"USER_PHONE" => $tmpPhone,
						"BONUS"=>strval($bonus)
					]
				);
				$sms->setTemplate($SMSSettings['COMMERCE_LOYAL_ORDERPAY_SMS']['userTemplate']);
				$sms->setSite($this->siteId);
				$smsResult = $sms->send(true);
				$strError = implode(",", $smsResult->getErrorMessages());
				return true;
			}
		}
		return false;
	}

	protected function checkEmailList(){
		$this->profileSetting['email_settings']=empty($this->profileSetting['email_settings'])?[]:$this->profileSetting['email_settings'];
		if(empty($this->profileSetting['email_settings']['COMMERCE_LOYAL_ORDERPAY'])){
			$this->profileSetting['email_settings']['COMMERCE_LOYAL_ORDERPAY']=[
				'userTemplate'=>0
			];
		}
		$this->checkEmailMain();
	}
	
	protected function mailType($type){
		$mailType = [
			'COMMERCE_LOYAL_ORDERPAY'=>[
				'LID'=>LANGUAGE_ID,
				'EVENT_NAME'=>'COMMERCE_LOYAL_ORDERPAY',
				'NAME'=>Loc::getMessage("commerce.loyaltyprogram_EVENT_BONUSPAY"),
				'DESCRIPTION'=>'
					#EMAIL_TO# - '.Loc::getMessage("commerce.loyaltyprogram_EVENT_BONUSPAY_EMAIL_TO").'
					#BONUS# - '.Loc::getMessage("commerce.loyaltyprogram_EVENT_BONUSPAY_BONUS").'
					#BONUS_LEFT# - '.Loc::getMessage("commerce.loyaltyprogram_EVENT_BONUSPAY_BONUS_LEFT").'
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
				'EVENT_NAME'=>'COMMERCE_LOYAL_ORDERPAY',
				'LID'=>$sites[0],
				'ACTIVE'=>'Y',
				'EMAIL_FROM'=>'#DEFAULT_EMAIL_FROM#',
				'EMAIL_TO'=>'#EMAIL_TO#',
				'SUBJECT'=>'#SITE_NAME#: '.Loc::getMessage("commerce.loyaltyprogram_EVENT_BONUSPAY_USERBONUSPAY"),
				'MESSAGE'=>$this->globalSettings->getEmailTemplate('orderpay_user'),
				'BODY_TYPE'=>'html'
			]
		];
		return $mailTemplates[$key];
	}
	
	public function sendEvent($bonus){
		if(!empty($this->profileSetting['email_settings']['COMMERCE_LOYAL_ORDERPAY']['userTemplate'])){
			$etemplate=$this->mailTemplates('userTemplate');
			
			\Bitrix\Main\Mail\Event::send([
				"EVENT_NAME" => $etemplate['EVENT_NAME'],
				"LID" => $etemplate['LID'],
				"C_FIELDS" => [
					'EMAIL_TO'=>$this->userEmail,
					'BONUS'=>\CurrencyFormat($bonus, $this->currency),
					'BONUS_LEFT'=>\CurrencyFormat(($this->currentBudget-$bonus), $this->currency),
				]
			]);
		}
	}
	
	
	public function setOrder($order){
		$this->order=$order;
	}

    /**
     * write off bonuses only from products that are not included in the exceptions
     * @return array
     */
    public function getSkipItems(){
	    return empty($this->skipItems)?[]:$this->skipItems;
    }

    private function setParamsFromBitrixSOA($order){
        if(!empty($_REQUEST["order"]['PAY_SYSTEM_ID'])){
            $paymentCollection = $order->getPaymentCollection();
            $payment = $paymentCollection->createItem(
                \Bitrix\Sale\PaySystem\Manager::getObjectById(
                    intval($_REQUEST["order"]['PAY_SYSTEM_ID'])
                )
            );
            $payment->setField("SUM", $order->getPrice());
            $payment->setField("CURRENCY", $order->getCurrency());
        }
        if(!empty($_REQUEST["order"]['DELIVERY_ID'])){
            $shipmentCollection = $order->getShipmentCollection();
            $shipment = $shipmentCollection->createItem(
                \Bitrix\Sale\Delivery\Services\Manager::getObjectById(
                    intval($_REQUEST["order"]['DELIVERY_ID'])
                )
            );
            $shipmentItemCollection = $shipment->getShipmentItemCollection();
            $shipment->setField('CURRENCY', $order->getCurrency());

            foreach ($order->getBasket()->getOrderableItems() as $item) {
                $shipmentItem = $shipmentItemCollection->createItem($item);
                $shipmentItem->setQuantity($item->getQuantity());
            }
        }
        return $order;
    }
	
	public function getMaxBonus($basket=false){
		if($basket===false){
			$basket = \Bitrix\Sale\Basket::loadItemsForFUser(
				\Bitrix\Sale\Fuser::getId(),
				\Bitrix\Main\Context::getCurrent()->getSite()
				//'s1'
			);
		}
		$this->basket=$basket;
		
		$globalStatus=false;
		
		$tmpOrderId=$this->basket->getOrderId();
		if($tmpOrderId>0){
			$order = \Bitrix\Sale\Order::load($tmpOrderId);
			$this->userId=$order->getUserId();
			$this->siteId=$order->getField("LID");
			$discounts = \Bitrix\Sale\Discount::buildFromOrder($order, new \Bitrix\Sale\Discount\Context\Fuser($basket->getFUserId(true)));
		}else{
			// $discounts = \Bitrix\Sale\Discount::buildFromBasket($this->basket, new \Bitrix\Sale\Discount\Context\Fuser($this->basket->getFUserId(true)));
			global $USER;
			$this->userId=$USER->GetID();
			$this->siteId=\Bitrix\Main\Context::getCurrent()->getSite();

			$basket = \Bitrix\Sale\Basket::loadItemsForFUser(\Bitrix\Sale\Fuser::getId(),\Bitrix\Main\Context::getCurrent()->getSite());
			$order = \Bitrix\Sale\Order::create($this->siteId, $this->userId);
			$order->setBasket($basket);
            $order=$this->setParamsFromBitrixSOA($order);
			$discounts = $order->getDiscount();
		}
		$this->userData= Tools::getUserData($this->userId);
		if(!empty($discounts)){
			$discounts->calculate();
			$result = $discounts->getApplyResult(true);
		}
		$this->discountList=[];
		if(!empty($result["DISCOUNT_LIST"]) && count($result["DISCOUNT_LIST"])>0){
			foreach($result["DISCOUNT_LIST"] as $nextDiscount){
				if(!empty($nextDiscount['REAL_DISCOUNT_ID'])){
					$this->discountList[]=$nextDiscount['REAL_DISCOUNT_ID'];
				}
			}
		}
		$items = $this->basket->getOrderableItems();
		$this->discountProducts=[];
		$this->discountPrices=[];
		$this->products=[];
		foreach ($items as $item){
			$basketCode = $item->getBasketCode();
			$this->products[]=$item->getProductId();
			if(isset($result['RESULT']['BASKET'][$basketCode])){
				$this->discountProducts[]=$item->getProductId();
				$this->discountPrices[$basketCode]=$result['PRICES']['BASKET'][$basketCode]['PRICE'];
			}else{
				if($item->getBasePrice()>$item->getPrice()){
					$this->discountProducts[]=$item->getProductId();
					$this->discountPrices[$basketCode]=$item->getPrice();
				}
			}
		}
		foreach($this->profileSetting['settings']["condition"]["children"] as $nextO){
			if(!isset($nextO['children'])){
				continue;
			}else{
				unset($this->conditionParameters['skipPropsProduct']);
				$status=$this->checkConditionGroup($nextO);
				if($status){
					$globalStatus=true;
					$params=$nextO['values'];
					$skipItems=[];
					$price=0;
					if(
							!empty($this->conditionParameters['skipPriceProduct']) ||
							!empty($this->conditionParameters['discountProducts']) ||
							!empty($this->conditionParameters['skipPropsProduct']) ||
							!empty($this->conditionParameters['sectionProducts'])
						){
						if(!empty($this->conditionParameters['skipPriceProduct'])){
							$skipItems=array_merge($skipItems, $this->conditionParameters['skipPriceProduct']);
						}
						if(!empty($this->conditionParameters['discountProducts'])){
							$skipItems=array_merge($skipItems, $this->conditionParameters['discountProducts']);
						}
						if(!empty($this->conditionParameters['skipPropsProduct'])){
							$skipItems=array_merge($skipItems, $this->conditionParameters['skipPropsProduct']);
						}
						if(!empty($this->conditionParameters['sectionProducts'])){
							$skipItems=array_merge($skipItems, $this->conditionParameters['sectionProducts']);
						}
						$skipItems=array_unique($skipItems);
					}
					if(count($skipItems)>0){
					    $this->skipItems=$skipItems;
                    }
					
					foreach ($this->basket->getOrderableItems() as $basketItem){
						$currency=$basketItem->getField('CURRENCY');
						if(count($skipItems)>0 && in_array($basketItem->getProductId(), $skipItems)){
							continue;
						}
						if(empty($this->discountPrices[$basketItem->getId()])){
							$price+=$basketItem->getPrice()*$basketItem->getQuantity();
						}else{
							$price+=$basketItem->getQuantity()*$this->discountPrices[$basketItem->getId()];
						}
					}
					$this->userEmail=$this->userData['EMAIL'];
					$account=\CSaleUserAccount::GetByUserID($this->userId, $currency);
					$this->currentBudget=$account['CURRENT_BUDGET'];
					$this->currency=$currency;
					$bonusPay=$params['bonus'];
					
					$coeff=$this->getRankCoeff($this->userId);
					$bonusPay=$coeff*$bonusPay;

					if($params['bonus_unit']=='percent'){
						$bonusPay=$price*$bonusPay/100;
					}

					//$bonusPay=min($bonusPay, $this->basket->getPrice());
					$bonusPay=min($bonusPay, $price);

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
					if(!empty($params['allow_bonus_max']) && $params['allow_bonus_max']=='F' && !empty($params['bonus_max'])){
                        $bonusPay=min($bonusPay, ($this->basket->getPrice()-$params['bonus_max']));
                        $params['bonus_max']=0;
					}
					if($bonusPay>$params['bonus_max'] && !empty($params['bonus_max'])){
						$bonusPay=$params['bonus_max'];
					}
					$bonusPay=min((float) $bonusPay, (float) $account['CURRENT_BUDGET']);
					if($bonusPay>0){
						return $bonusPay;
					}
					//return $bonusPay;
				}
			}
		}		
		//return $globalStatus;
		return 0;
	}
	
	public function save($params){
		global $DB;
		$saveFields=[];
		$saveFields['sort']=(int) $params['sort'];
		$saveFields['active']='"'.(empty($params['active'])?'N':'Y').'"';
		$saveFields['name']='"'.(empty($params['profile_name'])?'noname':$DB->ForSql($params['profile_name'])).'"';
		$saveFields['type']='"'.$this->profileSetting['type'].'"';
		$consid_discounts=empty($params['consid_discounts'])?'N':$params['consid_discounts'];
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
		$this->checkOrderProps();
		$this->registerEvent('sale', 'OnSaleOrderSaved', 'AfterOrderSave');
		$this->registerEvent('sale', 'OnSaleOrderCanceled', 'AfterOrderCancel');
		$this->registerEvent('sale', 'OnSalePaymentEntitySaved', 'AfterOrderInnerPaymentRefund');
		$this->registerEvent('sale', 'OnSaleComponentOrderResultPrepared', 'soaIntegration');
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
					"allow_bonus_max"=>"Y",
					"bonus_max"=>"1000",
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
		$basketRules=$this->globalSettings->getBasketRules();
		$personTypes=$this->globalSettings->getPersonTypes();
		
		$delyvery=[];
		foreach($this->globalSettings->getDelivery() as $nextDelivery){
			$delyvery[$nextDelivery['ID']]=$nextDelivery['NAME'].' ['.$nextDelivery['ID'].']';
		}
		
		$paySystems=[];
		foreach($this->globalSettings->getPaySystems() as $nextPaySystems){
			$paySystems[$nextPaySystems['ID']]=$nextPaySystems['NAME'].' ['.$nextPaySystems['ID'].']';
		}
		
		/*$paySystems=[];
		foreach($this->globalSettings->getPaySystems() as $nextPaySystems){
			$paySystems[$nextPaySystems['ID']]=$nextPaySystems['NAME'].' ['.$nextPaySystems['ID'].']';
		}*/
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
				'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_bonusPayGroup"),
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
					Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_allowPay"),
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
                            'F'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_fix_min"),
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
						'defaultText'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_UNLIMITED_BONUS"),
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
				'controlgroup'=> true,
				'group'=> false,
				'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_ShopParameters"),
				'showIn'=> ['registerbyRef', 'registerbyRefSubGrp'],
				'children'=> [[
					'controlId'=> 'productBasket',
					'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_hint_product_basket_pay"),
					'group'=> false,
					'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_product_basket"),
					'showIn'=> ['registerbyRef', 'registerbyRefSubGrp'],
					'control'=> [[
						'id'=> 'prefix',
						'type'=> 'prefix',
						'text'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_product_basket")
					],[
						'id'=> 'logic',
						'name'=> 'logic',
						'type'=> 'select',
						'values'=> [
							'Equal'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_EQUAL"),
							'Not'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_NOTEQUAL")
						],
						'defaultText'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_EQUAL"),
						'defaultValue'=> 'Equal'
					],[
						'type'=> 'dialog',
						'popup_url'=>'/bitrix/tools/sale/product_search_dialog.php',
						//'type'=> 'multiDialog',
						//'popup_url'=>'cat_product_search_dialog.php',
						'param_id'=>'n',
						//'multiple'=>'Y',
						'popup_params'=>[
							'lang'=>LANGUAGE_ID,
							'allow_select_parent'=>'Y',
							'caller'=>'discount_rules'
						],
						'show_value'=>'Y',
						'id'=> 'product_basket',
						'name'=> 'product_basket'
					]
					]
				],[
					'controlId'=> 'sectionBasket',
					'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_hint_section_basket_pay"),
					'group'=> false,
					'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_section_basket"),
					'showIn'=> ['registerbyRef', 'registerbyRefSubGrp'],
					'control'=> [[
						'id'=> 'prefix',
						'type'=> 'prefix',
						'text'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_section_basket")
					],[
						'id'=> 'logic',
						'name'=> 'logic',
						'type'=> 'select',
						'values'=> [
							'Equal'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_EQUAL"),
							'Not'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_NOTEQUAL")
						],
						'defaultText'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LOGIC_EQUAL"),
						'defaultValue'=> 'Equal'
					],[
						'type'=> 'popup',
						'popup_url'=>'/bitrix/admin/iblock_section_search.php',
						'param_id'=>'n',
						'popup_params'=>[
							'lang'=>LANGUAGE_ID,
							'discount'=>'Y',
                            'simplename'=>'Y'
						],
						'show_value'=>'Y',
						'id'=> 'section_basket',
						'name'=> 'section_basket'
					]
					]
				],[
					'controlId'=> 'skipDiscountProduct',
					'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_SKIP_hint_discount_product"),
					'group'=> false,
					'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_SKIP_DISCOUNT_PRODUCT"),
					'showIn'=> ['registerbyRef', 'registerbyRefSubGrp'],
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
					'controlId'=> 'discount',
					'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_ordering_discount"),
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
						//'defaultText'=> $firstBasketRule,
						//'defaultValue'=> $firstBasketRuleKey
						'first_option'=> '...',
						'defaultText'=> '...',
						'defaultValue'=> ''
					]]
				],[
					'controlId'=> 'skipPriceProduct',
					'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_hint_skip_price_product"),
					'group'=> false,
					'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_SKIP_PRICE_PRODUCT"),
					'showIn'=> ['registerbyRef', 'registerbyRefSubGrp'],
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
					'controlId'=> 'priceBasket',
					'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_hint_price_basket"),
					'group'=> false,
					'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_price_basket"),
					'showIn'=> ['registerbyRef', 'registerbyRefSubGrp'],
					'control'=> [[
						'id'=> 'prefix',
						'type'=> 'prefix',
						'text'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_price_basket")
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
						'id'=> 'price_basket',
						'name'=> 'price_basket',
						'param_id'=>'n',
						'show_value'=>'Y',
						'defaultValue'=>'100',
						'defaultText'=> ''
					],
					$optns['currency']
					]
				],[
					'controlId'=> 'paySystems',
					'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_ordering_paysystems"),
					'group'=> false,
					'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_PAYSYSTEMS"),
					'showIn'=> ['registerbyRef', 'registerbyRefSubGrp', 'registerbyParentRef', 'registerbyParentRefSubGrp'],
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
					'showIn'=> ['registerbyRef', 'registerbyRefSubGrp', 'registerbyParentRef', 'registerbyParentRefSubGrp'],
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
					'showIn'=> ['registerbyRef', 'registerbyRefSubGrp', 'registerbyParentRef', 'registerbyParentRefSubGrp'],
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
				]/*,[
					'controlId'=> 'paySystems',
					'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_ordering_paysystems"),
					'group'=> false,
					'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_PAYSYSTEMS"),
					'showIn'=> ['registerbyRef', 'registerbyRefSubGrp', 'registerbyParentRef', 'registerbyParentRefSubGrp'],
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
				]*/]
			]
			/*[
				'controlId'=> 'CondGroup',
				'group'=> true,
				'label'=> '',
				'defaultText'=> '',
				'showIn'=> [],
				'control'=> [Loc::getMessage("commerce.loyaltyprogram_CONDITION_PERFORM_OPERATIONS")]
			]*/
		];

        if(!empty($optns['filter_prop'])){
            $filterProp = explode(',',$optns['filter_prop']);
        }
        $alReadyProps=$this->getAlreadyProps();
        if(count($alReadyProps)>0){
            $filterProp=array_merge($filterProp, $alReadyProps);
        }
        $paramsProp=\Commerce\Loyaltyprogram\Tools\CondFilter::GetControlShow(['SHOW_IN_GROUPS'=>['registerbyRef', 'registerbyRefSubGrp', 'registerbyParentRef', 'registerbyParentRefSubGrp'],'filter' => $filterProp]);
		
		//$paramsProp=\CCatalogCondCtrlIBlockProps::GetControlShow(['SHOW_IN_GROUPS'=>['registerbyRef', 'registerbyRefSubGrp']]);
		if(count($paramsProp)>0){
			foreach($paramsProp as $nextProps){
				$params[]=$nextProps;
			}
			
		}
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
	
	//props product processing
	private function isPropControl($node){
		$code=explode(':',$node['controlId']);
		if(count($code)==3){
			if(count($this->products)>0){
				if(empty($this->productProps)){
					$this->productProps=[];
					$this->productProps['na']=[];
					\Bitrix\Main\Loader::includeModule('iblock');
					$dbEl = \CIBlockElement::GetList([], ["ID"=>$this->products]);
					while($obEl = $dbEl->GetNextElement()){
						// $fields=$obEl->GetFields();
						// $props = $obEl->GetProperties();
						// $this->productProps[$fields['ID']]=[];
						// foreach($props as $nextProp){
						// 	//$val=(is_array($nextProp['VALUE_ENUM_ID']))?$nextProp['VALUE_ENUM_ID']:$nextProp['VALUE'];
						// 	$val=($nextProp['PROPERTY_TYPE']=='L')?$nextProp['VALUE_ENUM_ID']:$nextProp['VALUE'];
						// 	//if(!empty($val) || is_array($val)){
						// 		$this->productProps[$fields['ID']][$nextProp['ID']]=$val;
						// 		if(empty($this->productProps['na'][$nextProp['ID']])){
						// 			$this->productProps['na'][$nextProp['ID']]='';
						// 		}
						// 	//}
						// }
						$fields=$obEl->GetFields();
						$props = $obEl->GetProperties();
						$this->productProps[$fields['ID']]=[];

						$parentProduct[$fields['ID']] = [];
						$isSKU = false;
						$isProp = false;

						foreach($props as $nextProp){
							//$val=(is_array($nextProp['VALUE_ENUM_ID']))?$nextProp['VALUE_ENUM_ID']:$nextProp['VALUE'];
							//$val=(!empty($nextProp['VALUE_ENUM_ID']))?$nextProp['VALUE_ENUM_ID']:$nextProp['VALUE'];
							
							$val=($nextProp['PROPERTY_TYPE']=='L')?$nextProp['VALUE_ENUM_ID']:$nextProp['VALUE'];
							
							if($nextProp['USER_TYPE'] == 'SKU'){$isSKU = true;	$parentProduct[$fields['ID']]['ID'] = $nextProp['VALUE'];}
							if($nextProp['ID'] == $code[2]){$isProp = true;}

							//if(!empty($val) || is_array($val)){
								$this->productProps[$fields['ID']][$nextProp['ID']]=$val;
							//}	
						}
						if($isSKU == true && $isProp == false){
							$db_props = \CIBlockElement::GetProperty($code[1], $parentProduct[$fields['ID']]['ID'], [], []); // 
							while($ar_props = $db_props->Fetch()){
								if(isset($ar_props["VALUE"])){
									if($ar_props["MULTIPLE"] == "Y"){
										$this->productProps[$fields['ID']][$ar_props["ID"]][] = $ar_props["VALUE"];
									} else {
										$this->productProps[$fields['ID']][$ar_props["ID"]] = $ar_props["VALUE"];
									}
								}
							}
						}
					}
				}
				return true;
			}
		}
		return false;
	}
	
	//check Product ByProp and insert product skipproduct array
	private function checkProductByProp($prop, $condition){
		$tmpSkipProps=[];
		$code=explode(':',$prop['controlId']);
		$propId=$code[2];
		$logic=$prop['values']['logic'];
		$currenrtVal=$prop['values']['value'];
		foreach($this->productProps as $keyProduct=>$nextProp){
			//if(!empty($nextProp[$propId]) || is_array($nextProp[$propId])){
				//logic
				$statusLogic=false;
				switch ($logic){
					case 'Equal':
						if(
							(!is_array($nextProp[$propId]) && $nextProp[$propId]==$currenrtVal) ||
							(is_array($nextProp[$propId]) && in_array($currenrtVal, $nextProp[$propId]))
						){
							$statusLogic=true;
						}
					break;
					case 'Not':
						if(
							(!is_array($nextProp[$propId]) && $nextProp[$propId]!=$currenrtVal) ||
							(is_array($nextProp[$propId]) && !in_array($currenrtVal, $nextProp[$propId]))
						){
							$statusLogic=true;
						}
					break;
					case 'Great':
						if(!is_array($nextProp[$propId]) && $nextProp[$propId]>$currenrtVal){
							$statusLogic=true;
						}elseif(is_array($nextProp[$propId])){
							foreach($nextProp[$propId] as $nextPropVal){
								if($nextPropVal>$currenrtVal){
									$statusLogic=true;
									break;
								}
							}
						}
					break;
					case 'Less':
						if(!is_array($nextProp[$propId]) && $nextProp[$propId]<$currenrtVal){
							$statusLogic=true;
						}elseif(is_array($nextProp[$propId])){
							foreach($nextProp[$propId] as $nextPropVal){
								if($nextPropVal<$currenrtVal){
									$statusLogic=true;
									break;
								}
							}
						}
					break;
					case 'EqGr':
						if(!is_array($nextProp[$propId]) && $nextProp[$propId]>=$currenrtVal){
							$statusLogic=true;
						}elseif(is_array($nextProp[$propId])){
							foreach($nextProp[$propId] as $nextPropVal){
								if($nextPropVal>=$currenrtVal){
									$statusLogic=true;
									break;
								}
							}
						}
					break;
					case 'EqLs':
						if(!is_array($nextProp[$propId]) && $nextProp[$propId]<=$currenrtVal){
							$statusLogic=true;
						}elseif(is_array($nextProp[$propId])){
							foreach($nextProp[$propId] as $nextPropVal){
								if($nextPropVal<=$currenrtVal){
									$statusLogic=true;
									break;
								}
							}
						}
					break;
				}
				/*if(
					($condition=='OR' || $condition=='ORNOT') &&
					is_array($this->conditionParameters['skipPropsProduct']) &&
					in_array($keyProduct, $this->conditionParameters['skipPropsProduct'])
				){
					$this->conditionParameters['skipPropsProduct']=array_diff($this->conditionParameters['skipPropsProduct'], [$keyProduct]);
				}*/
				if(
					($condition=='AND' && $statusLogic==false) ||
					($condition=='ANDNOT' && $statusLogic==true) ||
					($condition=='OR' && $statusLogic==false) ||
					($condition=='ORNOT' && $statusLogic==true)
				){
					$tmpSkipProps[]=$keyProduct;
				}elseif(
					($condition=='OR' && $statusLogic==true) ||
					($condition=='ORNOT' && $statusLogic==false)
				){
					if(count($tmpSkipProps)>0){
						$tmpSkipProps=array_diff($tmpSkipProps, [$keyProduct]);
					}
				}
			//}
		}
		return array_unique($tmpSkipProps);
	}

	private function getAllProducts(){
		if(!isset($this->pdoductsWithOffers)){
			$this->pdoductsWithOffers=[];
			if(count($this->products)>0){
				$this->pdoductsWithOffers=$this->products;
				$offers=\CCatalogSKU::getProductList($this->products, 0);
				if(count($offers)>0){
					foreach($offers as $nextOffer){
						$this->pdoductsWithOffers[]=$nextOffer['ID'];
					}
					$this->pdoductsWithOffers=array_unique($this->pdoductsWithOffers);
				}
			}
		}
		return $this->pdoductsWithOffers;
	}

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

	/**
	* the function returns an array of keys that occur in all nested arrays
	*/
	private function clearOrArray($arr){
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
			
			//additional status for props conditions, price conditions, discount conditions...
			$condStatusProps=false;
			
			foreach($group['children'] as $nextChildren){
				if(!empty($nextChildren['children'])){
					$condStatus=$this->checkConditionGroup($nextChildren);
				}else{
					$condStatus=false;
					//$optns=$this->globalSettings->getOptions();
					$isPropIblock=$this->isPropControl($nextChildren);
					if($isPropIblock){
						$condStatus=true;
						$condStatusProps=true;
						if($condition=='ANDNOT' || $condition=='OR'){
							$condStatus=false;
						}
						$tmpProps=$this->checkProductByProp($nextChildren, $condition);
						if(count($tmpProps)>0){
							if($condition=='ANDNOT' || $condition=='AND'){
								$this->conditionParameters['skipPropsProduct']=isset($this->conditionParameters['skipPropsProduct'])?$this->conditionParameters['skipPropsProduct']:[];
								$this->conditionParameters['skipPropsProduct']=array_merge($this->conditionParameters['skipPropsProduct'], $tmpProps);
							}else{
								$tmpSkipProps[]=$tmpProps;
							}
						}
					}else{
						switch ($nextChildren['controlId']){
							case 'sites':
								$site=(!empty($this->siteId))?$this->siteId:SITE_ID;
								$condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $site, $nextChildren['values']['sites']);
							break;
							case 'groupUsers':
								$userGroup=\CUser::GetUserGroup($this->userId);
								$condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $userGroup, $nextChildren['values']['users']);
							break;
                            case 'ranksUser':
                                if(empty($this->userId)){
                                    $rank=0;
                                }else{
                                    $rank=$this->ranksObject->getRankUser($this->userId);
                                }
                                $condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $rank, $nextChildren['values']['ranks']);
                            break;
							case 'productBasket':
								$condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $this->getAllProducts(), $nextChildren['values']['product_basket']);
							break;
							case 'sectionBasket':
									$logic=$nextChildren['values']['logic'];
									foreach ($this->basket as $basketItem){
										$nextProduct=$basketItem->getProductId();
										$sections=$this->getSections();
										if(
											($logic=='Not' && in_array($nextChildren['values']['section_basket'], $sections[$nextProduct])) ||
											($logic=='Equal' && !in_array($nextChildren['values']['section_basket'], $sections[$nextProduct]))
										){
											$this->conditionParameters['sectionProducts'][]=$nextProduct;
										}
									}
									$condStatus=true;
									$condStatusProps=true;
									if($condition=='ANDNOT' || $condition=='OR'){
										$condStatus=false;
									}
							break;
							case 'skipDiscountProduct':
								foreach ($this->basket as $basketItem){
									if(count($this->discountProducts)>0 && in_array($basketItem->getProductId(), $this->discountProducts)){
										$this->conditionParameters['discountProducts'][]=$basketItem->getProductId();
									}
								}
								$condStatus=true;
								$condStatusProps=true;
								if($condition=='ANDNOT' || $condition=='OR'){
									$condStatus=false;
								}
							break;
							case 'discount':
								$condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $this->discountList, $nextChildren['values']['discount']);
							break;
							case 'priceBasket':
								$condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $this->basket->getPrice(), $nextChildren['values']['price_basket']);
							break;
							case 'personTypes':
								$currentPerson=!empty($this->order)?$this->order->getPersonTypeId():0;
								$condStatus=$this->checkConditionChildren($nextChildren['values']['logic'],  $currentPerson, $nextChildren['values']['person_types']);
							break;
							case 'paySystems':
								$currentPays = [];
								if(!empty($this->order)){
									$paymentCollection = $this->order->getPaymentCollection();
									foreach ($paymentCollection as $payment) {
										$currentPays[]=$payment->getPaymentSystemId();
									}
								}
								$condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $currentPays, $nextChildren['values']['pay_systems']);
							break;
							case 'delyvery':
								$currentDelivery=!empty($this->order)?$this->order->getDeliverySystemId():0;
								$condStatus=$this->checkConditionChildren($nextChildren['values']['logic'],  $currentDelivery, $nextChildren['values']['delyvery']);
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
							/*case 'paySystems':
								$paymentCollection = $this->basket->getOrder()->getPaymentCollection();
								$currentPays=[];
								foreach ($paymentCollection as $payment) {
									$currentPays[]=$payment->getPaymentSystemId();
								}
								$condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $currentPays, $nextChildren['values']['pay_systems']);
							break;*/
						}
					}
				}
				if($condition=='AND' && $condStatus==false){
					return false;
				}elseif($condition=='ANDNOT' && $condStatus==true){
					return false;
				}elseif($condition=='OR' && $condStatus==true){
					$this->conditionParameters['skipPriceProduct']=$this->conditionParameters['discountProducts']=$this->conditionParameters['skipPropsProduct']=$this->conditionParameters['sectionProducts']=[];
					return true;
				}elseif($condition=='ORNOT' && $condStatus==false){
					$this->conditionParameters['skipPriceProduct']=$this->conditionParameters['discountProducts']=$this->conditionParameters['skipPropsProduct']=$this->conditionParameters['sectionProducts']=[];
					return true;
				}
			}
			if(!empty($tmpSkipProps) && count($tmpSkipProps)>0){
				$tmpSkipProps=$this->clearOrArray($tmpSkipProps);
				if(count($tmpSkipProps)>0){
					$this->conditionParameters['skipPropsProduct']=isset($this->conditionParameters['skipPropsProduct'])?$this->conditionParameters['skipPropsProduct']:[];
					$this->conditionParameters['skipPropsProduct']=array_merge($this->conditionParameters['skipPropsProduct'], $tmpSkipProps);
				}
			}
			if($condition=='AND' || $condition=='ANDNOT' || $condStatusProps){
				return true;
			}
		}
		return false;
	}

}

?>