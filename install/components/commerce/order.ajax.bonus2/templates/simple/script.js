var commerceOrderAjaxBonus={
    init:function(bonus_max, currency, orderPrice, inputName){
        this.blocks={};
        this.blocks.inputName=inputName;
        this.params={};
		
		this.bonuses={};
        this.bonuses.max=bonus_max*1;
        this.bonuses.currency=currency;
        this.bonuses.added=0;
        this.bonuses.added_format='';
        this.bonuses.orderPrice=orderPrice;
        if(this.bonuses.orderPrice<this.bonuses.max){
            this.bonuses.max=this.bonuses.orderPrice;
        }

        this.getParamFromSOA();
		this.getBonusAdded();
    },
    getParamFromSOA:function(){//this function update parameters from OrderAjaxComponent
		this.blocks.componentArea=document.querySelector('#bx-soa-bonus-2');
		this.blocks.AddedArea=document.querySelector('.added_bonus');
		this.blocks.AddedAreaTD=this.blocks.AddedArea.querySelector('td:nth-child(2)');
		this.params.bonusInput=this.blocks.componentArea.querySelector('input[name='+ this.blocks.inputName+']');
		
		
		this.params.bonusInput.addEventListener('change', function(){
			commerceOrderAjaxBonus.changePayBonus();
		})
		
    },
    getBonusAdded:function(){    
        BX.ajax({  
            url: '/bitrix/components/commerce/order.ajax.bonus2/ajax.php',
                data: {type:'bonus_added'},
                method: 'POST',
                dataType: 'json',
                timeout: 30,
                async: true,
                processData: true,
                scriptsRunFirst: true,
                emulateOnload: true,
                start: true,
                cache: false,
                onsuccess: function(data){
                    //console.log(data)
                    if(data.bonus){
						commerceOrderAjaxBonus.blocks.AddedArea.style.display='table-row';
						commerceOrderAjaxBonus.blocks.AddedAreaTD.innerHTML=' ~ '+BX.Currency.currencyFormat(data.bonus, commerceOrderAjaxBonus.bonuses.currency, true);
                    }
                },
                onfailure: function(data){
                    //console.log(data)
                }
        });
    },
    changePayBonus:function(){
        if(this.params.bonusInput.value>this.bonuses.max){
            this.params.bonusInput.value=this.bonuses.max;
        }
    }
}