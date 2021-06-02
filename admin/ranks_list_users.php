<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");

use \Bitrix\Main\Application,
    \Bitrix\Main\Page\Asset,
    \Bitrix\Main\Request,
    \Bitrix\Main\Localization\Loc,
    \Bitrix\Sale\Internals;

Loc::loadMessages(__FILE__);

$module_id = 'commerce.loyaltyprogram';
\Bitrix\Main\Loader::includeModule($module_id);

$context = Application::getInstance()->getContext();
$request = $context->getRequest();
$ranks = new \Commerce\Loyaltyprogram\RanksUsers;

if (!empty($_REQUEST['id']) && ($_REQUEST['id'] == 'new' || (int)$_REQUEST['id'] > 0)) {
    if ($_REQUEST['id'] == 'new') {
        $APPLICATION->SetTitle(Loc::getMessage("commerce.loyaltyprogram_TITLE_RANK_NEW"));
    } else {
        $APPLICATION->SetTitle(Loc::getMessage("commerce.loyaltyprogram_TITLE_RANK_EDIT"));
    }
} else {
    $APPLICATION->SetTitle(Loc::getMessage("commerce.loyaltyprogram_ADMIN_TITLE"));
}

if ((!empty($request['apply']) || !empty($request['save'])) && check_bitrix_sessid()) {
    $RankId = $ranks->getRankUser($request['user_id']);
    $res = $ranks->saveSetting($request);
    if ($RankId == 0 || $request['id'] != 'new') {
        if ($res->isSuccess()) {
            $id = $res->getId();
            if (!empty($request['save'])) {
                LocalRedirect($APPLICATION->GetCurPage() . '?lang=' . LANGUAGE_ID);
            } elseif (!empty($request['id']) && $request['id'] == 'new') {
                LocalRedirect($APPLICATION->GetCurPage() . '?id=' . $id . '&add=success&lang=' . LANGUAGE_ID);
            }
            $info = new CAdminMessage([
                "TYPE" => "OK",
                "MESSAGE" => Loc::getMessage("commerce.ranks_SAVE_SETTING_SUCCESS")
            ]);
        } else {
            $info = new CAdminMessage([
                "TYPE" => "ERROR",
                "MESSAGE" => implode(', ', $res->getErrorMessages()),
                "HTML" => true
            ]);
        }
    } else {
        $info = new CAdminMessage([
            "TYPE" => "ERROR",
            "MESSAGE" => Loc::getMessage("commerce.ranks_list_SAVE_SETTING_ERROR", ["#LINK#" => $APPLICATION->GetCurPage() . '?lang=' . LANGUAGE_ID . '&id=' . $id]),
            "HTML" => true
        ]);
    }
} elseif (!empty($request['ajax']) && $request['ajax'] == 'Y' && !empty($request['command'])) {
    if ($request['command'] == 'update_ranks') {
        \Commerce\Loyaltyprogram\Ranks::setRanks();
        echo \Bitrix\Main\Web\Json::encode(['status' => 'success', 'info'=>Loc::getMessage("commerce.ranks_update_RANKSUCCESS")]);
    }
    die();
}

Asset::getInstance()->addJs('/bitrix/js/' . $module_id . '/script.js');
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php");
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/" . $module_id . "/lib/informer.php");
Commerce\Informer::createInfo();

if (!empty($request['add']) && $request['add'] == 'success' && !empty($request['id'])) {
    $info = new CAdminMessage([
        "TYPE" => "OK",
        "MESSAGE" => Loc::getMessage("commerce.ranks_ADD_RANK_SUCCESS")
    ]);
}

if (!empty($info)) {
    echo $info->Show();
}

