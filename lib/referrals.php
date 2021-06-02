<?
namespace Commerce\Loyaltyprogram;
use \Bitrix\Main\Localization\Loc;
Loc::loadMessages(__DIR__ .'/lang.php');
/**
*    profiles list
*/
class Referrals{

	function __construct (){
		$this->settings=Settings::getInstance();
		$this->moduleOptions=$this->settings->getOptions();
		$this->linkType=[];
		foreach(Tools::getAllTypeLinkList() as $nextLink){
			$this->linkType[$nextLink['code']]=$nextLink['name'];
		}
	}
	
	private function checkFilter(){
		global $FilterArr;
		foreach ($FilterArr as $f){global $$f;}
		return count($this->lAdmin->arFilterErrors)==0;
	}
	
	/**
	* recalculate all tree with new level
	* new level should by less current level for $idUser
	* @param int $idUser id user for recalculate
	* @newLevel int $newLevel new level for $idUser
	*/
	private function recalculateTreeRef($idUser, $newLevel){
		global $DB;
		$select='select * from '.$this->settings->getTableUsersList().' where user='.$idUser.';';
		$rsData = $DB->Query($select);
		if($row = $rsData->Fetch()){
			$diffLevel=$row['level']-$newLevel;
			if($diffLevel>0){
				//update here
				/*$DB->Update($this->settings->getTableUsersList(), [
					'level'=>$newLevel,
					'date_create'=>'now()',
					'comment'=>'"manual update '.date('d.m.Y').'"'
				], "WHERE id='".$row['id']."'", $err_mess.__LINE__);*/

                Entity\UsersTable::update($row['id'], [
                    'level'=>$newLevel,
                    'comment'=>'manual update '.date('d.m.Y'),
                    'date_create'=>new \Bitrix\Main\Type\DateTime()
                ]);

				$selectChild='select * from '.$this->settings->getTableUsersList().' where ref_user='.$idUser.';';
				$rsDataChild = $DB->Query($selectChild);
				while($rowChild = $rsDataChild->Fetch()){
					$this->recalculateTreeRef($rowChild['user'], ($rowChild['level']-$diffLevel));
				}
			}
		}
	}
	
	/**
	* delete user from tree (set level=0, ref_user=0)
	* @param int $idUser id user for delete
	*/
	public function deleteFromTreeRef($idRef){
		$refUser=0; $newLevel=1;
		global $DB;
		$select='select * from '.$this->settings->getTableUsersList().' where id='.$idRef.';';
		$rsData = $DB->Query($select);
		if($row = $rsData->Fetch()){
			$idUser=$row['user'];
			$refUser=$row['ref_user'];
			$newLevel=$row['level'];
			//set ref_user=0 level=1
			/*$DB->Update($this->settings->getTableUsersList(), [
				'ref_user'=>0,
				'level'=>1,
				'date_create'=>'now()',
				'comment'=>'"manual update '.date('d.m.Y').'"'
			], "WHERE id='".$row['id']."'", $err_mess.__LINE__);*/
			//$DB->Query('delete from '.$this->settings->getTableUsersList().' where id='.$idRef.';');
            Entity\UsersTable::delete($idRef);
		}
		if(!empty($idUser)){
			$select='select * from '.$this->settings->getTableUsersList().' where ref_user='.$idUser.';';
			$rsData = $DB->Query($select);
			while($row = $rsData->Fetch()){
				//set ref_user=$refUser
				/*$DB->Update($this->settings->getTableUsersList(), [
					'ref_user'=>$refUser,
					'date_create'=>'now()',
					'comment'=>'"manual update '.date('d.m.Y').'"'
				], "WHERE id='".$row['id']."'", $err_mess.__LINE__);*/
                Entity\UsersTable::update($row['id'], [
                    'ref_user'=>$refUser,
                    'comment'=>'manual update '.date('d.m.Y'),
                    'date_create'=>new \Bitrix\Main\Type\DateTime()
                ]);
				$this->recalculateTreeRef($row['user'], $newLevel);
			}
		}
	}
	
	private function checkUserGroup($idUser){
		$rules=$this->moduleOptions['ref_basket_rules'];
		if($this->type=='coupon'){
			if(!empty($rules)){
				$ruleArr=explode(',', $rules);
				foreach($ruleArr as $keyRule=>$nextRule){
					$tmpKey=($keyRule==0)?'':$keyRule;
					$refGroups=$this->moduleOptions['ref_coupon_group'.$tmpKey];
					if(!empty($refGroups)){
						$refGroups=explode(',',$refGroups);
						if(count($refGroups)>0){
							$tmpArr=array_intersect($refGroups, \CUser::GetUserGroup($idUser));
							if(count($tmpArr)>0){
								return true;
							}
						}
					}else{
						return true;
					}
				}
			}
			return false;
		}
		return true;
	}
	
	/**
	* get source by type
	* @param int $type type referral link
	*/
	private function getLinkbyType($type){
		if($type=='coupon' && !empty($_SESSION['sw24_register_source_coupon'])){
			return $_SESSION['sw24_register_source_coupon'];
		}elseif($type=='partnerSite' && !empty($_SESSION['sw24_register_source_partnersite'])){
			return $_SESSION['sw24_register_source_partnersite'];
		}
		return false;
	}
	
