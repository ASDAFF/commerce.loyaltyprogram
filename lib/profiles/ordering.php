<?
namespace Commerce\Loyaltyprogram\Profiles;
use \Bitrix\Main\Application,
	Bitrix\Main\Localization\Loc,
	\Commerce\Loyaltyprogram;
/**
* type ordering profile
*/
class Ordering extends Profile implements Profileinterface{
	
	function __construct(){
		parent::__construct();
		$this->profileSetting['type']='Ordering';
		$options=$this->globalSettings->getOptions();
		$this->moduleOptions=$options;
		$maxLevel=$options['ref_level'];
		for($i=0; $i<$maxLevel; $i++){
			$this->profileSetting['settings']['rewards_unit'][$i]=(!empty($this->profileSetting['settings']['rewards_unit'][$i]))?$this->profileSetting['settings']['rewards_unit'][$i]:'bonus';
		}
        $this->isPropBonus=!empty($this->profileSetting['settings']["prop_bonus"])?$this->profileSetting['settings']["prop_bonus"]:false;
	}

	public function isPropBonus() {
        return !empty($this->profileSetting['settings']["prop_bonus"])?$this->profileSetting['settings']["prop_bonus"]:false;
    }

    public function getParametersMain(){
		foreach(['type', 'profileActive', 'profileName', 'profileSort', 'bonusProperty'] as $nextRow){
			$this->drawRow($nextRow);
		}
	}
	
	protected function checkSMSList(){
		$this->profileSetting['sms_settings']=empty($this->profileSetting['sms_settings'])?[]:$this->profileSetting['sms_settings'];
		if(empty($this->profileSetting['sms_settings']['COMMERCE_LOYAL_ORDERING_SMS'])){
			$this->profileSetting['sms_settings']['COMMERCE_LOYAL_ORDERING_SMS']=[
				'userTemplate'=>0,
				'refTemplate'=>0
			];
		}
		$this->checkSMSMain();
	}
	
	protected function SMSType($type){
		$SMSType = [
			'COMMERCE_LOYAL_ORDERING_SMS'=>[
				'LID'=>LANGUAGE_ID,
				'EVENT_NAME'=>'COMMERCE_LOYAL_ORDERING_SMS',
				'EVENT_TYPE'  => 'sms',
				'NAME'=>Loc::getMessage("commerce.loyaltyprogram_SMS_BONUSADD"),
				'DESCRIPTION'=>Loc::getMessage("commerce.loyaltyprogram_SMS_BONUSADD_DESC")
			]
		];
		return $SMSType[$type];
	}
	
	protected function SMSTemplates($key){
		$sites=array_keys($this->globalSettings->getSites());
		$smsTemplates = [
			'userTemplate'=>[
				'EVENT_NAME'=>'COMMERCE_LOYAL_ORDERING_SMS',
				"ACTIVE" => false,
				"SENDER" => "#DEFAULT_SENDER#",
				"RECEIVER" => "#USER_PHONE#",
				"MESSAGE" => GetMessage("commerce.loyaltyprogram_SMS_BONUSADD_USERBONUSADD")
			],
			'refTemplate'=>[
				'EVENT_NAME'=>'COMMERCE_LOYAL_ORDERING_SMS',
				"ACTIVE" => false,
				"SENDER" => "#DEFAULT_SENDER#",
				"RECEIVER" => "#USER_PHONE#",
				"MESSAGE" => GetMessage("commerce.loyaltyprogram_SMS_BONUSADD_REFBONUSADD")
			]
		];
		return $smsTemplates[$key];
	}
	
	protected function checkEmailList(){
		$this->profileSetting['email_settings']=empty($this->profileSetting['email_settings'])?[]:$this->profileSetting['email_settings'];
		if(empty($this->profileSetting['email_settings']['COMMERCE_LOYAL_ORDERING'])){
			$this->profileSetting['email_settings']['COMMERCE_LOYAL_ORDERING']=[
				'userTemplate'=>0,
				'refTemplate'=>0
			];
		}
		$this->checkEmailMain();
	}
	
	protected function mailType($type){
		$mailType = [
			'COMMERCE_LOYAL_ORDERING'=>[
				'LID'=>LANGUAGE_ID,
				'EVENT_NAME'=>'COMMERCE_LOYAL_ORDERING',
				'NAME'=>Loc::getMessage("commerce.loyaltyprogram_EVENT_BONUSADD"),
				'DESCRIPTION'=>'
					#EMAIL_TO# - '.Loc::getMessage("commerce.loyaltyprogram_EVENT_BONUSADD_EMAIL_TO").'
					#BONUS# - '.Loc::getMessage("commerce.loyaltyprogram_EVENT_BONUSADD_BONUS").'
					#USER_ID# - '.Loc::getMessage("commerce.loyaltyprogram_EVENT_BONUSADD_USER_ID").'
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
				'EVENT_NAME'=>'COMMERCE_LOYAL_ORDERING',
				'LID'=>$sites[0],
				'ACTIVE'=>'Y',
				'EMAIL_FROM'=>'#DEFAULT_EMAIL_FROM#',
				'EMAIL_TO'=>'#EMAIL_TO#',
				'SUBJECT'=>'#SITE_NAME#: '.Loc::getMessage("commerce.loyaltyprogram_EVENT_BONUSADD_USERBONUSADD"),
				'MESSAGE'=>$this->globalSettings->getEmailTemplate('ordering_user'),
				'BODY_TYPE'=>'html'
			],
			'refTemplate'=>[
				'EVENT_NAME'=>'COMMERCE_LOYAL_ORDERING',
				'LID'=>$sites[0],
				'ACTIVE'=>'Y',
				'EMAIL_FROM'=>'#DEFAULT_EMAIL_FROM#',
				'EMAIL_TO'=>'#EMAIL_TO#',
				'SUBJECT'=>'#SITE_NAME#: '.Loc::getMessage("commerce.loyaltyprogram_EVENT_BONUSADD_REFBONUSADD"),
				'MESSAGE'=>$this->globalSettings->getEmailTemplate('ordering_ref'),
				'BODY_TYPE'=>'html'
			]
		];
		return $mailTemplates[$key];
	}

