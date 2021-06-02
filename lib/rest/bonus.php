<?php

namespace Commerce\Loyaltyprogram\Rest;

use \Bitrix\Rest\RestException,
    \Bitrix\Main\Localization\Loc,
    \Commerce\Loyaltyprogram\Entity;

\Bitrix\Main\Loader::includeModule('rest');
\Bitrix\Main\Loader::includeModule('sale');

Loc::loadMessages(__DIR__ . '/lang.php');
Loc::loadMessages(__FILE__);

class Bonus extends \IRestService
{

    public static function bonusAdd($params, $n, $server) {
        $errors = [];
        $userId = (int)$params['user_id'];
        $bonus = (float)$params['bonus'];
        if (!empty($params['date_remove'])) {
            $dateRemove = (int)$params['date_remove'];
            if(empty($dateRemove)){
                $errors[] = 'date remove invalid';
            }
        }
        if (!empty($params['date_add'])) {
            $dateAdd = (int)$params['date_add'];
            if(empty($dateAdd)){
                $errors[] = 'date add invalid';
            }
        }
        if (empty($userId)) {
            $errors[] = 'user id is not specified';
        }
        if (empty($bonus)) {
            $errors[] = 'bonus size is not specified';
        }
        if($bonus<0){
            $errors[] = 'the bonus must be greater than zero';
        }
        if (count($errors) > 0) {
            throw new RestException(implode(',', $errors));
        } else {
            $rsUser = \CUser::GetByID($userId);
            if (!$rsUser->Fetch()) {
                throw new RestException('user not found');
            } else {
                $options = \Commerce\Loyaltyprogram\Settings::getInstance()->getOptions();
                $comment = empty($params['comment']) ? '' : $params['comment'];
                $comment = 'rest: '.Loc::getMessage("commerce.loyaltyprogram_BONUS_USER_GROUP_BONUSACC", ["#NUM#" => $bonus, '#COMMENT#' => $comment]);
                $addFields = [
                    'bonus_start' => $bonus,
                    'bonus' => $bonus,
                    'user_id' => $userId,
                    'currency' => $options['currency'],
                    'profile_type' => 'Groups',
                    'profile_id' => 0,
                    'status' => 'inactive',
                    'date_add' => new \Bitrix\Main\Type\DateTime(),
                    //'date_remove'=>
                    'add_comment' => $comment
                ];
                if (!empty($dateRemove) && $bonus>0) {
                    $addFields['date_remove'] = \Bitrix\Main\Type\DateTime::createFromTimestamp($dateRemove);
                }
                if (!empty($dateAdd)) {
                    $addFields['date_add'] = \Bitrix\Main\Type\DateTime::createFromTimestamp($dateAdd);
                }
                if (!empty($params['order_id']) && (int) $params['order_id']>0) {
                    $addFields['order_id'] = (int) $params['order_id'];
                }

                //test bonus add with profile type, profile_id, action_id, etc.
                if(!empty($params['profile_type']) || !empty($params['order_id'])){
                    $profiles=new \Commerce\Loyaltyprogram\Profiles;
                    $profileList=$profiles->getListProfilesClear();
                    if(!empty($params['order_id']) && !empty($params['profile_type']) && $params['profile_type']!='Ordering'){
                        throw new RestException('type profile is invalid');
                    }
                    if(!empty($params['order_id'])){
                        \Bitrix\Main\Loader::includeModule('sale');
                        $order = \Bitrix\Sale\Order::getList([
                            'filter'=>[
                                'ID'=>$params['order_id'],
                                'USER_ID'=>$userId
                            ]
                        ])->fetch();
                        if(!$order){
                            throw new RestException('order not found');
                        }
                        $addFields['profile_type']='Ordering';
                    }
                    if(!empty($params['profile_type']) && empty($profileList[$params['profile_type']])){
                        throw new RestException('type profile is invalid');
                    }

                    if(!empty($params['profile_type']) && $params['profile_type']=='Ordering' && empty($params['order_id'])){
                        throw new RestException('order id is not specified');
                    }
                    if(!empty($params['profile_type'])){
                        $addFields['profile_type']=$params['profile_type'];
                    }
                    $profileRow = Entity\ProfilesTable::getList()->fetchAll();
                    foreach($profileRow as $keyProf=>$nextProfile){
                        if($nextProfile['type']!=$addFields['profile_type']){
                            unset($profileRow[$keyProf]);
                        }
                    }
                    if(count($profileRow)<1){
                        throw new RestException('profile of the specified type was not found');
                    }
                    if(!empty($params['profile_id'])){
                        foreach($profileRow as $keyProf=>$nextProfile){
                            if($nextProfile['id']!=$params['profile_id']){
                                unset($profileRow[$keyProf]);
                            }
                        }
                        if(count($profileRow)<1){
                            throw new RestException('the profile of the specified id was not found');
                        }else{
                            $addFields['profile_id']=$params['profile_id'];
                            sort($profileRow);
                            /*if(!empty($profileRow[0]["settings"]["condition"]["children"])){
                                foreach($profileRow[0]["settings"]["condition"]["children"] as $nextchildren){

                                }
                            }*/
                        }
                    }
                    if(!empty($params['action_id'])){
                        $tmpAction=0;
                        foreach($profileRow as $nextProfile){
                            if(!empty($nextProfile["settings"]["condition"]["children"])){
                                foreach($nextProfile["settings"]["condition"]["children"] as $nextchildren){
                                    if(!empty($nextchildren["values"]["number_action"]) && $nextchildren["values"]["number_action"]==$params['action_id']);
                                    $tmpAction=$params['action_id'];
                                    $addFields['action_id']=$params['action_id'];
                                    if(empty($params['profile_id'])){
                                        $addFields['profile_id']=$nextProfile['id'];
                                    }
                                    break(2);
                                }
                            }
                        }
                        if($tmpAction!=$params['action_id']){
                            throw new RestException('the action of the specified id was not found');
                        }
                    }
                    if(!empty($params['currency'])){
                        \Bitrix\Main\Loader::includeModule('currency');
                        $currency = \Bitrix\Currency\CurrencyTable::getList([
                            'filter'=>[
                                'CURRENCY'=>$params['currency']
                            ]
                        ])->fetch();
                        if(!$currency){
                           throw new RestException('the currency not found');
                        }
                        $addFields['currency']=$currency['CURRENCY'];
                    }
                }
                //e. o. test bonus add with profile type, profile_id, action_id, etc.

                $res = Entity\BonusesTable::add($addFields);
                $idBonus = $res->getId();
                if (empty($idBonus)) {
                    $errors = $res->getErrorMessages();
                    throw new RestException(implode(',', $errors));
                }
                return ['id' => $idBonus, 'status'=>'success'];
            }
        }
        throw new RestException('Unknown error');
    }

