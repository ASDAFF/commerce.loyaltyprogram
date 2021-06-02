<?php

use Bitrix\Main\Config\Option;
use Bitrix\Main\EventManager;
use Bitrix\Main\Localization\Loc;

IncludeModuleLangFile(__FILE__);

class commerce_loyaltyprogram extends CModule
{
    const MODULE_ID = 'commerce.loyaltyprogram';
    var $MODULE_ID = 'commerce.loyaltyprogram';
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    var $MODULE_CSS;
    var $_502226742 = '';

    function __construct()
    {
        $arModuleVersion = array();
        include(dirname(__FILE__) . '/version.php');
        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME = GetMessage('commerce.loyaltyprogram_MODULE_NAME');
        $this->MODULE_DESCRIPTION = GetMessage('commerce.loyaltyprogram_MODULE_DESC');
        $this->PARTNER_NAME = GetMessage('commerce.loyaltyprogram_PARTNER_NAME');
        $this->PARTNER_URI = GetMessage('commerce.loyaltyprogram_PARTNER_URI');
    }

    function DoInstall()
    {
        global $APPLICATION;
        $this->InstallFiles();
        $this->InstallDB();
        RegisterModule(self::MODULE_ID);
        $this->InstallEvents();
    }

    function InstallFiles($_392044183 = array())
    {
        if (is_dir($_1125038350 = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . self::MODULE_ID . '/admin')) {
            if ($_1285493423 = opendir($_1125038350)) {
                while (false !== $_677798160 = readdir($_1285493423)) {
                    if ($_677798160 == '..' || $_677798160 == '.' || $_677798160 == 'menu.php') continue;
                    file_put_contents($_2039289076 = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin/commerce_loyaltyprogram_' . $_677798160, '<' . '? require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/' . self::MODULE_ID . '/admin/' . $_677798160 . '");?' . '>');
                }
                closedir($_1285493423);
            }
        }
        if (is_dir($_1125038350 = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . self::MODULE_ID . '/install/components')) {
            if ($_1285493423 = opendir($_1125038350)) {
                while (false !== $_677798160 = readdir($_1285493423)) {
                    if ($_677798160 == '..' || $_677798160 == '.') continue;
                    CopyDirFiles($_1125038350 . '/' . $_677798160, $_SERVER['DOCUMENT_ROOT'] . '/bitrix/components/' . $_677798160, $_1403249312 = True, $_2103574692 = True);
                }
                closedir($_1285493423);
            }
        }
        CopyDirFiles($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/themes', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/themes', true, true);
        CopyDirFiles($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/js', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/js', true, true);
        CopyDirFiles($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/images', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/images', true, true);
        $this->installLoyalDirectory();
        return true;
    }

    function installLoyalDirectory()
    {
        $_1371702673 = 'loyalty';
        $_60533336 = '<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
		$APPLICATION->SetTitle("' . GetMessage('commerce.loyaltyprogram_MODULE_NAME') . '");
		$APPLICATION->AddChainItem("' . GetMessage('commerce.loyaltyprogram_MODULE_NAME') . '", "/' . $_1371702673 . '/");?>
		<?$APPLICATION->IncludeComponent("commerce:loyaltyprogram", ".default", array(
			"CACHE_TIME" => "3600",
			"CACHE_TYPE" => "A",
			"CHAIN_REFERRAL" => "' . GetMessage('commerce.loyaltyprogram_MODULE_CABINET') . '",
			"CHAIN_BONUSES" => "' . GetMessage('commerce.loyaltyprogram_MODULE_ACC') . '",
			"DISPLAY_PAGER" => "Y",
			"PAGER_COUNT" => "10",
			"PAGER_NAME" => "",
			"PAGER_TEMPLATE" => "modern",
			"SEF_FOLDER" => "/' . $_1371702673 . '/",
			"SEF_MODE" => "Y",
			"COMPONENT_TEMPLATE" => ".default",
			"TITLE_REFERRAL" => "' . GetMessage('commerce.loyaltyprogram_MODULE_CABINET') . '",
			"TITLE_BONUSES" => "' . GetMessage('commerce.loyaltyprogram_MODULE_ACC') . '",
			"SEF_URL_TEMPLATES" => array(
				"referral" => "referral/",
				"bonuses" => "bonuses/",
			)),	false);?>
		<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>';
        $_1361648559 = CSite::GetList($_1513538929 = 'sort', $_1300439869 = 'desc');
        while ($_1754873173 = $_1361648559->Fetch()) {
            if (!file_exists($_1754873173['ABS_DOC_ROOT'] . '/' . $_1371702673) && !is_dir($_1754873173['ABS_DOC_ROOT'] . '/' . $_1371702673)) {
                mkdir($_1754873173['ABS_DOC_ROOT'] . '/' . $_1371702673);
                $_530028093 = fopen($_1754873173['ABS_DOC_ROOT'] . '/' . $_1371702673 . '/index.php', 'w');
                fwrite($_530028093, $_60533336);
                fclose($_530028093);
                CUrlRewriter::Add(['SITE_ID' => $_1754873173['LID'], 'CONDITION' => '#^/' . $_1371702673 . '/#', 'ID' => 'commerce:loyaltyprogram', 'PATH' => '/' . $_1371702673 . '/index.php']);
            }
        }
    }

    function InstallDB($_392044183 = array())
    {
        global $DB;
        $DB->Query('CREATE TABLE IF NOT EXISTS commerce_loyal_profiles (
			id INT NOT NULL AUTO_INCREMENT,
			sort SMALLINT UNSIGNED NOT NULL DEFAULT 0,
			active VARCHAR(2),
			name VARCHAR(100) NOT NULL,
			type VARCHAR(20) NOT NULL,
			site VARCHAR(40),
			date_setting datetime NULL,
			settings TEXT NULL,
			email_settings TEXT NULL,
			sms_settings TEXT NULL,
			PRIMARY KEY (id),
			KEY sort (sort)
		);');
        $DB->Query('CREATE TABLE IF NOT EXISTS commerce_loyal_action_list (
			id INT NOT NULL AUTO_INCREMENT,
			PRIMARY KEY (id)
		);');
        $DB->Query('CREATE TABLE IF NOT EXISTS commerce_loyal_users (
			id INT NOT NULL AUTO_INCREMENT,
			user INT NOT NULL,
			ref_user INT NOT NULL,
			type VARCHAR(20) NOT NULL DEFAULT "link",
			level INT UNSIGNED NOT NULL DEFAULT 1,
			date_create datetime NULL,
			source_link INT NULL,
			comment VARCHAR(120),
			PRIMARY KEY (id),
			KEY user (user),
			KEY ref_user (ref_user),
			KEY type (type),
			KEY level (level)
		);');
        $DB->Query('CREATE TABLE IF NOT EXISTS commerce_loyal_bonuses (
			id INT NOT NULL AUTO_INCREMENT,
			bonus_start decimal(18,4) NOT NULL,
			bonus decimal(18,4) NOT NULL,
			user_id INT NOT NULL,
			user_bonus INT UNSIGNED NOT NULL DEFAULT 0,
			order_id INT UNSIGNED,
			currency VARCHAR(3) NOT NULL,
			profile_type VARCHAR(20) NOT NULL,
			profile_id INT UNSIGNED NOT NULL,
			action_id INT UNSIGNED NULL,
			status VARCHAR(20) NOT NULL,
			date_add datetime NULL,
			date_remove datetime NULL,
			add_comment TEXT NULL,
			comments TEXT NULL,
			email TEXT NULL,
			sms TEXT NULL,
			notify VARCHAR(1) NOT NULL DEFAULT "N",
			PRIMARY KEY (id),
			KEY user_id (user_id),
			KEY order_id (order_id),
			KEY status (status),
			KEY currency (currency),
			KEY profile_type (profile_type),
			KEY profile_id (profile_id),
			KEY notify (notify)
		);');
        $DB->Query('CREATE TABLE IF NOT EXISTS commerce_loyal_bonuses_transaction (
			id INT AUTO_INCREMENT,
			bonus_id INT NULL DEFAULT NULL,
			transaction_id INT NOT NULL,
			PRIMARY KEY (id),
			KEY bonus_id (bonus_id),
			KEY transaction_id (transaction_id)
		);');
        $DB->Query('CREATE TABLE IF NOT EXISTS commerce_loyal_coupons (
			id INT AUTO_INCREMENT,
			user_id INT NOT NULL,
			rule_id INT NOT NULL,
			coupon_id INT NOT NULL,
			coupon VARCHAR(100) NOT NULL,
			PRIMARY KEY (id),
			KEY user_id (user_id),
			KEY rule_id (rule_id),
			KEY coupon_id (coupon_id)
		);');
        $DB->Query('CREATE TABLE IF NOT EXISTS commerce_loyal_rule_desc (
			id INT AUTO_INCREMENT,
			id_rule INT NOT NULL,
			description TEXT NULL,
			PRIMARY KEY (id),
			KEY id_rule (id_rule)
		);');
        $DB->Query('CREATE TABLE IF NOT EXISTS commerce_loyal_stat_link (
			id INT AUTO_INCREMENT,
			user INT UNSIGNED NOT NULL,
			transfer INT UNSIGNED default 0,
			reg INT UNSIGNED default 0,
			PRIMARY KEY (id),
			KEY user (user),
			KEY transfer (transfer),
			KEY reg (reg)
		);');
        $DB->Query('CREATE TABLE IF NOT EXISTS commerce_loyal_stat_site (
			id INT AUTO_INCREMENT,
			user INT UNSIGNED NOT NULL,
			transfer INT UNSIGNED default 0,
			reg INT UNSIGNED default 0,
			PRIMARY KEY (id),
			KEY user (user),
			KEY transfer (transfer),
			KEY reg (reg)
		);');
        $DB->Query('CREATE TABLE IF NOT EXISTS commerce_loyal_write_off (
			id INT AUTO_INCREMENT,
			bonus decimal(18,4) NOT NULL,
			transact_id INT UNSIGNED NOT NULL,
			profile_id INT UNSIGNED NOT NULL,
			user_id INT UNSIGNED NOT NULL,
			requisites_id INT UNSIGNED NOT NULL,
			date_order TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			status VARCHAR(20) NOT NULL,
			date_change TIMESTAMP,
			comment TEXT NULL,
			log TEXT NULL,
			PRIMARY KEY (id),
			KEY user_id (user_id),
			KEY requisites_id (requisites_id),
			KEY date_order (date_order),
			KEY date_change (date_change),
			KEY status (status)
		);');
        $DB->Query('CREATE TABLE IF NOT EXISTS commerce_loyal_user_requisites(
			id INT AUTO_INCREMENT,
			user_id INT UNSIGNED NOT NULL,
			cart_number VARCHAR(20) NULL,
			bik VARCHAR(20) NULL,
			invoice VARCHAR(20) NULL,
			date_change TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			active VARCHAR(1) NOT NULL DEFAULT "Y",
			PRIMARY KEY (id),
			KEY user_id (user_id),
			KEY active (active)
		);');
        $DB->Query('CREATE TABLE IF NOT EXISTS commerce_loyal_partner_sites(
			id INT AUTO_INCREMENT,
			user_id INT UNSIGNED NOT NULL,
			site VARCHAR(100) NOT NULL,
			code VARCHAR(33) NOT NULL,
			confirmed VARCHAR(1) NOT NULL DEFAULT "N",
			date_create TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			date_confirm TIMESTAMP NULL,
			PRIMARY KEY (id),
			KEY user_id (user_id),
			KEY site (site),
			KEY confirmed (confirmed),
			KEY date_create (date_create),
			KEY date_confirm (date_confirm)
		);');
        $DB->Query('CREATE TABLE IF NOT EXISTS commerce_loyal_subscribe_user(
			id INT NOT NULL AUTO_INCREMENT,
			user_id INT UNSIGNED NOT NULL,
			email VARCHAR(100) NOT NULL,
			date_create TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY user_id (user_id),
			KEY email (email));');
        $DB->Query('CREATE TABLE IF NOT EXISTS commerce_loyal_ranks (
			id INT NOT NULL AUTO_INCREMENT,
			name VARCHAR(100) NOT NULL,
			sort INT UNSIGNED NOT NULL DEFAULT 100,
			active VARCHAR(2) DEFAULT "N",
			coeff decimal(10,2) NOT NULL DEFAULT 1,
			type VARCHAR(30) NOT NULL,
			value decimal(18,4) NOT NULL DEFAULT 0,
			settings TEXT NULL,
			profiles TEXT NULL, 
			date_setting TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY sort (sort),
			KEY active (active),
			KEY coeff (coeff),
			KEY name (name),
			KEY type (type),
			KEY date_setting (date_setting)
		);');
        $DB->Query('CREATE TABLE IF NOT EXISTS commerce_loyal_rank_users (
			id INT NOT NULL AUTO_INCREMENT,
			user_id INT UNSIGNED NOT NULL,
			rank_id INT UNSIGNED NOT NULL,
			active VARCHAR(2) DEFAULT "N",
    		params TEXT NULL,
			date_setting TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY user_id (user_id),
			KEY rank_id (rank_id),
			KEY active (active),
			KEY date_setting (date_setting)
		);');
        $DB->Query('CREATE TABLE IF NOT EXISTS commerce_loyal_detail_stat (
			id INT AUTO_INCREMENT,
			date_stat TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			ref_user  INT UNSIGNED NOT NULL,
			user INT UNSIGNED default 0,
			type VARCHAR(20) NOT NULL,
			ip VARCHAR(20) default \'0.0.0.0\',
			url VARCHAR(250) NULL,
			description TEXT NULL,
			PRIMARY KEY (id),
			KEY date_stat (date_stat),
			KEY ref_user (ref_user),
			KEY user (user),
			KEY type (type),
			KEY ip (ip),
			KEY url (url)
		);');
        return true;
    }

    function InstallEvents()
    {
        EventManager::getInstance()->registerEventHandler('main', 'OnProlog', self::MODULE_ID, 'Commerce\Loyaltyprogram\Eventmanager', 'checkReferalLink');
        \Bitrix\Main\EventManager::getInstance()->registerEventHandler('sale', 'OnBeforeUserAccountDelete', self::MODULE_ID, 'Commerce\Loyaltyprogram\Eventmanager', 'clearBonusByUserBefore');
        \Bitrix\Main\EventManager::getInstance()->registerEventHandler('sale', 'OnAfterUserAccountDelete', self::MODULE_ID, 'Commerce\Loyaltyprogram\Eventmanager', 'clearBonusByUser');
        \CAgent::AddAgent('\Commerce\Loyaltyprogram\Eventmanager::manageBonuses();', self::MODULE_ID, 'N', 600, '', 'Y');
        return true;
    }

    function DoUninstall()
    {
        global $APPLICATION, $step;
        $step = IntVal($step);
        if ($_REQUEST['SAVE']) {
            $this->UnInstallDB();
        } else {
            $APPLICATION->IncludeAdminFile(Loc::getMessage('commerce.loyaltyprogram_MODULE_NAME'), $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/step.php');
        }
        $this->UnInstallEvents();
        UnRegisterModule(self::MODULE_ID);
        $this->UnInstallFiles();
    }

    function UnInstallDB($_392044183 = array())
    {
        $_1180073284 = CAgent::GetList(array('ID' => 'DESC'), array('MODULE_ID' => self::MODULE_ID));
        while ($_2074054231 = $_1180073284->GetNext()) {
            CAgent::Delete($_2074054231['ID']);
        }
        global $DB;
        if ($_REQUEST['SAVE'] == 'N') {
            $DB->Query('DROP TABLE IF EXISTS commerce_loyal_users;');
            $DB->Query('DROP TABLE IF EXISTS commerce_loyal_bonuses;');
            $DB->Query('DROP TABLE IF EXISTS commerce_loyal_bonuses_transaction;');
            $DB->Query('DROP TABLE IF EXISTS commerce_loyal_coupons;');
            $DB->Query('DROP TABLE IF EXISTS commerce_loyal_rule_desc;');
            $DB->Query('DROP TABLE IF EXISTS commerce_loyal_stat_link;');
            $DB->Query('DROP TABLE IF EXISTS commerce_loyal_stat_site;');
            $DB->Query('DROP TABLE IF EXISTS commerce_loyal_detail_stat;');
            $DB->Query('DROP TABLE IF EXISTS commerce_loyal_write_off;');
            $DB->Query('DROP TABLE IF EXISTS commerce_loyal_user_requisites;');
            $DB->Query('DROP TABLE IF EXISTS commerce_loyal_action_list;');
            $DB->Query('DROP TABLE IF EXISTS commerce_loyal_partner_sites;');
            $DB->Query('DROP TABLE IF EXISTS commerce_loyal_ranks;');
            $DB->Query('DROP TABLE IF EXISTS commerce_loyal_rank_users;');
        }
        $DB->Query('DROP TABLE IF EXISTS commerce_loyal_profiles;');
        $DB->Query('DROP TABLE IF EXISTS commerce_loyal_subscribe_user;');
        Option::delete($this->MODULE_ID);
        $_1522433969 = CEventType::GetList();
        $_338742050 = new CEventType;
        while ($_1118192930 = $_1522433969->Fetch()) {
            if (strpos($_1118192930['EVENT_NAME'], 'COMMERCE_LOYAL_') !== false) {
                $_338742050->Delete($_1118192930['EVENT_NAME']);
            }
        }
        $_1804823033 = CEventMessage::GetList($_1513538929 = 'site_id', $_1300439869 = 'desc', []);
        while ($_359242698 = $_1804823033->GetNext()) {
            if (strpos($_359242698['EVENT_NAME'], 'COMMERCE_LOYAL_') !== false) {
                CEventMessage::Delete($_359242698['ID']);
            }
        }
        return true;
    }

    function UnInstallEvents()
    {
        $_1821161433 = ['main' => ['OnProlog', 'OnAfterUserAdd', 'checkReferalLink', '\Bitrix\Main\Mail\Internal\Event::onBeforeAdd'], 'sale' => ['OnSaleOrderSaved', 'OnSaleOrderCanceled', 'OnSalePaymentEntitySaved', 'OnBeforeUserAccountDelete', 'OnAfterUserAccountDelete', 'OnSaleComponentOrderResultPrepared'], 'sender' => ['MailingSubscriptionOnAfterAdd']];
        foreach ($_1821161433 as $_1249339867 => $_1398438401) {
            foreach ($_1398438401 as $_1440545338) {
                $_437329811 = EventManager::getInstance()->findEventHandlers($_1249339867, $_1440545338);
                foreach ($_437329811 as $_1769518044) {
                    if ($_1769518044['TO_CLASS'] == 'Commerce\Loyaltyprogram\Eventmanager') {
                        EventManager::getInstance()->unRegisterEventHandler($_1249339867, $_1440545338, self::MODULE_ID, 'Commerce\Loyaltyprogram\Eventmanager', $_1769518044['TO_METHOD']);
                    }
                }
            }
        }
        \Bitrix\Main\EventManager::getInstance()->unRegisterEventHandler('rest', 'OnRestServiceBuildDescription', self::MODULE_ID, 'Commerce\Loyaltyprogram\Rest\Bonus', 'OnRestServiceBuildDescription');
        return true;
    }

    function UnInstallFiles()
    {
        if (is_dir($_1125038350 = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . self::MODULE_ID . '/admin')) {
            if ($_1285493423 = opendir($_1125038350)) {
                while (false !== $_677798160 = readdir($_1285493423)) {
                    if ($_677798160 == '..' || $_677798160 == '.') continue;
                    unlink($_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin/commerce_loyaltyprogram_' . $_677798160);
                }
                closedir($_1285493423);
            }
        }
        if (is_dir($_1125038350 = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . self::MODULE_ID . '/install/components')) {
            if ($_1285493423 = opendir($_1125038350)) {
                while (false !== $_677798160 = readdir($_1285493423)) {
                    if ($_677798160 == '..' || $_677798160 == '.' || !is_dir($_1659054390 = $_1125038350 . '/' . $_677798160)) continue;
                    $_1891480801 = opendir($_1659054390);
                    while (false !== $_1440262786 = readdir($_1891480801)) {
                        if ($_1440262786 == '..' || $_1440262786 == '.') continue;
                        DeleteDirFilesEx('/bitrix/components/' . $_677798160 . '/' . $_1440262786);
                    }
                    closedir($_1891480801);
                }
                closedir($_1285493423);
            }
        }
        DeleteDirFilesEx('/bitrix/js/' . $this->MODULE_ID);
        DeleteDirFilesEx('/bitrix/images/' . $this->MODULE_ID);
        DeleteDirFiles($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install/themes/.default/', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/themes/.default');
        DeleteDirFilesEx('/bitrix/themes/.default/icons/' . $this->MODULE_ID);
        DeleteDirFilesEx('/bitrix/themes/.default/' . $this->MODULE_ID . '/');
        return true;
    }
}