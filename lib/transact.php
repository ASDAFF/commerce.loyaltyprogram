<?
namespace Commerce\Loyaltyprogram;
use \Bitrix\Main\Localization\Loc;
Loc::loadMessages(__DIR__ .'/lang.php');
/**
* transact list class
*/
class Transact{

	function __construct (){
		$this->settings=Settings::getInstance();
	}
	
	private function checkFilter(){
		global $FilterArr;
		foreach ($FilterArr as $f){global $$f;}
		return count($this->lAdmin->arFilterErrors)==0;
	}
	
	/**
	* return array $action ids for parent ref acc
	*/
	private function getRefActions(){
		global $DB;
		$parentRefIds=[];
		$select='select * from '.$this->settings->getTableProfilesList().' order by id;';
		$rsData = $DB->Query($select);
		while($row = $rsData->Fetch()){
			$settings=unserialize($row['settings']);
			if(!empty($settings['condition']['children'])){
				foreach($settings['condition']['children'] as $nextCondition){
					if($nextCondition['controlId']=='registerbyParentRef' && !empty($nextCondition['values']['number_action'])){
						$parentRefIds[]=$nextCondition['values']['number_action'];
					}
				}
			}
		}
		return $parentRefIds;
	}
	
	private function getTransactTypes(){
		global $DB, $MESS;
		$parentRefIds=[];
		$select='select distinct description from tmp_loyal_bonuses where description is not null order by amount;';
		$types=['REFERENCE_ID'=>['all'], 'REFERENCE'=>[Loc::getMessage("commerce.loyaltyprogram_ALL")]];
		$rsData = $DB->Query($select);
		while($row = $rsData->Fetch()){
			if(empty($row['description'])){
				continue;
			}
			$tmpId=$tmpName=$row['description'];
			$tmpName=empty(Loc::getMessage('commerce.tr_name_'.$tmpName))?$tmpName:Loc::getMessage('commerce.tr_name_'.$tmpName);
			$types['REFERENCE_ID'][]=$tmpId;
			$types['REFERENCE'][]=$tmpName;
		}
		return $types;
	}
	
