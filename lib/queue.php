<?
namespace Commerce\Loyaltyprogram;
use \Bitrix\Main\Localization\Loc;
Loc::loadMessages(__DIR__ .'/lang.php');
/**
*    profiles list
*/
class Queue{

	function __construct (){
		$this->settings=Settings::getInstance();
		$profiles=new Profiles;
		$this->Profiles=$profiles->getListProfiles();
	}
	
	private static function getNewComment($row, $newComment){
		$comments=(empty($row['comments']))?[]:explode('###', $row['comments']);
		$comments[]=$newComment;
		return implode('###', $comments);
	}

	public static function deleteFromQueue($id){
        $data = Entity\BonusesTable::getList([
            'filter'=>['id'=>$id, 'status'=>'inactive']
        ]);
        if($arData = $data->fetch()) {
            Entity\BonusesTable::update($id, [
                'status'=>'overdue',
                'comments'=>self::getNewComment($arData, Loc::getMessage("commerce.loyaltyprogram_TABLE_BONUS_DELETE_MANUAL").' '.date('d.m.Y H:i'))
            ]);
            return true;
        }
        return false;
    }
	
	private function editRow($id){
		if(!empty($_REQUEST['FIELDS_OLD'][$id])){
			$updateArr=[];
			$oldFields=$_REQUEST['FIELDS_OLD'][$id];
			$newFields=$_REQUEST['FIELDS'][$id];
			if($oldFields['bonus']!=$newFields['bonus'] && (float) $newFields['bonus']>0){
				$updateArr['bonus']=(float) $newFields['bonus'];
			}
			if($oldFields['date_add']!=$newFields['date_add']){
				$updateArr['date_add']=$newFields['date_add'];
			}
			if($oldFields['date_remove']!=$newFields['date_remove']){
				$updateArr['date_remove']=$newFields['date_remove'];
			}
			if(count($updateArr)>0){
				global $DB;
				$DBUpdateArr=[];
				$select='select * from '.$this->settings->getTableBonusList().' where id='.$id.';';
				$rsData = $DB->Query($select);
				if($arRes = $rsData->Fetch()){
					foreach($updateArr as $key=>$val){
						switch($key){
							case "bonus":
								$DBUpdateArr['bonus']=$updateArr['bonus'];
								$DBUpdateArr['bonus_start']=$updateArr['bonus'];
								if(filter_var((float) $updateArr['bonus'], FILTER_VALIDATE_INT)===false){
									$strBonus=round($updateArr['bonus'], 2);
								}else{
									$strBonus=(int)$updateArr['bonus'];
								}
								if(!empty($arRes['email'])){
									$tmpMail=unserialize($arRes['email']);
									if(!empty($tmpMail['C_FIELDS']['BONUS'])){
										$tmpMail['C_FIELDS']['BONUS']=$strBonus;
										$DBUpdateArr['email']="'".serialize($tmpMail)."'";
									}
								}
								if(!empty($arRes['sms'])){
									$tmpSMS=unserialize($arRes['email']);
									if(!empty($tmpMail['C_FIELDS']['BONUS'])){
										$tmpSMS['C_FIELDS']['BONUS']=$strBonus;
										$DBUpdateArr['sms']="'".serialize($tmpSMS)."'";
									}
								}
							break;
							case "date_add":
								if(empty($updateArr['date_add'])){
									$DBUpdateArr['date_add']="''";
								}else{
									$DBUpdateArr['date_add']='FROM_UNIXTIME('.\MakeTimeStamp($updateArr['date_add']).')';
								}
							break;
							case "date_remove":
								if(empty($updateArr['date_remove'])){
									$DBUpdateArr['date_remove']="''";
								}else{
									$DBUpdateArr['date_remove']='FROM_UNIXTIME('.\MakeTimeStamp($updateArr['date_remove']).')';
								}
							break;
						}
					}
					$DBUpdateArr['comments']='"'.$DB->ForSql(self::getNewComment($arRes, Loc::getMessage("commerce.loyaltyprogram_TABLE_BONUS_UPDATE_MANUAL").' '.date('d.m.Y H:i'))).'"';
					$DB->Update($this->settings->getTableBonusList(), $DBUpdateArr, "where id='".$arRes['id']."'", $err_mess.__LINE__);
				}
			}
		}
	}
	
