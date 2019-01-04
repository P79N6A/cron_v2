<?php
/**
 * 观点和观点包定时任务入口  任务编号  1201 - 1299
 * User: zwg
 * Date: 2015/11/03
 * Time: 17:34
 */

class ViewCommand extends LcsConsoleCommand {

    public function init(){
        Yii::import('application.commands.view.*');
    }

    /**
     *1201 定时修复数据:lcs_package.comment_num 
     *@author liyong3
     */
    public function actionUpdatePkgComment() {
    	try{
    		$pc = new UpdatePkgComment();
    		$pc->update();
    		$this->monitorLog(UpdatePkgComment::CRON_NO);
    	}catch(Exception $e) {
    		Cron::model()->saveCronLog(UpdatePkgComment::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
    	}
    }
    /**
     * 1202 从redis中取数据入到临时的数据库
     */
    public function actionRecordClick() {
    	try{
    		$num = 2000;
    		$rc = new RecordClick();
    		$rc->process($num);
    		$this->monitorLog(RecordClick::CRON_NO);
    	}catch(Exception $e) {
    		Cron::model()->saveCronLog(RecordClick::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
    	}
    }
    
    /**
     * 1203 
     */
    public function actionSumClick() {
    	try{
    		$dt = "now";
    		$sc = new SumClick();
    		$sc->process($dt);
    		$this->monitorLog(SumClick::CRON_NO);
    	}catch(Exception $e) {
    		Cron::model()->saveCronLog(SumClick::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
    	}
    }
    
    /**
     *  1204 24小时点击数据清理定时任务
     *  1. 备份24hr点击到历史表
     *  2. 清理24hr点击临时表
     *
     */
    public function actionClearClick() {
    	try{
    		$b_date=4;  //清理几天前的数据 默认4 最小2
    		$check=0;   //只是查询需要清理的数据, 0直接清理
    		$cc = new ClearClick();
    		$cc->process($b_date, $check);
    		$this->monitorLog(ClearClick::CRON_NO);
    	}catch(Exception $e) {
    		Cron::model()->saveCronLog(ClearClick::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
    	}
    }
    
    /**
     * 1205 观点表的阅读数:从redis到DB
     * 15分钟执行一次
     */
    public function actionViewClick() {
    	try{
    		$vc = new ViewClick();
    		$vc->process();
    		$this->monitorLog(ViewClick::CRON_NO);
    	}catch(Exception $e) {
    		Cron::model()->saveCronLog(ViewClick::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
    	}
    }
    
    /**
     * 1206 统计两个月（60）天内观点包的观点数
     * 一天执行一次
     */
    public function actionViewNumOf2Month() {
        try{
            $vc = new ViewNumOf2Month();
            $vc->process();
            $this->monitorLog(ViewNumOf2Month::CRON_NO);
        }catch(Exception $e) {
            Cron::model()->saveCronLog(ViewNumOf2Month::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    /**
     * 1207 若视频观点已转码，则发布之。
     */
    public function actionCheckVideoView() {
    	try {
    		$cv = new CheckVideoView();
    		$cv->process();
    		$this->monitorLog(CheckVideoView::CRON_NO);
    	}catch (Exception $e) {
    		Cron::model()->saveCronLog(CheckVideoView::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
    	}
    }

    /**
     * 1208 推送观点到新财讯
     *
     */
    public function actionPushView(){
    	try {
    		$cv = new PushViewToCaiXun();
    		$cv->process();
    		$this->monitorLog(PushViewToCaiXun::CRON_NO);
    	}catch (Exception $e) {
    		Cron::model()->saveCronLog(PushViewToCaiXun::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
    	}
    }

    /**
     * 1209 从新财讯同步观点
     *
     */
    public function actionGetView(){
    	try {
    		$cv = new GetViewFromCaixun();
    		$cv->process();
    		$this->monitorLog(GetViewFromCaixun::CRON_NO);
    	}catch (Exception $e) {
    		Cron::model()->saveCronLog(GetViewFromCaixun::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
    	}
    }

    /**
     * 1210 从新财讯同步观点专题
     *
     */
    public function actionGetViewZhuanti(){
    	try {
    		$cv = new GetViewZhuanti();
    		$cv->process();
    		$this->monitorLog(GetViewZhuanti::CRON_NO);
    	}catch (Exception $e) {
    		Cron::model()->saveCronLog(GetViewZhuanti::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
    	}
    }

    public function actionTestMail() {
        try{
            $sendMail = new NewSendMail('test','<p>line 1</p><p>line 2</p>', array('liyong3@staff.sina.com.cn'));
        }catch(Exception $e) {
            error_log($e->getMessage());
        }
    }

    /**
     * 点赞机器人
     *
     */
    public function actionZanRobot(){
        try {
            $zan = new ZanRobot();
            $zan->process();
            $this->monitorLog(ZanRobot::CRON_NO);
        }catch (Exception $e) {
            Cron::model()->saveCronLog(ZanRobot::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    /**
     * 小程序深度观点推送
     */
    public function actionDepthView(){
        try{
            $DepthView = new DepthViewMessage();
            $DepthView->pushView();
            $this->monitorLog(DepthViewMessage::CRON_NO);
        }catch (Exception $e){
            Cron::model()->saveCronLog(DepthViewMessage::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

}
