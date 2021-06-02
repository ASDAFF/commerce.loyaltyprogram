<?
namespace Commerce\Loyaltyprogram;
use \Bitrix\Main\Localization\Loc,
	\Bitrix\Main\Application;
Loc::loadMessages(__DIR__ .'/lang.php');

\Bitrix\Main\Loader::includeModule('sale');
/**
main profile class
*/
class Groups{
	
	private $groupList;

	function __construct (){
		$this->globalSettings=Settings::getInstance();
		$this->groupList=[
			'bonusAcc'=>Loc::getMessage("commerce.loyaltyprogram_GROUPS_BONUSACC"),
			'bonusRemove'=>Loc::getMessage('commerce.loyaltyprogram_GROUPS_BONUSREMOVE')
		];
		$this->profileSetting=[
			'type'=>'Groups',
			'typeRemove'=>'Remove'
		];
		$this->etemplateGroupBonusacc=\Bitrix\Main\Config\Option::get($this->globalSettings->getModuleId(), 'etemplate_group_bonusacc');
		$this->etemplateGroupBonusremove=\Bitrix\Main\Config\Option::get($this->globalSettings->getModuleId(), 'etemplate_group_bonusremove');
		$this->notifyGroupBonusacc=\Bitrix\Main\Config\Option::get($this->globalSettings->getModuleId(), 'notify_group_bonusacc');
		$this->notifyGroupBonusRemove=\Bitrix\Main\Config\Option::get($this->globalSettings->getModuleId(), 'notify_group_bonusremove');
	}
	
	public function getGroupList(){
		return $this->groupList;
	}
	
	public function getFields(){
		return [
				'bonusAcc'=>[
					[
						'code'=>'bonus_size',
						'name'=>Loc::getMessage("commerce.loyaltyprogram_GROUPS_BONUSSIZE"),
						'type'=>'number',
						'required'=>true
					],
					[
						'code'=>'select_user',
						'name'=>Loc::getMessage("commerce.loyaltyprogram_GROUPS_SELECTUSERTYPE"),
						'type'=>'radiobutton',
						'list'=>[
							'group'=>Loc::getMessage("commerce.loyaltyprogram_GROUPS_USERGROUPS2"),
							'user'=>Loc::getMessage("commerce.loyaltyprogram_GROUPS_USERIDS2")
						]
					],
					[
						'code'=>'user_groups',
						'name'=>Loc::getMessage("commerce.loyaltyprogram_GROUPS_USERGROUPS"),
						'type'=>'multiselect',
						'list'=>$this->globalSettings->getUserGroups()
					],
					[
						'code'=>'user_ids',
						'name'=>Loc::getMessage("commerce.loyaltyprogram_GROUPS_USERIDS"),
						'type'=>'user_select',
					],
					[
						'code'=>'bonus_desc',
						'name'=>Loc::getMessage("commerce.loyaltyprogram_GROUPS_BONUSDESC"),
						'type'=>'textarea',
						'required'=>true
					],
					[
						'code'=>'bonus_live',
						'name'=>Loc::getMessage("commerce.loyaltyprogram_GROUPS_BONUSLIVE"),
						'type'=>'live_type',
						'list'=>[
							'day'=>Loc::getMessage("commerce.loyaltyprogram_TIME_DAY"),
							'week'=>Loc::getMessage("commerce.loyaltyprogram_TIME_WEEK"),
							'month'=>Loc::getMessage("commerce.loyaltyprogram_TIME_MONTH")
						]
					]
				],
				'bonusRemove'=>[
					[
						'code'=>'bonus_size_remove',
						'name'=>Loc::getMessage("commerce.loyaltyprogram_GROUPS_BONUSSIZE_REMOVE"),
						'type'=>'number',
						'required'=>true
					],
					[
						'code'=>'select_user_remove',
						'name'=>Loc::getMessage("commerce.loyaltyprogram_GROUPS_SELECTUSERTYPE"),
						'type'=>'radiobutton',
						'list'=>[
							'group'=>Loc::getMessage("commerce.loyaltyprogram_GROUPS_USERGROUPS"),
							'user'=>Loc::getMessage("commerce.loyaltyprogram_GROUPS_USERIDS")
						]
					],
					[
						'code'=>'user_groups_remove',
						'name'=>Loc::getMessage("commerce.loyaltyprogram_GROUPS_USERGROUPS"),
						'type'=>'multiselect',
						'list'=>$this->globalSettings->getUserGroups()
					],
					[
						'code'=>'user_ids',
						'name'=>Loc::getMessage("commerce.loyaltyprogram_GROUPS_USERIDS"),
						'type'=>'user_select',
					],
					[
						'code'=>'bonus_desc_remove',
						'name'=>Loc::getMessage("commerce.loyaltyprogram_GROUPS_BONUSDESC_REMOVE"),
						'type'=>'textarea',
						'required'=>true
					]
				]
			];
	}
	