	public function initFilter(){
		global $find_id, $find_type, $find_userId, $active_from, $active_to, $find_profileId, $find_actionId, $find_is_parent_ref, $active_from_FILTER_PERIOD, $APPLICATION;
		$oFilter = new \CAdminFilter(
			$this->settings->getTableBonusList().'_filter',
			[
				Loc::getMessage("commerce.loyaltyprogram_HEADER_DATE_PERIOD"),
				Loc::getMessage("commerce.loyaltyprogram_HEADER_BONUS_ID_USER"),
				Loc::getMessage("commerce.loyaltyprogram_HEADER_DESCRIPTION"),
				Loc::getMessage("commerce.loyaltyprogram_HEADER_BONUS_ID_TRANSACT"),
				Loc::getMessage("commerce.loyaltyprogram_HEADER_PROFILE_ID"),
				Loc::getMessage("commerce.loyaltyprogram_HEADER_ACTION_ID"),
				Loc::getMessage("commerce.loyaltyprogram_HEADER_REFERRAL_ID")
			]
		);?>
		<form name="find_form" method="get" action="<?echo $APPLICATION->GetCurPage();?>">
		<?$oFilter->Begin();?>

		<tr id="tr_PERIOD">
			<td width="40%"><?=Loc::getMessage("commerce.loyaltyprogram_HEADER_DATE_PERIOD")?></td>
			<td width="60%"><?
				$periodValue = '';
				if ('' != $active_from || '' != $active_to)
					$periodValue = \CAdminCalendar::PERIOD_INTERVAL;

				echo \CAdminCalendar::CalendarPeriod(
					'active_from',
					'active_to',
					$active_from,
					$active_to,
					true,
					10	
				);
			?></td>
		</tr>
		<tr>
			<td><b><?=Loc::getMessage("commerce.loyaltyprogram_HEADER_BONUS_ID_USER").":"?></b></td>
			<td>
				<?echo FindUserID("find_userId", $find_userId, "", "find_form");?>
			</td>
		</tr>
		<tr>
			<td><?=Loc::getMessage("commerce.loyaltyprogram_HEADER_DESCRIPTION").":"?></td>
			<td>
				<?/*<input type="text" name="find_type" value="<?echo htmlspecialchars($find_type)?>">*/?>
				<?echo \SelectBoxMFromArray("find_type[]", $this->getTransactTypes(), $find_type, "", false, 5);?>
			</td>
		</tr>
		<tr>
			<td><b><?=Loc::getMessage("commerce.loyaltyprogram_HEADER_BONUS_ID_TRANSACT").":"?></b></td>
			<td>
				<input type="text" min=1 step=1 size="25" name="find_id" value="<?echo htmlspecialchars($find_id)?>">
			</td>
		</tr>
		<tr>
			<td><b><?=Loc::getMessage("commerce.loyaltyprogram_HEADER_PROFILE_ID").":"?></b></td>
			<td>
				<input type="text" min=1 step=1 size="25" name="find_profileId" value="<?echo htmlspecialchars($find_profileId)?>">
			</td>
		</tr>
		<tr>
			<td><b><?=Loc::getMessage("commerce.loyaltyprogram_HEADER_ACTION_ID").":"?></b></td>
			<td>
				<input type="text" min=1 step=1 size="25" name="find_actionId" value="<?echo htmlspecialchars($find_actionId)?>">
			</td>
		</tr>
		<tr>
			<td><b><?=Loc::getMessage("commerce.loyaltyprogram_HEADER_REFERRAL_ID").":"?></b></td>
			<td>
				<select name="find_is_parent_ref">
					<option value=""><?=Loc::getMessage("commerce.loyaltyprogram_ALL")?></option>
					<option value="1"<?if ($find_is_parent_ref=="1") echo " selected"?>><?=Loc::getMessage("commerce.loyaltyprogram_Y")?></option>
					<option value="0"<?if ($find_is_parent_ref=="0") echo " selected"?>><?=Loc::getMessage("commerce.loyaltyprogram_N")?></option>
				</select>
			</td>
		</tr>
		<?
			$oFilter->Buttons(array("table_id"=>$this->settings->getTableBonusList(),"url"=>$APPLICATION->GetCurPage(),"form"=>"find_form"));
			$oFilter->End();
		?>
		</form>
	<?}
	
