<?
use Bitrix\Main\Localization\Loc,
	Bitrix\Main\Application;

Loc::loadMessages(__FILE__);

if(\Bitrix\Main\Loader::IncludeModule("commerce.loyaltyprogram")){

class loyaltyProgram extends \CBitrixComponent{
	
	public function executeComponent(){
		global $USER;
		if($USER->IsAuthorized()){
				//---------------------------------------------------------
				$arDefaultUrlTemplates404 = [
					"main" => "",
					"referral" => "referral/",
					"bonuses" => "bonuses/"
				];
				$arDefaultVariableAliases404 = [];
				$arDefaultVariableAliases = [];
				$arComponentVariables = ["REFERRAL", "BONUSES"];
				$SEF_FOLDER = "";
				$arUrlTemplates = [];
				if ($this->arParams["SEF_MODE"] == "Y"){
					$arVariables = [];

					$arUrlTemplates = CComponentEngine::MakeComponentUrlTemplates($arDefaultUrlTemplates404, $this->arParams["SEF_URL_TEMPLATES"]);
					$arVariableAliases = CComponentEngine::MakeComponentVariableAliases($arDefaultVariableAliases404, $this->arParams["VARIABLE_ALIASES"]);
					$componentPage = CComponentEngine::ParseComponentPath($this->arParams["SEF_FOLDER"], $arUrlTemplates, $arVariables);

					if(StrLen($componentPage) <= 0)
						$componentPage = "main";
					
					CComponentEngine::InitComponentVariables($componentPage, $arComponentVariables, $arVariableAliases, $arVariables);
					$SEF_FOLDER = $this->arParams["SEF_FOLDER"];
				}else{
					$arVariables = [];
					$arVariableAliases =  CComponentEngine::MakeComponentVariableAliases($arDefaultVariableAliases, $this->arParams["VARIABLE_ALIASES"]);
					CComponentEngine::InitComponentVariables(false, $arComponentVariables, $arVariableAliases, $arVariables);
					$componentPage = "";
					if (!empty($arVariables["BONUSES"]))
						$componentPage = "bonuses";
					elseif (!empty($arVariables["REFERRAL"]))
						$componentPage = "referral";
					else
						$componentPage = "main";
				}
				
				$this->arResult = [
					"FOLDER" => $SEF_FOLDER,
					"URL_TEMPLATES" => $arUrlTemplates,
					"VARIABLES" => $arVariables,
					"ALIASES" => $arVariableAliases
				];
				$this->IncludeComponentTemplate($componentPage);
				//---------------------------------------------------------
		}else{
			ShowMessage(Loc::getMessage('sw24_loyaltyprogram.NOT_AUTHORIZE'));
		}
	}
}

}else{
	class loyaltyProgram extends \CBitrixComponent{
		public function executeComponent(){
			ShowMessage(Loc::getMessage('sw24_loyaltyprogram.MODULE_NOT_INCLUDE'));
		}
	}
}
?>