	public function doSomething($reg){
		
		if(!empty($reg['groups_type'])){
			switch ($reg['groups_type']){
				case 'bonusAcc':
					return $this->bonusAcc($reg);
				case 'bonusRemove':
					return $this->bonusRemove($reg);
			}
		}
		$message = new \CAdminMessage(Loc::getMessage("commerce.loyaltyprogram_GROUPS_NOT_SELECT"));
		return $message->Show();
	}
	
	private function bonusRemove($reg){

		if(!empty((int) $reg['bonus_size_remove']) && !empty($reg['bonus_desc_remove'])){
			$users=['select'=>['USER_ID', 'USER_EMAIL' => 'USER.EMAIL']];
			$users['filter']=[];
			if(!empty($reg['user_groups_remove']) && $reg['select_user_remove']=='group'){
				$users['filter']['GROUP_ID']=$reg['user_groups_remove'];
				$users['filter']['!USER.EMAIL']=false;
			}elseif($reg['bonusRemove_user_select']>=1&&!empty($reg['USER_ID_bonusRemove_1']) && $reg['select_user_remove']=='user'){
				$tmpUserArr = [];
				for($i=1;$i<=$reg['bonusRemove_user_select'];$i++){
					$tmpUserArr[]=$reg['USER_ID_bonusRemove_'.$i];
				}
				$users['filter']['USER_ID']=$tmpUserArr;
			}else{
				$message = new \CAdminMessage(['MESSAGE'=>Loc::getMessage("commerce.loyaltyprogram_GROUPS_ERROR_GROUP_USER"), 'TYPE'=>'ERROR']);
				return $message->Show();
			}
			
			$usersArr=[];
			$res = \Bitrix\Main\UserGroupTable::getList($users);
			while($row = $res->fetch()){
				$usersArr[$row['USER_ID']]=$row['USER_EMAIL'];
			}
			
			global $DB;
			$options=$this->globalSettings->getOptions();
			$startBonus=time();
			$sites=array_keys($this->globalSettings->getSites());
			$mailTmplt='';
			if($this->notifyGroupBonusRemove=='Y'){
				$mailTmplt=[
					"EVENT_NAME" => "COMMERCE_LOYAL_GENERAL",
					"MESSAGE_ID" => $this->etemplateGroupBonusremove,
					"LANGUAGE_ID" => LANGUAGE_ID,
					"LID" => $sites[0],
					"C_FIELDS" => [
						"EMAIL_TO" => "",
						"BONUSMESSAGE" => $reg['bonus_desc_remove'],
						"BONUS" => $reg['bonus_size_remove']
					]
				];
			}
			$dateRemove='null';
			$tmpBonusSize = 0-(int)$reg['bonus_size_remove'];
			foreach($usersArr as $keyUser=>$valUser){
				if(is_array($mailTmplt)){
					$mailTmplt['C_FIELDS']['EMAIL_TO']=$valUser;
				}
				$DB->Insert($this->globalSettings->getTableBonusList(), [
					'bonus_start'=>$tmpBonusSize,
					'bonus'=>$tmpBonusSize,
					'user_id'=>$keyUser,
					'currency'=>'"'.$options['currency'].'"',
					'profile_type'=>'"'.$this->profileSetting['typeRemove'].'"',
					'profile_id'=>0,
					'status'=>'"not-removed"',
					'date_add'=>$dateRemove,
					'date_remove'=>'FROM_UNIXTIME('.$startBonus.')',
					'add_comment'=>'"'.$DB->ForSql(
							Loc::getMessage("commerce.loyaltyprogram_BONUS_USER_GROUP_BONUSREMOVE",
							["#NUM#"=>$reg['bonus_size_remove'], '#COMMENT#'=>$reg['bonus_desc_remove']]
						)
					).'"',
					'email'=>"'".serialize($mailTmplt)."'"
				], $err_mess.__LINE__);
			}
			
			$message = new \CAdminMessage(['MESSAGE'=>Loc::getMessage("commerce.loyaltyprogram_GROUPS_BONUSREMOVE_SUCCESS"), 'TYPE'=>'OK']);
			return $message->Show();

		}
		$message = new \CAdminMessage(Loc::getMessage("commerce.loyaltyprogram_GROUPS_NOT_SELECT_BONUSREMOVE"));
		return $message->Show();
	}
	
