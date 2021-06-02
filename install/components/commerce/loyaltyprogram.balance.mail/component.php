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

\Bitrix\Main\Loader::includeModule('sale');

$options=Commerce\Loyaltyprogram\Settings::getInstance()->getOptions();
$currency=empty($options['currency'])?'RUB':$options['currency'];

$arResult['BUDGET']=0;
$account=CSaleUserAccount::GetByUserID($userId, $currency);
if(!empty($account['CURRENT_BUDGET'])){
    $arResult['BUDGET']=$account['CURRENT_BUDGET'];
}
$arResult['BUDGET_FORMAT']=CurrencyFormat($arResult['BUDGET'], $currency);

if($arParams["SHOW_LAST_ADD_BONUSES"]=='Y'){
    $res = CSaleUserTransact::GetList(["ID" => "DESC"], ["USER_ID" => $userId, 'DEBIT'=>'Y']);
    if($arFields = $res->Fetch()){
        $arResult['LAST_ADD_BONUS']=[
            'BONUS'=>$arFields['AMOUNT'],
            'BONUS_FORMAT'=>CurrencyFormat($arFields['AMOUNT'], $arFields['CURRENCY']),
            'DATE_ADD'=>ConvertTimeStamp(strtotime($arFields['TRANSACT_DATE']), "SHORT", LANGUAGE_ID)
        ];
        if(!empty($arFields['NOTES'])){
            $arResult['LAST_ADD_BONUS']['NOTES']=$arFields['NOTES'];
        }
    }
}
if($arParams["SHOW_LAST_WRITEOFF_BONUSES"]=='Y'){
    $res = CSaleUserTransact::GetList(["ID" => "DESC"], ["USER_ID" => $userId, 'DEBIT'=>'N']);
    if($arFields = $res->Fetch()){

        $arResult['LAST_WRITEOFF_BONUS']=[
            'BONUS'=>$arFields['AMOUNT'],
            'BONUS_FORMAT'=>CurrencyFormat($arFields['AMOUNT'], $arFields['CURRENCY']),
            'DATE_ADD'=>ConvertTimeStamp(strtotime($arFields['TRANSACT_DATE']), "SHORT", LANGUAGE_ID)
        ];
        if(!empty($arFields['NOTES'])){
            $arResult['LAST_WRITEOFF_BONUS']['NOTES']=$arFields['NOTES'];
        }
    }
}

$this->IncludeComponentTemplate();
?>