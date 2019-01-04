<?php
/**
 *
 */
class ViewClick {
	
	const CRON_NO = 1205; //任务代码
	
	private $_redis_key = "lcs_v_c_";
	private $_real_redis_key = "lcs_v_c_real_";
	
	public function __construct(){
	
	}
	
	public function process() {
		$start_time = date('Y-m-d H:i:s', time()-3600*24*3);  //3 days.
		$db_w = Yii::app()->lcs_w;
		$db_r = Yii::app()->lcs_r;
		$redis_r = Yii::app()->redis_r;
		$redis_w = Yii::app()->redis_w;
		
		$view_sql = "select id,p_uid from lcs_view where p_time>='". $start_time ."'";
		$view_result = $db_r->createCommand($view_sql)->queryAll();
		
		$view_key_array = array();
		$view_real_key_array = array();
		if (is_array($view_result)) {
			foreach($view_result as $key => $value) {
				$view_key_array[] = $this->_redis_key.$value["id"];
				$view_real_key_array[] = $this->_real_redis_key.$value["id"];
				$view_id[] = $value['id'];
			}
		}
		$view_num_arr = $redis_r->mget($view_key_array);
		$view_real_num_arr = $redis_r->mget($view_real_key_array);
		if (is_array($view_num_arr)) {
			$i = 0;
			foreach ($view_num_arr as $k => $v) {
				if(0 == (int)$v) {
					continue;
				}
				$upview_sql = "update lcs_view set view_num=".(int)$v.",real_view_num=".(int)$view_real_num_arr[$k].",u_time=NOW()  where id=".$view_id[$k];
				$exec_view = $db_w->createCommand($upview_sql)->execute();
				$i++;
			}
			Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_INFO, date('Y-m-d H:i:s') .' update view:' .$i);
		}
		
		return 0;
	}
}