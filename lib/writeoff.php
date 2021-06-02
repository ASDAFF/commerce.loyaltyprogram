<?
namespace Commerce\Loyaltyprogram;
use \Bitrix\Main\Localization\Loc;
Loc::loadMessages(__DIR__ .'/lang.php');
\Bitrix\Main\Loader::includeModule('sale');
/**
*    write off list
*/
class Writeoff{

	function __construct (){
		$this->settings=Settings::getInstance();
        $this->moduleOptions=$this->settings->getOptions();
        $this->currency=empty($this->moduleOptions['currency'])?'RUB':$this->moduleOptions['currency'];
        $this->statuses = Entity\WriteOffTable::getStatuses();
	}
//list part
	public function initFilter(){
		global $find_status, $find_user,$APPLICATION;
		
		$oFilter = new \CAdminFilter(
			$this->settings->getTableWriteOff().'_filter',
			[
				Loc::getMessage("commerce.loyaltyprogram_WO_FILTER_USER"),
				Loc::getMessage("commerce.loyaltyprogram_WO_FILTER_STATUS")
			]
		);?>
		<form name="find_form" method="get" action="<?echo $APPLICATION->GetCurPage();?>">
		<?$oFilter->Begin();?>
		<tr>
			<td><b><?=Loc::getMessage("commerce.loyaltyprogram_WO_FILTER_USER")?>:</b></td>
			<td>
				<input type="text" min="1" step="1" size="25" name="find_user" value="<?echo htmlspecialchars($find_user)?>">
			</td>
		</tr>
		<tr>
			<td><?=Loc::getMessage("commerce.loyaltyprogram_WO_FILTER_STATUS").":"?></td>
			<td>
                <select name="find_status">
                    <option value="">...</option>
                    <?foreach($this->statuses as $key=>$val){
                        $selected=(!empty($find_status) && $find_status==$key)?' selected="selected"':'';?>
                        <option value="<?=$key?>"<?=$selected?>><?=$val?></option>
                    <?}?>
                </select>
			</td>
		</tr>
		<?
			$oFilter->Buttons(array("table_id"=>$this->settings->getTableWriteOff(),"url"=>$APPLICATION->GetCurPage(),"form"=>"find_form"));
			$oFilter->End();
		?>
		</form>
	<?}
	