    public function setReferral2($parenRef, $ref, $type='manual', $comment=''){
        global $DB;
        $this->type=$type;
        if(
            (int) $ref>0 &&
            (((int) $parenRef>0 && $this->checkUserGroup($parenRef)) || $parenRef==0)
        ){
            $rsData = $DB->Query('select * from '.$this->settings->getTableUsersList().' where ref_user='.$ref.';');
            $rowChildren=[];
            while($rowChild = $rsData->Fetch()){
                $rowChildren[]=$rowChild['user'];
            }
            if(count($rowChildren) == 0 || $type=='manual'){
                //check parent
                $parentRow=['level'=>0, 'id'=>$parenRef];
                if($parenRef>0){
                    $rsData = $DB->Query('select * from '.$this->settings->getTableUsersList().' where user='.$parenRef.';');
                    if($rowParent = $rsData->Fetch()){
                        $parentRow['level']=$rowParent['level'];
                    }else{
                        Entity\UsersTable::add([
                            'user'=>$parenRef,
                            'ref_user'=>0,
                            'type'=>'simple',
                            'level'=>1,
                            'date_create'=>new \Bitrix\Main\Type\DateTime()
                        ]);
                    }
                }
                $childLevel=$parentRow['level']+1;
                $rsData = $DB->Query('select * from '.$this->settings->getTableUsersList().' where user='.$ref.';');
                $row = $rsData->Fetch();
                $fields=[
                    'user'=>$ref,
                    'ref_user'=>$parenRef,
                    'type'=>$type,
                    'level'=>$childLevel,
                    'date_create'=>new \Bitrix\Main\Type\DateTime()
                ];
                $link=$this->getLinkbyType($type);
                if($link!=false){
                    $fields['source_link']=$link;
                }
                if($row==false){
                    $fields['comment']='manual added '.date('d.m.Y');
                    if(!empty($comment)){
                        $fields['comment']='manual added ('.$comment.') '.date('d.m.Y');
                    }
                    Entity\UsersTable::add($fields);
                }else{
                    if(
                        ($row['type']=='simple' && $row['level']==1 && $type=='coupon' && $this->moduleOptions['set_referal']=='ALL' && count($rowChildren) ==0) ||
                        $type=='manual'
                    ){
                        $fields['comment']='manual update '.date('d.m.Y');
                        if(!empty($comment)){
                            $fields['comment']='manual update ('.$comment.') '.date('d.m.Y');
                        }
                        Entity\UsersTable::update($row['id'], $fields);
                    }
                }
                if(count($rowChildren)>0){
                    $this->setChildRef($ref, $childLevel);
                }
            }
            return '/bitrix/admin/commerce_loyaltyprogram_referrals.php?lang='.LANGUAGE_ID.'&set_filter=Y&find_id='.$parenRef.'&by=date_create&order=desc';
        }
        return false;
    }

    /*public function setReferral2($parenRef, $ref, $type='manual', $comment=''){
        global $DB;
        $this->type=$type;
        if(
            (int) $ref>0 &&
            (((int) $parenRef>0 && $this->checkUserGroup($parenRef)) || $parenRef==0)
        ){
            $rsData = $DB->Query('select * from '.$this->settings->getTableUsersList().' where ref_user='.$ref.';');
            $rowChildren=[];
            while($rowChild = $rsData->Fetch()){
                $rowChildren[]=$rowChild['user'];
            }
            if(count($rowChildren) == 0 || $type=='manual'){
                //check parent
                $parentRow=['level'=>0, 'id'=>$parenRef];
                if($parenRef>0){
                    $rsData = $DB->Query('select * from '.$this->settings->getTableUsersList().' where user='.$parenRef.';');
                    if($rowParent = $rsData->Fetch()){
                        $parentRow['level']=$rowParent['level'];
                    }else{
                        $DB->Insert($this->settings->getTableUsersList(), [
                            'user'=>$parenRef,
                            'ref_user'=>0,
                            // 'type'=>'"'.$type.'"',
                            'type'=>'"simple"',
                            'level'=>1,
                            'date_create'=>'now()'
                        ], $err_mess.__LINE__);
                    }
                }
                $childLevel=$parentRow['level']+1;
                $rsData = $DB->Query('select * from '.$this->settings->getTableUsersList().' where user='.$ref.';');
                $row = $rsData->Fetch();
                $fields=[
                    'user'=>$ref,
                    'ref_user'=>$parenRef,
                    'type'=>'"'.$type.'"',
                    'level'=>$childLevel,
                    'date_create'=>'now()'
                ];
                $link=$this->getLinkbyType($type);
                if($link!=false){
                    $fields['source_link']=$link;
                }
                if($row==false){
                    $fields['comment']='"manual added '.date('d.m.Y').'"';
                    if(!empty($comment)){
                        $fields['comment']='"manual added ('.$DB->ForSQL($comment).') '.date('d.m.Y').'"';
                    }
                    $DB->Insert($this->settings->getTableUsersList(), $fields, $err_mess.__LINE__);
                }else{
                    if(
                        ($row['type']=='simple' && $row['level']==1 && $type=='coupon' && $this->moduleOptions['set_referal']=='ALL' && count($rowChildren) ==0) ||
                        $type=='manual'
                    ){
                        $fields['comment']='"manual update '.date('d.m.Y').'"';
                        if(!empty($comment)){
                            $fields['comment']='"manual update ('.$DB->ForSQL($comment).') '.date('d.m.Y').'"';
                        }
                        $DB->Update($this->settings->getTableUsersList(), $fields, "WHERE id='".$row['id']."'", $err_mess.__LINE__);
                    }
                }
                if(count($rowChildren)>0){
                    $this->setChildRef($ref, $childLevel);
                }
            }
            return '/bitrix/admin/commerce_loyaltyprogram_referrals.php?lang='.LANGUAGE_ID.'&set_filter=Y&find_id='.$parenRef.'&by=date_create&order=desc';
        }
        return false;
    }*/

