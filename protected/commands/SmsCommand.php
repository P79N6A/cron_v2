<?php

/**
 * 短信批处理命令进程
 * @author lixiang23 <lixiang23@staff.sina.com.cn>
 * @copyright (c) 20161107
 */
class SmsCommand extends LcsConsoleCommand {

    public function init() {
        Yii::import('application.commands.sms.*');
    }

    public function actionTest(){
        echo 'hello asura';
    }
    
    /**
     * 批处理短信发送,长期运行不间断
     */
    public function actionProcessSms() {
        try {
            $sms_handler = new SmsHandler();
            $res = $sms_handler->processAll();
            //记录任务结束时间
            $this->monitorLog(SmsHandler::CRON_NO);
        } catch (Exception $e) {
            Cron::model()->saveCronLog(SmsHandler::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    /**
     * 发送未来某个时刻需要发送的短信
     */
    public function actionSendDelaySms() {
        try {
            $delay_sms_handler = new SendDelaySms();
            $res = $delay_sms_handler->process();
            //记录任务结束时间
            $this->monitorLog(SendDelaySms::CRON_NO);
        } catch (Exception $e) {
            Cron::model()->saveCronLog(SendDelaySms::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

    /**
     * 快速发送短信通道
     */
    public function actionProcessFastSms() {
        try {
            $sms_handler = new SmsHandler();
            $res = $sms_handler->processFast();
            //记录任务结束时间
            $this->monitorLog(SmsHandler::CRON_NO);
        } catch (Exception $e) {
            Cron::model()->saveCronLog(SmsHandler::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }

}
