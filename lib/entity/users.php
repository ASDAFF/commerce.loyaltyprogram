<?php
namespace Commerce\Loyaltyprogram\Entity;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class UsersTable
 * 
 * Fields:
 * <ul>
 * <li> id int mandatory
 * <li> user int mandatory
 * <li> ref_user int mandatory
 * <li> type string(20) mandatory default 'link'
 * <li> level int optional default 1
 * <li> date_create datetime optional
 * <li> source_link int optional
 * <li> comment string(120) optional
 * </ul>
 *
 * @package Bitrix\Loyal
 **/

class UsersTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'commerce_loyal_users';
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
				'title' => Loc::getMessage('USERS_ENTITY_ID_FIELD'),
			),
			'user' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('USERS_ENTITY_USER_FIELD'),
			),
			'ref_user' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('USERS_ENTITY_REF_USER_FIELD'),
			),
			'type' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateType'),
				'title' => Loc::getMessage('USERS_ENTITY_TYPE_FIELD'),
			),
			'level' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('USERS_ENTITY_LEVEL_FIELD'),
			),
			'date_create' => array(
				'data_type' => 'datetime',
				'title' => Loc::getMessage('USERS_ENTITY_DATE_CREATE_FIELD'),
			),
			'source_link' => array(
				'data_type' => 'integer',
				'title' => Loc::getMessage('USERS_ENTITY_SOURCE_LINK_FIELD'),
			),
			'comment' => array(
				'data_type' => 'string',
				'validation' => array(__CLASS__, 'validateComment'),
				'title' => Loc::getMessage('USERS_ENTITY_COMMENT_FIELD'),
			),
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
	 * Returns validators for comment field.
	 *
	 * @return array
	 */
	public static function validateComment()
	{
		return array(
			new Main\Entity\Validator\Length(null, 120),
		);
	}
}