<?
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_before.php');
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);
$moduleID = "commerce.loyaltyprogram";
		require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/classes/general/update_client_partner.php');
		__IncludeLang(dirname(__FILE__).'/lang/'.LANGUAGE_ID.'/index.php');

		if(!function_exists('_vail_')){
			function _vail_($count, $arStr, $bStrOnly = false) {
				$ost10 = $count % 10;
				$ost100 = $count % 100;
				if(!$count || !$ost10 || ($ost100 > 10 && $ost100 < 20))
					return (!$bStrOnly ? intval($count).' ' : '').$arStr[2];
				if($ost10 == 1)
					return (!$bStrOnly ? intval($count).' ' : '').$arStr[0];
				if($ost10 > 1 && $ost10 < 5)
					return (!$bStrOnly ? intval($count).' ' : '').$arStr[1];
				return (!$bStrOnly ? intval($count).' ' : '').$arStr[2];
			}
		}

		$arModuleInfo = array();
		$arRequestedModules = array($moduleID);
		$updateModule=false;
		$arUpdateList = CUpdateClientPartner::GetUpdatesList($errorMessage, LANG, 'Y', $arRequestedModules, array('fullmoduleinfo' => 'Y'));
		if($arUpdateList && isset($arUpdateList['MODULE'])){
			foreach($arUpdateList['MODULE'] as $arModule){
				if($arModule['@']['ID'] === $moduleID){
					$arModuleInfo = $arModule['@'];
					if(!empty($arModule['#']['VERSION'])){
						$updateModule=$nextModule['#']['VERSION'][count($nextModule['#']['VERSION'])-1]['@']['ID'];
					}
					break;
				}
			}
		}
		?>
		<?if($arModuleInfo):?>
			<?
			if($dateSupportTo = strtotime($arModuleInfo['DATE_TO'])){
				$dateSupportTo += 86399;
				$bSupportActive = $dateSupportTo >= time();
				$bSupportLess14 = $bSupportActive && ($dateSupportTo - 1209599 < time());
				$bSupportExpired = $dateSupportTo < time();
			}
			?>
			<?endif;?>
		
		
		
		<?
$arRequestedModules = CModule::CreateModuleObject($moduleID);
?>
<div class="headerInfoBlock">
	
	<?if($bSupportLess14 || !$bSupportActive):?>
		<?if($bSupportActive):?>
			<?$cnt = floor(($dateSupportTo - time()) / 86400)?>
			<div class="dateBlock"><?=GetMessage('GD_SW24_RS_EXPIRED_SOON', array('#DAYS_STR#' => ($cnt ? GetMessage('GD_SW24_RS_THROUGH')._vail_($cnt, array(GetMessage('GD_SW24_RS_DAYS0'), GetMessage('GD_SW24_RS_DAYS1'), GetMessage('GD_SW24_RS_DAYS2'))) : GetMessage('GD_SW24_RS_DAYS0_TODAY'))))?>
		<?else:?>
			<div class="dateBlock bad"><?=GetMessage('GD_SW24_RS_EXPIRED')?>
		<?endif;?>
			<a class="licenseNew" href="/bitrix/admin/partner_modules.php"><?=GetMessage('GD_SW24_RS_BUY')?></a></div>
	<?else:?>
		<div class="dateBlock"><?=GetMessage('GD_SW24_RS_DATE_SUPPORT_TO', array('#DATE#' => date('d.m.Y', $dateSupportTo)))?></div>
	<?endif;?>
		
	<div class="versionBlock"><?=GetMessage('GD_SW24_RS_CURRENT_VERSION')?> <span><?=$arRequestedModules->MODULE_VERSION?></span> 
	<?
		if($updateModule!==false){?>
			<a class="versionNew" href="/bitrix/admin/update_system_partner.php?tabControl_active_tab=tab2&addmodule=<?=$moduleID?>"><?=GetMessage('GD_SW24_RS_UPDATENOW')?></a>
		<?}
	?>
	</div>
	
	<div class="buttonsBlock">
		<a class="infoButton review" href="https://marketplace.1c-bitrix.ru/solutions/<?=$moduleID?>/#tab-rating-link" target="_blank"><?=GetMessage('GD_SW24_RS_LEAVE_FEEDBACK')?></a>
		<a class="infoButton question" href="https://commerce.bitrix24.ru/online/go" target="_blank"><?=GetMessage('GD_SW24_RS_ASK_QUESTION')?></a>
	</div>
</div>