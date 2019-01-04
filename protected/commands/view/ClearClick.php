<?php
class ClearClick {
	const CRON_NO = 1204; //任务代码
	
	public function __construct(){
	
	}
	
	public function process($b_date=4, $check=0) {
		$db_w = Yii::app()->lcs_w;
		if($b_date<2){
			$b_date = 2;
		}
		$bak_date = (int)date('YmdH',mktime(23,0,0,date('m'),date('d')-$b_date,date('Y')));
		
		$sql_check = 'SELECT * FROM lcs_top_click_24hr WHERE date_ymdg<=:bak_date limit 0, 1;';
		
		$cmd = $db_w->createCommand($sql_check);
		$cmd->bindParam(':bak_date',$bak_date,PDO::PARAM_INT);
		$check_result = $cmd->queryRow();
		//未查询到数据，不做处理
		if(empty($check_result)){
			Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_INFO, 'clear 24hr click date['.$bak_date.'] :no data.');
			return;
		}
		$transaction=$db_w->beginTransaction();
		try {
			//清除临时表
			$sql_del_tmp = 'DELETE FROM lcs_tmp_record_24hr WHERE date_ymdg<=:bak_date;';
			$cmd = $db_w->createCommand($sql_del_tmp);
			$cmd->bindParam(':bak_date',$bak_date,PDO::PARAM_INT);
			$_result_tmp = $cmd->execute();
			//备份到历史表,lixiang23 停止向该表添加数据 20161230
			#$sql_bak_his = 'INSERT INTO lcs_top_click_24hr_his (SELECT * FROM lcs_top_click_24hr WHERE date_ymdg<=:bak_date);';
			#$cmd = $db_w->createCommand($sql_bak_his);
			#$cmd->bindParam(':bak_date',$bak_date,PDO::PARAM_INT);
            #$_result_his = $cmd->execute();
            $_result_his = "not execute";
			//删除已经同步的数据
			$sql_del_click  = 'DELETE FROM lcs_top_click_24hr WHERE date_ymdg<=:bak_date;';
			$cmd = $db_w->createCommand($sql_del_click);
			$cmd->bindParam(':bak_date',$bak_date,PDO::PARAM_INT);
			$_result_click = $cmd->execute();
		
			Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_INFO, 'clear 24hr click date['.$bak_date.'] :del tmp->'.$_result_tmp.' back his->'.$_result_his.' del_click->'.$_result_click);
			if($check){
				throw new Exception('检查，终止提交', -1);
			}
			
			$transaction->commit(); //提交
		}catch (Exception $e) {
			// 如果有一条查询失败，则会抛出异常
			$transaction->rollBack();
            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_ERROR, $e->getMessage());
		}
	}
}
