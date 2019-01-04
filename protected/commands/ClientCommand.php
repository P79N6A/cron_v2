<?php
/**
 * @description    客户端相关定时任务
 * @author         shixi_danxian
 * @date           2016/4/5
 */
class ClientCommand extends LcsConsoleCommand
{
    public function init(){
        Yii::import('application.commands.client.*');
    }

    public function actionClientGuideCycleHandle()
    {
        try {
            $client_guide = new ClientGuideCycleHandle();
            $client_guide->updateCycleTime();
            $this->monitorLog(ClientGuideCycleHandle::CRON_NO);
        } catch(Exception $e) {
            Cron::model()->saveCronLog(ClientGuideCycleHandle::CRON_NO, CLogger::LEVEL_ERROR, LcsException::errorHandlerOfException($e)->toJsonString());
        }

    }
}