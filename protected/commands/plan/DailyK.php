<?php
/**
 * 更新沪深300指数增长率
 */
class DailyK {
	
	const CRON_NO = 5006; //任务代码
	
	public function __construct() {  }

	public function update(){
		try{
			$db_r = Yii::app()->lcs_r;
			
			$today = date('Y-m-d');
			
			//若执行脚本的当天不是交易日，则exit
			$sql_trade = "select cal_id from lcs_calendar where cal_date='". $today ."'";
			$trade_day = $db_r->createCommand($sql_trade)->queryAll();
			if(!$trade_day) {
				echo 'It is not a trade day.'."\n";
				exit();
			}
			$run_time = strtotime($today.' 09:30:00');
			if(time() <= $run_time) {
				exit();
			}
			
			$plan_list = $this->getPlanList();
			if ($plan_list) {
				$date_list = array();
				foreach ($plan_list as $plan_info) {
					$date_list[] = $plan_info['start_date'];
				}
				$date_list = array_unique($date_list);
				sort($date_list);
				$start_date = current($date_list);  // 取最小的日期
				
				$daily_k_sql = "select symbol,day,open,high,low,close,volume,amount from lcs_daily_k where symbol='sh000300' and "
						     . " day>='". $start_date ."' and day<='". $today ."' ";
				$cmd = $db_r->createCommand($daily_k_sql);
				$day_list = $cmd->queryAll();
				$daily_list = array();
				foreach($day_list as $daily_row){
					$daily_list[$daily_row['day']] = $daily_row; //交易日列表
				}
				//if data of taday is null, exit.
				if(!isset($daily_list[$today]) || $daily_list[$today]['close']=='') {
					//exit();
					$daily_list[$today] = $this->getNewStock('sh000300');
					if(empty($daily_list[$today]) || !isset($daily_list[$today]['close']) || $daily_list[$today]['close']=='') {
						exit();  //今天不是交易日，则退出
					}
				}
				
				$this->processData($plan_list, $daily_list); //处理数据
				
			}//end if.
		    
		}catch (Exception $e){
			throw LcsException::errorHandlerOfException($e);
		}
	}
	/**
	 * 取正在运行或者今天结束的计划列表。
	 * @return array
	 */
	private function getPlanList() {
		$today = date('Y-m-d');
		$sql = "select pln_id,start_date,end_date,date_format(real_end_time, '%Y-%m-%d') as real_end_date,status "
				. " from lcs_plan_info where status>=3 and end_date!=0"
				. " and (real_end_time=0 or date_format(real_end_time, '%Y-%m-%d')='". $today ."') ";
		$plan_list = Yii::app()->lcs_r->createCommand($sql)->queryAll();
		return $plan_list;
	}
	
	private function getNewStock($stock = 'sh000300') {
		$result = array();
		try {
			$hs300 = Yii::app()->curl->get("http://hq.sinajs.cn/rn=".time()."&list=s_". $stock);
			$sz = explode('=', $hs300);
			if(count($sz) >= 2) {
				$rsArr = explode(',', $sz[1]);
				if($rsArr[1] > 0) {
					$result = array(
						'close' => $rsArr[1],
					);
				}
			}
		}catch (Exception $e) {
			Cron::model()->saveCronLog(DailyK::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
		}
		return $result;
	}
	
	/**
	 * 处理数据
	 * @param array $plan_list 计划数据
	 * @param array $daily_list 沪深300数据
	 * @return boolean
	 */
	private function processData($plan_list, $daily_list) {
        $today = date('Y-m-d');
		$db_r = Yii::app()->lcs_r;
		$db_w = Yii::app()->lcs_w;
		$i = 0;  //更新的计划数
		$log_list = array();
		foreach ($plan_list as $plan_info) {
			try {
				//计划开始后的第一个开盘日
				$sql_trade = "select cal_date from lcs_calendar where cal_date>='". $plan_info['start_date']."' order by cal_date asc limit 1";
				$start_date = $db_r->createCommand($sql_trade)->queryScalar();
				if(isset($daily_list[$start_date]) && isset($daily_list[$start_date]['open'])) {
					$s_index = $daily_list[$start_date]['open'];
					$e_index = $daily_list[$today]['close'];
				}else{
					//若开始日期没有数据，则取第二天的数据。
					$start_date = date('Y-m-d', strtotime($start_date)+3600*24); //
					if(!isset($daily_list[$start_date]) && !isset($daily_list[$start_date]['open'])) {
						continue;  //若仍然无数据，则跳过，处理下一个计划
					}
					$s_index = $daily_list[$start_date]['open'];
					$e_index = $daily_list[$today]['close'];
				}
				$percent = round(($e_index-$s_index)/$s_index, 4);  //计算增长率,保留4位小数，有符号
				
				$data = array( 'hs300' => $percent);
				$rs_update = $db_w->createCommand()->update('lcs_plan_info', $data, 'pln_id=:id', array(':id'=>$plan_info['pln_id']));
				if($rs_update > 0) {
				    $log_list[$plan_info['pln_id']] = $data;
				    $i++;
				}
			}catch (Exception $e) {
                Cron::model()->saveCronLog(DailyK::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
            }
            
		}//end foreach.
		
		Cron::model()->saveCronLog(DailyK::CRON_NO, CLogger::LEVEL_INFO, '更新计划数：'.$i .';data:'.json_encode($log_list));
		
		return true;
	}
}