    public static function userAccount($userId) {
        $options = \Commerce\Loyaltyprogram\Settings::getInstance()->getOptions();
        return \CSaleUserAccount::GetByUserID($userId, $options['currency']);
    }

    public static function bonusRemove($params, $n, $server) {
        $userId = (int)$params['user_id'];
        if (empty($userId)) {
            throw new RestException('user not specified');
        }
        $bonus = (float)$params['bonus'];
        if (empty($bonus)) {
            throw new RestException('bonus not specified');
        }
        global $USER;
        $rsUser = \CUser::GetByID($userId);
        if (!$rsUser->Fetch()) {
            throw new RestException('user not found');
        }
        $limitArr = ['to zero', 'unlimited'];
        $cLimit = 'to zero';//write off to zero
        if (!empty($params['limit']) && in_array($params['limit'], $limitArr)) {
            $cLimit = $params['limit'];
        }
        if ($cLimit == 'to zero') {
            $userAccount = self::userAccount($userId);
            if ($userAccount == false || $userAccount['CURRENT_BUDGET'] <= 0) {
                throw new RestException('no money to write off');
            }
            $bonus = min($bonus, $userAccount['CURRENT_BUDGET']);


        }
        $comment = empty($params['comment']) ? '' : $params['comment'];
        $comment = Loc::getMessage("commerce.loyaltyprogram_BONUS_USER_GROUP_BONUSREMOVE", ["#NUM#" => $bonus, '#COMMENT#' => $comment]);
        $options = \Commerce\Loyaltyprogram\Settings::getInstance()->getOptions();
        $res = Entity\BonusesTable::add([
            'bonus_start' => $bonus * (-1),
            'bonus' => $bonus * (-1),
            'user_id' => $userId,
            'currency' => $options['currency'],
            'profile_type' => 'Remove',
            'profile_id' => 0,
            'status' => 'not-removed',
            'date_remove' => new \Bitrix\Main\Type\DateTime(),
            'add_comment' => $comment
        ]);
        $idBonus = $res->getId();
        if (empty($idBonus)) {
            $errors = $res->getErrorMessages();
            throw new RestException(implode(',', $errors));
        }
        return ['id' => $idBonus];

        throw new RestException('Unknown error');
    }