	private function isTemporary(){
		$status=false;
		global $DB;
		if(!empty($this->moduleOptions['ref_basket_rules'])){
			$rules=explode(',',$this->moduleOptions['ref_basket_rules']);
			if(count($rules)>0){
				$discountData = $this->order->getDiscount()->getApplyResult();
				if(!empty($discountData['COUPON_LIST'])){
					foreach($discountData['COUPON_LIST'] as $nextCoupon){
						if(in_array($nextCoupon['DATA']['DISCOUNT_ID'], $rules)){
							$key=array_search($nextCoupon['DATA']['DISCOUNT_ID'], $rules);
							$key=($key==0)?'':$key;
							if(!empty($this->moduleOptions['ref_coupon_istemporary'.$key]) && $this->moduleOptions['ref_coupon_istemporary'.$key]=='Y'){
								$res=$DB->Query('select * from '.$this->globalSettings->getTableRefCoupons().' where coupon_id='.$nextCoupon['DATA']['ID'].';');
								if($row = $res->Fetch()){
									$this->temporaryParentRef=$row['user_id'];
									$status=true;
								}
							}
						}
						if($status){
							break;
						}
					}
				}
			}
			
		}
		$this->isTemporary=$status;
		return $status;
	}

	private function getDiscountProducts(){
		if(empty($this->order)){
			$basket = \Bitrix\Sale\Basket::loadItemsForFUser(
				\Bitrix\Sale\Fuser::getId(),
				\Bitrix\Main\Context::getCurrent()->getSite()
			);
			$discounts = \Bitrix\Sale\Discount::buildFromBasket($basket, new \Bitrix\Sale\Discount\Context\Fuser($basket->getFUserId(true)));
		}else{
			$basket = $this->order->getBasket();
			$discounts = \Bitrix\Sale\Discount::buildFromOrder($this->order, new \Bitrix\Sale\Discount\Context\Fuser($basket->getFUserId(true)));
		}
		
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
		return $this->discountProducts;
	}
	
	public function getBonusByProduct($product){
	    $currentBonus=0;
        global $USER;
        $this->userId=$USER->GetID();
        //if unAuthorized
        $this->userId=empty($this->userId)?0:$this->userId;
        $this->productArray=$product;
        foreach($this->profileSetting['settings']["condition"]["children"] as $nextO){
            if(!isset($nextO['children'])){
                continue;
            }else{
                $this->products=[$product['ID']];
                $status=$this->checkConditionGroupByProduct($nextO);

                if($status){
                    if($nextO['controlId']=='registerbyRef'){
                        if($this->isPropBonus()!=false){
                            $nextO['values']['bonus']=$this->getSummByPropBonus();
                        }
                        $params=$nextO['values'];
                        $params['bonus'] = str_replace(',','.',$params['bonus']);
                        if(!empty($params['bonus']) && (float) $params['bonus']>0){
                            $currentBonus=$params['bonus'];
                            $coeff=$this->getRankCoeff($this->userId);
                            $currentBonus=$coeff*$currentBonus;
                            if($params['bonus_unit']=='percent' && $this->isPropBonus()==false){
                                $price = $product['PRICE'];
                                $currentBonus=$currentBonus*$price/100;
                                if($params['bonus_round']=='more'){
                                    $currentBonus=ceil($currentBonus);
                                }elseif($params['bonus_round']=='less'){
                                    $currentBonus=floor($currentBonus);
                                }elseif($params['bonus_round']=='auto'){
                                    $currentBonus=round($currentBonus);
                                }else{
                                    $currentBonus=$currentBonus;
                                }
                            }
                            if(!empty($params['allow_bonus_max']) && $params['allow_bonus_max']=='N'){
                                $params['bonus_max']=0;
                            }
                            if(!empty($params['bonus_max']) && (int) $params['bonus_max']>0 && $params['bonus_max']<$currentBonus){
                                $currentBonus=(int) $params['bonus_max'];
                            }
                        }
                    }
                }
            }
        }
        return $currentBonus;
    }

