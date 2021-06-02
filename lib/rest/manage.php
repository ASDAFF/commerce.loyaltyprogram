<?php

namespace Commerce\Loyaltyprogram\Rest;

\Bitrix\Main\Loader::includeModule('rest');

class Manage
{
    const REST_APP_NAME = 'Commerce LoyaltyProgram';
    const REST_APP_CODE_OLD = 'sw24loyalty';
    const REST_APP_CODE = 'sw24loyaltyprogram';

    public static function findId() {
        $appId = false;
        $dbRes = \Bitrix\Rest\APAuth\PasswordTable::getList([
            'order' => ['ID' => 'DESC'],
            'filter' => ['TITLE' => self::REST_APP_NAME],
            'select' => ['ID'],
            "limit" => 1,
        ]);
        if ($arPass = $dbRes->fetch()) {
            $appId = $arPass['ID'];
        }
        return $appId;
    }

    public static function findApp() {
        $appId = false;
        $dbRes = \Bitrix\Rest\APAuth\PasswordTable::getList([
            'order' => ['ID' => 'DESC'],
            'filter' => ['TITLE' => self::REST_APP_NAME],
            'select' => ['*'],
            "limit" => 1,
        ]);
        if ($arPass = $dbRes->fetch()) {
            return $arPass;
        }
        return false;
    }

    public static function addApp() {
        global $USER;
        $password = \Bitrix\Rest\APAuth\PasswordTable::generatePassword();
        $result = \Bitrix\Rest\APAuth\PasswordTable::add(
            array(
                'USER_ID' => $USER->getId(),
                'PASSWORD' => $password,
                'DATE_CREATE' => new \Bitrix\Main\Type\DateTime(),
                'TITLE' => self::REST_APP_NAME,
                'COMMENT' => '',
            )
        );
        if ($result->isSuccess()) {
            $appId = $result->getId();
            $arScope = [self::REST_APP_CODE_OLD, self::REST_APP_CODE];
            foreach ($arScope as $scope) {
                \Bitrix\Rest\APAuth\PermissionTable::add(array(
                    'PASSWORD_ID' => $appId,
                    'PERM' => $scope,
                ));
            }
        }
    }

    public static function deleteApp() {
        $appId = self::findId();
        if ($appId) {
            $result = \Bitrix\Rest\APAuth\PasswordTable::delete($appId);
            if ($result->isSuccess()) {
                \Bitrix\Rest\APAuth\PermissionTable::deleteByPasswordId($appId);
            }
        }
    }

    public static function OnRestServiceBuildDescription() {
        return [
            'sw24loyalty' => [//deprecated
                'sw24loyalty.bonus.add' => ['callback' => ['Commerce\Loyaltyprogram\Rest\Bonus', 'bonusAdd']],
                'sw24loyalty.bonus.remove' => ['callback' => ['Commerce\Loyaltyprogram\Rest\Bonus', 'bonusRemove']]
            ],
            'sw24loyaltyprogram' => [
                'sw24loyaltyprogram.bonus.add' => ['callback' => ['Commerce\Loyaltyprogram\Rest\Bonus', 'bonusAdd']],
                'sw24loyaltyprogram.bonus.remove' => ['callback' => ['Commerce\Loyaltyprogram\Rest\Bonus', 'bonusRemove']],
                'sw24loyaltyprogram.bonus.list' => ['callback' => ['Commerce\Loyaltyprogram\Rest\Bonus', 'bonusList']],
                'sw24loyaltyprogram.bonus.delete' => ['callback' => ['Commerce\Loyaltyprogram\Rest\Bonus', 'bonusDelete']],
                'sw24loyaltyprogram.bonus.update' => ['callback' => ['Commerce\Loyaltyprogram\Rest\Bonus', 'bonusUpdate']],

                'sw24loyaltyprogram.ranks.list' => ['callback' => ['Commerce\Loyaltyprogram\Rest\Ranks', 'rankList']],
                'sw24loyaltyprogram.ranks.user.list' => ['callback' => ['Commerce\Loyaltyprogram\Rest\Ranks', 'rankUserList']],
                'sw24loyaltyprogram.ranks.user.add' => ['callback' => ['Commerce\Loyaltyprogram\Rest\Ranks', 'rankAdd']],
                'sw24loyaltyprogram.ranks.user.update' => ['callback' => ['Commerce\Loyaltyprogram\Rest\Ranks', 'rankUpdate']],
                'sw24loyaltyprogram.ranks.user.delete' => ['callback' => ['Commerce\Loyaltyprogram\Rest\Ranks', 'rankDelete']],

                'sw24loyaltyprogram.referral.list' => ['callback' => ['Commerce\Loyaltyprogram\Rest\Referrals', 'refList']],
                'sw24loyaltyprogram.referral.delete' => ['callback' => ['Commerce\Loyaltyprogram\Rest\Referrals', 'refDelete']],
                'sw24loyaltyprogram.referral.add' => ['callback' => ['Commerce\Loyaltyprogram\Rest\Referrals', 'refAdd']],

                'sw24loyaltyprogram.writeoff.list' => ['callback' => ['Commerce\Loyaltyprogram\Rest\Writeoff', 'writeOffList']],
                'sw24loyaltyprogram.writeoff.add' => ['callback' => ['Commerce\Loyaltyprogram\Rest\Writeoff', 'writeOffAdd']],
                'sw24loyaltyprogram.writeoff.update' => ['callback' => ['Commerce\Loyaltyprogram\Rest\Writeoff', 'writeOffUpdate']],
                'sw24loyaltyprogram.writeoff.requisites.list' => ['callback' => ['Commerce\Loyaltyprogram\Rest\Writeoff', 'requisiteByUser']],

                'sw24loyaltyprogram.profiles.list' => ['callback' => ['Commerce\Loyaltyprogram\Rest\Profiles', 'profilesList']]
            ]
        ];
    }

}