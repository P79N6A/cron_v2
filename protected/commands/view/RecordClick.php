<?php

class RecordClick {
	
	const CRON_NO = 1202; //任务代码
	private $_redis_view_key = "lcs_p_pageview";
	
	public function __construct(){
	
	}
	
	/**
	 * 从redis中取数据入到临时的数据库
	 * @param number $num
	 */
	public function process($num = 2000) {
		try {
			$db_w = Yii::app()->lcs_w;
			$redis_r = Yii::app()->redis_r;
			$redis_w = Yii::app()->redis_w;
			
			//获取某区间的list值
			$pageview_list = $redis_r->getRange($this->_redis_view_key, 0, $num);
			
			if (is_array($pageview_list) and sizeof($pageview_list) > 0) {
				//组织SQL，批量更新
				$sign = false;
				$rec_sql = "INSERT INTO lcs_tmp_record_24hr (`v_id`, `date_ymdg`, `pkg_id`, `ind_id`, `click_num`) VALUES   ";
				foreach($pageview_list as $key => $value) {
					$v = unserialize($value);
					$v_id = $v['v_id'];
					$date_ymdg = date("YmdH", strtotime($v['datetime']));
					$pkg_id = $v['pkg_id'];
					$ind_id = $v['ind_id'];
					$per_num = $v['num']; //作弊数
					if (empty($v_id) || empty($date_ymdg) || empty($pkg_id) || empty($ind_id) || empty($per_num)) {
						continue;
					}
					$rec_sql .= "('".$v_id."','".$date_ymdg."','".$pkg_id."', '".$ind_id."','".$per_num."'),";
					$sign = true;
				}//end foreach.
				if ($sign) {
					$rec_sql = substr($rec_sql, 0, -1);
					$rec_sql .=  " ON DUPLICATE KEY UPDATE `click_num`=`click_num`+".$per_num;
					$r_cmd = $db_w->createCommand($rec_sql);
					$r_count = $r_cmd->execute();
					//执行成功
					if (is_int($r_count) and $r_count>0) {
						//delete old list;
						$redis_trim = $redis_w->trimlist($this->_redis_view_key, count($pageview_list),-1);
					}
				}
			}
			
		}catch (Exception $e) {
        	throw LcsException::errorHandlerOfException($e);
        }
	}
}