    /*public function setReferral($parenRef, $ref, $type='manual'){
		global $DB;
		$this->type=$type;
        if(
			(int) $ref>0 &&
			(((int) $parenRef>0 && $this->checkUserGroup($parenRef)) || $parenRef==0)
		){
            $select='select * from '.$this->settings->getTableUsersList().' where ref_user='.$ref.';';
            $rsData = $DB->Query($select);
            if(!$row = $rsData->Fetch()){
                $select='select * from '.$this->settings->getTableUsersList().' where user='.$parenRef.' and user>0;';
                $rsData = $DB->Query($select);
                if($rowParent = $rsData->Fetch()){
                    $childLevel=$rowParent['level']+1;
                }elseif($parenRef>0){
                    $DB->Insert($this->settings->getTableUsersList(), [
                        'user'=>$parenRef,
                        'ref_user'=>0,
                       // 'type'=>'"'.$type.'"',
                        'type'=>'"simple"',
                        'level'=>1,
                        'date_create'=>'now()'
                    ], $err_mess.__LINE__);
                    $childLevel=2;
                }
                $select='select * from '.$this->settings->getTableUsersList().' where user='.$ref.';';
                $rsData = $DB->Query($select);
				$row = $rsData->Fetch();
                if($row != false && $type=='manual'){
					$childLevel=($parenRef==0)?1:$childLevel;
                    $DB->Update($this->settings->getTableUsersList(), [
                        'user'=>$ref,
                        'ref_user'=>$parenRef,
                        'type'=>'"'.$type.'"',
                        'level'=>$childLevel,
                        'date_create'=>'now()'
                    ], "WHERE id='".$row['id']."'", $err_mess.__LINE__);
                }elseif($row != false && $row['type']=='simple' && $row['level']==1 && $type=='coupon' && $this->moduleOptions['set_referal']=='ALL'){
					 $DB->Update($this->settings->getTableUsersList(), [
                        'user'=>$ref,
                        'ref_user'=>$parenRef,
                        'type'=>'"'.$type.'"',
                        'level'=>empty($childLevel)?1:$childLevel,
                        'date_create'=>'now()'
                    ], "WHERE id='".$row['id']."'", $err_mess.__LINE__);
				}elseif($row==false){
					$childLevel=empty($childLevel)?1:$childLevel;
					$fields=[
                        'user'=>$ref,
                        'ref_user'=>$parenRef,
                        'type'=>'"'.$type.'"',
                        'level'=>$childLevel,
                        'date_create'=>'now()'
                    ];
					$link=$this->getLinkbyType($type);
					if($link!=false){
						$fields['source_link']=$link;
					}
                    $DB->Insert($this->settings->getTableUsersList(), $fields, $err_mess.__LINE__);
                }
                return '/bitrix/admin/commerce_loyaltyprogram_referrals.php?lang='.LANGUAGE_ID.'&set_filter=Y&find_id='.$parenRef.'&by=date_create&order=desc';
            }elseif($type=='manual'){
				$select='select * from '.$this->settings->getTableUsersList().' where user='.$parenRef.';';
				$rsDataParent = $DB->Query($select);
				$rowParent = $rsDataParent->Fetch();
				if($rowParent != false){
                    $childLevel=$rowParent['level']+1;
                }else{
                    $DB->Insert($this->settings->getTableUsersList(), [
                        'user'=>$parenRef,
                        'ref_user'=>0,
                        'type'=>'"'.$type.'"',
                        'level'=>1,
                        'date_create'=>'now()'
                    ], $err_mess.__LINE__);
                    $childLevel=2;
                }
				$DB->Update($this->settings->getTableUsersList(), [
					'user'=>$ref,
					'ref_user'=>$parenRef,
					'type'=>'"'.$type.'"',
					'level'=>$childLevel,
					'date_create'=>'now()'
				], "WHERE id='".$row['id']."'", $err_mess.__LINE__);
				//$this->setChildRef($ref, $childLevel);
				return '/bitrix/admin/commerce_loyaltyprogram_referrals.php?lang='.LANGUAGE_ID.'&set_filter=Y&find_id='.$parenRef.'&by=date_create&order=desc';
			}
        }
        return false;
    }*/
	
	private function setChildRef($parentRef, $level){
		global $DB;
		$nextLevel=$level+1;
		$select='select * from '.$this->settings->getTableUsersList().' where ref_user='.$parentRef.';';
		$rsDataParent = $DB->Query($select);
		while($rowParent = $rsDataParent->Fetch()){
            Entity\UsersTable::update($rowParent['id'], [
                'level'=>$nextLevel,
                'date_create'=>new \Bitrix\Main\Type\DateTime()
            ]);
			/*$DB->Update($this->settings->getTableUsersList(), [
				'level'=>$nextLevel,
				'date_create'=>'now()'
			], "WHERE id='".$rowParent['id']."'", $err_mess.__LINE__);*/
			$this->setChildRef($rowParent['user'], $nextLevel);
		}
	}

    public function userIsReferral($userId){
        if((int) $userId>0){
            global $DB;
            $select='select * from '.$this->settings->getTableUsersList().' where ref_user='.$userId.';';
            $rsData = $DB->Query($select);
            if($row = $rsData->Fetch()){
                return true;
            }
        }
        return false;
    }
	
