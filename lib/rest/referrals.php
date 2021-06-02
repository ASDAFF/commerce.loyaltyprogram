<?php

namespace Commerce\Loyaltyprogram\Rest;

use \Bitrix\Rest\RestException,
    \Bitrix\Main\Localization\Loc,
    \Commerce\Loyaltyprogram\Entity;

\Bitrix\Main\Loader::includeModule('rest');
\Bitrix\Main\Loader::includeModule('sale');

Loc::loadMessages(__DIR__ .'/lang.php');

class Referrals extends \IRestService
{

    public static function refList($params, $n, $server){
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
                if( $clearKey=='date_create'){
                    $dataFilter['filter'][$keyOrder] = \Bitrix\Main\Type\DateTime::createFromTimestamp($valOrder);
                }else {
                    $dataFilter['filter'][$keyOrder] = $valOrder;
                }
            }
        }
        $data = Entity\UsersTable::getList($dataFilter);
        $result=[];
        while ($arData = $data->fetch()) {
            $arData['date_create']=empty($arData['date_create'])?:$arData['date_create']->toString();
            $result[]=$arData;
        }
        $nav=[
            'count'=>$data->getCount(),
            'offset' => $offset
        ];

        return self::setNavData($result, $nav);

        throw new RestException('Unknown error');
    }

    public static function refAdd($params, $n, $server){
        $refUser=0;
        $user=0;
        if(!empty($params['user_id'])){
            $user=$params['user_id'];
        }
        if(!empty($user)) {
            $result = \Bitrix\Main\UserTable::getList([
                'filter'=>['ID'=>$user]
            ]);
            if(!$arData = $result->fetch()){
                throw new RestException('user not registered');
            }
        }
        if(empty($user)){
            throw new RestException('user not found');
        }
        if(!empty($params['ref_user'])) {
            $result = \Bitrix\Main\UserTable::getList([
                'filter'=>['ID'=>$params['ref_user']]
            ]);
            if(!$arData = $result->fetch()){
                throw new RestException('referalodatel not found');
            }else{
                $refUser=$params['ref_user'];
            }
        }
        $listReferrals=new \Commerce\Loyaltyprogram\Referrals;
        $res=$listReferrals->setReferral2($refUser, $user, 'manual', 'by rest');
        if($res===false){
            throw new RestException('you can\'t create a reflink');
        }else{
            return ['user'=>$user, 'status'=>'success'];
        }
    }

    public static function refDelete($params, $n, $server){
        $primary=0;
        if(!empty($params['user'])) {
            $result = Entity\UsersTable::getList(['filter' => ['user' => $params['user']]]);
            if ($arUser = $result->fetch()) {
                $primary=$arUser['id'];
            }
        }elseif(!empty($params['id'])){
            $primary=$params['id'];
        }

        if(empty($primary)){
            throw new RestException('user not found');
        }

        $listReferrals=new \Commerce\Loyaltyprogram\Referrals;
        $listReferrals->deleteFromTreeRef($primary);
        return ['id'=>$primary, 'status'=>'success'];
    }

}