<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$userId = intval($arParams["USER_ID"]);

if(empty($userId)){
	$orderId=$arParams["ORDER_ID"];
	if(!empty($orderId)){
		 \Bitrix\Main\Loader::includeModule('sale');
		 if(intval($arParams["ORDER_ID"])>0 && $order = \Bitrix\Sale\Order::load($orderId)){
			 $userId=$order->getUserId();
		 }elseif($order = \Bitrix\Sale\Order::loadByAccountNumber($orderId)){
			 $userId=$order->getUserId();
		 }
	}
}

if($userId <= 0){
	return;
}
if(!\Bitrix\Main\Loader::IncludeModule("commerce.loyaltyprogram")){
	return;
}

$componentsData=new \Commerce\Loyaltyprogram\Components($userId);
if($arParams["SHOW_REF_LINK"]=='Y'){
	$arResult['REF_LINK'] = $componentsData->getRefLink();
}
if($arParams["SHOW_REF_COUPONS"]=='Y'){
	$arResult['COUPONS'] = $componentsData->getCoupons();
}
if($arParams["SHOW_BONUSES"]=='Y'){
	$bonuses=$componentsData->getUserAccount();
	$arResult['BONUSES'] = $bonuses['AMOUNT_FORMAT'];
}

$this->IncludeComponentTemplate();
?>