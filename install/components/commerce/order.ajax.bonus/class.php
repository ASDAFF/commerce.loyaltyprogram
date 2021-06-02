<?use Bitrix\Main,
	Bitrix\Main\Loader,
	Bitrix\Main\Localization\Loc;
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
Loc::loadMessages(__FILE__);
if(\Bitrix\Main\Loader::IncludeModule("commerce.loyaltyprogram")){
	class Commerce_OrderAjaxBonus extends \CBitrixComponent{
		public function onPrepareComponentParams($params){
			$params['MAX_BONUS'] = 0;
			$bonusPayClasses=\Commerce\Loyaltyprogram\Profiles\Profile::getActiveProfileByType('Orderpay');
			foreach($bonusPayClasses as $bonus){
				$bonusPay=\Commerce\Loyaltyprogram\Profiles\Profile::getProfileById($bonus);
				$pay=$bonusPay->getMaxBonus();
				if($pay>0&&$pay!==false){
					$params['MAX_BONUS']=$pay;
					break;
				}
			}
			$params['MESS_TITLE']=(empty($params['MESS_TITLE'])?Loc::getMessage('commerce.orderAjaxBonus_TITLE'):$params['MESS_TITLE']);
			$params['MESS_BONUS']=(empty($params['MESS_BONUS'])?Loc::getMessage('commerce.orderAjaxBonus_BONUS'):$params['MESS_BONUS']);
			$params['MESS_NO_BONUS']=(empty($params['MESS_NO_BONUS'])?Loc::getMessage('commerce.orderAjaxBonus_NO_BONUS'):$params['MESS_NO_BONUS']);
			$params['MESS_MAX']=(empty($params['MESS_MAX'])?Loc::getMessage('commerce.orderAjaxBonus_MAX_BONUS'):$params['MESS_MAX']);
			$params['MESS_BONUS_PAY_TOTAL']=(empty($params['MESS_BONUS_PAY_TOTAL'])?Loc::getMessage('commerce.orderAjaxBonus_MESS_BONUS_PAY_TOTAL'):$params['MESS_BONUS_PAY_TOTAL']);
			$params['MESS_ALL_BONUS']=(empty($params['MESS_ALL_BONUS'])?Loc::getMessage('commerce.orderAjaxBonus_ALL_BONUS'):$params['MESS_ALL_BONUS']);
			return $params;
		}
		public function executeComponent(){
			if(\Bitrix\Main\Config\Option::get('commerce.loyaltyprogram','bonus_pay_active')=='Y'){
				global $USER;
				$current_bonus = new \Commerce\Loyaltyprogram\Components();
				$basket = \Bitrix\Sale\Basket::loadItemsForFUser(
					   \Bitrix\Sale\Fuser::getId(),
					   \Bitrix\Main\Context::getCurrent()->getSite()
					);
				$basketItems = $basket->getBasketItems();
				$currency='RUB';
				foreach ($basketItems as $basketItem) {
					$currency=$basketItem->getField('CURRENCY');
					break;
				}
				$this->arResult['CURRENT_BONUS'] = $current_bonus->getUserAccount($currency);
				$currencyList = CCurrency::GetList(($by="name"),($order="asc"),LANGUAGE_ID);
				$currencyes=[];
				while($currency =$currencyList->Fetch()){
					$currencyes[$currency['CURRENCY']]=$currency;
				}
				$this->arParams['currency']=$currencyes;

				CJSCore::Init(array('currency')); 
				$this->IncludeComponentTemplate($componentPage);

			}
		}
	}
}