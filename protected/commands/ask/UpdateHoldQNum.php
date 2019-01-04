<?php
/**
 * 定时任务:修改理财师持有免费问题数量小于零的问题
 * 每小时更新一次
 * User: zwg
 * Date: 2015/9/15
 * Time: 09:33
 */

class UpdateHoldQNum {


    const CRON_NO = 1108; //任务代码


    public function __construct(){

    }


    /**
     * 修改理财师持有免费问题数量小于零的问题
     * @throws LcsException
     */
    public function update(){
        try{
            $records=0;
            $planner_str='';
            $planners = Ask::model()->getAskInfo('s_uid,hold_q_num');
            if(!empty($planners)){
                $update_data=array();
                foreach($planners as $item){
                    $hold_q_num = Ask::model()->getAskPlannerOfHoldQNum($item['s_uid']);
                    if(intval($hold_q_num)!=intval($item['hold_q_num'])){
                        $update_data[$item['s_uid']] = intval($hold_q_num);
                        $planner_str .= $item['s_uid'].':'.$item['hold_q_num'].'->'.$hold_q_num.';';
                    }
                }
                $records = Ask::model()->updateAskPlannerOfHoldQNum($update_data);
                if(!empty($planner_str)){
                    Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_INFO, $planner_str);
                }
            }

            return $records;
        }catch (Exception $e){
            throw LcsException::errorHandlerOfException($e);
        }
    }



}