<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
\Bitrix\Main\Loader::includeModule('sale');
\Bitrix\Main\Loader::includeModule('commerce.loyaltyprogram');

if(isset($_REQUEST['type'])&&$_REQUEST['type']=='bonus_added'){
	
	$basket = \Bitrix\Sale\Basket::loadItemsForFUser(
		\Bitrix\Sale\Fuser::getId(),
		\Bitrix\Main\Context::getCurrent()->getSite()
	);
	$bonuses=['bonus'=>0];
	if($basket){
		$tmpPrice=$basket->getPrice();
		if($tmpPrice>0){
			$activeProgramIds=Commerce\Loyaltyprogram\Profiles\Profile::getActiveProfileByType('Ordering');
			foreach($activeProgramIds as $nextProgramId){
				$cProfile=Commerce\Loyaltyprogram\Profiles\Profile::getProfileById($nextProgramId);
				$payed=$_REQUEST['payed']?:0;
				$isRun=$cProfile->getBonus($basket, $payed);
				if($isRun!==false){
					
					$currency=$isRun['currency'];
					$bonuses['bonus']+=$isRun['bonus'];
					if($settingsOptions['ref_perform_all']=='N'){
						break;
					}
				}
			}
			$bonuses['bonus_format']=CCurrencyLang::CurrencyFormat($bonuses['bonus'], $currency, true);
		}
	}
	
	echo \Bitrix\Main\Web\Json::encode($bonuses);
}
die();