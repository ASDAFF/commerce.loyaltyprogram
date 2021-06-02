<?php
namespace Commerce\Loyaltyprogram\Entity;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

/**
 * Class CouponsTable
 * 
 * Fields:
 * <ul>
 * <li> id int mandatory
 * <li> user_id int mandatory
 * <li> rule_id int mandatory
 * <li> coupon_id int mandatory
 * <li> coupon string(100) mandatory
 * </ul>
 *
 * @package Commerce\Loyaltyprogram\Entity
 **/

class CouponsTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'commerce_loyal_coupons';
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
				'title' => Loc::getMessage('COUPONS_ENTITY_ID_FIELD'),
			),
			'user_id' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('COUPONS_ENTITY_USER_ID_FIELD'),
			),
			'rule_id' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('COUPONS_ENTITY_RULE_ID_FIELD'),
			),
			'coupon_id' => array(
				'data_type' => 'integer',
				'required' => true,
				'title' => Loc::getMessage('COUPONS_ENTITY_COUPON_ID_FIELD'),
			),
			'coupon' => array(
				'data_type' => 'string',
				'required' => true,
				'validation' => array(__CLASS__, 'validateCoupon'),
				'title' => Loc::getMessage('COUPONS_ENTITY_COUPON_FIELD'),
			),
		);
	}
	/**
	 * Returns validators for coupon field.
	 *
	 * @return array
	 */
	public static function validateCoupon()
	{
		return array(
			new Main\Entity\Validator\Length(null, 100),
		);
	}
}