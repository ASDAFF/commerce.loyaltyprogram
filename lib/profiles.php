<?
namespace Commerce\Loyaltyprogram;
use \Bitrix\Main\Localization\Loc;
Loc::loadMessages(__DIR__ .'/lang.php');
/**
* profiles list
*/
class Profiles{

	private $listProfiles;

	function __construct (){
		$this->settings=Settings::getInstance();
		$this->listProfiles['Registration']=Loc::getMessage("commerce.loyaltyprogram_PROFILE_REGISTER");
		$this->listProfiles['Birthday']=Loc::getMessage("commerce.loyaltyprogram_PROFILE_BIRTHDAY");
		$this->listProfiles['Ordering']=Loc::getMessage("commerce.loyaltyprogram_PROFILE_BONUSADD");
		$this->listProfiles['Profilecompleted']=Loc::getMessage("commerce.loyaltyprogram_PROFILE_PROFILECOMPLETED");
		if(\Bitrix\Main\Loader::includeModule('sender')){
			$this->listProfiles['Subscribe']=Loc::getMessage("commerce.loyaltyprogram_PROFILE_SUBSCRIBE");
		}
		if(\Bitrix\Main\Loader::includeModule('blog') || \Bitrix\Main\Loader::includeModule('forum')){
			$this->listProfiles['Reviews']=Loc::getMessage("commerce.loyaltyprogram_PROFILE_REVIEWS");
		}
        $this->listProfiles['Copyrighter']=Loc::getMessage("commerce.loyaltyprogram_PROFILE_COPYRIGHTER");
		$this->listProfiles['Turnover']=Loc::getMessage("commerce.loyaltyprogram_PROFILE_TURNOVER");
		$this->listProfiles['TurnoverRef']=Loc::getMessage("commerce.loyaltyprogram_PROFILE_TURNOVER_REF");
		$this->listProfiles['Outersource']=Loc::getMessage("commerce.loyaltyprogram_PROFILE_OUTERSOURCE");
		
		$this->listProfiles['separator']='separator';
		
		$this->listProfiles['Orderpay']=Loc::getMessage("commerce.loyaltyprogram_PROFILE_BONUSPAY");
		$this->listProfiles['Writeoff']=Loc::getMessage("commerce.loyaltyprogram_PROFILE_WRITEOFF");
	}
	
	public function getListProfiles(){
		return $this->listProfiles;
	}


    /**
     * get profile type list without service keys
     * @return array
     */
    public function getListProfilesClear(){
	    $profiles=$this->listProfiles;
	    unset($profiles['separator']);
        return $profiles;
    }
	
	public function CopyProfile($id){
		global $DB;
		$res = $DB->Query('select sort,active,name,type,site,settings from '.$this->settings->getTableProfilesList().' where id='.$id);
		$tmpFields=array();
		if($r=$res->Fetch()){
			foreach($r as $code=>$value){
				if($code=='active'){
					$tmpFields[$code]="'N'";
				}else{
					$tmpFields[$code]="'".$DB->ForSql($value)."'";
				}
				if($code=='settings'){
					$tmpSetting=unserialize($value);
					if(!empty($tmpSetting['condition']['children'])){
						foreach($tmpSetting['condition']['children'] as &$nextChildren){
							//$nextChildren["values"]["number_action"]=(string) Tools::getLastAction();
							$nextChildren["values"]["number_action"]='';
						}
					}
					$tmpFields['settings']="'".serialize($tmpSetting)."'";
				}
			}
			$res=$DB->Insert($this->settings->getTableProfilesList(),$tmpFields,$err_mess.__LINE__);
			return $res;
		}else
			return false;
	}
	
