<?php

/**
 * 更新计划rundays
 */
class  UpdPlanRunDays{
	
	const CRON_NO = 20181025; //任务代码
	
	public function __construct() {
	}
	
	/**
	 * 更新计划的rundays
	 *
	 */
	public function updPlanRunDays(){
        if(date("H")>=10){
            echo "超过运行时间，9点半前执行";
            return;
        }
		$plans = Yii::app()->lcs_r->createCommand("select pln_id,name,number,p_uid,start_date,real_end_time,status from lcs_plan_info where status>=2")->queryAll();
        $counter = 0;

        foreach($plans as $plan){
            $data = array(
                'run_days' => 0
            );

            //运行天数
            $stat = Yii::app()->lcs_r->createCommand("select pln_id,profit_num,loss_num,max_profit_num,max_loss_num,max_profit,max_loss,avg_profit,avg_loss,total_trans_value,buy_num,hold_days,hold_total_weight,max_weight,min_weight,sell_total_profit,total_cost,u_time,c_time from lcs_plan_assess where pln_id=".$plan['pln_id'])->queryRow();
            if(!empty($stat)){
                if(in_array($plan['status'],array(4,5))){
                    $data['run_days'] = ceil((strtotime($plan['real_end_time'])-strtotime($plan['start_date']))/86400);
                }else{
                    $data['run_days'] = ceil((time()-strtotime($plan['start_date']))/86400) - 1;
                }
            }            
            $counter += Yii::app()->lcs_w->createCommand()->update("lcs_plan_info",$data,"pln_id=".$plan['pln_id']);
        }
       Cron::model()->saveCronLog(self::CRON_NO,'info','跟新数计划字段数:'.$counter);
			
	}
}
