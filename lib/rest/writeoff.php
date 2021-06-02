<?php

namespace Commerce\Loyaltyprogram\Rest;

use \Bitrix\Rest\RestException,
    \Bitrix\Main\Localization\Loc,
    \Commerce\Loyaltyprogram,
    \Commerce\Loyaltyprogram\Entity;

\Bitrix\Main\Loader::includeModule('rest');
\Bitrix\Main\Loader::includeModule('sale');

Loc::loadMessages(__DIR__ .'/lang.php');
Loc::loadMessages(__FILE__);

class Writeoff extends \IRestService
{
    private static function getRequisites($userId){
        $requisites=[];
        if(!empty($userId)){
            $result=Entity\UserRequisitesTable::getList(['filter'=>['user_id'=>$userId, 'active'=>'Y']]);
            while ($arUser = $result->fetch()){
                $requisites[]=$arUser;
            }
        }
        return $requisites;
    }

    public static function writeOffList($params, $n, $server){
        $limit=(!empty($params['limit']) && $params['limit']<50)?$params['limit']:50;
        $offset=empty($params['offset'])?0:$params['offset'];
        $dataFilter=[
            'limit'=>$limit,
            'count_total' => true,
            'offset' => $offset,
        ];
        if(!empty($params['order'])){
            foreach($params['order'] as $keyOrder=>$valOrder){
                $dataFilter['order'][$keyOrder]=$valOrder;
            }
        }
        if(!empty($params['filter'])){
            foreach($params['filter'] as $keyOrder=>$valOrder){
                $clearKey=str_replace(['!', '=', '>', '<'], '', $keyOrder);
                if($clearKey=='date_order' || $clearKey=='date_change'){
                    $dataFilter['filter'][$keyOrder] = \Bitrix\Main\Type\DateTime::createFromTimestamp($valOrder);
                }else {
                    $dataFilter['filter'][$keyOrder] = $valOrder;
                }
            }
        }
        $data = Entity\WriteOffTable::getList($dataFilter);
        $result=[];
        $statuses=Entity\WriteOffTable::getStatuses();
        while ($arData = $data->fetch()) {
            $arData['date_change']=$arData['date_change']->toString();
            $arData['date_order']=$arData['date_order']->toString();
            $arData['status_text']=!empty($statuses[$arData['status']])?$statuses[$arData['status']]:$arData['status'];
            $result[]=$arData;
        }

        $nav = [
            'count' => $data->getCount(),
            'offset' => $offset
        ];

        return self::setNavData($result, $nav);

        //throw new RestException('Unknown error');
    }

    public static function writeOffAdd($params, $n, $server){
        $errors=[];
        if(empty($params['user_id'])){
            $errors[]='user id not specified';
        }
        if(empty($params['bonus']) || $params['bonus']<=0){
            $errors[]='bonus size is not specified';
        }
        if(count($errors)){
            throw new RestException(implode(', ',$errors));
        }else{
            $writeOffClass=new Loyaltyprogram\Profiles\Writeoff($params['user_id']);
            $isAlreadyRequest=$writeOffClass->isAlreadyRequest();
            if($isAlreadyRequest!=false){
                throw new RestException('the user has already made a request');
            }
            $requisite=0;
            $cReqisite=empty($params['requisites_id'])?0:$params['requisites_id'];
            $requisites=self::getRequisites($params['user_id']);
            foreach ($requisites as $nextRequisite) {
                if($cReqisite==0 || $cReqisite==$nextRequisite['id']){
                    $requisite=$nextRequisite['id'];
                    break;
                }
            }
            if($requisite==0){
                throw new RestException('This user does not have such a card');
            }else{
                $options=$writeOffClass->getOptions();
                $account=\CSaleUserAccount::GetByUserID($params['user_id'], $options['currency']);
                $currentBudget=$account['CURRENT_BUDGET'];
                $params['bonus']=min($params['bonus'], $currentBudget);
                $dateTime = new \Bitrix\Main\Type\DateTime;
                global $USER;
                $comment=[[
                    'status'=>'request',
                    'comment'=>'by rest',
                    'date'=> $dateTime->toString(),
                    'manader_id'=>$USER->GetID()
                ]];
                $id=$writeOffClass->writeBonus($params['bonus'], $requisite, $comment);
                if($id!=false){
                    return ['id'=>$id, 'status'=>'success'];
                }
            }
            throw new RestException('Unknown error');
        }
    }

    public static function writeOffUpdate($params, $n, $server){
        $errors=[];
        if(empty($params['id'])){
            $filterData['id']=$params['id'];
            $errors[]='write off id is not specified';
        }
        if(empty($params['status']) || !in_array($params['status'], ['reject', 'execute'])){
            $errors[]='status type is not specified';
        }
        if(count($errors)>0){
            throw new RestException(implode(', ',$errors));
        }
        $result = Entity\WriteOffTable::getList(['filter' => ['id' => $params['id']]]);
        if ($arRes = $result->fetch()) {
            if($arRes['status']!='request'){
                throw new RestException('the request is already closed');
            }
            $updData=['status'=>$params['status']];
            $updData['id']=$params['id'];
            $updData['comment']='withdraw by rest';
            if(!empty($params['comment'])){
                $updData['comment']='rest: '.$params['comment'];
            }
            $writeoff=new \Commerce\Loyaltyprogram\Writeoff;
            $writeoff->getOrder($updData['id']);
            $writeoff->setOrder($updData);
            return ['id'=>$params['id'], 'status'=>'success'];
            /*$updRes=Entity\WriteOffTable::update($params['id'], $updData);
            if (!$updRes->isSuccess()){
                throw new RestException(implode(', ',$updRes->getErrorMessages()));
            }else{
                return ['id'=>$params['id'], 'status'=>'success'];
            }*/
        }
        throw new RestException('withdrawal request not found');
    }

    public static function requisiteByUser($params, $n, $server){
        if(empty($params['user_id'])){
            throw new RestException('user id not specified');
        }
        $data=Entity\UserRequisitesTable::getList([
            'filter'=>['user_id'=>$params['user_id']]
        ]);
        $result=[];
        while ($arData = $data->fetch()) {
            $result[]=$arData;
        }
        if(count($result)==0){
            throw new RestException('requisites not found');
        }
        return $result;
    }

}