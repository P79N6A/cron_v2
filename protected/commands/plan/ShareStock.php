<?php

/**
 * 股票的分红送股操作
 */
class  ShareStock{
	const CRON_NO = 5003; //任务代码
	
	public function __construct() {
	}

	public function ShareStock(){
		try{
			$date = date('y-m-d 00:00:00');
			//px税前红利 shpx 税后 sg 10送 zz 10增 cqcxr除权除息日
			$sql  = "select symbol,DISHTY3 as px,DISHTY4 as shpx,DISHTY7 as sg ,DISHTY8 as zz, DISHTY13 as cqcxr,DISHTY2 as type
					FROM DISHTY WHERE DISHTY13='$date' AND DISHTY2 IN ('111','151','181','211','251','281','311','351','381','411','451','481', '511','581','551') order by DISHTY2 asc";
			$res = Yii::app()->fcdb_r->createCommand($sql)->queryAll();
			if( is_array($res) && sizeof($res) > 0 ){
				$fgpg_array =array();
				//把分红送股的多条信息组合起来
				foreach ($res as $val){
					$symbol = $val['symbol'];
					if(isset($fgpg_array["$symbol"])){
						if(!empty($val['px'])){
							$fgpg_array["$symbol"]['px'] = $val['px']; 
							$fgpg_array["$symbol"]['shpx'] = $val['shpx']; 
						}elseif (!empty($val['sg'])){
							$fgpg_array["$symbol"]['sg'] = $val['sg']; 
						}elseif (!empty($val['zz'])){
							$fgpg_array["$symbol"]['zz'] = $val['zz']; 
						}
					}else{
						$fgpg_array["$symbol"] = $val;
					}
				}
				
				$upd_res = '';
				foreach ($fgpg_array as $val){
					$code = $val['symbol'];
					$sql = "select pln_id,symbol,amount,avg_cost,buy_avg_cost,hold_avg_cost from lcs_plan_asset where symbol in('sz$code','sh$code') and amount>0";

					$assent = Yii::app()->lcs_r->createCommand($sql)->queryAll($sql);
					if( is_array($assent) && sizeof($assent) > 0 ){
						foreach ($assent as $ass_val)
						{
							$add_amount = 0;
							$avg_cost = $ass_val['avg_cost'];
							$buy_avg_cost = $ass_val['buy_avg_cost'];
							$hold_avg_cost = $ass_val['hold_avg_cost'];
							if(!empty($val['px'])){
								$avg_cost = $ass_val['avg_cost']-($val['px']/10);
								$buy_avg_cost = $ass_val['buy_avg_cost']-($val['px']/10);
								$hold_avg_cost = $ass_val['hold_avg_cost']-($val['px']/10);
								//增加资金
								$add_money = intval(($ass_val['amount']/10)*$val['shpx']);
								$sub_money = intval(($ass_val['amount']/10)*($val['px']-$val['shpx']));
								$sql = "update lcs_plan_info set available_value=available_value+$add_money,market_value=market_value-$sub_money where pln_id=$ass_val[pln_id]";
								$result = Yii::app()->lcs_w->createCommand($sql)->execute();
								$upd_res .= '-'.$ass_val['pln_id'].'-'.$add_money.'-'.$result;
							}
							if(!empty($val['sg']) || !empty($val['zz'])){
								$sg_zz = floatval($val['sg'])+floatval($val['zz']);
								
								$add_amount = intval(($ass_val['amount']/10)*$sg_zz);
							
								$avg_cost = $avg_cost/(1+$sg_zz/10);
								$buy_avg_cost = $buy_avg_cost/(1+$sg_zz/10);
								$hold_avg_cost = $hold_avg_cost/(1+$sg_zz/10);
							}
							$sql = "update lcs_plan_asset set amount=amount+$add_amount,available_sell_amount=available_sell_amount+$add_amount,avg_cost=$avg_cost,buy_avg_cost=$buy_avg_cost,hold_avg_cost=$hold_avg_cost where pln_id=$ass_val[pln_id] and symbol='$ass_val[symbol]'";
							$result = Yii::app()->lcs_w->createCommand($sql)->execute();
							$upd_res .= '-'.$ass_val['pln_id'].'-'.$add_amount.'-'.$ass_val['symbol'].'-'.$buy_avg_cost.'-'.$result;
						}
					}
				}
				//记录日志
				Cron::model()->saveCronLog(self::CRON_NO,'info',$upd_res);
			}
		
		}catch (Exception $e){
			throw LcsException::errorHandlerOfException($e);
		}
	}
}