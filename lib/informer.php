<?
namespace Commerce;
use	Bitrix\Main\Localization\Loc;
class Informer{
	
	/**
	* set CAdminNotify about new version
	*/
	const MODULE_ID='commerce.loyaltyprogram'; //module id
	
	public static function getModuleInfo(){
		$moduleId=self::MODULE_ID;
		require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/classes/general/update_client_partner.php');
		$arModuleInfo = false;
		
		$folders = array(
			"/local/modules/".$moduleId,
			"/bitrix/modules/".$moduleId,
		);
		foreach($folders as $folder)
		{
			if(file_exists($_SERVER["DOCUMENT_ROOT"].$folder))
			{
				$handle = opendir($_SERVER["DOCUMENT_ROOT"].$folder);
				if($handle){
					if($info = \CModule::CreateModuleObject($moduleId)){
						$arModuleInfo["MODULE_ID"] = $info->MODULE_ID;
						$arModuleInfo["MODULE_NAME"] = $info->MODULE_NAME;
						$arModuleInfo["MODULE_DESCRIPTION"] = $info->MODULE_DESCRIPTION;
						$arModuleInfo["MODULE_VERSION"] = $info->MODULE_VERSION;
						$arModuleInfo["MODULE_VERSION_DATE"] = $info->MODULE_VERSION_DATE;
						$arModuleInfo["MODULE_SORT"] = $info->MODULE_SORT;
						$arModuleInfo["MODULE_PARTNER"] = $info->PARTNER_NAME;
						$arModuleInfo["MODULE_PARTNER_URI"] = $info->PARTNER_URI;
						$arModuleInfo["IsInstalled"] = $info->IsInstalled();
					}
					closedir($handle);
				}
			}
		}
		self::setNotify($arModuleInfo);
		return $arModuleInfo;
	}
	
	public static function setNotify($arModuleInfo){
		if(!empty($arModuleInfo['CURRENT_VERSION'])){
			$info = Loc::getMessage("SKWB24_INFORMER_informer_updates_available").' "'.$arModuleInfo['NAME'].'" <a  href="/bitrix/admin/update_system_partner.php?tabControl_active_tab=tab2&amp;addmodule='.$arModuleInfo["MODULE_ID"].'&amp;lang='.LANGUAGE_ID.'">'.Loc::getMessage("SKWB24_INFORMER_informer_update").'</a>';
			$lastInfo=\Bitrix\Main\Config\Option::get(self::MODULE_ID, 'last_informer', '');
			if($lastInfo!=$info){
				\Bitrix\Main\Config\Option::set(self::MODULE_ID, 'last_informer', $info);
				$tag=str_replace('.','_',$arModuleInfo['ID']).'_new_version';
				\CAdminNotify::Add([
					'MESSAGE' => $info,
					'TAG' => $tag,
					'MODULE_ID' => $arModuleInfo['ID'],
					'ENABLE_CLOSE' => 'Y'
				]);
			}
		}
	}
	
	public static function getStyles(){
		return 
		'<style>
			.commerce_informer{
				margin-bottom: 20px;
				padding: 15px;
				border: 2px dashed #2980b9;
				background: rgba(41, 128, 185, 0.1);
				font-size: 15px;
				color: #34495e;
			}
			.commerce_informer p{margin:0 0 10px;}
			.commerce_informer .error{color:#eb3b5a; font-weight:bolder;}
			.commerce_informer .good{color:#20bf6b;}
			.commerce_informer .buttonsBlock{
			    display: flex;
			}
			.commerce_informer .buttonsBlock a{
			    margin-left: 10px;
				transition: .3s;
			}
			.commerce_informer .buttonsBlock a:first-of-type{
			    margin: 0;
			}
			.commerce_informer .buttonsBlock a:last-of-type{
			    margin-left: auto;
			}
			
			.commerce_informer .buttonsBlock a{
				border-radius: 3px;
				display: inline-block;
				height: 15px;
				padding: 10px 20px;
				position: relative;
				margin-left: 15px;
				text-decoration: none;
				line-height: 16px;
				color: #ffffff;
				font-size: 16px;
				font-weight: bold;
				text-shadow: 0 -1px 0 rgba(106, 109, 111, 0.3);
			}
			
			.commerce_informer .buttonsBlock a.documentation{
				border: 1px solid #2980b9 !important;
				background: #2980b9;
			}
				.commerce_informer .buttonsBlock a.documentation:hover{
					background: #3498db!important;
				}
			.commerce_informer .buttonsBlock a.review{
				border: 1px solid #27ae60 !important;
				background: #27ae60;
			}
				.commerce_informer .buttonsBlock a.review:hover{
					background: #1dd1a1
				}
			.commerce_informer .buttonsBlock a.question{
				border: 1px solid #c0392b !important;
				background: #c0392b;
			}
				.commerce_informer .buttonsBlock a.question:hover{
					background: #ff6b6b
				}
			.commerce_informer .buttonsBlock a.payment{
				border: 1px solid #0abde3;
				background: #0abde3;
			}
				.commerce_informer .buttonsBlock a.payment:hover{
					background: #48dbfb
				}
			.commerce_informer .buttonsBlock a.modules{
				border: 1px solid #2e86de;
				background: #2e86de;
			}
				.commerce_informer .buttonsBlock a.modules:hover{
					background: #54a0ff
				}
		</style>'
		;
	}
	
	public static function cacheInfo(){
		$filename = $_SERVER['DOCUMENT_ROOT'].'/upload/'.self::MODULE_ID.'_informer.txt';

        $arModuleInfo=self::getModuleInfo();
        $strBlock='<section class="commerce_informer">';
        //links
        $idArr=explode('.',$arModuleInfo['MODULE_ID']);
        $strBlock.='<div class="buttonsBlock">';
        $strBlock.='<a class="documentation" href="https://skyweb24.ru/documentation/'.$idArr[1].'/" target="_blank">'.Loc::getMessage("SKWB24_INFORMER_button_docs").'</a>';
        $strBlock.='</div>';
        $strBlock.='</section>';

        file_put_contents($filename, self::getStyles().$strBlock);

		return file_get_contents($filename);
	}
	
	public static function createInfo(){
		echo self::cacheInfo();
	}
	
}
?>