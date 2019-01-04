<?php
/**
 * 问答定时任务入口
 * User: zwg
 * Date: 2015/5/18
 * Time: 17:34
 */

class AskCommand extends LcsConsoleCommand {

    public function init(){
        Yii::import('application.commands.ask.*');
    }

    /**
     * 1101
     * 理财师问答表的限时折扣提问数量清零
     */
    public function actionClearDiscountQNum(){
        try{
            $clearDiscountQNum = new ClearDiscountQNum();
            $records = $clearDiscountQNum->clear();
            //记录任务结束时间
            $this->monitorLog(ClearDiscountQNum::CRON_NO);
            if($records){
                Cron::model()->saveCronLog(ClearDiscountQNum::CRON_NO, CLogger::LEVEL_INFO, 'update records:'.$records);
            }
        }catch (Exception $e) {
            Cron::model()->saveCronLog(ClearDiscountQNum::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }


    }

    /**
     * 1102
     * 理财师抢答记录超时处理
     */
    public function actionClearGrabTimeout(){
        try{
            $clearGrabTimeout = new ClearGrabTimeout();
            $records = $clearGrabTimeout->clear();
            //记录任务结束时间
            $this->monitorLog(ClearGrabTimeout::CRON_NO);
            if($records){
                Cron::model()->saveCronLog(ClearGrabTimeout::CRON_NO, CLogger::LEVEL_INFO, 'update records:'.$records);
            }
        }catch (Exception $e) {
            Cron::model()->saveCronLog(ClearGrabTimeout::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }


    }


    /**
     * 1103
     * 确认抢答问题答案  10分钟确认一下
     */
    public function actionConfirmGrabAnswer(){
        try{
            $confirmGrabAnswer = new ConfirmGrabAnswer();
            $records = $confirmGrabAnswer->confirm();
            //记录任务结束时间
            $this->monitorLog(ConfirmGrabAnswer::CRON_NO);
            if($records){
                Cron::model()->saveCronLog(ConfirmGrabAnswer::CRON_NO, CLogger::LEVEL_INFO, 'update records:'.$records);
            }
        }catch (Exception $e) {
            Cron::model()->saveCronLog(ConfirmGrabAnswer::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }


    }


    /**
     * 1104
     * 理财师抢答记录超时处理 1分钟一次
     */
    public function actionGrabNoAnswerTimeout(){
        try{
            $grabNoAnswerTimeout = new GrabNoAnswerTimeout();
            $records = $grabNoAnswerTimeout->updateToTop();
            //记录任务结束时间
            $this->monitorLog(GrabNoAnswerTimeout::CRON_NO);
            if($records){
                Cron::model()->saveCronLog(GrabNoAnswerTimeout::CRON_NO, CLogger::LEVEL_INFO, 'update records:'.$records);
            }
        }catch (Exception $e) {
            Cron::model()->saveCronLog(GrabNoAnswerTimeout::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }


    }

    /**
     * 1105
     * 计算折扣中的理财师和即将折扣的理财师
     */
    public function actionDiscountPlanner(){
        try{
            $discountPlanner = new DiscountPlanner();
            $records = $discountPlanner->discounting();
            //记录任务结束时间
            $this->monitorLog(DiscountPlanner::CRON_NO);
            if($records){
                Cron::model()->saveCronLog(DiscountPlanner::CRON_NO, CLogger::LEVEL_INFO, 'update records:'.$records);
            }
        }catch (Exception $e) {
            Cron::model()->saveCronLog(DiscountPlanner::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }


    }


    /**
     * 1106
     * 问题超时 修改更新时间
     */
    public function actionQuestionTimeout(){
        try{
            $questionTimeout = new QuestionTimeout();
            $records = $questionTimeout->updateOfTimeout();
            //记录任务结束时间
            $this->monitorLog(QuestionTimeout::CRON_NO);
            if($records){
                Cron::model()->saveCronLog(QuestionTimeout::CRON_NO, CLogger::LEVEL_INFO, 'update records:'.$records);
            }
        }catch (Exception $e) {
            Cron::model()->saveCronLog(QuestionTimeout::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }


    /**
     * 1107
     * 未评价的问题到期后自动评级为好评
     */
    public function actionQuestionAutoScore(){
        try{
            $questionAutoScore = new QuestionAutoScore();
            $records = $questionAutoScore->autoScore();
            //记录任务结束时间
            $this->monitorLog(QuestionAutoScore::CRON_NO);
            if($records){
                Cron::model()->saveCronLog(QuestionAutoScore::CRON_NO, CLogger::LEVEL_INFO, 'update records:'.$records);
            }
        }catch (Exception $e) {
            Cron::model()->saveCronLog(QuestionAutoScore::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }


    /**
     * 1108
     * 修改理财师持有免费问题数量
     */
    public function actionUpdateHoldQNum(){
        try{
            $updateHoldQNum = new UpdateHoldQNum();
            $records = $updateHoldQNum->update();
            //记录任务结束时间
            $this->monitorLog(UpdateHoldQNum::CRON_NO);
            if($records){
                Cron::model()->saveCronLog(UpdateHoldQNum::CRON_NO, CLogger::LEVEL_INFO, 'update records:'.$records);
            }
        }catch (Exception $e) {
            Cron::model()->saveCronLog(UpdateHoldQNum::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    /**
     * 1109 生成百度xml文件
     */
    public function actionBaiduXml() {
    	try{
    		$bdXml = new BaiduXml();
    		$bdXml->create();
    		//记录任务结束时间
    		$this->monitorLog(BaiduXml::CRON_NO);
    	}catch (Exception $e) {
    		Cron::model()->saveCronLog(BaiduXml::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
    	}
    }

    /**
     * 1110
     * 抢答的问题被运营删除后，清理抢答问题表里面的数据
     */
    public function actionClearGrabDeleted()
    {
        try{
            $clearGrab = new ClearGrabDeleted();
            $clearGrab->clear();
            //记录任务结束时间
            $this->monitorLog(ClearGrabDeleted::CRON_NO);
        }catch (Exception $e) {
            Cron::model()->saveCronLog(ClearGrabDeleted::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }


}