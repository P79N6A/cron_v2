<?php
/**
 * 定时任务:最近回答30天内的响应时间
 * User: zwg
 * Date: 2015/10/27
 * Time: 17:33
 */

class QuestionRespTime {


    const CRON_NO = 1006; //任务代码

    private $redis_key='lcs_planner_ask_response_time';


    public function __construct(){

    }


    /**
     * 修改未回答的问题的修改时间
     * @throws LcsException
     */
    public function respTime(){
        try{
            //获取理财师的最近回答时间 开通问答的理财师
            $planners = Ask::model()->getAskInfo('s_uid,is_open,last_answer_time');
            //$planners = array(array('s_uid'=>'1190872560','is_open'=>'1','last_answer_time'=>'2015-09-25 19:25:24'));
            $upd_num=0;
            if(!empty($planners)){
                $p_resp_time = array();
                foreach($planners as $planner){
                    $resp_time = $this->getRespTime($planner['s_uid'],$planner['last_answer_time']);
                    $p_resp_time[$planner['s_uid']]=$resp_time;
                    //更新到redis
                    Yii::app()->redis_w->hset($this->redis_key,$planner['s_uid'],$resp_time);
                }
                //更新到数据库
                $upd_num = Ask::model()->updateAskPlannerOfRespTimeNum($p_resp_time);
            }

            Cron::model()->saveCronLog(QuestionRespTime::CRON_NO, CLogger::LEVEL_INFO, 'planner_num:'.count($planners).'   update records:'.$upd_num);
        }catch (Exception $e){
            throw LcsException::errorHandlerOfException($e);
        }
    }


    private function getRespTime($p_uid,$last_answer_time){
        $last_answer_time = strtotime($last_answer_time);
        if($last_answer_time <= 0) {
            return 0;
        }
        $date = new DateTime(date("Y-m-d H:i:s",$last_answer_time));
        $date->sub(new DateInterval("P30D"));

        $s_time = $date->format("Y-m-d H:i:s");
        $e_time = date("Y-m-d H:i:s",$last_answer_time);
        $answers = Ask::model()->getQuestionRespTime($p_uid,$s_time,$e_time);
        $resp_time=0;
        if(!empty($answers)){
            $differ_time = 0;
            array_walk($answers,function($val) use (&$differ_time){
                $differ_time += strtotime($val['answer_time'])-strtotime($val['question_time']);
            });

            $resp_time = ceil($differ_time/60/count($answers));
        }

        return $resp_time;

    }
}