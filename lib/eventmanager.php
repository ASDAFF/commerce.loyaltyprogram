<?
namespace Commerce\Loyaltyprogram;
use \Bitrix\Main\Localization\Loc,
	\Bitrix\Main\Application,
	\Bitrix\Main\Web\Cookie,
	\Bitrix\Sale;
Loc::loadMessages(__DIR__ .'/lang.php');

\Bitrix\Main\Loader::includeModule('sale');

/**
* event manager class
*/
class Eventmanager{
	
	/**
	* function for agent settting bonuses
	* @param int $idRow edit bonuses for current row or for all tables
	*/
	public static function manageBonuses($idRow=0){
		global $DB, $USER;
		$where='';
		$settings=Settings::getInstance();
		if($idRow>0){
			$where=' and id='.$idRow;
		}
		//check new bonuses
		$res=$DB->Query('select * from '.$settings->getTableBonusList().' where status="inactive" and date_add is not null'.$where.' order by profile_id, profile_type;');
		$currentTime=time();
		
		if(!is_object($USER)){
			$USER = new \CUser;
		}
		
		while($row = $res->Fetch()){
			$dateStart = new \DateTime($row['date_add']);
			if(!empty($row['date_remove'])){
				$dateEnd = new \DateTime($row['date_remove']);
				if($dateEnd->getTimestamp()<$currentTime){
					$comments=(empty($row['comments']))?[]:explode('###', $row['comments']);
					$comments[]=Loc::getMessage("commerce.loyaltyprogram_BONUS_OVERDUE", ["#NUM#"=>Tools::roundBonus($row['bonus'])]);
					$DB->Update($settings->getTableBonusList(), [
						'status'=>'"overdue"',
						'comments'=>'"'.$DB->ForSql(implode('###', $comments)).'"'
					], "where id='".$row['id']."'", $err_mess.__LINE__);
					continue;
				}
			}
			if($dateStart->getTimestamp()<=$currentTime){
				$DB->Update($settings->getTableBonusList(), [
					'status'=>'"active"'
				], "where id='".$row['id']."'", $err_mess.__LINE__);
				$type=strtoupper('commerce_loyal_'.$row['profile_type']);
				
				\CSaleUserAccount::UpdateAccount(
					$row['user_id'],
					$row['bonus'],
					$row['currency'],
					$type,
					$row['order_id'],
					$row['add_comment']
				);
				$transact=\CSaleUserTransact::GetList(['ID'=>'DESC'], [
					'AMOUNT'=>abs($row['bonus']),
					'CURRENCY'=>$row['currency'],
					'DESCRIPTION'=>$type,
					'USER_ID'=>$row['user_id']
				]);
		
				if($rowTransact = $transact->Fetch()){
					$DB->Query('insert into '.$settings->getTableTransactionList().' (bonus_id,transaction_id) values ('.$row['id'].','.$rowTransact["ID"].')');
				}
				if(!empty($row['email'])){
					$mailArr=unserialize($row['email']);
					if($mailArr!=false){
						\Bitrix\Main\Mail\Event::send($mailArr);
					}
				}
				
				if(!empty($row['sms'])){
					if(Tools::SMSActive()){
						$SMSArr=unserialize($row['sms']);
						$sms = new \Bitrix\Main\Sms\Event(
							$SMSArr['EVENT_NAME'],
							$SMSArr['C_FIELDS']
						);
						$sms->setTemplate($SMSArr['MESSAGE_ID']);
						$sms->setSite($SMSArr['LID']);
						$smsResult = $sms->send(true);
						//$strError .= implode(",", $smsResult->getErrorMessages());
					}
				}

				unset($row['status'], $row['email'], $row['notify']);
				//fire event activate bonus in refsystem
				$rowEvent=[
					"BONUS"=>$row['bonus'],
					"USER_ID"=>$row['user_id'],
					"CHILD_USER_ID"=>$row['user_bonus'],
					"ORDER_ID"=>$row['order_id'],
					"CURRENCY"=>$row['currency'],
					"PROFILE_TYPE"=>$row['profile_type'],
					"PROFILE_ID"=>$row['profile_id'],
					"ACTION_ID"=>$row['action_id'],
					"DATE_ADD"=>$row['date_add'],
					"DATE_REMOVE"=>$row['date_remove']
				];
				$event = new \Bitrix\Main\Event($settings->getModuleId(), "OnAfterActivateBonus", $rowEvent);
				$event->send();
			}
		}
		
		//overdue active bonuses
		$tmpOptions=$settings->getOptions();
		$generalSettings=new \Commerce\Loyaltyprogram\Modulesettings;
		//notify before overdue
		if(!empty($tmpOptions['notify_before_overdue']) && $tmpOptions['notify_before_overdue']=='Y'){
			$days=$tmpOptions['notify_delay_overdue'];
			if($tmpOptions['notify_delay_overdue_type']=='week'){
				$days=$days*7;
			}elseif($tmpOptions['notify_delay_overdue_type']=='month'){
				$days1=new \DateTime();
				$days2=new \DateTime('-'.$days.' month');
				$interval = $days2->diff($days1);
				$days=$interval->days;
			}
	
			$etemplate=$generalSettings->getEventSendByType('etemplate_before_overdue');
			$select='select
						commerce_loyal_bonuses.id as id,
						commerce_loyal_bonuses.bonus as bonus,
						commerce_loyal_bonuses.user_id as user_id,
						commerce_loyal_bonuses.profile_id as profile_id,
						DATEDIFF(commerce_loyal_bonuses.date_remove, now())  as diff,
						commerce_loyal_bonuses.date_remove as date_remove,
						commerce_loyal_bonuses.date_add as date_add,
						b_user.email as email
					from commerce_loyal_bonuses
					left join b_user
					on (b_user.ID=commerce_loyal_bonuses.user_id)
					where status="active"
						and date_remove is not null
						and bonus>0
						and notify="N"
						and DATEDIFF(commerce_loyal_bonuses.date_remove, now())>0
						and DATEDIFF(commerce_loyal_bonuses.date_remove, now())<'.$days.';';
			$res=$DB->Query($select);
			while($row = $res->Fetch()){
				if(!empty($row['email'])){
					$etemplate["C_FIELDS"]["EMAIL_TO"]=$row['email'];
					$etemplate["C_FIELDS"]["BONUS"]=round($row['bonus'], 2);
					\Bitrix\Main\Mail\Event::send($etemplate);
				}
				$DB->Update($settings->getTableBonusList(), [
					'notify'=>'"Y"'
				], "where id='".$row['id']."'", $err_mess.__LINE__);
			}
		}
		
		//notify overdue
		$etemplate=$generalSettings->getEventSendByType('etemplate_overdue');
		$select='select
					commerce_loyal_bonuses.id as id,
					commerce_loyal_bonuses.bonus as bonus,
					commerce_loyal_bonuses.user_id as user_id,
					commerce_loyal_bonuses.profile_id as profile_id,
					commerce_loyal_bonuses.currency as currency,
					DATEDIFF(commerce_loyal_bonuses.date_remove, now())  as diff,
					commerce_loyal_bonuses.date_remove as date_remove,
					commerce_loyal_bonuses.date_add as date_add,
					b_user.email as email
				from commerce_loyal_bonuses
				left join b_user
				on (b_user.ID=commerce_loyal_bonuses.user_id)
				where status="active"
					and date_remove is not null
					and bonus>0
					and DATEDIFF(commerce_loyal_bonuses.date_remove, now())<1;';
			$res=$DB->Query($select);
			while($row = $res->Fetch()){
				if(!empty($row['email']) && !empty($tmpOptions['notify_overdue']) &&  $tmpOptions['notify_overdue']=='Y'){
					$etemplate["C_FIELDS"]["EMAIL_TO"]=$row['email'];
					$etemplate["C_FIELDS"]["BONUS"]=round($row['bonus'], 2);
					\Bitrix\Main\Mail\Event::send($etemplate);
				}
				$comments=(empty($row['comments']))?[]:explode('###', $row['comments']);
				$comments[]=Loc::getMessage("commerce.loyaltyprogram_BONUS_OVERDUE", ["#NUM#"=>Tools::roundBonus($row['bonus'])]);
				$DB->Update($settings->getTableBonusList(), [
					'status'=>'"overdue"',
					'comments'=>'"'.$DB->ForSql(implode('###', $comments)).'"'
				], "where id='".$row['id']."'", $err_mess.__LINE__);
				
				
				$type='COMMERCE_LOYAL_BONUSOVERDUE';

				$currentAccount=\CSaleUserAccount::GetByUserID($row['user_id'], $row['currency']);
				if($currentAccount!==false){
                    $row['bonus']=min($row['bonus'], $currentAccount['CURRENT_BUDGET']);
                }

                $accBonus = 0-(float) $row['bonus'];
				
				\CSaleUserAccount::UpdateAccount(
					$row['user_id'],
					$accBonus,
					$row['currency'],
					$type,
					0,
					Loc::getMessage("commerce.loyaltyprogram_BONUS_OVERDUE", ["#NUM#"=>Tools::roundBonus($row['bonus'])])
				);
		
				$bonus = abs($accBonus);
				$transact=\CSaleUserTransact::GetList(['ID'=>'DESC'], [
					'AMOUNT'=>$bonus,
					'CURRENCY'=>$row['currency'],
					'DESCRIPTION'=>$type,
					'USER_ID'=>$row['user_id']
				]);
				if($rowTransact = $transact->Fetch()){
					$DB->Query('insert into '.$settings->getTableTransactionList().' (bonus_id,transaction_id) values ('.$row['id'].','.$rowTransact["ID"].')');
				}
			}
		
		$select = 'select * from commerce_loyal_bonuses where status = "order_payment_overdue_start"';
		$res = $DB->Query($select);
		while($row = $res->Fetch()){
			if($row['bonus']<0){
				$cale = \CSaleUserAccount::GetByUserID($row['user_id'],$row['currency']);
				if((float)$cale['CURRENT_BUDGET']>=abs($row['bonus'])&&$cale['LOCKED']=='N'){
					$tmpDebt = 0 - (float)$row['bonus'];
					\CSaleUserAccount::UpdateAccount(
                            $row['user_id'],
                            $tmpDebt,
                            $row['currency'],
                            "COMMERCE_LOYAL_BONUSREFUND_LATER",
                            $row['order_id'],
                            Loc::getMessage("commerce.loyaltyprogram_PROGRAM_BONUSREFUND_LATER")
                        );
					$DB->Update($settings->getTableBonusList(),[
						'status'=>'"order_payment_overdue_complete"',
						'bonus'=>'0'
					],"where id='".$row['id']."'", $err_mess.__LINE__);
				}elseif(abs((float)$row['bonus'])>(float)$cale['CURRENT_BUDGET']&&(float)$cale['CURRENT_BUDGET']!=0){
					$tmpBonusRefund = 0 - (float)$cale['CURRENT_BUDGET'];
					\CSaleUserAccount::UpdateAccount(
                        $row['user_id'],
                        $tmpBonusRefund,
                        $row['currency'],
                        "COMMERCE_LOYAL_BONUSREFUND_LATER",
                        $row['order_id'],
                        Loc::getMessage("commerce.loyaltyprogram_PROGRAM_BONUSREFUND_LATER")
                    );
					$tmpBonusRefund=(float)$row['bonus'] + (float)$cale['CURRENT_BUDGET'];
					$DB->Update($settings->getTableBonusList(),[
						'bonus'=>(float)$tmpBonusRefund
					],"where id='".$row['id']."'", $err_mess.__LINE__);
				}
			}
		}
		
		
		$select = 'select * from commerce_loyal_bonuses where status = "not-removed"';
		$res = $DB->Query($select);
		while($row = $res->Fetch()){
			\CSaleUserAccount::UpdateAccount(
                $row['user_id'],
                $row['bonus'],
                $row['currency'],
				"COMMERCE_LOYAL_GROUPS",//"BONUS_USER_GROUP_BONUSREMOVE",
				'',
				$row['add_comment']
            );
			$DB->Update($settings->getTableBonusList(),[
				'bonus'=>0,
				'status'=>'"removed"',
			],"where id='".$row['id']."'", $err_mess.__LINE__);
			if(!empty($row['email'])){
				$mailArr=unserialize($row['email']);
				if($mailArr!=false){
					\Bitrix\Main\Mail\Event::send($mailArr);
				}
			}
			
		}
		
		return '\Commerce\Loyaltyprogram\Eventmanager::manageBonuses();';
	}
	
