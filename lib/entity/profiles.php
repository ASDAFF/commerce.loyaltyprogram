<?php
namespace Commerce\Loyaltyprogram\Entity;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class ProfilesTable
 * 
 * Fields:
 * <ul>
 * <li> id int mandatory
 * <li> sort int mandatory
 * <li> active string(2) optional
 * <li> name string(100) mandatory
 * <li> type string(20) mandatory
 * <li> site string(40) optional
 * <li> date_setting datetime optional
 * <li> settings string optional
 * <li> email_settings string optional
 * <li> sms_settings string optional
 * </ul>
 *
 * @package Bitrix\Loyal
 **/

class ProfilesTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'commerce_loyal_profiles';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'id' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
				'title' => Loc::getMessage('PROFILES_ENTITY_ID_FIELD'),
			),
			'sort' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('PROFILES_ENTITY_SORT_FIELD'),
			),
			'active' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateActive'),
				'title' => Loc::getMessage('PROFILES_ENTITY_ACTIVE_FIELD'),
			),
			'name' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateName'),
				'title' => Loc::getMessage('PROFILES_ENTITY_NAME_FIELD'),
			),
			'type' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateType'),
				'title' => Loc::getMessage('PROFILES_ENTITY_TYPE_FIELD'),
			),
			'site' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateSite'),
				'title' => Loc::getMessage('PROFILES_ENTITY_SITE_FIELD'),
			),
			'date_setting' => array(
				'data_type' => 'datetime',
				'title' => Loc::getMessage('PROFILES_ENTITY_DATE_SETTING_FIELD'),
			),
			'settings' => array(
				'data_type' => 'text',
				'title' => Loc::getMessage('PROFILES_ENTITY_SETTINGS_FIELD'),
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
			),
			'email_settings' => array(
				'data_type' => 'text',
				'title' => Loc::getMessage('PROFILES_ENTITY_EMAIL_SETTINGS_FIELD'),
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
			),
			'sms_settings' => array(
				'data_type' => 'text',
				'title' => Loc::getMessage('PROFILES_ENTITY_SMS_SETTINGS_FIELD'),
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
			),
		);
	}
	/**
	 * Returns validators for active field.
	 *
	 * @return array
	 */
	public static function validateActive()
	{
		return array(
			new Main\Entity\Validator\Length(null, 2),
		);
	}
	/**
	 * Returns validators for name field.
	 *
	 * @return array
	 */
	public static function validateName()
	{
		return array(
			new Main\Entity\Validator\Length(null, 100),
		);
	}
	/**
	 * Returns validators for type field.
	 *
	 * @return array
	 */
	public static function validateType()
	{
		return array(
			new Main\Entity\Validator\Length(null, 20),
		);
	}
	/**
	 * Returns validators for site field.
	 *
	 * @return array
	 */
	public static function validateSite()
	{
		return array(
			new Main\Entity\Validator\Length(null, 40),
		);
	}
}