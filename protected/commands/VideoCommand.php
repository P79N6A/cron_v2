<?php
/**
 * 视频观看人数统计
 * User: haohao
 * Date: 2015/12/01
 * Time: 11:34
 */

class VideoCommand extends LcsConsoleCommand {

    public function init(){
        Yii::import('application.commands.video.*');
    }

    /**
     *在线人数统计
     *@author liyong3
     * 2001
     */
    public function actionOnline() {
    	try{
    		$online = new Online();
			$online->update();
    		$this->monitorLog(Online::CRON_NO);
    	}catch(Exception $e) {
    		Cron::model()->saveCronLog(Online::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
    	}
    }
    
    /**
     * 更改理财师直播的直播状态
     * 2002
     */
    public function actionChangeLiveStatus(){            
    	try{
    		$live = new ChangeLiveStatus();
			$live->update();
            $live->updatePlannerLastId();            
    		$this->monitorLog(ChangeLiveStatus::CRON_NO);
    	}catch(Exception $e) {
    		Cron::model()->saveCronLog(ChangeLiveStatus::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
    	}
    }

    /**
     * 开始直播
     * 2003
     */
    public function actionStartLive()
    {
        try {
            $live = new Live();
            $live->startLive(); // 开启直播
            $live->prolongLive(); // 延长直播 暂时放这里 后期逻辑复杂 单独提取
            $live->remindPlanner(); // 提前10、30分钟通知理财师
            #$live->startKtLive(); // 股商合作 徐小明、 冯矿伟通知
            $this->monitorLog(Live::CRON_NO);
        } catch (Exception $e) {
            Cron::model()->saveCronLog(Live::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    /**
     *
     * 视频同步
     */
    public function actionVideoList()
    {
        try {
            $obj = new VideoList();
            $obj->handle();
            $this->monitorLog(VideoList::CRON_NO);
        } catch (Exception $e) {
            Cron::model()->saveCronLog(Live::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }
    /**
     *修复点播视频图片阿里云获取失败
     */
    public function actionUpdateVideoImages()
    {
        try {
            $obj = new UpdateVideoImages();
            $obj->handle();
            $this->monitorLog(UpdateVideoImages::CRON_NO);
        } catch (Exception $e) {
            Cron::model()->saveCronLog(Live::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }
    public function actionMonitor(){
        $m = new VideoMonitor();
        $m->monitor();
    }
}
