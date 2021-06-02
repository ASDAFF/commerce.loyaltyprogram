<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
use Bitrix\Main\Localization\Loc;
	Loc::loadMessages(__FILE__);

$countWriteOff=0;

if(\Bitrix\Main\Loader::includeModule('commerce.loyaltyprogram')) {
    $data = \Commerce\Loyaltyprogram\Entity\WriteOffTable::getList([
        'filter' => ['status' => 'request'],
        'select' => ['CNT'],
        'runtime' => [new \Bitrix\Main\Entity\ExpressionField('CNT', 'COUNT(*)')]

    ]);

    while ($arData = $data->fetch()) {
        $countWriteOff = $arData["CNT"];
    }
}
$writeOffIcon=$countWriteOff>0?'subscribe_menu_icon':'';

?><?
$aMenu = [
    "parent_menu" => "global_menu_marketing", // поместим в раздел "Маркетинг"
    "sort"        => 100,                    // вес пункта меню
    "url"         => "",  // ссылка на пункте меню
    "text"        => Loc::getMessage("commerce.loyaltyprogram_MENU_MAIN"),       // текст пункта меню
    "title"       => Loc::getMessage("commerce.loyaltyprogram_MENU_MAIN_TITLE"), // текст всплывающей подсказки
    "icon"        => "skwb24_loyaltyprogram_menu_icon", // малая иконка
    "items_id"    => "commerce_loyaltyprogram",  // идентификатор ветви
    "items"       => [// остальные уровни меню сформируем ниже.
		[
			"title"       => Loc::getMessage("commerce.loyaltyprogram_MENU_MANAGE_TITLE"), // текст всплывающей подсказки
			"text"        => Loc::getMessage("commerce.loyaltyprogram_MENU_MANAGE"),       // текст пункта меню
			"items_id"    => "menu_sw24_loyal_manage",  // идентификатор ветви
			"items"       => [
				[
					"url"         => "commerce_loyaltyprogram_profiles.php?lang=".LANGUAGE_ID,  // ссылка на пункте меню
					"title"       => Loc::getMessage("commerce.loyaltyprogram_MENU_PROFILE_TITLE"), // текст всплывающей подсказки
					"text"        => Loc::getMessage("commerce.loyaltyprogram_MENU_PROFILE"),       // текст пункта меню
					"items_id"    => "sw24_loyal_manage_profile"
				],
				[
					"url"         => "commerce_loyaltyprogram_referrals.php?lang=".LANGUAGE_ID,  // ссылка на пункте меню
					"title"       => Loc::getMessage("commerce.loyaltyprogram_MENU_REFERRALS_TITLE"), // текст всплывающей подсказки
					"text"        => Loc::getMessage("commerce.loyaltyprogram_MENU_REFERRALS"),       // текст пункта меню
				],
				[
					"url"         => "commerce_loyaltyprogram_ranks.php?lang=".LANGUAGE_ID,  // ссылка на пункте меню
					"title"       => Loc::getMessage("commerce.loyaltyprogram_MENU_RANKS_TITLE"), // текст всплывающей подсказки
					"text"        => Loc::getMessage("commerce.loyaltyprogram_MENU_RANKS"),       // текст пункта меню
					"items_id"    => "menu_sw24_loyal_manage_ranks",
					"items"        => [
						[
							"url"         =>"commerce_loyaltyprogram_ranks_list_users.php?lang=".LANGUAGE_ID,
							"title"       => Loc::getMessage("commerce.loyaltyprogram_MENU_RANKS_LIST_USERS_TITLE"), // текст всплывающей подсказки
							"text"        => Loc::getMessage("commerce.loyaltyprogram_MENU_RANKS_LIST_USERS"),       // текст пункта меню
						],
					]
				],
				[
					"url"         => "commerce_loyaltyprogram_groups.php?lang=".LANGUAGE_ID,   // ссылка на пункте меню
					"title"       => Loc::getMessage("commerce.loyaltyprogram_MENU_GROUPS_TITLE"), // текст всплывающей подсказки
					"text"        => Loc::getMessage("commerce.loyaltyprogram_MENU_GROUPS"),       // текст пункта меню
				],
				[
					"url"         => "commerce_loyaltyprogram_writeoff.php?lang=".LANGUAGE_ID,   // ссылка на пункте меню
					"title"       => Loc::getMessage("commerce.loyaltyprogram_MENU_WRITEOFF_TITLE"), // текст всплывающей подсказки
					"text"        => Loc::getMessage("commerce.loyaltyprogram_MENU_WRITEOFF"),       // текст пункта меню
                    "icon"    => $writeOffIcon,
				],
				[
					"url"         => "commerce_loyaltyprogram_import.php?lang=".LANGUAGE_ID,   // ссылка на пункте меню
					"title"       => Loc::getMessage("commerce.loyaltyprogram_MENU_IMPORT_TITLE"), // текст всплывающей подсказки
					"text"        => Loc::getMessage("commerce.loyaltyprogram_MENU_IMPORT"),       // текст пункта меню
				],
				[
					"url"         => "commerce_loyaltyprogram_queue.php?lang=".LANGUAGE_ID,   // ссылка на пункте меню
					"title"       => Loc::getMessage("commerce.loyaltyprogram_MENU_EDITQUEUE_TITLE"), // текст всплывающей подсказки
					"text"        => Loc::getMessage("commerce.loyaltyprogram_MENU_EDITQUEUE"),       // текст пункта меню
				]
			],
		],
		[
			"title"       => Loc::getMessage("commerce.loyaltyprogram_MENU_BALANCE_TITLE"), // текст всплывающей подсказки
			"text"        => Loc::getMessage("commerce.loyaltyprogram_MENU_BALANCE"),       // текст пункта меню
			"items_id"    => "menu_sw24_loyal_balance",  // идентификатор ветви
			"items"       => [
				[
					"url"         => "/bitrix/admin/sale_account_admin.php?lang=".LANGUAGE_ID,  // ссылка на пункте меню
					"title"       => Loc::getMessage("commerce.loyaltyprogram_MENU_ACCOUNT_TITLE"), // текст всплывающей подсказки
					"text"        => Loc::getMessage("commerce.loyaltyprogram_MENU_ACCOUNT"),       // текст пункта меню
				],
				[
					"url"         => "commerce_loyaltyprogram_transact.php?lang=".LANGUAGE_ID,  // ссылка на пункте меню
					"title"       => Loc::getMessage("commerce.loyaltyprogram_MENU_TRANSACT_TITLE"), // текст всплывающей подсказки
					"text"        => Loc::getMessage("commerce.loyaltyprogram_MENU_TRANSACT"),       // текст пункта меню
				]
			]
		],
		[
			"url"         => "commerce_loyaltyprogram_documentation.php?lang=".LANGUAGE_ID,   // ссылка на пункте меню
			"title"       => Loc::getMessage("commerce.loyaltyprogram_MENU_DOCUMENTATION_TITLE"), // текст всплывающей подсказки
			"text"        => Loc::getMessage("commerce.loyaltyprogram_MENU_DOCUMENTATION"),       // текст пункта меню
		],
		[
			"url"         => "/bitrix/admin/settings.php?lang=".LANGUAGE_ID."&mid_menu=1&mid=commerce.loyaltyprogram",
			"title"       => GetMessage("commerce.loyaltyprogram_MENU_SETTING_TITLE"), // текст всплывающей подсказки
			"text"        => GetMessage("commerce.loyaltyprogram_MENU_SETTING"),       // текст пункта меню
		]
	]   
];

return $aMenu;
?>