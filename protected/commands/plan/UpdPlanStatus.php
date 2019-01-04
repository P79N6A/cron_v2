<?php

/**
 * 更新计划的一些状态
 */
class  UpdPlanStatus{
	
	const CRON_NO = 5007; //任务代码
	
	private $_plan_last_upt_key = "plan_status_update";
	
	public function __construct() {
	}
	
	/**
	 * 更新待运行计划为运行中
	 * 更新计划的剩余时间
	 *
	 */
	public function updPlanStatus(){
		$db_r = Yii::app()->lcs_r;
		$db_w = Yii::app()->lcs_w;

		//获取上次更新的日期
		$today = date("Y-m-d");
		//更新待运行计划为运行中，跟运行的时间相关，所以必须存取一个上次更新的日期，否则，有故障不运行的时候就会出问题。
		$sql = "update lcs_plan_info set status=3 where status=2 and start_date<='".$today."'";
		$result = $db_w->createCommand($sql)->execute();
		
		$log = "upd plan status success! affect $result records.";
		
		
		
		// 只有运行中的计划才能有 time_left
		$sql = "select pln_id,start_date,end_date from lcs_plan_info where status=3";
		$ret = $db_r->createCommand($sql)->queryAll();

		$sql = "update lcs_plan_info set time_left = CASE pln_id ";
		$idstr = " ";
		if (is_array($ret))
		{
			foreach($ret as $k=>$v)
			{
				$diff_total_days = (strtotime($v['end_date']) - strtotime($v['start_date']))/(60*60*24);
				$diff_now_days = (strtotime($today) - strtotime($v['start_date']))/(60*60*24);
				$left = round(($diff_now_days / $diff_total_days)*100, 2);
				if (0<=$left && $left<40)
				{
					$time_left = 1;//刚开始
				}
				elseif (40<=$left && $left<80)
				{
					$time_left = 2;//进行一半
				}
				else
				{
					$time_left = 3;	//将结束
				}
				$sql .= "WHEN ".$v['pln_id']." THEN ".$time_left." ";
				$idstr .= $v['pln_id'].",";
			}
			$idstr = substr($idstr,0,-1);
			$sql .= " END WHERE pln_id IN (".$idstr.")";
			$ret = $db_w->createCommand($sql)->execute();
			
			$log .= 'update lcs_plan_info.time_left success! affect '.$ret." records.";
			Cron::model()->saveCronLog(self::CRON_NO,'info',$log);
		}
			
	}
}