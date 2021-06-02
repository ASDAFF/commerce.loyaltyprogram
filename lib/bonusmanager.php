<?
namespace Commerce\Loyaltyprogram;
use \Bitrix\Main\Localization\Loc;

\Bitrix\Main\Loader::includeModule('sale');
class BonusManager{
	
    public function bonus_payment($bonus=false,$user_id=false,$currency='RUB',$order_id=0,$debit='N'){
        if($bonus===false||$user_id===false||$order_id===0) return false;
        global $DB;
        $settings=Settings::getInstance();
        $res=$DB->Query('select *, if(isnull(date_remove), 1, 0) as is_date_remove_null from '.$settings->getTableBonusList().' where status="active" and user_id="'.$user_id.'" and currency="'.$currency.'" order by is_date_remove_null asc, date_remove asc, id asc;');
        $mass_for_add_transaction=[];
        $tmpBonus=$bonus;
        while($r=$res->Fetch()){
            if($tmpBonus>=$r['bonus']){
				$tmpBonus-=$r['bonus'];
				$DB->Update($settings->getTableBonusList(), [
					'bonus'=>'"0"',
					'status'=>'"used"',
				], "where id='".$r['id']."'", $err_mess.__LINE__);
                $mass_for_add_transaction[]=$r['id'];
			}else{
				$tmpOst = (float)$r['bonus']-$tmpBonus;
				$tmpBonus=0;
				$DB->Update($settings->getTableBonusList(), [
					'bonus'=>'"'.$tmpOst.'"',
				], "where id='".$r['id']."'", $err_mess.__LINE__);
                $mass_for_add_transaction[]=$r['id'];
			}
			if($tmpBonus==0){
				break;
			}
        }
        
        if($debit=='Y'){
			$bonus = 0 - (float)$bonus;
            \CSaleUserAccount::UpdateAccount(
                $user_id,
                $bonus,
                $currency,
                //"commerce_bonus_payment".$order_id,
                "COMMERCE_LOYAL_ORDERPAY",
                $order_id,
                Loc::getMessage("commerce.loyaltyprogram_PROGRAM_BONUSPAY", ["#NUM#"=>abs($bonus)])
            );
			$bonus = abs($bonus);
        }
        
        $transact=\CSaleUserTransact::GetList(['ID'=>'DESC'], [
			'AMOUNT'=>$bonus,
			'CURRENCY'=>$currency,
            'DEBIT'=>'N',
			//'DESCRIPTION'=>"commerce_bonus_payment".$order_id,
			'USER_ID'=>$user_id,
            'ORDER_ID'=>$order_id
        ]);
        $tran = $transact->fetch();
        if($tran !== false && count($mass_for_add_transaction)>0){
            BonusManager::add_transaction($mass_for_add_transaction,$tran['ID']);
        }
    }
						