	public function getBonusBySumm($summ=0){
		if(!empty($this->profileSetting['settings']["condition"]["children"]) && $summ>0){
			foreach($this->profileSetting['settings']["condition"]["children"] as $nextO){
				if(!isset($nextO['children'])){
					continue;
				}else{
					if($nextO['controlId']=='registerbyRef'){
						$params=$nextO['values'];
						$fields['bonus']=$params['bonus'];
						if($params['bonus_unit']=='percent'){
							$fields['bonus']=$fields['bonus']*$summ/100;
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
						return $fields['bonus'];
					}
				}
			}
		}
		return 0;
	}
	
	public function getBonus($basket=false){
		global $USER;
		/*if (!$USER->IsAuthorized()){
			return false;
		}*/
		$this->userId=$USER->GetID();
		//if unAuthorized

		$this->userId=empty($this->userId)?0:$this->userId;
		if(!empty($this->profileSetting['settings']["condition"]["children"]) && $basket!==false){
			$this->basket=$basket;

			//$basket = \Bitrix\Sale\Basket::loadItemsForFUser(\Bitrix\Sale\Fuser::getId(),\Bitrix\Main\Context::getCurrent()->getSite());
			$order = \Bitrix\Sale\Order::create(\Bitrix\Main\Context::getCurrent()->getSite(), $this->userId);
			$order->setBasket($basket);
			$discounts = $order->getDiscount();
			$discounts->calculate();
			
			$result = $discounts->getApplyResult(true);
			$order->refreshData();
			
			foreach ($basket as $basketItem){
				$this->products[]=$basketItem->getProductId();
				$currency=$basketItem->getField('CURRENCY');
			}
			$this->bonusPay=0;
			$tmpBonus=['bonus'=>0, 'currency'=>''];
			foreach($this->profileSetting['settings']["condition"]["children"] as $nextO){
				if(!isset($nextO['children']) || $nextO['controlId']=='registerbyParentRef'){
					continue;
				}else{
					$this->conditionParameters=[];
					$status=$this->checkConditionGroup($nextO, true);
					if($status){
						$this->availablePrice=$basket->getPrice();
						
						$skipItems=[];
						
						if(!empty($this->conditionParameters['skipPropsProduct'])){
							$skipItems=array_merge($skipItems, $this->conditionParameters['skipPropsProduct']);
						}
						if(!empty($this->conditionParameters['discountProducts'])){
							$skipItems=array_merge($skipItems, $this->conditionParameters['discountProducts']);
						}
						if(!empty($this->conditionParameters['skipPriceProduct'])){
							$skipItems=array_merge($skipItems, $this->conditionParameters['skipPriceProduct']);
						}
						if(!empty($this->conditionParameters['sectionProducts'])){
							$skipItems=array_merge($skipItems, $this->conditionParameters['sectionProducts']);
						}
						if(!empty($this->conditionParameters['iBlockProducts'])){
							$skipItems=array_merge($skipItems, $this->conditionParameters['iBlockProducts']);
						}
						if(count($skipItems)>0){
							$skipItems=array_unique($skipItems);
						}
                        if($this->isPropBonus()!=false){
                            $nextO['values']['bonus']=$this->getSummByPropBonus($skipItems);
                        }elseif(count($skipItems)>0 && $nextO['values']['bonus_unit']=='percent' && $this->isPropBonus()==false){
							
							$this->availablePrice=0;
							foreach ($basket as $basketItem){
								if(in_array($basketItem->getProductId(), $skipItems)){
									continue;
								}
								$this->availablePrice+=$basketItem->getPrice()*$basketItem->getQuantity();
							}
						}

						if($nextO['controlId']=='registerbyRef'){
							$params=$nextO['values'];
							$params['bonus'] = str_replace(',','.',$params['bonus']);
							if(!empty($params['bonus']) && (float) $params['bonus']>0){
								$fields['bonus']= $params['bonus'];
								$fields['status']='"inactive"';
								if($params['bonus_unit']=='percent' && $this->isPropBonus()==false){
									$price = $this->availablePrice-$this->bonusPay;
									$fields['bonus']=$fields['bonus']*$price/100;
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
								if(!empty($params['bonus_max']) && (float) $params['bonus_max']>0 && $params['bonus_max']<$fields['bonus']){
									$fields['bonus']=(float) $params['bonus_max'];
								}
								if($fields['bonus']>0){
									$tmpBonus['bonus']+=$fields['bonus'];
									$tmpBonus['currency']=$currency;
									//return ['bonus'=>$fields['bonus'], 'currency'=>$currency];
								}
							}
						}
					}
				}
			}
		}
		if(!empty($tmpBonus['bonus'])){
			$coeff=$this->getRankCoeff($this->userId);
			$tmpBonus['bonus']=$coeff*$tmpBonus['bonus'];
            $tmpBonus['bonus_format']=\CurrencyFormat($tmpBonus['bonus'], $currency);
			return $tmpBonus;
		}
		return false;
	}

	public function getSummByPropBonus($skipItems=[]){
	    $bonusPrice=0;
	    $propBonus=$this->isPropBonus();

	    $items=array_diff($this->products, $skipItems);
	    if(count($items)>0 && $propBonus>0){
	        $quantities=array_fill_keys($items, 1);
            if($this->basket){
                foreach ($this->basket as $basketItem){
                    $basketItem->getProductId();
                    $quantities[$basketItem->getProductId()]=$basketItem->getQuantity();
                }
            }
            $items[]=15994;
            \Bitrix\Main\Loader::includeModule('iblock');
            $res = \CIBlockElement::GetList([], ['ID'=>$items], false, false, ["ID", 'IBLOCK_ID', 'PROPERTY_'.$propBonus]);
            while($ar_fields = $res->GetNext()){
                $tmpBonus=(float) $ar_fields['PROPERTY_'.$propBonus.'_VALUE'];
                $quantity=$quantities[$ar_fields['ID']];
                $bonusPrice+=$tmpBonus*$quantity;
            }
        }
	    return $bonusPrice;
    }
	
	public function setBonus($event=false, $debug=false){
		if(!empty($this->profileSetting['settings']["condition"]["children"]) && $event!==false){
			if($debug){
				$order = $event;
			}else{
				$order = $event->getParameter("ENTITY");
			}
			//$order=$event;//for test!!! remove after test!!!

			$this->order=$order;

			$basket = $this->order->getBasket();
			$this->basket=$basket;
			foreach ($basket as $basketItem){
				$this->products[]=$basketItem->getProductId();
			}
			$this->isTemporary();
			$this->userId=$order->getUserId();
			$this->bonusPay=0;
			$this->setBonus=false;

			foreach($this->profileSetting['settings']["condition"]["children"] as $nextO){
				if(!isset($nextO['children'])){
					continue;
				}else{
					$this->conditionParameters=[];
					$status=$this->checkConditionGroup($nextO);

					if($status){
						$this->availablePrice=$basket->getPrice();
						
						$skipItems=[];
						if(!empty($this->conditionParameters['skipPropsProduct'])){
							$skipItems=array_merge($skipItems, $this->conditionParameters['skipPropsProduct']);
						}
						if(!empty($this->conditionParameters['discountProducts'])){
							$skipItems=array_merge($skipItems, $this->conditionParameters['discountProducts']);
						}
						if(!empty($this->conditionParameters['skipPriceProduct'])){
							$skipItems=array_merge($skipItems, $this->conditionParameters['skipPriceProduct']);
						}
						if(!empty($this->conditionParameters['sectionProducts'])){
							$skipItems=array_merge($skipItems, $this->conditionParameters['sectionProducts']);
						}
						if(!empty($this->conditionParameters['iBlockProducts'])){
							$skipItems=array_merge($skipItems, $this->conditionParameters['iBlockProducts']);
						}
						if(count($skipItems)>0){
							$skipItems=array_unique($skipItems);
						}

						if($this->isPropBonus()!=false){
                            $nextO['values']['bonus']=$this->getSummByPropBonus($skipItems);
                        }elseif(count($skipItems)>0 && $nextO['values']['bonus_unit']=='percent' && $this->isPropBonus()==false){
							$this->availablePrice=0;
							foreach ($basket as $basketItem){
								if(in_array($basketItem->getProductId(), $skipItems)){
									continue;
								}
								$this->availablePrice+=$basketItem->getPrice();
							}
						}

						global $DB;
						if($nextO['controlId']=='registerbyRef'){
							$params=$nextO['values'];
							$params['bonus'] = str_replace(',','.',$params['bonus']);
							if(!empty($params['bonus']) && (float) $params['bonus']>0){
								$fields['bonus']=$params['bonus'];

								$coeff=$this->getRankCoeff($this->userId);
								$fields['bonus']=$coeff*$fields['bonus'];
								
								$fields['user_id']=$this->userId;
								$fields['order_id']=$this->order->getId();
								$fields['currency']='"'.$this->order->getCurrency().'"';
								$fields['profile_type']='"'.$this->profileSetting['type'].'"';
								$fields['profile_id']=$this->profileSetting['id'];
								$fields['status']='"inactive"';
								if($params['bonus_unit']=='percent' && $this->isPropBonus()==false){
									$price = $this->availablePrice-$this->bonusPay;
									$fields['bonus']=$fields['bonus']*$price/100;
									if($params['bonus_round']=='more'){
										$fields['bonus']=ceil($fields['bonus']);
									}elseif($params['bonus_round']=='less'){
										$fields['bonus']=floor($fields['bonus']);
									}elseif($params['bonus_round']=='auto'){
										$fields['bonus']=round($fields['bonus']);
									}else{
										//$fields['bonus']=round($fields['bonus'], 2);
										$fields['bonus']=$fields['bonus'];
									}
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
									$mailTmplt=$this->getUserEmail($this->userId, $fields['bonus']);
									$mailTmplt=($mailTmplt!=false)?serialize($mailTmplt):'';
									$fields['email']="'".$mailTmplt."'";
									
									$SMSTmplt=$this->getUserSMS($this->userId, $fields['bonus']);
									$SMSTmplt=($SMSTmplt!=false)?serialize($SMSTmplt):'';
									$fields['sms']="'".$SMSTmplt."'";
									
									$fields['add_comment']='"'.$DB->ForSql(Loc::getMessage("commerce.loyaltyprogram_BONUS_USER_BONUS_ADD", ["#NUM#"=>$fields['bonus']])).'"';
									
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
						}elseif($nextO['controlId']=='registerbyParentRef'){
							$this->setReferalBonuses($nextO);
						}
					}
				}
			}
			//return true;
			return $this->setBonus;
		}
		return false;
	}
	
	private function getRefSMS($userId, $bonus){
		$SMSSettings=$this->profileSetting['sms_settings'];
		if(!empty($SMSSettings['COMMERCE_LOYAL_ORDERING_SMS']['refTemplate'])){
			$userData=Loyaltyprogram\Tools::getUserData($userId);
			if(!empty($userData['PERSONAL_PHONE'])){
				$tmpPhone=$userData['PERSONAL_PHONE'];
			}elseif(!empty($userData['PERSONAL_MOBILE'])){
				$tmpPhone=$userData['PERSONAL_PHONE'];
			}
			if(!empty($tmpPhone)){
				$tmpPhone=\Bitrix\Main\UserPhoneAuthTable::normalizePhoneNumber($tmpPhone);
				return [
					"EVENT_NAME" => "COMMERCE_LOYAL_ORDERING_SMS",
					"MESSAGE_ID" => $SMSSettings['COMMERCE_LOYAL_ORDERING_SMS']['refTemplate'],
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
	
	private function getUserSMS($userId, $bonus){
		$SMSSettings=$this->profileSetting['sms_settings'];
		if(!empty($SMSSettings['COMMERCE_LOYAL_ORDERING_SMS']['userTemplate'])){
			$userData=Loyaltyprogram\Tools::getUserData($userId);
			if(!empty($userData['PERSONAL_PHONE'])){
				$tmpPhone=$userData['PERSONAL_PHONE'];
			}elseif(!empty($userData['PERSONAL_MOBILE'])){
				$tmpPhone=$userData['PERSONAL_PHONE'];
			}
			if(!empty($tmpPhone)){
				$tmpPhone=\Bitrix\Main\UserPhoneAuthTable::normalizePhoneNumber($tmpPhone);
				return [
					"EVENT_NAME" => "COMMERCE_LOYAL_ORDERING_SMS",
					"MESSAGE_ID" => $SMSSettings['COMMERCE_LOYAL_ORDERING_SMS']['userTemplate'],
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
	
	private function getUserEmail($userId, $bonus){
		$emailSettings=$this->profileSetting['email_settings'];
		if(!empty($emailSettings['COMMERCE_LOYAL_ORDERING']['userTemplate'])){
			$rsEM = \CEventMessage::GetByID($emailSettings['COMMERCE_LOYAL_ORDERING']['userTemplate']);
			if($arEM = $rsEM->Fetch()){
				$sites=array_keys($this->globalSettings->getSites());
				$cSite=!empty($this->order)?$this->order->getSiteId():$sites[0];
				global $DB;
				$results=$DB->Query('select * from b_user where id='.$userId);
				$arUser = $results->Fetch();
				if(!empty($arUser['EMAIL'])){
					return [
						"EVENT_NAME" => "COMMERCE_LOYAL_ORDERING",
						"MESSAGE_ID" => $emailSettings['COMMERCE_LOYAL_ORDERING']['userTemplate'],
						"LANGUAGE_ID" => LANGUAGE_ID,
						"LID" => $cSite,
						"C_FIELDS" => [
							"EMAIL_TO" => $arUser['EMAIL'],
							"BONUS"=>strval($bonus),
							"USER_ID" => $userId
						]
					];
				}
			}
		}
		return false;
	}
		
	private function setReferalBonuses($nextO=[]){
		global $DB;
		if($this->isTemporary && !empty($this->temporaryParentRef)){
			$rewards=$this->getChainReferalByFirstParent($this->temporaryParentRef);	
		}else{
			$rewards=$this->getChainReferal($this->userId);
		}
		//$newRew=[];
		//check selected referrals (groups, level)...

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
		//$newRew=(count($newRew)>0)?array_unique($newRew):$rewards;
		$newRew=(isset($newRew))?array_unique($newRew):$rewards;
		
		if(count($newRew)>0 && !empty($nextO['values'])){
			$params=$nextO['values'];

			$fields['bonus']=$params['bonus'];
			$fields['user_bonus']=$this->userId;
			$fields['order_id']=$this->order->getId();
			$fields['currency']='"'.$this->order->getCurrency().'"';
			$fields['profile_type']='"'.$this->profileSetting['type'].'"';
			$fields['profile_id']=$this->profileSetting['id'];
			$fields['status']='"inactive"';

			if(!empty($params['allow_bonus_max']) && $params['allow_bonus_max']=='N'){
				$params['bonus_max']=0;
			}

			if($fields['bonus']>0){
				
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
				
				foreach($newRew as $nextRew){
					$coeff=$this->getRankCoeff($nextRew);
					$fields['bonus']=$coeff*$params['bonus'];
					
					if($params['bonus_unit']=='percent' && $this->isPropBonus()==false){
						$price = $this->availablePrice-$this->bonusPay;
						$fields['bonus']=$params['bonus']*$price/100;
						if($params['bonus_round']=='more'){
							$fields['bonus']=ceil($fields['bonus']);
						}elseif($params['bonus_round']=='less'){
							$fields['bonus']=floor($fields['bonus']);
						}elseif($params['bonus_round']=='auto'){
							$fields['bonus']=round($fields['bonus']);
						}else{
							//$fields['bonus']=round($fields['bonus'], 2);
							$fields['bonus']=$fields['bonus'];
						}
					}
					if(!empty($params['bonus_max']) && (int) $params['bonus_max']>0 && $params['bonus_max']<$fields['bonus']){
						$fields['bonus']=(int) $params['bonus_max'];
					}
					if($fields['bonus']>0){
						$this->setBonus=true;
						$fields['add_comment']='"'.$DB->ForSql(Loc::getMessage("commerce.loyaltyprogram_BONUS_REF_BONUS_ADD", ["#NUM#"=>$fields['bonus']])).'"';
						
						$fields['bonus_start']=$fields['bonus'];
						
						$fields['user_id']=$nextRew;
						
						$mailTmplt=$this->getRefEmail($nextRew, $fields['bonus']);
						$mailTmplt=($mailTmplt!=false)?serialize($mailTmplt):'';
						$fields['email']="'".$mailTmplt."'";
						
						$SMSTmplt=$this->getRefSMS($nextRew, $fields['bonus']);
						$SMSTmplt=($SMSTmplt!=false)?serialize($SMSTmplt):'';
						$fields['sms']="'".$SMSTmplt."'";
						
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
	
	private function getRefEmail($userId, $bonus){
		$emailSettings=$this->profileSetting['email_settings'];
		if(!empty($emailSettings['COMMERCE_LOYAL_ORDERING']['refTemplate'])){
			global $DB;
			$results=$DB->Query('select * from b_user where id='.$userId);
			$arUser = $results->Fetch();
			if(!empty($arUser['EMAIL'])){
				$sites=array_keys($this->globalSettings->getSites());
				$cSite=!empty($this->order)?$this->order->getSiteId():$sites[0];
				if(!isset($this->refTemplate)){
					$rsEM = \CEventMessage::GetByID($emailSettings['COMMERCE_LOYAL_ORDERING']['refTemplate']);
					if($arEM = $rsEM->Fetch()){
						$this->refTemplate=$arEM;
					}else{
						$this->refTemplate=false;
					}
				}
				if($this->refTemplate!==false){
					return [
						"EVENT_NAME" => "COMMERCE_LOYAL_ORDERING",
						"MESSAGE_ID" => $emailSettings['COMMERCE_LOYAL_ORDERING']['refTemplate'],
						"LANGUAGE_ID" => LANGUAGE_ID,
						"LID" => $cSite,
						"C_FIELDS" => [
							"USER_ID" => $arUser['ID'],
							"EMAIL_TO" => $arUser['EMAIL'],
							"BONUS"=>strval($bonus)
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
        $tmpSettings['prop_bonus']=(empty($params['prop_bonus'])?'':$params['prop_bonus']);
		
		$saveFields['settings']="'".serialize($tmpSettings)."'";
		if($params['id']=='new'){
			$id = $DB->Insert($this->globalSettings->getTableProfilesList(), $saveFields, $err_mess.__LINE__);
		}else{
			$id = $DB->Update($this->globalSettings->getTableProfilesList(), $saveFields, "where id='".$params['id']."'", $err_mess.__LINE__);
		}
		$this->registerEvent('sale', 'OnSaleOrderSaved', 'orderBonusAdd');
		$this->registerEvent('sale', 'OnSaleOrderCanceled', 'AfterOrderCancel');
		$this->registerEvent('sale', 'OnSalePaymentEntitySaved', 'AfterOrderInnerPaymentRefund');
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

		$refRarentArr=[];
		for($i=1; $i<=$optns['ref_level']; $i++){
			$refRarentArr[$i]=Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_nLevel", ['#N#'=>$i]);
		}

		$mainParams=[[
            'controlId'=> 'sites',
            'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_ordering_sites"),
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
        ],[
            'controlId'=> 'levelParent',
            'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_ordering_levelParentRef"),
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
                //'defaultText'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_nLevel", ['#N#'=>1]),
                //'defaultValue'=> '1'
                'first_option'=> '...',
                'defaultText'=> '...',
                'defaultValue'=> ''
            ]]
        ],
            [
                'controlId'=> 'groupUsers',
                'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_ordering_GroupRef"),
                'group'=> false,
                'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_Group"),
                'showIn'=> ['registerbyRef', 'registerbyRefSubGrp','registerbyParentRef', 'registerbyParentRefSubGrp'],
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
            //---
            [
                'controlId'=> 'typeLink',
                'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_typeLink_hint"),
                'group'=> false,
                'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_typeLink"),
                'showIn'=> ['registerbyRef', 'registerbyRefSubGrp','registerbyParentRef', 'registerbyParentRefSubGrp'],
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
                    'values'=> Loyaltyprogram\Tools::getTypeLinkList(),
                    'id'=> 'type_link',
                    'name'=> 'type_link',
                    'show_value'=>'Y',
                    'first_option'=> '...',
                    'defaultText'=> '...',
                    'defaultValue'=> ''
                ]]
            ]
            //--
        ];

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
				'controlgroup'=> true,
				'group'=> false,
				'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_ShopParameters"),
				'showIn'=> ['registerbyRef', 'registerbyRefSubGrp', 'registerbyParentRef', 'registerbyParentRefSubGrp'],
				'children'=> [[
						'controlId' => 'orderPriceWithoutDiscount',
						'description' => Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_ordering_order_price_without_discount"),
						'group' => false,
						'label' => Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_ORDER_PRICE_WITHOUT_DISCOUNT"),
						'showIn' => ['registerbyRef', 'registerbyRefSubGrp', 'registerbyParentRef', 'registerbyParentRefSubGrp'],
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
					'controlId'=> 'skipBonusPay',
					'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_SKIP_hint_bonus_pay"),
					'group'=> false,
					'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_SKIP_bonus_pay"),
					'showIn'=> ['registerbyRef', 'registerbyRefSubGrp'],
					'control'=> [[
						'id'=> 'prefix',
						'type'=> 'prefix',
						'text'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_SKIP_bonus_pay")
					],[
						'type'=> 'select',
						'multiple'=>'Y',
						'values'=> [
							'yes'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_YES")
						],
						'id'=> 'skip_bonus_pay',
						'name'=> 'skip_bonus_pay',
						'first_option'=> '...',
						'defaultText'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_YES"),
						'defaultValue'=> 'yes'
					]]
				],[
					'controlId'=> 'skipDiscountProduct',
					'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_SKIP_hint_discount_product"),
					'group'=> false,
					'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_SKIP_DISCOUNT_PRODUCT"),
					'showIn'=> ['registerbyRef', 'registerbyRefSubGrp', 'registerbyParentRef', 'registerbyParentRefSubGrp'],
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
					'controlId'=> 'countOrders',
					'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_hint_countOrders"),
					'group'=> false,
					'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_countOrders"),
					'showIn'=> ['registerbyRef', 'registerbyRefSubGrp', 'registerbyParentRef', 'registerbyParentRefSubGrp'],
					'control'=> [[
						'id'=> 'prefix',
						'type'=> 'prefix',
						'text'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_countOrders")
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
					'controlId'=> 'product',
					'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_ordering_product"),
					'group'=> false,
					'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_PRODUCT"),
					'showIn'=> ['registerbyRef', 'registerbyRefSubGrp', 'registerbyParentRef', 'registerbyParentRefSubGrp'],
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
					'showIn'=> ['registerbyRef', 'registerbyRefSubGrp', 'registerbyParentRef', 'registerbyParentRefSubGrp'],
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
					'controlId'=> 'productIBlock',
					'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_ordering_product_iblock"),
					'group'=> false,
					'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_PRODUCT_IBLOCK"),
					'showIn'=> ['registerbyRef', 'registerbyRefSubGrp', 'registerbyParentRef', 'registerbyParentRefSubGrp'],
					'control'=> [[
						'id'=> 'prefix',
						'type'=> 'prefix',
						'text'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_PRODUCT_IBLOCK")
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
						'values'=> Loyaltyprogram\Tools::getCatalogs(),
						'id'=> 'product_iblock',
						'name'=> 'product_iblock',
						'first_option'=> '...',
						'defaultText'=> '...',
						'defaultValue'=> ''
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
					'controlId'=> 'orderPrice',
					'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_ordering_order_price"),
					'group'=> false,
					'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_ORDER_PRICE"),
					'showIn'=> ['registerbyRef', 'registerbyRefSubGrp', 'registerbyParentRef', 'registerbyParentRefSubGrp'],
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
					'showIn'=> ['registerbyRef', 'registerbyRefSubGrp', 'registerbyParentRef', 'registerbyParentRefSubGrp'],
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
				],[
					'controlId'=> 'turnover',
					'description'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_ORDER_ordering_turnover"),
					'group'=> false,
					'label'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_ORDER_TURNOVER"),
					'showIn'=> ['registerbyRef', 'registerbyRefSubGrp', 'registerbyParentRef', 'registerbyParentRefSubGrp'],
					'control'=> [[
						'id'=> 'prefix',
						'type'=> 'prefix',
						'text'=> Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_ORDER_TURNOVER")
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
				]]
			]/*,
			[
				'controlId'=> 'CondGroup',
				'group'=> true,
				'label'=> '',
				'defaultText'=> '',
				'showIn'=> [],
				'control'=> [Loc::getMessage("commerce.loyaltyprogram_CONDITION_PERFORM_OPERATIONS")]
			]*/
		];
        $filterProp=[];
		if(!empty($optns['filter_prop'])){
			$filterProp = explode(',',$optns['filter_prop']);
		}
        $alReadyProps=$this->getAlreadyProps();
		if(count($alReadyProps)>0){
		    $filterProp=array_merge($filterProp, $alReadyProps);
        }
		$paramsProp=\Commerce\Loyaltyprogram\Tools\CondFilter::GetControlShow(['SHOW_IN_GROUPS'=>['registerbyRef', 'registerbyRefSubGrp', 'registerbyParentRef', 'registerbyParentRefSubGrp'],'filter' => $filterProp]);

        //$paramsProp=\CCatalogCondCtrlIBlockProps::GetControlShow(['SHOW_IN_GROUPS'=>['registerbyRef', 'registerbyRefSubGrp', 'registerbyParentRef', 'registerbyParentRefSubGrp']]);

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

	//check Product ByProp and insert product skipproduct array
	private function checkProductByProp($prop, $condition){
		$tmpSkipProps=[0];
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
					case 'Contain':
						if(stripos($nextProp[$propId],$currenrtVal) !== false){
							$statusLogic=true;
							break;
						}
					break;
					case 'NotCont':
						if(stripos($nextProp[$propId],$currenrtVal) == false){
							$statusLogic=true;
							break;
						}
					break;
				}
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

	private function getUserTurnover(){
		if(!isset($this->usersTurnover)){
			$options=$this->globalSettings->getOptions();
			$where='USER_ID ='.$this->userId;
			$this->usersTurnover=['turnover'=>0, 'orders'=>0];
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
			if($row = $res->Fetch()){
				$this->usersTurnover['turnover']=$row['total_price'];
				$this->usersTurnover['orders']=$row['orders'];
			}
		}
		return $this->usersTurnover;
	}

	//props product processing
	private function isPropControl($node){
		$code=explode(':',$node['controlId']);
		if(count($code)==3){
			if(count($this->products)>0){
				if(empty($this->productProps)){
					\Bitrix\Main\Loader::includeModule('iblock');
					$dbEl = \CIBlockElement::GetList([], ["ID"=>$this->products]);
					while($obEl = $dbEl->GetNextElement()){
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
							$db_props = \CIBlockElement::GetProperty($code[1], $parentProduct[$fields['ID']]['ID'], [], []);
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

	private function getBonusPay(){
		$this->bonusPay=0;
        if($this->moduleOptions['bonus_as_discount']!='Y' && $this->order) {
            $res = \CSaleUserTransact::GetList([], array("USER_ID" => $this->userId, 'ORDER_ID' => $this->order->getId()));
            while ($arFields = $res->Fetch()) {
                if ($arFields['DEBIT'] == 'N') {
                    $this->bonusPay += $arFields['AMOUNT'];
                }
            }
        }elseif(!$this->order && !empty($_REQUEST['type']) && $_REQUEST['type']=='bonus_added' && !empty($_REQUEST['payed'])){
            $this->bonusPay=(int) $_REQUEST['payed'];
        }
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

	private function getIBlocks(){
		if(!isset($this->iblocksProduct)){
			$this->iblocksProduct=[];
			if(count($this->products)>0){
				$res = \CIBlockElement::GetList(false, ['ID'=>$this->products], ['IBLOCK_ID','ID']);
				while($el = $res->GetNext()){
				   $this->iblocksProduct[$el['ID']]=$el['IBLOCK_ID'];
				}
			}
		}
		return $this->iblocksProduct;
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
	
	private function checkConditionGroup($group, $type=false){
		$tmpSkipProps=[];
		if(empty($group['children']) || count($group['children'])==0){
			//set bonus
			return true;
		}else{
			$condition='AND';
			if($group['values']['All']=='AND' && $group['values']['True']=='False'){$condition='ANDNOT';}
			elseif($group['values']['All']=='OR' && $group['values']['True']=='True'){$condition='OR';}
			elseif($group['values']['All']=='OR' && $group['values']['True']=='False'){$condition='ORNOT';}

			$condStatusProps=false;
			foreach($group['children'] as $nextChildren){
				if(!empty($nextChildren['children'])){
					$condStatus=$this->checkConditionGroup($nextChildren);
				}else{
					$condStatus=false;
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
						
						//with prev calculate
						$skipPrevConditions=['personTypes','orderStatuses', 'paySystems', 'delyvery'];
						if($type && in_array($nextChildren["controlId"], $skipPrevConditions)){
							$condStatus=true;
							if($condition=='ANDNOT' || $condition=='OR'){
								$condStatus=false;
							}
						}else{							
							switch ($nextChildren['controlId']){
								case 'sites':
									$tmpSite=empty($this->order)?SITE_ID:$this->order->getSiteId();
									$condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $tmpSite, $nextChildren['values']['sites']);
								break;
								case 'groupUsers':
									$userGroup=\CUser::GetUserGroup($this->userId);
									$condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $userGroup, $nextChildren['values']['users']);
								break;
                                case 'ranksUser':
                                    $rank=$this->ranksObject->getRankUser($this->userId);
                                    $condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $rank, $nextChildren['values']['ranks']);
                                break;
								case 'typeLink':
									/*$availableTypes=1;
									$refType=$this->globalSettings->getUserRefData($this->userId, 'type');
									if($refType=='link'){
										$availableTypes=2;
									}
									elseif($refType=='coupon'){
										$availableTypes=3;
									}*/
									$condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], Loyaltyprogram\Tools::getIdTypeLinkUser($this->userId), $nextChildren['values']['type_link']);
								break;
								case 'product':
									$condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $this->getAllProducts(), $nextChildren['values']['product']);
								break;
								case 'productCat':
									$logic=$nextChildren['values']['logic'];
									$sections=$this->getSections();
									foreach ($this->basket as $basketItem){
										$nextProduct=$basketItem->getProductId();
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
									//$condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], array_unique($cats), $nextChildren['values']['product_cat']);
								break;
								case 'productIBlock':
									$logic=$nextChildren['values']['logic'];
									$iblocks=$this->getIBlocks();
									foreach ($this->basket as $basketItem){
										$nextProduct=$basketItem->getProductId();
										if(
											($logic=='Not' && in_array($iblocks[$nextProduct], $nextChildren['values']['product_iblock'])) ||
											($logic=='Equal' && !in_array($iblocks[$nextProduct], $nextChildren['values']['product_iblock']))
										){
											$this->conditionParameters['iBlockProducts'][]=$nextProduct;
										}elseif(//remove product from exception list
											($logic=='Not' && !in_array($iblocks[$nextProduct], $nextChildren['values']['product_iblock']) && $condition=='OR') ||
											($logic=='Equal' && in_array($iblocks[$nextProduct], $nextChildren['values']['product_iblock']) && $condition=='OR')
										){
											$this->conditionParameters['iBlockProductsNot'][]=$nextProduct;
										}
									}
									$condStatus=true;
									$condStatusProps=true;
									if($condition=='ANDNOT' || $condition=='OR'){
										$condStatus=false;
									}
								break;
								case 'orderPriceWithoutDiscount':
									if (!isset($this->discountProducts)) {
										$this->getDiscountProducts();
									}
									$basketPrice = $this->basket->getPrice();
									if (count($this->discountProducts) > 0) {
										foreach ($this->basket as $basketItem) {
											if (in_array($basketItem->getProductId(), $this->discountProducts)) {
												$basketPrice -= $basketItem->getPrice() * $basketItem->getQuantity();
											}
										}
									}
									$condStatus = $this->checkConditionChildren($nextChildren['values']['logic'], $basketPrice, $nextChildren['values']['order_price_without_discount']);
									break;
								case 'skipDiscountProduct':
									if(!isset($this->discountProducts)){
										$this->getDiscountProducts();
									}
									if(count($this->discountProducts)>0){
										foreach ($this->basket as $basketItem){
											if(in_array($basketItem->getProductId(), $this->discountProducts)){
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
								case 'skipBonusPay':
									$this->getBonusPay();
									$condStatus=true;
									$condStatusProps=true;
									if($condition=='ANDNOT' || $condition=='OR'){
										$condStatus=false;
									}
								break;
								case 'discount':									
									if(!empty($this->order)){
										$discountData=$this->order->getDiscount()->getApplyResult(true);
									}else{
										// $discounts = \Bitrix\Sale\Discount::buildFromBasket($this->basket, new \Bitrix\Sale\Discount\Context\Fuser($this->basket->getFUserId(true)));
										$order = \Bitrix\Sale\Order::create(\Bitrix\Main\Context::getCurrent()->getSite(), $this->userId);
										$order->setBasket($this->basket);
										$discounts = $order->getDiscount();
										if(!empty($discounts)){
											$discounts->calculate();
											$discountData = $discounts->getApplyResult(true);
										}
									}
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
								case 'turnover':
									$turnover=$this->getUserTurnover();
									$condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $turnover['turnover'], $nextChildren['values']['turnover']);
								break;
								case 'groupParentRef':
									if($this->isTemporary && !empty($this->temporaryParentRef)){
										$rewards=$this->getChainReferalByFirstParent($this->temporaryParentRef);	
									}else{
										$rewards=$this->getChainReferal($this->userId);
									}
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
									if($this->isTemporary && !empty($this->temporaryParentRef)){
										$rewards=$this->getChainReferalByFirstParent($this->temporaryParentRef);	
									}else{
										$rewards=$this->getChainReferal($this->userId);
									}
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
								case 'countOrders':
									$turnover=$this->getUserTurnover();
									$condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $turnover['orders'], $nextChildren['values']['count_orders']);
								break;
								case 'levelParent':
									$refRarentLevel=[];
									if($this->isTemporary && !empty($this->temporaryParentRef)){
										$rewards=$this->getChainReferalByFirstParent($this->temporaryParentRef);	
									}else{
										$rewards=$this->getChainReferal($this->userId);
									}
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
					}
				}
				
				//remove from exception lists
				if(
					!empty($this->conditionParameters['sectionProductsNot'])
					&& count($this->conditionParameters['sectionProductsNot'])>0
					&& !empty($this->conditionParameters['sectionProducts'])
					&& count($this->conditionParameters['sectionProducts'])>0
				){
					$this->conditionParameters['sectionProducts']=array_diff($this->conditionParameters['sectionProducts'], $this->conditionParameters['sectionProductsNot']);
				}
				if(
					!empty($this->conditionParameters['iBlockProductsNot'])
					&& count($this->conditionParameters['iBlockProductsNot'])>0
					&& !empty($this->conditionParameters['iBlockProducts'])
					&& count($this->conditionParameters['iBlockProducts'])>0
				){
					$this->conditionParameters['iBlockProducts']=array_diff($this->conditionParameters['iBlockProducts'], $this->conditionParameters['iBlockProductsNot']);
				}
				//e. o.remove from exception lists
				
				if($condition=='AND' && $condStatus==false){
					return false;
				}elseif($condition=='ANDNOT' && $condStatus==true){
					return false;
				}elseif($condition=='OR' && $condStatus==true){
					$this->conditionParameters['skipPriceProduct']=$this->conditionParameters['skipPropsProduct']=$this->conditionParameters['discountProducts']=$this->conditionParameters['sectionProducts']=$this->conditionParameters['iBlockProducts']=[];
					return true;
				}elseif($condition=='ORNOT' && $condStatus==false){
					$this->conditionParameters['skipPriceProduct']=$this->conditionParameters['skipPropsProduct']=$this->conditionParameters['discountProducts']=$this->conditionParameters['sectionProducts']=$this->conditionParameters['iBlockProducts']=[];
					return true;
				}
			}
			if(count($tmpSkipProps)>0){
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

    private function checkConditionGroupByProduct($group){
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
                    $isPropIblock=$this->isPropControl($nextChildren);
                    if($isPropIblock){
                        $tmpProps=$this->checkProductByProp($nextChildren, $condition);
                        $condStatus=!in_array($this->products[0], $tmpProps);

                    }else{
                        switch ($nextChildren['controlId']){
                            case 'sites':
                                $condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], SITE_ID, $nextChildren['values']['sites']);
                                break;
                            case 'groupUsers':
                                $userGroup=\CUser::GetUserGroup($this->userId);
                                $condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $userGroup, $nextChildren['values']['users']);
                                break;
                            case 'ranksUser':
                                $rank=$this->ranksObject->getRankUser($this->userId);
                                $condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $rank, $nextChildren['values']['ranks']);
                                break;
                            case 'typeLink':
                                $condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], Loyaltyprogram\Tools::getIdTypeLinkUser($this->userId), $nextChildren['values']['type_link']);
                                break;
                            case 'product':
                                $condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $this->getAllProducts(), $nextChildren['values']['product']);
                                break;
                            case 'productCat':
                                $sections=$this->getSections();
                                $cats=empty($sections[$this->products[0]])?[]:$sections[$this->products[0]];
                                $condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $cats, $nextChildren['values']['product_cat']);
                                break;
                            case 'productIBlock':
                                $logic=$nextChildren['values']['logic'];
                                $iblocks=$this->getIBlocks();
                                $blocks=empty($iblocks[$this->products[0]])?[]:$iblocks[$this->products[0]];
                                $condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $blocks, $nextChildren['values']['product_iblock']);
                                break;
                            case 'skipDiscountProduct':
                                global $USER;
                                $arDiscounts = \CCatalogDiscount::GetDiscountByProduct(
                                    $this->products[0],
                                    $USER->GetUserGroupArray(),
                                    "N",
                                    $this->productArray['CATALOG_GROUP'],
                                    SITE_ID
                                );
                                $discount=count($arDiscounts)==0?0:$this->products[0];
                                $condStatus=$this->checkConditionChildren('Not', $discount, $this->products[0]);
                                break;
                            case 'skipPriceProduct':
                                $condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $this->productArray['PRICE'], $nextChildren['values']['skip_price_product']);
                                break;
                            case 'orderPrice':
                                $condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $this->productArray['PRICE'], $nextChildren['values']['order_price']);
                                break;
                            case 'orderStatuses':
                            case 'paySystems':
                            case 'delyvery':
                            case 'personTypes':
                            case 'skipBonusPay':
                            case 'orderPriceWithoutDiscount':
                            case 'discount':
                                $condStatus=($condition=='ANDNOT' || $condition=='OR')?false:true;
                                break;
                            case 'turnover':
                                $turnover=$this->getUserTurnover();
                                $condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $turnover['turnover'], $nextChildren['values']['turnover']);
                                break;
                            case 'countOrders':
                                $turnover=$this->getUserTurnover();
                                $condStatus=$this->checkConditionChildren($nextChildren['values']['logic'], $turnover['orders'], $nextChildren['values']['count_orders']);
                                break;
                        }
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