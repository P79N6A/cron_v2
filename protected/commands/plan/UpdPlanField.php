<?php

/**
 * 更新计划的一些状态
 */
class  UpdPlanField{
	
	const CRON_NO = 5011; //任务代码
	
	public function __construct() {
	}
	
	/**
	 * 更新计划的资产和收益等
	 *
	 */
	public function updPlanField(){
		$plans = Yii::app()->lcs_r->createCommand("select pln_id,name,number,p_uid,start_date,real_end_time,status from lcs_plan_info where status>=2")->queryAll();
        $counter = 0;

        foreach($plans as $plan){
            $data = array(
                'prev_status' => 0,
                'prev_ror' => 0,
                'history_success_ratio' => 0,
                'history_year_ror' => 0,
                'trans_winning' => 0,
                'profit_loss_ratio' => 0,
                'total_weight' => 0,
                'new_pln_id' => 0
            );

            //上期计划状态 收益
            if($plan['number']>1){
                $prev_plan = Yii::app()->lcs_r->createCommand("select status,curr_ror from lcs_plan_info where status>2 and name='".$plan['name']."' and number=".($plan['number']-1))->queryRow();

                if(!empty($prev_plan)){
                    $data['prev_status'] = intval($prev_plan['status']);
                    $data['prev_ror'] = floatval($prev_plan['curr_ror']);
                }
            }

            //历史成功率，历史年化收益
            $history = Yii::app()->lcs_r->createCommand("select s_uid,pln_success_rate,pln_year_rate,pln_total_profit from lcs_planner_ext where s_uid=".$plan['p_uid'])->queryRow();
            if(!empty($history)){
                $data['history_success_ratio'] = $history['pln_success_rate'];
                $data['history_year_ror'] = $history['pln_year_rate'];
                $data['pln_total_profit'] = $history['pln_total_profit'];
            }

            //运行天数，交易胜率，盈亏比，累计使用仓位
            $stat = Yii::app()->lcs_r->createCommand("select pln_id,profit_num,loss_num,max_profit_num,max_loss_num,max_profit,max_loss,avg_profit,avg_loss,total_trans_value,buy_num,hold_days,hold_total_weight,max_weight,min_weight,sell_total_profit,total_cost,u_time,c_time from lcs_plan_assess where pln_id=".$plan['pln_id'])->queryRow();
            if(!empty($stat)){
                $data['trans_winning'] = $stat['profit_num']+$stat['loss_num'] > 0 ? ($stat['profit_num']/($stat['profit_num']+$stat['loss_num'])) : 0;
                $data['profit_loss_ratio'] =  $stat['avg_loss']!=0 ? abs($stat['avg_profit']/$stat['avg_loss']) : 0;
                $data['total_weight'] = $stat['hold_total_weight'];
            }            
            //新计划id
            $new_plan_id = Yii::app()->lcs_r->createCommand("select max(pln_id) from lcs_plan_info where status in (2,3) and p_uid=".$plan['p_uid']." and pln_id>".$plan['pln_id'])->queryScalar();
            $data['new_pln_id'] = $new_plan_id;

            $counter += Yii::app()->lcs_w->createCommand()->update("lcs_plan_info",$data,"pln_id=".$plan['pln_id']);
        }
       Cron::model()->saveCronLog(self::CRON_NO,'info','跟新数计划字段数:'.$counter);
			
	}
}