	public function setReferralForm(){
		global $APPLICATION, $USER;?>
        <form action="<?=$APPLICATION->GetCurPageParam("", array("id"));?>" method="post" name="get_user_id" class="newRefLigament">
            <h4><?=Loc::getMessage("commerce.loyaltyprogram_CREATE_NEW_REF")?></h4>
            <div>
                <span class="title"><?=Loc::getMessage("commerce.loyaltyprogram_HEADER_REF")?>: </span>
                <input type="text" name="id" id="user_id_input" value="" size="3" maxlength="" class="typeinput">
                <iframe style="width:0px; height:0px; border: 0px" src="javascript:void(0)" name="hiddenframeuser_id_input" id="hiddenframeuser_id_input"></iframe>
                <input class="tablebodybutton" type="button" name="button_user" id="button_user" onclick="window.open('/bitrix/admin/user_search.php?lang=<?=LANGUAGE_ID?>&amp;FN=get_user_id&amp;FC=user_id_input', '', 'scrollbars=yes,resizable=yes,width=760,height=500,top='+Math.floor((screen.height - 560)/2-14)+',left='+Math.floor((screen.width - 760)/2-5));" value="...">
                <span id="div_user_id_input"></span>
            </div>
			<div>
                <span class="title"><?=Loc::getMessage("commerce.loyaltyprogram_LINK_REF")?>: </span>
                <input type="text" name="id" id="linkuser_id_input" value="" size="3" maxlength="" class="typeinput">
                <iframe style="width:0px; height:0px; border: 0px" src="javascript:void(0)" name="hiddenframelinkuser_id_input" id="hiddenframelinkuser_id_input"></iframe>
                <input class="tablebodybutton" type="button" name="button_user" id="button_user" onclick="window.open('/bitrix/admin/user_search.php?lang=<?=LANGUAGE_ID?>&amp;FN=get_user_id&amp;FC=linkuser_id_input', '', 'scrollbars=yes,resizable=yes,width=760,height=500,top='+Math.floor((screen.height - 560)/2-14)+',left='+Math.floor((screen.width - 760)/2-5));" value="...">
                <span id="div_linkuser_id_input"></span>
			</div>
			<?ShowError(Loc::getMessage("commerce.loyaltyprogram_EMPTY_REF_NOTE"));?>
			<?/*?><div><?=ShowNote(Loc::getMessage("commerce.loyaltyprogram_NOTUSECHANGE"));?></div><?*/?>
            <div>
                <input class="tablebodybutton" disabled="disabled" type="button" name="create_ref" id="create_ref" value="<?=Loc::getMessage("commerce.loyaltyprogram_CREATE_REF")?>">
            </div>
        </form>
    <script>
    var cUseId='', cLinkUseId='', statusUser, statusLinkUser;

    function activeSetReferral(){
        var tmpDisabled=true,
            createButton = document.getElementById("create_ref"),
            nameUser = document.getElementById("div_user_id_input"),
            linkUser = document.getElementById("div_linkuser_id_input");
        if(statusLinkUser=='notreferral' && BX('user_id_input').value!=BX('linkuser_id_input').value){
            tmpDisabled=false;
        }
        createButton.disabled=tmpDisabled;
    }

    BX.bind(BX('create_ref'), 'click', function(e) {
        _this=this;
        _this.disabled=true;
        BX.ajax({
            url: '/bitrix/admin/commerce_loyaltyprogram_referrals.php?lang=<?=LANGUAGE_ID?>',
            data: {
                'ajax':'y',
                'setRef':'y',
                'refParent':BX('user_id_input').value,
                'ref':BX('linkuser_id_input').value
            },
            method: 'POST',
            dataType: 'json',
            timeout:300,
            async: false,
            onsuccess: function(data){
               if(data && data.url && data.url!=false){
                   location.href=data.url;
               }
               _this.disabled=false;
            },
            onfailure: function(data){
                console.log(data);
            }
        });
    })

    function getUserId(){
        var nameUser, linkUser;
        nameUser = document.getElementById("div_user_id_input");
        linkUser = document.getElementById("div_linkuser_id_input");
        if (!!nameUser){
            if (
                document.get_user_id
                && document.get_user_id['user_id_input']
                && typeof cUseId != 'undefined'
                && cUseId != document.get_user_id['user_id_input'].value
            ){
                cUseId=document.get_user_id['user_id_input'].value;
                if (cUseId!=''){
                    nameUser.innerHTML = '<i><?=Loc::getMessage("commerce.loyaltyprogram_SEARCH")?>...</i>';
					if(BX('user_id_input').value==BX('linkuser_id_input').value){
						setTimeout(function(){
                            nameUser.innerHTML='<span class="error"><?=Loc::getMessage("commerce.loyaltyprogram_USERMATCH")?></span>';
							activeSetReferral();
						},1000);
					}
                    else if (cUseId!=1)
                    {
                        document.getElementById("hiddenframeuser_id_input").src='/bitrix/admin/get_user.php?ID=' + cUseId+'&strName=user_id_input&lang=<?=LANGUAGE_ID?>&admin_section=Y';
                        statusUser=loyaltyTools.checkUser(cUseId);
                        if(statusUser=='notExists'){
                            setTimeout(function(){
                                nameUser.innerHTML='<span class="error"><?=Loc::getMessage("commerce.loyaltyprogram_NOTUSEREXIST")?></span>';
                            },1000);
                        }
                        setTimeout(function(){
                            activeSetReferral();
                        },1000);
                    }
                    else
                    {
                        nameUser.innerHTML = '[<a title=\"<?=Loc::getMessage("commerce.loyaltyprogram_HEADER_USER_ID")?>\" class=\"tablebodylink\" target=\"_blank\" href=\"/bitrix/admin/user_edit.php?ID=<?=$USER->GetID()?>&lang=<?=LANGUAGE_ID?>\"><?=$USER->GetID()?><\/a>] <?=$USER->GetFullName()?>';
                        statusUser=loyaltyTools.checkUser(cUseId);
                        setTimeout(function(){
                            activeSetReferral();
                        },1000);
                    }
                }else{
                    nameUser.innerHTML = '';
                }
            }else if(
                nameUser
                && nameUser.innerHTML.length > 0
                && document.get_user_id
                && document.get_user_id['user_id_input']
                && document.get_user_id['user_id_input'].value == ''
            ){
                document.getElementById('div_user_id_input').innerHTML = '';
            }
        }
        if (!!linkUser){
            if (
                document.get_user_id
                && document.get_user_id['linkuser_id_input']
                && typeof cLinkUseId != 'undefined'
                && cLinkUseId != document.get_user_id['linkuser_id_input'].value
            ){
                cLinkUseId=document.get_user_id['linkuser_id_input'].value;
                if (cLinkUseId!=''){
                    linkUser.innerHTML = '<i><?=Loc::getMessage("commerce.loyaltyprogram_SEARCH")?>...</i>';

                    if(BX('user_id_input').value==BX('linkuser_id_input').value){
						setTimeout(function(){
                            linkUser.innerHTML='<span class="error"><?=Loc::getMessage("commerce.loyaltyprogram_USERMATCH")?></span>';
							activeSetReferral();
						},1000);
					}
                    else if (cLinkUseId!=1)
                    {
                        document.getElementById("hiddenframelinkuser_id_input").src='/bitrix/admin/get_user.php?ID=' + cLinkUseId+'&strName=linkuser_id_input&lang=<?=LANGUAGE_ID?>&admin_section=Y';
                        statusLinkUser=loyaltyTools.checkUser(cLinkUseId, 'notreferral');
                        if(statusLinkUser=='notExists'){
                            setTimeout(function(){
                                linkUser.innerHTML='<span class="error"><?=Loc::getMessage("commerce.loyaltyprogram_NOTUSEREXIST")?></span>';
                            },1000);
                        }else if(statusLinkUser=='referral'){
                            setTimeout(function(){
                                linkUser.innerHTML=linkUser.innerHTML+' <span class="error"><?=Loc::getMessage("commerce.loyaltyprogram_USERISREFERRAL")?></span>';
                            },1000);
                        }
                        setTimeout(function(){
                            activeSetReferral();
                        },1000);
                    }
                    else
                    {
                        linkUser.innerHTML = '[<a title=\"<?=Loc::getMessage("commerce.loyaltyprogram_HEADER_USER_ID")?>\" class=\"tablebodylink\" target=\"_blank\" href=\"/bitrix/admin/user_edit.php?ID=<?=$USER->GetID()?>&lang=<?=LANGUAGE_ID?>\"><?=$USER->GetID()?><\/a>] <?=$USER->GetFullName()?>';
                        statusLinkUser=loyaltyTools.checkUser(cLinkUseId, 'notreferral');
                        setTimeout(function(){
                            activeSetReferral();
                        },1000);
                    }
                }else{
                    linkUser.innerHTML = '';
                }
            }else if(
                linkUser
                && linkUser.innerHTML.length > 0
                && document.get_user_id
                && document.get_user_id['linkuser_id_input']
                && document.get_user_id['linkuser_id_input'].value == ''
            ){
                document.getElementById('div_linkuser_id_input').innerHTML = '';
            }
        }
        setTimeout(function(){getUserId()},1000);
    }
    getUserId();

    </script>
	<?}
	
