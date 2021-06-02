<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
use \Bitrix\Main\Application,
	Bitrix\Main\Page\Asset,
	Bitrix\Main\Request,
	Bitrix\Main\Localization\Loc,
	Bitrix\Sale\Internals;
	Loc::loadMessages(__FILE__);
	
$module_id ='commerce.loyaltyprogram';
\Bitrix\Main\Loader::includeModule($module_id);


$queue=new \Commerce\Loyaltyprogram\Queue;
$queue->initTableList();
$APPLICATION->SetTitle(Loc::getMessage("commerce.loyaltyprogram_ADMIN_TITLE"));
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/".$module_id."/lib/informer.php");
Commerce\Informer::createInfo();

CAdminMessage::ShowMessage([
	"TYPE"=>"ERROR",
	"MESSAGE"=>Loc::getMessage("commerce.loyaltyprogram_QUEUE_ATTENTION"),
	'HTML'=>true
]);
$queue->getTableList();
?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");?>