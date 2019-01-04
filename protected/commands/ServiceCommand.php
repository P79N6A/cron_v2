<?php

/**
 * vip service相关定时人物
 */
class ServiceCommand extends LcsConsoleCommand {

    public function init() {
        Yii::import('application.commands.service.*');
    }

    public function actionTest(){
        echo 'hello asura';
    }

    /**
     * 更新用户订阅
     */
    public function actionUpdateSub() {
        try{
            $cmn_obj = new UpdateSub();  
            $cmn_obj->process();
        }catch (Exception $e) {
            var_dump($e->getMessage());
            Cron::model()->saveCronLog(UpdateSub::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }
	
	}
}
