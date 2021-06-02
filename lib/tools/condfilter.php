<? 
namespace Commerce\Loyaltyprogram\Tools;
\Bitrix\Main\Loader::includeModule('catalog');
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class CondFilter extends \CCatalogCondCtrlIBlockProps
{
    public static function GetControlShow($arParams)
	{
		$arControls = static::GetControlsFilter(false,$arParams['filter']);
		$arResult = array();
		$intCount = -1;
		foreach ($arControls as &$arOneControl)
		{
			if (isset($arOneControl['SEP']) && 'Y' == $arOneControl['SEP'])
			{
				$intCount++;
				$arResult[$intCount] = array(
					'controlgroup' => true,
					'group' =>  false,
					'label' => $arOneControl['SEP_LABEL'],
					'showIn' => static::GetShowIn($arParams['SHOW_IN_GROUPS']),
					'children' => array()
				);
			}
			$arLogic = static::GetLogicAtom($arOneControl['LOGIC']);
			$arValue = static::GetValueAtom($arOneControl['JS_VALUE']);

			$arResult[$intCount]['children'][] = array(
				'controlId' => $arOneControl['ID'],
				'group' => false,
				'label' => $arOneControl['LABEL'],
				'showIn' => static::GetShowIn($arParams['SHOW_IN_GROUPS']),
				'control' => array(
					array(
						'id' => 'prefix',
						'type' => 'prefix',
						'text' => $arOneControl['PREFIX']
					),
					$arLogic,
					$arValue
				)
			);
		}
		if (isset($arOneControl))
			unset($arOneControl);
        
        
		return $arResult;
	}

    public static function GetControlsFilter($strControlID = false, $filterProperties = '')
	{
		
		$arControlList = array();
		$arIBlockList = array();
		$iterator = \Bitrix\Catalog\CatalogIblockTable::getList(array(
			'select' => array('IBLOCK_ID', 'PRODUCT_IBLOCK_ID')
		));
		while ($arIBlock = $iterator->fetch())
		{
			$arIBlock['IBLOCK_ID'] = (int)$arIBlock['IBLOCK_ID'];
			$arIBlock['PRODUCT_IBLOCK_ID'] = (int)$arIBlock['PRODUCT_IBLOCK_ID'];
			if ($arIBlock['IBLOCK_ID'] > 0)
				$arIBlockList[$arIBlock['IBLOCK_ID']] = true;
			if ($arIBlock['PRODUCT_IBLOCK_ID'] > 0)
				$arIBlockList[$arIBlock['PRODUCT_IBLOCK_ID']] = true;
		}
		unset($arIBlock, $iterator);
		if (!empty($arIBlockList))
		{
			$arIBlockList = array_keys($arIBlockList);
			sort($arIBlockList);
			foreach ($arIBlockList as $intIBlockID)
			{
				$strName = \CIBlock::GetArrayByID($intIBlockID, 'NAME');
				if (false !== $strName)
				{
					$boolSep = true;
					$filter['IBLOCK_ID'] = $intIBlockID;
					if(!empty($filterProperties))
						$filter['ID'] = $filterProperties;

					$rsProps = \Bitrix\Iblock\PropertyTable::GetList(['select' => ['*'], 'filter' => $filter]);
					while ($arProp = $rsProps->Fetch())
					{
						if ('CML2_LINK' == $arProp['XML_ID'] || 'F' == $arProp['PROPERTY_TYPE'])
							continue;
						if ('L' == $arProp['PROPERTY_TYPE'])
							$arProp['VALUES'] = array();

						$strFieldType = '';
						$arLogic = array();
						$arValue = array();
						$arPhpValue = '';

						$boolUserType = false;
						if (isset($arProp['USER_TYPE']) && !empty($arProp['USER_TYPE']))
						{
							switch ($arProp['USER_TYPE'])
							{
								case 'DateTime':
									$strFieldType = 'datetime';
									$arLogic = static::GetLogic(array(BT_COND_LOGIC_EQ, BT_COND_LOGIC_NOT_EQ, BT_COND_LOGIC_GR, BT_COND_LOGIC_LS, BT_COND_LOGIC_EGR, BT_COND_LOGIC_ELS));
									$arValue = array(
										'type' => 'datetime',
										'format' => 'datetime'
									);
									$boolUserType = true;
									break;
								case 'Date':
									$strFieldType = 'date';
									$arLogic = static::GetLogic(array(BT_COND_LOGIC_EQ, BT_COND_LOGIC_NOT_EQ, BT_COND_LOGIC_GR, BT_COND_LOGIC_LS, BT_COND_LOGIC_EGR, BT_COND_LOGIC_ELS));
									$arValue = array(
										'type' => 'datetime',
										'format' => 'date'
									);
									$boolUserType = true;
									break;
								case 'directory':
									$strFieldType = 'text';
									$arLogic = static::GetLogic(array(BT_COND_LOGIC_EQ, BT_COND_LOGIC_NOT_EQ));
									$arValue = array(
										'type' => 'lazySelect',
										'load_url' => '/bitrix/tools/catalog/get_property_values.php',
										'load_params' => array(
											'lang' => LANGUAGE_ID,
											'propertyId' => $arProp['ID']
										)
									);
									$boolUserType = true;
									break;
								default:
									$boolUserType = false;
									break;
							}
						}

						if (!$boolUserType)
						{
							switch ($arProp['PROPERTY_TYPE'])
							{
								case 'N':
									$strFieldType = 'double';
									$arLogic = static::GetLogic(array(BT_COND_LOGIC_EQ, BT_COND_LOGIC_NOT_EQ, BT_COND_LOGIC_GR, BT_COND_LOGIC_LS, BT_COND_LOGIC_EGR, BT_COND_LOGIC_ELS));
									$arValue = array('type' => 'input');
									break;
								case 'S':
									$strFieldType = 'text';
									$arLogic = static::GetLogic(array(BT_COND_LOGIC_EQ, BT_COND_LOGIC_NOT_EQ, BT_COND_LOGIC_CONT, BT_COND_LOGIC_NOT_CONT));
									$arValue = array('type' => 'input');
									break;
								case 'L':
									$strFieldType = 'int';
									$arLogic = static::GetLogic(array(BT_COND_LOGIC_EQ, BT_COND_LOGIC_NOT_EQ));
									$arValue = array(
										'type' => 'lazySelect',
										'load_url' => '/bitrix/tools/catalog/get_property_values.php',
										'load_params' => array(
											'lang' => LANGUAGE_ID,
											'propertyId' => $arProp['ID']
										)
									);
									$arPhpValue = array('VALIDATE' => 'enumValue');
									break;
								case 'E':
									$strFieldType = 'int';
									$arLogic = static::GetLogic(array(BT_COND_LOGIC_EQ, BT_COND_LOGIC_NOT_EQ));
									$arValue = array(
										'type' => 'popup',
										// 'popup_url' => self::getAdminSection().'iblock_element_search.php',
										'popup_params' => array(
											'lang' => LANGUAGE_ID,
											'IBLOCK_ID' => $arProp['LINK_IBLOCK_ID'],
											'discount' => 'Y'
										),
										'param_id' => 'n'
									);
									$arPhpValue = array('VALIDATE' => 'element');
									break;
								case 'G':
									$popupParams = array(
										'lang' => LANGUAGE_ID,
										'IBLOCK_ID' => $arProp['LINK_IBLOCK_ID'],
										'discount' => 'Y',
										'simplename' => 'Y',
									);
									if ($arProp['LINK_IBLOCK_ID'] > 0)
										$popupParams['iblockfix'] = 'y';
									$strFieldType = 'int';
									$arLogic = static::GetLogic(array(BT_COND_LOGIC_EQ, BT_COND_LOGIC_NOT_EQ));
									$arValue = array(
										'type' => 'popup',
										// 'popup_url' => self::getAdminSection().'iblock_section_search.php',
										'popup_params' => $popupParams,
										'param_id' => 'n'
									);
									unset($popupParams);
									$arPhpValue = array('VALIDATE' => 'section');
									break;
							}
						}
						$arControlList['CondIBProp:'.$intIBlockID.':'.$arProp['ID']] = array(
							'ID' => 'CondIBProp:'.$intIBlockID.':'.$arProp['ID'],
							'PARENT' => false,
							'EXIST_HANDLER' => 'Y',
							'MODULE_ID' => 'catalog',
							'MODULE_ENTITY' => 'iblock',
							'ENTITY' => 'ELEMENT_PROPERTY',
							'ENTITY_ID' => $intIBlockID,
							'IBLOCK_ID' => $intIBlockID, // deprecated
							'PROPERTY_ID' => $arProp['ID'],
							'FIELD' => 'PROPERTY_'.$arProp['ID'].'_VALUE',
							'FIELD_TABLE' => $intIBlockID.':'.$arProp['ID'],
							'FIELD_TYPE' => $strFieldType,
							'MULTIPLE' => 'Y',
							'GROUP' => 'N',
							'SEP' => ($boolSep ? 'Y' : 'N'),
							'SEP_LABEL' => ($boolSep
								? str_replace(
									array('#ID#', '#NAME#'),
									array($intIBlockID, $strName),
									Loc::getMessage('BT_MOD_CATALOG_COND_CMP_IBLOCK_PROP_LABEL')
								)
								: ''
							),
							'LABEL' => $arProp['NAME'],
							'PREFIX' => str_replace(
								array('#NAME#', '#IBLOCK_ID#', '#IBLOCK_NAME#'),
								array($arProp['NAME'], $intIBlockID, $strName),
								Loc::getMessage('BT_MOD_CATALOG_COND_CMP_IBLOCK_ONE_PROP_PREFIX')
							),
							'LOGIC' => $arLogic,
							'JS_VALUE' => $arValue,
							'PHP_VALUE' => $arPhpValue
						);

						$boolSep = false;
					}
				}
			}
			unset($intIBlockID);
		}
		unset($arIBlockList);

		return static::searchControl($arControlList, $strControlID);
	}
}