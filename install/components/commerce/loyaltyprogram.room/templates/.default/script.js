function show_url(o){
	let parentNode=o.parentElement,
	elem = parentNode.querySelector('.perfectPreview'),
	sub = parentNode.querySelector('.sub_url'),
	selection=window.getSelection();
	sub.style.display = "inline-block";
	selection.selectAllChildren(elem);
	o.style.display = "none";
	try {  
		var successful = document.execCommand('copy');  
	} 
		catch(err) {  
	}  
}

function show_edit_coupon(o){
	let parentNode=o.parentElement,
		formSubmit=parentNode.querySelector('form[name=edit_coupon]');
	formSubmit.style.display='inline-block';
	o.style.display='none';
}

function show_desc(o){
	let descArea=o.nextElementSibling;
	if(descArea.style.display=='none'){
		descArea.style.display='block';
		o.innerHTML=o.dataset.hide;
	}else{
		descArea.style.display='none';
		o.innerHTML=o.dataset.show;
	}
}

function show_share(id){
	let startHeight=0,
		endHeight=35,
		cHeight=BX(id).style.height;
cHeight=cHeight?parseInt(cHeight):0;
	if(cHeight>0){
		startHeight=35;
		endHeight=0;
	}
	var easing = new BX.easing({
		duration:350,
		start:{height:startHeight},
		finish:{height:endHeight},
		transition:BX.easing.transitions.quart,
		step:function(state){
			//console.log(state.height);
			BX(id).style.height = state.height + "px";
		}
	});
	easing.animate();
}

function sortTable(){
	let index=0;
	let parent=this.parentElement;
	let tmpStr='';
	for(var i = 0; i < parent.children.length; i++) {
		if(parent.children[i] == this){
			index=i;
		}else{
			parent.children[i].classList.remove('up', 'down');
		}
	}
	
	sortDirect='up';
	if(this.classList.contains('down')){
		this.classList.remove('down');
		this.classList.add('up');
	}else if(this.classList.contains('up')){
		this.classList.remove('up');
		this.classList.add('down');
		sortDirect='down';
	}else{
		this.classList.add('up');
	}
	let trArr=[];
	let cRows=parent.parentElement.parentElement.querySelectorAll('tbody tr');
	for(let i=0; i<cRows.length; i++){
		trArr.push(cRows[i]);
	}
	trArr.sort(compareTR);
	function compareTR(a,b){
		let tdA=a.querySelectorAll('td'),
			cTdA=tdA[index],
			tdB=b.querySelectorAll('td'),
			cTdB=tdB[index],
			valA=(cTdA.dataset.sort)?parseInt(cTdA.dataset.sort):cTdA.innerHTML,
			valB=(cTdB.dataset.sort)?parseInt(cTdB.dataset.sort):cTdB.innerHTML;
		if(sortDirect=='up'){
			if (valA < valB) return 1;
			else return -1;
		}else{
			if (valA < valB) return -1;
			else return 1;
		}
	}
	for(let i=0; i<trArr.length; i++){
		tmpStr+='<tr>'+trArr[i].innerHTML+'</tr>';
	}
	parent.parentElement.parentElement.querySelector('tbody').innerHTML=tmpStr;
}