	private function bonusAcc($reg){
		
		if(!empty((int) $reg['bonus_size']) && !empty($reg['bonus_desc'])){
			$users=['select'=>['USER_ID', 'USER_EMAIL' => 'USER.EMAIL']];
			$users['filter']=[];
			if(!empty($reg['user_groups']) && $reg['select_user']=='group'){
				$users['filter']['GROUP_ID']=$reg['user_groups'];
				$users['filter']['!USER.EMAIL']=false;
			}elseif($reg['bonusAcc_user_select']>=1&&!empty($reg['USER_ID_bonusAcc_1']) && $reg['select_user']=='user'){
				$tmpUserArr = [];
				for($i=1;$i<=$reg['bonusAcc_user_select'];$i++){
					$tmpUserArr[]=$reg['USER_ID_bonusAcc_'.$i];
				}
				$users['filter']['USER_ID']=$tmpUserArr;
			}else{
				$message = new \CAdminMessage(['MESSAGE'=>Loc::getMessage("commerce.loyaltyprogram_GROUPS_ERROR_GROUP_USER"), 'TYPE'=>'ERROR']);
				return $message->Show();
			}

			$usersArr=[];
			$res = \Bitrix\Main\UserGroupTable::getList($users);
			while($row = $res->fetch()){
				$usersArr[$row['USER_ID']]=$row['USER_EMAIL'];
			}
			
			global $DB;
			$options=$this->globalSettings->getOptions();
			$startBonus=time();
			$sites=array_keys($this->globalSettings->getSites());
			$mailTmplt='';
			if($this->notifyGroupBonusacc=='Y'){
				$mailTmplt=[
					"EVENT_NAME" => "COMMERCE_LOYAL_GENERAL",
					"MESSAGE_ID" => $this->etemplateGroupBonusacc,
					"LANGUAGE_ID" => LANGUAGE_ID,
					"LID" => $sites[0],
					"C_FIELDS" => [
						"EMAIL_TO" => "",
						"BONUSMESSAGE" => $reg['bonus_desc'],
						"BONUS" => $reg['bonus_size']
					]
				];
			}
			$dateRemove='null';
			if(!empty($reg['bonus_live'])){
				$dateRemove=strtotime( '+'.$reg['bonus_live'].' '.$reg['bonus_live_type'].'' , time());
				$dateRemove='FROM_UNIXTIME('.$dateRemove.')';
			}
			foreach($usersArr as $keyUser=>$valUser){
				if(is_array($mailTmplt)){
					$mailTmplt['C_FIELDS']['EMAIL_TO']=$valUser;
				}
				$comment=$reg['bonus_size']>0?
					Loc::getMessage("commerce.loyaltyprogram_BONUS_USER_GROUP_BONUSACC", ["#NUM#"=>$reg['bonus_size'], '#COMMENT#'=>$reg['bonus_desc']]):
					Loc::getMessage("commerce.loyaltyprogram_BONUS_USER_GROUP_BONUSWITHDRAW", ["#NUM#"=>$reg['bonus_size'], '#COMMENT#'=>$reg['bonus_desc']]);
				$DB->Insert($this->globalSettings->getTableBonusList(), [
					'bonus_start'=>(int) $reg['bonus_size'],
					'bonus'=>(int) $reg['bonus_size'],
					'user_id'=>$keyUser,
					'currency'=>'"'.$options['currency'].'"',
					'profile_type'=>'"'.$this->profileSetting['type'].'"',
					'profile_id'=>0,
					'status'=>'"inactive"',
					'date_add'=>'FROM_UNIXTIME('.$startBonus.')',
					'date_remove'=>$dateRemove,
					'add_comment'=>'"'.$DB->ForSql($comment).'"',
					'email'=>"'".serialize($mailTmplt)."'"
				], $err_mess.__LINE__);
			}
			
			$message = new \CAdminMessage(['MESSAGE'=>Loc::getMessage("commerce.loyaltyprogram_GROUPS_BONUSACC_SUCCESS"), 'TYPE'=>'OK']);
			return $message->Show();

		}
		$message = new \CAdminMessage(Loc::getMessage("commerce.loyaltyprogram_GROUPS_NOT_SELECT_BONUSACC"));
		return $message->Show();
	}
	
}

?>