	public function initTableList(){
		global $DB,$by,$order,$FilterArr, $APPLICATION;
		$oSort = new \CAdminSorting($this->settings->getTableUsersList(), "id", "desc");
		$this->lAdmin = new \CAdminList($this->settings->getTableUsersList(), $oSort);
		$by=in_array($by, ['id','bonus','status','comment','last_name'])?$by:'id';

        $rights=$APPLICATION->GetGroupRight($this->settings->getModuleId());

		//filter
		$FilterArr = [
			"find_user",
			"find_status"
		];
		$this->lAdmin->InitFilter($FilterArr);
		global $find_user,$find_status;
		$where='';
		if(!empty($find_user)){
			$find_user=urldecode($find_user);
			$where.=' and (b_user.NAME="'.$find_user.'" or b_user.LOGIN="'.$find_user.'" or b_user.EMAIL="'.$find_user.'" or b_user.LAST_NAME="'.$find_user.'" or b_user.SECOND_NAME="'.$find_user.'")';
		}
		if(!empty($find_status)){
			$where.=' and '.$this->settings->getTableWriteOff().'.status="'.$find_status.'"';
		}
		
		$lastOrder=($by=='id')?'':', id '.$order;
		
		$select='select
            '.$this->settings->getTableWriteOff().'.id as id,
            '.$this->settings->getTableWriteOff().'.bonus as bonus,
            '.$this->settings->getTableWriteOff().'.date_order as date_order,
            '.$this->settings->getTableWriteOff().'.status as status,
            '.$this->settings->getTableWriteOff().'.date_change as date_change,
            '.$this->settings->getTableWriteOff().'.comment as comment,
            '.$this->settings->getTableWriteOff().'.log as log,
            '.$this->settings->getTableWriteOff().'.user_id as user_id,
            b_user.LAST_NAME as last_name,
            b_user.NAME as name,
            b_user.SECOND_NAME as second_name,
            b_user.LOGIN as login,
            b_user.EMAIL as email,
            '.$this->settings->getTableUserRequisites().'.cart_number as cart_number
        from '.$this->settings->getTableWriteOff().'
            left join b_user on (b_user.ID='.$this->settings->getTableWriteOff().'.user_id)
            left join '.$this->settings->getTableUserRequisites().' on ('.$this->settings->getTableWriteOff().'.requisites_id='.$this->settings->getTableUserRequisites().'.id)
            where 1=1 '.$where.'
		order by '.$by.' '.$order.$lastOrder;

		$rsData = $DB->Query($select);
		$rsData->NavStart(\CAdminResult::GetNavSize());
		$rsData = new \CAdminResult($rsData, $this->settings->getTableUsersList());
		$this->lAdmin->NavText($rsData->GetNavPrint(Loc::getMessage("commerce.loyaltyprogram_TABLELIST_PAGINATOR")));
		$this->lAdmin->AddHeaders(array(
			array(
				"id" => "id",
				"content" => "ID",
				"sort" => "id",
				"default" => true
			),
			array(
				"id" => "bonus",
				"content" => Loc::getMessage("commerce.loyaltyprogram_WO_HEADER_BONUS"),
				"sort" => "bonus",
				"default" => true
			),
			array(
				"id" => "status",
				"content" => Loc::getMessage("commerce.loyaltyprogram_WO_HEADER_STATUS"),
				"sort" => "status",
				"default" => true
			),
			array(
				"id" => "comment",
				"content" => Loc::getMessage("commerce.loyaltyprogram_WO_HEADER_COMMENT"),
				"sort" => "comment",
				"default" => false
			),
			array(
				"id" => "user",
				"content" => Loc::getMessage("commerce.loyaltyprogram_WO_HEADER_USER"),
				"sort" => "last_name",
				"default" => true
			)
		));
		
		while($arRes = $rsData->Fetch()){
            $row = $this->lAdmin->AddRow($arRes['id'], $arRes);
			$row->AddViewField("id", '<a href="/bitrix/admin/commerce_loyaltyprogram_writeoff.php?id='.$arRes['id'].'&lang='.SITE_ID.'">'.$arRes['id'].'</a>');
			$row->AddViewField("user", '<a href="/bitrix/admin/user_edit.php?lang='.SITE_ID.'&ID='.$arRes['user_id'].'" target="_blank">'.$this->getFullName($arRes).' ['.$arRes['user_id'].']</a>');
			$row->AddViewField("status", '<span class="status_write_off '.$arRes['status'].'">'.$this->getNameStatus($arRes['status']).'</span>');
			$row->AddViewField("comment", $arRes['comment']);
			$row->AddViewField("bonus", Tools::roundBonus($arRes['bonus']));

            $arActions = [];
            if($arRes['status']=='request'){
                $arActions[] = [
                    "ICON" => "edit",
                    "DEFAULT" => true,
                    "TEXT" => Loc::getMessage("commerce.loyaltyprogram_TABLE_EDIT"),
                    "ACTION" => $this->lAdmin->ActionRedirect('/bitrix/admin/commerce_loyaltyprogram_writeoff.php?id=' . $arRes['id'] . '&lang=' . LANGUAGE_ID)
                ];
            }else{
                $arActions[] = [
                    "ICON" => "view",
                    "DEFAULT" => true,
                    "TEXT" => Loc::getMessage("commerce.loyaltyprogram_TABLE_VIEW"),
                    "ACTION" => $this->lAdmin->ActionRedirect('/bitrix/admin/commerce_loyaltyprogram_writeoff.php?id=' . $arRes['id'] . '&lang=' . LANGUAGE_ID)
                ];
            }

            if($rights > "E"){
                $row->AddActions($arActions);
            }

        }
        $aContext = [];
        $this->lAdmin->AddAdminContextMenu($aContext);

		$this->lAdmin->AddFooter(
		  array(
			array("title"=>Loc::getMessage("MAIN_ADMIN_LIST_SELECTED"), "value"=>$rsData->SelectedRowsCount()), // ���-�� ���������
			array("counter"=>true, "title"=>Loc::getMessage("MAIN_ADMIN_LIST_CHECKED"), "value"=>"0"), // ������� ��������� ���������
		  )
		);

		$this->lAdmin->CheckListMode();
    }

