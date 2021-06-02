<?
use Bitrix\Main\Localization\Loc,
	Bitrix\Main\Application;

Loc::loadMessages(__FILE__);

if(\Bitrix\Main\Loader::IncludeModule("commerce.loyaltyprogram")){

class loyaltyProgramMain extends \CBitrixComponent{
	
	public function onPrepareComponentParams($arParams){
		$arParams['R_AUTOSHOW']=empty($arParams['R_AUTOSHOW'])?'Y':$arParams['R_AUTOSHOW'];
		return $arParams;
	}
	
	public function executeComponent(){
		global $USER;
		if($USER->IsAuthorized()){
			if($this->StartResultCache()){
				$this->arResult['PATH_ACCOUNT']=$this->arParams['PATH_ACCOUNT'];
				$this->arResult['PATH_REFERRAL']=$this->arParams['PATH_REFERRAL'];
				if($this->arParams['R_AUTOSHOW']=='Y'){
					$componentsData=new \Commerce\Loyaltyprogram\Components;
					$isActiveModule=$componentsData->getActiveModule();
					if($isActiveModule=='Y'){
						$this->arResult['REF_LINK']=$componentsData->getRefLink();
						$this->arResult['COUPONS']=$componentsData->getCoupons();
					}else{
						$this->arResult['REF_LINK']='';
					}
					$this->arResult['ACTIVE_ROOM']='Y';
					if(count($this->arResult['COUPONS'])==0 && empty($this->arResult['REF_LINK'])){
						$this->arResult['ACTIVE_ROOM']='N';
					}
				}
				$this->IncludeComponentTemplate();
			}
		}else{
			ShowMessage(Loc::getMessage('sw24_loyaltyprogram.NOT_AUTHORIZE'));
		}
	}
}

}else{
	class loyaltyProgramMain extends \CBitrixComponent{
		public function executeComponent(){
			ShowMessage(Loc::getMessage('sw24_loyaltyprogram.MODULE_NOT_INCLUDE'));
		}
	}
}
?>