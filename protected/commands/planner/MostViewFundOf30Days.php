<?php
/**
 * 定时任务:30天内发布基金类观点最多的理财师 20个
 * User: zwg
 * Date: 2015/10/27
 * Time: 17:33
 */

class MostViewFundOf30Days {


    const CRON_NO = 1040; //任务代码

    private $redis_key='lcs_planner_rank_viewfund_30_days_';


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
            $planners = View::model()->getPlannerRankByViewNum($r);
            $p_uids = array();
            if($planners){
                foreach($planners as $v){
                    $p_uids[] = $v['p_uid'];
                }
            }
            $views = array();
            if($p_uids){
                $views = View::model()->getMostViewNumof30Days($p_uids);
            }
            if($views){

                foreach($views as $vv){
                    $tmp_views[$vv['p_uid']] = $vv;
                }
                foreach($planners as $k=>$v){
                    if(isset($tmp_views[$v['p_uid']])){
                        $planners[$k]['v_id'] = $tmp_views[$v['p_uid']]['v_id'];
                        $planners[$k]['view_num'] = $tmp_views[$v['p_uid']]['view_num'];
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