    private function getNameStatus($status){
        return !empty($this->statuses[$status])?$this->statuses[$status]:$status;
    }

    private function getFullName($res){
        $tmpNames=[];
        if(!empty($res['last_name'])){
            $tmpNames[]=$res['last_name'];
        }
        if(!empty($res['name'])){
            $tmpNames[]=$res['name'];
        }
        if(!empty($res['second_name'])){
            $tmpNames[]=$res['second_name'];
        }
        if(count($tmpNames)>0){
            return implode(' ', $tmpNames);
        }else{
            return $res['login'];
        }
    }
	
	public function getTableList(){
		$this->lAdmin->DisplayList();
    }
    
//edit part
    public function getOrder($id){
		global $DB;
        $select='select
            '.$this->settings->getTableWriteOff().'.id as id,
			'.$this->settings->getTableWriteOff().'.bonus as bonus,
			'.$this->settings->getTableWriteOff().'.transact_id as transact_id,
			'.$this->settings->getTableWriteOff().'.profile_id as profile_id,
			'.$DB->DateToCharFunction($this->settings->getTableWriteOff().'.date_order').' as date_order,
            '.$this->settings->getTableWriteOff().'.status as status,
			'.$DB->DateToCharFunction($this->settings->getTableWriteOff().'.date_change').' as date_change,
            '.$this->settings->getTableWriteOff().'.comment as comment,
            '.$this->settings->getTableWriteOff().'.log as log,
            '.$this->settings->getTableWriteOff().'.user_id as user_id,
            b_user.LAST_NAME as last_name,
            b_user.NAME as name,
            b_user.SECOND_NAME as second_name,
            b_user.LOGIN as login,
            b_user.EMAIL as email,
            '.$this->settings->getTableUserRequisites().'.cart_number as cart_number,
            '.$this->settings->getTableUserRequisites().'.invoice as invoice,
            '.$this->settings->getTableUserRequisites().'.bik as bik
        from '.$this->settings->getTableWriteOff().'
            left join b_user on (b_user.ID='.$this->settings->getTableWriteOff().'.user_id)
            left join '.$this->settings->getTableUserRequisites().' on ('.$this->settings->getTableWriteOff().'.requisites_id='.$this->settings->getTableUserRequisites().'.id)
			where '.$this->settings->getTableWriteOff().'.id='.$id.';';
		$rsData = $DB->Query($select);
		if($row=$rsData->Fetch()){
			if(!empty($row['log'])){
				$row['log']=unserialize($row['log']);
			}
			$row['fill_name']=$this->getFullName($row);
			$this->dataOrder=$row;
			return $this->dataOrder;
		}

	}
	
