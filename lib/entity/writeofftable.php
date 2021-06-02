<?php
namespace Commerce\Loyaltyprogram\Entity;

use Bitrix\Main\Localization\Loc,
    Bitrix\Main\ORM\Data\DataManager,
    Bitrix\Main\ORM\Fields\DatetimeField,
    Bitrix\Main\ORM\Fields\FloatField,
    Bitrix\Main\ORM\Fields\IntegerField,
    Bitrix\Main\ORM\Fields\StringField,
    Bitrix\Main\ORM\Fields\TextField,
    Bitrix\Main\ORM\Fields\Validators\LengthValidator,
    Bitrix\Main\Type\DateTime;

Loc::loadMessages(__FILE__);

/**
 * Class WriteOffTable
 *
 * Fields:
 * <ul>
 * <li> id int mandatory
 * <li> bonus double mandatory
 * <li> transact_id int mandatory
 * <li> profile_id int mandatory
 * <li> user_id int mandatory
 * <li> requisites_id int mandatory
 * <li> date_order datetime optional default current datetime
 * <li> status string(20) mandatory
 * <li> date_change datetime optional default current datetime
 * <li> comment text optional
 * <li> log text optional
 * </ul>
 *
 * @package Bitrix\Loyal
 **/

class WriteOffTable extends DataManager
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'commerce_loyal_write_off';
    }

    /**
     * Returns entity map definition.
     *
     * @return array
     */
    public static function getMap()
    {
        return [
            new IntegerField(
                'id',
                [
                    'primary' => true,
                    'autocomplete' => true,
                    'title' => Loc::getMessage('WRITE_OFF_ENTITY_ID_FIELD')
                ]
            ),
            new FloatField(
                'bonus',
                [
                    'required' => true,
                    'title' => Loc::getMessage('WRITE_OFF_ENTITY_BONUS_FIELD')
                ]
            ),
            new IntegerField(
                'transact_id',
                [
                    'required' => true,
                    'title' => Loc::getMessage('WRITE_OFF_ENTITY_TRANSACT_ID_FIELD')
                ]
            ),
            new IntegerField(
                'profile_id',
                [
                    'required' => true,
                    'title' => Loc::getMessage('WRITE_OFF_ENTITY_PROFILE_ID_FIELD')
                ]
            ),
            new IntegerField(
                'user_id',
                [
                    'required' => true,
                    'title' => Loc::getMessage('WRITE_OFF_ENTITY_USER_ID_FIELD')
                ]
            ),
            new IntegerField(
                'requisites_id',
                [
                    'required' => true,
                    'title' => Loc::getMessage('WRITE_OFF_ENTITY_REQUISITES_ID_FIELD')
                ]
            ),
            new DatetimeField(
                'date_order',
                [
                    'default' => function()
                    {
                        return new DateTime();
                    },
                    'title' => Loc::getMessage('WRITE_OFF_ENTITY_DATE_ORDER_FIELD')
                ]
            ),
            new StringField(
                'status',
                [
                    'required' => true,
                    'validation' => [__CLASS__, 'validateStatus'],
                    'title' => Loc::getMessage('WRITE_OFF_ENTITY_STATUS_FIELD')
                ]
            ),
            new DatetimeField(
                'date_change',
                [
                    'default' => function()
                    {
                        return new DateTime();
                    },
                    'title' => Loc::getMessage('WRITE_OFF_ENTITY_DATE_CHANGE_FIELD')
                ]
            ),
            new TextField(
                'comment',
                [
                    'title' => Loc::getMessage('WRITE_OFF_ENTITY_COMMENT_FIELD')
                ]
            ),
            new TextField(
                'log',
                [
                    'title' => Loc::getMessage('WRITE_OFF_ENTITY_LOG_FIELD'),
                    'save_data_modification' => function () {
                        return array(
                            function ($value) {
                                return serialize($value);
                            }
                        );
                    },
                    'fetch_data_modification' => function () {
                        return array(
                            function ($value) {
                                return unserialize($value);
                            }
                        );
                    }
                ]
            )
        ];
    }

    /**
     * Returns validators for status field.
     *
     * @return array
     */
    public static function validateStatus()
    {
        return [
            new LengthValidator(null, 20),
        ];
    }

    public static function getStatuses(){
        return [
            'request'=>Loc::getMessage("commerce.loyaltyprogram_WO_STATUS_REQUEST"),
            'execute'=>Loc::getMessage("commerce.loyaltyprogram_WO_STATUS_EXECUTE"),
            'reject'=>Loc::getMessage("commerce.loyaltyprogram_WO_STATUS_REJECT")
        ];;
    }
}