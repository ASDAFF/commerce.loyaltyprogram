<?
namespace Commerce\Loyaltyprogram;
use \Bitrix\Main\Application,
	Bitrix\Main\Localization\Loc;
/**
* tools
*/
class Tools {
	
	
	/**
	* returns the period and the next day, depending on the specified period and date (if not required)
	*
	* the type field can contain values week, month, quarter, year.
	* the cData field can timestamp or false.
	*/
	public static function getPeriod($type='month', $cData=false){
		if(!$cData){$cData=time();}
		$nextData=$periodStart=$periodEnd=$cData;
		$date = new \DateTime();
		$date->setTimestamp($cData);
		$quarterArr=[0,4,4,4,7,7,7,10,10,10,1,1,1];
		switch ($type){
			case 'week':
			$nextData=strtotime('next monday');
			$date->setTimestamp($nextData);
			$date->modify('-1 day');
			$periodEnd=$date->getTimestamp();
			$date->modify('-6 day');
			$periodStart=$date->getTimestamp();
			break;
			case 'month':
			$nextData=mktime(0, 0, 0, date("n")+1, 1, date("Y"));
			$date->setTimestamp($nextData);
			$date->modify('-1 day');
			$periodEnd=$date->getTimestamp();
			$date->modify('-1 month +1 day');
			$periodStart=$date->getTimestamp();
			break;
			case 'year':
			$nextData=mktime(0, 0, 0, 1, 1, date("Y")+1);
			$date->setTimestamp($nextData);
			$date->modify('-1 day');
			$periodEnd=$date->getTimestamp();
			$date->modify('-1 year +1 day');
			$periodStart=$date->getTimestamp();
			break;
			case 'quarter':
			$nextData=mktime(0, 0, 0, $quarterArr[date("n")], 1, date("Y"));
			$date->setTimestamp(mktime(0, 0, 0, $quarterArr[date("n")], 0, date("Y")));
			$periodEnd=$date->getTimestamp();
			$date->modify('-3 month');
			$periodStart=mktime(0, 0, 0, $date->format('m')+1, 1, date("Y"));
			break;
		}
		$date->setTimestamp($periodEnd);
		$date->modify('+1 day');
		return [
			'currentDate'=>['unixTime'=>$cData, 'format'=>\ConvertTimeStamp($cData, "SHORT", LANGUAGE_ID)],
			'dateFrom'=>['unixTime'=>$periodStart, 'format'=>\ConvertTimeStamp($periodStart, "SHORT", LANGUAGE_ID)],
			'dateTo'=>['unixTime'=>$periodEnd, 'format'=>\ConvertTimeStamp($periodEnd, "SHORT", LANGUAGE_ID)],
			'afterPeriod'=>['unixTime'=>$date->getTimestamp(), 'format'=>\ConvertTimeStamp($date->getTimestamp(), "SHORT", LANGUAGE_ID)]
		];
    }
    
    public static function existUSer($userId){
        $userId= (int) $userId;
        if(!empty($userId)){
            global $USER;
            $rsUser = \CUser::GetByID($userId);
            $arUser = $rsUser->Fetch();
            if($arUser!=false){
                return true;
            }
        }
        return false;
    }
	
	/**
	* returns the full user name as string
	* $row include keys:LAST_NAME, NAME,SECOND_NAME, LOGIN
	* @param array $row 
	*/
	public static function getUserName($row){
		$tmpName=[];
		if(!empty($row['LAST_NAME'])){
			$tmpName[]=$row['LAST_NAME'];
		}
		if(!empty($row['NAME'])){
			$tmpName[]=$row['NAME'];
		}
		if(!empty($row['SECOND_NAME'])){
			$tmpName[]=$row['SECOND_NAME'];
		}
		return (count($tmpName)>0)?implode(' ',$tmpName):$row['LOGIN'];
	}
	
	public static function getUserData($userId){
		global $USER;
		$rsUser = \CUser::GetByID($userId);
		$arUser = $rsUser->Fetch();
		$tmpName=[];
		if(!empty($arUser['LAST_NAME'])){
			$tmpName[]=$arUser['LAST_NAME'];
		}
		if(!empty($arUser['NAME'])){
			$tmpName[]=$arUser['NAME'];
		}
		if(!empty($arUser['SECOND_NAME'])){
			$tmpName[]=$arUser['SECOND_NAME'];
		}
		$arUser['FULL_NAME']=(count($tmpName)>0)?implode(' ',$tmpName):$arUser['LOGIN'];
		return $arUser;
	}
	
