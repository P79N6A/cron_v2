<?php
/**
 * 计划开始通知提醒  每天9点 查询当天开始运行的计划和5天后将要到期的计划 给理财师发送通知
 * 判断逻辑   status=3 and start_date=date('Y-m-d')    status=3 and end_date=date("Y-m-d", strtotime('+5 day'))
 * User: zwg
 * Date: 2016/1/5
 * Time: 15:06
 */

class PlanStartMessage {
    const CRON_NO= 5101;

    public function planStart(){
        $result = array();
        try{
            $start_time = time() ;
            $start_date = date("Y-m-d", $start_time);
            $plan_list = Plan::model()->getPLanStartList($start_date);
            if(!empty($plan_list)){
                foreach($plan_list as $plan_info) {
                    $push_data=array("type" => "planChange", "pln_id" => $plan_info['pln_id']);
                    Yii::app()->redis_w->rPush("lcs_common_message_queue", json_encode($push_data));
                }
            }

            $result['start'] = count($plan_list);


            $e_date = date("Y-m-d", strtotime('+5 day'));
            $sql = "select pln_id,p_uid,name,number from lcs_plan_info where status=3 and end_date='$e_date'";
            $plan_list = Yii::app()->lcs_r->createCommand($sql)->queryAll();
            if(!empty($plan_list)){
                foreach($plan_list as $plan_info){
                    $push_data=array("type" => "planChange", "pln_id" => $plan_info['pln_id'],'expire'=>1);
                    Yii::app()->redis_w->rPush("lcs_common_message_queue",json_encode($push_data));
                }
            }

            $result['about_expire'] = count($plan_list);
        }catch (Exception $e){
            throw LcsException::errorHandlerOfException($e);
        }

        return $result;
    }
}