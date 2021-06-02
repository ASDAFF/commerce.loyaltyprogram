var firstLoad=true;
BX.ready(function(){
	//period buttons
	var accountFilterForm=document.querySelector('form[name=bonus_filter]');
	function periodStart(){
		accountFilterForm=document.querySelector('form[name=bonus_filter]');
		var selectTimeButtons=document.querySelectorAll('a.selectTime'),
			fromDateInput=document.querySelector('.lpBonusAccount [name=from_date]'),
			toDateInput=document.querySelector('.lpBonusAccount [name=to_date]');
		for (var i=0; i<selectTimeButtons.length; i++){
			 BX.bind(selectTimeButtons[i], 'click', BX.proxy(setPeriod, selectTimeButtons[i]));
		}
		function setPeriod(){
			fromDateInput.value=typeSelectPeriod[this.dataset.period].from;
			toDateInput.value=typeSelectPeriod[this.dataset.period].to;
		}
		
		var showWriteForm=document.querySelector('a.show_write_form');
		if(showWriteForm){
			showWriteForm.removeEventListener('click', showWriteOffForm);
			showWriteForm.addEventListener('click', showWriteOffForm);
			let form=document.getElementById('write_off');
			form.removeEventListener('submit', writeOff);
			form.addEventListener('submit', writeOff);
		}
		var showWriteOffService=document.querySelector('a.writeoff_select');
		if(showWriteOffService){
			showWriteOffService.removeEventListener("click", selectWriteOffList);
			showWriteOffService.addEventListener("click", selectWriteOffList);
		}
	}
	
	function selectWriteOffList(e){
		e.preventDefault();
		accountFilterForm.type_transactions.value='COMMERCE_LOYAL_WRITEOFF_withdraw';
		accountFilterForm.querySelector('button[type=submit]').click();
	}
	
	function showWriteOffForm(e){
		document.getElementById('write_off').style.display='block';
	}
	
	function writeOff(e){
		form=this;
		e.preventDefault();
		
		let tmpVal=parseFloat(this.bonus.value),
			tmpMin=parseFloat(this.bonus.min),
			tmpMax=parseFloat(this.bonus.max);
		if(tmpMin>tmpVal){
			this.bonus.value=this.bonus.min;
		}else if(tmpMax<tmpVal){
			this.bonus.value=this.bonus.max;
		}
		dataSend={
			writeoff_bonus:this.bonus.value,
			writeoff_requisite:this.id_req.value
		};
		if(this.currency && this.currency.value){
			dataSend.writeoff_currency=this.currency.value;
		}

		BX.ajax({
			url: '/bitrix/components/commerce/loyaltyprogram.account/ajax.php',
			data: dataSend,
			method: 'POST',
			dataType: 'json',
			timeout: 30,
			async: true,
			processData: true,
			emulateOnload: true,
			start: true,
			cache: false,
			onsuccess: function(data){
				let errorBlock=form.querySelector('.error');
				if(errorBlock){
					BX.remove(errorBlock);
				}
				if(data){
					location.reload();
				}else{
					BX.prepend(BX.create(
						'div',{
							attrs:{className: 'error'},
							html:BX.message('sw24_loyaltyprogram.ERROR_WRITE_OFF')
						}
					),form)
				}
			},
			onfailure: function(data){
				console.log(data);
			}
		});
	}
	
	
	function reloadFunctions(a,b){
		periodStart();
		if(!a && !b && !firstLoad){
			setTimeout(function(){
				accountFilterForm=document.querySelector('form[name=bonus_filter]');
				let tmpCoord=accountFilterForm.getBoundingClientRect();
				window.scrollTo(0, (tmpCoord.y+window.pageYOffset));
			}, 400);
		}
		if(firstLoad){
			firstLoad=false;
		}
	}
	
	reloadFunctions();
	
	BX.addCustomEvent('onAjaxSuccess', reloadFunctions); 
	
})

