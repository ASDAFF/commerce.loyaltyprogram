<?
namespace Commerce\Loyaltyprogram;
use \Bitrix\Main\Application,
	Bitrix\Main\Localization\Loc;
/**
* setting for all modules
*/
class Modulesettings{
	
	private $globalSettings;
	function __construct(){
		$this->globalSettings=Settings::getInstance();
	}

	public function getFilterProps(){
	    $props=[];
        $filterProps=\Bitrix\Main\Config\Option::get($this->globalSettings->getModuleId(), 'filter_prop');
        if(!empty($filterProps)) {
            $filterProps = explode(',', $filterProps);
            $res = \Bitrix\Iblock\PropertyTable::getList([
                "filter" => [
                    "ID" => $filterProps,
                ],
                'select'=>['*', 'iblockName'=>'IBLOCK.NAME']
            ]);
            while ($row = $res->fetch()) {
                $props[] = [
                    "id" => $row['ID'],
                    "name" => $row['NAME'],
                    "iblockId" => $row['IBLOCK_ID'],
                    "iblockName" => $row['iblockName'],
                ];
            }
        }
        return $props;
    }
	
	public function setGeneralTemplates(){
		$cOptions=$this->globalSettings->getOptions();
		$this->checkGeneralTemplatesType();
		foreach($this->mailTemplates() as $keyTemplate=>$nextTemplate){
			$insTeamplate=false;
			if(empty($cOptions[$keyTemplate])){
				$insTeamplate=true;
			}elseif($this->getTemplateById($cOptions[$keyTemplate])==false){
				$insTeamplate=true;
			}
			if($insTeamplate){
				$nextTemplate['MESSAGE']=preg_replace('/[ ]{2,}|[\t]/', ' ', trim($nextTemplate['MESSAGE']));
				$resAdd=\Bitrix\Main\Mail\Internal\EventMessageTable::add($nextTemplate);
				$id=$resAdd->getId();
				$tmpSites=array_keys($this->globalSettings->getSites());
				foreach($tmpSites as $nextSite){
					\Bitrix\Main\Mail\Internal\EventMessageSiteTable::add([
						'EVENT_MESSAGE_ID'=>$id,
						'SITE_ID'=>$nextSite
					]);
				}
				\Bitrix\Main\Config\Option::set($this->globalSettings->getModuleId(), $keyTemplate, $id);
			}
		}
	}
	
	private function checkGeneralTemplatesType(){
		$res=\Bitrix\Main\Mail\Internal\EventTypeTable::getList(
			['filter'=>['EVENT_NAME'=>'COMMERCE_LOYAL_GENERAL']]
		);
		if(!$nextRes=$res->fetch()){
			$fieldsEvent=[
				'LID'=>LANGUAGE_ID,
				'EVENT_NAME'=>'COMMERCE_LOYAL_GENERAL',
				'NAME'=>Loc::getMessage("commerce.loyaltyprogram_EVENT_GENERAL"),
				'DESCRIPTION'=>'
					#EMAIL_TO# - '.Loc::getMessage("commerce.loyaltyprogram_EVENT_GENERAL_EMAIL_TO").'
					#BONUS# - '.Loc::getMessage("commerce.loyaltyprogram_EVENT_BONUSES").'
					#BONUSMESSAGE# - '.Loc::getMessage("commerce.loyaltyprogram_EVENT_GENERAL_BONUSMESSAGE").'
				',
				'SORT'=>500
			];
			$fieldsEvent['DESCRIPTION']=preg_replace('/[ ]{2,}|[\t]/', ' ', trim($fieldsEvent['DESCRIPTION']));
			$resAdd=\Bitrix\Main\Mail\Internal\EventTypeTable::add($fieldsEvent);
			//$id = $resAdd->getId();
		}
	}
	
