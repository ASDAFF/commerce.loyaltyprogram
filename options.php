<?

use Bitrix\Main\Localization\Loc,
    Bitrix\Main\Config\Option,
    Bitrix\Main\Page\Asset,
    Bitrix\Main\EventManager;

Loc::loadMessages(__FILE__);
Loc::loadMessages($_SERVER["DOCUMENT_ROOT"] . BX_ROOT . '/modules/main/options.php');

$module_id = 'commerce.loyaltyprogram';
\Bitrix\Main\Loader::includeModule($module_id);
\Bitrix\Main\Loader::includeModule('iblock');
\Bitrix\Main\Loader::includeModule('sale');
Asset::getInstance()->addJs('/bitrix/js/' . $module_id . '/script.js');
Asset::getInstance()->addJs('/bitrix/js/' . $module_id . '/inputmultisearch.js');
//Asset::getInstance()->addCss('/bitrix/css/main/font-awesome.css');
global $APPLICATION;
$APPLICATION->SetAdditionalCSS("/bitrix/css/main/font-awesome.css");

$request = \Bitrix\Main\HttpApplication::getInstance()->getContext()->getRequest();
$generalSettings = new \Commerce\Loyaltyprogram\Modulesettings;
if (!empty($request['ajax']) && $request['ajax'] == 'y') {
    $GLOBALS['APPLICATION']->RestartBuffer();
    if (!empty($request['type']) && $request['type'] == 'getPropList') {
        $arIblocks = [];
        \Bitrix\Main\Loader::includeModule('catalog');
        $res = CCatalog::GetList();
        while ($row = $res->Fetch()) {
            $arIblocks[$row['ID']] = $row['NAME'];
        }

        if (!empty($_REQUEST['values'])) {
            $res = \Bitrix\Iblock\PropertyTable::getList([
                "filter" => [
                    "ID" => $_REQUEST['values'],

                ]
            ]);
        } else {
            $searchText = $_REQUEST['value'];
            if (LANG_CHARSET == 'windows-1251') {
                $searchText = iconv("UTF-8", LANG_CHARSET, $_REQUEST['value']);
            }

            $res = \Bitrix\Iblock\PropertyTable::getList([
                "filter" => [
                    "%NAME" => $searchText,
                    'IBLOCK_ID' => array_keys($arIblocks)
                ],
                'limit'=>10
            ]);
        }

        $arProperties = [];
        while ($row = $res->fetch()) {
            $arProperties[$row['IBLOCK_ID']]['id'] = $row['IBLOCK_ID'];
            $arProperties[$row['IBLOCK_ID']]['name'] = $arIblocks[$row['IBLOCK_ID']];
            $arProperties[$row['IBLOCK_ID']]['props'][] = [
                "id" => $row['ID'],
                "name" => $row['NAME'],
            ];
        }

        echo \Bitrix\Main\Web\Json::encode($arProperties);

    } elseif (!empty($request['type']) && $request['type'] == 'getDescription') {
        echo $generalSettings->getRuleDescription($request['idRule']);
    } elseif (!empty($request['type']) && $request['type'] == 'getXml') {
        echo $generalSettings->getRuleXml($request['idRule']);
    } elseif (!empty($request['type']) && $request['type'] == 'setDescription') {
        /*$desc=$request['desc'];
        if(LANG_CHARSET=='windows-1251'){
            $desc=iconv("UTF-8", LANG_CHARSET, $request['desc']);
        }
        echo $generalSettings->setRuleDescription($request['idRule'], $desc);*/
    } elseif (!empty($request['type']) && $request['type'] == 'setXml') {/*
		$desc=$request['desc'];
		if(LANG_CHARSET=='windows-1251'){
			$desc=iconv("UTF-8", LANG_CHARSET, $request['desc']);
		}
		echo $generalSettings->setRuleXml($request['idRule'], $desc);*/
    } elseif (!empty($request['type']) && $request['type'] == 'getRefLink') {
        $component = new \Commerce\Loyaltyprogram\Components;
        echo $component->getRefLinkTest($request['refCode'], $request['refLink'], $request['refProp']);
    } elseif (!empty($request['type']) && $request['type'] == 'getCouponCode') {/*
		$component=new \Commerce\Loyaltyprogram\Components;
		echo json_encode(['coupon'=>$component->getCouponTest($request['couponRule'], $request['couponType'], $request['couponProp']), 'i'=>$request['i']]);
	*/
    } elseif (!empty($request['type']) && $request['type'] == 'updateRest') {
        Commerce\Loyaltyprogram\Rest\Manage::deleteApp();
        $appId = Commerce\Loyaltyprogram\Rest\Manage::findId();
        if (!$appId) {
            Commerce\Loyaltyprogram\Rest\Manage::addApp();
            $data = Commerce\Loyaltyprogram\Rest\Manage::findApp();
            // ����������� ����������� ������� "OnRestServiceBuildDescription" ������ "rest"
            \Bitrix\Main\EventManager::getInstance()->registerEventHandler(
                'rest',
                'OnRestServiceBuildDescription',
                $module_id,
                'Commerce\Loyaltyprogram\Rest\Manage',
                'OnRestServiceBuildDescription'
            );
            echo Loc::getMessage("commerce.loyaltyprogram_REST_ID_USER") . ' - <b>' . $data['USER_ID'] . '</b>; ' . Loc::getMessage("commerce.loyaltyprogram_REST_PASSWORDCODE") . ' - <b>' . $data['PASSWORD'] . '</b>';
            /*echo json_encode([
                Loc::getMessage("commerce.loyaltyprogram_REST_ID_USER")=>$data['USER_ID'],
                Loc::getMessage("commerce.loyaltyprogram_REST_PASSWORDCODE")=>$data['PASSWORD']
            ]);*/
        }
    }
    die();
}

$basketRulesList = [0 => '...'];
$discountIterator = Bitrix\Sale\Internals\DiscountTable::getList([
    'select' => ["ID", "NAME"],
    'filter' => ['ACTIVE' => 'Y'],
    'order' => ["NAME" => "ASC"]
]);
while ($discount = $discountIterator->fetch()) {
    $basketRulesList[$discount['ID']] = $discount['NAME'] . ' [' . $discount['ID'] . ']';
}

//list options...
$refUserList = [
    'ID' => Loc::getMessage("commerce.loyaltyprogram_PARAM_RUL_ID"),
    'LOGIN' => Loc::getMessage("commerce.loyaltyprogram_PARAM_RUL_LOGIN"),
    'XML_ID' => Loc::getMessage("commerce.loyaltyprogram_PARAM_RUL_XML_ID"),
    'PROP' => Loc::getMessage("commerce.loyaltyprogram_PARAM_RUL_PROP")
];

$couponCodeList = [
    'user_id' => Loc::getMessage("commerce.loyaltyprogram_PARAM_REF_COUPON_USERID"),
    'user_login' => Loc::getMessage("commerce.loyaltyprogram_PARAM_REF_COUPON_USERLOGIN"),
    'user_xml_id' => Loc::getMessage("commerce.loyaltyprogram_PARAM_REF_COUPON_USERXMLID"),
    'user_prop' => Loc::getMessage("commerce.loyaltyprogram_PARAM_REF_COUPON_USERPROP")

];

$propUserList = [];
$rsProps = CUserTypeEntity::GetList(['FIELD_NAME' => 'ASC'], ['USER_TYPE_ID' => 'string', 'ENTITY_ID' => 'USER', 'LANG' => LANGUAGE_ID]);
while ($arProp = $rsProps->GetNext()) {
    $propUserList[$arProp['FIELD_NAME']] = '[' . $arProp['FIELD_NAME'] . '] ' . $arProp['EDIT_FORM_LABEL'];
}

$currencyList = [];
$lcur = CCurrency::GetList(($by = "name"), ($order = "asc"), LANGUAGE_ID);
while ($lcur_res = $lcur->Fetch()) {
    $currencyList[$lcur_res['CURRENCY']] = $lcur_res['FULL_NAME'];
}