	/**
	* function for birthday users bonus add
	*/
	public static function birthday(){
		$activeProgramIds=Profiles\Profile::getActiveProfileByType('Birthday');
		$settings=Settings::getInstance();
		$settingsOptions=$settings->getOptions();
        if(!empty($settingsOptions['bonus_add_active']) && $settingsOptions['bonus_add_active']=='Y') {
            foreach ($activeProgramIds as $nextProgramId) {
                $birthClass = Profiles\Profile::getProfileById($nextProgramId);
                $isRun = $birthClass->setBonus();
                if ($settingsOptions['ref_perform_all'] == 'N' && $isRun === true) {
                    break;
                }

            }
        }
		return '\Commerce\Loyaltyprogram\Eventmanager::birthday();';
	}
	
	/**
	* function for turnover users
	*/
	public static function turnover(){
		$activeProgramIds=Profiles\Profile::getActiveProfileByType('Turnover');
		$settings=Settings::getInstance();
		$settingsOptions=$settings->getOptions();
        if(!empty($settingsOptions['bonus_add_active']) && $settingsOptions['bonus_add_active']=='Y') {
            foreach ($activeProgramIds as $nextProgramId) {
                $turnoverClass = Profiles\Profile::getProfileById($nextProgramId);
                $isRun = $turnoverClass->setBonus();
                if ($settingsOptions['ref_perform_all'] == 'N' && $isRun === true) {
                    break;
                }

            }
        }
		return '\Commerce\Loyaltyprogram\Eventmanager::turnover();';
	}
	
