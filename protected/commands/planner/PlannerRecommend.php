<?php

/**
 * Description of PlannerRecommend
 * @datetime 2015-11-6  14:08:00
 * @author hailin3
 */
class PlannerRecommend {
    const CRON_NO = 1008;
    public function __construct() {
        ;
    }

    /**
     * 更新推荐理财师缓存
     */
    public function updateRecommendList(){        
        try{
            $plannerlist = Planner::model()->getPlannerRecommandList();
            $redis_key = "lcs_planner_recommand_top_20";                   
            Yii::app()->redis_w->set($redis_key,serialize($plannerlist));                        
            Cron::model()->saveCronLog(self::CRON_NO, CLogger::LEVEL_INFO, 'lcs_planner_recommand_top_20:'.serialize($plannerlist));
        } catch (Exception $ex) {
            throw LcsException::errorHandlerOfException($ex);
        }        
    }
}
