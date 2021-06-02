<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
global $USER;
if($USER->IsAuthorized()){
	$params=$_REQUEST['params'];
	$tmpltName==(empty($_REQUEST['templateName']))?'':$_REQUEST['templateName'];
	$params['AJAX']='Y';
	$params['FILTER']=$_REQUEST['form'];
	\Bitrix\Main\Loader::IncludeModule("commerce.loyaltyprogram");
	if(isset($_REQUEST['writeoff_requisite']) && $_REQUEST['writeoff_bonus']>0){
		$cProfile=new Commerce\Loyaltyprogram\Components;
		if(!empty($_REQUEST['writeoff_requisite'])){
			$currency=empty($_REQUEST['writeoff_currency'])?$cProfile->getCurrency():$_REQUEST['writeoff_currency'];
			$status=$cProfile->setWriteOffBonus($_REQUEST['writeoff_bonus'], $_REQUEST['writeoff_requisite'], $currency);
			echo json_encode($status);
		}else{
			echo json_encode(false);
		}
	}elseif(!empty($_REQUEST['type'])){
		$userId=$USER->GetID();
		$writeoff=new Commerce\Loyaltyprogram\Writeoff;
		if($_REQUEST['type']=='cart' && !empty($_REQUEST['cart'])){
			//add cart
			$cart=str_ireplace('____-____-____-____','',$_REQUEST['cart']);
			if(!empty($cart)){
				echo json_encode([
					'result'=>$writeoff->addCart($userId, $_REQUEST['cart']),
					'list'=>$writeoff->getRequisites($userId)
				]);
			}else{
				echo json_encode(false);
			}
		}elseif($_REQUEST['type']=='invoice' && !empty($_REQUEST['invoice']) && !empty($_REQUEST['bik'])){
			//add invoice
			//echo json_encode($writeoff->addInvoice($USER->GetID(), $_REQUEST['invoice'],$_REQUEST['bik']));
			echo json_encode([
				'result'=>$writeoff->addInvoice($userId, $_REQUEST['invoice'],$_REQUEST['bik']),
				'list'=>$writeoff->getRequisites($userId)
			]);
		}elseif($_REQUEST['type']=='getRequisites'){
			echo json_encode($writeoff->getRequisites($USER->GetID()));
		}elseif($_REQUEST['type']=='updateRequisites'){
			$data=[];
			foreach(['cart', 'invoice', 'bik'] as $nextType){
				if(!empty($_REQUEST[$nextType])){
					$data[$nextType]=$_REQUEST[$nextType];
				}
			}
			//echo json_encode($writeoff->updateRequisites($USER->GetID(), $_REQUEST['id'], $data));
			echo json_encode([
				'result'=>$writeoff->updateRequisites($userId, $_REQUEST['id'], $data),
				'list'=>$writeoff->getRequisites($userId)
			]);
		}elseif($_REQUEST['type']=='deleteRequisites'){
			//echo json_encode($writeoff->deleteRequisites($USER->GetID(), $_REQUEST['id']));
			echo json_encode([
				'result'=>$writeoff->deleteRequisites($userId, $_REQUEST['id']),
				'list'=>$writeoff->getRequisites($userId)
			]);
		}else{
			echo json_encode(false);
		}
	}else{
	?>
	<?$APPLICATION->IncludeComponent(
		"commerce:loyaltyprogram.account",
		$tmpltName,
		$params
	);?>
	<?}
}?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");?>