	public function initFilter(){
		global $find_id, $find_user, $find_date_from, $find_date_to, $find_level, $find_type, $find_source, $APPLICATION, $DB;
		
		$oFilter = new \CAdminFilter(
			$this->settings->getTableUsersList().'_filter',
			[
				Loc::getMessage("commerce.loyaltyprogram_HEADER_REF"),
				Loc::getMessage("commerce.loyaltyprogram_HEADER_USERS"),
				Loc::getMessage("commerce.loyaltyprogram_HEADER_DATE_CREATE"),
				Loc::getMessage("commerce.loyaltyprogram_HEADER_LEVEL"),
				Loc::getMessage("commerce.loyaltyprogram_HEADER_TYPE"),
				Loc::getMessage("commerce.loyaltyprogram_HEADER_SOURCE")
			]
		);?>
		<form name="find_form" method="get" action="<?echo $APPLICATION->GetCurPage();?>">
		<?$oFilter->Begin();?>
		<tr>
			<td><b><?=Loc::getMessage("commerce.loyaltyprogram_HEADER_REF")?>:</b></td>
			<td>
                <?echo \FindUserID("find_id", $find_id, "", "find_form");?>
			</td>
		</tr>
        <tr>
			<td><b><?=Loc::getMessage("commerce.loyaltyprogram_HEADER_USERS")?>:</b></td>
			<td>
                <?echo \FindUserID("find_user", $find_user, "", "find_form");?>
			</td>
		</tr>
        <tr>
			<td><b><?=Loc::getMessage("commerce.loyaltyprogram_HEADER_DATE_CREATE")?>:</b></td>
			<td>
                <?echo CalendarPeriod("find_date_from", $find_date_from, "find_date_to", $find_date_to, "find_form", "Y")?>
			</td>
		</tr>
		<tr>
			<td><?=Loc::getMessage("commerce.loyaltyprogram_HEADER_LEVEL").":"?></td>
			<td>
                <select name="find_level">
                    <option value="">...</option>
                    <?
                        $rsData = $DB->Query('select max(level) as level from commerce_loyal_users');
                        $maxLevel=0;
                        if($row=$rsData->Fetch()){
                            $maxLevel=$row['level'];
                        }
                        for ($i=0; $i<=$maxLevel; $i++){
                            $selected=($i==$find_level && isset($find_level) && $find_level!='')?' selected':'';?>
                            <option value="<?=$i?>" <?=$selected?>><?=$i?></option>
                        <?}
                    ?>
                </select>
			</td>
		</tr>
        <tr>
			<td><?=Loc::getMessage("commerce.loyaltyprogram_HEADER_TYPE").":"?></td>
			<td>
                <select name="find_type">
                    <option value="">...</option>
                    <?
                        $types=Tools::getAllTypeLinkList();
                        foreach ($types as $nextType){
                            $selected=$nextType['code']==$find_type?' selected':'';?>
                            <option value="<?=$nextType['code']?>" <?=$selected?>><?=$nextType['name']?></option>
                        <?}
                    ?>
                </select>
			</td>
		</tr>
            <tr>
			<td><?=Loc::getMessage("commerce.loyaltyprogram_HEADER_SOURCE").":"?></td>
			<td>
                <input type="text" name="find_source" value="<?echo htmlspecialchars($find_source)?>">
			</td>
		</tr>
  		<?
		if(!empty($_GET['mode']) ){?>
			<input type="hidden" name="mode" value="tree">
		<?}
			$oFilter->Buttons(array("table_id"=>$this->settings->getTableUsersList(),"url"=>$APPLICATION->GetCurPage(),"form"=>"find_form"));
			$oFilter->End();
		?>
		</form>
	<?}

    private function gelallParent($userId) {
        global $DB;
        $users = [];
        $rsData = $DB->Query('select * from ' . $this->settings->getTableUsersList() . ' where user=' . $userId . ';');
        if ($row = $rsData->Fetch()) {
            $users[] = $row['user'];
            if ($row['ref_user'] > 0) {
                $parentUser = $row['ref_user'];
                while ($parentUser > 0) {
                    $rsData = $DB->Query('select * from ' . $this->settings->getTableUsersList() . ' where user=' . $parentUser . ';');
                    $parentUser = 0;
                    if ($row = $rsData->Fetch()) {
                        $users[] = $row['user'];
                        if ($row['ref_user'] > 0) {
                            $parentUser = $row['ref_user'];
                        }
                    }
                }
            }
        }
        return $users;
    }

    private function getByInterval(){
        $users=[];
        global $DB, $find_date_from, $find_date_to;
        if(!empty($find_date_from) || !empty($find_date_to)){
            $parentUsers=[];
            $where='';
            if(!empty($find_date_from)){
                $where.=' and date_create>=FROM_UNIXTIME("'.\MakeTimeStamp($find_date_from).'")';
            }
            if(!empty($find_date_to)){
                $where.=' and date_create<=FROM_UNIXTIME("'.(\MakeTimeStamp($find_date_to)+86400).'")';
            }
            $rsData = $DB->Query('select * from ' . $this->settings->getTableUsersList() . ' where 1=1'.$where.';');
            while($row = $rsData->Fetch()) {
                $users[]=$row['user'];
                $users[]=$row['ref_user'];
                if ($row['ref_user'] > 0) {
                    $parentUsers[] = $row['ref_user'];
                }
            }
            if(count($parentUsers)>0){
                while (count($parentUsers) > 0) {
                    $rsData = $DB->Query('select * from ' . $this->settings->getTableUsersList() . ' where user in (' . implode(',', $parentUsers) . ');');
                    $parentUsers = [];
                    if ($row = $rsData->Fetch()) {
                        $users[] = $row['user'];
                        $users[]=$row['ref_user'];
                        if ($row['ref_user'] > 0) {
                            $parentUsers = $row['ref_user'];
                        }
                    }
                }
            }
        }
        return array_unique($users);
    }