	public function initTableList(){
		global $DB,$by,$order,$FIELDS,$arID;
		$oSort = new \CAdminSorting($this->settings->getTableProfilesList(), "id", "desc");
		$this->lAdmin = new \CAdminList($this->settings->getTableProfilesList(), $oSort);
		
		global $APPLICATION;
		$rights=$APPLICATION->GetGroupRight($this->settings->getModuleId());
		
		if($this->lAdmin->EditAction() && $rights>"E"){
			foreach($FIELDS as $ID=>$arFields){
				$active=($arFields['active']=='Y')?'Y':'N';
				$DB->Update($this->settings->getTableProfilesList(), [
					'sort'=>(int) $arFields['sort'],
					'active'=>'"'.$active.'"',
					'name'=>"'".$DB->ForSql($arFields['name'])."'"
				], "WHERE ID='".$ID."'", $err_mess.__LINE__);
			}
		}
		
		if($this->lAdmin->GroupAction() && $rights>'E'){
			$arID = is_array($_REQUEST['ID'])?$_REQUEST['ID']:[$_REQUEST['ID']];
			/* do nothing */
	
			if($_REQUEST['action_target']=='selected'){
				$arID=[];
				$rsData = $DB->Query('SELECT * FROM '.$this->settings->getTableProfilesList().';', false, $err_mess.__LINE__);
				while($arRes = $rsData->Fetch()){
					$arID[] = $arRes['id'];
				}
			}
			$tmpProfile=new \Commerce\Loyaltyprogram\Profiles\Profile;
			foreach($arID as $ID){
				if(strlen($ID)<=0)
					continue;
				$ID = IntVal($ID);
				switch($_REQUEST['action']){
					case "delete":
						//$DB->Query('DELETE FROM '.$this->settings->getTableProfilesList().' WHERE id='.$ID.';', false, $err_mess.__LINE__);
						$tmpProfile->deleteProfile($ID);
						break;
					case "activate":
					case "deactivate":
						$cData = $DB->Query('SELECT * FROM '.$this->settings->getTableProfilesList().' WHERE id='.$ID.';', false, $err_mess.__LINE__);
						if($arFields = $cData->Fetch()){
							$arFields["active"]=($_REQUEST['action']=="activate"?"Y":"N");	
							$DB->Query('UPDATE '.$this->settings->getTableProfilesList().' SET active="'.$arFields["active"].'" WHERE id='.$ID.';', false, $err_mess.__LINE__);
						}
						break;
				}
			}
		}

		$rsData = $DB->Query("select
			id,
			sort,
			active,
			name,
			type,
			site,
			date_setting as unixdata,
			".$DB->DateToCharFunction("date_setting")." date_setting
		from ".$this->settings->getTableProfilesList()." order by ".$by." ".$order);
		$rsData->NavStart(\CAdminResult::GetNavSize());
		$rsData = new \CAdminResult($rsData, $this->settings->getTableProfilesList());
		$this->lAdmin->NavText($rsData->GetNavPrint(Loc::getMessage("commerce.loyaltyprogram_TABLELIST_PAGINATOR")));
		$this->lAdmin->AddHeaders(array(
			array(
				"id" => "id",
				"content" => "ID",
				"sort" => "id",
				"default" => true
			),
			array(
				"id" => "sort",
				"content" => Loc::getMessage("commerce.loyaltyprogram_TABLELIST_SORT"),
				"sort" => "sort",
				"default" => true
			),
			array(
				"id" => "active",
				"content" => Loc::getMessage("commerce.loyaltyprogram_TABLELIST_ACTIVE"),
				"sort" => "active",
				"default" => true
			  ),
			array(
				"id" => "name",
				"content" => Loc::getMessage("commerce.loyaltyprogram_TABLELIST_NAME"),
				"sort" => "name",
				"default" => true
			),
			array(
				"id" => "type",
				"content" => Loc::getMessage("commerce.loyaltyprogram_TABLELIST_TYPE"),
				"sort" => "type",
				"default" => false
			),
			array(
				"id" => "date_setting",
				"content" => Loc::getMessage("commerce.loyaltyprogram_TABLELIST_DATE_SETTING"),
				"sort" => "unixdata",
				"default" => true
			)
		));

		while($arRes = $rsData->Fetch()){
			$row = $this->lAdmin->AddRow($arRes['id'], $arRes);
			$row->AddViewField("id", '<a href="./commerce_loyaltyprogram_profiles.php?lang='.SITE_ID.'&id='.$arRes['id'].'">'.$arRes['id'].'</a>');
			$tmpReady=($arRes['active']=='Y')?Loc::getMessage("commerce.loyaltyprogram_Y"):Loc::getMessage("commerce.loyaltyprogram_N");
			$row->AddViewField("active", $tmpReady);
			$row->AddCheckField("active");
			$row->AddInputField("sort", array("size"=>20));
			$row->AddInputField("name", array("size"=>20));
			
			$row->AddViewField("type", $this->listProfiles[$arRes['type']]);
			
			
			$arActions = Array();
			$arActions[] = [
				"ICON"=>"edit",
				"DEFAULT"=>true,
				"TEXT"=>Loc::getMessage("commerce.loyaltyprogram_TABLE_EDIT"),
				"ACTION"=>$this->lAdmin->ActionRedirect("./commerce_loyaltyprogram_profiles.php?id=".$arRes['id'])
			];
			$arActions[]=[
				"ICON"=>"copy",
				"TEXT"=>Loc::getMessage("commerce.loyaltyprogram_TABLE_COPY"),
				"ACTION"=>"if(confirm('".Loc::getMessage('commerce.loyaltyprogram_TABLE_COPY_CONFIRM')."')) ".$this->lAdmin->ActionRedirect("commerce_loyaltyprogram_profiles.php?action_button=copy&id=".$arRes['id'])
			];
			$arActions[] = [
				"ICON"=>"delete",
				"TEXT"=>Loc::getMessage("commerce.loyaltyprogram_TABLE_DELETE"),
				"ACTION"=>"if(confirm('".Loc::getMessage('commerce.loyaltyprogram_TABLE_DELETE_CONFIRM')."')) ".$this->lAdmin->ActionDoGroup($arRes['id'], "delete")
			];

			 if($rights > "E"){
				$row->AddActions($arActions);
			 }
		}

		$this->lAdmin->AddFooter(
		  array(
			array("title"=>Loc::getMessage("MAIN_ADMIN_LIST_SELECTED"), "value"=>$rsData->SelectedRowsCount()), // ���-�� ���������
			array("counter"=>true, "title"=>Loc::getMessage("MAIN_ADMIN_LIST_CHECKED"), "value"=>"0"), // ������� ��������� ���������
		  )
		);

		 if($rights > "E"){
			$this->lAdmin->AddGroupActionTable(Array(
				"edit"=>Loc::getMessage("commerce.loyaltyprogram_TABLE_EDIT"),
				"delete"=>Loc::getMessage("commerce.loyaltyprogram_TABLE_DELETE"),
				"activate"=>Loc::getMessage("commerce.loyaltyprogram_TABLE_ACTIVATE"),
				"deactivate"=>Loc::getMessage("commerce.loyaltyprogram_TABLE_DEACTIVATE")
			));
		 }
		$profilesMenu=[];
		foreach($this->listProfiles as $keyProf=>$nameProf){
			if($keyProf=='separator'){
				$profilesMenu[] = ["SEPARATOR"=>true];
			}else{
				$profilesMenu[]=[
					"TEXT"=>$nameProf,
					"ACTION"=>$this->lAdmin->ActionRedirect("./commerce_loyaltyprogram_profiles.php?id=new&type=".$keyProf)
				];
			}
		}
		
		$aContext = array(
			array(
				"TEXT"=>Loc::getMessage("commerce.loyaltyprogram_ADD_PROFILE"),
				//"LINK"=>"javascript:parse('all');",
				"TITLE"=>Loc::getMessage("commerce.loyaltyprogram_ADD_PROFILE_TITLE"),
				"MENU"=>$profilesMenu,
			)
		);


		 if($rights > "E"){
			$this->lAdmin->AddAdminContextMenu($aContext);
		 }

		$this->lAdmin->CheckListMode();
	}
	
	public function getTableList(){
		 $this->lAdmin->DisplayList();
	}

}

?>