var loyaltyTools={
    //checkUser: Check for existence, check for modifiers ("notreferral" - should not belong to the referral system)
	setHint:function(){
		var hints=document.querySelectorAll('.skwb24-item-hint');
		for(var i=0; i<hints.length; i++){
			var hint=hints[i];
			if(hint.dataset.hint){
				new top.BX.CHint({
					parent: hint,
					show_timeout: 10,
					hide_timeout: 200,
					dx: 2,
					preventHide: true,
					min_width: 400,
					hint: hint.dataset.hint
				});
			}
		}
	},
    checkUser:function(userId, modifier){
        //Check for existence
        var status='notExists';
        BX.ajax({
            url: '/bitrix/admin/commerce_loyaltyprogram_referrals.php?lang=ru',
            data: {
                'ajax':'y',
                'userId':userId,
            },
            method: 'POST',
            dataType: 'json',
            timeout:300,
            async: false,
            onsuccess: function(data){
                if(data && data.existStatus==true){
                    status='isExists';
                    if(modifier){
                        //Check for modifier
                        BX.ajax({
                            url: '/bitrix/admin/commerce_loyaltyprogram_referrals.php?lang=ru',
                            data: {
                                'ajax':'y',
                                'userId':userId,
                                'modifier':modifier
                            },
                            method: 'POST',
                            dataType: 'json',
                            timeout:300,
                            async: false,
                            onsuccess: function(data){
                                if(data && data.existStatus){
                                    status=data.existStatus;
                                }
                            },
                            onfailure: function(data){
                                console.log(data);
                            }
                        });
                    }
                }
            },
            onfailure: function(data){
                console.log(data);
            }
        });
        return status;
    }
}