BX.ready(function(){
	let sortTd=document.querySelectorAll('.ref_table thead td');
	for(let i=0; i<sortTd.length; i++){
		sortTd[i].addEventListener('click', sortTable);
	}
	
	let formSubmit=document.querySelectorAll('form[name=edit_coupon]');
	for(let i=0; i<formSubmit.length; i++){
		let currentForm=formSubmit[i];
		
		let cancelButton=currentForm.querySelector('button[type=button]');
		if(cancelButton){
			cancelButton.addEventListener('click', function(){
				let parentNode=this.closest('.couponItem');
				if(parentNode){
					let cancelLink=parentNode.querySelector('.edit_coupon');
					if(cancelLink){
						cancelLink.style.display='inline-block';
						this.closest('form').style.display='none';
					}
				}
			});
		}
		
		currentForm.addEventListener('submit', function(e){
			e.preventDefault();
			let strSerialized = BX.ajax.prepareData(BX.ajax.prepareForm(this).data),
				self=this;;
			BX.ajax({
				url: '/bitrix/components/commerce/loyaltyprogram.room/ajax.php',
				data: strSerialized,
				method: 'POST',
				dataType: 'json',
				timeout:300,
				async: true,
				/* scriptsRunFirst:true, */
				onsuccess: function(data){
					if(data && data.status){
						if(data.status=='error'){
							self.querySelector('.info_error').innerHTML=data.error[0];
						}else{
							let parentItem=self.closest('.couponItem');
							if(parentItem){
								parentItem.querySelector('.perfectPreview').innerHTML=data.coupon;
								self.style.display='none';
								parentItem.querySelector('.edit_coupon').style.display='inline-block';
								self.old_name.value=data.coupon;
							}
						}
					}else{
						self.querySelector('.info_error').innerHTML='inknown error.';
					}
				},
				onfailure: function(data){
					console.log(data);
				}
			});
		});
	}
	
	//partner sites
	let formSite=document.querySelector('form[name=add_site]');
	if(formSite){
		siteManager.init(formSite);
	}
	
})