	/**
	* function for completed users
	*/
	public static function completedProfile(){
		$activeProgramIds=Profiles\Profile::getActiveProfileByType('Profilecompleted');
		$settings=Settings::getInstance();
		$settingsOptions=$settings->getOptions();
        if(!empty($settingsOptions['bonus_add_active']) && $settingsOptions['bonus_add_active']=='Y') {
            foreach ($activeProgramIds as $nextProgramId) {
                $profileClass = Profiles\Profile::getProfileById($nextProgramId);
                $isRun = $profileClass->setBonus();
                if ($settingsOptions['ref_perform_all'] == 'N' && $isRun === true) {
                    break;
                }

            }
        }
		return '\Commerce\Loyaltyprogram\Eventmanager::completedProfile();';
	}
	
	/**
	* function for turnover referalodatel
	*/
	public static function turnoverRef(){
		$activeProgramIds=Profiles\Profile::getActiveProfileByType('TurnoverRef');
		$settings=Settings::getInstance();
		$settingsOptions=$settings->getOptions();
        if(!empty($settingsOptions['bonus_add_active']) && $settingsOptions['bonus_add_active']=='Y') {
            foreach ($activeProgramIds as $nextProgramId) {
                $turnoverClass = Profiles\Profile::getProfileById($nextProgramId);
                $isRun = $turnoverClass->setBonus();
                if ($settingsOptions['ref_perform_all'] == 'N' && $isRun === true) {
                    break;
                }

            }
        }
		return '\Commerce\Loyaltyprogram\Eventmanager::turnoverRef();';
	}
	
