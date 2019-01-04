<?php
/**
 * 无处安放的定时任务
 */

class ThirdCommand extends LcsConsoleCommand {

    public function init(){
        Yii::import('application.commands.third.*');
        Yii::import('application.components.CommonUtils*');
    }

    /**
     * 推送49800套餐订阅的计划信息
     */
    public function actionPush49800() {
        try{
            $op = new Push49800();
            $op->process();
        }catch(Exception $e) {
            var_dump($e->getMessage());
            Cron::model()->saveCronLog(Push49800::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
    }
}
