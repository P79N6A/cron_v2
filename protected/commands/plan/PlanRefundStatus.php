<?php
class PlanRefundStatus {
	
	const CRON_NO = 5014; //任务代码

	public function process() {
		$btime = date("Y-m-d 00:00:00", time()-86400*6);
		$etime = date("Y-m-d 23:59:59", time()-86400*6);
		
		$sql = "select pln_id from lcs_plan_info where status=5 and performance_promise=2 and subscription_price>0 and min_profit>stop_loss and real_end_time>='$btime' and real_end_time<='$etime'";
		$pln_ids = Yii::app()->lcs_r->createCommand($sql)->queryColumn();
		
		$log_info = array();
		if(!empty($pln_ids)) {
			$db_w = Yii::app()->lcs_w;
			//修改订阅计划的退款状态
			$sql_up = "update lcs_plan_subscription set status=3,u_time='".date("Y-m-d H:i:s")."' where status=1 and pln_id in (".implode(',',$pln_ids).")";
			$up_count = $db_w->createCommand($sql_up)->execute();
			if($up_count > 0) {
				$log_info[] = '更新订阅信息数量：'.$up_count .';';
			}
			
		}//end if.
		
		//write log.
		if(!empty($log_info)) {
			Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_INFO, json_encode($log_info));
		}else{
			Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_INFO, '无退款数据');
		}
	}
	
}