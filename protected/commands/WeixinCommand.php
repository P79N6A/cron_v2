<?php
/**
 * 微信相关的定时任务 1900 1999
 * User: zwg
 * Date: 2015/11/03
 * Time: 17:34
 */

class WeixinCommand extends LcsConsoleCommand {

    public function init(){
        Yii::import('application.commands.weixin.*');
    }

    /**
     *1901 更新微信公众平台TOKEN
     *@author weiguang3
     */
    public function actionUpdatePFToken() {
    	try{
    		$p = new UpdatePFToken();
    		$p->process();
    		$this->monitorLog(UpdatePFToken::CRON_NO);
    	}catch(Exception $e) {
    		Cron::model()->saveCronLog(UpdatePFToken::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
    	}
    }

	/**
	 * 1902 更新微信第三方平台 component_access_token
	 * 小于2h执行一次,系统定为5min
	 *
	 * @author danxian
	 */
	public function actionUpdateComponentToken() {
		try {
			$p = new UpdateComponentToken();
			$p->process();
			$this->monitorLog(UpdateComponentToken::CRON_NO);
		} catch(Exception $e) {
			Cron::model()->saveCronLog(UpdateComponentToken::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
		}
	}

	/**
	 * 1903 更新微信第三方平台 pre_auth_code
	 * 小于10min更新一次，系统定为 5min
	 *
	 * @author danxian
	 */
	public function actionUpdatePreAuthCode() {
		try {
			$p = new UpdatePreAuthCode();
			$p->process();
			$this->monitorLog(UpdatePreAuthCode::CRON_NO);
		} catch(Exception $e) {
			Cron::model()->saveCronLog(UpdatePreAuthCode::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
		}
	}

    /**
     * 1904 更新公众号资料
     * 一天更新一次
     *
     * @author danxian
     */
	public function actionUpdateWxInfo() {
		try {
            $p = new UpdateWxInfo();
			$p->process();
			$this->monitorLog(UpdateWxInfo::CRON_NO);
		} catch(Exception $e) {
			Cron::model()->saveCronLog(UpdateWxInfo::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
		}
	}

    /**
     * 1905 更新公众号授权token
     * 2h过期， 系统5min更新一次
     *
     * @author danxian
     */
	public function actionUpdateAuthAccessToken() {
        try {
            $p = new UpdateAuthAccessToken();
            $p->process();
            $this->monitorLog(UpdateAuthAccessToken::CRON_NO);
        } catch(Exception $e) {
            Cron::model()->saveCronLog(UpdateAuthAccessToken::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

}
