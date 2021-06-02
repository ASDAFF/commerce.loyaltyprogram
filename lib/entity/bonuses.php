<?php

namespace Commerce\Loyaltyprogram\Entity;

use Bitrix\Main\Localization\Loc,
    Bitrix\Main\ORM\Data\DataManager,
    Bitrix\Main\ORM\Fields\BooleanField,
    Bitrix\Main\ORM\Fields\DatetimeField,
    Bitrix\Main\ORM\Fields\FloatField,
    Bitrix\Main\ORM\Fields\IntegerField,
    Bitrix\Main\ORM\Fields\StringField,
    Bitrix\Main\ORM\Fields\TextField,
    Bitrix\Main\ORM\Fields\Validators\LengthValidator;

Loc::loadMessages(__FILE__);

/**
 * Class BonusesTable
 *
 * Fields:
 * <ul>
 * <li> id int mandatory
 * <li> bonus_start double mandatory
 * <li> bonus double mandatory
 * <li> user_id int mandatory
 * <li> user_bonus int optional default 0
 * <li> order_id int mandatory
 * <li> currency string(3) mandatory
 * <li> profile_type string(20) mandatory
 * <li> profile_id int mandatory
 * <li> action_id int optional
 * <li> status string(20) mandatory
 * <li> date_add datetime optional
 * <li> date_remove datetime optional
 * <li> add_comment text optional
 * <li> comments text optional
 * <li> email text optional
 * <li> sms text optional
 * <li> notify bool ('N', 'Y') optional default 'N'
 * </ul>
 *
 * @package Bitrix\Loyal
 **/
class BonusesTable extends DataManager
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName() {
        return 'commerce_loyal_bonuses';
    }

    /**
     * Returns entity map definition.
     *
     * @return array
     */
    public static function getMap() {
        return [
            new IntegerField(
                'id',
                [
                    'primary' => true,
                    'autocomplete' => true,
                    'title' => Loc::getMessage('BONUSES_ENTITY_ID_FIELD')
                ]
            ),
            new FloatField(
                'bonus_start',
                [
                    'required' => true,
                    'title' => Loc::getMessage('BONUSES_ENTITY_BONUS_START_FIELD')
                ]
            ),
            new FloatField(
                'bonus',
                [
                    'required' => true,
                    'title' => Loc::getMessage('BONUSES_ENTITY_BONUS_FIELD')
                ]
            ),
            new IntegerField(
                'user_id',
                [
                    'required' => true,
                    'title' => Loc::getMessage('BONUSES_ENTITY_USER_ID_FIELD')
                ]
            ),
            new IntegerField(
                'user_bonus',
                [
                    'default' => 0,
                    'title' => Loc::getMessage('BONUSES_ENTITY_USER_BONUS_FIELD')
                ]
            ),
            new IntegerField(
                'order_id',
                [
                    'default' => 0,
                    //'required' => true,
                    'title' => Loc::getMessage('BONUSES_ENTITY_ORDER_ID_FIELD')
                ]
            ),
            new StringField(
                'currency',
                [
                    'required' => true,
                    'validation' => [__CLASS__, 'validateCurrency'],
                    'title' => Loc::getMessage('BONUSES_ENTITY_CURRENCY_FIELD')
                ]
            ),
            new StringField(
                'profile_type',
                [
                    'required' => true,
                    'validation' => [__CLASS__, 'validateProfileType'],
                    'title' => Loc::getMessage('BONUSES_ENTITY_PROFILE_TYPE_FIELD')
                ]
            ),
            new IntegerField(
                'profile_id',
                [
                    //'required' => true,
                    'default' => 0,
                    'title' => Loc::getMessage('BONUSES_ENTITY_PROFILE_ID_FIELD')
                ]
            ),
            new IntegerField(
                'action_id',
                [
                    'title' => Loc::getMessage('BONUSES_ENTITY_ACTION_ID_FIELD')
                ]
            ),
            new StringField(
                'status',
                [
                    'required' => true,
                    'validation' => [__CLASS__, 'validateStatus'],
                    'title' => Loc::getMessage('BONUSES_ENTITY_STATUS_FIELD')
                ]
            ),
            new DatetimeField(
                'date_add',
                [
                    'title' => Loc::getMessage('BONUSES_ENTITY_DATE_ADD_FIELD')
                ]
            ),
            new DatetimeField(
                'date_remove',
                [
                    'title' => Loc::getMessage('BONUSES_ENTITY_DATE_REMOVE_FIELD')
                ]
            ),
            new TextField(
                'add_comment',
                [
                    'title' => Loc::getMessage('BONUSES_ENTITY_ADD_COMMENT_FIELD')
                ]
            ),
            new TextField(
                'comments',
                [
                    'title' => Loc::getMessage('BONUSES_ENTITY_COMMENTS_FIELD')
                ]
            ),
            new TextField(
                'email',
                [
                    'title' => Loc::getMessage('BONUSES_ENTITY_EMAIL_FIELD'),
                    'save_data_modification' => function () {
                        return [
                            function ($value) {
                                return serialize($value);
                            }
                        ];
                    },
                    'fetch_data_modification' => function () {
                        return [
                            function ($value) {
                                return unserialize($value);
                            }
                        ];
                    }
                ]
            ),
            new TextField(
                'sms',
                [
                    'title' => Loc::getMessage('BONUSES_ENTITY_SMS_FIELD'),
                    'save_data_modification' => function () {
                        return [
                            function ($value) {
                                return serialize($value);
                            }
                        ];
                    },
                    'fetch_data_modification' => function () {
                        return [
                            function ($value) {
                                return unserialize($value);
                            }
                        ];
                    }
                ]
            ),
            new BooleanField(
                'notify',
                [
                    'values' => ['N', 'Y'],
                    'default' => 'N',
                    'title' => Loc::getMessage('BONUSES_ENTITY_NOTIFY_FIELD')
                ]
            ),
        ];
    }

    /**
     * Returns validators for currency field.
     *
     * @return array
     */
    public static function validateCurrency() {
        return [
            new LengthValidator(null, 3),
        ];
    }

    /**
     * Returns validators for profile_type field.
     *
     * @return array
     */
    public static function validateProfileType() {
        return [
            new LengthValidator(null, 20),
        ];
    }

    /**
     * Returns validators for status field.
     *
     * @return array
     */
    public static function validateStatus() {
        return [
            new LengthValidator(null, 20),
        ];
    }
}