/* select time period */
function returnFormatDate(date){
	return ('0' + date.getDate()).slice(-2)+'.'+('0' + (date.getMonth() + 1)).slice(-2)+'.'+date.getFullYear();
}
var dayWeek=(new Date().getDay()==0)?6:new Date().getDay()-1;
var quarterO=[0,0,0,1,1,1,2,2,2,3,3,3];
var cDate=returnFormatDate(new Date()),
	typeSelectPeriod={
		'today':{
			'from':cDate,
			'to':cDate
		},
		'yesterday':{
			'from':returnFormatDate(new Date(new Date-86400*1000)),
			'to':returnFormatDate(new Date(new Date-86400*1000))
		},
		'week':{
			'from':returnFormatDate(new Date(new Date().getFullYear(),new Date().getMonth(),(new Date().getDate()-dayWeek),0,0,0)),
			'to':returnFormatDate(new Date(new Date().getFullYear(),new Date().getMonth(),(new Date().getDate()-dayWeek+6),0,0,0))
		},
		'month':{
			'from':returnFormatDate(new Date(new Date().getFullYear(),new Date().getMonth(),1,0,0,0)),
			'to':returnFormatDate(new Date(new Date().getFullYear(),(new Date().getMonth()+1),0,0,0,0))
		},
		'year':{
			'from':returnFormatDate(new Date(new Date().getFullYear(),0,1,0,0,0)),
			'to':returnFormatDate(new Date((new Date().getFullYear()+1),0,0,0,0,0))
		},
		'quarter':{
			'from':returnFormatDate(new Date(new Date().getFullYear(),quarterO[new Date().getMonth()],1,0,0,0)),
			'to':returnFormatDate(new Date(new Date().getFullYear(),quarterO[new Date().getMonth()]+3,0,0,0,0))
		},
		'all':{
			'from':'',
			'to':''
		}
	};
	
