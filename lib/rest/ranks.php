<?php

namespace Commerce\Loyaltyprogram\Rest;

use \Bitrix\Rest\RestException,
    \Bitrix\Main\Localization\Loc,
    \Commerce\Loyaltyprogram\Entity;

\Bitrix\Main\Loader::includeModule('rest');
\Bitrix\Main\Loader::includeModule('sale');

Loc::loadMessages(__DIR__ .'/lang.php');

class Ranks extends \IRestService
{

    public static function rankList($params, $n, $server){
        $data = Entity\RanksTable::getList([]);
        $result=[];
        while ($arData = $data->fetch()) {
            $arData['date_setting']=$arData['date_setting']->toString();
            unset($arData['type']);
            $result[]=$arData;
        }
        return $result;

        //throw new RestException('Unknown error');
    }

    public static function rankAdd($params, $n, $server){
        $addData=['active'=>(!empty($params['active']) && $params['active']=='N')?'N':'Y'];
        if(!empty($params['rank_id'])){
            $addData['rank_id']=$params['rank_id'];
        }
        if(!empty($params['user_id'])){
            $addData['user_id']=$params['user_id'];
        }
        $result=Entity\RankUsersTable::add($addData);
        if (!$result->isSuccess()){
            throw new RestException(implode(', ',$result->getErrorMessages()));
        }else{
            return ['id'=>$result->getId(), 'status'=>'success'];
        }
    }

    public static function rankUpdate($params, $n, $server){
        $addData=[];
        if(!empty($params['rank_id'])){
            $addData['rank_id']=$params['rank_id'];
        }
        if(!empty($params['active'])){
            $addData['active']=(!empty($params['active']) && $params['active']=='N')?'N':'Y';
        }
        $primary=0;
        if(!empty($params['user_id'])) {
            $addData['user_id']=$params['user_id'];
            $result = Entity\RankUsersTable::getList(['filter' => ['user_id' => $params['user_id']]]);
            if ($arUser = $result->fetch()) {
                $primary=$arUser['id'];
            }
        }
        if($primary==0){
            throw new RestException('user not found');
        }

        $result=Entity\RankUsersTable::update($primary, $addData);
        if (!$result->isSuccess()){
            throw new RestException(implode(', ',$result->getErrorMessages()));
        }else{
            return ['id'=>$result->getId(), 'status'=>'success'];
        }
    }

    public static function rankDelete($params, $n, $server){
        if(!empty($params['user_id'])) {
            $result = Entity\RankUsersTable::getList(['filter' => ['user_id' => $params['user_id']]]);
            if ($arUser = $result->fetch()) {
                $primary=$arUser['id'];
            }
        }elseif(!empty($params['id'])){
            $primary=$params['id'];
        }

        if(empty($primary)){
            throw new RestException('user not found');
        }

        $result=Entity\RankUsersTable::delete($primary);
        if (!$result->isSuccess()){
            throw new RestException(implode(', ',$result->getErrorMessages()));
        }else{
            return ['id'=>$primary, 'status'=>'success'];
        }
    }

    public static function rankUserList($params, $n, $server){
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
                if( $clearKey=='date_setting'){
                    $dataFilter['filter'][$keyOrder] = \Bitrix\Main\Type\DateTime::createFromTimestamp($valOrder);
                }else {
                    $dataFilter['filter'][$keyOrder] = $valOrder;
                }
            }
        }
        $data = Entity\RankUsersTable::getList($dataFilter);
        $result=[];
        while ($arData = $data->fetch()) {
            $arData['date_setting']=empty($arData['date_setting'])?:$arData['date_setting']->toString();
            $result[]=$arData;
        }
        $nav=[
            'count'=>$data->getCount(),
            'offset' => $offset
        ];

        return self::setNavData($result, $nav);

        throw new RestException('Unknown error');
    }

}