var manageCondTree={
	init:function(params){
		this.rootNode=params.rootNode;
		this.showControls=params.showControls;
		this.condDescriptions=[];
		this.conditionRulesSet=[
			{
				code:'allow_bonus_max',
				type:'SELECT',
				left:0,
				right:3,
				showVal:['Y', 'F']
			},{
				code:'through_time',
				type:'SELECT',
				left:0,
				right:3,
				showVal:'Y'
			},{
				code:'limit_time',
				type:'SELECT',
				left:0,
				right:3,
				showVal:'Y'
			},{
				code:'before_date',
				type:'SELECT',
				left:0,
				right:3,
				showVal:['Y', 'A']
			}
		];
		for(key in this.showControls){
			if(this.showControls[key].children){
				for(keyChildren in this.showControls[key].children){
					var nextChildren=this.showControls[key].children[keyChildren]
					if(nextChildren.description){
						this.condDescriptions[nextChildren.controlId]=nextChildren.description;
					}
				}
			}
		}
		
		setTimeout(function(){manageCondTree.updateView();}, 300);
		setTimeout(function(){manageCondTree.setActionMarker();}, 300);
	},
	setActionMarker:function(){
		let actionLinks=document.querySelectorAll('a[id*="number_action_link"]');
		for(let nextLink of actionLinks){
			letParentWrapper=nextLink.closest('.condition-container');
			if(letParentWrapper && nextLink.innerHTML>0){
				BX.insertAfter(BX.create('span',{
					attrs:{
						className: 'actionLabel'
					},
					text: '#'+nextLink.innerHTML
				}), letParentWrapper);
			}
		}
	},
	setVisibleTree:function(){
		let statusShow='none';
		if(Array.isArray(this.conditionAnimate.showVal)){
			statusShow = (this.conditionAnimate.showVal.includes(this.value)) ? 'inline-block' : 'none';
		}else {
			statusShow = (this.value == this.conditionAnimate.showVal) ? 'inline-block' : 'none';
		}
		if(this.conditionAnimate && this.conditionAnimate.left>0){
			var prevNodeCount=0, prevNode=this;
			while(prevNodeCount<=this.conditionAnimate.left){
				prevNode=prevNode.previousElementSibling;
				if(prevNode.nodeName=='SPAN' || prevNode.nodeName=='A'){
					prevNode.style.display=statusShow;
					prevNodeCount++;
				}
			}
		}
		if(this.conditionAnimate && this.conditionAnimate.right>0){
			var nextNodeCount=0, nextNode=this;
			while(nextNodeCount<=this.conditionAnimate.right){
				nextNode=nextNode.nextElementSibling;
				if(nextNode.nodeName=='SPAN' || nextNode.nodeName=='A'){
					nextNode.style.display=statusShow;
					nextNodeCount++;
				}
			}
		}
	},
	conditionRules:function(){
		let conditionInputs=document.querySelectorAll('.condition-container>input, .condition-container>select');
		if(conditionInputs.length>0 && this.conditionRulesSet.length>0){
			for(let i=0; i<conditionInputs.length; i++){
				let nextO=conditionInputs[i];
				if(nextO.id){
					for(let j=0; j<this.conditionRulesSet.length; j++){
						let nextConditionRulesSet=this.conditionRulesSet[j];
						if(nextO.nodeName==nextConditionRulesSet.type && nextO.id.indexOf(nextConditionRulesSet.code)!=-1){
							nextO.conditionAnimate=nextConditionRulesSet;
							nextO.addEventListener('change', this.setVisibleTree);
							this.setVisibleTree.call(nextO);
							break;
						}
					}
				}
			}
		}
	},
	updateEventListener:function(){
		let selectsConst=this.rootNode.querySelectorAll('select[id*=popupPropsCont__], input[id*=bonus_delay], select[id*=bonus_delay_type]');
		for(let i=0; i<selectsConst.length; i++){
			if(!selectsConst[i].condMarker){
				selectsConst[i].condMarker=true;
				selectsConst[i].addEventListener('change', function(){
					manageCondTree.updateView();
				});
			}
		}
	},
	updateColor:function(){
		let rows=this.rootNode.querySelectorAll('.condition-container');
		for(let i=0; i<rows.length; i++){
			for(let j=0; j<rows[i].childNodes.length; j++){
				let nextEl=rows[i].childNodes[j];
				if(nextEl.type && nextEl.type=='hidden' && nextEl.value!=''){
					rows[i].classList.add(nextEl.value);
				}
			}
		}
	},
	updateHints:function(){
		let rows=this.rootNode.querySelectorAll('input[type=hidden]');
		for(let i=0; i<rows.length; i++){
			if(this.condDescriptions[rows[i].value]){
				let currentHint=rows[i].parentElement.querySelector('.skwb24-item-hint');
				if(!currentHint){
					let hintSpan=document.createElement("span");
					hintSpan.id='hint_condition_'+i;
					hintSpan.className='skwb24-item-hint';
					hintSpan.innerHTML='?';
					rows[i].parentElement.insertBefore(hintSpan, rows[i]);
					new top.BX.CHint({
						parent: hintSpan,
						show_timeout:10,
						hide_timeout:200,
						dx:2,
						preventHide:true,
						min_width:400,
						hint:this.condDescriptions[rows[i].value]
					});
				}
			}
		}
	},
	updfatePeriodMarker:function(){
		let selectsPeriod=this.rootNode.querySelectorAll('select[id*=period]'),
			bonus_delay=this.rootNode.querySelectorAll('input[id*=bonus_delay]'),
			bonus_delay_type=this.rootNode.querySelectorAll('select[id*=bonus_delay_type]'),
			through_time=this.rootNode.querySelectorAll('select[id*=through_time]');

		for(let i=0; i<selectsPeriod.length; i++){
			let parentBlock=selectsPeriod[i].closest('.condition-container');
			if(parentBlock){
				let stickerPeriod=parentBlock.querySelector('.swPeriodSticker');
				if(!stickerPeriod){
					stickerPeriod=document.createElement('div');
					stickerPeriod.className='swPeriodSticker';
					parentBlock.insertBefore(stickerPeriod, parentBlock.firstChild);
				}
			
				tmpBonusDelay=(through_time[i] && through_time[i].value=='N')?0:bonus_delay[i].value;
				let tmpPeriod=this.prevRunPeriod(selectsPeriod[i].value, tmpBonusDelay, bonus_delay_type[i].value);
				if(tmpPeriod!=''){
					stickerPeriod.innerHTML='<div><span>'+BX.message('periodLang')+'</span>: '+tmpPeriod+'<span> '+BX.message('periodLangOver')+'</span>: '+this.prevPeriod(selectsPeriod[i].value)+'</div>';
				}else{
					BX.remove(stickerPeriod);
				}
				
			}
		}
	},
	updateView:function(){
		this.updateEventListener();
		this.updateColor();
		this.updateHints();
		this.updfatePeriodMarker();
		this.conditionRules();
	},
	prevRunPeriod:function(selectsPeriod, bonus_delay, bonus_delay_type){
		bonus_delay=parseInt(bonus_delay);
		if(selectsPeriod){
			let finishDate=this.prevPeriod(selectsPeriod).split(' - '),
				strDay, strMonth;
			finishDate=finishDate[1];
			finishDate=finishDate.split('.');
			finishDate=new Date(finishDate[2]+'-'+finishDate[1]+'-'+finishDate[0]);
			if(bonus_delay>0){
				if(bonus_delay_type=='hour'){
					finishDate.setHours(finishDate.getHours()+bonus_delay);
				}else if(bonus_delay_type=='day'){
					finishDate.setDate(finishDate.getDate() + bonus_delay);
				}else if(bonus_delay_type=='week'){
					finishDate.setDate(finishDate.getDate() + (bonus_delay*7));
				}else if(bonus_delay_type=='month'){
					finishDate.setMonth(finishDate.getMonth() + bonus_delay);
				}
			}
			strDay=finishDate.getDate();
			strMonth=finishDate.getMonth()+1;
			strMonth=(strMonth>9)?strMonth:'0'+strMonth;
			strDay=(strDay>9)?strDay:'0'+strDay;
			return strDay+'.'+strMonth+'.'+finishDate.getFullYear();
		}else{
			return '';
		}
	},
	prevPeriod:function(period){
		var dBase=new Date();
		if(period=='month'){
			let md=new Date(dBase.getFullYear(), dBase.getMonth()+1, 0),
				strMonth=md.getMonth()+1;
			if(strMonth<10){
				strMonth='0'+strMonth;
			}
			return '01.'+strMonth+'.'+md.getFullYear()+' - '+md.getDate()+'.'+strMonth+'.'+md.getFullYear();
		}else if(period=='year'){
			return '01.01.'+dBase.getFullYear()+' - 31.12.'+dBase.getFullYear();
		}else if(period=='quarter'){
			if(dBase.getMonth()>8){
				return '01.10.'+(dBase.getFullYear()-1)+' - 31.12.'+(dBase.getFullYear()-1);
			}else if(dBase.getMonth()>5){
				return '01.07.'+dBase.getFullYear()+' - 30.09.'+dBase.getFullYear();
			}else if(dBase.getMonth()>2){
				return '01.04.'+dBase.getFullYear()+' - 30.06.'+dBase.getFullYear();
			}else{
				return '01.01.'+dBase.getFullYear()+' - 31.03.'+dBase.getFullYear();
			}
		}else if(period=='week'){
			let weekDay=dBase.getDay();
			weekDay=(weekDay==0)?0:(7-weekDay);
			let wdEnd=new Date(dBase.getFullYear(), dBase.getMonth(), dBase.getDate()+weekDay),
				wdStart=new Date(dBase.getFullYear(), dBase.getMonth(), (dBase.getDate()+weekDay-6)),
				strStartMonth=wdStart.getMonth()+1,
				strEndMonth=wdEnd.getMonth()+1;
			if(strStartMonth<10){
				strStartMonth='0'+strStartMonth;
			}
			if(strEndMonth<10){
				strEndMonth='0'+strEndMonth;
			}
			return wdStart.getDate()+'.'+strStartMonth+'.'+wdStart.getFullYear()+' - '+wdEnd.getDate()+'.'+strEndMonth+'.'+wdEnd.getFullYear();
		}
	}
}

BX.ready(function(){
	loyaltyTools.setHint();
	let hintConstructor=BX('popupPropsCont');
	if(hintConstructor){

		manageCondTree.init({
			rootNode:hintConstructor,
			showControls:showControls
		});
	}
})