var managerRequisite={
	init:function(options){
		
		this.requisite_area=options.requisite_area;
		this.requisiteList=[];
		
		this.control={};
		this.control.addLink=this.requisite_area.querySelector('#addRequisite');
		this.control.cancelLink=this.requisite_area.querySelector('#cancelAddRequisite');
		this.control.TypeButtons=this.requisite_area.querySelectorAll('[name=type_req]');
		this.control.AddButton=this.requisite_area.querySelector('#addRequisiteButton');
		
		this.area={};
		this.area.addForm=this.requisite_area.querySelector('#add_area_form');
		this.area.InfoBLock=this.requisite_area.querySelector('.info');
		this.area.ReqList=this.requisite_area.querySelector('.list');
		this.area.ReqListSelect=document.querySelector('#select_cart_area');
		
		new BX.MaskedInput({
            mask: '9999-9999-9999-9999',
            input: this.area.addForm.querySelector('input[name=cart]'),
            placeholder: '_'
        });
		new BX.MaskedInput({
            mask: '99999999999999999999',
            input: this.area.addForm.querySelector('input[name=invoice]'),
            placeholder: '_'
        });
		new BX.MaskedInput({
            mask: '999999999',
            input: this.area.addForm.querySelector('input[name=bik]'),
            placeholder: '_'
        });
		
		this.statuses={};
		this.statuses.addType='cart';
		
		if(options.requisites.length>0){
			this.requisiteList=options.requisites;
		}
		
		this.setListRequisites();
		this.setControlEvent();
		requisites:[]
	},
	setControlEvent:function(){
		this.control.addLink.addEventListener("click", function(event){
			managerRequisite.area.InfoBLock.innerHTML='';
			this.style.display='none';
			managerRequisite.control.cancelLink.style.display='';
			managerRequisite.area.addForm.style.display='';
		});
		this.control.AddButton.addEventListener("click", function(event){
			managerRequisite.area.InfoBLock.innerHTML='';
			managerRequisite.area.InfoBLock.classList.remove('error', 'success');
			if(managerRequisite.statuses.addType=='cart' && managerRequisite.area.addForm.querySelector('input[name=cart]').value==''){
				managerRequisite.area.InfoBLock.innerHTML=BX.message('sw24_loyaltyprogram.ERROR_EMPTY_CART');
				managerRequisite.area.InfoBLock.classList.add('error');
			}else if(
				managerRequisite.statuses.addType=='invoice'
				&& (
					managerRequisite.area.addForm.querySelector('input[name=bik]').value==''
					|| managerRequisite.area.addForm.querySelector('input[name=invoice]').value==''
				)
			){
				managerRequisite.area.InfoBLock.innerHTML=BX.message('sw24_loyaltyprogram.ERROR_EMPTY_INVOICE');
				managerRequisite.area.InfoBLock.classList.add('error');
			}else{
				BX.ajax({
					url: '/bitrix/components/commerce/loyaltyprogram.account/ajax.php',
					data: {
						type:managerRequisite.statuses.addType,
						cart:managerRequisite.area.addForm.querySelector('input[name=cart]').value,
						invoice:managerRequisite.area.addForm.querySelector('input[name=invoice]').value,
						bik:managerRequisite.area.addForm.querySelector('input[name=bik]').value
					},
					method: 'POST',
					dataType: 'json',
					timeout: 30,
					async: true,
					processData: true,
					emulateOnload: true,
					start: true,
					cache: false,
					onsuccess: function(data){
						managerRequisite.area.InfoBLock.innerHTML='';
						managerRequisite.area.InfoBLock.classList.remove('error', 'success');
						if(data){
							managerRequisite.area.InfoBLock.innerHTML=BX.message('sw24_loyaltyprogram.REQUISITE_SUCCESS_ADD');
							managerRequisite.area.InfoBLock.classList.add('success');
							managerRequisite.area.addForm.style.display='none';
							managerRequisite.control.addLink.style.display='';
							managerRequisite.control.cancelLink.style.display='none';
							managerRequisite.getListRequisites(data.list);
						}else{
							managerRequisite.area.InfoBLock.innerHTML=BX.message('sw24_loyaltyprogram.ERROR_SERVER');
							managerRequisite.area.InfoBLock.classList.add('error');
						}
					},
					onfailure: function(data){
						console.log(data);
					}
				});
			}
		});
		this.control.cancelLink.addEventListener("click", function(event){
			this.style.display='none';
			managerRequisite.control.addLink.style.display='';
			managerRequisite.area.addForm.style.display='none';
		});
		for(let tcb of this.control.TypeButtons){
			tcb.addEventListener("change", function(event){
				managerRequisite.area.addForm.querySelector('input[name=cart]').closest('label').style.display='none';
				managerRequisite.area.addForm.querySelector('input[name=bik]').closest('label').style.display='none';
				managerRequisite.area.addForm.querySelector('input[name=invoice]').closest('label').style.display='none';
				managerRequisite.statuses.addType=this.value;
				if(this.value=='cart'){
					managerRequisite.area.addForm.querySelector('input[name=cart]').closest('label').style.display='';
				}else{
					managerRequisite.area.addForm.querySelector('input[name=bik]').closest('label').style.display='';
					managerRequisite.area.addForm.querySelector('input[name=invoice]').closest('label').style.display='';
				}
			})
		}
	},
	getListRequisites:function(listRequisite){
		if(listRequisite){
			this.requisiteList=listRequisite;
		}
		this.setListRequisites();
	},
	setListRequisites:function(){
		//this.requisiteList
		this.area.ReqList.innerHTML='';
		this.area.ReqListSelect.innerHTML='';
		if(this.requisiteList.length>0){
			var tmpList='';
			var tmpListSelect='';
			var ruleLine='<span class="success" style="display:none;">'+BX.message('sw24_loyaltyprogram.EDITED_REQUISITE')+'</span> <a href="javacript:void(0);" class="startEdit">'+BX.message('sw24_loyaltyprogram.EDIT_REQUISITE')+'</a> <button class="edit" style="display:none;" type="button">'+BX.message('sw24_loyaltyprogram.EDIT_REQUISITE')+'</button> <button class="delete" style="display:none;" type="button">'+BX.message('sw24_loyaltyprogram.DELETE_REQUISITE')+'</button> <button class="cancel" style="display:none;" type="button">'+BX.message('sw24_loyaltyprogram.CANCEL_ADD_REQUISITE')+'</button>'
			for(let i=0; i<this.requisiteList.length; i++){
				var nextReq=this.requisiteList[i];
				let cChecked=this.requisiteList.length-i==1?' checked':'';
				let cSelected=this.requisiteList.length-i==1?' selected':'';
				let reqInput='<input type="hidden" name="id_req1" value="'+nextReq.id+'"'+cChecked+'>';
				if(nextReq.type=='cart'){
					tmpList+='<tr><td>'+reqInput+BX.message('sw24_loyaltyprogram.PAY_TYPE_CART')+'</td><td><input name="cart" type="text" value="'+nextReq.cart+'" disabled></td><td>&nbsp;</td><td>&nbsp;</td><td>'+ruleLine+'</td></tr>';
					tmpListSelect+='<option '+cSelected+' value="'+nextReq.id+'">'+BX.message('sw24_loyaltyprogram.PAY_TYPE_CART')+': '+nextReq.cart+'</option>';
				}else{
					tmpList+='<tr><td>'+reqInput+BX.message('sw24_loyaltyprogram.PAY_TYPE_INVOICE')+'</td><td>&nbsp;</td><td><input name="invoice" type="text" value="'+nextReq.invoice+'" disabled></td><td><input name="bik" type="text" value="'+nextReq.bik+'" disabled></td><td>'+ruleLine+'</td></tr>';
					tmpListSelect+='<option '+cSelected+' value="'+nextReq.id+'">'+BX.message('sw24_loyaltyprogram.PAY_TYPE_INVOICE')+': '+nextReq.invoice+'</option>';
				}
			}
			this.area.ReqListSelect.innerHTML='<select name="id_req">'+tmpListSelect+'</select>';
			this.area.ReqList.innerHTML=tmpList;
			this.setRequisiteManager();
			
			//set mask
			let carts=this.area.ReqList.querySelectorAll('input[name=cart]');
			if(carts.length>0){
				for(let nextCart of carts){
					new BX.MaskedInput({
						mask: '9999-9999-9999-9999',
						input: nextCart,
						placeholder: '_'
					});
				}
			}
			
			let invoice=this.area.ReqList.querySelectorAll('input[name=invoice]');
			if(invoice.length>0){
				for(let nextCart of invoice){
					new BX.MaskedInput({
						 mask: '99999999999999999999',
						input: nextCart,
						placeholder: '_'
					});
				}
			}
			let bik=this.area.ReqList.querySelectorAll('input[name=bik]');
			if(bik.length>0){
				for(let nextCart of bik){
					new BX.MaskedInput({
						mask: '999999999',
						input: nextCart,
						placeholder: '_'
					});
				}
			}
			BX('write_off').querySelector('button[type=submit]').disabled=false;
		}else{
			this.area.ReqListSelect.innerHTML+=' <div class="error">'+BX.message('sw24_loyaltyprogram.EMPTY_CARTLIST')+'</div>';
			BX('write_off').querySelector('button[type=submit]').disabled=true;
		}
		this.area.ReqListSelect.innerHTML+=' <a href="javascript:void(0);" id="open_edit_requisite">'+BX.message('sw24_loyaltyprogram.PAY_SETTING')+'</a>';
		this.setRequisiteListManager();
	},
	setRequisiteListManager:function(){
		let showLinkArea=this.area.ReqListSelect.querySelector('#open_edit_requisite');
		if(showLinkArea){
			showLinkArea.addEventListener("click", function(event){
				managerRequisite.requisite_area.style.display=managerRequisite.requisite_area.style.display=='none'?'':'none';
			});
		}
	},
	setRequisiteManager:function(){
		
		let startEditLinks=this.area.ReqList.querySelectorAll('.startEdit');
		for(let netxLink of startEditLinks){
			netxLink.addEventListener("click", function(event){
				let parentLi=this.closest('tr');
				let cart=parentLi.querySelector('input[name=cart]'),
					infoBlock=parentLi.querySelector('.success'),
					cancelButton=parentLi.querySelector('.cancel'),
					deleteButton=parentLi.querySelector('.delete'),
					editButton=parentLi.querySelector('.edit');
				if(cart){
					cart.disabled=false;
				}else{
					parentLi.querySelector('input[name=invoice]').disabled=false;
					parentLi.querySelector('input[name=bik]').disabled=false;
				}
				infoBlock.style.display='none';
				editButton.style.display='';
				cancelButton.style.display='';
				deleteButton.style.display='';
				this.style.display='none';
			});
		}
		
		let cancelButtons=this.area.ReqList.querySelectorAll('.cancel');
		for(let nextButton of cancelButtons){
			nextButton.addEventListener("click", function(event){
				let parentLi=this.closest('tr');
				let cart=parentLi.querySelector('input[name=cart]'),
					infoBlock=parentLi.querySelector('.success'),
					editLink=parentLi.querySelector('.startEdit'),
					deleteButton=parentLi.querySelector('.delete'),
					editButton=parentLi.querySelector('.edit');
				if(cart){
					cart.disabled=true;
				}else{
					parentLi.querySelector('input[name=invoice]').disabled=true;
					parentLi.querySelector('input[name=bik]').disabled=true;
				}
				infoBlock.style.display='none';
				editButton.style.display='none';
				deleteButton.style.display='none';
				editLink.style.display='';
				this.style.display='none';
			});
		}
		
		let editButtons=this.area.ReqList.querySelectorAll('.edit');
		for(let nextButton of editButtons){
			nextButton.addEventListener("click", function(event){
				let parentLi=this.closest('tr');
				let cart=parentLi.querySelector('input[name=cart]'),
					infoBlock=parentLi.querySelector('.success'),
					editLink=parentLi.querySelector('.startEdit'),
					deleteButton=parentLi.querySelector('.delete'),
					cancelButton=parentLi.querySelector('.cancel');
					sendData={};
				
				sendData.type='updateRequisites';
				sendData.id=parentLi.querySelector('input[name=id_req1]').value;
				
				if(cart){
					cart.disabled=true;
					sendData.cart=cart.value;
				}else{
					parentLi.querySelector('input[name=invoice]').disabled=true;
					parentLi.querySelector('input[name=bik]').disabled=true;
					sendData.invoice=parentLi.querySelector('input[name=invoice]').value;
					sendData.bik=parentLi.querySelector('input[name=bik]').value;
				}

				infoBlock.style.display='none';
				cancelButton.style.display='none';
				deleteButton.style.display='none';
				editLink.style.display='none';
				this.style.display='none';
				BX.ajax({
					url: '/bitrix/components/commerce/loyaltyprogram.account/ajax.php',
					data:sendData,
					method: 'POST',
					dataType: 'json',
					timeout: 30,
					async: true,
					processData: true,
					emulateOnload: true,
					start: true,
					cache: false,
					onsuccess: function(data){
						editLink.style.display='';
						if(data){
							infoBlock.style.display='';
							if(sendData.cart){
								managerRequisite.area.ReqListSelect.querySelector('option[value="'+sendData.id+'"]').innerHTML=BX.message('sw24_loyaltyprogram.PAY_TYPE_CART')+':'+sendData.cart;
							}else{
								managerRequisite.area.ReqListSelect.querySelector('option[value="'+sendData.id+'"]').innerHTML=BX.message('sw24_loyaltyprogram.PAY_TYPE_INVOICE')+':'+sendData.invoice;
							}
						}
					},
					onfailure: function(data){
						console.log(data);
					}
				});
				
			});
		}
		
		let deleteButtons=this.area.ReqList.querySelectorAll('.delete');
		for(let nextButton of deleteButtons){
			nextButton.addEventListener("click", function(event){
				let parentLi=this.closest('tr');
				let cart=parentLi.querySelector('input[name=cart]'),
					infoBlock=parentLi.querySelector('.success'),
					editLink=parentLi.querySelector('.startEdit'),
					editButton=parentLi.querySelector('.delete'),
					cancelButton=parentLi.querySelector('.cancel');
					sendData={};
				
				sendData.type='deleteRequisites';
				sendData.id=parentLi.querySelector('input[name=id_req1]').value;
				
				infoBlock.style.display='none';
				cancelButton.style.display='none';
				editButton.style.display='none';
				editLink.style.display='none';
				this.style.display='none';
				BX.ajax({
					url: '/bitrix/components/commerce/loyaltyprogram.account/ajax.php',
					data:sendData,
					method: 'POST',
					dataType: 'json',
					timeout: 30,
					async: true,
					processData: true,
					emulateOnload: true,
					start: true,
					cache: false,
					onsuccess: function(data){
						editLink.style.display='';
						if(data){
							parentLi.remove();
							managerRequisite.area.ReqListSelect.querySelector('option[value="'+sendData.id+'"]').remove();
							managerRequisite.getListRequisites(data.list);
						}
					},
					onfailure: function(data){
						console.log(data);
					}
				});
				
			});
		}
		
	}
}