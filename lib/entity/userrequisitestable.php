<?php
namespace Commerce\Loyaltyprogram\Entity;

use Bitrix\Main\Localization\Loc,
    Bitrix\Main\ORM\Data\DataManager,
    Bitrix\Main\ORM\Fields\BooleanField,
    Bitrix\Main\ORM\Fields\DatetimeField,
    Bitrix\Main\ORM\Fields\IntegerField,
    Bitrix\Main\ORM\Fields\StringField,
    Bitrix\Main\ORM\Fields\Validators\LengthValidator,
    Bitrix\Main\Type\DateTime;

Loc::loadMessages(__FILE__);

/**
 * Class UserRequisitesTable
 *
 * Fields:
 * <ul>
 * <li> id int mandatory
 * <li> user_id int mandatory
 * <li> cart_number string(20) optional
 * <li> bik string(12) mandatory
 * <li> invoice string(20) mandatory
 * <li> date_change datetime optional default current datetime
 * <li> active bool ('N', 'Y') optional default 'Y'
 * </ul>
 *
 * @package Bitrix\Loyal
 **/

class UserRequisitesTable extends DataManager
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'commerce_loyal_user_requisites';
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
                    'title' => Loc::getMessage('USER_REQUISITES_ENTITY_ID_FIELD')
                ]
            ),
            new IntegerField(
                'user_id',
                [
                    'required' => true,
                    'title' => Loc::getMessage('USER_REQUISITES_ENTITY_USER_ID_FIELD')
                ]
            ),
            new StringField(
                'cart_number',
                [
                    'validation' => [__CLASS__, 'validateCartNumber'],
                    'title' => Loc::getMessage('USER_REQUISITES_ENTITY_CART_NUMBER_FIELD')
                ]
            ),
            new StringField(
                'bik',
                [
                    'required' => true,
                    'validation' => [__CLASS__, 'validateBik'],
                    'title' => Loc::getMessage('USER_REQUISITES_ENTITY_BIK_FIELD')
                ]
            ),
            new StringField(
                'invoice',
                [
                    'required' => true,
                    'validation' => [__CLASS__, 'validateInvoice'],
                    'title' => Loc::getMessage('USER_REQUISITES_ENTITY_INVOICE_FIELD')
                ]
            ),
            new DatetimeField(
                'date_change',
                [
                    'default' => function()
                    {
                        return new DateTime();
                    },
                    'title' => Loc::getMessage('USER_REQUISITES_ENTITY_DATE_CHANGE_FIELD')
                ]
            ),
            new BooleanField(
                'active',
                [
                    'values' => array('N', 'Y'),
                    'default' => 'Y',
                    'title' => Loc::getMessage('USER_REQUISITES_ENTITY_ACTIVE_FIELD')
                ]
            ),
        ];
    }

    /**
     * Returns validators for cart_number field.
     *
     * @return array
     */
    public static function validateCartNumber()
    {
        return [
            new LengthValidator(null, 20),
        ];
    }

    /**
     * Returns validators for bik field.
     *
     * @return array
     */
    public static function validateBik()
    {
        return [
            new LengthValidator(null, 12),
        ];
    }

    /**
     * Returns validators for invoice field.
     *
     * @return array
     */
    public static function validateInvoice()
    {
        return [
            new LengthValidator(null, 20),
        ];
    }
}