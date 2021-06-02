<?
use Bitrix\Main\Localization\Loc,
	Bitrix\Main\Application;
Loc::loadMessages(__FILE__);
if(\Bitrix\Main\Loader::IncludeModule("commerce.loyaltyprogram")){
	
class loyaltyProgramRoom extends \CBitrixComponent{
	
	public function onPrepareComponentParams($arParams){
		global $USER;
		$arParams['USER_ID']=0;
		if($USER->IsAuthorized()){
			$arParams['USER_ID']=$USER->GetID();
		}
		return $arParams;
	}
	
	private function getUsers($users){
		$usersArr=[];
		$rsUsers = CUser::GetList(($by="ID"), ($order="DESC"), ['ID'=>implode('|',$users)]);
		while ($arUser = $rsUsers->Fetch()){
			$usersArr[$arUser['ID']]=$arUser;
		}
		return $usersArr;
	}
	
	/*private function getUserName($user){
		$tmpUsers=[];
		foreach(['LAST_NAME', 'NAME', 'SECOND_NAME'] as $nextCode){
			if(!empty($user[$nextCode])){
				$tmpUsers[]=$user[$nextCode];
			}
		}
		return (count($tmpUsers)>0)?implode(' ', $tmpUsers):$user['LOGIN'];
	}*/
	
	private function setTreeUsers($level, $parentUser){
		if(!empty($this->tmpChain["chain"][$level])){
			foreach($this->tmpChain["chain"][$level] as $nextChain){
				if($nextChain['ref_user']==$parentUser){
					$nextChain['level']=$level+1;
					//$nextChain['userName']=$this->getUserName($this->users[$nextChain['user']]);
					$nextChain['userName']=!empty($this->users[$nextChain['user']])?$this->users[$nextChain['user']]:$nextChain['user'];
					$nextChain['login']=$this->users[$nextChain['user']]['LOGIN'];
					$this->arResult['CHAIN'][]=$nextChain;
					$this->setTreeUsers($nextChain['level'], $nextChain['user']);
				}
			}
		}
	}
	
	private function convertIdnaUrl($url){
		if(function_exists('idn_to_utf8')){
			$prefix='';
			if(strpos($url, 'http:')!==false){
				$url=str_replace('http://', '', $url);
				$prefix='http://';
			}elseif(strpos($url, 'https:')!==false){
				$url=str_replace('https://', '', $url);
				$prefix='https://';
			}
			$url=(LANG_CHARSET=='windows-1251')?iconv('UTF-8' , 'CP1251' , idn_to_utf8($url)):idn_to_utf8($url);
			return $prefix.$url;
		}
		return $url;
	}
	
	public function executeComponent(){
		\CJSCore::Init(['popup', 'bx', 'ajax']);
		
		global $APPLICATION;
		
		if(!empty($this->arParams['TITLE_REFERRAL'])){
			$APPLICATION->SetTitle($this->arParams['TITLE_REFERRAL']);
		}
		if(!empty($this->arParams['CHAIN_REFERRAL'])){
			$APPLICATION->AddChainItem($this->arParams['CHAIN_REFERRAL'], $APPLICATION->GetCurPage());
		}
		$componentsData=new \Commerce\Loyaltyprogram\Components;
		$moduleOptions=$componentsData->getModuleOptions();
		$this->arParams['REF_LINK_NAME']=$moduleOptions['ref_link_name'];
		$this->arParams['REF_LINK_VALUE']=$moduleOptions['ref_link_value'];
		
		//if($this->StartResultCache()){
			
			$this->arResult['ERRORS']=[];
			if($this->arParams['USER_ID']>0){
				
				$isActiveModule=$componentsData->getActiveModule();
				if($isActiveModule=='Y'){
					
					//partner sites
					if(!empty($moduleOptions['ref_partner_active']) && $moduleOptions['ref_partner_active']=='Y'){
						$this->arResult['PARTNER_SITE']=[
							'ACTIVE'=>true,
							'SITES'=>$componentsData->getPartnerSiteList([
								'filter'=>['user_id'=>$this->arParams['USER_ID']],
								'order'=>['by'=>'id', 'order'=>'desc']
							])
						];
					}
					if(!empty($this->arResult['PARTNER_SITE']['SITES']) && count($this->arResult['PARTNER_SITE']['SITES'])>0){
						foreach($this->arResult['PARTNER_SITE']['SITES'] as &$nextSite){
							$nextSite['site']=$this->convertIdnaUrl($nextSite['site']);
						}
					}
					
					$this->arResult['REF_LINK']=$componentsData->getRefLink();

                    //qrcode
                    if(!empty($this->arParams['SHOW_QRCODE']) && $this->arParams['SHOW_QRCODE']=='Y' && !empty($this->arResult['REF_LINK'])){
                        $qrcode_level=$this->arParams['QRCODE_LEVEL']?:'Q';
                        $qrcode_size=$this->arParams['QRCODE_SIZE']?:'4';
                        $qrcode_margin=$this->arParams['QRCODE_MARGIN']?:'8';
                        $this->arResult['QRCODE_IMG']=Commerce\Loyaltyprogram\Tools\Qrcode::show($this->arResult['REF_LINK'], $qrcode_level, $qrcode_size, $qrcode_margin);
                    }
					
					//pinycode
					if(function_exists('idn_to_utf8')){
						$domainName=(LANG_CHARSET=='windows-1251')?iconv('UTF-8' , 'CP1251' , idn_to_utf8($_SERVER['SERVER_NAME'])):idn_to_utf8($_SERVER['SERVER_NAME']);
						$this->arResult['REF_LINK']=str_replace($_SERVER['SERVER_NAME'], $domainName, $this->arResult['REF_LINK']);
					}
					
					$this->arResult['COUPONS']=$componentsData->getCoupons();
				}else{
					$this->arResult['REF_LINK']='';
				}
				
				$tmpChain=$componentsData->getRefChain();
				
				$this->arResult['CURRENCY']=$componentsData->getCurrency();
				$this->arResult['CHAIN']=[];
				
				$users=[];
				if(count($tmpChain)>0){
					if(count($tmpChain['allUsers'])>0){
						$users=$this->getUsers($tmpChain['allUsers']);
						$this->arResult['BONUSES']=$componentsData->getBonuses($tmpChain['allUsers']);
					}
				}
				if(!empty($tmpChain["chain"][0])){
				
					$this->tmpChain=$tmpChain;
					$this->users=$users;
					//$this->setTreeUsers(0, 0);
					
					foreach($tmpChain["chain"][0] as $nextChain){
						$nextChain['level']=1;
						//$nextChain['userName']=$this->getUserName($users[$nextChain['user']]);
						$nextChain['userName']=!empty($users[$nextChain['user']])?$users[$nextChain['user']]:$nextChain['user'];
						$nextChain['login']=$users[$nextChain['user']]['LOGIN'];
						$this->arResult['CHAIN'][]=$nextChain;
						$parentUser=$nextChain['user'];
						$this->setTreeUsers(1, $parentUser);
					}
				}
				$this->arResult['STATISTIC']=\Commerce\Loyaltyprogram\Statistic::getStatisticByLink($this->arParams['USER_ID']);
				if(!empty($this->arResult['COUPONS'])){
					foreach($this->arResult['COUPONS'] as $nextCoupon){
						if(empty($nextCoupon['COUPON'])){
							continue;
						}
						$this->arResult['STATISTIC']['COUPONS'][$nextCoupon['COUPON']]=\Commerce\Loyaltyprogram\Statistic::getStatisticByCoupons([$nextCoupon['COUPON']]);
					}
				};
			}else{
				$this->arResult['ERRORS'][]=Loc::getMessage('sw24_loyaltyprogram.NOT_AUTHORIZE');
			}
			if($this->arParams['VK'] == "Y")
				$this->arResult['SOCIAL']['VK'] = "https://vk.com/share.php?url=".urlencode($this->arResult['REF_LINK']);
			if($this->arParams['ODNOKLASSNIKI'] == "Y"){
				//$this->arResult['SOCIAL']['ODNOKLASSNIKI'] = "http://www.odnoklassniki.ru/dk?st.cmd=addShare&st.s=1&st._surl=".$this->arResult['REF_LINK'];
				$this->arResult['SOCIAL']['ODNOKLASSNIKI'] = "https://connect.ok.ru/offer?url=".urlencode($this->arResult['REF_LINK']);
			}
			if($this->arParams['FACEBOOK'] == "Y")
				$this->arResult['SOCIAL']['FACEBOOK'] = "http://www.facebook.com/sharer/sharer.php?u=".urlencode($this->arResult['REF_LINK']);
			if($this->arParams['TWITTER'] == "Y")
				$this->arResult['SOCIAL']['TWITTER'] = "http://twitter.com/share?url=".urlencode($this->arResult['REF_LINK']);
			if($this->arParams['GOOGLE'] == "Y")
				$this->arResult['SOCIAL']['GOOGLE'] = "http://plus.google.com/share?url=".urlencode($this->arResult['REF_LINK']);
			if($this->arParams['MOYMIR'] == "Y")
				$this->arResult['SOCIAL']['MOYMIR'] = "http://connect.mail.ru/share?share_url=".urlencode($this->arResult['REF_LINK']);
			if($this->arParams['MOYMIR'] == "Y")
				$this->arResult['SOCIAL']['MOYMIR'] = "http://connect.mail.ru/share?share_url=".urlencode($this->arResult['REF_LINK']);
			if($this->arParams['MOYMIR'] == "Y")
				$this->arResult['SOCIAL']['MOYMIR'] = "http://connect.mail.ru/share?share_url=".urlencode($this->arResult['REF_LINK']);
			if($this->arParams['WHATSAPP'] == "Y")
				$this->arResult['SOCIAL']['WHATSAPP'] = "whatsapp://send?text=".urlencode($this->arResult['REF_LINK']);
			if($this->arParams['VIBER'] == "Y")
				$this->arResult['SOCIAL']['VIBER'] = "viber://forward?text=".urlencode($this->arResult['REF_LINK']);
			if($this->arParams['TELEGRAM'] == "Y")
				$this->arResult['SOCIAL']['TELEGRAM'] = "tg://msg?text=".urlencode($this->arResult['REF_LINK']);

			//if($this->arParams['EMAIL'] == 'Y')
			//	$this->arResult['SOCIAL']['EMAIL']='Y';
			$this->IncludeComponentTemplate();
		//}
		
	}
}

}else{
	class loyaltyProgramRoom extends \CBitrixComponent{
		public function executeComponent(){
			ShowMessage(Loc::getMessage('sw24_loyaltyprogram.MODULE_NOT_INCLUDE'));
		}
	}
}
?>