	public function bonus_payment_refund($userId='0',$orderId='0',$currency='RUB',$summ='0'){
        if($orderId===0||$userId===0||$summ==0)     return false;
        if(empty($options['bonus_as_discount']) || $options['bonus_as_discount']=='N'){
            global $DB;
            $settings=Settings::getInstance();
            $options=$settings->getOptions();
            $tmpForRefundLater = 0;
            $amount = (float)$summ;
            $res=$DB->Query('select * from '.$settings->getTableBonusList().' where user_id=\''.$userId.'\' order by id;');
            while($r=$res->Fetch()){
                $difference = (float)$r['bonus_start']-(float)$r['bonus'];
                if((float)$amount==0 || $amount<0){break;}
                if((float)$amount>=(float)$difference){
                    $amount-=(float)$difference;
                    $inputBonus = (float)$r['bonus']+(float)$difference;
                    $status = $r['status'];
                    if($r['bonus_start']==$inputBonus){
                        if($status=="used") {$status='active';}
                        if($status=='overdue') $tmpForRefundLater+=(float)$difference;
                    }
                    $DB->Update($settings->getTableBonusList(), [
                        'bonus'=>'"'.(float)$inputBonus.'"',
                        'status'=>'"'.$status.'"',
                    ], "where id='".$r['id']."'", $err_mess.__LINE__);
                }else{
                    $amount-=(float)$difference;
                    $inputBonus = (float)$r['bonus']+(float)$amount;
                    $status = $r['status'];
                    if($status=='overdue') $tmpForRefundLater+=(float)$difference;
                    $DB->Update($settings->getTableBonusList(), [
                        'bonus'=>'"'.(float)$inputBonus.'"',
                    ], "where id='".$r['id']."'", $err_mess.__LINE__);
                }
            }
            if($tmpForRefundLater>0){
                $cale = \CSaleUserAccount::GetByUserID($userId,$currency);
                if((float)$cale['CURRENT_BUDGET']>=$tmpForRefundLater){
                    $tmpForRefundLater=0-(float)$tmpForRefundLater;
                    \CSaleUserAccount::UpdateAccount(
                        $userId,
                        $tmpForRefundLater,
                        $currency,
                        "COMMERCE_LOYAL_BONUSREFUND_LATER",
                        $orderId,
                        Loc::getMessage("commerce.loyaltyprogram_PROGRAM_BONUSREFUND_LATER")
                    );
                    return;
                }elseif(((float)$cale['CURRENT_BUDGET']<(float)$tmpForRefundLater)&&(float)$cale['CURRENT_BUDGET']>0){
                    $tmpForRefundLater=(float)$tmpForRefundLater-(float)$cale['CURRENT_BUDGET'];
                    \CSaleUserAccount::UpdateAccount(
                        $userId,
                        $tmpForRefundLater,
                        (float)$cale['CURRENT_BUDGET'],
                        "COMMERCE_LOYAL_BONUSREFUND_LATER",
                        $orderId,
                        Loc::getMessage("commerce.loyaltyprogram_PROGRAM_BONUSREFUND_LATER")
                    );
                }
                if($tmpForRefundLater>0){
                    $tmpForRefundLater=0-(float)$tmpForRefundLater;
                    $DB->Query('insert into '.$settings->getTableBonusList().' (
                        bonus_start,
                        bonus,
                        user_id,
                        order_id,
                        currency,
                        profile_type,
                        profile_id,
                        status,
                        date_remove,
                        add_comment
                    ) values (
                        '.$tmpForRefundLater.',
                        '.$tmpForRefundLater.',
                        '.$userId.',
                        '.$orderId.'.
                        \''.$currency.'\',
                        \'order_payment_overdue\',
                        0,
                        \'order_payment_overdue_start\',
                        FROM_UNIXTIME('.time().'),
                        \''.Loc::getMessage("commerce.loyaltyprogram_PROGRAM_BONUSREFUND_LATER").'\'
                    );');
                    return;
                }
            }
        }else{
            return;
        }
    }

    public function bonus_ordering_refund($order_id=0,$user_id=0,$currency='RUB'){
        if($order_id===0||$user_id===0)     return false;
        global $DB;
        //$res=$DB->Query('select * from '.Settings::getInstance()->getTableBonusList().' where user_id='.$user_id.' and currency="'.$currency.'" and order_id='.$order_id.' and profile_type="Ordering";');
        $res=$DB->Query('select * from '.Settings::getInstance()->getTableBonusList().' where user_id='.$user_id.' and currency="'.$currency.'" and order_id='.$order_id.';');
        $bonus_refund=0;
        while($r=$res->fetch()){
            if($r['status']=='inactive'){
                $comments=(empty($r['comments']))?[]:explode('###', $r['comments']);
				$comments[]=Loc::getMessage("commerce.loyaltyprogram_BONUS_OVERDUE_CANCEL", ["#NUM#"=>$r['bonus']]);
                $DB->Update(Settings::getInstance()->getTableBonusList(), [
                    'status'=>'"overdue"',
                    'comments'=>'"'.$DB->ForSql(implode('###', $comments)).'"'
                ], "where id='".$r['id']."'", $err_mess.__LINE__);
            }elseif($r['status']=='active' && $r['bonus_start']>0){
                $bonus_refund+=$r['bonus_start'];
                $comments=(empty($r['comments']))?[]:explode('###', $r['comments']);
				$comments[]=Loc::getMessage("commerce.loyaltyprogram_BONUS_OVERDUE_CANCEL", ["#NUM#"=>$r['bonus']]);
                $DB->Update(Settings::getInstance()->getTableBonusList(), [
                    'status'=>'"overdue"',
                    'comments'=>'"'.$DB->ForSql(implode('###', $comments)).'"'
                ], "where id='".$r['id']."'", $err_mess.__LINE__);
            }
        }
        if($bonus_refund>0){
            \CSaleUserAccount::UpdateAccount(
                $user_id,
                '-'.$bonus_refund,
                $currency,
                "COMMERCE_LOYAL_BONUSREFUND",
                $order_id,
                Loc::getMessage("commerce.loyaltyprogram_PROGRAM_BONUSREFUND_ORDERING", ["#NUM#"=>$bonus_refund])
            );
        }
    }
	
