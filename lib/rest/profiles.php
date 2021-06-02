<?php

namespace Commerce\Loyaltyprogram\Rest;

use \Bitrix\Rest\RestException,
    \Bitrix\Main\Localization\Loc,
    \Commerce\Loyaltyprogram\Entity;

\Bitrix\Main\Loader::includeModule('rest');
\Bitrix\Main\Loader::includeModule('sale');

class Profiles extends \IRestService
{

    public static function profilesList($params, $n, $server) {
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
                if ($clearKey == 'date_setting') {
                    $dataFilter['filter'][$keyOrder] = \Bitrix\Main\Type\DateTime::createFromTimestamp($valOrder);
                } else {
                    $dataFilter['filter'][$keyOrder] = $valOrder;
                }
            }
        }
        $data = Entity\ProfilesTable::getList($dataFilter);
        $result = [];
        while ($arData = $data->fetch()) {
            $arData['date_setting'] = empty($arData['date_setting']) ?: $arData['date_setting']->toString();
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