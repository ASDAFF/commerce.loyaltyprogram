<?
namespace Commerce\Loyaltyprogram;
use \Bitrix\Main\Localization\Loc,
	\Bitrix\Main\Config\Option;
Loc::loadMessages(__DIR__ .'/lang.php');
\Bitrix\Main\Loader::includeModule('sale');
/**
* Global settings for modules
*/
class Settings{
	private static $_instance = null;
	
	private $module_id;
	private $tableProfilesList;
	private $tableUsersList;
	private $options;
	
	private function __clone(){}
	private function __wakeup(){}
	
	private function __construct(){
		$this->module_id='commerce.loyaltyprogram';
		$this->tableProfilesList='commerce_loyal_profiles';
		$this->tableUsersList='commerce_loyal_users';
		$this->tableBonusList='commerce_loyal_bonuses';
		$this->tableTransactionList='commerce_loyal_bonuses_transaction';
		$this->tableRefCoupons='commerce_loyal_coupons';
		$this->tableRuleDescription='commerce_loyal_rule_desc';
		$this->tableStatLink='commerce_loyal_stat_link';
		$this->tableStatSite='commerce_loyal_stat_site';
		$this->tableStatDetail='commerce_loyal_detail_stat';
		$this->tableWriteOff='commerce_loyal_write_off';
		$this->tableUserRequisites='commerce_loyal_user_requisites';
		$this->tableActionList='commerce_loyal_action_list';
		$this->tablePartnerSiteList='commerce_loyal_partner_sites';
		$this->tableSubscribeUser='commerce_loyal_subscribe_user';
		$this->options=[];
		//$tmpOptions=['ref_link_name','ref_link_value','ref_perform_all','ref_active','cookie_time','ref_prop','cookie_rename','group_user','set_referal','ref_level','currency'];
		$tmpOptions=\Bitrix\Main\Config\Option::getDefaults($this->module_id);
		$tmpOptions=array_keys($tmpOptions);
		foreach($tmpOptions as $nextOption){
			$this->options[$nextOption]=Option::get($this->module_id, $nextOption);
		}
		
		$tmpOptions=\Bitrix\Main\Config\Option::getForModule($this->module_id);
		$tmpOptions=array_keys($tmpOptions);
		foreach($tmpOptions as $nextOption){
			if(empty($this->options[$nextOption])){
				$this->options[$nextOption]=Option::get($this->module_id, $nextOption);
			}
		}
		
		$tmpRefBasketRules=explode(',',Option::get($this->module_id, 'ref_basket_rules'));
		$this->options['ref_basket_rules']=Option::get($this->module_id, 'ref_basket_rules');
		foreach($tmpRefBasketRules as $key=>$nextRule){
			if($key==0){continue;}
			$this->options['ref_coupon_prop'.$key]=Option::get($this->module_id, 'ref_coupon_prop'.$key);
			$this->options['ref_coupon_group'.$key]=Option::get($this->module_id, 'ref_coupon_group'.$key);
			$this->options['ref_coupon_code'.$key]=Option::get($this->module_id, 'ref_coupon_code'.$key);
			$this->options['ref_coupon_istemporary'.$key]=Option::get($this->module_id, 'ref_coupon_istemporary'.$key);
		}
	}

	public static function getInstance(){
		if (self::$_instance != null){
			return self::$_instance;
		}
		return new self;
	}

	public function getOptions(){
		return $this->options;
	}
	
	public function getModuleId(){
		return $this->module_id;
	}
	
	public function getTableProfilesList(){
		return $this->tableProfilesList;
	}

	public function getTablePartnerSiteList(){
		return $this->tablePartnerSiteList;
	}
	
	public function getTableActionList(){
		return $this->tableActionList;
	}

	public function getTableUserRequisites(){
		return $this->tableUserRequisites;
	}

	/**
	* table for write off bonuses
	* the status field can contain values request, reject, execute.
	*/
	public function getTableWriteOff(){
		return $this->tableWriteOff;
		
	}
	
	public function getTableRuleDescription(){
		return $this->tableRuleDescription;
	}
	public function getTableSubscribeUser(){
		return $this->tableSubscribeUser;
	}

	public function getTableRefCoupons(){
		return $this->tableRefCoupons;
	}
	
	public function getTableUsersList(){
		return $this->tableUsersList;
	}

	public function getTableStatLink(){
		return $this->tableStatLink;
	}

	public function getTableStatSite(){
		return $this->tableStatSite;
	}

	public function getTableStatDetail(){
		return $this->tableStatDetail;
	}
	
	/**
	* table for bonuses
	*
	* the status field can contain values inactive, active, used, overdue.
	* inactive - start status
	* active - bonuses added in sale account
	* used - bonuses is used
	* overdue - bonuses is overdue
	*/
	public function getTableBonusList(){
		return $this->tableBonusList;
	}
	public function getTableTransactionList(){
		return $this->tableTransactionList;
	}
	/**
	* uf_props for users
	*
	* return list uf_props for users with filter by type
	* @var array|string $type - filter data for return list
	* @return array
	*/
	public function getUsersProps($type=''){
		if(empty($this->userProps)){
			global $USER_FIELD_MANAGER;
			$this->userProps = $USER_FIELD_MANAGER->GetUserFields("USER", '', LANGUAGE_ID);
		}
		$retArr=$this->userProps;
		if(!empty($type)){
			$retArr=[];
			if(!is_array($type)){
				$type=[$type];
			}
			foreach($this->userProps as $nextKey=>$nextProp){
				if(in_array($nextProp['USER_TYPE_ID'], $type)){
					$retArr[$nextKey]=$nextProp;
				}
			}
		}
		
		return $retArr;
	}