if (!empty($_REQUEST['id']) && ($_REQUEST['id'] == 'new' || (int)$_REQUEST['id'] > 0)) {
    ?>
    <?
    $ranksData = new \Commerce\Loyaltyprogram\Ranks;
    $ranksName = $ranksData->getRanks();
    ?>
    <form name="bidding_setting" method="post"
          action="<? echo $APPLICATION->GetCurPage() ?>?id=<?= $_REQUEST['id'] ?>&lang=<?= LANGUAGE_ID ?>">
        <?
        if ($_REQUEST['id'] == 'new') {
            $aTabs = [
                ["DIV" => "sw24_loyal_rank_edit", "TAB" => Loc::getMessage("commerce.ranks_list_new_TITLE"), "TITLE" => Loc::getMessage("commerce.ranks_list_new_TAB_RANK_TITLE")]
            ];
        } else {
            $aTabs = [
                ["DIV" => "sw24_loyal_rank_edit", "TAB" => Loc::getMessage("commerce.ranks_list_edit_TITLE"), "TITLE" => Loc::getMessage("commerce.ranks_list_edit_TAB_RANK_TITLE")]
            ];
        }
        $tabControl = new CAdminTabControl("tabControl_import", $aTabs);
        $tabControl->Begin();
        $tabControl->BeginNextTab();
        $data = $ranks->getData($request['id']);
        $rsUser = \CUser::GetByID($data['user_id']);
        $arUser = $rsUser->Fetch();
        ?>
        <tr>
            <td style="text-align: right" width="50%"><?= Loc::getMessage("commerce.ranks_edit_ACTIVE") ?></td>
            <td style="text-align: left" width="50%"><input type="checkbox" name="active" value="Y"<?= $data['active'] == 'Y' ? ' checked' : ''; ?>>
            </td>
        </tr>
        <tr>
            <td style="text-align: right" width="50%"><?= Loc::getMessage("commerce.ranks_list_edit_USER") ?></td>
            <? if ($_REQUEST['id'] == 'new') {
                ?>
                <td>
                    <input type="text" name="user_id" id="user_id_input" value="<?= $cUserId ?>" size="3" maxlength=""
                           class="typeinput">
                    <iframe style="width:0px; height:0px; border: 0px" src="javascript:void(0)"
                            name="hiddenframeuser_id_input" id="hiddenframeuser_id_input"></iframe>
                    <input class="tablebodybutton"
                           type="button"
                           name="button_user"
                           id="button_user"
                           onclick="window.open('/bitrix/admin/user_search.php?lang=<?= LANGUAGE_ID; ?>&amp;FN=bidding_setting&amp;FC=user_id_input', '', 'scrollbars=yes,resizable=yes,width=760,height=500,top='+Math.floor((screen.height - 560)/2-14)+',left='+Math.floor((screen.width - 760)/2-5));"
                           value="...">
                    <span id="div_user_id_input"></span>
                    <script>
                        var cUseId = '';

                        function getUserId() {
                            var nameUser;
                            nameUser = document.getElementById("div_user_id_input");
                            if (!!nameUser) {
                                cUseId = document.bidding_setting['user_id_input'].value;
                                if (document.bidding_setting && document.bidding_setting['user_id_input'] && typeof cUseId != 'undefined') {
                                    document.getElementById("hiddenframeuser_id_input").src = '/bitrix/admin/get_user.php?ID=' + cUseId + '&strName=user_id_input&lang=<?=LANGUAGE_ID;?>&admin_section=Y';
                                }
                            }
                            setTimeout(function () {
                                getUserId()
                            }, 1000);
                        }

                        getUserId();
                    </script>
                </td>
            <? } else {
                ?>
                <td style="text-align: left" width="50%"><input type="hidden" name="user_id"
                                                                value="<?= $data['user_id'] ?>"><a target="_blank"
                                                                                                   href="/bitrix/admin/user_edit.php?lang=<?= LANGUAGE_ID ?>&ID=<?= $arUser["ID"] ?>">[<?= $arUser["ID"] ?>
                        ]</a><?= $arUser["NAME"] ?> <?= $arUser["LAST_NAME"] ?></td>
            <? } ?>
        </tr>
        <tr>
            <td style="text-align: right" width="50%"><?= Loc::getMessage("commerce.ranks_edit_NAME") ?></td>
            <td style="text-align: left" width="50%">
                <select name="rank_id">
                    <? foreach ($ranksName as $rank) {
                        $selected = $rank['id'] == $data['rank_id'] ? ' selected' : ''; ?>
                        <option value="<?= $rank['id'] ?>"<?= $selected ?>><?= $rank['name'] ?></option>
                    <? } ?>
                </select>
            </td>
        </tr>
        <tr>
            <td style="text-align: right" width="50%"><?= Loc::getMessage("commerce.ranks_lock_USER") ?></td>
            <td>
                <?$checked=(!empty($data['params']['lock_user']) && $data['params']['lock_user']=='Y')?' checked':'';?>
                <input type="checkbox" name="lock_user" value="Y" <?=$checked?>/>
            </td>
        </tr>
        <? $tabControl->Buttons(["back_url" => $APPLICATION->GetCurPage() . '?lang=' . LANGUAGE_ID]);
        $tabControl->End();
        echo bitrix_sessid_post(); ?>
    </form>
<? } else {
    $ranks->show();
    ?>
    <script>
        BX.ready(function () {
            BX.bindDelegate(BX('sw24_rank_list_table'), 'keypress', {attribute: {name: 'coeff'}}, function () {
                if (!event.key.match(/[0-9]/) && event.key != '.') {
                    event.preventDefault();
                    //return false;
                }
                ;
            });
            BX.bindDelegate(BX('sw24_rank_list_table'), 'keypress', {attribute: {name: 'value'}}, function () {
                if (!event.key.match(/[0-9]/) && event.key != '.') {
                    event.preventDefault();
                    //return false;
                }
                ;
            });
            BX.bindDelegate(BX('sw24_rank_list_table'), 'keypress', {attribute: {name: 'sort'}}, function () {
                if (!event.key.match(/[0-9]/)) {
                    event.preventDefault();
                    //return false;
                }
                ;
            });
        })

        function update_ranks() {
            BX.ajax({
                method: 'POST',
                dataType: 'json',
                url: location.href,
                data: {ajax: 'Y', command: 'update_ranks'},
                onsuccess: function (data) {
                    //console.log(data);
                    alert(data.info);
                },
                onfailure: function (data) {
                    console.log('error');
                    console.log(data);
                }
            });
        }
    </script>
<? } ?>
<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php"); ?>