	/**
	* function for event register users
	* @param array $arFields data from main->OnAfterUserAdd
	*/
	public static function registerUser($arFields, $force=false){
		$idUser=$arFields['ID'];
		if(!empty($idUser)){
			$context = Application::getInstance()->getContext();
			$request = $context->getRequest();
			$settings=Settings::getInstance();
			$settingsOptions=$settings->getOptions();
			if(
				!$force &&
				$settingsOptions['ref_coupon_active']=='Y' &&
				(
					(!empty($request['action']) && $request['action']=='saveOrderAjax') || 
					(!empty($request['soa-action']) && $request['soa-action']=='saveOrderAjax') ||
					(!empty($request['save']) && $request['save']=='Y')
				)
			){
				//delay reg in refsystem while not werify coupon checking
				$_SESSION['sw24_register_ref']='Y';
			}else{
				$tmpCookieSource=$context->getRequest()->getCookie("skwb24_loyaltyprogram_source");
				$tmpGroupAdd=true;
				if($settingsOptions['ref_partner_active']=='Y' && !empty($tmpCookieSource)){
					$tmpCookieSource=urldecode($tmpCookieSource);
					$componentsData=new Components;
					$sites=$componentsData->getPartnerSiteList([
						'filter'=>['site'=>$tmpCookieSource, 'confirmed'=>'Y']
					]);
					if(count($sites)>0){
						$_SESSION['sw24_register_source_partnersite']=$sites[0]['id'];
						$listReferrals=new Referrals;
						$listReferrals->setReferral2(
							$sites[0]['user_id'],
							$idUser,
							'partnerSite'
						);
						Statistic::setRegisterBySite($sites[0]['user_id'], $idUser);

						if(!empty($settingsOptions['group_user'])){
							$tmpGroupAdd=false;
							$arGroups = \CUser::GetUserGroup($idUser);
							$arGroups[] = $settingsOptions['group_user'];
							\CUser::SetUserGroup($idUser, $arGroups);
						}
						
						$activeProgramIds=Profiles\Profile::getActiveProfileByType('Registration');
						foreach($activeProgramIds as $nextProgramId){
							$cProfile=Profiles\Profile::getProfileById($nextProgramId);
							$isRun=$cProfile->setBonus($idUser);
							if($settingsOptions['ref_perform_all']=='N' && $isRun===true){
								break;
							}
						}
					}
				//}elseif($settingsOptions['ref_link_active']=='Y'){
				}else{
					//register
					$profile=new Profiles\Profile;
					$profile->getChainReferal($idUser);
                    $cookieRefId=$request->getCookie("skwb24_loyaltyprogram_ref");
					if(!empty($settingsOptions['group_user']) && !empty($cookieRefId)){
						$tmpGroupAdd=false;
						$arGroups = \CUser::GetUserGroup($idUser);
						$arGroups[] = $settingsOptions['group_user'];
						\CUser::SetUserGroup($idUser, $arGroups);
					}

					$activeProgramIds=Profiles\Profile::getActiveProfileByType('Registration');
					$removeCookie=false;
					foreach($activeProgramIds as $nextProgramId){
						$cProfile=Profiles\Profile::getProfileById($nextProgramId);
						$isRun=$cProfile->setBonus($idUser);
						if($isRun===true){
							$removeCookie=true;
						}
						if($settingsOptions['ref_perform_all']=='N' && $isRun===true){
							break;
						}
					}
					
					if($removeCookie && !empty($cookieRefId)){
						$cookie = new Cookie("skwb24_loyaltyprogram_ref", $cookieRefId, (time() - 10));
						
						$tmpHost=$context->getServer()->getHttpHost();
						//if httphost return with port
						$hostArr=explode(':',$tmpHost);
						$tmpHost=$hostArr[0];
						//e.o. if httphost return with port
						
						$cookie->setDomain($tmpHost);
						$cookie->setHttpOnly(false);
						$context->getResponse()->addCookie($cookie);
						//$context->getResponse()->flush();
					}
				}
				
				//add to group by default
				if(!empty($settingsOptions['group_user']) && $tmpGroupAdd){
					$profile=new Profiles\Profile;
					$parents=$profile->getChainReferal($idUser);
					if(count($parents)>0){
						$arGroups = \CUser::GetUserGroup($idUser);
						$arGroups[] = $settingsOptions['group_user'];
						\CUser::SetUserGroup($idUser, $arGroups);
					}
				}
				
			}
		}
		return true;
	}
	
