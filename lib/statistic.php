<?
namespace Commerce\Loyaltyprogram;

class Statistic{

    /**
     * @param string $type get from tools::getAllTypeLinkList();
     * @param int $refId id user referrer
     * @param int $userId id user referral
     * @return bool true|false add statistic row
     */
    public function setDetailStat($type, $refId, $userId=0){
        $options=Settings::getInstance()->getOptions();
        if(empty($options['ref_detail_stat']) || $options['ref_detail_stat']!='Y'){
            return true;
        }
        global $DB;
        $filter=['ref_user'=>$refId, 'type'=>'"'.$type.'"'];
        if(!empty($userId)){
            $filter['user']=$userId;
        }
        if(!empty($_SERVER['REMOTE_ADDR'])){
            $filter['ip']='"'.$_SERVER['REMOTE_ADDR'].'"';
        }
        if(!empty($_SERVER['HTTP_REFERER'])){
            $filter['url']='"'.$DB->ForSql($_SERVER['HTTP_REFERER']).'"';
        }
        $DB->Insert($table=Settings::getInstance()->getTableStatDetail(), $filter, $err_mess.__LINE__);
        return true;
    }
	
	public static function setFollowingLink($refLink){
		$p=new Profiles\Profile;
		$refId=$p->getUserByRef($refLink);
		if($refId>0){
			global $DB;
			$table=Settings::getInstance()->getTableStatLink();
			$results=$DB->Query('select * from '.$table.' where id='.$refId);
			if($row = $results->Fetch()){
				$DB->Update($table, ['transfer'=>($row['transfer']+1)], "where id='".$refId."'", $err_mess.__LINE__);
			}else{
				$DB->Insert($table, ['transfer'=>1, 'user'=>$refId], $err_mess.__LINE__);
			}
			self::setDetailStat('link', $refId);
			return true;
		}
		return false;
	}

	public static function setFollowingSite($refId){
        if(!empty($refId) && (int) $refId>0){
            global $DB;
            $table=Settings::getInstance()->getTableStatSite();
            $results=$DB->Query('select * from '.$table.' where id='.$refId);
            if($row = $results->Fetch()){
                $DB->Update($table, ['transfer'=>($row['transfer']+1)], "where id='".$refId."'", $err_mess.__LINE__);
            }else{
                $id = $DB->Insert($table, ['transfer'=>1, 'user'=>$refId], $err_mess.__LINE__);
            }
            self::setDetailStat('partnerSite', $refId);
            return true;
        }
        return false;
    }
	
	public static function setRegisterBySite($refId, $userId){
		if(!empty($refId) && (int) $refId>0){
			global $DB;
			$table=Settings::getInstance()->getTableStatSite();
			$results=$DB->Query('select * from '.$table.' where id='.$refId);
			if($row = $results->Fetch()){
				$DB->Update($table, ['reg'=>($row['reg']+1)], "where id='".$refId."'", $err_mess.__LINE__);
			}else{
				$DB->Insert($table, ['reg'=>1, 'user'=>$refId], $err_mess.__LINE__);
			}
            /*if(!empty($userId)) {
                self::setDetailStat('partnerSite', $refId, $userId);
            }*/
			return true;
		}
		return false;
	}

	public static function setRegisterByLink($refId, $userId=0){
		if($refId>0){
			global $DB;
			$table=Settings::getInstance()->getTableStatLink();
			$results=$DB->Query('select * from '.$table.' where id='.$refId);
			if($row = $results->Fetch()){
				$DB->Update($table, ['reg'=>($row['reg']+1)], "where id='".$refId."'", $err_mess.__LINE__);
			}else{
				$DB->Insert($table, ['reg'=>1, 'user'=>$refId], $err_mess.__LINE__);
			}
			/*if(!empty($userId)){
                self::setDetailStat('link', $refId, $userId);
            }*/
			return true;
		}
		return false;
	}
	
	public static function getStatisticByLink($userId){
		$stat=['transfer'=>0, 'registratin'=>0];
		global $DB;
		$results=$DB->Query('select * from '.Settings::getInstance()->getTableStatLink().' where id='.$userId);
		if($row = $results->Fetch()){
			$stat=['transfer'=>$row['transfer'], 'registration'=>$row['reg']];
		}
		return $stat;
	}
	
	public static function getStatisticByCoupons($couponsList){
		\Bitrix\Main\Loader::includeModule('sale');
		$price=['orders'=>0, 'price'=>0];
		$rsOrders = \CSaleOrder::GetList(array(), array('BASKET_DISCOUNT_COUPON' =>$couponsList));
		while($row = $rsOrders->Fetch()){
			$currency=$row['CURRENCY'];
			$price['orders']++;
			$price['price']+=$row['PRICE'];
		}
		$price['price_format']=\CurrencyFormat($price['price'], $currency);
		return $price;
	}
	