	private function clearBonus(){
		//$this->dataOrder['transact_id']
		//$this->dataOrder['bonus']
		//$this->dataOrder['user_id']
		//$data=$this->dataOrder;
		//$data=['transact_id'=>1818,'bonus'=>3000,'user_id'=>1];
		$data=$this->dataOrder;
		$currentBonus=$data['bonus'];
		if(empty($currentBonus)){
			return;
		}
		global $DB;
		$sqls=[
			'select * from '.$this->settings->getTableBonusList().' where user_id='.$data['user_id'].' and status="active" and date_remove is not null order by date_remove asc',
			'select * from '.$this->settings->getTableBonusList().' where user_id='.$data['user_id'].' and status="active" and date_remove is null order by date_add asc'
		];
		foreach($sqls as $sql){
			if($currentBonus<=0){
				break;
			}
			$rsData = $DB->Query($sql);
			while($row=$rsData->Fetch()){
				if($currentBonus<=0){
					break;
				}
				$updArr=[];
				$comments=(empty($row['comments']))?[]:explode('###', $row['comments']);
				if($row['bonus']>$currentBonus){
					$writeBonus=$currentBonus;
					$updArr['bonus']=$row['bonus']-$currentBonus;
					$currentBonus=0;
				}else{
					$updArr['bonus']=0;
					$updArr['status']='"used"';
					$currentBonus-=$row['bonus'];
					$writeBonus=$row['bonus'];
				}
				$writeBonus=round($writeBonus, 2);
				$comments[]=Loc::getMessage("commerce.loyaltyprogram_BONUS_WRITE_OFF", ["#NUM#"=>$writeBonus]);
				$updArr['comments']='"'.$DB->ForSql(implode('###', $comments)).'"';
				$DB->Update($this->settings->getTableBonusList(), $updArr, "where id='".$row['id']."'", $err_mess.__LINE__);
				$insSQL='insert into '.$this->settings->getTableTransactionList().' (bonus_id,transaction_id) values ('.$row['id'].', '.$data['transact_id'].')';
				$DB->Query($insSQL);
			}
		}
	}

	public function setOrder($data){
		if($this->isEdit()){
			if($data['status']=='execute'){
				\CSaleUserTransact::Update(
					$this->dataOrder['transact_id'],
					['NOTES'=>Loc::getMessage("commerce.loyaltyprogram_PROGRAM_WRITEOFF_EXECUTE")]
				);
				
				//clear bonus from bonus table
				$this->clearBonus();
				if(!empty($this->dataOrder['profile_id'])) {
                    $profile = Profiles\Profile::getProfileById($this->dataOrder['profile_id']);
                    $profile->sendEvent([
                        'type' => 'userTemplate',
                        'bonus' => $this->dataOrder['bonus'],
                        'id_bonus' => $this->dataOrder['id'],
                        'email' => $this->dataOrder['email'],
                        'user_id' => $this->dataOrder['user_id'],
                        'user_name' => $this->dataOrder['fill_name']
                    ]);
                }
				$this->setLog($data);
			}elseif($data['status']=='reject'){
				\CSaleUserAccount::UpdateAccount(
					$this->dataOrder['user_id'],
					$this->dataOrder['bonus'],
					$this->moduleOptions['currency'],
					"COMMERCE_LOYAL_WRITEOFF",
					'',
					Loc::getMessage("commerce.loyaltyprogram_PROGRAM_WRITEOFF_REJECT")
				);
                if(!empty($this->dataOrder['profile_id'])) {
                    $profile = Profiles\Profile::getProfileById($this->dataOrder['profile_id']);
                    $profile->sendEvent([
                        'type' => 'userTemplateReject',
                        'bonus' => $this->dataOrder['bonus'],
                        'id_bonus' => $this->dataOrder['id'],
                        'email' => $this->dataOrder['email'],
                        'user_id' => $this->dataOrder['user_id'],
                        'user_name' => $this->dataOrder['fill_name']
                    ]);
                }
				$this->setLog($data);
			}
		}
	}

