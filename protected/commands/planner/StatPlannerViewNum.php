<?php
/*
 * 统计理财师发的观点数
 * 半小时执行一次
 */

class StatPlannerViewNum {

    const CRON_NO = 1011; //任务代码

    public function __construct() {
        
    }

    /**
     * 更新lcs_planner.view_num
     */
    public function update() {
        //拿到所有发过观点的理财师
    	$sql = "select distinct(p_uid)  from lcs_view ";
    	$p_uids = Yii::app()->lcs_r->createCommand($sql)->queryColumn();

        $counter = 0;
        if(!empty($p_uids)){
            $g_uids = array_chunk($p_uids, 50);  //每组 50个
            foreach($g_uids as $uids){
                $sql_count = "select p_uid,count(p_uid) as `count` from lcs_view where p_uid in (".implode(",",$uids).") and status=0 group by p_uid ";
                $v_count = Yii::app()->lcs_r->createCommand($sql_count)->queryAll();

                $sql = "";
                if(!empty($v_count)) {
                    foreach($v_count as $v_c){
                        $sql .= "update lcs_planner set view_num=".$v_c['count']." where s_uid=".$v_c['p_uid'].";";
                    }
                }

                if(!empty($sql)) {
                    $counter += Yii::app()->lcs_w->createCommand($sql)->execute();
                }
            }
        }
    	
    	Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_INFO, date('Y-m-d H:i:s') .' stat planners:' .$counter);
    }

 
}