	/**
	* order bonus add
	*/
	public static function orderBonusAdd($event){
		$activeProgramIds=Profiles\Profile::getActiveProfileByType('Ordering');
		$settings=Settings::getInstance();
		$settingsOptions=$settings->getOptions();
        if(empty($settingsOptions['bonus_add_active']) ||  $settingsOptions['bonus_add_active']=='N'){
            return true;
        }
		foreach($activeProgramIds as $nextProgramId){
			$cProfile=Profiles\Profile::getProfileById($nextProgramId);
			$isRun=$cProfile->setBonus($event);
			if($settingsOptions['ref_perform_all']=='N' && $isRun===true){
				break;
			}
		}
		return true;
	}
	
	/**
	* copyright bonus add
	*/
	public static function copyrightBonusAdd($event){
		$activeProgramIds=Profiles\Profile::getActiveProfileByType('Copyrighter');
		$settings=Settings::getInstance();
		$settingsOptions=$settings->getOptions();
        if(!empty($settingsOptions['bonus_add_active']) && $settingsOptions['bonus_add_active']=='Y') {
            foreach ($activeProgramIds as $nextProgramId) {
                $cProfile = Profiles\Profile::getProfileById($nextProgramId);
                $isRun = $cProfile->setBonus($event);
                if ($settingsOptions['ref_perform_all'] == 'N' && $isRun === true) {
                    break;
                }
            }
        }
		return true;
	}
	
	/**
	* subscribe bonus add
	*/
	public static function subscribeBonusAdd($event){
		
		//fix if user who entered the mail for subscription
		if(!empty($_REQUEST['SENDER_SUBSCRIBE_EMAIL'])){
			global $USER;
			if($USER->IsAuthorized()){
				$writeEmail=$_REQUEST['SENDER_SUBSCRIBE_EMAIL'];
				$writeUserId=$USER->GetID();
				$isWrite=false;
			}
		}
		
		$activeProgramIds=Profiles\Profile::getActiveProfileByType('Subscribe');
		$settings=Settings::getInstance();
		$settingsOptions=$settings->getOptions();

            foreach ($activeProgramIds as $nextProgramId) {
                $cProfile = Profiles\Profile::getProfileById($nextProgramId);

                if (!empty($writeEmail) && !$isWrite) {
                    $cProfile->setSubscribeUser($writeUserId, $writeEmail);
                    $isWrite = true;
                }
                if(!empty($settingsOptions['bonus_add_active']) && $settingsOptions['bonus_add_active']=='Y') {
                    $isRun = $cProfile->setBonus($event);
                }else{
                    $isRun=true;
                }
                if ($settingsOptions['ref_perform_all'] == 'N' && $isRun === true) {
                    break;
                }
            }

		return true;
	}

	/**
	* the function records the user who entered the mail for subscription
	*/
	public static function subscribeFixUserWrite($event){
		global $USER;
		if($USER && $USER->IsAuthorized()){
			$fields = $event->getParameter("fields");
			if($fields['EVENT_NAME']=='SENDER_SUBSCRIBE_CONFIRM' && !empty($fields['C_FIELDS']['EMAIL'])){
				$activeProgramIds=Profiles\Profile::getActiveProfileByType('Subscribe');
				foreach($activeProgramIds as $nextProgramId){
					$cProfile=Profiles\Profile::getProfileById($nextProgramId);
					$cProfile->setSubscribeUser($USER->GetID(), $fields['C_FIELDS']['EMAIL']);
					break;
				}
			}
		}
	}
	
	/**
	* function for event main->OnProlog
	* checks and controls the presence of a referral link
	*/
	public static function checkReferalLink(){
		if(!defined('ADMIN_SECTION')){
			$settings=Settings::getInstance();
			$settingsOptions=$settings->getOptions();
			$context = Application::getInstance()->getContext();
			if(!empty($settingsOptions['ref_link_name']) && $settingsOptions['ref_link_active']=='Y'){
				$request = $context->getRequest();
				if(!empty($request[$settingsOptions['ref_link_name']])){
					$cookie=$request->getCookie("skwb24_loyaltyprogram_ref");
					if(empty($cookie) || $settingsOptions['cookie_rename']=='Y'){
						$cookieTime=!empty($settingsOptions['cookie_time'])?$settingsOptions['cookie_time']*86400:86400*365;
						$cookie = new Cookie("skwb24_loyaltyprogram_ref", $request[$settingsOptions['ref_link_name']], (time() + $cookieTime));

						$tmpHost=$context->getServer()->getHttpHost();
						//if httphost return with port
						$hostArr=explode(':',$tmpHost);
						$tmpHost=$hostArr[0];
						//e.o. if httphost return with port
						
						$cookie->setDomain($tmpHost);
						$cookie->setHttpOnly(false);
						$context->getResponse()->addCookie($cookie);
						
						//$context->getResponse()->flush();
						$_SESSION['skwb24_loyaltyprogram_ref']=$request[$settingsOptions['ref_link_name']];
						Statistic::setFollowingLink($request[$settingsOptions['ref_link_name']]);
						
						$uriString = $request->getRequestUri();
						$uri = new \Bitrix\Main\Web\Uri($uriString);
						$newUrl=$uri->deleteParams([$settingsOptions['ref_link_name']]);
						\LocalRedirect($newUrl);
						
					}
				}
			}
			if(!empty($settingsOptions['ref_partner_active']) && $settingsOptions['ref_partner_active']=='Y'){//referer partner site
				$tmpCookieSource=$context->getRequest()->getCookie("skwb24_loyaltyprogram_source");
				if(!empty($_SERVER['HTTP_REFERER']) && empty($tmpCookieSource)){
					$tmpHost=$context->getServer()->getHttpHost();
					$cookieTime=!empty($settingsOptions['cookie_time'])?$settingsOptions['cookie_time']*86400:86400*365;
					$newRef=Components::clearPartnerSite($_SERVER['HTTP_REFERER']);
					$componentsData=new Components;
					$activeSite=$componentsData->getPartnerSiteList([
						'filter'=>['site'=>$newRef, 'confirmed'=>'Y']
					]);
					if(count($activeSite)>0){
                        Statistic::setFollowingSite($activeSite[0]['user_id']);
						$cookieRef = new Cookie("skwb24_loyaltyprogram_source", $newRef, (time() + $cookieTime));
						$cookieRef->setDomain($tmpHost);
						$cookieRef->setHttpOnly(false);
						$context->getResponse()->addCookie($cookieRef);
					}
				}
			}
		}
		return true;
	}