	public function initTableList(){
		global $DB,$by,$order,$FIELDS,$arID,$APPLICATION;
		$oSort = new \CAdminSorting($this->settings->getTableBonusList(), "ID", "desc");
		$this->lAdmin = new \CAdminList($this->settings->getTableBonusList(), $oSort);

		$rights=$APPLICATION->GetGroupRight($this->settings->getModuleId());
		$select='select 
			'.$this->settings->getTableBonusList().'.ID,
			'.$this->settings->getTableBonusList().'.bonus,
			'.$this->settings->getTableBonusList().'.user_id,
			'.$this->settings->getTableBonusList().'.user_bonus,
			'.$this->settings->getTableBonusList().'.order_id,
			'.$this->settings->getTableBonusList().'.profile_type,
			'.$this->settings->getTableBonusList().'.profile_id,
			'.$DB->DateToCharFunction($this->settings->getTableBonusList().'.date_add').' as date_add,
			'.$DB->DateToCharFunction($this->settings->getTableBonusList().'.date_remove').' as date_remove,
			b_user.NAME as user_name,
			b_user.LAST_NAME as user_last_name,
			b_user.LOGIN as user_login
		from '.$this->settings->getTableBonusList().' 
		left join b_user on ('.$this->settings->getTableBonusList().'.user_id=b_user.ID)
		where status="inactive" order by '.$by.' '.$order.';';
		//edit
		if($rights>"E"){
			$ids=[];
			$action='';
			if($this->lAdmin->GroupAction()){
				if($_REQUEST['action_target']=='selected'){
					$cTmpData = $DB->Query($select);
					while($arRes = $cTmpData->Fetch()){
						$ids[] = $arRes['ID'];
					}
				}else{
					if(is_array($_REQUEST['ID'])){
						$ids=$_REQUEST['ID'];
					}elseif(!empty($_REQUEST['ID'])){
						$ids=[$_REQUEST['ID']];
					}
				}
				if($_REQUEST['action']=='delete'){
					$action=$_REQUEST['action'];
				}
			}
			
			if($this->lAdmin->EditAction()) {
				$action='edit';
				$ids=array_keys($FIELDS);
			}
			
			
			if(count($ids)>0){
				foreach($ids as $nextId){
					switch($action){
						case "delete":
							self::deleteFromQueue($nextId);
							break;
						case "edit":
							$this->editRow($nextId);
							break;
					}
				}
			}
			
		}

		$rsData = $DB->Query($select);
		$rsData->NavStart(\CAdminResult::GetNavSize());
		$rsData = new \CAdminResult($rsData, $this->settings->getTableBonusList());
		$this->lAdmin->NavText($rsData->GetNavPrint(Loc::getMessage("commerce.loyaltyprogram_TABLELIST_PAGINATOR")));
		$this->lAdmin->AddHeaders(array(
			["id" => "ID","content" => "ID","sort" => "ID","default" => true],
			["id" => "bonus","content" => Loc::getMessage("commerce.loyaltyprogram_QUEUE_HEADER_bonus"), "sort" => "bonus","default" => true],
			["id" => "user_id","content" => Loc::getMessage("commerce.loyaltyprogram_QUEUE_HEADER_user_id"),"sort" => "user_id","default" => true],
			["id" => "user_bonus","content" => Loc::getMessage("commerce.loyaltyprogram_QUEUE_HEADER_user_bonus"),"sort" => "user_bonus","default" => true],
			["id" => "order_id","content" => Loc::getMessage("commerce.loyaltyprogram_QUEUE_HEADER_order_id"),"sort" => "order_id","default" => true],
			["id" => "profile_type","content" => Loc::getMessage("commerce.loyaltyprogram_QUEUE_HEADER_profile_type"),"sort" => "profile_type","default" => true],
			["id" => "profile_id","content" => Loc::getMessage("commerce.loyaltyprogram_QUEUE_HEADER_profile_id"),"sort" => "profile_id","default" => true],
			["id" => "date_add","content" => Loc::getMessage("commerce.loyaltyprogram_QUEUE_HEADER_date_add"),"sort" => "date_add","default" => true],
			["id" => "date_remove","content" => Loc::getMessage("commerce.loyaltyprogram_QUEUE_HEADER_date_remove"),"sort" => "date_remove","default" => true]
		));
		while($arRes = $rsData->Fetch()){
			$row = $this->lAdmin->AddRow($arRes['ID'], $arRes);
			//if(!empty($this->Profiles[$arRes['profile_type']])){
				$row->AddViewField("profile_type", $this->Profiles[$arRes['profile_type']]);
			//}
			$row->AddViewField("bonus", Tools::priceFormat($arRes['bonus']));
			$row->AddCalendarField("date_add", [], true);
			$row->AddCalendarField("date_remove", [], true);
			$row->AddInputField("bonus");


            $orderId=($arRes['order_id']>0)?'<a href="/bitrix/admin/sale_order_view.php?ID='.$arRes['order_id'].'&lang='.SITE_ID.'" target="_blank">'.$arRes['order_id'].'</a>':'';
            $row->AddViewField("order_id", $orderId);
            $row->AddViewField("user_id", '<a href="/bitrix/admin/user_edit.php?lang='.SITE_ID.'&ID='.$arRes['user_id'].'" target="_blank">['.$arRes['user_id'].']</a> '.$arRes['user_login'].' ('.$arRes['user_name'].' '.$arRes['user_last_name'].')');
            $row->AddViewField("user_bonus", $arRes['user_bonus']>0?Loc::getMessage("commerce.loyaltyprogram_Y"):Loc::getMessage("commerce.loyaltyprogram_N"));

			$arActions = [];
			$arActions[] = [
				"ICON"=>"delete",
				"TEXT"=>Loc::getMessage("commerce.loyaltyprogram_TABLE_DELETE"),
				"ACTION"=>"if(confirm('".Loc::getMessage('commerce.loyaltyprogram_TABLE_DELETE_BONUSQUEUE')."')) ".$this->lAdmin->ActionDoGroup($arRes['ID'], "delete")
			];
			$arActions[] = [
				"ICON"=>"edit",
				"TEXT"=>Loc::getMessage("commerce.loyaltyprogram_TABLE_EDIT"),
				"ACTION"=>"if(confirm('".Loc::getMessage('commerce.loyaltyprogram_TABLE_EDIT_BONUSQUEUE')."')) ".$this->lAdmin->ActionDoGroup($arRes['ID'], "edit")
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
			$this->lAdmin->AddGroupActionTable([
				"delete"=>Loc::getMessage("commerce.loyaltyprogram_TABLE_DELETE"),
			]);
		 }
        $this->lAdmin->AddAdminContextMenu();

		$this->lAdmin->CheckListMode();
	}
	
	public function getTableList(){
		 $this->lAdmin->DisplayList();
	}
	
}

?>