	public static function drawStatistics($id){
		global $DB;
		$totalTrigger=0;
		$totalBonus=0;
		$rows=[];
		
		//get type profile
		$result=$DB->Query('select * from commerce_loyal_profiles where id='.$id.';');
		$row = $result->Fetch();
		if($row['type']=='Writeoff'){
			$result=$DB->Query('select
				sum(bonus) as sum,
				count(id) as counttrigger,
				DATE(date_order) as day
				from commerce_loyal_write_off where profile_id='.$id.' and status!="request"
				group by DATE(date_order)
				order by DATE(date_order);');
		}else{
			
			
			$result=$DB->Query('select
				sum(bonus_start) as sum,
				count(id) as counttrigger,
				DATE(date_add) as day
				from commerce_loyal_bonuses where profile_id='.$id.'
				group by DATE(date_add)
				order by DATE(date_add);');
		}
		while($row = $result->Fetch()){
			$row['jsdata']=$row['day'];
			$totalBonus+=$row['sum'];
			$totalTrigger+=$row['counttrigger'];
			if(empty($startDate)){
				$startDate=$DB->FormatDate($row['day'], "YYYY-MM-DD","DD.MM.YYYY");
			}
			$endDate=$DB->FormatDate($row['day'], "YYYY-MM-DD","DD.MM.YYYY");
			$rows[]=$row;
		}
		if($totalTrigger==0){
		?>
		<tr><td><?
		\CAdminMessage::ShowMessage([
			"TYPE"=>"OK",
			"MESSAGE"=>\Bitrix\Main\Localization\Loc::getMessage("commerce.loyaltyprogram_STATISTICS_IS_EMPTY")
		]);
		?></td></tr>
		<?}else{?>
		<tr>
			<td width="40%"><?=\Bitrix\Main\Localization\Loc::getMessage("commerce.loyaltyprogram_STATISTICS_FIRST_DATE")?>:</td>
			<td width="60%"><?=$startDate?></td>
		</tr>
		<tr>
			<td width="40%"><?=\Bitrix\Main\Localization\Loc::getMessage("commerce.loyaltyprogram_STATISTICS_LAST_DATE")?>:</td>
			<td width="60%"><?=$endDate?></td>
		</tr>
		<tr>
			<td width="40%"><?=\Bitrix\Main\Localization\Loc::getMessage("commerce.loyaltyprogram_STATISTICS_BONUS")?>:</td>
			<td width="60%"><?=Tools::numberFormat($totalBonus)?></td>
		</tr>
		<tr>
			<td width="40%"><?=\Bitrix\Main\Localization\Loc::getMessage("commerce.loyaltyprogram_STATISTICS_TRIGGERS")?>:</td>
			<td width="60%"><?=Tools::numberFormat($totalTrigger)?></td>
		</tr>
		<tr>
			<td colspan="2">
			<div id="stat_chart" style="width:100%; height:500px;"></div>
			<script>
				var statData=<?=\Bitrix\Main\Web\Json::encode($rows);?>,
					amChartsData=[];
					drawGraph=false;
					for(var m in statData){
						amChartsData.push({
							date:new Date(statData[m].jsdata),
							counttrigger:parseInt(statData[m].counttrigger),
							bonuses:parseInt(statData[m].sum)
						});
					}
					
				function setGraphics(){
					if(drawGraph==false){
						drawGraph=true;
						chart = AmCharts.makeChart("stat_chart", {
							"type": "serial",
							"theme": "light",
							"legend": {
								"useGraphSettings": true
							},
							"dataProvider": amChartsData,
							"synchronizeGrid":true,
							"valueAxes": [
									{
										"id":"v1",
										"axisColor": "#FF6600",
										"axisThickness": 2,
										"axisAlpha": 1,
										"position": "left"
									}, {
										"id":"v2",
										"axisColor": "#FCD202",
										"axisThickness": 2,
										"axisAlpha": 1,
										"position": "right"
									}
							],
							"graphs":[
									{
										"valueAxis": "v1",
										"lineColor": "#FF6600",
										"bullet": "round",
										"bulletBorderThickness": 1,
										"hideBulletsCount": 30,
										"title": "<?=\Bitrix\Main\Localization\Loc::GetMessage("commerce.loyaltyprogram_STATISTICS_AXIS_TRIGGERS")?>",
										"valueField": "counttrigger",
										"fillAlphas": 0
									}, {
										"valueAxis": "v2",
										"lineColor": "#FCD202",
										"bullet": "square",
										"bulletBorderThickness": 1,
										"hideBulletsCount": 30,
										"title": "<?=\Bitrix\Main\Localization\Loc::GetMessage("commerce.loyaltyprogram_STATISTICS_AXIS_BONUS")?>",
										"valueField": "bonuses",
										"fillAlphas": 0
									}
							],
							"pathToImages":"/bitrix/js/main/amcharts/3.3/images/",
							"chartScrollbar": {
								"dragIcon":"dragIconRectBig.gif"
							},
							"chartCursor": {
								"cursorPosition": "mouse"
							},
							"categoryField": "date",
							"categoryAxis": {
								"parseDates": true,
								"axisColor": "#DADADA",
								"minorGridEnabled": true
							},
							"export": {
								"enabled": true,
								"position": "top-right"
							 }
						});
						
						chart.addListener("dataUpdated", zoomChart);
						zoomChart();
						
						function zoomChart(){
							chart.zoomToIndexes(chart.dataProvider.length - 20, chart.dataProvider.length - 1);
						}
					}
				}
			</script>
			</td>
		</tr>
		<?}?>
	<?}
    
}