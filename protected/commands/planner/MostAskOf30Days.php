<?php
/**
 * 定时任务:30天内回答问题最多的理财师 20个
 * User: zwg
 * Date: 2015/10/27
 * Time: 17:33
 */

class MostAskOf30Days {


    const CRON_NO = 1005; //任务代码

    private $redis_key='lcs_planner_rank_7_days_';


    public function __construct(){

    }


    /**
     * 修改未回答的问题的修改时间
     * @throws LcsException
     */
    public function state(){
        try{
            $range = range(0, 8);
            $records = array();
            foreach ($range as $r){
                $redis_key = $this->redis_key."{$r}";
                Yii::app()->redis_w->delete($redis_key);
                $result = Ask::model()->getPlannerRankByAskNum($r);
                Yii::app()->redis_w->set($redis_key, serialize($result));
                $records[$r]=count($result);
            }
            Cron::model()->saveCronLog(MostAskOf30Days::CRON_NO, CLogger::LEVEL_INFO, 'records:'.json_encode($records));
        }catch (Exception $e){
            throw LcsException::errorHandlerOfException($e);
        }
    }
}