	public function getEditArea(){?>
		<tr><td width="40%"><?=Loc::getMessage("commerce.loyaltyprogram_WO_DET_NAME")?></td><td><a href="/bitrix/admin/user_edit.php?lang=<?=SITE_ID?>&ID=<?=$this->dataOrder['user_id']?>" target="_blank"><?=$this->dataOrder['fill_name'].' ['.$this->dataOrder['user_id'].']'?></a></td></tr>
		<tr><td><?=Loc::getMessage("commerce.loyaltyprogram_WO_DET_BONUS")?></td><td><?=\CCurrencyLang::CurrencyFormat($this->dataOrder['bonus'], $this->moduleOptions['currency'])?></td></tr>
		<tr><td><?=Loc::getMessage("commerce.loyaltyprogram_WO_DET_DATE_ORDER")?></td><td><?=$this->dataOrder['date_order']?></td></tr>
		<?if(!empty($this->dataOrder['cart_number'])){?>
		<tr><td><?=Loc::getMessage("commerce.loyaltyprogram_WO_DET_CART_NUMBER")?></td><td><?=$this->dataOrder['cart_number']?></td></tr>
		<?}else{?>
		<tr><td><?=Loc::getMessage("commerce.loyaltyprogram_WO_DET_INVOICE_NUMBER")?></td><td><?=$this->dataOrder['invoice']?></td></tr>
		<tr><td><?=Loc::getMessage("commerce.loyaltyprogram_WO_DET_BIK_NUMBER")?></td><td><?=$this->dataOrder['bik']?></td></tr>
		<?}?>
		<?if($this->isEdit()){?>
			<tr><td><?=Loc::getMessage("commerce.loyaltyprogram_WO_DET_SET_STATUS")?></td><td><select name="status"><?foreach($this->statuses as $statusKey=>$statusName){
				if($statusKey=='request'){
					continue;
				}
				?>
				<option value="<?=$statusKey?>"><?=$statusName?></option>
				<?}?></select></td>
			</tr>
			<tr><td><?=Loc::getMessage("commerce.loyaltyprogram_WO_DET_COMMENT")?></td><td><textarea name="comment" id="" cols="50" rows="5"></textarea></td></tr>
		<?}else{?>
			<tr><td><?=Loc::getMessage("commerce.loyaltyprogram_WO_FILTER_STATUS")?></td><td><?=$this->statuses[$this->dataOrder['status']]?></td></tr>
		<?}?>
	<?}

	private function setLog($data){
		global $DB, $USER;
		$cLog=empty($this->dataOrder['log'])?[]:$this->dataOrder['log'];
        $dateTime = new \Bitrix\Main\Type\DateTime;
		$cLog[]=['status'=>$data['status'], 'comment'=>$data['comment'], 'date'=>$dateTime->toString(), 'manader_id'=>$USER->GetID()];
		$DB->Update($this->settings->getTableWriteOff(), [
			'status'=>'"'.$data['status'].'"',
			'comment'=>'"'.$DB->ForSql($data['comment']).'"',
			'date_change'=>'NOW()',
			'log'=>'"'.$DB->ForSql(serialize($cLog)).'"'
		], "where id='".$this->dataOrder['id']."'", $err_mess.__LINE__);
	}

	public function getLog(){?>
		<tr><td width="40%"><?=Loc::getMessage("commerce.loyaltyprogram_WO_DET_DATE_ORDER")?></td><td><?=$this->dataOrder['date_order']?></td></tr>
		<?if(!empty($this->dataOrder['log']) && is_array($this->dataOrder['log'])){
			foreach($this->dataOrder['log'] as $nextRow){
				$user=Tools::getUserData($nextRow['manader_id']);
				$status=empty($nextRow['status'])?$this->statuses[$this->dataOrder['status']]:$this->statuses[$nextRow['status']];
				?>
				<tr><td><?=$status?></td>
				<td>
					<?=Loc::getMessage("commerce.loyaltyprogram_WO_DET_TIME")?>: <?=$nextRow['date']?><br>
					<?=Loc::getMessage("commerce.loyaltyprogram_WO_DET_RESPONSIVE")?>: <a href="/bitrix/admin/user_edit.php?lang=<?=SITE_ID?>&ID=<?=$nextRow['manader_id']?>" target="_blank"><?=$user['FULL_NAME'].' ['.$nextRow['manader_id'].']'?></a><br>
					<?=Loc::getMessage("commerce.loyaltyprogram_WO_DET_COMMENT")?>: <br><?=$nextRow['comment']?>
				</td></tr>
			<?}
		}?>
	<?}
	