	private function mailTemplates(){
		$sites=array_keys($this->globalSettings->getSites());
		return [
			'etemplate_overdue'=>[
				'EVENT_NAME'=>'COMMERCE_LOYAL_GENERAL',
				'LID'=>$sites[0],
				'ACTIVE'=>'Y',
				'EMAIL_FROM'=>'#DEFAULT_EMAIL_FROM#',
				'EMAIL_TO'=>'#EMAIL_TO#',
				'SUBJECT'=>'#SITE_NAME#: '.Loc::getMessage("commerce.loyaltyprogram_EVENT_GENERAL_OVERDUE"),
				'MESSAGE'=>$this->globalSettings->getEmailTemplate('sys_overdue_after'),
				'BODY_TYPE'=>'html'
			],
			'etemplate_before_overdue'=>[
				'EVENT_NAME'=>'COMMERCE_LOYAL_GENERAL',
				'LID'=>$sites[0],
				'ACTIVE'=>'Y',
				'EMAIL_FROM'=>'#DEFAULT_EMAIL_FROM#',
				'EMAIL_TO'=>'#EMAIL_TO#',
				'SUBJECT'=>'#SITE_NAME#: '.Loc::getMessage("commerce.loyaltyprogram_EVENT_GENERAL_BEFORE_OVERDUE"),
				'MESSAGE'=>$this->globalSettings->getEmailTemplate('sys_overdue_before'),
				'BODY_TYPE'=>'html'
			],
			'etemplate_group_bonusacc'=>[
				'EVENT_NAME'=>'COMMERCE_LOYAL_GENERAL',
				'LID'=>$sites[0],
				'ACTIVE'=>'Y',
				'EMAIL_FROM'=>'#DEFAULT_EMAIL_FROM#',
				'EMAIL_TO'=>'#EMAIL_TO#',
				'SUBJECT'=>'#SITE_NAME#: '.Loc::getMessage("commerce.loyaltyprogram_EVENT_GENERAL_GROUPACC"),
				'MESSAGE'=>$this->globalSettings->getEmailTemplate('sys_massoperation_plus'),
				'BODY_TYPE'=>'html'
			],
			'etemplate_group_bonusremove'=>[
				'EVENT_NAME'=>'COMMERCE_LOYAL_GENERAL',
				'LID'=>$sites[0],
				'ACTIVE'=>'Y',
				'EMAIL_FROM'=>'#DEFAULT_EMAIL_FROM#',
				'EMAIL_TO'=>'#EMAIL_TO#',
				'SUBJECT'=>'#SITE_NAME#: '.Loc::getMessage("commerce.loyaltyprogram_EVENT_GENERAL_GROUPREMOVE"),
				'MESSAGE'=>$this->globalSettings->getEmailTemplate('sys_massoperation_minus'),
				'BODY_TYPE'=>'html'
			]
		];
	}
	
	public function getEventSendByType($type){
		
		$sites=array_keys($this->globalSettings->getSites());
		$options=$this->globalSettings->getOptions();
		$eventsArr=[
			'etemplate_before_overdue'=>[
				"EVENT_NAME" => "COMMERCE_LOYAL_GENERAL",
				"MESSAGE_ID" => $options['etemplate_before_overdue'],
				"LANGUAGE_ID" => LANGUAGE_ID,
				"LID" => $sites[0],
				"C_FIELDS" => [
					"EMAIL_TO" => '',
					"BONUS"=>0
				]
			],
			'etemplate_overdue'=>[
				"EVENT_NAME" => "COMMERCE_LOYAL_GENERAL",
				"MESSAGE_ID" => $options['etemplate_overdue'],
				"LANGUAGE_ID" => LANGUAGE_ID,
				"LID" => $sites[0],
				"C_FIELDS" => [
					"EMAIL_TO" => '',
					"BONUS"=>0
				]
			]
		];
		
		return $eventsArr[$type];
	}
	
	public function getRuleDescription($id){
		global $DB;
		$select='select * from '.$this->globalSettings->getTableRuleDescription().' where id_rule='.$id.';';
		$rsData = $DB->Query($select);
		if($row = $rsData->Fetch()){
			return json_encode($row['description']);
		}
		return json_encode(false);
	}
	
	public function getRuleXml($id){
		$discountIterator = \Bitrix\Sale\Internals\DiscountTable::getList([
			'select' => ["ID", "NAME", 'XML_ID'],
			'filter' => ['ACTIVE' => 'Y', 'ID'=>$id],
			'order' => ["NAME" => "ASC"]
		]);
		if($discount = $discountIterator->fetch()){
			return json_encode($discount['XML_ID']);
		}
		return json_encode(false);
	}
	
	public function setRuleXml($id, $desc){
		\Bitrix\Sale\Internals\DiscountTable::update($id, ['XML_ID'=>$desc]);
		return true;
	}
	
	public function setRuleDescription($id, $desc){
		global $DB;
		$select='select * from '.$this->globalSettings->getTableRuleDescription().' where id_rule='.$id.';';
		$rsData = $DB->Query($select);
		if($row = $rsData->Fetch()){
			$DB->Update($this->globalSettings->getTableRuleDescription(), [
				'description'=>'"'.$DB->ForSql($desc).'"'
			], "WHERE id_rule='".$id."'", $err_mess.__LINE__);
		}else{
			$DB->Insert($this->globalSettings->getTableRuleDescription(), [
				'id_rule'=>$id,
				'description'=>'"'.$DB->ForSql($desc).'"'
			], $err_mess.__LINE__);
		}
		return true;
	}
	
	public function getTemplateById($id){
		$res=\Bitrix\Main\Mail\Internal\EventMessageTable::getList(['filter'=>['ID'=>$id]]);
		$nextRes=$res->fetch();
		return $nextRes;
	}

}

?>