	public static function registerByCoupon($ENTITY){
		$isNew = $ENTITY->getParameter("IS_NEW");
		$settings=Settings::getInstance();
		$settingsOptions=$settings->getOptions();
		if($isNew && 
		((!empty($_SESSION['sw24_register_ref']) && $_SESSION['sw24_register_ref']=='Y') || $settingsOptions['ref_coupon_active']=='Y')
		){
			global $DB, $USER;
			$order = $ENTITY->getParameter("ENTITY");
			$userId=$order->getUserId();
			$discountData = $order->getDiscount()->getApplyResult();
			$regByCoupon=false;
			if(!empty($discountData['COUPON_LIST'])){
				foreach($discountData['COUPON_LIST'] as $nextCoupon){
					$cCoupon=$nextCoupon['COUPON'];
					$res=$DB->Query('select * from '.$settings->getTableRefCoupons().' where coupon="'.$DB->ForSQL($cCoupon).'";');
					if($row = $res->Fetch()){
						$listReferrals=new Referrals;
						$isTemporaryLink=$listReferrals->isTemporaryLink($cCoupon);
						if(!$isTemporaryLink){
							$_SESSION['sw24_register_discount']=$nextCoupon['DATA']['DISCOUNT_ID'];
							$_SESSION['sw24_register_source_coupon']=$row['id'];
							$listReferrals->setReferral2(
								$row['user_id'],
								$userId,
								'coupon'
							);
							$regByCoupon=true;
						}
						break;
					}
				}
			}
			//$_SESSION['sw24_is_register_by_coupon']='Y';
			//if(!$regByCoupon){//fix if active registration by ref
				Eventmanager::registerUser(['ID'=>$userId], true);
			//}
		}
	}
	
