<?php
/**
 * weibo 更换token
 * User: lixiang23
 * Date: 2017/03/27
 * Time: 17:34
 */

class WeiboCommand extends LcsConsoleCommand {

    public function init(){
        Yii::import('application.commands.weibo.*');
    }

    /**
     *11001 更新weibo token,每天晚上运行一次
     *@author lixiang23
     */
    public function actionUpdateWBToken() {
    	try{
    		$p = new RefreshToken();
    		$p->process();
    		$this->monitorLog(RefreshToken::CRON_NO);
    	}catch(Exception $e) {
    		Cron::model()->saveCronLog(RefreshToken::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
    	}
    }

}
