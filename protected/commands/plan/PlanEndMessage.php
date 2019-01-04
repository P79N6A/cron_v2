<?php
/**
 * 计划结束提醒，在每天9:20 15:10 每分钟查询一次 通知理财师
 * 判断逻辑  status>3 and real_end_time>(time()-60)
 * User: zwg
 * Date: 2016/1/5
 * Time: 15:06
 */

class PlanEndMessage {
    const CRON_NO= 5102;
    public function planEnd(){
        $s_time = strtotime(date('Y-m-d').' 09:20:00');
        $e_time = strtotime(date('Y-m-d').' 15:10:00');
        $cur_time = time();
        if($cur_time<$s_time || $cur_time>$e_time){
           return 0;
        }
        
        try{
            $end_time = time()-60 ;
            $etime = date("Y-m-d H:i:s", $end_time);
            $plan_list = Plan::model()->getPLanEndList($etime);
            if(empty($plan_list)){
                return 0;
            }
            foreach($plan_list as $plan_info){
                $push_data=array("type" => "planChange", "pln_id" => $plan_info['pln_id']);
                Yii::app()->redis_w->rPush("lcs_common_message_queue",json_encode($push_data));
            }
            return count($plan_list);
        }catch (Exception $e){
            throw LcsException::errorHandlerOfException($e);
        }
    }
          
}