    public function bonus_refund($order_id=0,$user_id=0,$currency='RUB'){
        if($order_id===0||$user_id===0)     return false;
        global $DB;
        $settings=Settings::getInstance();
        $options=$settings->getOptions();
        //return ordering profile bonus
        BonusManager::bonus_ordering_refund($order_id,$user_id,$currency);
		if(empty($options['bonus_as_discount']) || $options['bonus_as_discount']=='N'){
			return;
        }
        $transact=\CSaleUserTransact::GetList(['ID'=>'DESC'], [
			'CURRENCY'=>$currency,
            'DEBIT'=>'N',
			'USER_ID'=>$user_id,
            'ORDER_ID'=>$order_id,
			'!DESCRIPTION'=>'COMMERCE_LOYAL_BONUSREFUND'
		]);
        if($t=$transact->Fetch()){
            $amount = $t['AMOUNT'];
            $res=$DB->Query('select * from '.$settings->getTableTransactionList().' where transaction_id="'.$t['ID'].'";');
            $bonuses = [];
            while($r=$res->fetch()){
                $bonuses[]=$r['bonus_id'];
            }
            $tmpBonuses = [];
            $tmpAmout = $amount;
            if(!empty($bonuses) && count($bonuses)>0){
                $tmpForRefundLater = 0;
                $res=$DB->Query('select * from '.$settings->getTableBonusList().' where id in ('.implode(',',$bonuses).') order by id;');
                while($r=$res->Fetch()){
                    $difference = $r['bonus_start']-$r['bonus'];
                    if($tmpAmout==0){break;}
                    if($tmpAmout>=$difference){
                        $tmpBonuses[]=$r['id'];
                        $tmpAmout-=$difference;
                        $inputBonus = $r['bonus']+$difference;
                        $status = $r['status'];
                        if($r['bonus_start']==$inputBonus){
                            if($status=="used") {$status='active';}
                            if($status=='overdue') $tmpForRefundLater+=$difference;
                        }
                        $DB->Update($settings->getTableBonusList(), [
                        	'bonus'=>'"'.$inputBonus.'"',
                        	'status'=>'"'.$status.'"',
                        ], "where id='".$r['id']."'", $err_mess.__LINE__);
                    }else{
                        $tmpBonuses[]=$r['id'];
                        $tmpAmout-=$difference;
                        $inputBonus = $r['bonus']+$tmpAmout;
                        $status = $r['status'];
                        if($status=='overdue') $tmpForRefundLater+=$difference;
                        $DB->Update($settings->getTableBonusList(), [
                        	'bonus'=>'"'.$inputBonus.'"',
                        ], "where id='".$r['id']."'", $err_mess.__LINE__);
                    }
                }
				if($amount>0){
                   
                        \CSaleUserAccount::UpdateAccount(
                            $user_id,
                            $amount,
                            $currency,
                            "COMMERCE_LOYAL_BONUSREFUND",
                            $order_id,
                            Loc::getMessage("commerce.loyaltyprogram_PROGRAM_BONUSREFUND", ["#NUM#"=>round($amount,2)])
                        );
                        $transact_new=\CSaleUserTransact::GetList(['ID'=>'DESC'], [
                            'AMOUNT'=>$amount,
                            'CURRENCY'=>$currency,
                            'DEBIT'=>'Y',
                            'DESCRIPTION'=>"COMMERCE_LOYAL_BONUSREFUND",
                            'USER_ID'=>$user_id,
                            'ORDER_ID'=>$order_id
                        ]);
                        if($tran_new = $transact_new->fetch()){
                           BonusManager::add_transaction($tmpBonuses,$tran_new['ID']);
                        }
                    
				}
            }else{
                \CSaleUserAccount::UpdateAccount(
                    $user_id,
                    $amount,
                    $currency,
                    "COMMERCE_LOYAL_BONUSREFUND",
                    $order_id,
                    Loc::getMessage("commerce.loyaltyprogram_PROGRAM_BONUSREFUND", ["#NUM#"=>round($amount,2)])
                );
            }
        }
        return;
    }
	
