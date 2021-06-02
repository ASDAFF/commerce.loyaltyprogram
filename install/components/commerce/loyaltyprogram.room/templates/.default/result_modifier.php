<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
use Bitrix\Main\Localization\Loc;

function createName($userName, $arParams){
	if(!is_array($userName)){
		return '['.$userName.'] - '.Loc::getMessage('sw24_loyaltyprogram.TABLE_REF_EMPTY_USER');
	}
	if(empty($arParams['SHOW_USER_FIELDS']) || count($arParams['SHOW_USER_FIELDS'])==0){
		return '['.$userName['ID'].']';
	}elseif(in_array('LOGIN', $arParams['SHOW_USER_FIELDS']) && count($arParams['SHOW_USER_FIELDS'])==1){
		return '['.$userName['ID'].'] '.$userName['LOGIN'];
	}elseif(in_array('LOGIN', $arParams['SHOW_USER_FIELDS']) && count($arParams['SHOW_USER_FIELDS'])>1){
		$tmpName=[];
		foreach(['LAST_NAME', 'NAME', 'SECOND_NAME', 'EMAIL'] as $nextCode){
			if(in_array($nextCode, $arParams['SHOW_USER_FIELDS']) && !empty($userName[$nextCode])){
				$tmpName[]=$userName[$nextCode];
			}
		}
		$tmpName=(count($tmpName)==0)?'':' ('.implode(' ',$tmpName).')';
		return '['.$userName['ID'].'] '.$userName['LOGIN'].$tmpName;
	}else{
		foreach(['LAST_NAME', 'NAME', 'SECOND_NAME', 'EMAIL'] as $nextCode){
			if(in_array($nextCode, $arParams['SHOW_USER_FIELDS']) && !empty($userName[$nextCode])){
				$tmpName[]=$userName[$nextCode];
			}
		}
		$tmpName=(count($tmpName)==0)?'':' ('.implode(' ',$tmpName).')';
		return '['.$userName['ID'].'] '.$tmpName;
	}
}

if(!empty($arResult['CHAIN']) && count($arResult['CHAIN'])>0){
	$arResult['TOTAL_CHAIN']=[];
	foreach($arResult['CHAIN'] as &$nextChain){
		$nextChain['name']=createName($nextChain['userName'], $arParams);
		$bonus=(!empty($arResult['BONUSES'][$nextChain['user']]))?$arResult['BONUSES'][$nextChain['user']]:0;
		$arResult['TOTAL_LEVEL_CHAIN'][$nextChain['level']]['count']++;
		$arResult['TOTAL_LEVEL_CHAIN'][$nextChain['level']]['bonuses']+=$bonus['bonus'];
		$arResult['TOTAL_CHAIN']['count']++;
		$arResult['TOTAL_CHAIN']['bonuses']+=$bonus['bonus'];
	}
	$arResult['TOTAL_CHAIN']['bonuses_format']=CCurrencyLang::CurrencyFormat($arResult['TOTAL_CHAIN']['bonuses'], $arResult['CURRENCY']);
	foreach($arResult['TOTAL_LEVEL_CHAIN'] as &$nextChain){
		$nextChain['bonuses_format']=CCurrencyLang::CurrencyFormat($nextChain['bonuses'], $arResult['CURRENCY']);
	}
}
?>