    private function getByResource($resource, $mode='table'){
        global $DB;
        $users = [];
        $rsData = $DB->Query('select * from commerce_loyal_coupons where coupon="' . $resource . '";');
        if($row = $rsData->Fetch()){
            $type='coupon';
            $source_link=$row['id'];
        }else{
            $resource=str_replace(['http://', 'https://'], '', $resource);
            if(function_exists('idn_to_ascii')){
                $resource=(LANG_CHARSET=='windows-1251')?iconv('CP1251' , 'UTF-8' , $resource):$resource;
                $resource=idn_to_ascii($resource);
            }
            $rsData = $DB->Query('select * from commerce_loyal_partner_sites where site like "%' . $resource . '%";');
            if($row = $rsData->Fetch()){
                $type='partnerSite';
                $source_link=$row['id'];
            }
        }
        if(!empty($type)){
            $rsData = $DB->Query('select * from ' . $this->settings->getTableUsersList() . ' where type="'.$type.'" and source_link=' . $source_link . ';');
            $parentUsers=[];
            while($row = $rsData->Fetch()) {
                $users[]=$row['user'];
                if($mode=='tree') {
                    $users[] = $row['ref_user'];
                }
                if ($row['ref_user'] > 0) {
                    $parentUsers[] = $row['ref_user'];
                }
            }
            if($mode=='tree' && count($parentUsers)>0){
                while (count($parentUsers) > 0) {
                    $rsData = $DB->Query('select * from ' . $this->settings->getTableUsersList() . ' where user in (' . implode(',', $parentUsers) . ');');
                    $parentUsers = [];
                    if ($row = $rsData->Fetch()) {
                        $users[] = $row['user'];
                        if($mode=='tree') {
                            $users[] = $row['ref_user'];
                        }
                        if ($row['ref_user'] > 0) {
                            $parentUsers = $row['ref_user'];
                        }
                    }
                }
            }
        }
        return array_unique($users);
    }
	
	public function initTableList(){
		global $DB,$by,$order,$FIELDS,$arID,$FilterArr,$APPLICATION;
		$oSort = new \CAdminSorting($this->settings->getTableUsersList(), "id", "desc");
		$this->lAdmin = new \CAdminList($this->settings->getTableUsersList(), $oSort);

		$rights=$APPLICATION->GetGroupRight($this->settings->getModuleId());
		
		//filter
		$FilterArr = [
			"find_id",
			"find_user",
			"find_level",
			"find_type",
			"find_source",
            "find_date_from",
            "find_date_to",
		];
		$this->lAdmin->InitFilter($FilterArr);

		//edit
		if($rights>"E"){
			if($this->lAdmin->GroupAction()){
				$ids=[];
				if(is_array($_REQUEST['ID'])){
					$ids=$_REQUEST['ID'];
				}elseif(!empty($_REQUEST['ID'])){
					$ids=[$_REQUEST['ID']];
				}
				if(count($ids)>0){
					foreach($ids as $nextId){
						$this->deleteFromTreeRef($nextId);
					}
				}
			}
		}

		global $find_id, $find_user, $find_date_from, $find_date_to, $find_level, $find_type, $find_source, $del_filter;

		if(!empty($del_filter) && $del_filter=='Y'){
		    unset($find_id, $find_user, $find_level, $find_type, $find_source, $find_date_from, $find_date_to);
        }

		$where='';
		if(!empty($find_id)){
			//$where.=' and '.$this->settings->getTableUsersList().'.ref_user='.$find_id;
			$find_id=urldecode($find_id);
			$where.=' and ('.$this->settings->getTableUsersList().'.ref_user="'.$find_id.'" or b_user.LOGIN="'.$find_id.'" or b_user.EMAIL="'.$find_id.'")';
		}

		if(!empty($find_user)){
			$refUsers=$this->gelallParent($find_user);
			if(count($refUsers)>0){
                $where.=' and '.$this->settings->getTableUsersList().'.user in('.implode(',', $refUsers).')';
            }else{
                $where.=' and '.$this->settings->getTableUsersList().'.user = -1';
            }
		}

		if(!empty($find_date_from)){
            $where.=' and '.$this->settings->getTableUsersList().'.date_create>=FROM_UNIXTIME("'.\MakeTimeStamp($find_date_from).'")';
		}

		if(!empty($find_date_to)){
            $where.=' and '.$this->settings->getTableUsersList().'.date_create<=FROM_UNIXTIME("'.(\MakeTimeStamp($find_date_to)+86400).'")';
		}

		if(!empty($find_source)){
			$sourceUsers=$this->getByResource($find_source, 'table');
			if(count($sourceUsers)>0){
                $where.=' and '.$this->settings->getTableUsersList().'.user in('.implode(',', $sourceUsers).')';
            }else{
                $where.=' and '.$this->settings->getTableUsersList().'.user = -1';
            }
		}

		if(isset($find_level)){
			$where.=' and '.$this->settings->getTableUsersList().'.level="'.((int) $find_level+1).'"';
		}

		if(!empty($find_type)){
            $where.=' and '.$this->settings->getTableUsersList().'.type="'.$find_type.'"';
        }

        $where.=' and '.$this->settings->getTableUsersList().'.user not in(select
        '.$this->settings->getTableUsersList().'.user as id
        from '.$this->settings->getTableUsersList().' left join commerce_loyal_users as ref
        on('.$this->settings->getTableUsersList().'.user=ref.ref_user)
        where '.$this->settings->getTableUsersList().'.level=1
        group by '.$this->settings->getTableUsersList().'.user
        having count(ref.id)<1)';

		
		$lastOrder=($by=='id')?'':', id '.$order;
		