$groupUserList = ['0' => '...'];
$rsGroups = CGroup::GetList($by = "id", $order = "asc", [">ID" => '1']);
while ($group = $rsGroups->GetNext()) {
    if ($group['ID'] == 1) {
        continue;
    }
    $groupUserList[$group['ID']] = '[' . $group['ID'] . '] ' . $group['NAME'];
}

$setReferalList = [
    'ONLY_NEW' => Loc::getMessage("commerce.loyaltyprogram_PARAM_SET_REFERAL_NEW"),
    'ALL' => Loc::getMessage("commerce.loyaltyprogram_PARAM_SET_REFERAL_ALL")
];

$orderStatuses = [];
foreach (\Commerce\Loyaltyprogram\Settings::getInstance()->getOrderStatuses() as $nextStatus) {
    $orderStatuses[$nextStatus['STATUS_ID']] = '[' . $nextStatus['STATUS_ID'] . '] ' . $nextStatus['NAME'];
}


$aTabs = [
    [
        "DIV" => "sw24_general_settings_main",
        "TAB" => Loc::getMessage("commerce.loyaltyprogram_GENERAL_MAIN"),
        "TITLE" => Loc::getMessage("commerce.loyaltyprogram_GENERAL_MAIN_TITLE"),
        "OPTIONS" => [
            ['ref_perform_all', Loc::getMessage("commerce.loyaltyprogram_PARAM_REF_PERFORM_ALL_PROFILES"), '', ['checkbox']],
            ['ref_detail_stat', Loc::getMessage("commerce.loyaltyprogram_PARAM_REF_DETAIL_STAT"), '', ['checkbox']],
            ['filter_prop_prev', Loc::getMessage("commerce.loyaltyprogram_PARAM_REF_SET_PROP_FILTER"), '
<div class="sw24-input-multi-search">
    <div class="ims_item storage">
        <div class="storage_title">' . Loc::getMessage("commerce_loyaltyprogram.MILTISEARCH_selected_props") . '</div>
        <div class="storage_wrapper"></div>
    </div>
    <div class="ims_item search">
        <div class="search_item search_input">
            <input type="text" placeholder="' . Loc::getMessage("commerce_loyaltyprogram.MILTISEARCH_insert_props") . '">
        </div>
        <div class="search_item search_dropdown"></div>
    </div>
</div>', ['statichtml']]
        ]
    ],
    [
        "DIV" => "sw24_general_settings",
        "TAB" => Loc::getMessage("commerce.loyaltyprogram_GENERAL"),
        "TITLE" => Loc::getMessage("commerce.loyaltyprogram_GENERAL_TITLE"),
        "OPTIONS" => [
            GetMessage("commerce.loyaltyprogram_PARAM_MAIN_OPTION"),
            ['ref_active', Loc::getMessage("commerce.loyaltyprogram_PARAM_ACTIVE_ALL"), '', ['checkbox']],
            Loc::getMessage("commerce.loyaltyprogram_PARAM_REF_PARTNER"),
            ['ref_partner_active', Loc::getMessage("commerce.loyaltyprogram_PARAM_REF_PARTNER_ACTIVE"), '', ['checkbox']],
            GetMessage("commerce.loyaltyprogram_PARAM_REF_LINK"),
            ['ref_link_active', Loc::getMessage("commerce.loyaltyprogram_PARAM_REF_ACTIVE"), '', ['checkbox']],
            ['ref_link_name', Loc::getMessage("commerce.loyaltyprogram_PARAM_REF_NAME"), '', ['text', 10]],
            ['ref_link_value', Loc::getMessage("commerce.loyaltyprogram_PARAM_RUL_NAME"), '', ['selectbox', $refUserList]],
            ['ref_prop', Loc::getMessage("commerce.loyaltyprogram_PARAM_RUL_PROPS"), '', ['selectbox', $propUserList]],
            ['cookie_time', Loc::getMessage("commerce.loyaltyprogram_PARAM_COOKIE_TIME"), '', ['text', 20]],
            ['cookie_rename', Loc::getMessage("commerce.loyaltyprogram_PARAM_COOKIE_RENAME"), '', ['checkbox']],
            ['ref_link_group', Loc::getMessage("commerce.loyaltyprogram_PARAM_REF_GROUP"), Bitrix\Main\Config\Option::get($module_id, 'ref_link_group'), ['multiselectbox', $groupUserList]],
            GetMessage("commerce.loyaltyprogram_PARAM_REF_COUPON"),
            ['ref_coupon_active', Loc::getMessage("commerce.loyaltyprogram_PARAM_REF_COUPON_ACTIVE"), '', ['checkbox']],
            ['set_referal', Loc::getMessage("commerce.loyaltyprogram_PARAM_SET_REFERAL_NAME"), '', ['selectbox', $setReferalList]],
            ['note' => Loc::getMessage("commerce.loyaltyprogram_PARAM_REF_RULE") . '#1'],
            ['ref_basket_rules[]', Loc::getMessage("commerce.loyaltyprogram_PARAM_REF_BASKET_RULES"), $refBasketRules[0], ['selectbox', $basketRulesList]],
            ['ref_coupon_rule_desc', Loc::getMessage("commerce.loyaltyprogram_PARAM_REF_COUPON_CODE_RULE_DESC"), '', ['textarea',]],
            ['ref_coupon_code', Loc::getMessage("commerce.loyaltyprogram_PARAM_REF_COUPON_CODE"), '', ['selectbox', $couponCodeList]],
            ['ref_coupon_prop', Loc::getMessage("commerce.loyaltyprogram_PARAM_RUL_PROPS"), '', ['selectbox', $propUserList]],
            ['ref_coupon_rule_xml', Loc::getMessage("commerce.loyaltyprogram_PARAM_REF_COUPON_CODE_RULE_XML"), '', ['text']],
            ['ref_coupon_withoutprefix', Loc::getMessage("commerce.loyaltyprogram_PARAM_REF_COUPON_WITHOUTPREFIX"), '', ['checkbox']],

            ['sample_ref_coupon', Loc::getMessage("commerce.loyaltyprogram_PARAM_REF_COUPON_SAMPLE"), '<div class="sample_ref_link coupon"></div>', ['statichtml']],
            ['ref_coupon_istemporary', Loc::getMessage("commerce.loyaltyprogram_PARAM_REF_COUPON_ISTEMPORARY"), '', ['checkbox']],
            ['ref_coupon_group', Loc::getMessage("commerce.loyaltyprogram_PARAM_COUPON_GROUP"), Bitrix\Main\Config\Option::get($module_id, 'ref_coupon_group'), ['multiselectbox', $groupUserList]],
            ['deletelink', '&nbsp;', '<a href="javascript:void(0);" class="delete_coupon_rule">' . Loc::getMessage("commerce.loyaltyprogram_PARAM_REF_RULE_DELETE") . '</a>', ['statichtml']],
            GetMessage("commerce.loyaltyprogram_PARAM_SUB_OPTION"),
            ['group_user', Loc::getMessage("commerce.loyaltyprogram_PARAM_GROUP_REFERAL"), '', ['selectbox', $groupUserList]],
            ['ref_level', Loc::getMessage("commerce.loyaltyprogram_PARAM_REFERAL_LEVEL"), '', ['text', 10]],
        ]
    ],
    [
        "DIV" => "sw24_general_settings_bonus",
        "TAB" => Loc::getMessage("commerce.loyaltyprogram_GENERAL_BONUS"),
        "TITLE" => Loc::getMessage("commerce.loyaltyprogram_GENERAL_BONUS_TITLE"),
        "OPTIONS" => [
            GetMessage("commerce.loyaltyprogram_PARAM_ADD_BONUS_OPTION"),
            ['bonus_add_active', Loc::getMessage("commerce.loyaltyprogram_PARAM_BONUS_ADD_ACTIVE"), '', ['checkbox']],
            GetMessage("commerce.loyaltyprogram_PARAM_WRITE_OFF_BONUS_OPTION"),
            ['bonus_write_off_active', Loc::getMessage("commerce.loyaltyprogram_PARAM_WRITE_OFF_BONUS_ACTIVE"), '', ['checkbox']],
            GetMessage("commerce.loyaltyprogram_PARAM_BUY_BONUS_OPTION"),
            ['bonus_pay_active', Loc::getMessage("commerce.loyaltyprogram_PARAM_BONUS_PAY_ACTIVE"), '', ['checkbox']],
            ['ref_insert_to_soa', Loc::getMessage("commerce.loyaltyprogram_PARAM_REF_INSERT_TO_SOA"), '', ['checkbox']],
            ['bonus_return', Loc::getMessage("commerce.loyaltyprogram_PARAM_BONUS_RETURN"), '', ['checkbox']],
            ['bonus_as_discount', Loc::getMessage("commerce.loyaltyprogram_PARAM_BONUS_AS_DISCOUNT"), '', ['checkbox']],
            ['bonus_skip_discount_product', Loc::getMessage("commerce.loyaltyprogram_PARAM_BONUS_SKIP_DISCOUNT_PRODUCT"), '', ['checkbox']],
            ['bonus_skip_condition_product', Loc::getMessage("commerce.loyaltyprogram_PARAM_BONUS_SKIP_CONDITION_PRODUCT"), '', ['checkbox']],
            GetMessage("commerce.loyaltyprogram_PARAM_SUB_OPTION"),
            ['currency', Loc::getMessage("commerce.loyaltyprogram_PARAM_CURRENCY"), '', ['selectbox', $currencyList]],
            ['orderstatus', Loc::getMessage("commerce.loyaltyprogram_PARAM_ORDERSTATUSES"), '', ['selectbox', $orderStatuses]],
        ]
    ],
    [
        "DIV" => "sw24_general_settings_etemplates",
        "TAB" => Loc::getMessage("commerce.loyaltyprogram_GENERAL_ETEMPLATES"),
        "TITLE" => Loc::getMessage("commerce.loyaltyprogram_GENERAL_ETEMPLATES_TITLE"),
        "OPTIONS" => [
            ['notify_group_bonusacc', Loc::getMessage("commerce.loyaltyprogram_PARAM_NOTIFY_GROUP_BONUSACC"), '', ['checkbox']],
            ['notify_group_bonusremove', Loc::getMessage("commerce.loyaltyprogram_PARAM_NOTIFY_GROUP_BONUSREMOVE"), '', ['checkbox']],
            ['notify_overdue', Loc::getMessage("commerce.loyaltyprogram_PARAM_NOTIFY_OVERDUE"), '', ['checkbox']],
            ['notify_before_overdue', Loc::getMessage("commerce.loyaltyprogram_PARAM_NOTIFY_BEFORE_OVERDUE"), '', ['checkbox']],
            ['notify_delay_overdue', Loc::getMessage("commerce.loyaltyprogram_PARAM_NOTIFY_DELAY_OVERDUE"), '', ['text', 10]],
            ['notify_delay_overdue_type', Loc::getMessage("commerce.loyaltyprogram_PARAM_NOTIFY_DELAY_OVERDUE_TYPE"), '', ['selectbox', [
                'hour' => GetMessage("commerce.loyaltyprogram_TIME_HOUR"),
                'day' => GetMessage("commerce.loyaltyprogram_TIME_DAY"),
                'week' => GetMessage("commerce.loyaltyprogram_TIME_WEEK"),
                'month' => GetMessage("commerce.loyaltyprogram_TIME_MONTH"),
            ]]],
        ]
    ],
    ["DIV" => "sw24_general_agents", "TAB" => Loc::getMessage("commerce.loyaltyprogram_GENERAL_AGENTS"), "TITLE" => Loc::getMessage("commerce.loyaltyprogram_GENERAL_AGENTS_TITLE")],
    ["DIV" => "sw24_general_rest", "TAB" => Loc::getMessage("commerce.loyaltyprogram_GENERAL_REST"), "TITLE" => Loc::getMessage("commerce.loyaltyprogram_GENERAL_REST_TITLE")],
    ["DIV" => "sw24_general_access", "TAB" => Loc::getMessage("MAIN_TAB_RIGHTS"), "TITLE" => Loc::getMessage("MAIN_TAB_TITLE_RIGHTS")],
];

//set etemplates
$generalSettings = new \Commerce\Loyaltyprogram\Modulesettings;
$generalSettings->setGeneralTemplates();
$tempateOverdue = Bitrix\Main\Config\Option::get($module_id, 'etemplate_overdue');
$tmpSetting = $generalSettings->getTemplateById($tempateOverdue);
$linkTempateOverdue = '<a href="/bitrix/admin/message_edit.php?ID=' . $tempateOverdue . '" target="_blank">#' . $tmpSetting['ID'] . '</a>';

$tempateBeforeOverdue = Bitrix\Main\Config\Option::get($module_id, 'etemplate_before_overdue');
$tmpSetting = $generalSettings->getTemplateById($tempateBeforeOverdue);
$linkTempateBeforeOverdue = '<a href="/bitrix/admin/message_edit.php?ID=' . $tempateBeforeOverdue . '" target="_blank">#' . $tmpSetting['ID'] . '</a>';

$tempateBonucAcc = Bitrix\Main\Config\Option::get($module_id, 'etemplate_group_bonusacc');
$tempateBonucRemove = Bitrix\Main\Config\Option::get($module_id, 'etemplate_group_bonusremove');
$tmpSetting = $generalSettings->getTemplateById($tempateBonucAcc);
$linkTempateBonucAcc = '<a href="/bitrix/admin/message_edit.php?ID=' . $tempateBonucAcc . '" target="_blank">#' . $tmpSetting['ID'] . '</a>';
$tmpSetting = $generalSettings->getTemplateById($tempateBonucRemove);
$linkTempateBonucRemove = '<a href="/bitrix/admin/message_edit.php?ID=' . $tempateBonucRemove . '" target="_blank">#' . $tmpSetting['ID'] . '</a>';

if ($request->isPost() && $request['Update'] && check_bitrix_sessid()) {
    foreach ($aTabs as $keyTab => $aTab) {
        if (!empty($aTab['OPTIONS'])) {

            if ($keyTab == 0) {
                $aTab['OPTIONS'][] = [
                    "filter_prop",
                    "filter_prop",
                    "",
                    ["multiselectbox"]
                ];
            }
            __AdmSettingsSaveOptions($module_id, $aTab['OPTIONS']);
        }
    }

    //include pay block to sale.order.ajax
    if (!empty($request['ref_insert_to_soa']) && $request['ref_insert_to_soa'] == 'Y') {

        $soaIntegration = true;
        $handlers = \Bitrix\Main\EventManager::getInstance()->findEventHandlers('sale', 'OnSaleComponentOrderResultPrepared');
        foreach ($handlers as $nextHandler) {
            if ($nextHandler['TO_MODULE_ID'] == $module_id && $nextHandler['TO_METHOD'] == 'soaIntegration') {
                $soaIntegration = false;
                break;
            }
        }
        if ($soaIntegration === true) {
            \Bitrix\Main\EventManager::getInstance()->registerEventHandler(
                'sale',
                'OnSaleComponentOrderResultPrepared',
                $module_id,
                "Commerce\\Loyaltyprogram\\Eventmanager",
                'soaIntegration'
            );
        }
    }

    if (
        (!empty($request['ref_link_active']) && $request['ref_link_active'] == 'Y' && !empty($request['ref_active']) && $request['ref_active'] == 'Y') ||
        (!empty($request['ref_partner_active']) && $request['ref_partner_active'] == 'Y' && !empty($request['ref_active']) && $request['ref_active'] == 'Y') ||
        (!empty($request['ref_coupon_active']) && $request['ref_coupon_active'] == 'Y' && !empty($request['ref_active']) && $request['ref_active'] == 'Y')
    ) {

        $register = true;
        $handlers = \Bitrix\Main\EventManager::getInstance()->findEventHandlers('main', 'OnAfterUserAdd');
        foreach ($handlers as $nextHandler) {
            if ($nextHandler['TO_MODULE_ID'] == $module_id && $nextHandler['TO_METHOD'] == 'registerUser') {
                $register = false;
                break;
            }
        }
        if ($register === true) {
            \Bitrix\Main\EventManager::getInstance()->registerEventHandler(
                'main',
                'OnAfterUserAdd',
                $module_id,
                "Commerce\\Loyaltyprogram\\Eventmanager",
                'registerUser'
            );
        }
    }

    if (!empty($request['ref_coupon_active']) && $request['ref_coupon_active'] == 'Y' && !empty($request['ref_active']) && $request['ref_active'] == 'Y') {
        $controlCoupon = true;
        $handlers = \Bitrix\Main\EventManager::getInstance()->findEventHandlers('sale', 'onManagerCouponApply');
        foreach ($handlers as $nextHandler) {
            if ($nextHandler['TO_MODULE_ID'] == $module_id && $nextHandler['TO_METHOD'] == 'onCouponApply') {
                $controlCoupon = false;
                break;
            }
        }
        if ($controlCoupon === true) {
            /*\Bitrix\Main\EventManager::getInstance()->registerEventHandler(
                'sale',
                'onManagerCouponApply',
                $module_id,
                "Commerce\\Loyaltyprogram\\Eventmanager",
                'onCouponApply'
            );*/
            \Bitrix\Main\EventManager::getInstance()->registerEventHandler(
                'sale',
                'onManagerCouponAdd',
                $module_id,
                "Commerce\\Loyaltyprogram\\Eventmanager",
                'onCouponApply'
            );
            \Bitrix\Main\EventManager::getInstance()->registerEventHandler(
                'sale',
                'OnSaleOrderSaved',
                $module_id,
                "Commerce\\Loyaltyprogram\\Eventmanager",
                'orderBonusAdd'
            );
        }
    }

    Bitrix\Main\Config\Option::set($module_id, 'ref_basket_rules', 0);
    if (count($request['ref_basket_rules']) > 0) {
        //$tmpRules=array_unique($request['ref_basket_rules']);
        //$tmpRules=array_values(array_diff($tmpRules, [0]));

        foreach ($request['ref_basket_rules'] as $keyRequest => $nextRule) {

            $tmpKey = ($keyRequest == 0) ? '' : $keyRequest;
            if (!empty($request['ref_coupon_rule_desc' . $tmpKey])) {
                $generalSettings->setRuleDescription($nextRule, trim($request['ref_coupon_rule_desc' . $tmpKey]));
            }
            if (!empty($request['ref_coupon_rule_xml' . $tmpKey]) && !empty($nextRule)) {
                $generalSettings->setRuleXml($nextRule, trim($request['ref_coupon_rule_xml' . $tmpKey]));
            }

            if ($nextRule > 0) {
                $tmpRules[] = $nextRule;
                $currentCount = (count($tmpRules) == 1) ? '' : count($tmpRules) - 1;

                if (!empty($currentCount)) {
                    $clearProps = [
                        'ref_coupon_group' => '0',
                        'ref_coupon_istemporary' => 'N',
                        'ref_coupon_withoutprefix' => 'N'
                    ];

                    foreach ($clearProps as $keyProp => $valProp) {
                        Bitrix\Main\Config\Option::set($module_id, $keyProp . $currentCount, $valProp);
                    }
                }


                if (!empty($request['ref_coupon_code' . $keyRequest])) {
                    Bitrix\Main\Config\Option::set($module_id, 'ref_coupon_code' . $currentCount, $request['ref_coupon_code' . $currentCount]);
                }
                if (!empty($request['ref_coupon_prop' . $keyRequest])) {
                    Bitrix\Main\Config\Option::set($module_id, 'ref_coupon_prop' . $currentCount, $request['ref_coupon_prop' . $currentCount]);
                }
                if (!empty($request['ref_coupon_group' . $keyRequest])) {
                    Bitrix\Main\Config\Option::set($module_id, 'ref_coupon_group' . $currentCount, implode(',', $request['ref_coupon_group' . $currentCount]));
                }
                if (!empty($request['ref_coupon_istemporary' . $keyRequest])) {
                    Bitrix\Main\Config\Option::set($module_id, 'ref_coupon_istemporary' . $currentCount, $request['ref_coupon_istemporary' . $currentCount]);
                }
                if (!empty($request['ref_coupon_withoutprefix' . $keyRequest])) {
                    Bitrix\Main\Config\Option::set($module_id, 'ref_coupon_withoutprefix' . $currentCount, $request['ref_coupon_withoutprefix' . $currentCount]);
                }
            }
        }
        if (!empty($tmpRules) && count($tmpRules) > 0) {
            //set event for registration by coupon
            $handlers = EventManager::getInstance()->findEventHandlers('sale', 'OnSaleOrderSaved');
            $regEvent = true;
            foreach ($handlers as $nextHandler) {
                if ($nextHandler['TO_CLASS'] == 'Commerce\Loyaltyprogram\Eventmanager' && $nextHandler['TO_METHOD'] == 'registerByCoupon') {
                    $regEvent = false;
                    break;
                }
            }
            if ($regEvent) {
                EventManager::getInstance()->registerEventHandler(
                    "sale",
                    "OnSaleOrderSaved",
                    $module_id,
                    "Commerce\\Loyaltyprogram\\Eventmanager",
                    "registerByCoupon",
                    "20"
                );
            }
            Bitrix\Main\Config\Option::set($module_id, 'ref_basket_rules', implode(',', $tmpRules));
        }
    }
}

$component = new \Commerce\Loyaltyprogram\Components;
//$sampleRefLink=$component->getRefLink();
$sampleRefLink = $component->getRefLinkTest(
    Bitrix\Main\Config\Option::get($module_id, 'ref_link_name'),
    Bitrix\Main\Config\Option::get($module_id, 'ref_link_value'),
    Bitrix\Main\Config\Option::get($module_id, 'ref_prop')
);

$sampleRefCoupons = $component->getCouponsTest();

$refBasketRules = (!empty(Bitrix\Main\Config\Option::get($module_id, 'ref_basket_rules'))) ? explode(',', Bitrix\Main\Config\Option::get($module_id, 'ref_basket_rules')) : [0];
$tmpOptions = [
    Loc::getMessage("commerce.loyaltyprogram_PARAM_MAIN_OPTION"),
    ['ref_active', Loc::getMessage("commerce.loyaltyprogram_PARAM_ACTIVE_ALL"), '', ['checkbox']],
    Loc::getMessage("commerce.loyaltyprogram_PARAM_REF_PARTNER"),
    ['ref_partner_active', Loc::getMessage("commerce.loyaltyprogram_PARAM_REF_PARTNER_ACTIVE"), '', ['checkbox']],
    Loc::getMessage("commerce.loyaltyprogram_PARAM_REF_LINK"),
    ['ref_link_active', Loc::getMessage("commerce.loyaltyprogram_PARAM_REF_ACTIVE"), '', ['checkbox']],
    ['ref_link_name', Loc::getMessage("commerce.loyaltyprogram_PARAM_REF_NAME"), '', ['text', 10]],
    ['ref_link_value', Loc::getMessage("commerce.loyaltyprogram_PARAM_RUL_NAME"), '', ['selectbox', $refUserList]],
    ['ref_prop', Loc::getMessage("commerce.loyaltyprogram_PARAM_RUL_PROPS"), '', ['selectbox', $propUserList]],
    ['sample_ref_link', Loc::getMessage("commerce.loyaltyprogram_PARAM_REF_SAMPLE"), '<div class="sample_ref_link">' . $sampleRefLink . '</div>', ['statichtml']],

    ['cookie_time', Loc::getMessage("commerce.loyaltyprogram_PARAM_COOKIE_TIME"), '', ['text', 20]],
    ['cookie_rename', Loc::getMessage("commerce.loyaltyprogram_PARAM_COOKIE_RENAME"), '', ['checkbox']],
    ['ref_link_group', Loc::getMessage("commerce.loyaltyprogram_PARAM_REF_GROUP"), Bitrix\Main\Config\Option::get($module_id, 'ref_link_group'), ['multiselectbox', $groupUserList]],

    Loc::getMessage("commerce.loyaltyprogram_PARAM_REF_COUPON"),
    ['ref_coupon_active', Loc::getMessage("commerce.loyaltyprogram_PARAM_REF_COUPON_ACTIVE"), '', ['checkbox']],
    ['set_referal', Loc::getMessage("commerce.loyaltyprogram_PARAM_SET_REFERAL_NAME"), '', ['selectbox', $setReferalList]],
    ['note' => Loc::getMessage("commerce.loyaltyprogram_PARAM_REF_RULE") . '#1'],
    ['ref_basket_rules[]', Loc::getMessage("commerce.loyaltyprogram_PARAM_REF_BASKET_RULES"), $refBasketRules[0], ['selectbox', $basketRulesList]],
    ['ref_coupon_rule_desc', Loc::getMessage("commerce.loyaltyprogram_PARAM_REF_COUPON_CODE_RULE_DESC"), '', ['textarea',]],
    ['ref_coupon_code', Loc::getMessage("commerce.loyaltyprogram_PARAM_REF_COUPON_CODE"), '', ['selectbox', $couponCodeList]],
    ['ref_coupon_prop', Loc::getMessage("commerce.loyaltyprogram_PARAM_RUL_PROPS"), '', ['selectbox', $propUserList]],
    ['ref_coupon_rule_xml', Loc::getMessage("commerce.loyaltyprogram_PARAM_REF_COUPON_CODE_RULE_XML"), '', ['text']],
    ['ref_coupon_withoutprefix', Loc::getMessage("commerce.loyaltyprogram_PARAM_REF_COUPON_WITHOUTPREFIX"), '', ['checkbox']],

    ['sample_ref_coupon', Loc::getMessage("commerce.loyaltyprogram_PARAM_REF_COUPON_SAMPLE"), '<div class="sample_ref_link coupon">' . $sampleRefCoupons[$refBasketRules[0]]['COUPON'] . '</div>', ['statichtml']],
    ['ref_coupon_istemporary', Loc::getMessage("commerce.loyaltyprogram_PARAM_REF_COUPON_ISTEMPORARY"), '', ['checkbox']],
    ['ref_coupon_group', Loc::getMessage("commerce.loyaltyprogram_PARAM_COUPON_GROUP"), Bitrix\Main\Config\Option::get($module_id, 'ref_coupon_group'), ['multiselectbox', $groupUserList]],

    ['deletelink', '&nbsp;', '<a href="javascript:void(0);" class="delete_coupon_rule">' . Loc::getMessage("commerce.loyaltyprogram_PARAM_REF_RULE_DELETE") . '</a>', ['statichtml']],
];
if (count($refBasketRules) > 1) {
    foreach ($refBasketRules as $key => $val) {
        if ($key == 0) {
            continue;
        }
        $tmpOptions[] = ['note' => Loc::getMessage("commerce.loyaltyprogram_PARAM_REF_RULE") . '#' . ($key + 1)];
        $tmpOptions[] = ['ref_basket_rules[]', Loc::getMessage("commerce.loyaltyprogram_PARAM_REF_BASKET_RULES"), $refBasketRules[$key], ['selectbox', $basketRulesList]];
        $tmpOptions[] = ['ref_coupon_rule_desc' . $key, Loc::getMessage("commerce.loyaltyprogram_PARAM_REF_COUPON_CODE_RULE_DESC"), '', ['textarea']];
        $tmpOptions[] = ['ref_coupon_code' . $key, Loc::getMessage("commerce.loyaltyprogram_PARAM_REF_COUPON_CODE"), Bitrix\Main\Config\Option::get($module_id, 'ref_coupon_code' . $key), ['selectbox', $couponCodeList]];
        $tmpOptions[] = ['ref_coupon_prop' . $key, Loc::getMessage("commerce.loyaltyprogram_PARAM_RUL_PROPS"), Bitrix\Main\Config\Option::get($module_id, 'ref_coupon_prop' . $key), ['selectbox', $propUserList]];
        $tmpOptions[] = ['ref_coupon_rule_xml' . $key, Loc::getMessage("commerce.loyaltyprogram_PARAM_REF_COUPON_CODE_RULE_XML"), '', ['text']];
        $tmpOptions[] = ['ref_coupon_withoutprefix' . $key, Loc::getMessage("commerce.loyaltyprogram_PARAM_REF_COUPON_WITHOUTPREFIX"), Bitrix\Main\Config\Option::get($module_id, 'ref_coupon_withoutprefix' . $key), ['checkbox']];

        $tmpOptions[] = ['sample_ref_coupon', Loc::getMessage("commerce.loyaltyprogram_PARAM_REF_COUPON_SAMPLE"), '<div class="sample_ref_link coupon">' . $sampleRefCoupons[$refBasketRules[$key]]['COUPON'] . '</div>', ['statichtml']];
        $tmpOptions[] = ['ref_coupon_istemporary' . $key, Loc::getMessage("commerce.loyaltyprogram_PARAM_REF_COUPON_ISTEMPORARY"), Bitrix\Main\Config\Option::get($module_id, 'ref_coupon_istemporary' . $key), ['checkbox']];
        $tmpOptions[] = ['ref_coupon_group' . $key, Loc::getMessage("commerce.loyaltyprogram_PARAM_COUPON_GROUP"), Bitrix\Main\Config\Option::get($module_id, 'ref_coupon_group' . $key), ['multiselectbox', $groupUserList]];
        $tmpOptions[] = ['deletelink', '&nbsp;', '<a href="javascript:void(0);" class="delete_coupon_rule">' . Loc::getMessage("commerce.loyaltyprogram_PARAM_REF_RULE_DELETE") . '</a>', ['statichtml']];
    }
}
$tmpOptions[] = GetMessage("commerce.loyaltyprogram_PARAM_SUB_OPTION");
$tmpOptions[] = ['group_user', Loc::getMessage("commerce.loyaltyprogram_PARAM_GROUP_REFERAL"), '', ['selectbox', $groupUserList]];
$tmpOptions[] = ['ref_level', Loc::getMessage("commerce.loyaltyprogram_PARAM_REFERAL_LEVEL"), '', ['text', 10]];
$aTabs[1]['OPTIONS'] = $tmpOptions;


$tabControl = new CAdminTabControl("tabControl_sw24", $aTabs);
$tabControl->Begin();

?>
<form class="loyalty_settings" method="post"
      action="<? echo $APPLICATION->GetCurPage() ?>?mid=<?= urlencode($module_id) ?>&amp;lang=<?= LANGUAGE_ID ?>">
    <?
    $tabControl->BeginNextTab();
    __AdmSettingsDrawList($module_id, $aTabs[0]['OPTIONS']);
    $tabControl->BeginNextTab();
    __AdmSettingsDrawList($module_id, $aTabs[1]['OPTIONS']);
    $tabControl->BeginNextTab();
    __AdmSettingsDrawList($module_id, $aTabs[2]['OPTIONS']);
    $tabControl->BeginNextTab();
    //etemplates
    $notifyOverduechecked = (Bitrix\Main\Config\Option::get($module_id, 'notify_overdue') == 'Y') ? ' checked="checked"' : '';
    $notifyGroupBonusacc = (Bitrix\Main\Config\Option::get($module_id, 'notify_group_bonusacc') == 'Y') ? ' checked="checked"' : '';
    $notifyGroupBonusremove = (Bitrix\Main\Config\Option::get($module_id, 'notify_group_bonusremove') == 'Y') ? ' checked="checked"' : '';
    $notifyBeforeOverdueChecked = (Bitrix\Main\Config\Option::get($module_id, 'notify_before_overdue') == 'Y') ? ' checked="checked"' : '';
    $notifyDelayOverdue = Bitrix\Main\Config\Option::get($module_id, 'notify_delay_overdue');
    $notifyDelayOverdueType = Bitrix\Main\Config\Option::get($module_id, 'notify_delay_overdue_type');
    ?>
    <tr class="heading">
        <td colspan="2"><?= GetMessage("commerce.loyaltyprogram_PARAM_NOTIFY_OVERDUE") ?></td>
    </tr>
    <tr>
        <td width="50%"
            class="adm-detail-content-cell-l"><?= GetMessage("commerce.loyaltyprogram_PARAM_NOTIFY_OVERDUE_LETTER") ?></td>
        <td width="50%" class="adm-detail-content-cell-r"><input type="checkbox" id="notify_overdue"
                                                                 name="notify_overdue"
                                                                 value="Y"<?= $notifyOverduechecked ?>> <?= $linkTempateOverdue ?>
        </td>
    </tr>
    <tr>
        <td width="50%"
            class="adm-detail-content-cell-l"><?= Loc::getMessage("commerce.loyaltyprogram_PARAM_NOTIFY_BEFORE_OVERDUE_LETTER") ?></td>
        <td width="50%" class="adm-detail-content-cell-r"><input type="checkbox" id="notify_before_overdue"
                                                                 name="notify_before_overdue"
                                                                 value="Y"<?= $notifyBeforeOverdueChecked ?>> <?= $linkTempateBeforeOverdue ?>
        </td>
    </tr>
    <tr class="overdue_delay_row">
        <td width="50%"
            class="adm-detail-content-cell-l"><?= Loc::getMessage("commerce.loyaltyprogram_PARAM_NOTIFY_DELAY_OVERDUE") ?></td>
        <td width="50%" class="adm-detail-content-cell-r">
            <input type="number" size="20" maxlength="255" value="<?= $notifyDelayOverdue ?>"
                   name="notify_delay_overdue" min="0" step="1" style="width: 50px;">
            <select name="notify_delay_overdue_type">
                <?
                $types = [
                    //'hour'=>Loc::getMessage("commerce.loyaltyprogram_TIME_HOUR"),
                    'day' => Loc::getMessage("commerce.loyaltyprogram_TIME_DAY"),
                    'week' => Loc::getMessage("commerce.loyaltyprogram_TIME_WEEK"),
                    'month' => Loc::getMessage("commerce.loyaltyprogram_TIME_MONTH")
                ];
                foreach ($types as $keyType => $nextType) {
                    $select = ($keyType == $notifyDelayOverdueType) ? ' selected="selected"' : ''; ?>
                    <option value="<?= $keyType ?>"<?= $select ?>><?= $nextType ?></option>
                <? } ?>
            </select>
        </td>
    </tr>
    <tr class="heading">
        <td colspan="2"><?= Loc::getMessage("commerce.loyaltyprogram_PARAM_NOTIFY_GROUP_BONUSACC") ?></td>
    </tr>
    <tr>
        <td width="50%"
            class="adm-detail-content-cell-l"><?= Loc::getMessage("commerce.loyaltyprogram_PARAM_NOTIFY_GROUP_BONUSACC_LETTER") ?></td>
        <td width="50%" class="adm-detail-content-cell-r"><input type="checkbox" id="notify_group_bonusacc"
                                                                 name="notify_group_bonusacc"
                                                                 value="Y"<?= $notifyGroupBonusacc ?>> <?= $linkTempateBonucAcc ?>
        </td>
    </tr>
    <tr>
        <td width="50%"
            class="adm-detail-content-cell-l"><?= Loc::getMessage("commerce.loyaltyprogram_PARAM_NOTIFY_GROUP_BONUSREMOVE_LETTER") ?></td>
        <td width="50%" class="adm-detail-content-cell-r"><input type="checkbox" id="notify_group_bonusremove"
                                                                 name="notify_group_bonusremove"
                                                                 value="Y"<?= $notifyGroupBonusremove ?>> <?= $linkTempateBonucRemove ?>
        </td>
    </tr>
    <?
    $tabControl->BeginNextTab();
    $namesAgent = [
        '\Commerce\Loyaltyprogram\Eventmanager::setRanks();' => Loc::getMessage("commerce.loyaltyprogram_AGENT_SETRANKS"),
        '\Commerce\Loyaltyprogram\Eventmanager::manageBonuses();' => Loc::getMessage("commerce.loyaltyprogram_AGENT_MANAGEBONUSES"),
        '\Commerce\Loyaltyprogram\Eventmanager::birthday();' => Loc::getMessage("commerce.loyaltyprogram_AGENT_BIRTHDAY"),
        '\Commerce\Loyaltyprogram\Eventmanager::turnover();' => Loc::getMessage("commerce.loyaltyprogram_AGENT_TURNOVER"),
        '\Commerce\Loyaltyprogram\Eventmanager::turnoverRef();' => Loc::getMessage("commerce.loyaltyprogram_AGENT_TURNOVER_REF"),
        '\Commerce\Loyaltyprogram\Eventmanager::completedProfile();' => Loc::getMessage("commerce.loyaltyprogram_AGENT_COMPLETEDPROFILE")
    ];
    $res = CAgent::GetList(["ID" => "DESC"], ['MODULE_ID' => "commerce.loyaltyprogram"]);
    while ($agent = $res->GetNext()) {
        $name = (empty($namesAgent[$agent['NAME']])) ? 'noname' : $namesAgent[$agent['NAME']];
        ?>
        <tr>
            <td width="40%" class="adm-detail-content-cell-l"><?= $name ?></td>
            <td width="60%" class="adm-detail-content-cell-r"><a
                        href="/bitrix/admin/agent_edit.php?ID=<?= $agent['ID'] ?>&lang=<?= LANGUAGE_ID ?>"
                        target="_blank">#<?= $agent['ID'] ?></a></td>
        </tr>
    <?
    }
    $tabControl->BeginNextTab();
    if (\Bitrix\Main\Loader::includeModule('rest')) {
        $data = Commerce\Loyaltyprogram\Rest\Manage::findApp();
        if ($data == false) {
            $str = Loc::getMessage("commerce.loyaltyprogram_REST_NOT_PERMISIION");
        } else {
            $str = Loc::getMessage("commerce.loyaltyprogram_REST_ID_USER") . ' - <b>' . $data['USER_ID'] . '</b>; ' . Loc::getMessage("commerce.loyaltyprogram_REST_PASSWORDCODE") . ' - <b>' . $data['PASSWORD'] . '</b>';
        }
        ?>
        <tr>
            <td width="40%"
                class="adm-detail-content-cell-l"><?= Loc::getMessage("commerce.loyaltyprogram_REST_PASSWORD") ?></td>
            <td width="60%" class="adm-detail-content-cell-r"><span class="data_rest"><?= $str ?></span><a
                        class="adm-btn-save adm-btn updaterest"
                        href="#"><?= Loc::getMessage("commerce.loyaltyprogram_REST_UPDATEPASSWORD") ?></a></td>
        </tr>
    <?
    } else {
        ?>
        <tr>
            <td width="40%"
                class="adm-detail-content-cell-l"><?= Loc::getMessage("commerce.loyaltyprogram_REST_MODULE_NOT_INCLUDED") ?></td>
            <td width="60%" class="adm-detail-content-cell-r">
                <a class="adm-btn-save adm-btn updaterest"
                   href="/bitrix/admin/module_admin.php?lang=<?= LANGUAGE_ID ?>"><?= Loc::getMessage("commerce.loyaltyprogram_REST_MODULE_ACTIVATE") ?></a>
            </td>
        </tr>
    <?
    }
    $tabControl->BeginNextTab();
    require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/admin/group_rights.php");
    $tabControl->Buttons(); ?>
    <input type="submit" name="Update" class="adm-btn-save" value="<?= Loc::getMessage("MAIN_SAVE") ?>">
    <input type="reset" name="reset" value="<?= Loc::getMessage("MAIN_RESET") ?>">
    <?= bitrix_sessid_post(); ?>
</form>
<?
$tabControl->End();
?>
<script>
    var sampleCode = '<?=randString(6, ["ABCDEFGHIJKLNMOPQRSTUVWXYZ"])?>';

    <?
    global $USER;
    $usArr = CUser::GetByID($USER->GetID());
    $arUser = $usArr->Fetch();
    ?>
    var cUser = <?=\Bitrix\Main\Web\Json::encode($arUser);?>

    function removeRule(o) {
        if (confirm(BX.message('deleteRule') + '?')) {
            let parentRow = o.closest('tr'),
                remArr = [];

            for (let i = 0; i < 20; i++) {
                remArr[i] = parentRow;
                parentRow = remArr[i].previousElementSibling;
                if (parentRow.querySelector('[name="ref_basket_rules[]"]')) {
                    //parentRow.querySelector('[name="ref_basket_rules[]"]').value=0;
                    parentRow.querySelector('[name="ref_basket_rules[]"]').selectedIndex = 0;
                    break;
                }
            }
            /*for(let i=0; i<remArr.length; i++){
                remArr[i].remove();
            }*/
            //document.querySelector('.loyalty_settings').submit();
            let el = document.querySelector('.loyalty_settings .adm-btn-save');
            el.click();
        }
    }


    function getRuleDescription(idRule, cInput) {
        let cData = '';
        BX.ajax({
            url: location.href,
            data: {
                'ajax': 'y',
                'idRule': idRule,
                'type': 'getDescription'
            },
            method: 'POST',
            dataType: 'json',
            timeout: 300,
            async: false,
            onsuccess: function (data) {
                cData = data;
                cInput.innerHTML = '';
                if (data && data != false) {
                    cInput.innerHTML = data
                }
            },
            onfailure: function (data) {
                console.log(data);
            }
        });
        return cData;
    }

    function getRuleXml(idRule, cInput, i) {
        let cData = '';
        BX.ajax({
            url: location.href,
            data: {
                'ajax': 'y',
                'idRule': idRule,
                'type': 'getXml'
            },
            method: 'POST',
            dataType: 'json',
            timeout: 300,
            async: false,
            onsuccess: function (data) {
                cData = data;
                cInput.value = '';
                let samplesArea = BX('sw24_general_settings').querySelectorAll(".sample_ref_link.coupon");
                if (data && data != false) {
                    setTimeout(function () {
                        cInput.value = data
                        samplesArea[i].innerHTML = samplesArea[i].innerHTML.replace(sampleCode, data);
                    }, 10);
                }
            },
            onfailure: function (data) {
                console.log(data);
            }
        });
        return cData;
    }

    BX.message({
        'rule': '<?=Loc::getMessage("commerce.loyaltyprogram_PARAM_REF_RULE")?>',
        'deleteRule': '<?=Loc::getMessage("commerce.loyaltyprogram_PARAM_REF_RULE_DELETE")?>'
    });

    BX.ready(function () {

        BX.ready(function () {
            new BX.InputMultiSearch({
                storage: <?=\Bitrix\Main\Web\Json::encode($generalSettings->getFilterProps());?>,
                inputName: "filter_prop"
            });
        })

        <?
        $selectBasketRulesArr = empty(Bitrix\Main\Config\Option::get($module_id, 'ref_basket_rules')) ? 0 : Bitrix\Main\Config\Option::get($module_id, 'ref_basket_rules');
        ?>
        var mainArea = BX('sw24_general_settings_edit_table'),
            refParamSelect = document.querySelector('select[name=ref_link_value]'),
            refPropSelect = document.querySelector('select[name=ref_prop]'),
            //refCouponParamSelect=document.querySelector('select[name=ref_coupon_code]'),
            //refPropCouponSelect=document.querySelector('select[name=ref_coupon_prop]'),
            refCookieTime = document.querySelector('[name=cookie_time]'),
            refLevel = document.querySelector('[name=ref_level]'),
            notifyBeforeOverdue = document.querySelector('[name=notify_before_overdue]'),
            notifyBeforeOverdueRow = document.querySelector('.overdue_delay_row'),
            //basketRulesSet=document.querySelector('[name="ref_basket_rules[]"]'),
            selectBasketRulesArr = [<?=$selectBasketRulesArr?>],
            selectBasketRulesCount = selectBasketRulesArr.length,
            timerRefLink = 0;

        refParamSelect.addEventListener('change', function () {
            showHidePropSelect();
            setNewRefLink();
        });

        let typeGenerateCoupon = document.querySelectorAll('[name^=ref_coupon_code]');
        if (typeGenerateCoupon.length > 0) {
            for (let i = 0; i < typeGenerateCoupon.length; i++) {
                if (typeGenerateCoupon[i].value != 'user_prop') {
                    typeGenerateCoupon[i].closest('tr').nextElementSibling.style.display = 'none';
                }
            }
        }

        notifyBeforeOverdue.addEventListener('change', function () {
            showHidePropDelayOverdue();
        });

        let buttonAddRow = BX.create('tr');
        buttonAddRow.innerHTML = '<td width="50%" class="adm-detail-content-cell-l"></td>' +
            '<td width="50%" class="adm-detail-content-cell-r"><input type="button" name="addRule" value="<?=Loc::getMessage("commerce.loyaltyprogram_ADD")?>" ></td>';
        let tmpLinks = document.querySelectorAll('.delete_coupon_rule'),
            tmpLink = tmpLinks[0];
        for (let i = 0; i < tmpLinks.length; i++) {
            tmpLink = tmpLinks[i];
        }
        tmpLink.closest('tr').parentNode.insertBefore(buttonAddRow, tmpLink.closest('tr').nextSibling);

        let addRuleButton = document.querySelector('[name="addRule"]');
        addRuleButton.addEventListener('click', function (e) {
            addCouponRuleArea();
        });

        function addCouponRuleArea() {
            couponRuleArea = document.createElement("tr");
            couponRuleArea.innerHTML = '<td colspan="2" align="center"><div class="adm-info-message-wrap" align="center"><div class="adm-info-message">' + BX.message('rule') + '#' + (selectBasketRulesCount * 1 + 1) + '</div></div></td>';
            buttonAddRow.parentNode.insertBefore(couponRuleArea, buttonAddRow);
            let els = ['[name="ref_basket_rules[]"]', '[name="ref_coupon_rule_desc"]', '[name="ref_coupon_rule_xml"]', '[name=ref_coupon_withoutprefix]', '[name=ref_coupon_code]', '[name=ref_coupon_prop]', '[name="ref_coupon_group[]"]', '.delete_coupon_rule'];
            for (let i = 0; i < els.length; i++) {
                let nextRow = document.querySelector(els[i]).closest('tr').innerHTML;
                couponRuleArea = document.createElement("tr");
                couponRuleArea.innerHTML = nextRow;
                if (couponRuleArea.querySelector('select')) {
                    couponRuleArea.querySelector('select').value = couponRuleArea.querySelector('option').value;
                }
                if (i == 0) {
                    couponRuleArea.querySelector('.rule_link').style.display = 'none';
                    /*couponRuleArea.querySelector('.rule_link_desc').style.display='none';
                    couponRuleArea.querySelector('.rule_link_desc').addEventListener('click', function(){
                        createRuleDescription(this);
                    })*/
                } else if (i == 1) {
                    couponRuleArea.querySelector('textarea').name = 'ref_coupon_rule_desc' + selectBasketRulesCount;
                } else if (i == 2) {
                    couponRuleArea.querySelector('input').name = 'ref_coupon_rule_xml' + selectBasketRulesCount;
                } else if (i == 3) {
                    couponRuleArea.querySelector('input').name = 'ref_coupon_withoutprefix' + selectBasketRulesCount;
                } else if (i == 4) {
                    couponRuleArea.querySelector('select').name = 'ref_coupon_code' + selectBasketRulesCount;
                } else if (i == 5) {
                    couponRuleArea.style.display = 'none';
                    couponRuleArea.querySelector('select').name = 'ref_coupon_prop' + selectBasketRulesCount;
                } else if (i == 6) {
                    couponRuleArea.querySelector('select').name = 'ref_coupon_group' + selectBasketRulesCount + '[]';
                }
                buttonAddRow.parentNode.insertBefore(couponRuleArea, buttonAddRow);
            }
            let CouponSampleArea = mainArea.querySelector('.sample_ref_link.coupon').closest('tr');
            couponRuleArea = document.createElement("tr");
            couponRuleArea.innerHTML = CouponSampleArea.innerHTML;
            couponRuleArea.querySelector('.sample_ref_link.coupon').innerHTML = '';
            buttonAddRow.parentNode.insertBefore(couponRuleArea, buttonAddRow);
            selectBasketRulesCount++;
            loyaltyTools.setHint();
        }

        BX('sw24_general_settings').addEventListener('change', function (e) {
            if (e.target.name && e.target.name.indexOf('ref_prop') > -1) {
                setNewRefLink();
            } else if (e.target.name && e.target.name.indexOf('ref_coupon_withoutprefix') > -1) {
                setRuleLink();
            } else if (e.target.name && e.target.name == 'ref_basket_rules[]') {
                setRuleLink(true);
            } else if (e.target.name && e.target.name.indexOf('ref_coupon_code') > -1) {
                showHidePropCouponSelect.call(e.target);
                setRuleLink();
            }/*else if(e.target.name && e.target.name.indexOf('ref_coupon_prop')>-1){
			updateCoupon(e.target);
		}*/
        })

        BX('sw24_general_settings').addEventListener('keyup', function (e) {
            if (e.target.name && e.target.name.indexOf('ref_link_name') > -1) {
                setNewRefLink();
            } else if (e.target.name && e.target.name.indexOf('ref_coupon_rule_xml') > -1) {
                setRuleLink();
            }
        });

        function setNewRefLink() {
            clearTimeout(timerRefLink);
            timerRefLink = setTimeout(function () {
                BX.ajax({
                    url: location.href,
                    data: {
                        'ajax': 'y',
                        'type': 'getRefLink',
                        'refCode': BX('sw24_general_settings').querySelector('[name=ref_link_name]').value,
                        'refLink': refParamSelect.value,
                        'refProp': refPropSelect.value,
                    },
                    method: 'POST',
                    dataType: 'html',
                    timeout: 300,
                    async: false,
                    onsuccess: function (data) {
                        BX('sw24_general_settings').querySelector('.sample_ref_link').innerHTML = data;
                    },
                    onfailure: function (data) {
                        console.log(data);
                    }
                });
            }, 300);
        }

        BX('sw24_general_settings').addEventListener('click', function (e) {
            if (e.target.className && e.target.className == 'delete_coupon_rule') {
                removeRule(e.target);
            }
        })

        function setRuleLink(withGetAjax) {
            let linkRules = BX('sw24_general_settings').querySelectorAll("[name='ref_basket_rules[]']");
            if (linkRules.length > 0) {
                for (let i = 0; i < linkRules.length; i++) {
                    let nextLinkRules = linkRules[i], newLink, newDescLink;
                    if (!nextLinkRules.nextElementSibling || nextLinkRules.nextElementSibling.nodeName != 'A' || nextLinkRules.nextElementSibling.className != 'rule_link') {
                        newLink = BX.create('a', {
                            'attrs': {
                                'class': 'rule_link',
                                'target': '_blank',
                                'href': '/bitrix/admin/sale_discount_edit.php?ID=' + nextLinkRules.value + '&lang=<?=LANGUAGE_ID?>'
                            }, 'text': '<?=Loc::getMessage("commerce_loyaltyprogram.EDIT_RULE")?>'
                        });
                        nextLinkRules.parentNode.insertBefore(newLink, nextLinkRules.nextSibling);
                    } else {
                        newLink = nextLinkRules.nextElementSibling;
                        newDescLink = newLink.nextElementSibling;
                        newLink.href = '/bitrix/admin/sale_discount_edit.php?ID=' + nextLinkRules.value + '&lang=<?=LANGUAGE_ID?>';
                        //newDescLink.dataset.rule=nextLinkRules.value;
                    }
                    let samplesArea = BX('sw24_general_settings').querySelectorAll(".sample_ref_link.coupon"),
                        descRules = BX('sw24_general_settings').querySelectorAll("[name^=ref_coupon_rule_desc]"),
                        xmlRules = BX('sw24_general_settings').querySelectorAll("[name^=ref_coupon_rule_xml]"),
                        usersCode = BX('sw24_general_settings').querySelectorAll("[name^=ref_coupon_code]"),
                        usersWithoutPrefix = BX('sw24_general_settings').querySelectorAll("[name^=ref_coupon_withoutprefix]"),
                        usersProps = BX('sw24_general_settings').querySelectorAll("[name^=ref_coupon_prop]");
                    samplesArea[i].innerHTML = '';
                    if (nextLinkRules.value > 0) {
                        if (withGetAjax && withGetAjax == true) {
                            getRuleDescription(nextLinkRules.value, descRules[i]);
                            getRuleXml(nextLinkRules.value, xmlRules[i], i);
                        }
                        let tmpCode = sampleCode;
                        let usCode = cUser['ID'];
                        if (usersCode[i].value == 'user_login') {
                            usCode = cUser['LOGIN'];
                        } else if (usersCode[i].value == 'user_xml_id') {
                            usCode = cUser['XML_ID'];
                        } else if (usersCode[i].value == 'user_prop') {
                            usCode = cUser[usersProps[i].value];
                        }
                        if (xmlRules[i].value != '' && !withGetAjax) {
                            tmpCode = xmlRules[i].value;
                        }
                        if (usersWithoutPrefix[i] && usersWithoutPrefix[i].checked) {
                            samplesArea[i].innerHTML = usCode;
                        } else {
                            samplesArea[i].innerHTML = tmpCode + '_' + usCode;
                        }
                    }
                }
            }
        }

        setRuleLink(true);

        function showHidePropDelayOverdue() {
            var dspl = (notifyBeforeOverdue.checked) ? 'table-row' : 'none';
            notifyBeforeOverdueRow.style.display = dspl;
        }

        showHidePropDelayOverdue();

        function convertToNumber() {
            this.type = 'number';
            this.min = '0';
            this.step = '1';
            this.style.width = '50px';
        }

        function showHidePropSelect(direction) {
            var rowProps = refPropSelect.parentElement.parentElement,
                rowDisplay = 'table-row',
                selectDisable = false;
            if (refParamSelect.value != 'PROP') {
                rowDisplay = 'none';
                selectDisable = true;
            }
            refPropSelect.disabled = selectDisable;
            rowProps.style.display = rowDisplay;
        }

        function showHidePropCouponSelect() {

            var rowProps = this.closest('tr').nextElementSibling,
                rowDisplay = 'table-row',
                selectDisable = false;
            if (this.value != 'user_prop') {
                rowDisplay = 'none';
                selectDisable = true;
            }
            rowProps.style.display = rowDisplay;
        }


        <?if(\Bitrix\Main\Loader::includeModule('rest')){?>
        BX.bindDelegate(
            document.querySelector('#sw24_general_rest'),
            'click',
            {
                tagName: 'a',
                className: 'updaterest'
            },
            function (e) {
                e.preventDefault();
                let self = this;
                console.log('updaterest');
                BX.ajax({
                    url: location.href,
                    data: {
                        'ajax': 'y',
                        'type': 'updateRest'
                    },
                    method: 'POST',
                    dataType: 'html',
                    timeout: 300,
                    async: false,
                    onsuccess: function (data) {
                        console.log(data);
                        console.log(self.closest('td').querySelector('.data_rest'));
                        self.closest('td').querySelector('.data_rest').innerHTML = data;
                    },
                    onfailure: function (data) {
                        console.log(data);
                    }
                });
            }
        );
        <?}?>


        //start call
        showHidePropSelect();
        //showHidePropCouponSelect();
        convertToNumber.call(refCookieTime);
        convertToNumber.call(refLevel);
    })
</script>