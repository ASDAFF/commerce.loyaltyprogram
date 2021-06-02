<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
use Bitrix\Main\Localization\Loc,
	Bitrix\Main\Application;
Loc::loadMessages(__FILE__);
if(\Bitrix\Main\Loader::includeModule('commerce.loyaltyprogram')){
	global $USER;
	global $APPLICATION;
	if(!empty($_REQUEST['ajax']) && $_REQUEST['ajax']=='Y' && $USER->GetID()>0 && check_bitrix_sessid()){
		//echo \Bitrix\Main\Web\Json::encode($_REQUEST);
		$componentsData=new \Commerce\Loyaltyprogram\Components;
		$coupons=$componentsData->getCoupons();

		if(count($coupons)>0 && !empty($_REQUEST['newCoupon'])){
			foreach($coupons as $keyCoupon=>$nextcoupon){
				if($nextcoupon['COUPON']==$_REQUEST['old_name'] && $keyCoupon==$_REQUEST['rule_id'] && $nextcoupon['TYPE']=='user_prop'){
					$upd=$componentsData->updateCoupon($nextcoupon, $_REQUEST['newCoupon']);
					echo \Bitrix\Main\Web\Json::encode($upd);
					break;
				}
			}
		}elseif(isset($_REQUEST['newSite'])){
			$sites=[];
			if(empty($_REQUEST['newSite'])){
				$sites['error']=Loc::getMessage('sw24_loyaltyprogram.EMPTYSITE');
			}else{
				$newSite=$componentsData->addPartnerSite($_REQUEST['newSite']);
				if($newSite!=false){
					$sites['rows']=$newSite;
				}else{
					$sites['error']=Loc::getMessage('sw24_loyaltyprogram.ISALREADYSITE');
				}
			}
			echo \Bitrix\Main\Web\Json::encode($sites);
		}elseif(!empty((int) $_REQUEST['deleteSite'])){
			$componentsData->deletePartnerSite($_REQUEST['deleteSite']);
			$sites['rows']=$componentsData->getPartnerSiteList([
				'filter'=>['user_id'=>$USER->GetID()],
				'order'=>['by'=>'id', 'order'=>'desc']
			]);
			echo \Bitrix\Main\Web\Json::encode($sites);
		}elseif(!empty((int) $_REQUEST['checkSite'])){
			$check=$componentsData->checkPartnerSite($_REQUEST['checkSite']);
			$sites=[];
			if($check){
				$sites['rows']=$componentsData->getPartnerSiteList([
					'filter'=>['user_id'=>$USER->GetID()],
					'order'=>['by'=>'id', 'order'=>'desc']
				]);
			}else{
				$sites['error']=Loc::getMessage('sw24_loyaltyprogram.NOTCHECK');
			}
			echo \Bitrix\Main\Web\Json::encode($sites);
		}
		die();
	}
}
?>