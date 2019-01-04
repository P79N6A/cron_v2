<?php
/**
 * 圈子相关定时任务
 * @author yougang1
 *
 */
class CircleCommand extends LcsConsoleCommand
{
    
    public function init()
    {
        Yii::import('application.commands.circle.*');
    }
    
    /**
     * 定时打乱推荐圈子顺序
     */
    public function actionShuffleRecommendCircle()
    {
        try {

            $circle = new ShuffleRecommendCircle();
            $circle->ShuffleCircle();
            
            //记录任务结束时间
            $this->monitorLog(ShuffleRecommendCircle::CRON_NO);  //update  cron监控任务表
            Cron::model()->saveCronLog(ShuffleRecommendCircle::CRON_NO, CLogger::LEVEL_INFO, '更新时间：'.date('Y-m-d H:i:s'));
            
        } catch (Exception $e) {
            
            Cron::model()->saveCronLog(ShuffleRecommendCircle::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }
    /**
    * 更新圈子用户的服务状态
    */
    public function actionUpdateCircleUser(){
        try {
            $circle = new UpdateCircleUser();
            $circle->handle();
            
            //记录任务结束时间
            $this->monitorLog(UpdateCircleUser::CRON_NO);  //update  cron监控任务表
            Cron::model()->saveCronLog(UpdateCircleUser::CRON_NO, CLogger::LEVEL_INFO, '更新时间：'.date('Y-m-d H:i:s'));
            
        } catch (Exception $e) {
            
            Cron::model()->saveCronLog(UpdateCircleUser::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    /**
     * 变更用户到期直播间
     */
    public function actionExpireUserCircle(){
        try{
            $start_time = date('Y-m-d H:i').':00';
            $end_time = date('Y-m-d H:i',strtotime("+ 10 minute")).':00';
            $circle = new ChangeUserCircle();
            $circle->expireChange($start_time,$end_time);

        } catch (Exception $e){

            Cron::model()->saveCronLog(ChangeUserCircle::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    /**
     * 变更用户到期直播间
     */
    public function actionRenewUserCircle(){
        try{
            $start_time = date('Y-m-d H:i',strtotime("- 5 minute")).":00";
            $start_time = '2018-10-24 16:22:00';
            $end_time = date('Y-m-d H:i').":00";
            $circle = new ChangeUserCircle();
            $circle->renewChange($start_time,$end_time);

        } catch (Exception $e){

            Cron::model()->saveCronLog(ChangeUserCircle::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }
}