	public static function getLastAction(){
		global $DB;
		$maxId=0;
		$sql='select max(id) as last_id from '.Settings::getInstance()->getTableActionList().';';
		$results=$DB->Query($sql);
		if($res = $results->Fetch()){
			$maxId=$res['last_id'];
		}
		$maxId++;
		$results=$DB->Query('insert into '.Settings::getInstance()->getTableActionList().' set id='.$maxId.';');
		return $maxId;
	}
	
	public static function clearBonusByUser($userId){
		global $DB;
		$userId=(int) $userId;
		if($userId>0){
			$sql='select * from '.Settings::getInstance()->getTableBonusList().' where user_id='.$userId.';';
			$results=$DB->Query($sql);
			$transactIDS=[];
			while($res = $results->Fetch()){
				$transactIDS[]=$res['id'];
			}
			$DB->Query('delete from '.Settings::getInstance()->getTableBonusList().' where user_id='.$userId.';');
			echo 'delete from '.Settings::getInstance()->getTableBonusList().' where user_id='.$userId.';';
			if(count($transactIDS)>0){
				echo 'delete from '.Settings::getInstance()->getTableTransactionList().' where bonus_id in ('.implode(',',$transactIDS).');';
				$DB->Query('delete from '.Settings::getInstance()->getTableTransactionList().' where bonus_id in ('.implode(',',$transactIDS).');');
			}
			return true;
		}
		return false;
	}
	
