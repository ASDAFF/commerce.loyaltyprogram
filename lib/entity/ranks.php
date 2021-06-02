<?php
namespace Commerce\Loyaltyprogram\Entity;

use Bitrix\Main\Localization\Loc,
	Bitrix\Main\ORM\Data\DataManager,
	Bitrix\Main\ORM\Fields\DatetimeField,
	Bitrix\Main\ORM\Fields\IntegerField,
	Bitrix\Main\ORM\Fields\StringField,
	Bitrix\Main\ORM\Fields\TextField,
	Bitrix\Main\ORM\Fields\FloatField,
	Bitrix\Main\ORM\Fields\Validators\LengthValidator,
	Bitrix\Main\Type\DateTime;

Loc::loadMessages(__FILE__);

/**
 * Class RanksTable
 * 
 * Fields:
 * <ul>
 * <li> id int mandatory
 * <li> sort int optional default 0
 * <li> active string(2) optional
 * <li> coeff int optional default 0
 * <li> name string(100) mandatory
 * <li> type string(30) mandatory
 * <li> settings text optional
 * <li> profiles text optional
 * <li> date_setting datetime optional default current datetime
 * </ul>
 *
 * @package Bitrix\Loyal
 **/

class RanksTable extends DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'commerce_loyal_ranks';
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
					'title' => Loc::getMessage('RANKS_ENTITY_ID_FIELD')
				]
			),
			new StringField(
				'name',
				[
					'required' => true,
					'validation' => [__CLASS__, 'validateName'],
					'title' => Loc::getMessage('RANKS_ENTITY_NAME_FIELD')
				]
			),
			new IntegerField(
				'sort',
				[
					'default' => 100,
					'title' => Loc::getMessage('RANKS_ENTITY_SORT_FIELD')
				]
			),
			new StringField(
				'active',
				[
					'default' => 'N',
					'validation' => [__CLASS__, 'validateActive'],
					'title' => Loc::getMessage('RANKS_ENTITY_ACTIVE_FIELD')
				]
			),
			new FloatField(
				'coeff',
				[
					'default' => 1.00,
					'title' => Loc::getMessage('RANKS_ENTITY_COEFF_FIELD')
				]
			),
			new StringField(
				'type',
				[
					'required' => true,
					'validation' => [__CLASS__, 'validateType'],
					'title' => Loc::getMessage('RANKS_ENTITY_TYPE_FIELD')
				]
			),
			new FloatField(
				'value',
				[
					'default' => 0.0000,
					'title' => Loc::getMessage('RANKS_ENTITY_VALUE_FIELD')
				]
			),
			new TextField(
				'settings',
				[
					'title' => Loc::getMessage('RANKS_ENTITY_SETTINGS_FIELD'),
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
			),
			new TextField(
				'profiles',
				[
					'title' => Loc::getMessage('RANKS_ENTITY_PROFILES_FIELD'),
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
			),
			new DatetimeField(
				'date_setting',
				[
					'default' => function()
					{
						return new DateTime();
					},
					'title' => Loc::getMessage('RANKS_ENTITY_DATE_SETTING_FIELD')
				]
			),
		];
	}

	/**
	 * Returns validators for name field.
	 *
	 * @return array
	 */
	public static function validateName()
	{
		return [
			new LengthValidator(null, 100),
		];
	}

	/**
	 * Returns validators for active field.
	 *
	 * @return array
	 */
	public static function validateActive()
	{
		return [
			new LengthValidator(null, 2),
		];
	}

	/**
	 * Returns validators for type field.
	 *
	 * @return array
	 */
	public static function validateType()
	{
		return [
			new LengthValidator(null, 30),
		];
	}
}