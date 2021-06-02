<?
namespace Commerce\Loyaltyprogram;
use \Bitrix\Main\Localization\Loc,
	\Bitrix\Main\Config\Option;
Loc::loadMessages(__DIR__ .'/lang.php');
/**
* Global settings for modules
*/
class Components{
	
	private $idUser;
	private $res;
	
	function __construct($idUser=0){
		$this->globalSettings=Settings::getInstance();
		$this->moduleOptions=$this->globalSettings->getOptions();
		if($idUser==0){
			global $USER;
			$this->idUser=$USER->GetID();
		}else{
			$this->idUser=$idUser;
		}
	}

	public function setOptions(array $options){
	    foreach ($options as $keyOption=>$valOption){
            $this->moduleOptions[$keyOption]=$valOption;
        }
	    return true;
    }

	public function getModuleOptions(){
		return $this->moduleOptions;
	}
	
	public function getCurrency(){
		return $this->moduleOptions['currency'];
	}
	
	public function getRes(){
		return $this->res;
	}
	
	public function getActiveModule(){
		return $this->moduleOptions['ref_active'];
	}
	
	public function getRefChain(){
		$chain=[];
		$maxLevelChain=$this->moduleOptions['ref_level'];
		if((int) $maxLevelChain>0){
			$cUsers=[$this->idUser];
			$allUsers=[];
			global $DB;
			for($i=0; $i<$maxLevelChain; $i++){
				if(count($cUsers)>0){
					$tmpUsers=[];
					$res=$DB->Query('select
						'.$this->globalSettings->getTableUsersList().'.id id,
						'.$this->globalSettings->getTableUsersList().'.user user,
						'.$this->globalSettings->getTableUsersList().'.ref_user ref_user,
						'.$this->globalSettings->getTableUsersList().'.type type,
						'.$this->globalSettings->getTableUsersList().'.level level,
						'.$this->globalSettings->getTableRefCoupons().'.coupon coupon,
						'.$this->globalSettings->getTablePartnerSiteList().'.site site,
						'.$DB->DateToCharFunction($this->globalSettings->getTableUsersList().".date_create").' date_create
						from '.$this->globalSettings->getTableUsersList().'
						left join '.$this->globalSettings->getTableRefCoupons().' on ('.$this->globalSettings->getTableUsersList().'.type="coupon" and '.$this->globalSettings->getTableUsersList().'.source_link>0 and '.$this->globalSettings->getTableUsersList().'.source_link='.$this->globalSettings->getTableRefCoupons().'.id)
						left join '.$this->globalSettings->getTablePartnerSiteList().' on ('.$this->globalSettings->getTableUsersList().'.type="partnerSite" and '.$this->globalSettings->getTableUsersList().'.source_link>0 and '.$this->globalSettings->getTableUsersList().'.source_link='.$this->globalSettings->getTablePartnerSiteList().'.id)
						where ref_user in ('.implode(',',$cUsers).');');
					while($row = $res->Fetch()){
						$row['source']='';
						if(!empty($row['coupon'])){
							$row['source']=$row['coupon'];
						}elseif(!empty($row['site'])){
							$row['site']=str_replace(['http://', 'https://'], '', $row['site']);
							if(function_exists('idn_to_utf8')){
								$row['site']=(LANG_CHARSET=='windows-1251')?iconv('UTF-8' , 'CP1251' , idn_to_utf8($row['site'])):idn_to_utf8($row['site']);
							}
							$row['source']=$row['site'];
						}
						$chain[$i][$row['user']]=$row;
						$tmpUsers[]=$row['user'];
						$allUsers[]=$row['user'];
					}
					$cUsers=$tmpUsers;
				}
			}
		}
		return ['chain'=>$chain, 'allUsers'=>$allUsers];
	}
	
	public function getActiveBasketRules(){
		$selectBasketRulesArr=explode(',' , \Bitrix\Main\Config\Option::get($this->globalSettings->getModuleId(), 'ref_basket_rules'));
		if(count($selectBasketRulesArr)>0){
			$retRules=[];
			\Bitrix\Main\Loader::includeModule('sale');
			$db_res = \CSaleDiscount::GetList(["SORT" => "ASC"],["ID" => $selectBasketRulesArr],false,false,[]);
			while($ar_res = $db_res->Fetch()){
				$retRules[$ar_res['ID']]=$ar_res['NAME'];
			}
			return $retRules;
		}
		return [];
	}
	
	public function getCouponsTest(){
		$coupons=[];
		global $DB, $USER;
		$selectBasketRulesArr=explode(',' , \Bitrix\Main\Config\Option::get($this->globalSettings->getModuleId(), 'ref_basket_rules'));
		if(!empty(\Bitrix\Main\Config\Option::get($this->globalSettings->getModuleId(), 'ref_basket_rules'))){
			
			//descriptions
			$res=$DB->Query('select * from '.$this->globalSettings->getTableRuleDescription().' where id_rule in ('.implode(',',$selectBasketRulesArr).');');
			$tmpDesc=[];
			while($row = $res->Fetch()){
				$tmpDesc[$row['id_rule']]=$row['description'];
			}
			
			foreach($selectBasketRulesArr as $keyRule=>$valRule){
				$propKey=($keyRule==0)?'':$keyRule;
				$couponGroups=$this->moduleOptions['ref_coupon_group'.$propKey];
				/*if(!empty($couponGroups) && !$this->checkUserGroup($couponGroups)){
					continue;
				}*/
	
				$discountIterator = \Bitrix\Sale\Internals\DiscountTable::getList([
					'select' => ["ID", "NAME", 'XML_ID'],
					'filter' => ['ACTIVE' => 'Y', 'ID'=>$valRule],
					'order' => ["NAME" => "ASC"]
				]);
				while ($discount = $discountIterator->fetch()){
					$coupons[$discount['ID']]=['DISCOUNT_NAME'=>$discount['NAME'], 'XML_ID'=>$discount['XML_ID']];
					if(!empty($tmpDesc[$discount['ID']])){
						$coupons[$discount['ID']]['DESCRIPTION']=$tmpDesc[$discount['ID']];
					}
				}
			}

			if(count($coupons)>0){
				$usArr=\CUser::GetByID($this->idUser);
				$arUser = $usArr->Fetch();
				$i=0;
				foreach($coupons as $keyCoupon=>&$nextCoupon){
					//$nextCoupon['COUPON']=$this->getCoupon($keyCoupon, $propKey, $nextCoupon['XML_ID']);
					$propKey=($i==0)?'':$i;
					$generateType=$this->moduleOptions['ref_coupon_code'.$propKey];
	
					if($generateType=='user_login'){
						$userCodeCoupon=$arUser['LOGIN'];
					}elseif($generateType=='user_xml_id'){
						$userCodeCoupon=$arUser['XML_ID'];
					}elseif($generateType=='user_prop' && !empty($this->moduleOptions['ref_coupon_prop'.$propKey])){
						$tmpCode=$arUser[$this->moduleOptions['ref_coupon_prop'.$propKey]];
						if(!empty($tmpCode)){
							if(is_array($tmpCode)){
								$userCodeCoupon=$tmpCode[0];
							}else{
								$userCodeCoupon=$tmpCode;
							}
						}else{
							$userCodeCoupon=$this->idUser;
						}
					}else{
						$userCodeCoupon=$this->idUser;
					}
					$tmpPrefix=\Bitrix\Main\Config\Option::get($this->globalSettings->getModuleId(), 'ref_coupon_withoutprefix'.$propKey);
					if(!empty($tmpPrefix) && $tmpPrefix=='Y'){
						$cpn=$userCodeCoupon;
					}else{
						$xmlId=(empty($nextCoupon['XML_ID']))?\randString(6, ["ABCDEFGHIJKLNMOPQRSTUVWXYZ"]):$nextCoupon['XML_ID'];
						$cpn=$xmlId.'_'.$userCodeCoupon;
					}
					$cpn=substr($cpn, -32);
					$nextCoupon['COUPON']=$cpn;
					$i++;
				}
			}
		}
		return $coupons;
	}

	public function getCoupons(){
		$coupons=[];
		$selectBasketRulesActive=\Bitrix\Main\Config\Option::get($this->globalSettings->getModuleId(), 'ref_coupon_active', 'N');
		if($selectBasketRulesActive=='N'){
			return [];
		}
		global $DB;
		$selectBasketRulesArr=explode(',' , \Bitrix\Main\Config\Option::get($this->globalSettings->getModuleId(), 'ref_basket_rules'));
		
		$keysBasketArr=[];
		foreach($selectBasketRulesArr as $key=>$val){
			$keysBasketArr[$val]=$key;
		}
	
		if(!empty(\Bitrix\Main\Config\Option::get($this->globalSettings->getModuleId(), 'ref_basket_rules'))){
			
			global $DB;
			//descriptions
			$res=$DB->Query('select * from '.$this->globalSettings->getTableRuleDescription().' where id_rule in ('.implode(',',$selectBasketRulesArr).');');
			$tmpDesc=[];
			while($row = $res->Fetch()){
				$tmpDesc[$row['id_rule']]=$row['description'];
			}
			
			foreach($selectBasketRulesArr as $keyRule=>$valRule){
				$propKey=($keyRule==0)?'':$keyRule;
				$couponGroups=$this->moduleOptions['ref_coupon_group'.$propKey];
				if(!empty($couponGroups) && !$this->checkUserGroup($couponGroups)){
					continue;
				}
	
				$discountIterator = \Bitrix\Sale\Internals\DiscountTable::getList([
					'select' => ["ID", "NAME", 'XML_ID'],
					'filter' => ['ACTIVE' => 'Y', 'ID'=>$valRule],
					'order' => ["NAME" => "ASC"]
				]);
				while ($discount = $discountIterator->fetch()){
					$coupons[$discount['ID']]=[
						'DISCOUNT_NAME'=>$discount['NAME'],
						'XML_ID'=>$discount['XML_ID'],
						'ID'=>$discount['ID'],
						'RULE_NUMBER'=>$propKey,
						'TYPE'=>$this->moduleOptions['ref_coupon_code'.$propKey]
					];
					if(!empty($tmpDesc[$discount['ID']])){
						$coupons[$discount['ID']]['DESCRIPTION']=$tmpDesc[$discount['ID']];
					}
				}
			}
			if(count($coupons)>0){
				$res=$DB->Query('select * from '.$this->globalSettings->getTableRefCoupons().' where user_id='.$this->idUser.' and rule_id in('.implode(',',array_keys($coupons)).');');
				while($row = $res->Fetch()){
					$coupons[$row['rule_id']]['COUPON']=$row['coupon'];
				}
				$i=0;
				foreach($coupons as $keyCoupon=>&$nextCoupon){
					$propKey=($i==0)?'':$i;
					if(!empty($nextCoupon['COUPON'])){
						if($this->checkCoupon($nextCoupon['COUPON'])==false){
							$DB->Query('delete from '.$this->globalSettings->getTableRefCoupons().' where coupon="'.$nextCoupon['COUPON'].'";');
							$nextCoupon['COUPON']=$this->getCoupon($keyCoupon, $propKey, $nextCoupon['XML_ID']);
						}
					}
					if(empty($nextCoupon['COUPON'])){
						$nextCoupon['COUPON']=$this->getCoupon($keyCoupon, $propKey, $nextCoupon['XML_ID']);
					}
					$i++;
				}
			}
		}
		return $coupons;
	}

	public function updateCoupon($oldCoupon, $newCoupon){
		global $DB;
		$sqlDel='delete from '.$this->globalSettings->getTableRefCoupons().' where user_id='.$this->idUser.' and rule_id='.$oldCoupon['ID'].' and coupon="'.$DB->ForSql($oldCoupon['COUPON']).'";';
		$newCoupon=$this->getCoupon($oldCoupon['ID'], $oldCoupon['RULE_NUMBER'], $oldCoupon['XML_ID'], $newCoupon);
		if($newCoupon===false){
			return['status'=>'error', 'error'=>$this->error];
		}else{
			$DB->Query($sqlDel);
			$tmpCoupon = \Bitrix\Sale\Internals\DiscountCouponTable::getList(['filter' => ['DISCOUNT_ID' => $oldCoupon['ID'], 'COUPON' => $oldCoupon['COUPON']]])->fetch();
			if($tmpCoupon!==false){
				\Bitrix\Sale\Internals\DiscountCouponTable::delete($tmpCoupon['ID']);
			}
			return['status'=>'succes', 'coupon'=>$newCoupon];
		}
	}
	
	private function checkUserGroup($group){
		$group=explode(',',$group);
		if(count($group)>0){
			$tmpArr=array_intersect($group, \CUser::GetUserGroup($this->idUser));
			if(count($tmpArr)>0){
				return true;
			}
		}
		return false;
	}
	
	private function checkCoupon($coupon){
		$couponIterator = \Bitrix\Sale\Internals\DiscountCouponTable::getList(
			['filter' => ['COUPON' => $coupon]]
		);
		if($coupon2 = $couponIterator->fetch()){
			return true;
		}
		return false;
	}
	
	private function getCoupon($idRule, $keyRule='', $xmlId='', $manualCode=''){
		//if(empty($this->userCodeCoupon)){
			$this->userCodeCoupon='';
			$generateType=$this->moduleOptions['ref_coupon_code'.$keyRule];
			$usArr=\CUser::GetByID($this->idUser);
			$arUser = $usArr->Fetch();
			if($generateType=='user_login'){
				$this->userCodeCoupon=$arUser['LOGIN'];
			}elseif($generateType=='user_xml_id'){
				$this->userCodeCoupon=$arUser['XML_ID'];
			}elseif($generateType=='user_prop' && !empty($this->moduleOptions['ref_coupon_prop'.$keyRule])){
				$tmpCode=$arUser[$this->moduleOptions['ref_coupon_prop'.$keyRule]];
				if(!empty($tmpCode)){
					if(is_array($tmpCode)){
						$this->userCodeCoupon=$tmpCode[0];
					}else{
						$this->userCodeCoupon=$tmpCode;
					}
				}/*else{
					$this->userCodeCoupon=$this->idUser;
				}*/
			}elseif($generateType=='user_id'){
				$this->userCodeCoupon=$this->idUser;
			}
		//}
		if(!empty($this->userCodeCoupon)){
			global $DB;
			$tmpPrefix=\Bitrix\Main\Config\Option::get($this->globalSettings->getModuleId(), 'ref_coupon_withoutprefix'.$keyRule);
			if(!empty($tmpPrefix) && $tmpPrefix=='Y'){
				$coupon=$this->userCodeCoupon;
			}else{
				$xmlId=(empty($xmlId))?\randString(6, ["ABCDEFGHIJKLNMOPQRSTUVWXYZ"]):$xmlId;
				$coupon=$xmlId.'_'.$this->userCodeCoupon;	
			}
			if(!empty($manualCode)){
				$coupon=$manualCode;
			}
			$coupon=substr($coupon, -32);
		
			$addDb = \Bitrix\Sale\Internals\DiscountCouponTable::add([
				'DISCOUNT_ID' => $idRule,
				'COUPON'      => $coupon,
				'TYPE'        => \Bitrix\Sale\Internals\DiscountCouponTable::TYPE_MULTI_ORDER
			]);
			if ($addDb->isSuccess()){
				$DB->Query('insert into '.$this->globalSettings->getTableRefCoupons().' (user_id, rule_id, coupon_id, coupon) VALUES ('.$this->idUser.', '.$idRule.', '.$addDb->getId().', "'.$DB->ForSQL($coupon).'");');
				//Tools::checkUserInSystem($this->idUser);
				return $coupon;
			}else{
				//$errors = $addDb->getErrorMessages();
				$this->error=$addDb->getErrorMessages();
				return false;
			}
		}
	}
	
	public function getUserAccount($currency=0){
		if($currency===0) $currency=$this->moduleOptions['currency'];
		$currencyFormat = \CCurrencyLang::GetFormatDescription($currency); 
		if(!empty($this->idUser)){
			$dbAccountCurrency = \CSaleUserAccount::GetList(['NAME'=>'ASC'], ["USER_ID" => $this->idUser, 'CURRENCY'=>$currency], false, false, ["*"]);
			if($arAccountCurrency = $dbAccountCurrency->Fetch()){
				//$arAccountCurrency['CURRENCY_FORMAT']=str_replace('#','',$currencyFormat['FORMAT_STRING']);
				$arAccountCurrency['CURRENCY_FORMAT']=preg_replace("/^# /","",$currencyFormat['FORMAT_STRING']);
				$arAccountCurrency['AMOUNT_FORMAT']=\CCurrencyLang::CurrencyFormat($arAccountCurrency['CURRENT_BUDGET'],  $arAccountCurrency['CURRENCY']);
				$arAccountCurrency['AMOUNT_FORMAT']=str_replace("'", '"', $arAccountCurrency['AMOUNT_FORMAT']);
				$arAccountCurrency['AMOUNT']=$arAccountCurrency['CURRENT_BUDGET'];
				$accounts=$arAccountCurrency;
			}
		}
		if(empty($accounts)){
			$accounts=[
				'CURRENT_BUDGET'=>0,
				'AMOUNT_FORMAT'=>\CCurrencyLang::CurrencyFormat(0,  $currency),
				'AMOUNT'=>0,
				'CURRENCY'=>$currency,
				'CURRENCY_FORMAT'=>str_replace('#','',$currencyFormat['FORMAT_STRING'])
			];
		}
		return $accounts;
	}
	
	public function getOverdueTransactions(){
		global $DB;
		$overdue=[];
		$res=$DB->Query('select
		    id, bonus_start,bonus,user_id,user_bonus,order_id,currency,profile_type,profile_id,status,'.$DB->DateToCharFunction("date_add").' date_add,'.$DB->DateToCharFunction("date_remove").'date_remove,add_comment,comments,email
		    from '.$this->globalSettings->getTableBonusList().'
		    where user_id='.$this->idUser.' and status="active" and date_remove>now() and currency="'.$this->getCurrency().'" order by id desc');
		if($row = $res->Fetch()){
			$row['AMOUNT_FORMAT']=\CCurrencyLang::CurrencyFormat($row['bonus'],  $row['currency']);
			$overdue=$row;
		}
		return $overdue;
	}
	
	public function getUserTransactions($arNavParams, $arrFilter=[], $orders=['id'=>'desc']){
		global $DB;
		$transactions=[];
		$sortStr='';
		foreach($orders as $sort=>$order){
			switch ($sort){
				case 'id':
					$sortStr.=' ID '.$order;
				break;
				case 'date':
					$sortStr.=' ID '.$order;
				break;
				case 'acc':
					$debitSort=($order=='asc')?'DESC':'ASC';
					$order=($order=='asc')?'DESC':'ASC';
					$sortStr.=' DEBIT '.$debitSort.', AMOUNT '.$order;
				break;
				case 'withdraw':
					$debitSort=($order=='asc')?'DESC':'ASC';
					$sortStr.=' DEBIT '.$debitSort.', AMOUNT '.$order;
				break;
				case 'desc':
					$sortStr.=' DESCRIPTION '.$order;
				break;
				case 'notes':
					$sortStr.=' NOTES '.$order;
				break;
				case 'date_remove':
					$sortStr.=' date_remove '.$order;
				break;
				case 'order_id':
					$sortStr.=' ORDER_ID '.$order;
				break;
			}
		}
		
		$filterStr='(commerce_loyal_bonuses.user_id='.$this->idUser.' or commerce_loyal_bonuses.user_id is null) and b_sale_user_transact.USER_ID='.$this->idUser.' and b_sale_user_transact.CURRENCY="'.$this->moduleOptions['currency'].'"';
		foreach($arrFilter as $keyFilter=>$valFilter){
			switch ($keyFilter){
				case 'DEBIT':
					$filterStr.=' AND b_sale_user_transact.DEBIT="'.$valFilter.'"';
				break;
				case 'DESCRIPTION':
					$filterStr.=' AND b_sale_user_transact.DESCRIPTION="'.$valFilter.'"';
				break;
				case '>=TRANSACT_DATE':
					$filterStr.=' AND b_sale_user_transact.TRANSACT_DATE>='.$DB->CharToDateFunction($valFilter);
				break;
				case '<=TRANSACT_DATE':
					$filterStr.=' AND b_sale_user_transact.TRANSACT_DATE<='.$DB->CharToDateFunction($valFilter);
				break;
			}
		}
		$limit=(!empty($arNavParams['nTopCount']))?' limit '.$arNavParams['nTopCount']:'';

		
		$dbTransactions=$DB->Query('select
			b_sale_user_transact.ID as ID,
			b_sale_user_transact.USER_ID as USER_ID,
			'.$DB->DateToCharFunction("b_sale_user_transact.TRANSACT_DATE").' TRANSACT_DATE,
			b_sale_user_transact.AMOUNT as AMOUNT,
			b_sale_user_transact.CURRENCY as CURRENCY,
			b_sale_user_transact.DEBIT as DEBIT,
			b_sale_user_transact.ORDER_ID as ORDER_ID,
			group_concat(commerce_loyal_bonuses.order_id) as ORDER_ID2,
			b_sale_user_transact.DESCRIPTION as DESCRIPTION,
			b_sale_user_transact.NOTES as NOTES,
			b_sale_order.ACCOUNT_NUMBER as ACCOUNT_NUMBER,
			group_concat(commerce_loyal_bonuses.bonus_start) as bonus_start,
			group_concat('.$DB->DateToCharFunction("commerce_loyal_bonuses.date_add").') as date_add,
			group_concat('.$DB->DateToCharFunction("commerce_loyal_bonuses.date_remove").') as date_remove,
			group_concat(commerce_loyal_bonuses.add_comment) as add_comment

			from b_sale_user_transact
			left join commerce_loyal_bonuses_transaction
			on(commerce_loyal_bonuses_transaction.transaction_id=b_sale_user_transact.ID and b_sale_user_transact.DEBIT="Y")
			left join commerce_loyal_bonuses
			on (commerce_loyal_bonuses_transaction.bonus_id=commerce_loyal_bonuses.id)
			left join b_sale_order
			on (b_sale_order.ID=b_sale_user_transact.ORDER_ID)
			where '.$filterStr.'
			group by b_sale_user_transact.ID
			order by '.$sortStr.$limit.';');

		if(!empty($arNavParams['nPageSize'])){
			$dbTransactions->NavStart($arNavParams['nPageSize'],false);
		}
		$this->res=$dbTransactions;
		while ($transaction = $dbTransactions->Fetch()){
			$transaction['ORDER_ID']=(empty($transaction['ORDER_ID']) && !empty($transaction['ORDER_ID2']))?$transaction['ORDER_ID2']:$transaction['ORDER_ID'];
			$transaction['AMOUNT_FORMAT']=\CCurrencyLang::CurrencyFormat($transaction['AMOUNT'],  $transaction['CURRENCY']);
			$transaction['date_remove']=($transaction['DEBIT']=='N')?'':$transaction['date_remove'];
			$transactions[]=$transaction;
		}
		return $transactions;
	}
	
	public function getUserTotalTransactions($arNavParams, $arrFilter=[]){
		global $DB;
		$transactions=[];
		
		$filterStr=' b_sale_user_transact.USER_ID='.$this->idUser.' and b_sale_user_transact.CURRENCY="'.$this->moduleOptions['currency'].'"';
		foreach($arrFilter as $keyFilter=>$valFilter){
			switch ($keyFilter){
				case 'DEBIT':
					$filterStr.=' AND b_sale_user_transact.DEBIT="'.$valFilter.'"';
				break;
				case 'DESCRIPTION':
					$filterStr.=' AND b_sale_user_transact.DESCRIPTION="'.$valFilter.'"';
				break;
				case '>=TRANSACT_DATE':
					$filterStr.=' AND b_sale_user_transact.TRANSACT_DATE>='.$DB->CharToDateFunction($valFilter);
				break;
				case '<=TRANSACT_DATE':
					$filterStr.=' AND b_sale_user_transact.TRANSACT_DATE<='.$DB->CharToDateFunction($valFilter);
				break;
			}
		}
		$limit=(!empty($arNavParams['nTopCount']))?' limit '.$arNavParams['nTopCount']:'';

		
		$sql='select
			sum(b_sale_user_transact.AMOUNT) as AMOUNT,
			min(b_sale_user_transact.CURRENCY) as CURRENCY,
			b_sale_user_transact.DEBIT as DEBIT

			from b_sale_user_transact
			where '.$filterStr.'
			group by b_sale_user_transact.DEBIT
			;';
		$dbTransactions=$DB->Query($sql);
		$totalArr=[
			'add'=>['AMOUNT'=>0, 'AMOUNT_FORMAT'=>\CCurrencyLang::CurrencyFormat(0, $this->moduleOptions['currency'])],
			'remove'=>['AMOUNT'=>0, 'AMOUNT_FORMAT'=>\CCurrencyLang::CurrencyFormat(0, $this->moduleOptions['currency'])],
			'difference'=>['AMOUNT'=>0, 'AMOUNT_FORMAT'=>\CCurrencyLang::CurrencyFormat(0, $this->moduleOptions['currency'])]
		];
		$tmpCurrency='';
		while ($transaction = $dbTransactions->Fetch()){
			$key=($transaction['DEBIT']=='N')?'remove':'add';
			$transaction['AMOUNT_FORMAT']=\CCurrencyLang::CurrencyFormat($transaction['AMOUNT'],  $transaction['CURRENCY']);
			$totalArr[$key]=$transaction;
			$tmpCurrency=$transaction['CURRENCY'];
		}
		$totalArr['difference']['AMOUNT']=$totalArr['add']['AMOUNT']-$totalArr['remove']['AMOUNT'];
		$totalArr['difference']['AMOUNT_FORMAT']=\CCurrencyLang::CurrencyFormat($totalArr['difference']['AMOUNT'],  $tmpCurrency);
		return $totalArr;
	}
	
	public function getTypeTransactions(){
		global $DB;
		$types=[];
		$res=$DB->Query('select distinct DESCRIPTION from b_sale_user_transact where DESCRIPTION !="" and USER_ID='.$this->idUser.';');
		while($row = $res->Fetch()){
			$types[]=$row['DESCRIPTION'];
		}
		
		return $types;
	}
	
	public function getRefLinkTest($refCode, $refLink, $refProp=''){
		$refId='';
		$rsUser = \CUser::GetByID($this->idUser);
		$arUser = $rsUser->Fetch();
		if($refLink=='ID'){
			$refId=$arUser['ID'];
		}elseif($refLink=='XML_ID'){
			$refId=$arUser['XML_ID'];
		}elseif($refLink=='LOGIN'){
			$refId=$arUser['LOGIN'];
		}
		elseif($refLink=='PROP' && !empty($refProp)){
			$tmpProp=$arUser[$refProp];
			if(is_array($tmpProp)){
				$tmpProp=$tmpProp[0];
			}
			$refId=$tmpProp;
		}
		if(!empty($refId)){
			$typeHTTP = (\CMain::IsHTTPS()) ? "https://" : "http://";
			//return $typeHTTP.$_SERVER['HTTP_HOST'].'/?'.$refCode.'='.$refId;
			return $typeHTTP.$_SERVER['SERVER_NAME'].'/?'.$refCode.'='.$refId;
		}else{
			return '---';
		}
	}
	
	public function getRefLink(){
		$refActive=$this->moduleOptions['ref_link_active'];
		if(!$refActive || $refActive!='Y'){
			return false;
		}
		$refGroups=$this->moduleOptions['ref_link_group'];
		if(!empty($refGroups) && !$this->checkUserGroup($refGroups)){
			return false;
		}
		$refId='';
		$rsUser = \CUser::GetByID($this->idUser);
		$arUser = $rsUser->Fetch();
		if($this->moduleOptions['ref_link_value']=='ID'){
			$refId=$arUser['ID'];
		}elseif($this->moduleOptions['ref_link_value']=='XML_ID'){
			$refId=$arUser['XML_ID'];
		}elseif($this->moduleOptions['ref_link_value']=='LOGIN'){
			$refId=$arUser['LOGIN'];
		}
		elseif($this->moduleOptions['ref_link_value']=='PROP' && !empty($this->moduleOptions['ref_prop'])){
			$tmpProp=$arUser[$this->moduleOptions['ref_prop']];
			if(is_array($tmpProp)){
				$tmpProp=$tmpProp[0];
			}
			$refId=$tmpProp;
		}
		if(!empty($refId)){
			$typeHTTP = (\CMain::IsHTTPS()) ? "https://" : "http://";
			//return $typeHTTP.$_SERVER['HTTP_HOST'].'/?'.$this->moduleOptions['ref_link_name'].'='.$refId;
			return $typeHTTP.$_SERVER['SERVER_NAME'].'/?'.$this->moduleOptions['ref_link_name'].'='.$refId;
		}else{
			return false;
		}
	}
	
	public function getBonuses($users){
		$users[]=0;
		$retBonuses=[];
		global $DB;
		$res=$DB->Query('select sum(bonus_start) as bonus_start, min(user_bonus) as user_bonus, currency from '.$this->globalSettings->getTableBonusList().' where user_id='.$this->idUser.' and user_bonus in ('.implode(',', $users).') and (status="active" or status="used") group by user_bonus;');
		while($row = $res->Fetch()){
			$retBonuses[$row['user_bonus']]=['bonus'=>$row['bonus_start'], 'currency'=>$row['currency']];
		}
		return $retBonuses;
	}
	
	public function sendEmail($request){
		if(!empty($request['type']) && $request['type']=='email'){
			$this->shareEmail($request);
		}elseif(!empty($request['type']) && $request['type']=='coupon'){
			$this->shareCoupon($request);
		}
	}
	
	private function checkEvent(){
		$res=\Bitrix\Main\Mail\Internal\EventTypeTable::getList(
			['filter'=>['EVENT_NAME'=>'COMMERCE_LOYAL_SHARE']]
		);
		if(!$nextRes=$res->fetch()){
			$fieldsEvent=[
				'LID'=>LANGUAGE_ID,
				'EVENT_NAME'=>'COMMERCE_LOYAL_SHARE',
				'NAME'=>Loc::getMessage("commerce.loyaltyprogram_EVENT_SHARE"),
				'DESCRIPTION'=>'
					#EMAIL_TO# - '.Loc::getMessage("commerce.loyaltyprogram_EVENT_SHARE_EMAIL_TO").'
					#REF_LINK# - '.Loc::getMessage("commerce.loyaltyprogram_EVENT_SHARE_REF_LINK").'
					#COUPON# - '.Loc::getMessage("commerce.loyaltyprogram_EVENT_SHARE_COUPON").'
					#NAME# - '.Loc::getMessage("commerce.loyaltyprogram_EVENT_SHARE_NAME").'
				',
				'SORT'=>500
			];
			$fieldsEvent['DESCRIPTION']=preg_replace('/[ ]{2,}|[\t]/', ' ', trim($fieldsEvent['DESCRIPTION']));
			$resAdd=\Bitrix\Main\Mail\Internal\EventTypeTable::add($fieldsEvent);
		}
	}
	
	private function shareCoupon($request){
		$templateId=\Bitrix\Main\Config\Option::get($this->globalSettings->getModuleId(), 'templateShareCoupon', 0);
		$restoreTemplate=false;
		if($templateId==0){
			$restoreTemplate=true;
		}else{
			$res=\Bitrix\Main\Mail\Internal\EventMessageTable::getList(['filter'=>['ID'=>$templateId]]);
			if(!$nextRes=$res->fetch()){
				$restoreTemplate=true;
			}
		}
		if($restoreTemplate){
			$this->checkEvent();
			$sites=array_keys($this->globalSettings->getSites());
			$templateId=\Bitrix\Main\Mail\Internal\EventMessageTable::add([
				'EVENT_NAME'=>'COMMERCE_LOYAL_SHARE',
				'LID'=>$sites[0],
				'ACTIVE'=>'Y',
				'EMAIL_FROM'=>'#DEFAULT_EMAIL_FROM#',
				'EMAIL_TO'=>'#EMAIL_TO#',
				'SUBJECT'=>'#SITE_NAME#: '.Loc::getMessage("commerce.loyaltyprogram_EVENT_SHARE_TMPLT_COUPON"),
				'MESSAGE'=>$this->globalSettings->getEmailTemplate('share_coupon'),
				'BODY_TYPE'=>'html'
			]);
			$templateId=$templateId->getId();
			$tmpSites=array_keys($this->globalSettings->getSites());
			foreach($tmpSites as $nextSite){
				\Bitrix\Main\Mail\Internal\EventMessageSiteTable::add([
					'EVENT_MESSAGE_ID'=>$templateId,
					'SITE_ID'=>$nextSite
				]);
			}
			\Bitrix\Main\Config\Option::set($this->globalSettings->getModuleId(), 'templateShareCoupon', $templateId);
		}
		\Bitrix\Main\Mail\Event::send([
			"EVENT_NAME" => "COMMERCE_LOYAL_SHARE",
			"MESSAGE_ID" => $templateId,
			"LID" => SITE_ID,
			"C_FIELDS" => [
				"EMAIL_TO" => $request['emailto'],
				"COUPON" => $request['coupon'],
				"NAME" => $request['name']
			],
		]);
	}
	
	private function shareEmail($request){
		$templateId=\Bitrix\Main\Config\Option::get($this->globalSettings->getModuleId(), 'templateShareEmail', 0);
		$restoreTemplate=false;
		if($templateId==0){
			$restoreTemplate=true;
		}else{
			$res=\Bitrix\Main\Mail\Internal\EventMessageTable::getList(['filter'=>['ID'=>$templateId]]);
			if(!$nextRes=$res->fetch()){
				$restoreTemplate=true;
			}
		}
		if($restoreTemplate){
			$this->checkEvent();
			$sites=array_keys($this->globalSettings->getSites());
			$templateId=\Bitrix\Main\Mail\Internal\EventMessageTable::add([
				'EVENT_NAME'=>'COMMERCE_LOYAL_SHARE',
				'LID'=>$sites[0],
				'ACTIVE'=>'Y',
				'EMAIL_FROM'=>'#DEFAULT_EMAIL_FROM#',
				'EMAIL_TO'=>'#EMAIL_TO#',
				'SUBJECT'=>'#SITE_NAME#: '.Loc::getMessage("commerce.loyaltyprogram_EVENT_SHARE_TMPLT_REF_LINK"),
				'MESSAGE'=>$this->globalSettings->getEmailTemplate('share_ref_link'),
				'BODY_TYPE'=>'html'
			]);
			$templateId=$templateId->getId();
			$tmpSites=array_keys($this->globalSettings->getSites());
			foreach($tmpSites as $nextSite){
				\Bitrix\Main\Mail\Internal\EventMessageSiteTable::add([
					'EVENT_MESSAGE_ID'=>$templateId,
					'SITE_ID'=>$nextSite
				]);
			}
			\Bitrix\Main\Config\Option::set($this->globalSettings->getModuleId(), 'templateShareEmail', $templateId);
		}
		\Bitrix\Main\Mail\Event::send([
			"EVENT_NAME" => "COMMERCE_LOYAL_SHARE",
			"MESSAGE_ID" => $templateId,
			"LID" => SITE_ID,
			"C_FIELDS" => [
				"EMAIL_TO" => $request['emailto'],
				"REF_LINK" => $this->getRefLink(),
				"NAME" => $request['name']
			],
		]); 
	}
	
	//write off block
	public function writeOffList(){
		$list=['SUCCESS'=>0];
		global $DB;
		//$res=$DB->Query('select sum(bonus) as total_bonus, status from '.$this->globalSettings->getTableWriteOff().' where user_id='.$this->idUser.' group by status;');
		$res=$DB->Query('select 
            sum(commerce_loyal_write_off.bonus) as total_bonus,
            commerce_loyal_write_off.status as status
            from '.$this->globalSettings->getTableWriteOff().'
            left join b_sale_user_transact on (b_sale_user_transact.id=commerce_loyal_write_off.transact_id)
            where commerce_loyal_write_off.user_id=1 and b_sale_user_transact.CURRENCY="'.$this->getCurrency().'"
            group by commerce_loyal_write_off.status;');
		while($row = $res->Fetch()){
			if($row['status']=='execute'){
				$list['SUCCESS']=$row['total_bonus'];
				$list['SUCCESS_FORMAT']=\CCurrencyLang::CurrencyFormat($row['total_bonus'],  $this->getCurrency());
			}
		}
		return $list;
	}

	/*public function getWriteOffCart(){
		$cart='';
		global $DB;	
		$res=$DB->Query('select cart_number from '.$this->globalSettings->getTableUserRequisites().' where user_id='.$this->idUser.';');
		if($row = $res->Fetch()){
			$cart=$row['cart_number'];
		}
		return $cart;
	}*/
	
	public function getWriteOffBonus(){
		$bonus=false;
		$activeProgramIds=Profiles\Profile::getActiveProfileByType('Writeoff');
		foreach($activeProgramIds as $nextProgramId){
			$writeOffClass=Profiles\Profile::getProfileById($nextProgramId);
            $writeOffClass->setCurrency($this->getCurrency());
				$bonus=$writeOffClass->getMaxBonus();
				if($this->moduleOptions['ref_perform_all']=='N'){
					break;
				}
		}
		if($bonus!==false){
			$minBonus=$writeOffClass->getMinBonus();
			if($minBonus>$bonus){
				$bonus=false;
			}else{
				$bonus=[
					'BONUS'=>$bonus,
					'BONUS_FORMAT'=>\CCurrencyLang::CurrencyFormat($bonus,  $this->getCurrency()),
                    'CURRENCY'=>$this->getCurrency(),
					'MIN_BONUS'=>$minBonus,
					'MIN_BONUS_FORMAT'=>\CCurrencyLang::CurrencyFormat($minBonus,  $this->getCurrency()),
					'IS_ALREADY_REQUEST'=>$writeOffClass->isAlreadyRequest()
				];
				if($bonus['IS_ALREADY_REQUEST']!==false){
					$bonus['IS_ALREADY_REQUEST']=[
						'BONUS'=>$bonus['IS_ALREADY_REQUEST']['bonus'],
						'BONUS_FORMAT'=>\CCurrencyLang::CurrencyFormat($bonus['IS_ALREADY_REQUEST']['bonus'],  $this->getCurrency()),
						'DATE_ORDER'=>$bonus['IS_ALREADY_REQUEST']['date_order']
					];
				}
			}
		}
		return $bonus;
	}
	
	public function setWriteOffBonus($bonus, $regId, $currency=''){
		if(empty((int) $regId)){
			return false;
		}
        $currency=empty($currency)?$this->getCurrency():$currency;
		
		//if invalid requisite id
		global $DB;
		$select='select * from '.$this->globalSettings->getTableUserRequisites().' where id='.$regId.';';
		$rsData = $DB->Query($select);
		if(!$row = $rsData->Fetch()){
			return false;
		}
		
		$maxBonus=0;
		$activeProgramIds=Profiles\Profile::getActiveProfileByType('Writeoff');
		foreach($activeProgramIds as $nextProgramId){
			$writeOffClass=Profiles\Profile::getProfileById($nextProgramId);
            $writeOffClass->setCurrency($currency);
				$maxBonus=$writeOffClass->getMaxBonus();
				if($this->moduleOptions['ref_perform_all']=='N'){
					break;
				}
		}
		$tmpBonus=$this->getWriteOffBonus();
		if($maxBonus>0 && $maxBonus>=$bonus && $tmpBonus['IS_ALREADY_REQUEST']==false){
			$writeOffClass->writeBonus($bonus, $regId);
			return true;
		}
		return false;
	}
	
	//partner sites
	
	public function getPartnerSiteList($selectData=[]){
		global $DB;
		$sites=[];
		$where='1=1';
		if(!empty($selectData['filter'])){
			foreach($selectData['filter'] as $keyFilter=>$valFilter){
				$where.=' and '.$keyFilter.'="'.$DB->ForSql($valFilter).'"';
			}
			$order=(!empty($selectData['order']))?' order by '.$selectData['order']['by'].' '.$selectData['order']['order']:' order by id desc';
			$select='select *, '.$DB->DateToCharFunction("date_confirm").' date_confirm from '.$this->globalSettings->getTablePartnerSiteList().' where '.$where.$order.';';
			$rsData = $DB->Query($select);
			while($row = $rsData->Fetch()){
				$sites[]=$row;
			}
		}
		return $sites;
	}
	
	public function addPartnerSite($siteName){
		global $DB;
		$siteName=self::clearPartnerSite($siteName);
		$siteName=$DB->ForSQL($siteName);
		$sites=$this->getPartnerSiteList([
			'filter'=>['site'=>$siteName, 'confirmed'=>'Y']
		]);
		$sites2=$this->getPartnerSiteList([
			'filter'=>['site'=>$siteName, 'user_id'=>$this->idUser]
		]);
		if(count($sites)>0 || count($sites2)>0){
			return false;
		}else{
			$id=$DB->Insert($this->globalSettings->getTablePartnerSiteList(),[
				'user_id'=>$this->idUser,
				'site'=>'"'.$siteName.'"',
				'code'=>'"'.md5($_SERVER['HTTP_HOST'].$siteName.$this->idUser).'"'
			]);
			if($id>0){
				return $sites=$this->getPartnerSiteList([
					'filter'=>['user_id'=>$this->idUser],
					'order'=>['by'=>'id', 'order'=>'desc']
				]);
			}else{
				return false;
			}
		}
		return $siteName;
	}
	
	public static function clearPartnerSite($site){
		$site=urldecode($site);
		$site=str_ireplace(['http://', 'https://'], ['',''], strtolower($site));
		$site='http://'.$site;
		$site=rtrim($site, '/');
		return $site;
	}
	
	public function checkPartnerSite($id){
		global $DB;
		$id=(int) $id;
		if(!empty($id)){
			$rsData = $DB->Query('select * from '.$this->globalSettings->getTablePartnerSiteList().' where user_id='.$this->idUser.' and id='.$id.';');
			if($row = $rsData->Fetch()){
				$url=$row['site'].'/'.$row['code'].'.txt';
				$ch = curl_init($url);
				curl_setopt($ch, CURLOPT_TIMEOUT, 5);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                $url2=str_replace('http:','https:', $url);
                $ch2 = curl_init($url2);
                curl_setopt($ch2, CURLOPT_TIMEOUT, 5);
                curl_setopt($ch2, CURLOPT_CONNECTTIMEOUT, 5);
                curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
                curl_exec($ch2);
				$httpCode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
				curl_close($ch2);

				if($httpCode == 200 || $httpCode2 == 200) {
					$rsData = $DB->Query('update '.$this->globalSettings->getTablePartnerSiteList().' set confirmed="Y", date_confirm=NOW() where user_id='.$this->idUser.' and id='.$id.';');
					return true;
				}
			}
		}
		return false;
	}
	
	public function deletePartnerSite($id, $userId=0){
		global $DB;
		$id=(int) $id;
		$userId=$userId==0?$this->idUser:$userId;
		$rsData = $DB->Query('select * from '.$this->globalSettings->getTablePartnerSiteList().' where user_id='.$userId.' and id='.$id.';');
		if($row = $rsData->Fetch()){
			$rsData = $DB->Query('delete from '.$this->globalSettings->getTablePartnerSiteList().' where id='.$id.';');
			return true;
		}
		return false;
	}

}

?>