	public function initTableList(){

		global $find_id,$find_type,$del_filter,$find_userId,$DB,$by,$order,$FIELDS,$arID, $active_from, $active_to, $find_profileId, $find_actionId, $find_is_parent_ref, $active_from_FILTER_PERIOD, $FilterArr,$APPLICATION;
		$where='';
		if(empty($del_filter)){
			if(!empty($find_id)){
				$where.=' and tmp_loyal_bonuses.id_transact='.$find_id;
			}
			if(!empty($find_type)){
				if(in_array('all', $find_type)){
					$find_type=[];
				}elseif(count($find_type)>0){
					$where.=' and tmp_loyal_bonuses.description in ("'.implode('","',$find_type).'")';
				}
			}
			if(!empty($find_userId)){
				$where.=' and tmp_loyal_bonuses.user_id="'.$find_userId.'"';
			}
			
			if(!empty($find_profileId)){
				$where.=' and tmp_loyal_bonuses.profile_id="'.$find_profileId.'"';
			}
			
			if(!empty($find_actionId)){
				$where.=' and tmp_loyal_bonuses.action_id="'.$find_actionId.'"';
			}
			if(isset($find_is_parent_ref) && ($find_is_parent_ref==0 || $find_is_parent_ref==1)){
				$where.=' and tmp_loyal_bonuses.is_parent_ref="'.$find_is_parent_ref.'"';
			}
			
			//date filter
			if(!empty($active_from_FILTER_PERIOD)){
				switch ($active_from_FILTER_PERIOD) {
					case 'exact':
						if(!empty($active_from)){
							$tmptime=\MakeTimeStamp($active_from, \CSite::GetDateFormat("SHORT"));
							$where.=' and tmp_loyal_bonuses.transact_date>=FROM_UNIXTIME('.$tmptime.')';
							$where.=' and tmp_loyal_bonuses.transact_date<=FROM_UNIXTIME('.($tmptime+86400).')';
						}
						break;
					case 'before':
						if(!empty($active_to)){
							$tmptime=\MakeTimeStamp($active_to, \CSite::GetDateFormat("SHORT"));
							$where.=' and tmp_loyal_bonuses.transact_date<=FROM_UNIXTIME('.($tmptime+86400).')';
						}
						break;
					case 'after':
						if(!empty($active_from)){
							$tmptime=\MakeTimeStamp($active_from, \CSite::GetDateFormat("SHORT"));
							$where.=' and tmp_loyal_bonuses.transact_date>=FROM_UNIXTIME('.$tmptime.')';
						}
						break;
					default:
						if(!empty($active_from)){
							$tmptime=\MakeTimeStamp($active_from, \CSite::GetDateFormat("SHORT"));
							$where.=' and tmp_loyal_bonuses.transact_date>=FROM_UNIXTIME('.$tmptime.')';
						}
						if(!empty($active_to)){
							$tmptime=\MakeTimeStamp($active_to, \CSite::GetDateFormat("SHORT"));
							$where.=' and tmp_loyal_bonuses.transact_date<=FROM_UNIXTIME('.($tmptime+86400).')';
						}
						
				}
			}
		}
		
		//hard
		$tmpSelect=['
		CREATE TEMPORARY TABLE IF NOT EXISTS tmp_loyal_bonuses
		(id int(11) NOT NULL AUTO_INCREMENT,
		id_transact int(11),
		user_id int(11) NOT NULL,
		transact_date datetime NOT NULL,
		amount decimal(18,4),
		currency char(3),
		debit char(1),
		order_id int(11) DEFAULT NULL,
		profile_id int(11) DEFAULT NULL,
		action_id int(11) DEFAULT NULL,
		is_parent_ref TINYINT(1) DEFAULT 0,
		description varchar(255),
		notes text,
		PRIMARY KEY (id), INDEX user_id (user_id), INDEX debit (debit), INDEX profile_id (profile_id), INDEX action_id (action_id), INDEX is_parent_ref (is_parent_ref));',
	
		'insert into tmp_loyal_bonuses
		(user_id, transact_date, amount, currency, debit, description, notes, profile_id, action_id)
		select 
		user_id,
		date_add,
		bonus_start,
		currency,
		"Y",
		profile_type,
		add_comment,
		profile_id,
		action_id
		from '.$this->settings->getTableBonusList().'
		where status="inactive";',

		'insert into tmp_loyal_bonuses
		(id_transact,user_id,transact_date,amount,currency,debit,order_id,description,notes, profile_id, action_id)
		select distinct 
		b_sale_user_transact.ID,
		b_sale_user_transact.USER_ID,
		b_sale_user_transact.TRANSACT_DATE,
		b_sale_user_transact.AMOUNT,
		b_sale_user_transact.CURRENCY,
		b_sale_user_transact.DEBIT,
		b_sale_user_transact.ORDER_ID,
		b_sale_user_transact.DESCRIPTION,
		b_sale_user_transact.NOTES,
		max('.$this->settings->getTableBonusList().'.profile_id) as profile_id,
        max('.$this->settings->getTableBonusList().'.action_id) as action_id
		from  b_sale_user_transact
		left join '.$this->settings->getTableTransactionList().' on ('.$this->settings->getTableTransactionList().'.transaction_id=b_sale_user_transact.id)
		left join '.$this->settings->getTableBonusList().' on ('.$this->settings->getTableTransactionList().'.bonus_id='.$this->settings->getTableBonusList().'.id)
		where b_sale_user_transact.DESCRIPTION!="COMMERCE_LOYAL_WRITEOFF" and b_sale_user_transact.DESCRIPTION!="COMMERCE_LOYAL_ORDERPAY" and b_sale_user_transact.DESCRIPTION!="ORDER_PAY"
		group by b_sale_user_transact.ID
		;',
		
		'insert into tmp_loyal_bonuses
		(id_transact,user_id,transact_date,amount,currency,debit,order_id,description,notes)
		select distinct 
		b_sale_user_transact.ID,
		b_sale_user_transact.USER_ID,
		b_sale_user_transact.TRANSACT_DATE,
		b_sale_user_transact.AMOUNT,
		b_sale_user_transact.CURRENCY,
		b_sale_user_transact.DEBIT,
		b_sale_user_transact.ORDER_ID,
		b_sale_user_transact.DESCRIPTION,
		b_sale_user_transact.NOTES
		from  b_sale_user_transact
		where b_sale_user_transact.DESCRIPTION="COMMERCE_LOYAL_WRITEOFF" or b_sale_user_transact.DESCRIPTION="COMMERCE_LOYAL_ORDERPAY" or b_sale_user_transact.DESCRIPTION="ORDER_PAY"
		;',
		
		'update tmp_loyal_bonuses set amount=amount*(-1) where debit="N"'
		];
		
		$parentActionIds=$this->getRefActions();
		if(count($parentActionIds)>0){
			$tmpSelect[]='update tmp_loyal_bonuses set is_parent_ref=1 where action_id in('.implode(',', $parentActionIds).');';
		}
		foreach($tmpSelect as $nextSQL){
			$DB->Query($nextSQL);
		}
		
		$order=empty($order)?'desc':$order;
		$by=empty($by)?'id_transact':$by;
		
		//$oSort = new \CAdminSorting($this->settings->getTableBonusList(), "id", "desc");
		//$this->lAdmin = new \CAdminList($this->settings->getTableBonusList(), $oSort);
		$oSort = new \CAdminSorting('tmp_loyal_bonuses', "id", "desc");
		$this->lAdmin = new \CAdminList('tmp_loyal_bonuses', $oSort);
		//filter
		$FilterArr = [
			"find_userId",
			"find_type",
			"find_id",
			"active_from",
			"active_to",
			"profile_id",
			"action_id",
			"is_parent_ref"
		];
		$this->lAdmin->InitFilter($FilterArr);

		$rsData = $DB->Query('select
		tmp_loyal_bonuses.id as id,
		tmp_loyal_bonuses.id_transact as id_transact,
		tmp_loyal_bonuses.user_id as user_id,
		tmp_loyal_bonuses.transact_date as transact_date_sort,
		'.$DB->DateToCharFunction("tmp_loyal_bonuses.transact_date").' as transact_date,
		tmp_loyal_bonuses.amount as amount,
		tmp_loyal_bonuses.currency as currency,
		tmp_loyal_bonuses.debit as debit,
		tmp_loyal_bonuses.order_id as order_id,
		tmp_loyal_bonuses.profile_id as profile_id,
		tmp_loyal_bonuses.action_id as action_id,
		tmp_loyal_bonuses.is_parent_ref as is_parent_ref,
		tmp_loyal_bonuses.description as description,
		tmp_loyal_bonuses.notes as notes,
		concat(b_user.NAME, " ", b_user.LAST_NAME) as user_name,
		b_user.LOGIN as login
		from tmp_loyal_bonuses left join b_user on(tmp_loyal_bonuses.user_id=b_user.ID) where 1=1 '.$where.' order by '.$by.' '.$order);
		$rsData->NavStart(\CAdminResult::GetNavSize());
		//$rsData = new \CAdminResult($rsData, $this->settings->getTableBonusList());
		$rsData = new \CAdminResult($rsData, 'tmp_loyal_bonuses');
		$this->lAdmin->NavText($rsData->GetNavPrint(Loc::getMessage("commerce.loyaltyprogram_TABLELIST_PAGINATOR")));
		$this->lAdmin->AddHeaders(array(
			array(
				"id" => "id_transact",
				"content" => Loc::getMessage("commerce.loyaltyprogram_HEADER_BONUS_ID_TRANSACT"),
				"sort" => "id_transact",
				"default" => true
			),
			array(
				"id" => "transact_date",
				"content" => Loc::getMessage("commerce.loyaltyprogram_HEADER_DATE_ADD"),
				"sort" => "transact_date_sort",
				"default" => true
			),
			array(
				"id" => "user_id",
				"content" => Loc::getMessage("commerce.loyaltyprogram_HEADER_USER_ID"),
				"sort" => "user_id",
				"default" => true
			),
			array(
				"id" => "amount",
				"content" => Loc::getMessage("commerce.loyaltyprogram_HEADER_BONUS"),
				"sort" => "amount",
				"default" => true
			),
			array(
				"id" => "description",
				"content" => Loc::getMessage("commerce.loyaltyprogram_HEADER_DESCRIPTION"),
				"sort" => "description",
				"default" => true
			),
			array(
				"id" => "notes",
				"content" => Loc::getMessage("commerce.loyaltyprogram_HEADER_ADD_NOTES"),
				"sort" => "notes",
				"default" => true
			),
			array(
				"id" => "order_id",
				"content" => Loc::getMessage("commerce.loyaltyprogram_HEADER_ORDER_ID"),
				"sort" => "order_id",
				"default" => true
			),
			array(
				"id" => "profile_id",
				"content" => Loc::getMessage("commerce.loyaltyprogram_HEADER_PROFILE_ID"),
				"sort" => "profile_id",
				"default" => true
			),
			array(
				"id" => "action_id",
				"content" => Loc::getMessage("commerce.loyaltyprogram_HEADER_ACTION_ID"),
				"sort" => "action_id",
				"default" => false
			),
			array(
				"id" => "is_parent_ref",
				"content" => Loc::getMessage("commerce.loyaltyprogram_HEADER_REFERRAL_ID"),
				"sort" => "is_parent_ref",
				"default" => false
			)
		));
		
		while($arRes = $rsData->Fetch()){
			$row = $this->lAdmin->AddRow($arRes['id'], $arRes);
			$row->AddViewField("user_id", '<a href="/bitrix/admin/user_edit.php?lang='.SITE_ID.'&ID='.$arRes['user_id'].'" target="_blank">['.$arRes['user_id'].']</a> '.$arRes['login'].' ('.$arRes['user_name'].' '.$arRes['user_last_name'].')');
			$orderId=($arRes['order_id']>0)?'<a href="/bitrix/admin/sale_order_view.php?ID='.$arRes['order_id'].'&lang='.SITE_ID.'" target="_blank">'.$arRes['order_id'].'</a>':'';
			$row->AddViewField("order_id", $orderId);
			$row->AddViewField("amount", \CurrencyFormat($arRes['amount'], $arRes['currency']));
			$description=empty(Loc::getMessage("commerce.tr_name_".$arRes['description']))?$arRes['description']:Loc::getMessage("commerce.tr_name_".$arRes['description']);
			$row->AddViewField("description", $description);
			$row->AddViewField("is_parent_ref", $arRes['is_parent_ref']>0?Loc::getMessage("commerce.loyaltyprogram_Y"):Loc::getMessage("commerce.loyaltyprogram_N"));
		}

		$this->lAdmin->AddFooter(
		  array(
			array("title"=>Loc::getMessage("MAIN_ADMIN_LIST_SELECTED"), "value"=>$rsData->SelectedRowsCount()), // кол-во элементов
			array("counter"=>true, "title"=>Loc::getMessage("MAIN_ADMIN_LIST_CHECKED"), "value"=>"0"), // счетчик выбранных элементов
		  )
		);

		$this->lAdmin->AddAdminContextMenu();
		

		$this->lAdmin->CheckListMode();
	}
	
	public function getTableList(){
		 $this->lAdmin->DisplayList();
	}

}
?>