<?php

/**
 * Desc  : 发送手机短信
 * Author: meixin
 * Date  : 2016-1-6 16:32:13
 */
class SendSmsCommand extends LcsConsoleCommand{
    public function init(){
		Yii::import('application.commands.sendSms.*');
	}
    
    public function actionSendSms() {
        try{
            $cmn_obj = new SendSms();  
            $stop_time = time()+59;                 
            while(true) {
                if(time()>$stop_time){                    
                    break;
                }
                $fail_num = $cmn_obj->sendPhoneMsg();
                sleep(1);
                //记录任务结束时间
                $this->monitorLog(SendSms::CRON_NO);  //update  cron监控任务表 
                if($fail_num > 0 ){
                    Cron::model()->saveCronLog(SendSms::CRON_NO, CLogger::LEVEL_INFO, '发送手机验证码失败条数: '.$fail_num);
                }
            }
        }catch (Exception $e) {
            Cron::model()->saveCronLog(SendSms::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
	
	}
}