		$select='select 
		'.$this->settings->getTableUsersList().'.id as id,
		'.$this->settings->getTableUsersList().'.ref_user as ref,
		'.$this->settings->getTableUsersList().'.user as user,
		('.$this->settings->getTableUsersList().'.level-1) as level,
		'.$this->settings->getTableUsersList().'.date_create as date_create_sort,
		'.$DB->DateToCharFunction($this->settings->getTableUsersList().'.date_create').' as date_create,
		b_user.LOGIN as ref_login,
		b_user2.LOGIN as user_login,
		concat(b_user.NAME, " ", b_user.LAST_NAME) as ref_name,
		concat(b_user2.NAME, " ", b_user2.LAST_NAME) as user_name,
		ifnull('.$this->settings->getTableRefCoupons().'.coupon, '.$this->settings->getTablePartnerSiteList().'.site) as source,
		'.$this->settings->getTableUsersList().'.type as type
		from '.$this->settings->getTableUsersList().'
		left join b_user on('.$this->settings->getTableUsersList().'.ref_user=b_user.id)
		left join b_user as b_user2 on('.$this->settings->getTableUsersList().'.user=b_user2.id)
		left join '.$this->settings->getTableRefCoupons().' on ('.$this->settings->getTableUsersList().'.type="coupon" and '.$this->settings->getTableUsersList().'.source_link>0 and '.$this->settings->getTableUsersList().'.source_link='.$this->settings->getTableRefCoupons().'.id)
		left join '.$this->settings->getTablePartnerSiteList().' on ('.$this->settings->getTableUsersList().'.type="partnerSite" and '.$this->settings->getTableUsersList().'.source_link>0 and '.$this->settings->getTableUsersList().'.source_link='.$this->settings->getTablePartnerSiteList().'.id)
		where '.$this->settings->getTableUsersList().'.ref_user>-1'.$where.'
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
				"id" => "level",
				"content" => Loc::getMessage("commerce.loyaltyprogram_HEADER_LEVEL"),
				"sort" => "level",
				"default" => true
			),
			array(
				"id" => "ref",
				"content" => Loc::getMessage("commerce.loyaltyprogram_HEADER_REF"),
				"sort" => "ref_login",
				"default" => true
			),
			array(
				"id" => "user",
				"content" => Loc::getMessage("commerce.loyaltyprogram_HEADER_USERS"),
				"sort" => "user",
				"default" => true
			),
			array(
				"id" => "date_create",
				"content" => Loc::getMessage("commerce.loyaltyprogram_HEADER_DATE_CREATE"),
				"sort" => "date_create_sort",
				"default" => true
			),
			array(
				"id" => "type",
				"content" => Loc::getMessage("commerce.loyaltyprogram_HEADER_TYPE"),
				"sort" => "type",
				"default" => true
			),
			array(
				"id" => "source",
				"content" => Loc::getMessage("commerce.loyaltyprogram_HEADER_SOURCE"),
				"sort" => "source",
				"default" => true
			)
		));
		
		while($arRes = $rsData->Fetch()){
			$row = $this->lAdmin->AddRow($arRes['id'], $arRes);
			if(empty($arRes['ref'])){
				$row->AddViewField("ref", ' ');
			}else{
				$row->AddViewField("ref", '<a href="/bitrix/admin/user_edit.php?lang='.SITE_ID.'&ID='.$arRes['ref'].'">['.$arRes['ref'].']</a> '.$arRes['ref_login'].' ('.$arRes['ref_name'].')');
			}
			
			$row->AddViewField("user", '<a href="/bitrix/admin/user_edit.php?lang='.SITE_ID.'&ID='.$arRes['user'].'">['.$arRes['user'].']</a> '.$arRes['user_login'].' ('.$arRes['user_name'].')');
			$row->AddViewField("type", $this->linkType[$arRes['type']]);
			
			if($arRes['type']=='partnerSite'){
				$arRes['source']=str_replace(['http://', 'https://'], '', $arRes['source']);
				if(function_exists('idn_to_utf8')){
					$arRes['source']=(LANG_CHARSET=='windows-1251')?iconv('UTF-8' , 'CP1251' , idn_to_utf8($arRes['source'])):idn_to_utf8($arRes['source']);
				}
			}
			$row->AddViewField("source", $arRes['source']);


			$arActions = [];
			$arActions[] = [
				"ICON"=>"delete",
				"TEXT"=>Loc::getMessage("commerce.loyaltyprogram_TABLE_DELETE"),
				"ACTION"=>"if(confirm('".Loc::getMessage('commerce.loyaltyprogram_TABLE_DELETE_CONFIRM')."')) ".$this->lAdmin->ActionDoGroup($arRes['id'], "delete")
			];

			 if($rights > "E"){
				$row->AddActions($arActions);
			 }

		}
		
		$aContext = [
			[
				"TEXT"=>Loc::getMessage("commerce.loyaltyprogram_REF_TREE_MODE"),
				"LINK"=>"commerce_loyaltyprogram_referrals.php?mode=tree&lang=".LANG,
				"TITLE"=>Loc::getMessage("commerce.loyaltyprogram_REF_TREE_MODE")
			]
		];
		
        $this->lAdmin->AddAdminContextMenu($aContext);

		$this->lAdmin->AddFooter(
		  array(
			array("title"=>Loc::getMessage("MAIN_ADMIN_LIST_SELECTED"), "value"=>$rsData->SelectedRowsCount()), // ���-�� ���������
			array("counter"=>true, "title"=>Loc::getMessage("MAIN_ADMIN_LIST_CHECKED"), "value"=>"0"), // ������� ��������� ���������
		  )
		);
		if($rights > "E"){
			$this->lAdmin->AddGroupActionTable([
				"delete"=>Loc::getMessage("commerce.loyaltyprogram_TABLE_DELETE")
			]);
		 }

		$this->lAdmin->CheckListMode();
	}
	
	public function getTableList(){
		 $this->lAdmin->DisplayList();
	}

	private function createTree($row){
		$li='<a href="javascript:void(0);">'.$row['fullName'].'</a>';
		if(!empty($row['children'])){
			$li.='<ul>';
			foreach($row['children'] as $nextRow){
				$li.='<li>'.$this->createTree($nextRow).'</li>';
			}
			$li.='</ul>';
		}
		return $li;
	}
	
	private function setDataForJSON($tree){
		if(empty($tree['name'])){
			$tree['name']=$tree['fullName'];
		}
		unset($tree['fullName']);
		if(!empty($tree['children'])){
			$tree['children']=array_values($tree['children']);
			foreach($tree['children'] as &$nextJson){
				if(!empty($nextJson['children'])){
					$nextJson=$this->setDataForJSON($nextJson);
				}else{
					$nextJson['size']=10;
					$nextJson['name']=$nextJson['fullName'];
					unset($nextJson['fullName']);
				}
			}
		}
		return $tree;
	}
	
	public function getTreeList(){
		global $find_id, $find_user, $find_date_from, $find_date_to, $find_level, $find_type, $find_source, $del_filter;
		global $DB;
        $where='';

        if(!empty($del_filter) && $del_filter=='Y'){
            unset($find_id, $find_user, $find_level, $find_type, $find_source, $find_date_from, $find_date_to);
        }

        $whereBase=' and '.$this->settings->getTableUsersList().'.user not in(select
        '.$this->settings->getTableUsersList().'.user as id
        from '.$this->settings->getTableUsersList().' left join commerce_loyal_users as ref
        on('.$this->settings->getTableUsersList().'.user=ref.ref_user)
        where '.$this->settings->getTableUsersList().'.level=1
        group by '.$this->settings->getTableUsersList().'.user
        having count(ref.id)<1)';

        if(isset($find_level) && (int) $find_level>-1 && $find_level!=''){
            $where.=' and '.$this->settings->getTableUsersList().'.level<='.(int) $find_level;
        }

        if(!empty($find_type)){
            $users=[];
            $parentUsers=[];
            $rsData=$DB->Query('select * from '.$this->settings->getTableUsersList().' where type="'.$find_type.'"'.$where.';');
            while($row = $rsData->Fetch()){
                $users[]=$row['user'];
                if($row['ref_user']>0){
                    $parentUsers[]=$row['ref_user'];
                }
            }
            if(count($parentUsers)>0){
                while(count($parentUsers)>0){
                    $rsData=$DB->Query('select * from '.$this->settings->getTableUsersList().' where user in('.implode(',', array_unique($parentUsers)).');');
                    $parentUsers=[];
                    while($row = $rsData->Fetch()){
                        $users[]=$row['user'];
                        if($row['ref_user']>0){
                            $parentUsers[]=$row['ref_user'];
                        }
                    }
                }
            }
            if(count($users)>0){
                $where.=' and '.$this->settings->getTableUsersList().'.user  in('.implode(',',$users).')';
            }else{
                $where.=' and '.$this->settings->getTableUsersList().'.type="111"';
            }
        }

        if(!empty($find_user)){
            $refUsers=$this->gelallParent($find_user);
            if(count($refUsers)>0){
                $where.=' and '.$this->settings->getTableUsersList().'.user in('.implode(',', $refUsers).')';
            }else{
                $where.=' and '.$this->settings->getTableUsersList().'.user = -1';
            }
        }

        if(!empty($find_source)){
            $sourceUsers=$this->getByResource($find_source, 'tree');
            if(count($sourceUsers)>0){
                $where.=' and '.$this->settings->getTableUsersList().'.user in('.implode(',', $sourceUsers).')';
            }else{
                $where.=' and '.$this->settings->getTableUsersList().'.user = -1';
            }
        }

        if(!empty($find_date_from) || !empty($find_date_to)){
            $intervalUsers=$this->getByInterval();
            if(count($intervalUsers)>0){
                $where.=' and '.$this->settings->getTableUsersList().'.user in('.implode(',', $intervalUsers).')';
            }else{
                $where.=' and '.$this->settings->getTableUsersList().'.user = -1';
            }
        }

        $select='select 
        '.$this->settings->getTableUsersList().'.*,
        b_user.NAME as NAME,
        b_user.LAST_NAME as LAST_NAME,
        b_user.SECOND_NAME as SECOND_NAME,
        b_user.LOGIN as LOGIN
        from '.$this->settings->getTableUsersList().'
        left join b_user on(b_user.id='.$this->settings->getTableUsersList().'.user)
        where 1=1'.$whereBase.$where.'
        order by level;';

		$rsData = $DB->Query($select);
		$this->treeRows=[];
		$this->tmpTree=[];
		while($row = $rsData->Fetch()){
			unset($targetR);
			//$fullName=Tools::getUserName($row).'--'.$row['type'].'--';
			$fullName=Tools::getUserName($row);
			$row['fullName']=$fullName.' ['.$row['user'].']';
			if($row['level']==1){
				$row['tree']='';
				$this->treeRows[$row['user']]=$row;
				if(!empty($find_id) && $find_id==$row['user']){
					$selectedRow=&$this->treeRows[$row['user']];
				}
			}elseif($row['level']==2){
				$row['tree']=$row['ref_user'];
				$this->treeRows[$row['ref_user']]['children'][$row['user']]=$row;
				if(!empty($find_id) && $find_id==$row['user']){
					$selectedRow=&$this->treeRows[$row['ref_user']]['children'][$row['user']];
				}
			}else{
				$row['tree']=$this->tmpTree[$row['ref_user']]['tree'].'_'.$row['ref_user'];
				$tmpTree=explode('_', $row['tree']);
				foreach($tmpTree as $nextChildren){
					if(empty($targetR)){
						$targetR=&$this->treeRows[$nextChildren]['children'];
					}else{
						$targetR=&$targetR[$nextChildren]['children'];
					}
				}
				$targetR[$row['user']]=$row;
				if(!empty($find_id) && $find_id==$row['user']){
					$selectedRow=&$targetR[$row['user']];
				}

			}
			$this->tmpTree[$row['user']]=$row;
		}
		$list='';
		
		if(!empty($selectedRow)){
			$forJson=$this->setDataForJSON($selectedRow);
		}else{
			$forJson=$this->setDataForJSON([
				'fullName'=>'mainLoyalty',
				'name'=>'mainLoyalty',
				'level'=>0,
				'children'=>$this->treeRows
			]);
		}
        /*$forJson["children"] = [
            0 => [
                'name' => 'user [1]',
                'id' => 1,
                'children'=>[
                        0=>['name' => 'user [2]','id' => 2],
                        1=>['name' => 'user [3]','id' => 3]
                ]
            ]
        ];*/
        $list.='<script>var treeRefNet='.\Bitrix\Main\Web\Json::encode($forJson).'</script>';
		return $list;
	}
	
	public function isTemporaryLink($coupon){
		$status=false;
		if(!empty($this->moduleOptions['ref_basket_rules'])){
			$rules=explode(',',$this->moduleOptions['ref_basket_rules']);
			if(count($rules)>0){
				$couponList = \Bitrix\Sale\Internals\DiscountCouponTable::getList(
					['filter' => ['COUPON' => $coupon]]
				);
				if($couponRow = $couponList->fetch()){
					if(in_array($couponRow['DISCOUNT_ID'], $rules)){
						$keyRule=array_search($couponRow['DISCOUNT_ID'], $rules);
						if($keyRule!==false){
							$keyRule=($keyRule==0)?'':$keyRule;
							if(!empty($this->moduleOptions['ref_coupon_istemporary'.$keyRule]) && $this->moduleOptions['ref_coupon_istemporary'.$keyRule]=='Y'){
								$status=true;
							}
						}
					}
				}
			}
		}
		return $status;
	}

}

?>