var siteManager={
	init:function(form){
		this.form=form;
		this.errorBlock=this.form.querySelector('.info_error');
		this.cancelButton=form.querySelector('button[type=button]');
		if(this.cancelButton){
			this.cancelButton.addEventListener('click', function(){
				let parentNode=siteManager.form.parentNode;
				if(parentNode){
					let editLink=parentNode.querySelector('a.add_site');
					if(editLink){
						editLink.style.display='inline-block';
						siteManager.form.style.display='none';
					}
				}
			});
		}
		this.addLink=this.form.parentNode.querySelector('a.add_site');
		this.table=this.form.parentNode.querySelector('table tbody');
		this.setEventTable();
		if(this.addLink){
			this.addLink.addEventListener('click', function(){
				siteManager.form.style.display='block';
				this.style.display='none';
				siteManager.errorBlock.innerHTML='';
			})
		}
		this.form.addEventListener('submit', this.addSite);
	},
	addSite:function(e){
		e.preventDefault();
		let strSerialized = BX.ajax.prepareData(BX.ajax.prepareForm(this).data);
			BX.ajax({
				url: '/bitrix/components/commerce/loyaltyprogram.room/ajax.php',
				data: strSerialized,
				method: 'POST',
				dataType: 'json',
				timeout:300,
				async: true,
				onsuccess: function(data){
					if(data.error){
						siteManager.errorBlock.innerHTML=data.error+'<br>';
					}else{
						siteManager.errorBlock.innerHTML='';
						siteManager.form.style.display='none';
						siteManager.addLink.style.display='inline-block';
						siteManager.updateTable(data.rows);
					}
				},
				onfailure: function(data){
					console.log(data);
				}
			});
	},
	updateTable:function(rows){
		if(this.table){
			let newTRs='';
			for(let nextRow of rows){
				let status=BX.message("commerce_loyaltyprogram_TABLE_SITE_STATUS_Y"),
					actionConfirm='',
					dateConfirm=nextRow['date_confirm'];
				if(nextRow['confirmed']=='N'){
					status=BX.message("commerce_loyaltyprogram_TABLE_SITE_STATUS_N");
					actionConfirm='<span class="fa fa-check" data-code="'+nextRow['code']+'" title="'+BX.message("commerce_loyaltyprogram_ACTION_CONFIRM")+'"></span>';
					dateConfirm='';
				}
				newTRs+='<tr data-id="'+nextRow.id+'"><td>'+nextRow.site+'</td><td>'+status+'</td><td>'+dateConfirm+'</td><td>'+actionConfirm+' <span class="fa fa-remove" title="'+BX.message("commerce_loyaltyprogram_ACTION_DELETE")+'"></span></td></tr>';
			}
			this.table.innerHTML=newTRs;
			this.setEventTable();
		}
	},
	setEventTable:function(){
		let remButtons=this.table.querySelectorAll('.fa-remove');
		if(remButtons.length>0){
			for(let nextRow of remButtons){
				nextRow.addEventListener('click', function(){
					siteManager.actionId=this.closest('tr').dataset.id;
					siteManager.popupDelete = BX.PopupWindowManager.create("popup-delete", null, {
						content: BX.message('commerce_loyaltyprogram_TABLE_SITE_MESS_DELETE'),
						closeIcon: true,
						autoHide: true,
						 buttons:[
							new BX.PopupWindowButton({
								text: BX.message('commerce_loyaltyprogram_ACTION_DELETE'),
								className: "popup-window-button-blue",
								events: {click: function(){
									siteManager.deleteSite();
								}}
							})
						],
						events:{
							onPopupClose: function(){
								this.destroy();
							}
						}
					});
					siteManager.popupDelete.show();
				});
			}
		}
		
		let checkButtons=this.table.querySelectorAll('.fa-check');
		if(checkButtons.length>0){
			for(let nextRow of checkButtons){
				nextRow.addEventListener('click', function(){
					siteManager.actionId=this.closest('tr').dataset.id;
					siteManager.popupCheck = BX.PopupWindowManager.create("popup-delete", null, {
						content: BX.message('commerce_loyaltyprogram_TABLE_SITE_MESS_CHECK_1')+'<br><br><b>'+this.dataset.code+'.txt</b><br><br>'+BX.message('commerce_loyaltyprogram_TABLE_SITE_MESS_CHECK_2'),
						closeIcon: true,
						autoHide: true,
						 buttons:[
							new BX.PopupWindowButton({
								text: BX.message('commerce_loyaltyprogram_ACTION_CONFIRM'),
								className: "popup-window-button-blue",
								events: {click: function(){
									siteManager.checkSite();
								}}
							})
						],
						events:{
							onPopupClose: function(){
								this.destroy();
							}
						}
					});
					siteManager.popupCheck.setContent(BX.message('commerce_loyaltyprogram_TABLE_SITE_MESS_CHECK_1')+'<br><br><b>'+this.dataset.code+'.txt</b><br><br>'+BX.message('commerce_loyaltyprogram_TABLE_SITE_MESS_CHECK_2'));
					siteManager.popupCheck.show();
				});
			}
		}
	},
	checkSite:function(){
		if(siteManager.actionId){
			BX.ajax({
				url: '/bitrix/components/commerce/loyaltyprogram.room/ajax.php',
				data: {'ajax':'Y', 'sessid':siteManager.form.sessid.value, 'checkSite':siteManager.actionId},
				method: 'POST',
				dataType: 'json',
				timeout:300,
				async: true,
				onsuccess: function(data){
					if(data){
						if(data.error){
							siteManager.popupCheck.setContent(siteManager.popupCheck.contentContainer.innerHTML+'<p class="loyalty error">'+data.error+'</p>');
						}else if(data.rows){
							siteManager.updateTable(data.rows);
							siteManager.popupCheck.close();
							siteManager.popupCheck.destroy();
						}
					}
				},
				onfailure: function(data){
					console.log(data);
					siteManager.popupCheck.close();
					siteManager.popupCheck.destroy();
				}
			});
		}
	},
	deleteSite:function(){
		if(siteManager.actionId){
			BX.ajax({
				url: '/bitrix/components/commerce/loyaltyprogram.room/ajax.php',
				data: {'ajax':'Y', 'sessid':siteManager.form.sessid.value, 'deleteSite':siteManager.actionId},
				method: 'POST',
				dataType: 'json',
				timeout:300,
				async: true,
				onsuccess: function(data){
					console.log(data);
					if(data.rows){
						siteManager.updateTable(data.rows);
					}
					siteManager.popupDelete.close();
				},
				onfailure: function(data){
					console.log(data);
					siteManager.popupDelete.close();
					siteManager.popupDelete.destroy();
				}
			});
		}
		//siteManager.popupDelete.close();
	}
}