	 public function control_bonus_b($orderId=0){
		 if(!empty($orderId)){
			$settings=Settings::getInstance();
			$transact=\CSaleUserTransact::GetList(['ID'=>'DESC'], ['ORDER_ID'=>$orderId]);
			$transactions=[];
			while($tran = $transact->fetch()){
				$transactions[]=$tran['ID'];
			}
			if(count($transactions)>0){
				global $DB;
				$res=$DB->Query('select * from '.$settings->getTableBonusList().' where order_id='.$orderId.';');
				if($r=$res->Fetch()){
					$resTR=$DB->Query('select GROUP_CONCAT(transaction_id) as transaction_ids, bonus_id from '.$settings->getTableTransactionList().' where bonus_id='.$r['id'].' group by bonus_id;');
					if($row=$resTR->Fetch()){
						$existingTR=explode(',',$row['transaction_ids']);
						$newTransaction=[];
						foreach($transactions as $nextTransaction){
							if(!in_array($nextTransaction, $existingTR)){
								$newTransaction[]=$nextTransaction;
							}
						}
						if(count($newTransaction)>0){
							foreach($newTransaction as $nextTransaction){
								$DB->Query('insert into '.$settings->getTableTransactionList().' (bonus_id,transaction_id) values ('.$r['id'].', '.$nextTransaction.')');
							}
						}
					}
				}
			}
		}
     }

    public function control_bonus($orderId=0){
        if(!empty($orderId)){
           $settings=Settings::getInstance();
           $transact=\CSaleUserTransact::GetList(['ID'=>'DESC'], ['ORDER_ID'=>$orderId]);
           $transactions=[];
           while($tran = $transact->fetch()){
                $transactions[$tran['ID']]=$tran['AMOUNT'];
           }
           if(count($transactions)>0){
               global $DB;
               foreach($transactions as $keyTransaction=>$amountTransaction){
                    $res=$DB->Query('select * from '.$settings->getTableBonusList().' where order_id='.$orderId.' and bonus_start='.$amountTransaction.';');
                    if($r=$res->Fetch()){
                        $resTR=$DB->Query('select transaction_id bonus_id from '.$settings->getTableTransactionList().' where bonus_id='.$r['id'].' and transaction_id='.$keyTransaction.';');
                        if($row=$resTR->Fetch()){
                        //isset
                        }else{
                            $DB->Query('insert into '.$settings->getTableTransactionList().' (bonus_id,transaction_id) values ('.$r['id'].', '.$keyTransaction.')');
                        }
                    }
               }
           }
       }
    }
	
    public function add_transaction($ids,$transaction){
        global $DB;
        $settings=Settings::getInstance();
        $sql = 'insert into '.$settings->getTableTransactionList().' (bonus_id,transaction_id) values ';
        foreach($ids as $key=>$id){
            $sql.='('.$id.','.$transaction.')';
            if(isset($ids[($key+1)])){
                $sql.=',';
            }
        }
        $DB->Query($sql);
        return;
    }
}