	public static function AfterOrderSave($ENTITY){
		$isNew = $ENTITY->getParameter("IS_NEW");
		if($isNew/* && !defined('ADMIN_SECTION')*/){
			$order = $ENTITY->getParameter("ENTITY");
			$basket=$order->getBasket();
			Tools::controlCoupon($order);
			$propertyCollection = $order->getPropertyCollection();
			$personTypeId = $order->getPersonTypeId();
			$ar = $propertyCollection->getArray();
			foreach($ar['properties'] as $prop){
				if($prop['PERSON_TYPE_ID']==$personTypeId){
					if($prop['CODE']=='commerce_bonus'){
						$someProp = $propertyCollection->getItemByOrderPropertyId($prop['ID']);
						$somePropValue = $someProp->getValue();
						$bonusPayClasses=Profiles\Profile::getActiveProfileByType('Orderpay');
						$max_bonus=0;
						$tmpBonus = 0;
						foreach($bonusPayClasses as $bonusClass){
							$bonusPay=Profiles\Profile::getProfileById($bonusClass);
							$bonusPay->setOrder($order);
							$pay=$bonusPay->getMaxBonus($basket);
							if($pay>0&&$pay!==false){
								$max_bonus=$pay;
								$price=$basket->getPrice();
								$max_bonus=$max_bonus>$price?$price:$max_bonus;
								break;
							}
						}
						if((float)$somePropValue>$max_bonus){
							$tmpBonus = $max_bonus;
							$someProp->setValue($max_bonus);
							$someProp->save();
						}elseif((float)$somePropValue<=$max_bonus&&(float)$somePropValue!==0){
							$tmpBonus = (float)$somePropValue;
						}
						if($tmpBonus!=0){
							$settings=Settings::getInstance();
							$options=$settings->getOptions();
							$payActive =$options['bonus_pay_active'];
							$asDiscount = $options['bonus_as_discount'];
							if($payActive=='Y'){
								
								//fix for error on history order on problem marker
								$orderId = $order->getId();
								$order=\Bitrix\Sale\Order::load($orderId);
								//e. o. fix for error on history order on problem marker
								
								if($asDiscount=='N'){
									$paymentCollection = $order->getPaymentCollection();
									foreach($paymentCollection as $onePayment){
										$tmp_summ_payment=$onePayment->getSum()-$tmpBonus;
										$onePayment->setFields(array('SUM'=>$tmp_summ_payment));
										$onePayment->save();
										break;
									}
									$innerPS=$settings->getInnerPaySystem();
									if(!empty($innerPS)){
										$payment = $paymentCollection->createItem();
										//$paySystemService = Bitrix\Sale\PaySystem\Manager::getObjectById($innerPS['ID']);
										$payment->setFields([
											'SUM'=>$tmpBonus,
											'PAY_SYSTEM_ID'=>$innerPS['ID'],
											'PAY_SYSTEM_NAME'=>$innerPS['NAME']
										]);
										$payment->setPaid("Y");
										$payment->save();
									}
									$paymentCollection->save();
									$userId = $order->getUserId();
									$currency = $order->getCurrency();
									$orderId = $order->getId();
									$order->save();
									BonusManager::bonus_payment((float)$tmpBonus,$userId,$currency,$orderId);
									$tmpBonusWithdraw=$tmpBonus;
								}else{
									$basket = $order->getBasket();
									$price=$basket->getPrice();
									
									$tmpBonusWithdraw=$tmpBonus;

									$skipProducts=[];
                                    if(!empty($options['bonus_skip_condition_product']) && $options['bonus_skip_condition_product']=='Y'){
                                        $skipProducts=array_merge($skipProducts, $bonusPay->getSkipItems());
                                    }
									//product for discount
									if(!empty($options['bonus_skip_discount_product']) && $options['bonus_skip_discount_product']=='Y'){
										$items = $basket->getOrderableItems();
										foreach ($items as $item){
											if($item->getBasePrice()>$item->getPrice()){
												$skipProducts[]=$item->getProductId();
											}
										}
									}

									if(count($skipProducts)>0){
                                        $items = $basket->getOrderableItems();
                                        foreach ($items as $item){
                                            $pId=$item->getProductId();
                                            if(in_array($pId, $skipProducts)){
                                                $price-=$item->getPrice()*$item->getQuantity();
                                            }
                                        }
                                    }

									//remove from skipProducts if bonus > basket price without skipProducts
									if($price<$tmpBonus){
										foreach ($items as $item){
											if($item->getBasePrice()>$item->getPrice()){
												$skipProducts=array_diff($skipProducts, [$item->getProductId()]);
												$price+=$item->getPrice()*$item->getQuantity();
												if($price>=$tmpBonus){
													break;
												}
											}
										}
									}
									$koeff=$tmpBonus/$price;
									$arrayDiscount=[];
									foreach($basket as $basketItem){
										$item = $basketItem->getFields();
										
										$arItem = $item->getValues();
										if(in_array($arItem['PRODUCT_ID'], $skipProducts)){
											$tmpDiscount=0;
										}else{
											//$tmpDiscount=ceil($arItem["PRICE"]*$arItem["QUANTITY"]*$koeff);
											$tmpDiscount=$arItem["PRICE"]*$arItem["QUANTITY"]*$koeff;
											$tmpDiscount=min($tmpDiscount, $tmpBonus);
										}
										$tmpBonus=$tmpBonus-$tmpDiscount;

										
										$arrayDiscount[$arItem['PRODUCT_ID']]=$tmpDiscount;
									}
									//fix for float bonus pay
									if($tmpBonus>0){
									    foreach($arrayDiscount as &$nextDiscount){
									        if($nextDiscount>0){
									            $nextDiscount+=$tmpBonus;
									            break;
                                            }
                                        }
										//$arrayDiscount[$arItem['PRODUCT_ID']]=$arrayDiscount[$arItem['PRODUCT_ID']]+$tmpBonus;
									}
									$updateArray=[];
									foreach($basket as $basketItem){
										$item = $basketItem->getFields();
										$arItem = $item->getValues();
										$currentDiscount=$arrayDiscount[$arItem['PRODUCT_ID']]/$arItem["QUANTITY"];
										if($arItem["PRICE"]<$currentDiscount){
											$currentDiscount=$arItem["PRICE"];
										}
									
										$updateArray[]=[
											'PRICE'=>($arItem["PRICE"]-$currentDiscount),
											'BASE_PRICE'=>$arItem["BASE_PRICE"],
											'DISCOUNT_PRICE'=>($arItem["DISCOUNT_PRICE"]+$currentDiscount),
											'CUSTOM_PRICE'=>'Y'
										];
									}
									
									//set discount value
									$i=0;
									foreach($basket as $basketItem){
										$basketItem->setFields($updateArray[$i]);
										$i++;
									}

									$userId = $order->getUserId();
									$currency = $order->getCurrency();
									$orderId = $order->getId();
									//$basket->save();
									$order->save();
									BonusManager::bonus_payment((float)$tmpBonusWithdraw,$userId,$currency,$orderId,'Y');
								}
								
								//send email
								$bonusPay->sendEvent($tmpBonusWithdraw);
								//send sms
								$bonusPay->sendSMS($tmpBonusWithdraw);
							}
						}
						//$someProp->setValue('12');
					}
				}
			}
		}else{
			$order = $ENTITY->getParameter("ENTITY");
			$order = $order->getId();
			BonusManager::control_bonus($order);
		}
	}
	
