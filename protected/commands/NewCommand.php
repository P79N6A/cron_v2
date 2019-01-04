<?php
/**
 * 新闻的定时任务入口  任务编号  1601 - 1699
 * User: xiaocong
 * Date: 2017/12/19
 * Time: 17:32
 */

class NewCommand extends LcsConsoleCommand {

    public function init(){
        Yii::import('application.commands.new.*');
    }

    /**
     * 1601 从新财讯同步新闻
     *
     */
    public function actionGetNew(){
    	try {
    		$cv = new GetNewFromCaixun();
    		$cv->process();
    		$this->monitorLog(GetNewFromCaixun::CRON_NO);
    	}catch (Exception $e) {
    		Cron::model()->saveCronLog(GetNewFromCaixun::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
    	}
    }
}
