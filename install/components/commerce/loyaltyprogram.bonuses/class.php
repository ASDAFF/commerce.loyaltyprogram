<?
use Bitrix\Main\Localization\Loc,
	Bitrix\Main\Application,
	Bitrix\Sale;

Loc::loadMessages(__FILE__);
if(\Bitrix\Main\Loader::IncludeModule("commerce.loyaltyprogram")){

	class loyaltyProgramMain extends \CBitrixComponent{
		
		public function executeComponent(){
			$tmpBasket = Sale\Basket::loadItemsForFUser(Sale\Fuser::getId(), Bitrix\Main\Context::getCurrent()->getSite());
			$this->arResult['BONUS']=0;
			
			$settings=Commerce\Loyaltyprogram\Settings::getInstance();
			$settingsOptions=$settings->getOptions();
			$this->arResult['CURRENCY']=$settingsOptions['currency'];
			if($tmpBasket){
				$tmpPrice=$tmpBasket->getPrice();
				if($tmpPrice>0){
					$activeProgramIds=Commerce\Loyaltyprogram\Profiles\Profile::getActiveProfileByType('Ordering');
					foreach($activeProgramIds as $nextProgramId){
						$cProfile=Commerce\Loyaltyprogram\Profiles\Profile::getProfileById($nextProgramId);
						$isRun=$cProfile->getBonus($tmpBasket);
						if($isRun!==false){
							$this->arResult['CURRENCY']=$isRun['currency'];
							$this->arResult['BONUS']+=$isRun['bonus'];
							if($settingsOptions['ref_perform_all']=='N'){
								break;
							}
						}
					}
					$this->arResult['BONUS_FORMAT']=CCurrencyLang::CurrencyFormat($this->arResult['BONUS'], $this->arResult['CURRENCY'], true);
				}
			}
			$this->IncludeComponentTemplate();
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