	public function getUserTurnover($userId=0){
		$summ=0;
		$statusSlect=(!empty($this->options['orderstatus']))?' status_id>="'.$this->options['orderstatus'].'"':'';
		$select='select
				sum(PRICE-PRICE_DELIVERY) as total_price,
				USER_ID as user_id
				from b_sale_order
				where  1=1 and'.$statusSlect.' and user_id='.$userId.'
				group by USER_ID ;';
		global $DB;
		$res=$DB->Query($select);
		if($row = $res->Fetch()){
			$summ=$row['total_price'];
		}
		return $summ;
	}
	
	public function getOrderStatuses(){
		if(empty($this->orderStatuses)){
			$this->orderStatuses=[];
			$statusResult = \Bitrix\Sale\Internals\StatusLangTable::getList([
				'order' => ['STATUS.SORT'=>'ASC'],
				'filter' => ['LID'=>LANGUAGE_ID],
				'select' => ['STATUS_ID','NAME']
			]);
			while($status = $statusResult->fetch()){
				$this->orderStatuses[]=$status;
			}
		}
		return $this->orderStatuses;
	}
	
	public function getSites(){
		if(empty($this->sites)){
			$rsSites=\CSite::GetList(($by = "SORT"), ($order = "asc"));
			while ($arSite = $rsSites->Fetch()){
				$this->sites[$arSite['LID']]=$arSite['SITE_NAME'];
			}
		}
		return $this->sites;
	}
	
	public function getPaySystems(){
		if(empty($this->paySystems)){
			$paySystems=[];
			$res=\Bitrix\Sale\Internals\PaySystemActionTable::GetList(['order' => ["NAME" => "ASC"]]);
			while($row=$res->fetch()){
				$this->paySystems[]=$row;
			}
		}
		return $this->paySystems;
	}
	
	public function getDelivery(){
		if(empty($this->delivery)){
			$paySystems=[];
			$res = \Bitrix\Sale\Delivery\Services\Table::getList(['order' => ["NAME" => "ASC"]]);
               while ($dev = $res->Fetch()) {
                   $this->delivery[] = $dev;
               }
		}
		return $this->delivery;
	}
	
	public function getPersonTypes(){
		if(empty($this->personTypes)){
			$paySystems=[];
			$db_ptype=\CSalePersonType::GetList(['NAME'=>'ASC'],[],false,false,array());
			while ($ptype = $db_ptype->Fetch()){
				$this->personTypes[$ptype['ID']]=$ptype['NAME'].' ['.$ptype['ID'].']';
			}
		}
		return $this->personTypes;
	}
	
	public function getInnerPaySystem(){
		if(!isset($this->innerPaySystem)){
			$this->innerPaySystem=0;
			$res=\Bitrix\Sale\Internals\PaySystemActionTable::GetList(['filter'=>['ACTION_FILE'=>'inner']]);
			if($row=$res->fetch()){
				$this->innerPaySystem=$row;
			}
		}
		return $this->innerPaySystem;
	}
	
	public function getBasketRules(){
		if(!isset($this->basketRules)){
		$discountIterator = \Bitrix\Sale\Internals\DiscountTable::getList([
			'select' => ["ID", "NAME"],
			'filter' => ['ACTIVE' => 'Y'],
			'order' => ["NAME" => "ASC"]
		]);
		while ($discount = $discountIterator->fetch()){
			 $this->basketRules[$discount['ID']]=$discount['NAME'].' ['.$discount['ID'].']';
		}
		}
		return $this->basketRules;
	}
	
	public function getUserRefData($userId, $data='type'){
		if(empty($userId)){
			return false;
		}
		if(empty($this->userRefData)){
			global $DB;
            $select='select * from '.$this->tableUsersList.' where user='.$userId.';';
            $rsData = $DB->Query($select);
            if($row = $rsData->Fetch()){
                $this->userRefData=$row;
            }
		}
		if($this->userRefData){
			return $this->userRefData[$data];
		}
	}
	
	public function getUserGroups(){
		if(empty($this->userGroups)){
			$this->userGroups=[];
			$res = \Bitrix\Main\GroupTable::getList(['order'  => ['C_SORT', 'NAME']]);
			while($row = $res->fetch()){
				$this->userGroups[$row['ID']]=$row['NAME'];
			}
		}
		return $this->userGroups;
	}
	
	public function getEmailTemplate($name){
		if(file_exists(__DIR__.'/../include/posttemplates/'.$name.'.html')){
			$str=file_get_contents(__DIR__.'/../include/posttemplates/'.$name.'.html');
			if(LANG_CHARSET=='UTF-8'){$str=iconv('windows-1251', 'UTF-8', $str);}
			return $str;
		}
		return '';
	}

}

?>