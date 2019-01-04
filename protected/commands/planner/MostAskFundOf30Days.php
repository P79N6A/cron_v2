<?php
/**
 * 定时任务:30天内发布基金类观点最多的理财师 20个
 * User: zwg
 * Date: 2015/10/27
 * Time: 17:33
 */

class MostAskFundOf30Days {


    const CRON_NO = 1041; //任务代码

    private $redis_key='lcs_planner_rank_askfund_30_days_';


    public function __construct(){

    }


    /**
     * 修改未回答的问题的修改时间
     * @throws LcsException
     */
    public function state(){
        try{
//            $range = range(0, 8);
//            $records = array();
//            foreach ($range as $r){
            $r=2;
            $redis_key = $this->redis_key."{$r}";
            //Yii::app()->redis_w->delete($redis_key);
            $planners = Ask::model()->getPlannerRankByAskNum($r);
            $p_uids = array();
            if($planners){
                foreach($planners as $v){
                    $p_uids[] = $v['p_uid'];
                }
            }
            $asks = array();
            if($p_uids){
                $asks = Ask::model()->getMostLockQuestionof30Days($p_uids);
            }
            if($asks){
                foreach($asks as $vv){
                    $tmp_asks[$vv['p_uid']] = $vv;
                }
                foreach($planners as $k=>$v){
                    if(isset($tmp_asks[$v['p_uid']])){
                        $planners[$k]['answer_id'] = $tmp_asks[$v['p_uid']]['answer_id'];
                        $planners[$k]['q_id'] = $tmp_asks[$v['p_uid']]['q_id'];
                        $planners[$k]['unlock_num'] = $tmp_asks[$v['p_uid']]['unlock_num'];
                    }
                }
            }
            $result = $planners;
            Yii::app()->redis_w->set($redis_key, serialize($result));
            $records[$r]=count($result);
//            }
            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_INFO, 'records:'.json_encode($records));
        }catch (Exception $e){
            throw LcsException::errorHandlerOfException($e);
        }
    }
}