	/**
	* the function checks that the user is included in the referral system (and if not - add)
	* @param ineger $idUser user id
	*/
	public static function checkUserInSystem($idUser){
		global $DB;
		$results=$DB->Query('select * from '.Settings::getInstance()->getTableUsersList().' where user='.$idUser.';');
		if(!$res = $results->Fetch()){
			$ref=new Referrals;
			$ref->setReferral2(0, $idUser, 'simple');
		}
	}
	
	
	public static function SMSActive(){
		$smsActive = \COption::GetOptionString("main", "sms_default_service", "");
		return !empty($smsActive);
	}
	
	
	//type link
	public static function getAllTypeLinkList(){
		return [
			1=>['code'=>'simple', 'name'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_typeLinkSimple")],
			2=>['code'=>'link', 'name'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_typeLinkRefLink")],
			3=>['code'=>'coupon', 'name'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_typeLinkCoupons")],
			4=>['code'=>'manual', 'name'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_typeLinkManual")],
			5=>['code'=>'import', 'name'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_typeLinkImport")],
            6=>['code'=>'partnerSite', 'name'=>Loc::getMessage("commerce.loyaltyprogram_CONDITION_LABEL_typeLinkPartnerSite")]
		];
	}
	/**
	* the function return all type users (simple[1], link[2], coupon[3] , manual[4], import[5], partnerSite[6], etc.)
	* @param array $withoutArr array with values ​​that do not need to be shown
	*/
	public static function getTypeLinkList(array $withoutArr=[]){
		$newList=[];
		foreach(self::getAllTypeLinkList() as $keyType=>$nextType){
			if(!in_array($nextType['code'], $withoutArr)){
				$newList[$keyType]=$nextType['name'];
			}
		}
		return $newList;
	}
	
	/**
	* the function return id type user link (simple[1], link[2], coupon[3] , manual[4], import[5], partnerSite[6], etc.)
	* @param integer $idUser user id
	* return integer type link (1=simple, 2=link, etc.)
	*/
	public static function getIdTypeLinkUser($userId){
		if(empty($userId)){
			return false;
		}
		global $DB;
		$select='select * from '.Settings::getInstance()->getTableUsersList().' where user='.$userId.';';
		$rsData = $DB->Query($select);
		if($row = $rsData->Fetch()){
			foreach(self::getAllTypeLinkList() as $keyType=>$nextType){
				if($nextType['code']==$row['type']){
					return $keyType;
				}
			}
		}
		return 0;
	}
	
	
	/**
	* @param string $bonus 
	* return normalize bonus (23.67 instead 23.6700, 23 instead 23.0000)
	*/
	public static function roundBonus($bonus){
		$bonus=(float) $bonus;
		if((float)((int) $bonus)<$bonus){
			return round($bonus, 2);
		}
		return round($bonus);
	}

    public static function priceFormat($num){
        $num=(empty($num))?0:$num;
        $options=Settings::getInstance()->getOptions();
        $currency=empty($options['currency'])?'RUB':$options['currency'];
        return \CurrencyFormat($num, $currency);
    }

    public static function numberFormat($num, $accuracy=0){
        return number_format(floatval($num), $accuracy, ".", " ");
    }

	
	/**
	* the function clear coupon from order if coupon if this is a personal referral coupon
	* @param object $order order
	*/
	public static function controlCoupon($order=false){
		$userId=0;
		global $USER;
		if($order==false && $USER->IsAuthorized()){
			$userId=$USER->GetID();
		}elseif($order!==false){
			$userId=$order->getUserId();
		}
	
		if($userId>0){
			\Bitrix\Main\Loader::includeModule('sale');
			$publicUsed=\Bitrix\Sale\DiscountCouponsManager::usedByClient();//if not admin used
			if($publicUsed){
				$userCoupons=[];
				$result = \Commerce\Loyaltyprogram\Entity\CouponsTable::getList(['filter'=>['user_id'=>$userId]]);
				while ($row = $result->fetch()){
					$userCoupons[]=$row['coupon_id'];
				}
				if(count($userCoupons)>0){
					if($order===false){
						$couponList=\Bitrix\Sale\DiscountCouponsManager::get();
					}else{
						$couponList=[];
						$discountData = $order->getDiscount()->getApplyResult();
						if(!empty($discountData['COUPON_LIST'])){
							foreach($discountData['COUPON_LIST'] as $nextCoupon){
								$couponList[]=['ID'=>$nextCoupon['COUPON_ID'], 'COUPON'=>$nextCoupon['COUPON']];
							}
						}
					}
					
					$addCoupons=[];
					$updateStatus=false;
					foreach($couponList as $nextCoupon){
						if(in_array($nextCoupon['ID'], $userCoupons)){
							$addCoupons[]=$nextCoupon['COUPON'].'_NOT!';
							$updateStatus=true;
						}else{
							$addCoupons[]=$nextCoupon['COUPON'];
						}
					}
					if($updateStatus===true){
						if($order===false){
							$basket = \Bitrix\Sale\Basket::loadItemsForFUser(\Bitrix\Sale\Fuser::getId(),\Bitrix\Main\Context::getCurrent()->getSite());
							\Bitrix\Sale\DiscountCouponsManager::clear(true);
						}else{
							$basket = $order->getBasket();
							$discount = $order->getDiscount();
							//\Bitrix\Sale\DiscountCouponsManager::init(\Bitrix\Sale\DiscountCouponsManager::MODE_ORDER, ['userId'=>$userId, 'orderId'=>$order->getId()], true);
							\Bitrix\Sale\DiscountCouponsManager::clear(true);
							\Bitrix\Sale\DiscountCouponsManager::clearApply(true);
							\Bitrix\Sale\DiscountCouponsManager::useSavedCouponsForApply(true);
						}
						foreach($addCoupons as $nextNewCoupon){
							\Bitrix\Sale\DiscountCouponsManager::add($nextNewCoupon);
							\Bitrix\Sale\DiscountCouponsManager::setApply($nextNewCoupon,$basket);
						}
						if($order!==false){
							$discount->setOrderRefresh(true);
							$discount->setApplyResult(array());
							$basket->refreshData(array('PRICE', 'COUPONS'));
							$discount->calculate();
							$order->save();
						}else{
							$basket->save();
						}
					}
				}
			}
		}
	}
	
	/**
	* @param string $type short|full 
	* return array catalogs view [id=>name](short) or [id=>iblockArray](full)
	*/
	public static function getCatalogs($type='short'){
		$blocks=[];
		$results=\CCatalog::GetList(['SORT'=>'NAME']);
		while($res = $results->Fetch()){
			if($type=='short'){
				$blocks[$res['ID']]=$res['NAME'];
			}else{
				$blocks[$res['ID']]=$res;
			}
		}
		return $blocks;
	}
		
	

}

?>