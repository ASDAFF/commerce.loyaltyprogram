<?
namespace Commerce\Loyaltyprogram;
use \Bitrix\Main\Localization\Loc,
	\Bitrix\Main\Application;
Loc::loadMessages(__DIR__ .'/lang.php');

\Bitrix\Main\Loader::includeModule('sale');
/**
main profile class
*/
class Import{

	function __construct (){
		$this->globalSettings=Settings::getInstance();
		$this->options=$this->globalSettings->getOptions();
		$this->typeUserRef='import';
	}
	
	private function clearFilerow($str){
		return explode(';',trim($str));
	}
	
	public function setBonus($idFile, $type='add'){
		$status=false;
		$this->countUpload=0;
		if((int) $idFile>0){
			$path=\CFile::GetPath($idFile);
			if(!empty($path)){
				$currency=$this->options['currency'];
				$bonusArr=file($_SERVER["DOCUMENT_ROOT"].$path);
				if(count($bonusArr)>0){
					foreach($bonusArr as $keyRow=>$row){
						if($keyRow==0){continue;}
						$rowArr=$this->clearFilerow($row);
						if((int) $rowArr[0]==0){continue;}
						$currentCurrency=empty($rowArr[2])?$currency:$rowArr[2];
						$account = \CSaleUserAccount::GetByUserID($rowArr[0], $currentCurrency);
					
						if($account==false){
							\CSaleUserAccount::Add([
								"USER_ID" => $rowArr[0],
								"CURRENCY" => $currentCurrency,
								"CURRENT_BUDGET" => 0
							]);
						}
						if($type=='replace'){
							$arRemove = \CSaleUserAccount::GetByUserID($rowArr[0], $currentCurrency);
							if($arRemove['CURRENT_BUDGET']>0){
								\CSaleUserAccount::UpdateAccount(
									$rowArr[0],
									(-1*$arRemove['CURRENT_BUDGET']),
									$currentCurrency,
									"COMMERCE_LOYAL_IMPORTBONUS"
								);
							}
						}
						$upd=\CSaleUserAccount::UpdateAccount(
							$rowArr[0],
							$rowArr[1],
							$currentCurrency,
							"COMMERCE_LOYAL_IMPORTBONUS"
						);
						
						if($upd){
							$status=true;
							$this->countUpload++;
						}
					}
				}
			}
		}
		return ['status'=>$status, 'count'=>$this->countUpload];
	}
	
	public function setReferalNet($idFile){
		$this->refNetStatus=false;
		$this->refUserRows=[];//array from file
		$this->refUserArray=[];//current level users
		$this->countUpload=0;
		if((int) $idFile>0){
			$path=\CFile::GetPath($idFile);
			if(!empty($path)){
				$users=$this->getRealUsers();
				$rows=file($_SERVER["DOCUMENT_ROOT"].$path);
				if(count($rows)>1){
					foreach($rows as $keyRow=>$row){
						if($keyRow==0){continue;}
						
						$rowArr=$this->clearFilerow($row);
						if(
							(int) $rowArr[0]==0
							|| !in_array($rowArr[0], $users)
						){continue;}
						$rowArr[1]=(empty($rowArr[1]) || !in_array($rowArr[1], $users))?0:$rowArr[1];
						if(empty($rowArr[1])){
							$this->refUserArray[]=['user'=>$rowArr[0], 'parent'=>0];
						}else{
							$this->refUserRows[]=['user'=>$rowArr[0], 'parent'=>$rowArr[1]];
						}
					}
					if(count($this->refUserArray)>0){
						$this->refO=new Referrals;
						$this->add2RefNet();
					}
				}
			}
		}
		return ['status'=>$this->refNetStatus, 'count'=>$this->countUpload];
	}
	
	private function getRealUsers(){
		global $DB;
		if(empty($this->realUsers)){
			$this->realUsers=[];
			$rsData = $DB->Query('select * from b_user');
			while($row = $rsData->Fetch()){
				$this->realUsers[]=$row['ID'];
			}
		}
		return $this->realUsers;
	}
	
	private function add2RefNet(){
		$tmpRefUserArray=[];
		foreach($this->refUserArray as $nextUser){
			$tmpRefUserArray[]=$nextUser['user'];
			$status=$this->refO->setReferral2($nextUser['parent'], $nextUser['user'], $this->typeUserRef);
			if($status!==false){
				$this->refNetStatus=true;
				$this->countUpload++;
			}
		}
		$this->refUserArray=[];
		if(count($this->refUserRows)>0){
			foreach($this->refUserRows as $nextUser){
				if(in_array($nextUser['parent'], $tmpRefUserArray)){
					$this->refUserArray[]=$nextUser;
				}
			}
		}
		if(count($this->refUserArray)>0){
			$this->add2RefNet();
		}
	}
	
	public function clearFiles(){
		$res = \CFile::GetList([], array("MODULE_ID"=>$this->globalSettings->getModuleId()));
		while($res_arr = $res->GetNext()){
			\CFile::Delete($res_arr["ID"]);
		}
	}
	
}

?>