	public function addCart($userId, $cart){
		global $DB;
		$cart=
		$id=$DB->Insert($this->settings->getTableUserRequisites(), [
				'cart_number'=>'"'.$cart.'"',
				'user_id'=>$userId,
			], $err_mess.__LINE__);
		return $id;
	}

	public function addInvoice($userId, $invoice, $bik){
		global $DB;
		$invoice=preg_replace("/[^0-9]/", '', $invoice);
		$bik=preg_replace("/[^0-9]/", '', $bik);
		$cart=
		$id=$DB->Insert($this->settings->getTableUserRequisites(), [
				'invoice'=>'"'.$invoice.'"',
				'bik'=>'"'.$bik.'"',
				'user_id'=>$userId,
			], $err_mess.__LINE__);
		return $id;
	}
	
	/**
	* @param int $userId - user id
	* @param int $idReq - requisite id
	* @param array $data - contents key cart, invoice, bik for change
	*/
	public function updateRequisites($userId, $idReq, $data){
		$userId=(int) $userId;
		$idReq=(int) $idReq;
		if(!empty($idReq) && !empty($idReq)){
			global $DB;
			$select='select * from '.$this->settings->getTableUserRequisites().' where user_id='.$userId.' and id='.$idReq.';';
			$rsData = $DB->Query($select);
			if($row = $rsData->Fetch()){
				$updFields=[];
				if(!empty($data['cart'])){
					$updFields['cart_number']='"'.$DB->ForSql($data['cart']).'"';
				}
				if(!empty($data['invoice'])){
					$updFields['invoice']=$data['invoice'];
				}
				if(!empty($data['bik'])){
					$updFields['bik']='"'.$DB->ForSql($data['bik']).'"';;
				}
				$updFields['date_change']='now()';
				$DB->Update($this->settings->getTableUserRequisites(), $updFields, "where id='".$idReq."'", $err_mess.__LINE__);
				return true;
			}
		}
		return false;
	}
	
	/**
	* delete or deactivate requizite (delete id not used, deacivate if used)
	*/
	public function deleteRequisites($userId, $idReq){
		$userId=(int) $userId;
		$idReq=(int) $idReq;
		if(!empty($idReq) && !empty($idReq)){
			global $DB;
			$select='select * from '.$this->settings->getTableUserRequisites().' where user_id='.$userId.' and id='.$idReq.';';
			$rsData = $DB->Query($select);
			if($row = $rsData->Fetch()){
				$rsDataUsed = $DB->Query('select * from '.$this->settings->getTableWriteOff().' where requisites_id='.$idReq.';');
				if($rowUsed = $rsDataUsed->Fetch()){
					$DB->Query('update '.$this->settings->getTableUserRequisites().' set active ="N" where id='.$idReq.';');
				}else{
					$DB->Query('delete from '.$this->settings->getTableUserRequisites().' where id='.$idReq.';');
				}
				return true;
			}
		}
		return false;
	}
	
	public function getRequisites($userId){
		global $DB;
		$requisites=[];
		$select='select * from '.$this->settings->getTableUserRequisites().' where user_id='.$userId.' and active="Y" order by id;';
		$rsData = $DB->Query($select);
		while($row = $rsData->Fetch()){
			if(!empty($row['cart_number'])){
				$requisites[]=['type'=>'cart', 'cart'=>$row['cart_number'], 'id'=>$row['id']];
			}elseif(!empty($row['bik']) && !empty($row['invoice'])){
				$requisites[]=['type'=>'invoice', 'invoice'=>$row['invoice'], 'bik'=>$row['bik'], 'id'=>$row['id']];
			}
		}
		return $requisites;
	}
	
	public function isEdit(){
		if($this->dataOrder['status']=='request'){
			return true;
		}
		return false;
	}

}

?>