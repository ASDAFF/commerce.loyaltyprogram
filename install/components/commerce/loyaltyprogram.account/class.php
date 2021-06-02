<?
use Bitrix\Main\Localization\Loc,
	Bitrix\Main\Application;

Loc::loadMessages(__FILE__);

if(\Bitrix\Main\Loader::IncludeModule("commerce.loyaltyprogram")){

class loyaltyProgramAccount extends \CBitrixComponent{
	
	public function onPrepareComponentParams($arParams){
		$arParams['DISPLAY_PAGER']=empty($arParams["DISPLAY_PAGER"])?'N':$arParams["DISPLAY_PAGER"];
		$arParams['PAGER_NAME']=empty($arParams["PAGER_NAME"])?Loc::getMessage('sw24_loyaltyprogram.PAGER_NAME'):$arParams["PAGER_NAME"];
		$arParams['PAGER_COUNT']=empty($arParams["PAGER_COUNT"])?20:$arParams["PAGER_COUNT"];
		
		$context = Application::getInstance()->getContext();
		$request = $context->getRequest();
		$arParams['FILTER']=[];
		$filterParams=['from_date','to_date','type_transactions'];
		$fromForms=['from_date','to_date','type_transactions'];
		
		if(isset($request['from_date'])){
			unset($_SESSION['FILTER']);
		}
		
		foreach($filterParams as $nextData){
			if(!empty($request[$nextData])){
				$arParams['FILTER'][$nextData]=$request[$nextData];
				$_SESSION['FILTER'][$nextData]=$request[$nextData];
			}
		}
		if(count($arParams['FILTER'])==0 && !empty($_SESSION['FILTER'])){
			foreach($filterParams as $nextData){
				if(!empty($_SESSION['FILTER'][$nextData])){
					$arParams['FILTER'][$nextData]=$_SESSION['FILTER'][$nextData];
				}
			}
		}
		$arParams['ORDER']=['date'=>'desc'];
		if(!empty($request['sort'])){
			$order=(empty($request['order']) || $request['order']=='desc')?'desc':'asc';
			$arParams['ORDER']=[$request['sort']=>$order];
		}
		global $USER;
		$arParams['USER_ID']=0;
		if($USER->IsAuthorized()){
			$arParams['USER_ID']=$USER->GetID();
		}
		return $arParams;
	}
	
	private function getTypetransaction(){
		return [
			'acc'=>[
				'COMMERCE_LOYAL_REGISTRATION'=>Loc::getMessage("sw24_loyaltyprogram.TRANSACT_COMMERCE_LOYAL_REGISTER"),
				'COMMERCE_LOYAL_BIRTHDAY'=>Loc::getMessage("sw24_loyaltyprogram.TRANSACT_COMMERCE_LOYAL_BIRTHDAY"),
				'COMMERCE_LOYAL_ORDERING'=>Loc::getMessage("sw24_loyaltyprogram.TRANSACT_COMMERCE_LOYAL_BONUSADD"),
				'COMMERCE_LOYAL_PROFILECOMPLETED'=>Loc::getMessage("sw24_loyaltyprogram.TRANSACT_COMMERCE_LOYAL_PROFILECOMPLETED"),
				'COMMERCE_LOYAL_SUBSCRIBE'=>Loc::getMessage("sw24_loyaltyprogram.TRANSACT_COMMERCE_LOYAL_SUBSCRIBE"),
				'COMMERCE_LOYAL_TURNOVER'=>Loc::getMessage("sw24_loyaltyprogram.TRANSACT_COMMERCE_LOYAL_TURNOVER"),
				'COMMERCE_LOYAL_TURNOVERREF'=>Loc::getMessage("sw24_loyaltyprogram.TRANSACT_COMMERCE_LOYAL_TURNOVERREF"),
				'COMMERCE_LOYAL_IMPORTBONUS'=>Loc::getMessage("sw24_loyaltyprogram.TRANSACT_COMMERCE_LOYAL_IMPORTBONUS"),
				'COMMERCE_LOYAL_OUTERSOURCE'=>Loc::getMessage("sw24_loyaltyprogram.TRANSACT_COMMERCE_LOYAL_OUTERSOURCE"),
				'COMMERCE_LOYAL_COPYRIGHTER'=>Loc::getMessage("sw24_loyaltyprogram.TRANSACT_COMMERCE_LOYAL_COPYRIGHTER"),
				'OUT_CHARGE_OFF'=>Loc::getMessage("sw24_loyaltyprogram.TRANSACT_OUT_CHARGE_OFF"),
				'ORDER_UNPAY'=>Loc::getMessage("sw24_loyaltyprogram.TRANSACT_ORDER_UNPAY"),
				'ORDER_CANCEL_PART'=>Loc::getMessage("sw24_loyaltyprogram.TRANSACT_ORDER_CANCEL_PART_SYSTEM"),
				'COMMERCE_LOYAL_BONUSREFUND_LATER'=>Loc::getMessage("sw24_loyaltyprogram.TRANSACT_ORDER_CANCEL_PART"),
				'COMMERCE_LOYAL_WRITEOFF'=>Loc::getMessage("sw24_loyaltyprogram.TRANSACT_COMMERCE_LOYAL_WRITEOFF_acc"),
				'COMMERCE_LOYAL_REVIEWS'=>Loc::getMessage("sw24_loyaltyprogram.TRANSACT_COMMERCE_LOYAL_REVIEWS_acc"),
				'COMMERCE_LOYAL_GROUPS'=>Loc::getMessage("sw24_loyaltyprogram.TRANSACT_COMMERCE_LOYAL_GROUPS_acc"),
				'MANUAL'=>Loc::getMessage("sw24_loyaltyprogram.TRANSACT_MANUAL"),
				'EXCESS_SUM_PAID'=>Loc::getMessage("sw24_loyaltyprogram.TRANSACT_COMMERCE_LOYAL_EXCESS_SUM_PAID"),
			],
			'withdraw'=>[
				'COMMERCE_LOYAL_ORDERPAY'=>Loc::getMessage("sw24_loyaltyprogram.TRANSACT_COMMERCE_LOYAL_BONUSPAY"),
				'ORDER_PAY'=>Loc::getMessage("sw24_loyaltyprogram.TRANSACT_ORDER_PAY"),
				'COMMERCE_LOYAL_WRITEOFF'=>Loc::getMessage("sw24_loyaltyprogram.TRANSACT_COMMERCE_LOYAL_WRITEOFF"),
				'COMMERCE_LOYAL_BONUSREFUND'=>Loc::getMessage("sw24_loyaltyprogram.TRANSACT_COMMERCE_LOYAL_BONUSREFUND"),
				'COMMERCE_LOYAL_GROUPS'=>Loc::getMessage("sw24_loyaltyprogram.TRANSACT_COMMERCE_LOYAL_GROUPS"),
				'COMMERCE_LOYAL_BONUSOVERDUE'=>Loc::getMessage("sw24_loyaltyprogram.TRANSACT_COMMERCE_LOYAL_BONUSOVERDUE"),
				'MANUAL'=>Loc::getMessage("sw24_loyaltyprogram.TRANSACT_MANUAL"),
				'BONUS_USER_GROUP_BONUSREMOVE'=>Loc::getMessage("sw24_loyaltyprogram.TRANSACT_MANUAL")
			]
		];
	}
	
	private function getHiddenTransaction(){
		return ['COMMERCE_LOYAL_BONUSREFUND_LATER'];
	}
	
	public function executeComponent(){
		global $APPLICATION;
		if(!empty($this->arParams['TITLE_BONUSES'])){
			$APPLICATION->SetTitle($this->arParams['TITLE_BONUSES']);
		}
		if($this->arParams['USER_ID']>0){
			$arNavParams = [
				"nPageSize" => $this->arParams["PAGER_COUNT"]
			];
			$arNavigation = CDBResult::GetNavParams($arNavParams);
						
			$arrFilter=[];
			if(!empty($this->arParams["FILTER"])){
				if(!empty($this->arParams["FILTER"]['type_transactions'])){
					$tmpType=$this->arParams["FILTER"]['type_transactions'];
					if(strpos($tmpType, '_withdraw')>0){
						$arrFilter['DEBIT']='N';
					}
					if(strpos($tmpType, '_acc')>0){
						$arrFilter['DEBIT']='Y';
					}
					$arrFilter['DESCRIPTION']=str_replace(['_withdraw', '_acc'], '', $tmpType);
				}
				if(!empty($this->arParams["FILTER"]['from_date'])){
					$arrFilter['>=TRANSACT_DATE']=$this->arParams["FILTER"]['from_date'];
				}
				if(!empty($this->arParams["FILTER"]['to_date'])){
					$arrFilter['<=TRANSACT_DATE']=$this->arParams["FILTER"]['to_date'];
				}
			}
			if(!empty($this->arParams['CHAIN_BONUSES'])){
				$APPLICATION->AddChainItem($this->arParams['CHAIN_BONUSES'], $APPLICATION->GetCurPage());
			}
			
			$componentsData=new \Commerce\Loyaltyprogram\Components;
			if(!empty($this->arParams['CURRENCY'])){
                $componentsData->setOptions(['currency'=>$this->arParams['CURRENCY']]);
			}

			$moduleOptions=$componentsData->getModuleOptions();
			$this->arParams['WRITE_OFF_SERVICE']=$moduleOptions['bonus_write_off_active'];
			
			$this->arResult['LAST_TRANSACTIONS']=$componentsData->getUserTransactions(['nTopCount'=>1]);
			$this->arParams['LAST_TRANSACTION']=(!empty($this->arResult['LAST_TRANSACTIONS'][0]['ID']))?$this->arResult['LAST_TRANSACTIONS'][0]['ID']:0;
			
			if($this->StartResultCache(false, false, false, $arNavigation, $arrFilter)){
	
				$this->arResult['ERRORS']=[];
				
				//write off service
				if($this->arParams['WRITE_OFF_SERVICE']=='Y'){
					$this->arResult['WRITEOFF']=$componentsData->writeOffList();
				
					$this->arResult['WRITEOFF']['AVAILABLE']=$componentsData->getWriteOffBonus();
					//$this->arResult['WRITEOFF']['CART']=$componentsData->getWriteOffCart();
				}
				
				$this->arResult['ACCOUNTS']=$componentsData->getUserAccount();
				$this->arResult['TRANSACTIONS']=$componentsData->getUserTransactions($arNavParams, $arrFilter, $this->arParams['ORDER']);
				$this->arResult['TOTAL_TRANSACTIONS']=$componentsData->getUserTotalTransactions($arNavParams, $arrFilter);
				$this->arResult['OVERDUE']=$componentsData->getOverdueTransactions();
				$tmpType=$componentsData->getTypeTransactions();
				$preTypes=$this->getTypetransaction();
				$this->arResult['TRANSACTIONS_NAME']=[];
				$this->arResult['TRANSACTIONS_TYPE']=['acc'=>[], 'withdraw'=>[], 'other'=>[]];
				
				//for save sorting
				foreach($preTypes['acc'] as $nextKey=>$nextVal){
					if(in_array($nextKey, $tmpType)){
						$this->arResult['TRANSACTIONS_TYPE']['acc'][$nextKey.'_acc']=$nextVal;
					}
				}
				foreach($preTypes['withdraw'] as $nextKey=>$nextVal){
					if(in_array($nextKey, $tmpType)){
						$this->arResult['TRANSACTIONS_TYPE']['withdraw'][$nextKey.'_withdraw']=$nextVal;
					}
				}
				//e. o. for save sorting
				foreach($tmpType as $nextType){
					if(!empty($preTypes['acc'][$nextType]) || !empty($preTypes['withdraw'][$nextType])){
						if(!empty($preTypes['acc'][$nextType])){
							//$this->arResult['TRANSACTIONS_TYPE']['acc'][$nextType.'_acc']=$preTypes['acc'][$nextType];
							$this->arResult['TRANSACTIONS_NAME']['acc'][$nextType]=$preTypes['acc'][$nextType];
							$this->arResult['TRANSACTIONS_NAME'][$nextType]=$preTypes['acc'][$nextType];
						}
						if(!empty($preTypes['withdraw'][$nextType])){
							//$this->arResult['TRANSACTIONS_TYPE']['withdraw'][$nextType.'_withdraw']=$preTypes['withdraw'][$nextType];
							$this->arResult['TRANSACTIONS_NAME']['withdraw'][$nextType]=$preTypes['withdraw'][$nextType];
							$this->arResult['TRANSACTIONS_NAME'][$nextType]=$preTypes['withdraw'][$nextType];
						}
					}else{
						$this->arResult['TRANSACTIONS_TYPE']['other'][$nextType]=$nextType;
						$this->arResult['TRANSACTIONS_NAME'][$nextType]=$nextType;
					}
				}
				
				$this->arResult["NAV_STRING"] = $componentsData->getRes()->GetPageNavStringEx(
					$navComponentObject,
					$this->arParams["PAGER_NAME"],
					$this->arParams["PAGER_TEMPLATE"],
					'N',
					$this,
					[]
				);
				
				$writeoff=new Commerce\Loyaltyprogram\Writeoff;
				$this->arResult["REQUISITES"]=$writeoff->getRequisites($this->arParams['USER_ID']);
				
				$this->IncludeComponentTemplate();
			}
		}else{
			ShowMessage(Loc::getMessage('sw24_loyaltyprogram.NOT_AUTHORIZE'));
		}
	}
}

}else{
	class loyaltyProgramAccount extends \CBitrixComponent{
		public function executeComponent(){
			ShowMessage(Loc::getMessage('sw24_loyaltyprogram.MODULE_NOT_INCLUDE'));
		}
	}
}
?>