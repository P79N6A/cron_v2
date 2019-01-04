<?php

class SumClick {
	const CRON_NO = 1203; //任务代码
	
	public function __construct(){
	
	}
	
	/**
	 * sum click number from the a temp table
	 * @param string dt "now" 表示计算以当前时间为准的24小时热门，若制定日期时刻，则表示统计到那个时刻为止的最近24小时热门
	 */
	public function process($dt = 'now') {
		try {
			$db_r = Yii::app()->lcs_r;
			$db_w = Yii::app()->lcs_w;
			
			if ($dt == "now") {
				$day_now =  date("YmdH");
				$from_time = time() - 24*3600;
			}else{
				$day_now = $dt;
				$spc_dt = substr($dt,0,4)."-".substr($dt,4,2)."-".substr($dt,6,2)." ".substr($dt,-2).":00:00";
				$from_time = strtotime($spc_dt) - 24*3600;
			}
			
			$day_from = date("YmdH",$from_time);
			
			$sql = "SELECT v_id, pkg_id, ind_id, sum(click_num) as total_click
                    FROM lcs_tmp_record_24hr
                    WHERE date_ymdg<=".$day_now." and date_ymdg>=".$day_from."
                    GROUP BY v_id";
			$s_cmd = $db_r->createCommand($sql);
			$record_result = $s_cmd->queryAll();
			$total = count($record_result);
			if (is_array($record_result) && $total > 0) {
				$limit = 50;
				$pages = ceil($total/$limit);
				
				for ($i = 0; $i< $pages; $i++) {
				
					$data_i = array_slice($record_result, $i*$limit, $limit);
					if(empty($data_i)) {
						break;
					}
					$sql_u = "INSERT INTO lcs_top_click_24hr (`v_id`,`date_ymdg`,`pkg_id`,`ind_id`,`click_num`) VALUES ";
					foreach($data_i as $k => $v) {
						$sql_u .= "('".$v['v_id']."','".$day_now."','".$v['pkg_id']."','".$v['ind_id']."','".$v['total_click']."'),";
					}
					$sql_u = substr($sql_u, 0, -1);
					$sql_u .= " ON DUPLICATE KEY UPDATE `click_num`=VALUES(`click_num`)";
					//echo $sql_u."\n";
					$r_cmd = $db_w->createCommand($sql_u);
					$r_count = $r_cmd->execute();
				}
                Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_INFO, '更新数据：'.$total);
			}
		}catch (Exception $e) {
        	throw LcsException::errorHandlerOfException($e);
        }
	
	}
	
}
