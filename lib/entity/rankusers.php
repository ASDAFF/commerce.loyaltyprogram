<?php
namespace Commerce\Loyaltyprogram\Entity;

use Bitrix\Main\Localization\Loc,
	Bitrix\Main\ORM\Data\DataManager,
	Bitrix\Main\ORM\Fields\DatetimeField,
	Bitrix\Main\ORM\Fields\IntegerField,
	Bitrix\Main\ORM\Fields\StringField,
    Bitrix\Main\ORM\Fields\TextField,
	Bitrix\Main\ORM\Fields\Validators\LengthValidator,
	Bitrix\Main\ORM\Fields\Validators\UniqueValidator,
	Bitrix\Main\Type\DateTime,
    Bitrix\Main\ORM;

Loc::loadMessages(__FILE__);

/**
 * Class RankUsersTable
 * 
 * Fields:
 * <ul>
 * <li> id int mandatory
 * <li> user_id int mandatory
 * <li> rank_id int mandatory
 * <li> active string(2) optional default 'N'
 * <li> date_setting datetime optional default current datetime
 * </ul>
 *
 * @package Bitrix\Loyal
 **/

class RankUsersTable extends DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'commerce_loyal_rank_users';
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
					'title' => Loc::getMessage('RANK_USERS_ENTITY_ID_FIELD')
				]
			),
			new IntegerField(
				'user_id',
				[
					'required' => true,
					'title' => Loc::getMessage('RANK_USERS_ENTITY_USER_ID_FIELD'),
                    'validation' => function() {
                        return [
                            function($value) {
                                if(!empty($value)){
                                    $result = \Bitrix\Main\UserTable::getList([
                                        'filter'=>['ID'=>$value]
                                    ]);
                                    if(!$arData = $result->fetch()){
                                        return Loc::getMessage('RANK_USERS_ENTITY_USER_NOT_FOUND');
                                    }
                                }
                                return true;
                            },
                            new UniqueValidator(Loc::getMessage("RANK_USERS_ENTITY_RANK_ADDED_EARLIER"))
                        ];
                    }
				]
			),
			new IntegerField(
				'rank_id',
				[
					'required' => true,
					'title' => Loc::getMessage('RANK_USERS_ENTITY_RANK_ID_FIELD'),
                    'validation' => function() {
                        return [
                            function($value){
                                if(!empty($value)){
                                    $result = RanksTable::getList(['filter' => ['id' => $value]]);
                                    if(!$arRank = $result->fetch()) {
                                       return Loc::getMessage('RANK_USERS_ENTITY_RANK_NOT_FOUND');
                                    }
                                }
                                return true;
                            }
                        ];
                    }
				]
			),
			new StringField(
				'active',
				[
					'default' => 'N',
					'validation' => [__CLASS__, 'validateActive'],
					'title' => Loc::getMessage('RANK_USERS_ENTITY_ACTIVE_FIELD')
				]
			),
            new TextField(
                'params',
                [
                    'title' => Loc::getMessage('RANK_USERS_ENTITY_PARAMS_FIELD'),
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
					'title' => Loc::getMessage('RANK_USERS_ENTITY_DATE_SETTING_FIELD')
				]
			),
			new \Bitrix\Main\Entity\ReferenceField(
				'ranks',
				'Commerce\Loyaltyprogram\Entity\Ranks',
				['=this.rank_id' => 'ref.id'],
				['join_type' => 'LEFT']
			)
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

}