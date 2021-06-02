<?
namespace Commerce\Loyaltyprogram\Pub;
use \Bitrix\Main\Application,
	Bitrix\Main\Localization\Loc,
	\Commerce\Loyaltyprogram;
/**
* type dirthday 
*/
class Bonuses{
	
	public static function orderPayGetMaxBonuses(){
		$maxBonus = 0;
		$bonusPayClasses=\Commerce\Loyaltyprogram\Profiles\Profile::getActiveProfileByType('Orderpay');
		foreach($bonusPayClasses as $bonus){
			$bonusPay=\Commerce\Loyaltyprogram\Profiles\Profile::getProfileById($bonus);
			$pay=$bonusPay->getMaxBonus();
			if($pay>0&&$pay!==false){
				$maxBonus=$pay;
				break;
			}
		}
		$settings=\Commerce\Loyaltyprogram\Settings::getInstance();
		$settingsOptions=$settings->getOptions();
		return [
				'BONUS'=>$maxBonus,
				'BONUS_FORMAT'=>\CurrencyFormat($maxBonus, $settingsOptions['currency']),
				'CURRENCY'=>$settingsOptions['currency']
			];
	}


	public static function orderingGetBonusByProduct($id, $priceId=0){
        \Bitrix\Main\Loader::includeModule('iblock');
        \Bitrix\Main\Loader::includeModule('catalog');
        \Bitrix\Main\Loader::includeModule('sale');
        $settings=\Commerce\Loyaltyprogram\Settings::getInstance();
        $settingsOptions=$settings->getOptions();
        $retArr=[
            'BONUS'=>0,
            'BONUS_FORMAT'=>\CurrencyFormat(0, $settingsOptions['currency']),
            'CURRENCY'=>$settingsOptions['currency']
        ];
        if(empty($priceId)){
            /*$group=(\CCatalogGroup::GetBaseGroup());
            $priceId='PRICE_'.$group["ID"];
            $catalogGroup=$group["ID"];*/
            global $USER;
            \Bitrix\Main\Loader::includeModule('catalog');
            $tmpPrices=\CCatalogProduct::GetOptimalPrice($id, 1, $USER->GetUserGroupArray());
            $priceId='PRICE_'.$tmpPrices["RESULT_PRICE"]["PRICE_TYPE_ID"];
            $catalogGroup=$tmpPrices["RESULT_PRICE"]["PRICE_TYPE_ID"];
        }else{
            $priceId='PRICE_'.$priceId;
            $catalogGroup=$priceId;
        }
        $activeProgramIds=\Commerce\Loyaltyprogram\Profiles\Profile::getActiveProfileByType('Ordering');
        $res = \CIBlockElement::GetList([], ['ID'=>$id], false, false, ['*', $priceId, 'QUANTITY']);
        $processedProducts=[];//for double row
        while($ob = $res->GetNext()){
            if(in_array($ob['ID'], $processedProducts)){continue;}
            $processedProducts[]=$ob['ID'];
            $ob['PRICE']=$ob[$priceId];
            $ob['CATALOG_GROUP']=$catalogGroup;
            foreach($activeProgramIds as $nextProgramId){
                $ordering=\Commerce\Loyaltyprogram\Profiles\Profile::getProfileById($nextProgramId);
                $nextSumm=$ordering->getBonusByProduct($ob);
                $retArr['BONUS']+=$nextSumm;
                if($settingsOptions['ref_perform_all']=='N' && $nextSumm>0){
                    break;
                }
            }
            unset($nextProgramId);
            $retArr['BONUS_FORMAT']=\CurrencyFormat($retArr['BONUS'], $settingsOptions['currency']);
        }
        return $retArr;
	}

	public static function orderingGetBonusBySumm($summ){
		if($summ>0){
			$orderingSumm=0;
			$activeProgramIds=\Commerce\Loyaltyprogram\Profiles\Profile::getActiveProfileByType('Ordering');
			$settings=\Commerce\Loyaltyprogram\Settings::getInstance();
			$settingsOptions=$settings->getOptions();
			foreach($activeProgramIds as $nextProgramId){
				$ordering=\Commerce\Loyaltyprogram\Profiles\Profile::getProfileById($nextProgramId);
				$nextSumm=$ordering->getBonusBySumm($summ);
				$orderingSumm+=$nextSumm;
				if($settingsOptions['ref_perform_all']=='N' && $nextSumm>0){
					break;
				}
			}
			$retArr=[
				'BONUS'=>$orderingSumm,
				'BONUS_FORMAT'=>\CurrencyFormat($orderingSumm, $settingsOptions['currency']),
				'CURRENCY'=>$settingsOptions['currency']
			];
			return $retArr;
		}
		return false;
	}
	
	public static function setBonusFromOuterSource($idUser, $summ=0){
		$isRun=false;
		$activeProgramIds=\Commerce\Loyaltyprogram\Profiles\Profile::getActiveProfileByType('Outersource');
		$settings=\Commerce\Loyaltyprogram\Settings::getInstance();
		$settingsOptions=$settings->getOptions();
		foreach($activeProgramIds as $nextProgramId){
			$source=\Commerce\Loyaltyprogram\Profiles\Profile::getProfileById($nextProgramId);
			$isRun=$source->setBonus($idUser, $summ);
			if($settingsOptions['ref_perform_all']=='N' && $isRun===true){
				break;
			}
		}
		return $isRun;
	}

}

?>