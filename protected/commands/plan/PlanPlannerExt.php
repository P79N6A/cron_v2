<?php

/**
 * 计划的交易水平透视数据
 */
class  PlanPlannerExt{
	
	const CRON_NO = 5005; //任务代码
	
	public function __construct() {
	}
	public function PlanPlannerExt(){
		
		try{
			$sql = "select distinct(p_uid) from lcs_plan_info where status in (4,5,6,7)";
			$res = Yii::app()->lcs_r->createCommand($sql)->queryColumn();
			if(!empty($res)){
				foreach ($res as $p_uid)
				{	
					$sql = "select pln_id,p_uid,curr_ror,start_date,end_date,comment_count,real_end_time,status,available_value,init_value,market_value from lcs_plan_info where status in (4,5,6,7) and p_uid=$p_uid";
					$plns = Yii::app()->lcs_r->createCommand($sql)->queryAll();
					if(empty($plns))continue;
					$pln_num = 0;//计划数
					$succ_nums = 0;//成功数
					$loss_nums = 0;//止损数
					$buy_num = 0;//建仓数
					$sell_num = 0;//平仓数
					$pln_u_comment_num = 0;//大家说数
					$pln_profit_num = 0; //收益大于0的计划
					$pln_total_profit = 0; //历史累计收益率
					$pln_ror_arr = array(); //最佳收益率
					$pln_id = array();
					$pln_rors = 0;
					$start_date = $end_date = '0000-00-00 00:00:00';
					$all_init_value = 0;
					$all_available_value = 0;
					foreach ($plns as $pln){
						$sql = "select buy_num,profit_num+loss_num as sell_num from lcs_plan_assess where pln_id=$pln[pln_id]";
						$assess_info = Yii::app()->lcs_r->createCommand($sql)->queryRow();
						if(!empty($assess_info)){
							$buy_num += $assess_info['buy_num'];
							$sell_num += $assess_info['sell_num'];
						}
						$pln_rors += $pln['curr_ror'];
						$pln_id[] = $pln['pln_id'];
						$pln_u_comment_num +=$pln['comment_count'];
						$pln_num++;
						if($pln['status'] == 4){
							$succ_nums++;
						}elseif ($pln['status'] == 5 && ($pln['real_end_time'] > $pln['end_date'].'16:00:00')){
							$loss_nums++;
						}elseif ($pln['status'] == 7){
							$pln['real_end_time'] = $pln['end_date'].'16:00:00';
						}
						if($start_date > $pln['start_date'] || $start_date=='0000-00-00 00:00:00'){
							$start_date = $pln['start_date'];
						}
						if($end_date < $pln['real_end_time']){
							$end_date = $pln['real_end_time'];
						}
						$pln_ror_arr[] = $pln['curr_ror'];
						if($pln['curr_ror'] > 0){
							$pln_profit_num += 1;
						}			
						$all_available_value += (1+$pln['curr_ror']) * $pln['init_value'];
						$all_init_value += $pln['init_value'];
					}
					$pln_total_profit = sprintf("%.4f", ($all_available_value-$all_init_value)/$all_init_value);					
					$days = ceil((strtotime($end_date)-strtotime("$start_date 00:00:00"))/86400);
	
					//年华收益率
					if($days>0){
						$pln_year_rate = sprintf("%.4f", $pln_rors*365/$days);
					}else{
						$pln_year_rate = 0;
					}
					//成功率
					$pln_success_rate = @($succ_nums/$pln_num);
					//大家说数量
					$sql = "select count(pln_id) from lcs_plan_comment where pln_id in(".implode(',',$pln_id).") and u_type=2";
					$pln_p_comment_num = Yii::app()->lcs_r->createCommand($sql)->queryScalar();
				
					$sql = "select id from lcs_planner_ext where s_uid=$p_uid";
					$row = Yii::app()->lcs_r->createCommand($sql)->queryRow();
					$now_data = date('Y-m-d H:i:s');
					$pln_max_ror = max($pln_ror_arr);
					if(empty($row)){
						$sql = "insert into lcs_planner_ext set pln_profit_num=$pln_profit_num,pln_total_profit=$pln_total_profit,pln_max_ror=$pln_max_ror,pln_loss_num=$loss_nums,pln_buy_num=$buy_num,pln_sell_num=$sell_num,s_uid=$p_uid,pln_num=$pln_num,pln_success_rate=$pln_success_rate,pln_year_rate=$pln_year_rate,pln_u_comment_num=$pln_u_comment_num,pln_p_comment_num=$pln_p_comment_num,u_time='$now_data',c_time='$now_data'";
					}else{
						$sql = "update lcs_planner_ext set pln_profit_num=$pln_profit_num,pln_total_profit=$pln_total_profit,pln_max_ror=$pln_max_ror,pln_loss_num=$loss_nums,pln_buy_num=$buy_num,pln_sell_num=$sell_num,pln_num=$pln_num,pln_success_rate=$pln_success_rate,pln_year_rate=$pln_year_rate,pln_u_comment_num=$pln_u_comment_num,pln_p_comment_num=$pln_p_comment_num,u_time='$now_data' where s_uid=$p_uid";
					}					
					Yii::app()->lcs_w->createCommand($sql)->execute();
				}
			}
			
		}catch (Exception $e){
			throw LcsException::errorHandlerOfException($e);
		}
	}
	
}