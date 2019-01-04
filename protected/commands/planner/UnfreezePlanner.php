<?php
/*
 * 自动解冻冻结到期的理财师
 * 每小时执行一次
 */

class UnfreezePlanner {

    const CRON_NO = 1010; //任务代码

    public function __construct() {
        
    }

    public function unfreeze() {
    	$now = date('Y-m-d H:i:s');
    	$freeze_list = array();
    	$db_r = Yii::app()->lcs_r;
    	$sql = "select p_uid from lcs_planner_freeze where freeze_timelength!=0 and unfreeze_time>'0' and unfreeze_time<='". $now ."' AND `type`=1";
    	$select_cmd = $db_r->createCommand($sql);
    	$freeze_list = $select_cmd->queryColumn();
    	if($freeze_list) {
    		Planner::model()->unfreezePlanner($freeze_list);
    	}
    	
    	Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_INFO, $now .' unfreeze planners:' .count($freeze_list));
    }

 
}