    public static function bonusDelete($params, $n, $server) {
        $id = (int)$params['id'];
        if (empty($id)) {
            throw new RestException('id not specified');
        }
        $res = \Commerce\Loyaltyprogram\Queue::deleteFromQueue($id);
        if (!$res) {
            throw new RestException('id not found');
        } else {
            return ['id' => $id, 'status' => 'success'];
        }
    }

    public static function bonusUpdate($params, $n, $server) {
        $id = (int)$params['id'];
        if (empty($id)) {
            throw new RestException('id not specified');
        }
        $data = Entity\BonusesTable::getList([
            'filter'=>['id'=>$id, 'status'=>'inactive']
        ]);
        if (!$arData = $data->fetch()) {
            throw new RestException('id not found');
        } else {
            $updFields=['add_comment'=>'update by rest'];
            if(!empty($params['bonus'])){
                $updFields['bonus']=(float) $params['bonus'];
                $updFields['bonus_start']=(float) $params['bonus'];
            }
            if(!empty($params['add_comment'])){
                $updFields['add_comment']='rest:'.$params['add_comment'];
            }
            if(!empty($params['user_id'])){
                $updFields['user_id']=(int) $params['user_id'];
            }
            if(!empty($params['order_id'])){
                $updFields['order_id']=(int) $params['order_id'];
            }
            if(!empty($params['profile_id'])){
                $updFields['profile_id']=(int) $params['profile_id'];
            }
            if(!empty($params['action_id'])){
                $updFields['action_id']=(int) $params['action_id'];
            }
            if(!empty($params['user_bonus'])){
                $updFields['user_bonus']=(int) $params['user_bonus'];
            }
            if(!empty($params['currency'])){
                $lcur = \CCurrency::GetList(($by="name"), ($order="asc"), LANGUAGE_ID);
                $arCur=[];
                while($lcur_res = $lcur->Fetch()){
                    $arCur[]=$lcur_res['CURRENCY'];
                }
                if(!in_array($params['currency'], $arCur)){
                    throw new RestException('currency is invalid');
                }
                $updFields['currency']=$params['currency'];
            }
            if(!empty($params['profile_type'])){
                $profiles=new \Commerce\Loyaltyprogram\Profiles;
                $profileList=$profiles->getListProfilesClear();
                if(empty($profileList[$params['profile_type']])){
                    throw new RestException('type profile is invalid');
                }
                $updFields['profile_type']=$params['profile_type'];
            }
            if (!empty($params['date_remove'])) {
                $updFields['date_remove'] = \Bitrix\Main\Type\DateTime::createFromTimestamp($params['date_remove']);
            }
            if (!empty($params['date_add'])) {
                $updFields['date_add'] = \Bitrix\Main\Type\DateTime::createFromTimestamp($params['date_add']);
            }
            $res = Entity\BonusesTable::update($id, $updFields);
            if (!$res->isSuccess()) {
                $errors = $res->getErrorMessages();
                throw new RestException(implode(',', $errors));
            }
            return ['id' => $id, 'status' => 'success'];
        }
    }

    public static function bonusList($params, $n, $server) {
        $limit = (!empty($params['limit']) && $params['limit'] < 50) ? $params['limit'] : 50;
        $offset = empty($params['offset']) ? 0 : $params['offset'];
        $dataFilter = [
            'limit' => $limit,
            'count_total' => true,
            'offset' => $offset,
        ];
        if (!empty($params['order'])) {
            foreach ($params['order'] as $keyOrder => $valOrder) {
                $dataFilter['order'][$keyOrder] = $valOrder;
            }
        }
        if (!empty($params['filter'])) {
            foreach ($params['filter'] as $keyOrder => $valOrder) {
                $clearKey = str_replace(['!', '=', '>', '<'], '', $keyOrder);
                if ($clearKey == 'date_add' || $clearKey == 'date_remove') {
                    $dataFilter['filter'][$keyOrder] = \Bitrix\Main\Type\DateTime::createFromTimestamp($valOrder);
                } else {
                    $dataFilter['filter'][$keyOrder] = $valOrder;
                }
            }
        }
        $data = Entity\BonusesTable::getList($dataFilter);
        $result = [];
        while ($arData = $data->fetch()) {
            $arData['date_add'] = empty($arData['date_add']) ?: $arData['date_add']->toString();
            $arData['date_remove'] = empty($arData['date_remove']) ?: $arData['date_remove']->toString();
            $result[] = $arData;
        }

        $nav = [
            'count' => $data->getCount(),
            'offset' => $offset
        ];
        return self::setNavData($result, $nav);

        throw new RestException('Unknown error');
    }

}