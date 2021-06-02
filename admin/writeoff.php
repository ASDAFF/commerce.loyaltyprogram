<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
use Bitrix\Main\Application,
    Bitrix\Main\Page\Asset,
	Bitrix\Main\Localization\Loc;
	Loc::loadMessages(__FILE__);

$module_id ='commerce.loyaltyprogram';

\Bitrix\Main\Loader::includeModule($module_id);

$context = Application::getInstance()->getContext();
$request = $context->getRequest();

$writeoff=new \Commerce\Loyaltyprogram\Writeoff;

if(empty($request['id'])){
    $writeoff->initTableList();
}else{
    $writeoff->getOrder($request['id']);
    if($request->isPost() && ($request['save'] || $request['apply']) && check_bitrix_sessid()){
      $writeoff->setOrder($request);
      $writeoff->getOrder($request['id']);
    }
}
$APPLICATION->SetTitle(Loc::getMessage("commerce.loyaltyprogram_WO_TITLE"));

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
Asset::getInstance()->addJs('/bitrix/js/'.$module_id.'/script.js');

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/".$module_id."/lib/informer.php");
Commerce\Informer::createInfo();

if(empty($request['id'])){
    $writeoff->initFilter();
    $writeoff->getTableList();
}else{
    $aTabs = [
		["DIV" => "sw24_wo_edit".$currentId, "TAB" => Loc::getMessage("commerce.loyaltyprogram_WO_TITLE_SINGLE"), "TITLE" => Loc::getMessage("commerce.loyaltyprogram_WO_TITLE_SINGLE")],
		["DIV" => "sw24_wo_history".$currentId, "TAB" => Loc::getMessage("commerce.loyaltyprogram_WO_TITLE_HISTORY"), "TITLE" => Loc::getMessage("commerce.loyaltyprogram_WO_TITLE_HISTORY")]
    ];
    $tabControl = new CAdminTabControl("tabControl_wo", $aTabs);
    $tabControl->Begin();?>
    <form class="order_edit_block" name="order_edit_block" method="post" action="<?echo $APPLICATION->GetCurPage()?>?lang=<?=LANGUAGE_ID?>">
    <input type="hidden" name="id" value="<?=$request['id']?>" />
    <?$tabControl->BeginNextTab();
    $writeoff->getEditArea();
    $tabControl->BeginNextTab();
    $writeoff->getLog();
    if($writeoff->isEdit()){
      $tabControl->Buttons([
          "back_url" => $APPLICATION->GetCurPage().'?lang='.LANGUAGE_ID
      ]);
    }
    echo bitrix_sessid_post();?>
    </form>
    <?$tabControl->End();
}

?>
<style>
    .status_write_off.request{color:#0abde3;}
    .status_write_off.reject{color:#c0392b;}
    .status_write_off.execute{color:#27ae60;}
</style>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");?>