	public static function onCouponApply(){
		Tools::controlCoupon();
	}

	public static function AfterOrderCancel($ENTITY){
		$order = $ENTITY->getParameter("ENTITY");
		if($order->isCanceled()){
			$orderId=$order->getId();
			$currency=$order->getCurrency();
			$userId = $order->getUserId();
			BonusManager::bonus_refund($orderId,$userId,$currency);
		}
	}
	
	public static function AfterOrderInnerPaymentRefund(\Bitrix\Main\Event $event){
		$payment = $event->getParameter("ENTITY");
		$oldValues = $event->getParameter("VALUES")['IS_RETURN'];
		 if ($payment->isInner()){
			$isReturn = $payment->getField('IS_RETURN');
			if($isReturn=='Y'&&$oldValues=='N'){
				$orderId = $payment->getField('ORDER_ID');
				$currency = $payment->getField('CURRENCY');
				$userId = $payment->getCollection()->getOrder()->getUserId();
				$summ=$payment->getField('SUM');
				BonusManager::bonus_payment_refund($userId,$orderId,$currency,$summ);   
			}    
		}
	}
	
	/**
	* function for reviews profile
	*/
	public static function ReviewAdd($id, $fields){
		if(!empty($id) && !empty($fields)){
            $settingsOptions=Settings::getInstance()->getOptions();
            if(!empty($settingsOptions['bonus_add_active']) && $settingsOptions['bonus_add_active']=='Y') {
                $activeProfileIds = Profiles\Profile::getActiveProfileByType('Reviews');
                foreach ($activeProfileIds as $nextProfileId) {
                    $cProfile = Profiles\Profile::getProfileById($nextProfileId);
                    $isRun = $cProfile->setBonus($id, $fields);
                    if ($settingsOptions['ref_perform_all'] == 'N' && $isRun === true) {
                        break;
                    }
                }
            }
		}
	}
	public static function ReviewAddIBlock($arParams){
		if($arParams["CREATED_BY"]){
			$authorId = $arParams["CREATED_BY"];
		} else {
			$rsElement = \CIBlockElement::GetList([],["ID"=>$arParams["ID"]],false,false,["CREATED_BY"]);
			if($arElement = $rsElement->fetch()) {
				$authorId = $arElement["CREATED_BY"];
			}
		}
		$activeProfileIds=Profiles\Profile::getActiveProfileByType('Reviews');
        $settingsOptions=Settings::getInstance()->getOptions();
        if(!empty($settingsOptions['bonus_add_active']) && $settingsOptions['bonus_add_active']=='Y') {
            foreach ($activeProfileIds as $nextProfileId) {
                $cProfile = Profiles\Profile::getProfileById($nextProfileId);
                $fields = [
                    "type" => "IBlock",
                    "ACTIVE" => $arParams["ACTIVE"],
                    "AUTHOR_ID" => 3 //$authorId
                ];
                $isRun = $cProfile->setBonus($arParams["IBLOCK_ID"], $fields);
                if ($settingsOptions['ref_perform_all'] == 'N' && $isRun === true) {
                    break;
                }
            }
        }
	}
	
	/**
	* function for insert soa component in page with sale.order.ajax
	*/
	public static function soaIntegration($tmpOrder, $arUserResult, $request, $arParams, &$arResult){
		if(\Bitrix\Main\Config\Option::get('commerce.loyaltyprogram','ref_insert_to_soa')=='Y'){
		$arResult['JS_DATA']['sw24_loyalty_max_bonus']=0;
			
			$bonusPayClasses=Profiles\Profile::getActiveProfileByType('Orderpay');
			foreach($bonusPayClasses as $bonus){
				$bonusPay=Profiles\Profile::getProfileById($bonus);
				$bonusPay->setOrder($tmpOrder);
				$pay=$bonusPay->getMaxBonus();
				if($pay>0&&$pay!==false){
					$arResult['JS_DATA']['sw24_loyalty_max_bonus']=$pay;
					break;
				}
			}
			
			$GLOBALS['APPLICATION']->IncludeComponent("commerce:order.ajax.bonus2", "", ['TMP_ORDER'=>$tmpOrder],false);
		}
	}
	
	/**
	* function for delete bonus and trasnsaction from tables module
	*/
	public static function clearBonusByUser($ID){
		if(!empty($_SESSION['sw24_delAccount_'.$ID])){
			Tools::clearBonusByUser($_SESSION['sw24_delAccount_'.$ID]);
		}
	}
	
	public static function clearBonusByUserBefore($ID){
		$arOldUserAccount = \CSaleUserAccount::GetByID($ID);
		if($arOldUserAccount!=false){
			$_SESSION['sw24_delAccount_'.$ID]=$arOldUserAccount['USER_ID'];
		}
	}
	
	/**
	* function for setting ranks user
	*/
	public static function setRanks(){
		Ranks::setRanks();
		return '\Commerce\Loyaltyprogram\Eventmanager::setRanks();';
	}
	
}
?>