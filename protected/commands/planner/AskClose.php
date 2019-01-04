<?php

/**
 * 定时任务:处理不活跃的特惠理财师，理财师5个交易日没有上线，则关掉该理财师的问答和限时特惠。
 * User: huang
 * Date: 2015/11/04
 * Time: 17:33
 */
class AskClose{
    
    const CRON_NO = 1007; //任务代码
    const DAYS = 5;       //最小登录交易日天数

    public function __construct() {
        ;
    }

    public function updateAskPlanner(){
        try{
            $askplannerlist = Planner::model()->getAskOpenPlanner();            
            if(!empty($askplannerlist)){
                $upd_num = 0;
                $p_uids = array();
                $foo_suid = array();
                foreach ($askplannerlist as $planner){
                    $p_uids[] = $planner['s_uid'];
                }
                $plannerlist = Planner::model()->getPlannerById($p_uids);
                foreach ($plannerlist as $s_uid=>$planner){
                    $u_time = date('Y-m-d',strtotime($planner['u_time']));
                    $day = Common::model()->getMarketDays($u_time);
                    if($day > self::DAYS){
                        $foo_suid[] = $s_uid;
                    }
                }                
                if(sizeof($foo_suid) > 0){
                    $upd_num = Planner::model()->closeAskPlanner($foo_suid,array(
                        'is_open'=>0,
                        'is_discount'=>0
                    ));
                }
                Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_INFO, 'planner_ids:'.  implode(',', $foo_suid).'   update records:'.$upd_num);
            }            
        }catch (Exception $e){
            throw LcsException::errorHandlerOfException($e);
        }
    }
}